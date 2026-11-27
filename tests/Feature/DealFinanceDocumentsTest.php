<?php

namespace Tests\Feature;

use App\Models\{Deal, DealFinanceDocument, DealProposal, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Tests\TestCase;

class DealFinanceDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Storage::fake('public');
        foreach (['deal_finance_documents','deal_proposals','deals','users','user_permissions','role_permissions'] as $table) Schema::dropIfExists($table);
        Schema::create('users', function (Blueprint $t) {
            $t->id();$t->string('name');$t->string('email');$t->string('role');$t->boolean('is_active')->default(true);
            $t->boolean('must_change_password')->default(false);$t->timestamps();
        });
        Schema::create('user_permissions', function (Blueprint $t) {
            $t->id();$t->unsignedBigInteger('user_id');$t->boolean('access_finance')->default(false);$t->boolean('create_finance')->default(false);$t->boolean('access_deals')->default(false);
        });
        Schema::create('role_permissions', function (Blueprint $t) {$t->id();$t->string('role');$t->boolean('create_finance')->default(false);$t->boolean('access_finance')->default(false);$t->boolean('access_deals')->default(false);});
        Schema::create('deals', function (Blueprint $t) {$t->id();$t->string('deal_name');$t->timestamps();});
        Schema::create('deal_proposals', function (Blueprint $t) {
            $t->id();$t->unsignedBigInteger('deal_id');$t->boolean('is_current')->default(true);$t->integer('proposal_version')->default(1);
            foreach (['quotation_finance_file_path','quotation_client_file_path','invoice_file_path','quotation_status','quotation_approved_at','status'] as $col) $t->text($col)->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_18_000001_create_deal_finance_documents_table.php'))->up();
        $this->actingAs(User::create(['name'=>'Finance','email'=>'finance@example.test','role'=>'Employee']));
        DB::table('user_permissions')->insert(['user_id'=>auth()->id(),'access_finance'=>true,'create_finance'=>true]);
    }

    private function deal(): Deal
    {
        $id=DB::table('deals')->insertGetId(['deal_name'=>'Finance upload test','created_at'=>now(),'updated_at'=>now()]);
        return Deal::findOrFail($id);
    }

    private function pdf(string $name='document.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name,"%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_both_types_keep_originals_and_classification_after_reopening(): void
    {
        $deal=$this->deal();
        foreach (['quotation','payment_notice'] as $type) {
            $response=$this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>$type,'document'=>$this->pdf($type.'.pdf')])->assertCreated();
            $record=DealFinanceDocument::findOrFail($response->json('id'));
            $this->assertSame($type,$record->document_type);$this->assertSame($type.'.pdf',$record->original_name);
            $this->assertSame(auth()->id(),$record->uploaded_by);Storage::disk('public')->assertExists($record->path);
            $this->assertStringStartsWith('%PDF',Storage::disk('public')->get($record->path));
            $file=$this->get(route('deals.finance-documents.file',[$deal,$record->id]))->assertOk()->assertHeader('content-type','application/pdf');
            $this->assertStringContainsString('inline',$file->headers->get('content-disposition'));
            $download=$this->get(route('deals.finance-documents.file',[$deal,$record->id,'download'=>1]))->assertOk();
            $this->assertStringContainsString($type.'.pdf',$download->headers->get('content-disposition'));
        }
        $list=$this->getJson(route('deals.finance-documents.index',$deal))->assertOk()->json('documents');
        $this->assertCount(2,$list);$this->assertSame(['payment_notice','quotation'],array_column($list,'document_type'));
        $this->assertSame(['payment_notice.pdf','quotation.pdf'],array_column($list,'original_name'));
        $this->assertDatabaseCount('deal_finance_documents',2);
    }

    public function test_classification_is_required_and_invalid_files_are_rejected_without_storage(): void
    {
        $deal=$this->deal();$url=route('deals.finance-documents.store',$deal);
        $this->postJson($url,['document'=>$this->pdf()])->assertUnprocessable()->assertJsonValidationErrors('document_type');
        $this->postJson($url,['document_type'=>'invoice','document'=>$this->pdf()])->assertUnprocessable()->assertJsonValidationErrors('document_type');
        $this->postJson($url,['document_type'=>'quotation'])->assertUnprocessable()->assertJsonValidationErrors('document');
        $fake=UploadedFile::fake()->createWithContent('bad.png','<?php echo 1;');
        $spoof=new UploadedFile($fake->getPathname(),'bad.png',null,null,true);
        $this->postJson($url,['document_type'=>'quotation','document'=>$spoof])->assertUnprocessable();
        $this->postJson($url,['document_type'=>'quotation','document'=>UploadedFile::fake()->create('huge.pdf',10241,'application/pdf')])->assertUnprocessable();
        $this->assertDatabaseCount('deal_finance_documents',0);$this->assertSame([],Storage::disk('public')->allFiles());
    }

    public function test_read_only_staff_clients_and_disabled_users_cannot_upload(): void
    {
        $deal=$this->deal();
        foreach ([['Employee',true],['Client',true],['SuperAdmin',false]] as [$role,$active]) {
            $user=User::create(['name'=>'Denied','email'=>uniqid().'@example.test','role'=>$role,'is_active'=>$active]);
            DB::table('user_permissions')->insert(['user_id'=>$user->id,'access_finance'=>true,'create_finance'=>false]);
            $this->actingAs($user)->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'quotation','document'=>$this->pdf()])->assertForbidden();
        }
        $this->assertDatabaseCount('deal_finance_documents',0);
    }

    public function test_files_are_scoped_to_the_deal_and_missing_files_do_not_return_another_document(): void
    {
        $deal=$this->deal();$other=$this->deal();
        $id=$this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'payment_notice','document'=>$this->pdf()])->assertCreated()->json('id');
        $this->get(route('deals.finance-documents.file',[$other,$id]))->assertNotFound();
        Storage::disk('public')->delete(DealFinanceDocument::findOrFail($id)->path);
        $this->getJson(route('deals.finance-documents.index',$deal))->assertOk()->assertJsonPath('documents.0.available',false);
        $this->get(route('deals.finance-documents.file',[$deal,$id]))->assertNotFound();
    }

    public function test_previously_uploaded_documents_are_listed_and_reopened_by_their_existing_classification(): void
    {
        $deal=$this->deal();$other=$this->deal();
        foreach (['finance.pdf','client.pdf','invoice.pdf'] as $path) Storage::disk('public')->put('legacy/'.$path,'%PDF-1.4 legacy');
        $proposal=DealProposal::withoutEvents(fn()=>DealProposal::create(['deal_id'=>$deal->id,'quotation_finance_file_path'=>'legacy/finance.pdf','quotation_client_file_path'=>'legacy/client.pdf','invoice_file_path'=>'legacy/invoice.pdf']));
        $list=$this->getJson(route('deals.finance-documents.index',$deal))->assertOk()->json('documents');
        $this->assertSame(['quotation','quotation','payment_notice'],array_column($list,'document_type'));
        foreach ($list as $record) {$this->assertTrue($record['legacy']);$this->get($record['url'])->assertOk();}
        $this->get(route('deals.finance-documents.file',[$other,'legacy-'.$proposal->id.'-finance']))->assertNotFound();
        $this->assertDatabaseCount('deal_finance_documents',0);
    }

    public function test_quotation_updates_existing_workflow_and_payment_notice_does_not_overwrite_invoice(): void
    {
        $deal=$this->deal();
        $proposal=DealProposal::withoutEvents(fn()=>DealProposal::create(['deal_id'=>$deal->id,'invoice_file_path'=>'previous/invoice.pdf']));
        $id=$this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'quotation','document'=>$this->pdf()])->assertCreated()->json('id');
        $this->assertSame(DealFinanceDocument::findOrFail($id)->path,$proposal->fresh()->quotation_finance_file_path);
        $this->assertSame('uploaded',$proposal->fresh()->quotation_status);
        $this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'payment_notice','document'=>$this->pdf()])->assertCreated();
        $this->assertSame('previous/invoice.pdf',$proposal->fresh()->invoice_file_path);
        $this->assertCount(3,$this->getJson(route('deals.finance-documents.index',$deal))->json('documents'));
        $proposal->update(['quotation_status'=>'approved']);
        $this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'quotation','document'=>$this->pdf()])->assertUnprocessable();
        $this->assertDatabaseCount('deal_finance_documents',2);
    }

    public function test_original_image_is_served_as_image_and_word_is_downloaded(): void
    {
        $deal=$this->deal();
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jHioAAAAASUVORK5CYII=');
        $id=$this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'payment_notice','document'=>UploadedFile::fake()->createWithContent('notice.png',$png)])->assertCreated()->json('id');
        $this->get(route('deals.finance-documents.file',[$deal,$id]))->assertOk()->assertHeader('content-type','image/png');
        $temporary=tempnam(sys_get_temp_dir(),'finance-word-');
        try {
            $zip=new \ZipArchive();$zip->open($temporary,\ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml','<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Original quotation</w:t></w:r></w:p></w:body></w:document>');
            $zip->close();
            $wordFile=new UploadedFile($temporary,'Original.docx',null,null,true);
            $id=$this->postJson(route('deals.finance-documents.store',$deal),['document_type'=>'quotation','document'=>$wordFile])->assertCreated()->json('id');
            $record=DealFinanceDocument::findOrFail($id);
            $this->assertSame(hash_file('sha256',$temporary),hash('sha256',Storage::disk('public')->get($record->path)));
        } finally { if (file_exists($temporary)) unlink($temporary); }
        $response=$this->get(route('deals.finance-documents.file',[$deal,$record->id]))->assertOk();
        $this->assertStringContainsString('attachment',$response->headers->get('content-disposition'));
    }

    public function test_finance_views_compile_and_keep_a_single_finance_tab(): void
    {
        foreach (['deals.show','deals.partials.finance-documents'] as $view) {
            $compiled=app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.str_replace('.','/',$view).'.blade.php')));
            $this->assertNotEmpty(token_get_all($compiled,TOKEN_PARSE));
        }
        $view=file_get_contents(resource_path('views/deals/show.blade.php'));
        $this->assertSame(1,substr_count($view,'id="tab-panel-finance"'));
        $this->assertStringContainsString('onclick="openFinanceDocumentUpload()"',$view);
    }
    public function test_legacy_quotation_upload_uses_shared_storage_and_preserves_the_previous_original(): void
    {
        $deal=$this->deal();Storage::disk('public')->put('legacy/old-quote.pdf','%PDF-1.4 old quotation');
        $proposal=DealProposal::withoutEvents(fn()=>DealProposal::create(['deal_id'=>$deal->id,'quotation_finance_file_path'=>'legacy/old-quote.pdf']));
        $this->post(route('deals.quotation.upload-finance',$deal),['quotation_file'=>$this->pdf('New Quotation.pdf')])->assertSessionHasNoErrors()->assertRedirect(route('deals.quotation.finance',$deal));
        $this->assertDatabaseCount('deal_finance_documents',2);
        $records=$this->getJson(route('deals.finance-documents.index',$deal))->assertOk()->json('documents');
        $this->assertSame(['New Quotation.pdf','old-quote.pdf'],array_column($records,'original_name'));
        foreach ($records as $record) $this->get($record['url'])->assertOk();
        Storage::disk('public')->assertExists('legacy/old-quote.pdf');
    }

}
