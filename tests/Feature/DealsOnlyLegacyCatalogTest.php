<?php

namespace Tests\Feature;

use App\Models\{Deal, DealProposal, Product, Service};
use App\Services\{DealCatalogSelection, DealProposalAggregationService, ProductCatalogConfiguration, ServiceCatalogConfiguration};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Mail, Notification};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Tests\DealsMysqlTestCase;

/** Catalog behavior and partial-schema safeguards on the guarded disposable MySQL schema. */
class DealsOnlyLegacyCatalogTest extends DealsMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
        $this->createBaseCatalogSchema();
        DB::table('services')->insert([
            'id' => 1, 'service_id' => 'S0001', 'service_name' => 'Legacy Service', 'service_description' => 'Agreed service scope',
            'service_activity_output' => 'Configured filing output', 'service_area' => '["Tax"]',
            'engagement_structure' => '["Project Engagement"]', 'price_fee' => 0, 'tax_type' => 'VAT', 'status' => 'Active',
            'requirements' => '{"groups":{"individual":["Proof of identity"],"juridical":["Registration record"]}}',
        ]);
        DB::table('products')->insert([
            'id' => 1, 'product_id' => 'P0001', 'product_name' => 'Legacy Product', 'product_description' => 'Product scope',
            'product_inclusions' => 'Configured handover', 'product_area' => '["Corporate"]', 'price' => 25, 'tax_type' => 'Non-VAT',
            'status' => 'Active', 'linked_service_ids' => '[1]', 'linked_service_id' => 1,
            'requirements' => '{"groups":{"individual":["Delivery identity"]}}',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function service(): Service
    {
        return Service::query()->findOrFail(1);
    }

    private function product(): Product
    {
        return Product::query()->findOrFail(1);
    }

    private function deal(array $attributes = [], bool $existing = false): Deal
    {
        // setRawAttributes avoids baseline mass assignment and never saves this model.
        $deal = new Deal;
        $deal->setRawAttributes(array_merge(['id' => 40, 'customer_type' => 'Individual', 'services' => '["Legacy Service"]', 'products' => '[]'], $attributes), true);
        $deal->exists = $existing;
        return $deal;
    }

    private function snapshot(array $attributes): DealProposal
    {
        $proposal = new DealProposal;
        $proposal->setRawAttributes($attributes, true);
        return $proposal;
    }

    public function test_legacy_service_uses_master_requirements_output_zero_price_and_tax(): void
    {
        $result = app(ServiceCatalogConfiguration::class)->forService($this->service(), 'Individual');
        $this->assertSame('S0001', $result['sku']);
        $this->assertSame(['Proof of identity'], array_column($result['requirements'], 'name'));
        $this->assertSame('Configured filing output', $result['deliverables']);
        $this->assertSame(0.0, $result['commercial']['price']);
        $this->assertSame('VAT', $result['commercial']['tax_treatment']);
        $this->assertSame([], $result['activities']);
        $this->assertSame([], $result['terms']);
        $this->assertTrue($result['compatible']);
    }

    public function test_legacy_product_uses_inclusions_requirements_and_linked_service_ids(): void
    {
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame('Configured handover', $result['deliverables']);
        $this->assertSame('Product scope', $result['scope_of_work']);
        $this->assertSame('Non-VAT', $result['commercial']['tax_treatment']);
        $this->assertSame(['Delivery identity'], array_column($result['requirements'], 'name'));
        $choices = app(DealCatalogSelection::class)->choices($this->deal());
        $this->assertSame([1], $choices['products'][0]['linked_service_ids']);
        $this->assertSame(['Corporate'], $choices['products'][0]['service_area']);
    }

    public function test_old_category_items_and_other_groups_are_not_discarded(): void
    {
        $issues = [];
        $rows = ServiceCatalogConfiguration::requirements(['category' => 'SOLE / NATURAL PERSON / INDIVIDUAL', 'items' => ['Original wording']], null, 'Individual', 'service', 1, $issues);
        $this->assertSame('Original wording', $rows[0]['name']);
        $rows = ServiceCatalogConfiguration::requirements(['groups' => ['other' => ['Unmapped obligation']]], null, 'Business', 'product', 1, $issues);
        $this->assertSame('Unmapped obligation', $rows[0]['name']);
        $this->assertNull($rows[0]['applicable']);
        $this->assertNotEmpty($issues);
    }

    public function test_business_and_individual_groups_remain_distinct(): void
    {
        $result = app(ServiceCatalogConfiguration::class)->forService($this->service(), 'Business');
        $this->assertSame(['Registration record'], array_column($result['requirements'], 'name'));
    }

    public function test_malformed_requirement_json_returns_blocking_diagnostic(): void
    {
        $service = $this->service();
        $attributes = $service->getAttributes();
        $attributes['requirements'] = '{broken';
        $service->setRawAttributes($attributes);
        $result = app(ServiceCatalogConfiguration::class)->forService($service, 'Individual');
        $this->assertFalse($result['compatible']);
        $this->assertNotEmpty($result['diagnostics']);
    }

    public function test_partial_optional_schema_does_not_query_missing_filter_columns(): void
    {
        Schema::create('product_requirements', fn (Blueprint $table) => $table->id());
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame('Delivery identity', $result['requirements'][0]['name']);
        $this->assertFalse($result['compatible']);
        $this->assertStringContainsString('product_requirements', implode(' ', $result['diagnostics']));
    }

    public function test_populated_normalized_requirements_cannot_replace_legacy_obligations(): void
    {
        Schema::create('product_requirements', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('product_id'); $table->string('name');
            $table->string('client_type'); $table->string('status');
        });
        DB::table('product_requirements')->insert( ['id' => 1, 'product_id' => 1, 'name' => 'Additional obligation', 'client_type' => 'Individual', 'status' => 'Active']);
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame(['Delivery identity', 'Additional obligation'], array_column($result['requirements'], 'name'));
        $this->assertFalse($result['compatible']);
    }

    public function test_empty_optional_requirements_preserve_legacy_obligations(): void
    {
        Schema::create('product_requirements', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('product_id'); $table->string('name');
            $table->string('client_type'); $table->string('status');
        });
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame(['Delivery identity'], array_column($result['requirements'], 'name'));
        $this->assertTrue($result['compatible']);
    }

    public function test_owned_service_version_can_supply_explicit_zero_price(): void
    {
        Schema::create('service_versions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_id'); $table->decimal('standard_price', 15, 2);
        });
        DB::table('service_versions')->insert( ['id' => 5, 'service_id' => 1, 'standard_price' => 0]);
        $service = $this->service();
        $service->setRawAttributes(array_merge($service->getAttributes(), ['active_version_id' => 5]));
        $result = app(ServiceCatalogConfiguration::class)->forService($service, 'Individual');
        $this->assertSame(5, $result['service_version_id']);
        $this->assertSame(0.0, $result['commercial']['price']);
        $this->assertTrue($result['compatible']);
    }

    public function test_foreign_service_version_is_not_used(): void
    {
        Schema::create('service_versions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_id'); $table->decimal('standard_price', 15, 2);
        });
        DB::table('service_versions')->insert( ['id' => 5, 'service_id' => 99, 'standard_price' => 500]);
        $service = $this->service();
        $service->setRawAttributes(array_merge($service->getAttributes(), ['active_version_id' => 5]));
        $result = app(ServiceCatalogConfiguration::class)->forService($service, 'Individual');
        $this->assertNull($result['service_version_id']);
        $this->assertSame(0.0, $result['commercial']['price']);
        $this->assertFalse($result['compatible']);
    }

    public function test_term_scope_without_anchor_blocks_instead_of_borrowing_master_area(): void
    {
        Schema::create('product_terms', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('product_id'); $table->string('title');
            $table->text('content'); $table->string('scope'); $table->string('status'); $table->integer('sort_order');
        });
        DB::table('product_terms')->insert( ['id' => 1, 'product_id' => 1, 'title' => 'Area term', 'content' => 'Configured text', 'scope' => 'service_area', 'status' => 'Active', 'sort_order' => 1]);
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame([], $result['terms']);
        $this->assertFalse($result['compatible']);
    }

    public function test_duplicate_names_are_unresolved_instead_of_first_match(): void
    {
        DB::table('services')->insert( ['id' => 2, 'service_id' => 'S0002', 'service_name' => 'Legacy Service', 'status' => 'Active']);
        $issues = [];
        $items = app(DealProposalAggregationService::class)->resolveSelections([['type' => 'SERVICE', 'name' => 'Legacy Service']], $issues);
        $this->assertNull($items[0]['model']);
        $this->assertStringContainsString('ambiguous', implode(' ', $issues));
    }

    public function test_custom_selection_does_not_link_a_same_named_master(): void
    {
        $issues = [];
        $items = app(DealProposalAggregationService::class)->resolveSelections([['id' => 'custom-1', 'type' => 'PRODUCT', 'name' => 'Legacy Product', 'custom' => true, 'unit_price' => 0, 'qty' => 1]], $issues);
        $this->assertNull($items[0]['model']);
        $this->assertSame(0.0, $items[0]['unit_price']);
        $this->assertSame('custom-1', $items[0]['id']);
    }

    public function test_zero_and_unavailable_prices_have_distinct_meanings(): void
    {
        $product = $this->product();
        $attributes = $product->getAttributes();
        $attributes['price'] = null;
        $product->setRawAttributes($attributes);
        $result = app(ProductCatalogConfiguration::class)->forProduct($product, 'Individual');
        $this->assertNull($result['commercial']['price']);
        $this->assertFalse($result['compatible']);
    }

    public function test_repeated_pricing_instances_are_preserved_exactly(): void
    {
        $old = [['id' => 'a', 'type' => 'SERVICE', 'service_id' => 1, 'name' => 'Old A', 'price' => 10],
            ['id' => 'b', 'type' => 'SERVICE', 'service_id' => 1, 'name' => 'Old B', 'price' => 20]];
        $this->assertSame($old, app(DealCatalogSelection::class)->preserveExistingInstances([['type' => 'SERVICE', 'service_id' => 1, 'name' => 'Current']], $old));
    }

    public function test_preview_uses_explicit_arrays_without_changing_deal_fillable(): void
    {
        $before = (new Deal)->getFillable();
        $result = app(DealCatalogSelection::class)->configuration(['services' => [['id' => 1, 'name' => 'Legacy Service']], 'products' => []], 'Individual');
        $this->assertSame(1, $result['items'][0]['service_id']);
        $this->assertSame('Proof of identity', $result['items'][0]['requirements'][0]['name']);
        $this->assertSame($before, (new Deal)->getFillable());
        $this->assertNotContains('services_products', $before);
    }

    public function test_explicit_empty_selection_does_not_restore_old_names(): void
    {
        $deal = $this->deal(['services_products' => '[]']);
        $this->assertSame([], app(DealProposalAggregationService::class)->sourceRows($deal));
    }

    public function test_malformed_selection_json_is_not_reinterpreted_as_a_name(): void
    {
        $this->expectException(ValidationException::class);
        app(DealProposalAggregationService::class)->sourceRows($this->deal(['services_products' => '[broken']));
    }

    public function test_missing_table_and_missing_name_column_produce_diagnostics(): void
    {
        $issues = [];
        $this->assertSame([], ServiceCatalogConfiguration::rows('absent_catalog', ['name'], [], $issues));
        Schema::create('partial_catalog', fn (Blueprint $table) => $table->id());
        $this->assertSame([], ServiceCatalogConfiguration::rows('partial_catalog', ['name'], [], $issues));
        $this->assertNotEmpty($issues);
    }

    public function test_historical_snapshots_are_unchanged_and_make_no_catalog_queries(): void
    {
        $catalog = ['services' => [['name' => 'Historical name', 'requirements' => [['name' => 'Historical obligation']]]], 'products' => [], 'terms' => [['title' => 'Agreed', 'content' => 'Historical terms']]];
        $items = [['id' => 'old-line', 'type' => 'SERVICE', 'service_id' => 1, 'name' => 'Historical name', 'unit_price' => 7]];
        $proposal = $this->snapshot(['status' => 'accepted', 'catalog_snapshot' => json_encode($catalog), 'line_items_snapshot' => json_encode($items), 'document_html' => '<p>Issued document</p>', 'price_total' => 7, 'price_down' => 0]);
        $connection = $this->assertDealsMysqlIsolationIdentity();
        $connection->enableQueryLog();
        $connection->flushQueryLog();
        $result = app(DealProposalAggregationService::class)->aggregate($this->deal([], true), $proposal);
        $this->assertSame([], $connection->getQueryLog());
        $this->assertSame($catalog, $result['catalog']);
        $this->assertSame($items, $result['line_items']);
        $this->assertSame('<p>Issued document</p>', $result['proposal_content']['document_html']);
        $this->assertSame('0.00', $result['totals']['downpayment']);
    }

    public function test_issued_proposal_without_snapshots_uses_stored_content_only(): void
    {
        $proposal = $this->snapshot(['status' => 'accepted', 'document_html' => 'Original terms', 'scope_of_service' => 'Original scope', 'requirements_sole' => 'Original obligation']);
        $result = app(DealProposalAggregationService::class)->aggregate($this->deal(), $proposal);
        $this->assertSame([], $result['line_items']);
        $this->assertSame('Original scope', $result['proposal_content']['scope_of_work']);
        $this->assertSame('Original obligation', $result['requirements']['stored_sole']);
        $this->assertFalse($result['compatible']);
    }

    public function test_existing_hybrid_is_readable_without_activation_or_reference_changes(): void
    {
        $deal = $this->deal(['engagement_type' => 'Hybrid Project / Regular', 'deal_code' => 'UNCHANGED'], true);
        $before = $deal->getAttributes();
        $result = app(DealProposalAggregationService::class)->aggregate($deal);
        $this->assertSame('Hybrid Project / Regular', $result['engagement_route']);
        $this->assertSame(40, $result['engagement']['deal_id']);
        $this->assertSame($before, $deal->getAttributes());
        $this->assertNull($result['totals']['downpayment']);
        $this->assertSame([], $result['automation']);
    }

    public function test_new_hybrid_label_does_not_establish_a_legacy_exception(): void
    {
        $result = app(DealProposalAggregationService::class)->aggregate($this->deal(['engagement_type' => 'Hybrid']));
        $this->assertNotSame('Hybrid', $result['engagement_route']);
    }

    public function test_baseline_models_keep_write_fields_casts_and_relationships(): void
    {
        $service = new Service;
        $product = new Product;
        foreach (['service_id', 'service_name', 'requirements', 'engagement_structure', 'created_by', 'approved_by'] as $field) {
            $this->assertContains($field, $service->getFillable());
        }
        foreach (['product_id', 'product_name', 'product_area', 'product_description', 'linked_service_ids', 'created_by', 'approved_by'] as $field) {
            $this->assertContains($field, $product->getFillable());
        }
        $this->assertSame('array', $service->getCasts()['requirements']);
        $this->assertSame('array', $product->getCasts()['product_area']);
        $this->assertSame('decimal:2', $product->getCasts()['price']);
        $this->assertSame('linked_service_id', $product->service()->getForeignKeyName());
        $this->assertSame('company_id', $service->company()->getForeignKeyName());
        $this->assertFalse(method_exists($service, 'activeVersion'));
        $this->assertFalse(method_exists($product, 'activities'));
    }

    public function test_reader_queries_never_mutate_business_tables(): void
    {
        $connection = $this->assertDealsMysqlIsolationIdentity();
        $before = [$connection->table('services')->get()->toJson(), $connection->table('products')->get()->toJson()];
        $connection->enableQueryLog();
        $connection->flushQueryLog();
        app(DealCatalogSelection::class)->configuration(['services' => [['id' => 1, 'name' => 'Legacy Service']], 'products' => [['id' => 1, 'name' => 'Legacy Product']]], 'Individual');
        foreach ($connection->getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $query['query']);
        }
        $this->assertSame($before, [$connection->table('services')->get()->toJson(), $connection->table('products')->get()->toJson()]);
    }

    public function test_fixture_guard_refuses_non_testing_environment_before_connecting(): void
    {
        $old = config('app.env');
        try {
            config(['app.env' => 'production']);
            $this->expectException(\RuntimeException::class);
            $this->assertDealsMysqlIsolationIdentity();
        } finally {
            config(['app.env' => $old]);
        }
    }

    public function test_fixture_guard_refuses_persistent_configuration_before_connecting(): void
    {
        $key = 'database.connections.'.config('database.default').'.database';
        $old = config($key);
        try {
            config([$key => 'forbidden-file.sqlite']);
            $this->expectException(\RuntimeException::class);
            $this->assertDealsMysqlIsolationIdentity();
        } finally {
            config([$key => $old]);
        }
    }

    public function test_complete_normalized_service_reads_owned_requirements_activities_and_terms(): void
    {
        Schema::create('service_versions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_id'); $table->decimal('standard_price', 15, 2);
        });
        Schema::create('service_requirements', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_version_id'); $table->string('requirement_name');
            $table->string('client_type'); $table->string('status');
        });
        Schema::create('service_activities', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_version_id'); $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable(); $table->integer('sequence');
        });
        Schema::create('service_terms', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('service_version_id'); $table->string('title');
            $table->text('content'); $table->string('status'); $table->string('scope'); $table->integer('sort_order');
        });
        DB::table('service_versions')->insert( ['id' => 5, 'service_id' => 1, 'standard_price' => 0]);
        DB::table('service_requirements')->insert( ['id' => 1, 'service_version_id' => 5, 'requirement_name' => 'Configured evidence', 'client_type' => 'Individual', 'status' => 'Active']);
        DB::table('service_activities')->insert( ['id' => 1, 'service_version_id' => 5, 'name' => 'Configured step', 'sequence' => 2]);
        DB::table('service_activities')->insert( ['id' => 2, 'service_version_id' => 5, 'name' => 'Configured child step', 'parent_id' => 1, 'sequence' => 3]);
        DB::table('service_terms')->insert( ['id' => 1, 'service_version_id' => 5, 'title' => 'Agreed heading', 'content' => 'Configured terms', 'status' => 'Active', 'scope' => 'service_specific', 'sort_order' => 1]);
        $service = $this->service();
        $service->setRawAttributes(array_merge($service->getAttributes(), ['active_version_id' => 5, 'requirements' => null]));
        $result = app(ServiceCatalogConfiguration::class)->forService($service, 'Individual');
        $this->assertSame('Configured evidence', $result['requirements'][0]['name']);
        $this->assertSame('Configured step', $result['activities'][0]['name']);
        $this->assertSame(['Configured step', 'Configured child step'], array_column($result['activities'], 'name'));
        $this->assertSame('Configured terms', $result['terms'][0]['content']);
        $this->assertTrue($result['compatible']);
    }

    public function test_partial_activity_and_term_ordering_columns_are_guarded(): void
    {
        foreach (['product_activities', 'product_terms'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id(); $table->unsignedBigInteger('product_id');
            });
        }
        $result = app(ProductCatalogConfiguration::class)->forProduct($this->product(), 'Individual');
        $this->assertSame([], $result['activities']);
        $this->assertSame([], $result['terms']);
        $this->assertFalse($result['compatible']);
    }

    public function test_preview_keeps_each_repeated_items_saved_price_and_identity(): void
    {
        $result = app(ProductCatalogConfiguration::class)->forSelections([
            ['id' => 'a', 'type' => 'PRODUCT', 'product_id' => 1, 'name' => 'Legacy Product', 'unit_price' => 10],
            ['id' => 'b', 'type' => 'PRODUCT', 'product_id' => 1, 'name' => 'Legacy Product', 'unit_price' => 20],
        ], 'Individual');
        $this->assertSame(['a', 'b'], array_column($result['products'], 'line_item_id'));
        $this->assertSame([10.0, 20.0], array_column(array_column($result['products'], 'commercial'), 'price'));
    }

    public function test_reselecting_retained_id_preserves_all_its_commercial_instances_in_preview(): void
    {
        $deal = $this->deal(['services_products' => json_encode([
            ['id' => 'a', 'type' => 'PRODUCT', 'product_id' => 1, 'name' => 'Legacy Product', 'price' => 10],
            ['id' => 'b', 'type' => 'PRODUCT', 'product_id' => 1, 'name' => 'Legacy Product', 'price' => 20],
        ])], true);
        $selection = app(DealCatalogSelection::class);
        $selected = $selection->fromRequest(Request::create('/', 'POST', ['catalog_selection_present' => 1, 'product_ids' => [1]]), $deal);
        $result = $selection->configuration($selected, 'Individual');
        $this->assertSame(['a', 'b'], array_column($result['items'], 'line_item_id'));
        $this->assertSame([10.0, 20.0], array_column(array_column($result['items'], 'commercial'), 'price'));
    }

    public function test_retained_inactive_selection_is_readable_but_unrelated_inactive_is_rejected(): void
    {
        DB::table('products')->insert( ['id' => 2, 'product_id' => 'P0002', 'product_name' => 'Inactive product', 'status' => 'Inactive', 'price' => 10]);
        $deal = $this->deal(['services_products' => '[{"id":"old","type":"PRODUCT","product_id":2,"name":"Inactive product"}]'], true);
        $selection = app(DealCatalogSelection::class);
        $request = Request::create('/', 'POST', ['catalog_selection_present' => 1, 'product_ids' => [2]]);
        $retained = $selection->fromRequest($request, $deal);
        $this->assertSame(2, $retained['products'][0]['id']);
        $this->expectException(ValidationException::class);
        $selection->fromRequest($request, $this->deal());
    }

    public function test_omitted_input_preserves_rows_and_explicit_empty_input_removes_them(): void
    {
        $selection = app(DealCatalogSelection::class);
        $deal = $this->deal();
        $this->assertCount(1, $selection->fromRequest(Request::create('/', 'POST'), $deal)['services']);
        $this->assertSame([], $selection->fromRequest(Request::create('/', 'POST', ['catalog_selection_present' => 1]), $deal)['services']);
    }

    public function test_item_snapshot_alone_cannot_trigger_live_catalog_reconstruction(): void
    {
        $proposal = $this->snapshot(['status' => 'draft', 'line_items_snapshot' => '[{"name":"Historical item","price":0}]']);
        $connection = $this->assertDealsMysqlIsolationIdentity();
        $connection->enableQueryLog();
        $connection->flushQueryLog();
        $result = app(DealProposalAggregationService::class)->aggregate($this->deal(), $proposal);
        $this->assertSame([], $connection->getQueryLog());
        $this->assertSame('Historical item', $result['line_items'][0]['name']);
        $this->assertFalse($result['compatible']);
    }

    public function test_malformed_historical_snapshots_preserve_raw_content_and_document(): void
    {
        $proposal = $this->snapshot(['status' => 'accepted', 'catalog_snapshot' => '{broken', 'line_items_snapshot' => '[broken', 'document_html' => 'Preserved original']);
        $result = app(DealProposalAggregationService::class)->aggregate($this->deal(), $proposal);
        $this->assertSame('Preserved original', $result['proposal_content']['document_html']);
        $this->assertSame('[broken', $result['raw_line_items_snapshot']);
        $this->assertSame('{broken', $result['catalog']['raw_catalog_snapshot']);
        $this->assertFalse($result['compatible']);
    }

    public function test_fixture_guard_checks_cached_actual_connection_not_just_configuration(): void
    {
        $connection = \Mockery::mock(\Illuminate\Database\Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');
        $connection->shouldReceive('getDatabaseName')->never();
        $this->expectException(\RuntimeException::class);
        $this->assertDealsMysqlConnectionIdentity($connection);
    }
}
