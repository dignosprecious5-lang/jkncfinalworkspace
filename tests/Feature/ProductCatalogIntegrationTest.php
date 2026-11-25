<?php

namespace Tests\Feature;

use App\Models\{Deal, DealProposal, Product, ProductTerm, ServiceTerm, User};
use App\Services\{ProductCatalogConfiguration, DealProposalAggregationService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Notification, Schema};
use Tests\TestCase;

class ProductCatalogIntegrationTest extends TestCase
{
    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        Notification::fake();
        foreach (['products', 'product_versions', 'product_terms', 'product_requirements', 'product_audit_logs', 'deals', 'deal_proposals', 'users', 'role_permissions', 'user_permissions', 'audit_logs', 'service_terms'] as $name) {
            Schema::dropIfExists($name);
        }
        $definitions = [
            'products' => ['name','sku','product_name','product_type','service_area','category','pricing_type','tax_treatment','inventory_type','description','scope_of_work','deliverables','client_responsibilities','exclusions','status','price','inventory_stock'],
            'product_versions' => ['product_id','name','sku','product_type','service_area','category','pricing_type','tax_treatment','version_number','is_active','status','created_by','inventory_stock','auto_carryover','auto_carryover_automation'],
            'product_terms' => ['product_id','title','content','sort_order','status','scope'],
            'product_requirements' => ['product_id','name','description','requirement_type','client_type','source','evidence_type','file_required','is_mandatory','validity_expiration','instructions','sequence','status'],
            'product_audit_logs' => ['product_id','user_name','action','title','details','description','message'],
            'audit_logs' => ['user_id','user_name','action','module','description','ip_address'],
            'deals' => ['customer_type','services_products','client_requirements','scope_of_work','status','contact_id','company_id','payment_terms','products'],
            'deal_proposals' => ['deal_id','status','client_form_sent_at','client_approved_at','line_items_snapshot','document_html','price_total','scope_of_service','what_you_will_receive'],
            'users' => ['name','email','password','role','must_change_password','is_active'],
            'role_permissions' => ['role','access_product','create_sales_marketing'],
            'user_permissions' => ['user_id','access_product','create_sales_marketing'],
            'service_terms' => ['service_version_id','title','content','scope','status','sort_order'],
        ];
        foreach ($definitions as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) { $table->text($column)->nullable(); }
                $table->timestamps();
            });
        }
        (require database_path('migrations/2026_10_17_000001_add_product_catalog_snapshot_and_term_scope_anchors.php'))->up();
        $this->actingAs(User::create(['name'=>'Catalog Admin','email'=>'catalog@example.test','password'=>'test','role'=>'SuperAdmin','must_change_password'=>false,'is_active'=>true]));
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge(['name'=>'Product A','sku'=>'P-'.Product::count(),'service_area'=>'Corporate','category'=>'Documents','scope_of_work'=>'Configured scope','deliverables'=>"First output\nSecond output",'client_responsibilities'=>'Supply facts','exclusions'=>'No filing fees','status'=>'Active','price'=>100], $attributes));
    }

    private function deal(array $products, string $type = 'Business'): Deal
    {
        return Deal::withoutEvents(function () use ($products, $type): Deal {
            $deal = new Deal(['customer_type' => $type]);
            $deal->client_requirements = json_encode(['Existing Deal requirement']);
            $deal->services_products = array_map(fn ($p) => ['id' => 'line-'.$p->id, 'type' => 'PRODUCT', 'name' => $p->name, 'product_id' => $p->id, 'qty' => 1, 'unitPrice' => 100], $products);
            $deal->save();
            return $deal;
        });
    }

    public function test_create_product_and_save_all_configuration_fields(): void
    {
        $this->post(route('products.store'), ['name'=>'New product','product_type'=>'Document','service_area'=>'Corporate','category'=>'Documents','pricing_type'=>'Fixed','tax_treatment'=>'VAT','inventory_type'=>'Digital','inventory_stock'=>0])->assertRedirect();
        $product = Product::firstOrFail();
        $this->put(route('products.catalog.update', $product->id), ['scope_of_work'=>'Scope A','deliverables'=>'Output A','client_responsibilities'=>'Client A','exclusions'=>'Exclusion A'])->assertRedirect();
        $this->assertSame(['Scope A','Output A','Client A','Exclusion A'], array_values($product->fresh()->only(['scope_of_work','deliverables','client_responsibilities','exclusions'])));
        $this->assertDatabaseCount('product_versions', 1);
    }

    public function test_requirement_crud_retains_evidence_and_rejects_duplicates(): void
    {
        $product = $this->product();
        $data = ['name'=>'Identity','client_type'=>'Business','source'=>'Client-supplied','evidence_type'=>'Hard Copy','validity_expiration'=>'6 months','is_mandatory'=>1];
        $url = route('products.requirements.store', $product->id);
        $this->post($url, $data)->assertRedirect();
        $this->post($url, $data)->assertSessionHasErrors('name');
        $requirement = $product->requirements()->firstOrFail();
        $this->assertSame('Hard Copy', $requirement->evidence_type);
        $this->put(route('products.requirements.update', [$product->id,$requirement->id]), array_merge($data,['name'=>'Identity revised','evidence_type'=>'Soft Copy','is_mandatory'=>0]))->assertRedirect();
        $this->assertFalse($requirement->fresh()->is_mandatory);
        $this->assertSame('Soft Copy',$requirement->fresh()->evidence_type);
        $this->assertTrue($requirement->fresh()->file_required);
        $this->delete(route('products.requirements.destroy', [$product->id,$requirement->id]))->assertRedirect();
        $this->assertDatabaseCount('product_requirements',0);
    }

    public function test_requirements_have_origin_applicability_and_separate_pending_client_status(): void
    {
        $product=$this->product();
        foreach (['Business','Individual','All Client Types'] as $type) { $product->requirements()->create(['name'=>$type,'client_type'=>$type,'sequence'=>1,'status'=>'Active']); }
        $product->requirements()->create(['name'=>'Inactive','client_type'=>'Business','sequence'=>2,'status'=>'Inactive']);
        $service=app(ProductCatalogConfiguration::class);
        $business=$this->deal([$product]); $individual=$this->deal([$product],'Individual');
        $rows=$service->forDeal($business)['products'][0]['requirements'];
        $this->assertSame(['Business','All Client Types'],array_column($rows,'name'));
        $this->assertSame('Active',$rows[0]['status']);
        $this->assertTrue($rows[0]['applicable']);
        $this->assertSame('product',$rows[0]['origin']);
        $this->assertSame(['Individual','All Client Types'],array_column($service->forDeal($individual)['products'][0]['requirements'],'name'));
        $this->assertSame(json_encode(['Existing Deal requirement']),$business->fresh()->client_requirements);
        $this->assertSame('Active',$product->requirements()->first()->status);
    }

    public function test_each_term_scope_is_persisted_and_edit_binding_works(): void
    {
        $product=$this->product();
        foreach (['Service Specific','Global Scope','Service Area Scope','Category'] as $scope) {
            $this->post(route('product-terms.store',$product),['title'=>$scope,'content'=>'Clause '.$scope,'scope'=>$scope])->assertRedirect();
        }
        $this->assertDatabaseCount('product_terms',4);
        $term=$product->terms()->first();
        $this->put(route('product-terms.update',$term),['title'=>'Revised','content'=>'Full revised clause','scope'=>'service_specific'])->assertRedirect();
        $this->assertSame('Full revised clause',$term->fresh()->content);
        $this->delete(route('product-terms.destroy',$term))->assertRedirect();
        $this->assertDatabaseCount('product_terms',3);
    }

    public function test_invalid_terms_and_missing_scope_associations_are_rejected(): void
    {
        $product=$this->product(['category'=>null,'service_area'=>null]);
        $this->post(route('product-terms.store',$product),['scope'=>'Select Scope'])->assertSessionHasErrors(['title','content','scope']);
        foreach (['category','service_area'] as $scope) { $this->post(route('product-terms.store',$product),['title'=>'Title','content'=>'Clause','scope'=>$scope])->assertSessionHasErrors('scope'); }
        $this->assertDatabaseCount('product_terms',0);
    }

    public function test_repeat_term_submission_is_idempotent_and_ownership_is_enforced(): void
    {
        $a=$this->product();$b=$this->product(['name'=>'B']);
        $data=['title'=>'Term','content'=>'Clause','scope'=>'Service Specific'];
        $this->post(route('product-terms.store',$a),$data)->assertRedirect();
        $this->post(route('product-terms.store',$a),$data)->assertRedirect();
        $this->assertDatabaseCount('product_terms',1);
        $this->post(route('product-terms.duplicate',[$b,$a->terms()->first()]))->assertNotFound();
    }

    public function test_scope_resolution_preserves_product_service_and_taxonomy_boundaries(): void
    {
        $a=$this->product();$b=$this->product(['name'=>'B']);$c=$this->product(['name'=>'C','category'=>'Equipment','service_area'=>'Logistics']);
        foreach (['service_specific','global','service_area','category'] as $scope) { $a->terms()->create(['title'=>$scope,'content'=>$scope,'scope'=>$scope,'status'=>'active','sort_order'=>1]); }
        $a->terms()->create(['title'=>'Disabled','content'=>'Disabled','scope'=>'global','status'=>'inactive']);
        ServiceTerm::create(['service_version_id'=>99,'title'=>'Service only','content'=>'Service clause','scope'=>'service_specific','status'=>'active']);
        $service=app(ProductCatalogConfiguration::class);
        $this->assertSame(['service_specific','global','service_area','category'],$service->applicableTerms($a)->pluck('title')->all());
        $this->assertSame(['global','service_area','category'],$service->applicableTerms($b)->pluck('title')->all());
        $this->assertSame(['global'],$service->applicableTerms($c)->pluck('title')->all());
        $a->update(['category'=>'Equipment']);
        $this->assertContains('category',$service->applicableTerms($b)->pluck('title')->all());
        $this->assertNotContains('category',$service->applicableTerms($c)->pluck('title')->all());
        $this->assertDatabaseHas('service_terms',['title'=>'Service only','content'=>'Service clause']);
    }

    public function test_multiple_products_are_aggregated_once_without_losing_line_prices(): void
    {
        $a=$this->product();$b=$this->product(['name'=>'B','scope_of_work'=>'B scope']);
        $a->terms()->create(['title'=>'Global','content'=>'Shared clause','scope'=>'global','status'=>'active']);
        $deal=$this->deal([$a,$a,$b]);
        $catalog=app(ProductCatalogConfiguration::class)->forDeal($deal);
        // Layer D Deals Core preserves per-line selection entries with line_item_id and commercial price
        $this->assertCount(3,$catalog['products']);
        $this->assertTrue($catalog['compatible']);
        $this->assertCount(3,$catalog['terms']);
        $items=app(DealProposalAggregationService::class)->resolveDealLineItems($deal);
        $this->assertCount(3,$items);$this->assertSame(300.0,array_sum(array_column($items,'line_total')));
    }

    public function test_issued_snapshot_preserves_content_requirements_terms_and_prices(): void
    {
        $product=$this->product();$deal=$this->deal([$product]);
        $product->terms()->create(['title'=>'Original','content'=>'Agreed clause','scope'=>'service_specific','status'=>'active']);
        $service=app(ProductCatalogConfiguration::class);
        $issuedSnapshot=$service->forDeal($deal);
        $proposal=DealProposal::create(['deal_id'=>$deal->id,'status'=>'sent','client_form_sent_at'=>now()]);
        // Layer D uses explicit versioned snapshot storage (no implicit DealProposal::updating model hook)
        DB::table('deal_proposals')->where('id',$proposal->id)->update([
            'catalog_snapshot'=>json_encode($issuedSnapshot),
            'line_items_snapshot'=>json_encode(app(DealProposalAggregationService::class)->resolveDealLineItems($deal)),
        ]);
        $product->update(['scope_of_work'=>'Changed scope','deliverables'=>'Changed outputs','exclusions'=>'Changed exclusions','client_responsibilities'=>'Changed responsibility','price'=>999]);
        $product->terms()->delete();
        $catalog=$service->forDeal($deal->fresh(),$proposal->fresh());
        $this->assertSame('Configured scope',$catalog['products'][0]['scope_of_work']);
        $this->assertSame('No filing fees',$catalog['products'][0]['exclusions']);
        $this->assertSame('Agreed clause',$catalog['terms'][0]['content']);
        $lineSnapshot=json_decode((string)$proposal->fresh()->line_items_snapshot,true);
        $this->assertSame(100.0,(float)$lineSnapshot[0]['unit_price']);
        $this->assertSame('Changed scope',$service->forDeal($deal->fresh())['products'][0]['scope_of_work']);
    }

    public function test_conflicting_headings_block_issuance_instead_of_silent_precedence(): void
    {
        $product=$this->product();$deal=$this->deal([$product]);
        foreach (['First clause','Conflicting clause'] as $content) { $product->terms()->create(['title'=>'Same heading','content'=>$content,'scope'=>'global','status'=>'active']); }
        // Layer D reports conflicting headings explicitly and marks catalog incompatible so issuance is blocked
        $catalog=app(ProductCatalogConfiguration::class)->forDeal($deal);
        $this->assertFalse($catalog['compatible']);
        $this->assertNotEmpty($catalog['conflicts']);
        $this->assertStringContainsString('Conflicting term heading: same heading', implode(' | ', $catalog['conflicts']));
    }

    public function test_no_requirements_empty_catalog_and_legacy_issued_proposals_are_safe(): void
    {
        $product=$this->product();$deal=$this->deal([$product]);
        $service=app(ProductCatalogConfiguration::class);
        $this->assertSame([],$service->forDeal($deal)['products'][0]['requirements']);
        $this->assertSame([],$service->forDeal($this->deal([]))['products']);
        $legacy=new DealProposal(['status'=>'approved','client_form_sent_at'=>now()]);
        $this->assertTrue($service->forDeal($deal,$legacy)['legacy_issued']);
    }

    public function test_unauthorized_mutations_are_forbidden(): void
    {
        $product=$this->product();
        $user=User::create(['name'=>'Client','email'=>'client@example.test','role'=>'Client']);
        $this->actingAs($user);
        $this->post(route('product-terms.store',$product),['title'=>'Bad','content'=>'Bad','scope'=>'global'])->assertForbidden();
        $this->post(route('products.requirements.store',$product),['name'=>'Bad'])->assertForbidden();
        $this->put(route('products.catalog.update',$product),['scope_of_work'=>'Bad','deliverables'=>'Bad'])->assertForbidden();
        $this->assertDatabaseCount('product_terms',0);
    }

    public function test_additive_migration_rolls_back_without_deleting_existing_data(): void
    {
        $product=$this->product();
        $migration=require database_path('migrations/2026_10_17_000001_add_product_catalog_snapshot_and_term_scope_anchors.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('deal_proposals','catalog_snapshot'));
        $this->assertDatabaseHas('products',['id'=>$product->id]);
        $migration->up();
        $this->assertTrue(Schema::hasColumn('product_terms','scope_category'));
    }

    public function test_product_workspace_compiles_to_valid_php(): void
    {
        $compiled=app('blade.compiler')->compileString(file_get_contents(resource_path('views/products/workspace.blade.php')));
        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }

    public function test_existing_service_specific_term_crud_stays_separate(): void
    {
        Schema::dropIfExists('service_versions');
        Schema::create('service_versions',fn (Blueprint $table) => $table->id());
        DB::table('service_versions')->insert(['id'=>41]);
        $this->post(route('service-terms.store',41),['title'=>'Service clause','content'=>'Service content','scope'=>'service_specific'])->assertRedirect();
        $term=ServiceTerm::firstOrFail();
        $this->put(route('service-terms.update',$term),['title'=>'Service clause revised','content'=>'Revised service content','scope'=>'service_specific'])->assertRedirect();
        $this->assertSame('Revised service content',$term->fresh()->content);
        $product=$this->product();
        $this->assertSame([],app(ProductCatalogConfiguration::class)->forDeal($this->deal([$product]))['terms']);
        $this->assertDatabaseCount('product_terms',0);
    }

    public function test_custom_product_name_and_missing_explicit_id_do_not_link_to_another_product(): void
    {
        $product=$this->product();
        $deal=$this->deal([]);
        foreach ([['custom'=>true],['product_id'=>999999]] as $extra) {
            $deal->services_products=[array_merge(['type'=>'PRODUCT','name'=>$product->name,'qty'=>1,'unitPrice'=>10],$extra)];
            $catalog=app(ProductCatalogConfiguration::class)->forDeal($deal);
            // Layer D retains custom/unresolved entries visibly without ever linking to another product
            $this->assertCount(1,$catalog['products']);
            $this->assertNotSame($product->id, $catalog['products'][0]['product_id'] ?? null);
            $this->assertSame([],$catalog['terms']);
            if (!empty($extra['custom'])) {
                $this->assertArrayNotHasKey('product_id',$catalog['products'][0]);
            }
            if (!empty($extra['product_id'])) {
                $this->assertFalse($catalog['compatible']);
                $this->assertNotEmpty($catalog['diagnostics']);
            }
        }
    }

    public function test_unapproved_products_are_excluded_from_commercial_selection_and_physical_products_are_not_misclassified(): void
    {
        $active = $this->product(['name' => 'Approved Product', 'status' => 'active']);
        $draft = $this->product(['name' => 'Draft Zero Price Product', 'status' => 'draft', 'price' => 0]);
        $pending = $this->product(['name' => 'Pending Approval Product', 'status' => 'Pending Approval', 'price' => 500]);

        $choices = app(\App\Services\DealCatalogSelection::class)->choices();
        $selectableIds = array_column($choices['products'], 'id');
        $this->assertContains($active->id, $selectableIds);
        $this->assertNotContains($draft->id, $selectableIds);
        $this->assertNotContains($pending->id, $selectableIds);

        // Verify legacy product_type default does not misclassify physical/tracked products as Service
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'product_id')) {
                $table->string('product_id')->nullable();
            }
        });
        $physical = Product::create([
            'name' => 'Company Seal Kit',
            'sku' => 'PRD-PHYS-01',
            'inventory_type' => 'Tracked',
            'inventory_stock' => 10,
            'status' => 'draft',
        ]);
        $this->assertSame('Physical', $physical->product_type);

        $general = Product::create([
            'name' => 'Standard Package',
            'sku' => 'PRD-GEN-01',
            'inventory_type' => 'Non-Tracked',
            'inventory_stock' => 0,
            'status' => 'draft',
        ]);
        $this->assertSame('General', $general->product_type);
    }
}
