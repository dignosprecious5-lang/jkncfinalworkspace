<?php

namespace Tests\Feature;

use App\Models\{Deal, DealProposal, Product, Service, User};
use App\Services\{DealCatalogSelection, ProductCatalogConfiguration, ServiceCatalogConfiguration};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Notification, Schema, Storage};
use Tests\TestCase;

class DealCatalogSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        Notification::fake();
        $definitions = [
            'products' => 'linked_service_id linked_service_ids requirements name product_name sku service_area category scope_of_work deliverables client_responsibilities exclusions status price pricing_model currency tax_treatment payment_structure description product_description',
            'product_requirements' => 'product_id name description requirement_type client_type source evidence_type file_required is_mandatory validity_expiration instructions sequence status',
            'product_terms' => 'product_id title content sort_order status scope scope_service_area scope_category',
            'service_areas' => 'name slug status',
            'services' => 'name service_name service_code service_id service_area service_area_other service_area_id category status price_fee active_version_id service_description short_description tax_type engagement_behavior engagement_structure',
            'service_versions' => 'requirements service_id version_number status is_active standard_price scope_of_work deliverables client_responsibilities exclusions currency pricing_model tax_treatment payment_structure',
            'service_requirements' => 'service_id service_version_id requirement_name client_type status source file_required is_mandatory validity_expiration instructions',
            'service_terms' => 'service_version_id title content sort_order scope status',
            'service_term_library' => 'title content scope status sort_order service_area_id category',
            'users' => 'name email password role is_active must_change_password',
            'user_permissions' => 'user_id access_deals create_sales_marketing',
            'role_permissions' => 'role access_deals create_sales_marketing',
            'accounts' => 'account_name account_code account_type status company_id individual_contact_id',
            'companies' => 'company_name primary_contact_id status', 'contacts' => 'first_name last_name email status',
            'employees' => 'user_id position',
            'deal_contacts' => 'deal_id contact_type salutation first_name middle_name last_name name_extension email mobile_number address position is_primary',
            'deal_histories' => 'deal_id user_id user_name activity_type title description old_stage new_stage old_value new_value metadata',
            'deal_stage_histories' => 'deal_id stage started_at ended_at user_id user_name notes',
            'deal_proposals' => 'deal_id status client_form_sent_at client_approved_at catalog_snapshot line_items_snapshot price_total scope_of_service what_you_will_receive document_html',
            'deals' => 'contact_id deal_name stage service_area services products scope_of_work engagement_type requirements_status required_actions estimated_professional_fee estimated_government_fees estimated_service_support_fee total_estimated_engagement_value payment_terms payment_terms_other planned_start_date estimated_duration estimated_completion_date client_preferred_completion_date confirmed_delivery_date timeline_notes service_complexity support_required complexity_notes proposal_decision decline_reason assigned_consultant assigned_associate service_department_unit consultant_notes associate_notes customer_type salutation first_name middle_name last_name email mobile address company_name company_address position deal_code created_by deal_status stage_id total_service_fee total_product_fee other_fees qualification_result qualification_notes approved_at approved_by_name rejected_at rejected_by_name rejection_reason deal_discount assigned_finance_user_id prepared_by reviewed_by internal_name internal_date client_fullname_signature referred_closed_by internal_sales_marketing lead_consultant lead_associate_assigned internal_finance internal_president delete_request_status delete_requested_by delete_requested_at stage_entered_at inquiry_details inquiry_records consultation_records payment_status payment_reference activation_date assigned_team assigned_person service_start_date activation_notes closed_won_date closed_lost_date lost_reason closing_notes client_need decision_maker consultation_date consultation_type requirements_confirmed proposal_number proposal_date proposal_value proposal_valid_until proposal_status proposal_notes negotiation_status final_deal_value pricing_model negotiation_notes payment_method payment_amount payment_date account_id company_id deal_title sex middle_initial date_of_birth mobile_number company primary_contact_name service_areas services_products est_professional_fee discount owner_name inquiry_source inquiry_date name_extension amount commercial_stage client_search pipeline_stage expected_close approval_date record_custodian date_recorded date_signed referred_by sales_marketing lead_associate finance president assigned_user_id responsible_user_id reviewer_id approver_id assigned_user_ids assigned_by assigned_at start_batches casa_stage',
        ];
        foreach ($definitions as $name => $columns) {
            Schema::dropIfExists($name);
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach (explode(' ', $columns) as $column) { $table->text($column)->nullable(); }
                $table->timestamps();
            });
        }
        DB::table('accounts')->insert(['id'=>1,'account_name'=>'Test Client','account_type'=>'Individual','status'=>'Active']);
        $this->actingAs(User::create(['name'=>'Selection Admin','email'=>'select@example.test','role'=>'SuperAdmin','is_active'=>true,'must_change_password'=>false]));
    }

    private function product(string $name = 'Configured Product', string $status = 'Active'): Product
    {
        return Product::create(['name'=>$name,'sku'=>'P-'.(Product::count()+1),'status'=>$status,'price'=>125,
            'service_area'=>'Corporate','category'=>'Documents','scope_of_work'=>$name.' scope','deliverables'=>$name.' output',
            'client_responsibilities'=>$name.' client duty','exclusions'=>$name.' exclusion','currency'=>'PHP']);
    }

    private function service(string $status = 'active'): Service
    {
        $id=DB::table('services')->insertGetId(['name'=>'Configured Service','service_name'=>'Configured Service','service_code'=>'SVC-1','status'=>$status,'service_area'=>'["Corporate"]','category'=>'Advisory','service_area_id'=>7,'price_fee'=>10]);
        $version=DB::table('service_versions')->insertGetId(['service_id'=>$id,'version_number'=>'V1','standard_price'=>300,'scope_of_work'=>'Service master scope','deliverables'=>'Service master output','client_responsibilities'=>'Service client duty','exclusions'=>'Service exclusion']);
        DB::table('services')->where('id',$id)->update(['active_version_id'=>$version]);
        DB::table('service_requirements')->insert(['service_id'=>$id,'service_version_id'=>$version,'requirement_name'=>'Service evidence','client_type'=>'All','status'=>'Active','file_required'=>1,'is_mandatory'=>1]);
        DB::table('service_terms')->insert(['service_version_id'=>$version,'title'=>'Service clause','content'=>'Specific service content','scope'=>'service_specific','status'=>'active','sort_order'=>1]);
        return Service::findOrFail($id);
    }

    private function createPayload(array $values = []): array
    {
        return array_merge(['account_id'=>1,'customer_type'=>'Individual','pipeline_stage'=>'Inquiry','deal_title'=>'Selection Deal','first_name'=>'Test','last_name'=>'Client','catalog_selection_present'=>1],$values);
    }

    public function test_new_form_lists_all_active_products_and_both_active_service_spellings(): void
    {
        $a=$this->product('First product');$b=$this->product('Second product');$this->product('Hidden product','inactive');$this->service('active');
        $response=$this->get(route('deals.create'))->assertOk();
        $response->assertSee('First product')->assertSee('Second product')->assertDontSee('Product: First product')->assertDontSee('Hidden product');
        $response->assertDontSee('PRODUCT CATALOG')->assertSee('Others');
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*type="checkbox"[^>]*name="product_ids\[\]"/s', $response->getContent());
        $this->assertSame(['Configured Service'],array_column($response->viewData('regularDealServiceMap')['Corporate'],'name'));
        $this->assertSame([$a->id,$b->id],array_column($response->viewData('dealProductCatalog'),'id'));
        DB::table('services')->update(['status'=>'Active']);
        $this->assertCount(1,app(DealCatalogSelection::class)->choices()['services']);
    }

    public function test_selection_preview_reads_each_master_and_does_not_write_records(): void
    {
        $product=$this->product();$service=$this->service();
        $product->requirements()->create(['name'=>'Product evidence','client_type'=>'Individual','sequence'=>1,'status'=>'Active']);
        $product->terms()->create(['title'=>'Product clause','content'=>'Product content','scope'=>'service_specific','status'=>'active']);
        $this->postJson(route('deals.catalog-configuration'),['customer_type'=>'Individual','catalog_selection_present'=>1,'service_ids'=>[$service->id],'product_ids'=>[$product->id]])
            ->assertOk()->assertJsonPath('items.0.type','SERVICE')->assertJsonPath('items.0.scope_of_work','Service master scope')
            ->assertJsonPath('items.0.requirements.0.origin','service')->assertJsonPath('items.0.requirements.0.status','Pending')
            ->assertJsonPath('items.0.terms.0.content','Specific service content')->assertJsonPath('items.0.commercial.price',300)
            ->assertJsonPath('items.1.type','PRODUCT')->assertJsonPath('items.1.scope_of_work','Configured Product scope')
            ->assertJsonPath('items.1.requirements.0.origin','product')->assertJsonPath('items.1.terms.0.content','Product content')
            ->assertJsonPath('items.1.commercial.price',125);
        $this->assertDatabaseCount('deals',0);$this->assertDatabaseCount('deal_proposals',0);
        $this->assertDatabaseCount('products',1);$this->assertDatabaseCount('services',1);
    }

    public function test_real_create_save_reopen_and_resubmit_preserve_unique_items_and_pipeline(): void
    {
        $a=$this->product('Product A');$b=$this->product('Product B');$service=$this->service();
        $payload=$this->createPayload(['service_ids'=>[$service->id,$service->id],'product_ids'=>[$a->id,$b->id]]);
        $this->post(route('deals.store'),$payload)->assertRedirect();
        $deal=Deal::firstOrFail();$items=$deal->services_products;
        $this->assertCount(3,$items);$this->assertSame($service->id,$items[0]['service_id']);
        $this->assertSame([$a->id,$b->id],array_column(array_slice($items,1),'product_id'));
        $this->assertSame(300.0,(float)$items[0]['unitPrice']);$this->assertSame(125.0,(float)$items[1]['unitPrice']);
        $this->assertSame('Inquiry',$deal->pipeline_stage);$this->assertDatabaseCount('deal_proposals',0);
        $this->assertStringContainsString('Product A scope',$deal->scope_of_work);
        $this->assertStringContainsString('Product B scope',$deal->scope_of_work);
        $this->assertStringContainsString('Service master scope',$deal->scope_of_work);
        $reopened=$this->get(route('deals.create',['deal'=>$deal->id]))->assertOk()->assertSee('Product A')->assertSee('Product B');
        foreach ([$a->id,$b->id] as $productId) {
            $reopened->assertSee('<input type="hidden" name="product_ids[]" value="'.$productId.'"',false);
        }
        $this->post(route('deals.store'),$payload)->assertRedirect();
        $this->assertDatabaseCount('deals',1);$this->assertDatabaseCount('products',2);$this->assertDatabaseCount('services',1);
        $catalog=app(ProductCatalogConfiguration::class)->forDeal($deal->fresh());
        $this->assertCount(2,$catalog['products']);$this->assertCount(1,$catalog['services']);
    }

    public function test_invalid_malformed_and_inactive_ids_are_rejected_before_any_write(): void
    {
        $inactive=$this->product('Inactive','inactive');$service=$this->service('inactive');
        foreach ([['product_ids'=>[$inactive->id]],['service_ids'=>[$service->id]],['product_ids'=>[9999]],['product_ids'=>'bad'],['service_ids'=>['1.5']]] as $bad) {
            $this->postJson(route('deals.store'),$this->createPayload($bad))->assertUnprocessable();
        }
        $this->assertDatabaseCount('deals',0);$this->assertDatabaseCount('deal_contacts',0);
    }

    public function test_edit_retains_existing_inactive_items_but_cannot_add_other_inactive_items(): void
    {
        $retained=$this->product('Retained');$other=$this->product('Other inactive','inactive');
        $this->post(route('deals.store'),$this->createPayload(['product_ids'=>[$retained->id]]))->assertRedirect();
        $deal=Deal::firstOrFail();$lineId=$deal->services_products[0]['id'];
        DB::table('products')->where('id',$retained->id)->update(['status'=>'inactive']);
        $this->put(route('deals.update',$deal),['catalog_selection_present'=>1,'product_ids'=>[$retained->id]])->assertRedirect();
        $this->assertSame($lineId,$deal->fresh()->services_products[0]['id']);
        $this->putJson(route('deals.update',$deal),['catalog_selection_present'=>1,'product_ids'=>[$retained->id,$other->id]])->assertUnprocessable();
        $this->assertCount(1,$deal->fresh()->services_products);
    }

    public function test_deselect_all_preserves_requirements_and_approved_catalog_snapshot(): void
    {
        $product=$this->product();$service=$this->service();
        $this->post(route('deals.store'),$this->createPayload(['product_ids'=>[$product->id],'service_ids'=>[$service->id],'requirements'=>['Existing Deal requirement']]))->assertRedirect();
        $deal=Deal::firstOrFail();$requirements=$deal->getRawOriginal('requirements_status');
        $proposal=DealProposal::create(['deal_id'=>$deal->id,'status'=>'draft']);$proposal->update(['status'=>'approved']);
        $snapshot=$proposal->fresh()->catalog_snapshot;
        $this->assertSame('Service master scope',$snapshot['services'][0]['scope_of_work']);
        $this->put(route('deals.update',$deal),['catalog_selection_present'=>1])->assertRedirect();
        $this->assertSame([],$deal->fresh()->services_products);
        $this->assertSame($requirements,$deal->fresh()->getRawOriginal('requirements_status'));
        $this->assertSame($snapshot,$proposal->fresh()->catalog_snapshot);
        $this->assertDatabaseCount('service_requirements',1);
        $this->assertSame('Inquiry',$deal->fresh()->pipeline_stage);
    }

    public function test_partial_product_edit_preserves_services_prices_and_existing_line_ids(): void
    {
        $a=$this->product();$b=$this->product('New product');$service=$this->service();
        $this->post(route('deals.store'),$this->createPayload(['product_ids'=>[$a->id],'service_ids'=>[$service->id]]))->assertRedirect();
        $deal=Deal::firstOrFail();$serviceLine=$deal->services_products[0];
        $this->put(route('deals.update',$deal),['product_ids'=>[$b->id]])->assertRedirect();
        $items=$deal->fresh()->services_products;
        $this->assertSame($serviceLine['id'],$items[0]['id']);$this->assertSame($serviceLine['unitPrice'],$items[0]['unitPrice']);
        $this->assertSame($b->id,$items[1]['product_id']);
    }

    public function test_legacy_unlinked_items_are_not_retroactively_forced_into_the_catalog(): void
    {
        $legacy=Deal::withoutEvents(fn () => Deal::create(['deal_name'=>'Legacy','customer_type'=>'Individual','pipeline_stage'=>'Inquiry','services_products'=>[['id'=>'legacy-service','type'=>'SERVICE','name'=>'Legacy custom service','unitPrice'=>45]]]));
        $this->get(route('deals.create',['deal'=>$legacy->id]))->assertOk()->assertSee('Legacy custom service');
        $this->put(route('deals.update',$legacy),['catalog_selection_present'=>1,'services'=>['Legacy custom service']])->assertRedirect();
        $line=$legacy->fresh()->services_products[0];
        $this->assertSame('legacy-service',$line['id']);$this->assertSame('Legacy custom service',$line['name']);
        $this->assertEmpty($line['service_id'] ?? null);$this->assertSame(45.0,(float)$line['unitPrice']);
        $this->assertDatabaseCount('services',0);$this->assertDatabaseCount('products',0);
    }

    public function test_service_scoped_terms_use_the_existing_priority_and_client_applicability(): void
    {
        $service=$this->service();
        DB::table('service_term_library')->insert([
            ['title'=>'Shared heading','content'=>'Global','scope'=>'global','status'=>'active','sort_order'=>1,'service_area_id'=>null,'category'=>null],
            ['title'=>'Shared heading','content'=>'Category override','scope'=>'category','status'=>'active','sort_order'=>1,'service_area_id'=>null,'category'=>'Advisory'],
            ['title'=>'Area clause','content'=>'Area content','scope'=>'service_area','status'=>'active','sort_order'=>2,'service_area_id'=>7,'category'=>null],
        ]);
        DB::table('service_terms')->insert(['service_version_id'=>$service->active_version_id,'title'=>'Shared heading','content'=>'Specific override','scope'=>'service_specific','status'=>'active']);
        DB::table('service_requirements')->insert(['service_id'=>$service->id,'service_version_id'=>$service->active_version_id,'requirement_name'=>'Business only','client_type'=>'Business','status'=>'Active']);
        $configuration=app(ServiceCatalogConfiguration::class)->forService($service,'Individual');
        $terms=collect($configuration['terms'])->keyBy('title');
        $this->assertSame('Specific override',$terms['Shared heading']['content']);$this->assertSame('Area content',$terms['Area clause']['content']);
        $this->assertSame(['Service evidence'],array_column($configuration['requirements'],'name'));
    }

    public function test_later_service_edits_do_not_rewrite_an_issued_service_snapshot(): void
    {
        $service=$this->service();
        $this->post(route('deals.store'),$this->createPayload(['service_ids'=>[$service->id]]))->assertRedirect();
        $deal=Deal::firstOrFail();$proposal=DealProposal::create(['deal_id'=>$deal->id,'status'=>'draft']);
        $proposal->update(['status'=>'sent','client_form_sent_at'=>now()]);
        DB::table('service_versions')->where('id',$service->active_version_id)->update(['scope_of_work'=>'Changed service scope']);
        $this->assertSame('Service master scope',app(ProductCatalogConfiguration::class)->forDeal($deal,$proposal->fresh())['services'][0]['scope_of_work']);
        $this->assertSame('Changed service scope',app(ProductCatalogConfiguration::class)->forDeal($deal)['services'][0]['scope_of_work']);
    }

    public function test_client_disabled_and_unpermitted_users_cannot_select_or_save(): void
    {
        foreach ([['role'=>'Client','is_active'=>true],['role'=>'Employee','is_active'=>false],['role'=>'Employee','is_active'=>true]] as $attributes) {
            $user=User::create(array_merge(['name'=>'Denied','email'=>uniqid().'@example.test'],$attributes));
            DB::table('user_permissions')->insert(['user_id'=>$user->id,'access_deals'=>0,'create_sales_marketing'=>0]);
            $this->actingAs($user);
            $this->get(route('deals.create'))->assertForbidden();
            $this->postJson(route('deals.catalog-configuration'),['customer_type'=>'Individual'])->assertForbidden();
            $this->postJson(route('deals.store'),$this->createPayload())->assertForbidden();
        }
        $this->assertDatabaseCount('deals',0);
    }

    public function test_new_deal_blade_compiles_to_valid_php(): void
    {
        $compiled=app('blade.compiler')->compileString(file_get_contents(resource_path('views/deals/create.blade.php')));
        $this->assertNotEmpty(token_get_all($compiled,TOKEN_PARSE));
        $document = app('blade.compiler')->compileString(file_get_contents(resource_path('views/deals/proposal/partials/document.blade.php')));
        $this->assertNotEmpty(token_get_all($document,TOKEN_PARSE));
    }

    public function test_authorized_staff_can_select_without_catalog_master_edit_permissions(): void
    {
        $user=User::create(['name'=>'Sales','email'=>'sales@example.test','role'=>'Employee','is_active'=>true]);
        DB::table('user_permissions')->insert(['user_id'=>$user->id,'access_deals'=>1,'create_sales_marketing'=>1]);
        $product=$this->product();$this->actingAs($user);
        $this->postJson(route('deals.catalog-configuration'),['customer_type'=>'Individual','product_ids'=>[$product->id]])->assertOk();
        $this->post(route('deals.store'),$this->createPayload(['product_ids'=>[$product->id]]))->assertRedirect();
        $this->assertDatabaseCount('deals',1);
    }

    public function test_explicit_catalog_selection_does_not_adopt_an_existing_custom_item_of_the_same_name(): void
    {
        $product=$this->product();
        $deal=Deal::withoutEvents(fn () => Deal::create(['deal_name'=>'Legacy','pipeline_stage'=>'Inquiry','customer_type'=>'Individual','services_products'=>[['id'=>'old-custom','type'=>'PRODUCT','name'=>$product->name,'custom'=>true,'unitPrice'=>9]]]));
        $this->put(route('deals.update',$deal),['catalog_selection_present'=>1,'product_ids'=>[$product->id],'products'=>[$product->name]])->assertRedirect();
        $rows=$deal->fresh()->services_products;
        $this->assertCount(2,$rows);$this->assertSame($product->id,$rows[0]['product_id']);$this->assertFalse($rows[0]['custom']);
        $this->assertSame('old-custom',$rows[1]['id']);$this->assertTrue($rows[1]['custom']);
        $this->assertSame(9.0,(float)$rows[1]['unitPrice']);
        $this->assertCount(1,app(ProductCatalogConfiguration::class)->forDeal($deal->fresh())['products']);
    }

    public function test_new_deal_selected_sources_reach_the_actual_proposal_preview_and_pdf(): void
    {
        Storage::fake('public');
        $product=$this->product();$service=$this->service();
        $product->requirements()->create(['name'=>'Product only evidence','client_type'=>'Individual','sequence'=>1,'status'=>'Active']);
        $product->terms()->create(['title'=>'Product agreement','content'=>'Product-only agreement content','scope'=>'service_specific','status'=>'active']);
        $this->post(route('deals.store'),$this->createPayload(['product_ids'=>[$product->id],'service_ids'=>[$service->id]]))->assertRedirect();
        $deal=Deal::firstOrFail();
        $controller=app(\App\Http\Controllers\DealProposalController::class);
        $data=(new \ReflectionMethod($controller,'documentData'))->invoke($controller,$deal,new DealProposal());
        $html=(new \ReflectionMethod($controller,'resolveProposalDocumentHtml'))->invoke($controller,$data,null,false);
        $this->assertStringContainsString('Configured Product output',$data['what_you_will_receive']);
        $this->assertStringContainsString('Service master output',$data['what_you_will_receive']);
        foreach (['Service master scope','Service master output','Service client duty','Service exclusion','Configured Product scope','Configured Product output','Configured Product client duty','Configured Product exclusion','Service evidence','Product only evidence','Specific service content','Product-only agreement content'] as $text) {
            $this->assertStringContainsString($text,$html);
        }
        $pdf=(new \ReflectionMethod($controller,'generateProposalPdf'))->invoke($controller,array_merge($data,['document_html'=>$html]),'deal-catalog-preview.docx');
        $this->assertStringStartsWith('%PDF-',Storage::disk('public')->get($pdf));
        $this->assertDatabaseCount('deal_proposals',0);
    }

    public function test_catalog_checkboxes_preserve_multiple_existing_pricing_instances(): void
    {
        $product=$this->product();$service=$this->service();
        $rows=[
            ['id'=>'service-a','type'=>'SERVICE','service_id'=>$service->id,'name'=>$service->name,'qty'=>1,'unitPrice'=>30],
            ['id'=>'service-b','type'=>'SERVICE','service_id'=>$service->id,'name'=>$service->name,'qty'=>2,'unitPrice'=>40],
            ['id'=>'product-a','type'=>'PRODUCT','product_id'=>$product->id,'name'=>$product->name,'qty'=>1,'unitPrice'=>10],
            ['id'=>'product-b','type'=>'PRODUCT','product_id'=>$product->id,'name'=>$product->name,'qty'=>3,'unitPrice'=>20],
        ];
        $deal=Deal::withoutEvents(fn () => Deal::create(['deal_name'=>'Multiple instances','customer_type'=>'Individual','pipeline_stage'=>'Inquiry','services_products'=>$rows]));
        $this->put(route('deals.update',$deal),['catalog_selection_present'=>1,'service_ids'=>[$service->id],'product_ids'=>[$product->id]])->assertRedirect();
        $saved=$deal->fresh()->services_products;
        $this->assertCount(4,$saved);$this->assertSame(array_column($rows,'id'),array_column($saved,'id'));
        $this->assertSame(array_column($rows,'qty'),array_column($saved,'qty'));
        $this->assertSame(array_column($rows,'unitPrice'),array_column($saved,'unitPrice'));
        foreach ($saved as $index => $row) { $this->assertSame($rows[$index]['unitPrice'], $row['price'] ?? $row['unitPrice']); }
        $catalog=app(ProductCatalogConfiguration::class)->forDeal($deal->fresh());
        $this->assertCount(1,$catalog['products']);$this->assertCount(1,$catalog['services']);
    }

    public function test_missing_legacy_master_is_shown_as_unlinked_without_resubmitting_an_invalid_id(): void
    {
        $deal=Deal::withoutEvents(fn () => Deal::create(['deal_name'=>'Removed service master','customer_type'=>'Individual','pipeline_stage'=>'Inquiry','services_products'=>[['id'=>'old-service','type'=>'SERVICE','service_id'=>999999,'name'=>'Previously saved service','unitPrice'=>7]]]));
        $this->get(route('deals.create',['deal'=>$deal->id]))->assertOk()->assertSee('Previously saved service')->assertDontSee('value="999999"',false);
        $this->put(route('deals.update',$deal),['catalog_selection_present'=>1,'services'=>['Previously saved service']])->assertRedirect();
        $row=$deal->fresh()->services_products[0];
        $this->assertSame('old-service',$row['id']);$this->assertSame(999999,$row['service_id']);
        $this->assertDatabaseCount('services',0);
    }

    public function test_line_item_catalog_ids_and_custom_edits_persist_without_cross_association(): void
    {
        $product = $this->product();
        $service = $this->service();
        $this->post(route('deals.store'), $this->createPayload())->assertRedirect();
        $deal = Deal::latest('id')->firstOrFail();
        $history = \Mockery::mock(\App\Services\DealHistoryService::class);
        $history->shouldReceive('logNoteAdded')->times(4);
        app()->instance(\App\Services\DealHistoryService::class, $history);
        foreach (['SERVICE' => $service, 'PRODUCT' => $product] as $type => $record) {
            $key = strtolower($type).'_id';
            $payload = ['type'=>$type, $key=>$record->id, 'custom'=>false, 'name'=>'Ignored client name',
                'description'=>'Agreed scope', 'qty'=>2, 'unit_price'=>75, 'billing'=>'Full Payment Before Service'];
            $request = \Illuminate\Http\Request::create('/line-items','POST',$payload);
            $request->headers->set('Accept','application/json');
            $result = app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id)->getData(true)['item'];
            $this->assertSame($record->id, $result[$key]);
            $this->assertSame($record->name, $result['name']);
            $this->assertFalse($result['custom']);
            $saved = collect($deal->fresh()->services_products)->firstWhere('id',$result['id']);
            $this->assertSame($record->id,$saved[$key]);
            $payload = array_merge($payload,['id'=>$result['id'],'custom'=>true,$key=>null,'name'=>'Custom '.$type]);
            $request = \Illuminate\Http\Request::create('/line-items','POST',$payload);
            $request->headers->set('Accept','application/json');
            $result = app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id)->getData(true)['item'];
            $this->assertTrue($result['custom']);
            $this->assertArrayNotHasKey('service_id',$result);
            $this->assertArrayNotHasKey('product_id',$result);
            $this->assertSame('Custom '.$type,$result['name']);
        }
        $this->assertCount(2,$deal->fresh()->services_products);
        $this->assertSame(1,Product::count());
        $this->assertSame(1,Service::count());
        $this->assertSame(0,DealProposal::count());
    }

    public function test_line_item_rejects_invalid_catalog_identity(): void
    {
        $product=$this->product('Inactive','Inactive');
        $this->post(route('deals.store'),$this->createPayload())->assertRedirect();
        $deal=Deal::latest('id')->firstOrFail();
        $request=\Illuminate\Http\Request::create('/line-items','POST',[
            'type'=>'PRODUCT','product_id'=>$product->id,'name'=>'Inactive','qty'=>1,'unit_price'=>10,'billing'=>'Full Payment Before Service']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id);
    }

    public function test_picker_exposes_actual_links_and_saves_product_service_context_without_a_second_billed_service(): void
    {
        $product=$this->product();$service=$this->service();$other=$this->service();
        DB::table('products')->where('id',$product->id)->update(['linked_service_id'=>$service->id,'linked_service_ids'=>json_encode([$service->id]),'scope_of_work'=>null,'product_description'=>'Legacy Product specification']);
        $choices=app(DealCatalogSelection::class)->choices();
        $this->assertSame([$service->id],$choices['products'][0]['linked_service_ids']);
        $this->assertSame('Legacy Product specification',$choices['products'][0]['desc']);
        $this->post(route('deals.store'),$this->createPayload())->assertRedirect();$deal=Deal::firstOrFail();
        $history=\Mockery::mock(\App\Services\DealHistoryService::class);$history->shouldReceive('logNoteAdded')->twice();
        app()->instance(\App\Services\DealHistoryService::class,$history);
        $payload=['type'=>'PRODUCT','product_id'=>$product->id,'associated_service_id'=>$service->id,
            'name'=>'Ignored name','description'=>'Edited scope','qty'=>2,'unit_price'=>125,'discount'=>10,'tax'=>5,'billing'=>'Full Payment Before Service'];
        $request=\Illuminate\Http\Request::create('/line-items','POST',$payload);$request->headers->set('Accept','application/json');
        $item=app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id)->getData(true)['item'];
        $this->assertSame($product->id,$item['product_id']);$this->assertSame($service->id,$item['associated_service_id']);
        $this->assertArrayNotHasKey('service_id',$item);$this->assertSame('Edited scope',$item['description']);
        $this->assertSame(245.0,(float)$deal->fresh()->amount);$this->assertCount(1,$deal->fresh()->services_products);
        $resolved=app(\App\Services\DealProposalAggregationService::class)->resolveDealLineItems($deal->fresh());
        $this->assertSame($service->id,$resolved[0]['associated_service_id']);$this->assertSame('PRODUCT',$resolved[0]['type']);
        $controller=app(\App\Http\Controllers\DealProposalController::class);
        $snapshot=(new \ReflectionMethod($controller,'snapshotDealLineItems'))->invoke($controller,$deal->fresh());
        $this->assertSame($service->id,$snapshot[0]['associated_service_id']);
        $this->assertSame($product->id,$snapshot[0]['product_id']);
        DB::table('services')->where('id',$service->id)->update(['status'=>'Inactive']);
        $retained=collect(app(DealCatalogSelection::class)->choices($deal->fresh())['services'])->firstWhere('id',$service->id);
        $this->assertNotNull($retained);$this->assertTrue($retained['retained']);$this->assertFalse($retained['selectable']);
        $payload=array_merge($payload,['id'=>$item['id'],'product_id'=>null,'associated_service_id'=>null,'custom'=>true,'name'=>'Custom binder']);
        $request=\Illuminate\Http\Request::create('/line-items','POST',$payload);$request->headers->set('Accept','application/json');
        $item=app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id)->getData(true)['item'];
        $this->assertTrue($item['custom']);$this->assertArrayNotHasKey('product_id',$item);$this->assertArrayNotHasKey('associated_service_id',$item);
        $this->assertDatabaseCount('products',1);$this->assertDatabaseCount('services',2);$this->assertDatabaseCount('deal_proposals',0);
    }

    public function test_picker_rejects_an_unrelated_service_context_before_saving(): void
    {
        $product=$this->product();$service=$this->service();$other=$this->service();
        DB::table('products')->where('id',$product->id)->update(['linked_service_ids'=>json_encode([$service->id])]);
        $this->post(route('deals.store'),$this->createPayload())->assertRedirect();$deal=Deal::firstOrFail();
        $request=\Illuminate\Http\Request::create('/line-items','POST',[
            'type'=>'PRODUCT','product_id'=>$product->id,'associated_service_id'=>$other->id,
            'name'=>'Product','qty'=>1,'unit_price'=>10,'billing'=>'Full Payment Before Service']);
        try { app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id); $this->fail('Unrelated Service accepted.'); }
        catch (\Illuminate\Validation\ValidationException $exception) { $this->assertArrayHasKey('associated_service_id',$exception->errors()); }
        $this->assertSame([],$deal->fresh()->services_products);
    }

    public function test_identifications_resolve_foreign_keys_and_legacy_memberships_without_losing_items(): void
    {
        DB::table('service_areas')->insert([
            ['id'=>7,'name'=>'Accounting Advisory','slug'=>'accounting','status'=>'active'],
            ['id'=>8,'name'=>'Business Consulting','slug'=>'business','status'=>'active'],
            ['id'=>9,'name'=>'Empty Identification','slug'=>'empty','status'=>'active'],
        ]);
        $first=$this->service(); $second=$this->service(); $legacy=$this->service();
        DB::table('services')->where('id',$second->id)->update(['name'=>'Business Strategy','service_area_id'=>8]);
        DB::table('services')->where('id',$legacy->id)->update(['name'=>'Legacy Training','service_area_id'=>null,'service_area'=>json_encode(['Business Consulting','Legacy Advisory'])]);
        $a=$this->product('Linked Product');$b=$this->product('Independent Product');
        DB::table('products')->where('id',$a->id)->update(['linked_service_ids'=>json_encode([$first->id])]);
        $choices=app(DealCatalogSelection::class)->choices();
        $services=collect($choices['services'])->keyBy('id');
        $this->assertCount(3,$services);$this->assertCount(2,$choices['products']);
        $this->assertSame([['id'=>7,'name'=>'Accounting Advisory']],$services[$first->id]['service_identifications']);
        $this->assertSame([['id'=>8,'name'=>'Business Consulting']],$services[$second->id]['service_identifications']);
        $this->assertSame([['id'=>8,'name'=>'Business Consulting'],['id'=>null,'name'=>'Legacy Advisory']],$services[$legacy->id]['service_identifications']);
        $this->assertContains('Empty Identification',array_column($choices['service_identifications'],'name'));
        $this->assertNotContains('Corporate',array_column($choices['service_identifications'],'name'));
        // Existing deployments also store plain text areas and have no area master records.
        DB::table('service_areas')->delete();
        DB::table('services')->where('id',$first->id)->update(['service_area'=>'Plain Text Identification']);
        $services=collect(app(DealCatalogSelection::class)->choices()['services'])->keyBy('id');
        $this->assertSame([['id'=>null,'name'=>'Plain Text Identification']],$services[$first->id]['service_identifications']);
    }

    public function test_line_item_identification_is_derived_from_database_and_cleared_when_type_changes(): void
    {
        DB::table('service_areas')->insert(['id'=>7,'name'=>'Accounting Advisory','slug'=>'accounting','status'=>'active']);
        $service=$this->service();$product=$this->product();
        $this->post(route('deals.store'),$this->createPayload(['service_ids'=>[$service->id]]))->assertRedirect();
        $deal=Deal::firstOrFail();$initial=$deal->services_products[0];
        $this->assertSame([['id'=>7,'name'=>'Accounting Advisory']],$initial['service_identifications']);
        $history=\Mockery::mock(\App\Services\DealHistoryService::class);$history->shouldReceive('logNoteAdded')->times(3);
        app()->instance(\App\Services\DealHistoryService::class,$history);
        $payload=['type'=>'SERVICE','service_id'=>$service->id,'name'=>'Client supplied name','qty'=>2,'unit_price'=>75,
            'billing'=>'Full Payment Before Service','route'=>'Project','service_area_id'=>999,
            'service_identifications'=>[['id'=>999,'name'=>'Forged group']]];
        $save=function ($payload) use ($deal) {
            $request=\Illuminate\Http\Request::create('/line-items','POST',$payload);$request->headers->set('Accept','application/json');
            return app(\App\Http\Controllers\DealController::class)->storeLineItem($request,$deal->id)->getData(true)['item'];
        };
        $item=$save($payload);
        $this->assertSame($service->id,$item['service_id']);$this->assertSame('SERVICE',$item['type']);
        $this->assertSame('Project',$item['route']);$this->assertSame('Configured Service',$item['name']);
        $this->assertSame(7,(int)$item['service_area_id']);
        $this->assertSame([['id'=>7,'name'=>'Accounting Advisory']],$item['service_identifications']);
        $this->assertSame($item['service_identifications'],collect($deal->fresh()->services_products)->firstWhere('id',$item['id'])['service_identifications']);
        $item=$save(array_merge($payload,['id'=>$item['id'],'type'=>'PRODUCT','service_id'=>null,'product_id'=>$product->id]));
        $this->assertSame($product->id,$item['product_id']);$this->assertSame('PRODUCT',$item['type']);
        $this->assertArrayNotHasKey('service_identifications',$item);$this->assertArrayNotHasKey('service_area_id',$item);
        $item=$save(array_merge($payload,['id'=>$item['id'],'custom'=>true,'service_id'=>null,'name'=>'Custom service']));
        $this->assertArrayNotHasKey('service_id',$item);$this->assertArrayNotHasKey('product_id',$item);
        $this->assertArrayNotHasKey('service_identifications',$item);
    }

}
