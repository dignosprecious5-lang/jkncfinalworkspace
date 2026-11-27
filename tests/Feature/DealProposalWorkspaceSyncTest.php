<?php

namespace Tests\Feature;

use App\Http\Controllers\DealProposalController;
use App\Http\Controllers\DealController;
use App\Models\Deal;
use App\Models\DealHistory;
use App\Models\DealProposal;
use App\Services\DealHistoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DealProposalWorkspaceSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['deal_proposals', 'deals', 'service_versions', 'services', 'products', 'contacts'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('deal_code')->nullable();
            $table->string('deal_name')->nullable();
            $table->string('deal_title')->nullable();
            $table->string('company_name')->nullable();
            $table->string('customer_type')->nullable();
            $table->text('service_area')->nullable();
            $table->text('services')->nullable();
            $table->text('products')->nullable();
            $table->json('services_products')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('total_service_fee', 12, 2)->nullable();
            $table->decimal('total_product_fee', 12, 2)->nullable();
            $table->decimal('estimated_professional_fee', 12, 2)->nullable();
            $table->decimal('est_professional_fee', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('deal_discount', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('total_estimated_engagement_value', 12, 2)->nullable();
            $table->string('engagement_type')->nullable();
            $table->string('stage')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_address')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('deal_proposals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->date('proposal_date')->nullable();
            $table->timestamp('client_form_sent_at')->nullable();
            $table->json('line_items_snapshot')->nullable();
            $table->decimal('price_regular', 12, 2)->nullable();
            $table->decimal('price_discount', 12, 2)->nullable();
            $table->decimal('price_subtotal', 12, 2)->nullable();
            $table->decimal('price_tax', 12, 2)->nullable();
            $table->decimal('price_total', 12, 2)->nullable();
            $table->decimal('price_down', 12, 2)->nullable();
            $table->decimal('price_balance', 12, 2)->nullable();
            $table->text('document_html')->nullable();
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('service_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('status')->nullable();
            $table->string('name')->nullable();
            $table->string('service_name')->nullable();
            $table->string('service_code')->nullable();
            $table->text('service_description')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('price_fee', 12, 2)->nullable();
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->string('engagement_behavior')->nullable();
            $table->json('service_area')->nullable();
            $table->string('service_area_other')->nullable();
            $table->string('engagement_type')->nullable();
            $table->string('tax_type')->nullable();
            $table->json('engagement_structure')->nullable();
            $table->text('service_activity_output')->nullable();
            $table->string('frequency')->nullable();
            $table->string('deadline')->nullable();
        });

        Schema::create('service_versions', function (Blueprint $table): void {
            $table->id();
            $table->decimal('standard_price', 12, 2)->nullable();
            $table->text('scope_of_work')->nullable();
            $table->string('tax_treatment')->nullable();
            $table->text('client_description')->nullable();
            $table->string('short_name')->nullable();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('product_id')->nullable();
            $table->string('status')->nullable();
            $table->string('name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->text('product_description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->json('product_area')->nullable();
            $table->string('product_area_other')->nullable();
        });
    }

    public function test_workspace_uses_saved_line_items_quantities_prices_and_totals(): void
    {
        $deal = $this->createDeal([
            'services' => 'AFS Preparation',
            'products' => 'Compliance Kit',
            'services_products' => [
                [
                    'id' => 'service-1',
                    'type' => 'SERVICE',
                    'name' => 'AFS Preparation',
                    'description' => 'Prepare the annual financial statements.',
                    'qty' => 2,
                    'unitPrice' => 125,
                    'discount' => 12,
                    'tax' => 5,
                ],
                [
                    'id' => 'product-1',
                    'type' => 'PRODUCT',
                    'name' => 'Compliance Kit',
                    'description' => 'Compliance document package.',
                    'qty' => 3,
                    'unitPrice' => 40,
                ],
                [
                    'id' => 'service-2',
                    'type' => 'SERVICE',
                    'name' => 'Tax Preparation',
                    'description' => 'Prepare the required tax filings.',
                    'qty' => 1,
                    'unitPrice' => 50,
                ],
                [
                    'id' => 'product-2',
                    'type' => 'PRODUCT',
                    'name' => 'Filing Binder',
                    'description' => 'Organize supporting records.',
                    'qty' => 2,
                    'unitPrice' => 10,
                ],
            ],
            'total_service_fee' => 999,
            'total_product_fee' => 999,
            'discount' => 12,
        ]);

        $data = $this->documentData($deal, new DealProposal([
            'price_regular' => 0,
            'price_discount' => 0,
            'price_subtotal' => 0,
            'price_tax' => 0,
            'price_total' => 0,
            'price_down' => 0,
            'price_balance' => 0,
        ]));

        $this->assertSame('AFS Preparation', $data['service_items'][0]['name']);
        $this->assertSame('Prepare the annual financial statements.', $data['service_items'][0]['description']);
        $this->assertSame('Compliance Kit', $data['product_items'][0]['name']);
        $this->assertSame('Compliance document package.', $data['product_items'][0]['description']);
        $this->assertSame(['AFS Preparation', 'Tax Preparation'], array_column($data['service_items'], 'name'));
        $this->assertSame(['Compliance Kit', 'Filing Binder'], array_column($data['product_items'], 'name'));
        $this->assertSame([250.0, 50.0], array_column($data['service_fee_rows'], 'price'));
        $this->assertSame([120.0, 20.0], array_column($data['product_fee_rows'], 'price'));
        $this->assertSame(300.0, $data['price_regular']);
        $this->assertSame(140.0, $data['price_products']);
        $this->assertSame(12.0, $data['price_discount']);
        $this->assertSame(5.0, $data['price_tax']);
        $this->assertSame(433.0, $data['price_total']);

        $snapshot = $this->snapshotDealLineItems($deal);
        $this->assertCount(4, $snapshot);
        $this->assertSame('service-1', $snapshot[0]['id']);
        $this->assertSame(2.0, $snapshot[0]['qty']);
        $this->assertSame(125.0, $snapshot[0]['unitPrice']);
        $this->assertSame($deal->services_products, $deal->fresh()->services_products);

        $reopenedData = $this->documentData($deal->fresh(), new DealProposal());
        $this->assertSame(2, count($reopenedData['service_items']));
        $this->assertSame(2, count($reopenedData['product_items']));
        $this->assertDatabaseCount('deal_proposals', 0);
    }

    public function test_legacy_deal_services_use_their_catalog_details_and_own_saved_fee(): void
    {
        DB::table('services')->insert([
            [
                'service_id' => 'SVC-100',
                'status' => 'Active',
                'service_name' => 'Bookkeeping',
                'service_description' => 'Monthly bookkeeping services.',
                'price_fee' => 300,
            ],
            [
                'service_id' => 'SVC-200',
                'status' => 'Active',
                'service_name' => 'Tax Preparation',
                'service_description' => 'Preparation of tax returns.',
                'price_fee' => 400,
            ],
        ]);

        $deal = $this->createDeal([
            'services' => 'Bookkeeping, Tax Preparation',
            'services_products' => ['Bookkeeping', 'Tax Preparation'],
            'total_service_fee' => 650,
        ]);
        $otherDeal = $this->createDeal([
            'services' => 'Tax Preparation',
            'services_products' => ['Tax Preparation'],
            'total_service_fee' => 425,
        ]);

        $data = $this->documentData($deal, new DealProposal());
        $otherData = $this->documentData($otherDeal, new DealProposal());

        $this->assertSame(['Bookkeeping', 'Tax Preparation'], array_column($data['service_items'], 'name'));
        $this->assertSame('Monthly bookkeeping services.', $data['service_items'][0]['description']);
        $this->assertSame([[
            'item_no' => 1,
            'name' => 'Bookkeeping, Tax Preparation',
            'service_id' => 'SVC-100, SVC-200',
            'quantity' => null,
            'unit' => null,
            'unit_price' => null,
            'discount' => 0.0,
            'tax' => 0.0,
            'price' => 650.0,
            'line_total' => 650.0,
            'price_available' => true,
            'aggregate' => true,
        ]], $data['service_fee_rows']);
        $this->assertSame(650.0, $data['price_regular']);
        $this->assertSame(425.0, $otherData['price_regular']);
        $this->assertSame(['Tax Preparation'], array_column($otherData['service_items'], 'name'));
        $this->assertSame(425.0, $otherData['service_fee_rows'][0]['price']);
    }

    public function test_payment_terms_and_splits_use_saved_deal_terms_only(): void
    {
        $deal = $this->createDeal([
            'services_products' => [[
                'id' => 'service-payment',
                'type' => 'SERVICE',
                'name' => 'Bookkeeping',
                'qty' => 1,
                'unitPrice' => 100,
            ]],
            'total_service_fee' => 100,
            'payment_terms' => '50% upon acceptance, balance upon completion.',
        ]);

        $data = $this->documentData($deal, new DealProposal());
        $html = $this->resolveProposalHtml($data, null);

        $this->assertSame($deal->payment_terms, $data['payment_terms']);
        $this->assertSame(50.0, $data['price_down']);
        $this->assertSame(50.0, $data['price_balance']);
        $this->assertStringContainsString($deal->payment_terms, $html);
        $this->assertStringNotContainsString('remaining fifty percent', $html);
    }

    public function test_saved_draft_placeholders_fall_back_to_the_deals_scope_and_items(): void
    {
        $deal = $this->createDeal([
            'scope_of_work' => 'Prepare and file annual reports.',
            'services_products' => [[
                'id' => 'annual-report-service',
                'type' => 'SERVICE',
                'name' => 'Annual Report Filing',
                'unitPrice' => 200,
            ]],
        ]);
        $proposal = new DealProposal([
            'scope_of_service' => 'Scope of work needs confirmation.',
            'what_you_will_receive' => 'Deliverables need confirmation.',
        ]);

        $data = $this->documentData($deal, $proposal);

        $this->assertSame('Prepare and file annual reports.', $data['scope_of_service']);
        $this->assertSame('Annual Report Filing', $data['what_you_will_receive']);
    }

    public function test_missing_prices_and_payment_terms_are_not_fabricated(): void
    {
        $deal = $this->createDeal([
            'services_products' => [[
                'id' => 'unpriced-service',
                'type' => 'SERVICE',
                'name' => 'Unpriced Service',
                'qty' => 1,
            ]],
        ]);

        $data = $this->documentData($deal, new DealProposal());
        $html = $this->resolveProposalHtml($data, null);

        $this->assertNull($data['price_regular']);
        $this->assertNull($data['price_subtotal']);
        $this->assertNull($data['price_total']);
        $this->assertNull($data['price_down']);
        $this->assertNull($data['price_balance']);
        $this->assertFalse($data['pricing_complete']);
        $this->assertStringContainsString('Needs confirmation', $html);
        $this->assertStringContainsString('Payment terms need confirmation.', $html);
        $this->assertStringNotContainsString('50%', $html);
    }

    public function test_unissued_saved_draft_does_not_keep_the_old_hardcoded_payment_schedule(): void
    {
        $deal = $this->createDeal([
            'services_products' => [],
            'payment_terms' => 'Net 30 days.',
        ]);
        $proposal = new DealProposal([
            'document_html' => '<div class="proposal-term-block"><h3>1. Acceptance and Commencement</h3>'
                .'<ol><li>Pay fifty percent (50%) before work starts.</li></ol></div>',
        ]);

        $html = $this->resolveProposalHtml($this->documentData($deal, $proposal), $proposal->document_html);

        $this->assertStringNotContainsString('fifty percent (50%)', $html);
        $this->assertStringContainsString('in accordance with the payment terms agreed by both parties.', $html);
    }

    public function test_saved_proposal_html_refreshes_only_service_and_pricing_tables_and_issued_items_use_snapshot(): void
    {
        $deal = $this->createDeal([
            'services' => 'Current Service',
            'services_products' => [[
                'id' => 'current-service',
                'type' => 'SERVICE',
                'name' => 'Current Service',
                'unitPrice' => 900,
            ]],
            'total_service_fee' => 900,
        ]);
        $proposal = new DealProposal([
            'client_form_sent_at' => now(),
            'price_total' => 250,
            'line_items_snapshot' => [[
                'id' => 'issued-service',
                'type' => 'SERVICE',
                'name' => 'Issued Service',
                'description' => 'Frozen issued scope.',
                'qty' => 1,
                'unitPrice' => 250,
            ], [
                'id' => 'issued-product',
                'type' => 'PRODUCT',
                'name' => 'Issued Product',
                'description' => 'Frozen issued product.',
                'qty' => 1,
                'unitPrice' => 40,
            ]],
            'document_html' => '<p>Keep this approved proposal wording.</p>'
                .'<table class="proposal-data-table proposal-availed-table"><tbody><tr><td>Old service</td></tr></tbody></table>'
                .'<table class="proposal-data-table proposal-availed-table"><tbody><tr><td>Old product</td></tr></tbody></table>'
                .'<table class="proposal-data-table proposal-fee-detail-table"><tbody><tr><td>Old service fee</td></tr></tbody></table>'
                .'<table class="proposal-data-table proposal-fee-detail-table"><tbody><tr><td>Old product fee</td></tr></tbody></table>'
                .'<table class="proposal-pricing-table"><tbody><tr><td>Old total</td></tr></tbody></table>',
        ]);

        $data = $this->documentData($deal, $proposal);
        $html = $this->resolveProposalHtml($data, $proposal->document_html);

        $this->assertSame('Issued Service', $data['service_items'][0]['name']);
        $this->assertSame(250.0, $data['price_regular']);
        $this->assertStringContainsString('Keep this approved proposal wording.', $html);
        $this->assertStringContainsString('Issued Service', $html);
        $this->assertStringContainsString('Issued Product', $html);
        $this->assertStringNotContainsString('Old service', $html);
        $this->assertStringNotContainsString('Old product', $html);
        $this->assertStringNotContainsString('Old service fee', $html);
        $this->assertStringNotContainsString('Old product fee', $html);
        $this->assertStringNotContainsString('Old total', $html);
        $this->assertStringNotContainsString('Current Service', $html);

        $pdfHtml = view('deals.proposal.pdf', ['proposalHtml' => $html])->render();
        $this->assertStringContainsString('Issued Service', $pdfHtml);
        $this->assertStringContainsString('Issued Product', $pdfHtml);

        Storage::fake('public');
        $generatedPdfPath = $this->generateProposalPdf($data, $html);
        $this->assertNotNull($generatedPdfPath);
        $this->assertTrue(Storage::disk('public')->exists($generatedPdfPath));
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($generatedPdfPath));
        Storage::disk('public')->delete($generatedPdfPath);

        $legacyProposal = new DealProposal([
            'client_form_sent_at' => now(),
            'price_regular' => 250,
            'price_total' => 250,
            'line_items_snapshot' => [[
                'id' => 'legacy-issued-service',
                'type' => 'SERVICE',
                'name' => 'Legacy Issued Service',
            ]],
            'document_html' => '<p>Historical wording.</p><table class="proposal-availed-table"><tbody><tr><td>Legacy issued amount</td></tr></tbody></table>',
        ]);
        $legacyData = $this->documentData($deal, $legacyProposal);
        $legacyHtml = $this->resolveProposalHtml($legacyData, $legacyProposal->document_html);

        $this->assertSame(250.0, $legacyData['price_regular']);
        $this->assertStringContainsString('Historical wording.', $legacyHtml);
        $this->assertStringContainsString('Legacy issued amount', $legacyHtml);
        $this->assertStringNotContainsString('Current Service', $legacyHtml);
    }

    public function test_selected_catalog_items_are_saved_with_their_prices_and_persist_after_reload(): void
    {
        $versionId = DB::table('service_versions')->insertGetId([
            'standard_price' => 180,
            'scope_of_work' => 'Configured scope for Regular service',
            'tax_treatment' => 'VAT Exclusive',
            'client_description' => 'Short configured client description',
        ]);
        $serviceId = DB::table('services')->insertGetId([
            'service_name' => 'Catalog Service',
            'name' => 'Catalog Service',
            'service_description' => 'Service description',
            'price_fee' => 125,
            'active_version_id' => $versionId,
            'engagement_behavior' => 'regular',
            'engagement_structure' => json_encode(['Regular (Retainer) Engagement']),
            'service_area' => json_encode(['New Regular Area']),
            'tax_type' => 'Tax Exclusive',
        ]);
        DB::table('products')->insert([
            'product_name' => 'Catalog Product',
            'name' => 'Catalog Product',
            'price' => 55,
        ]);

        $controller = app(DealController::class);
        $builder = new \ReflectionMethod(DealController::class, 'buildSavedDealLineItems');
        $items = $builder->invoke($controller, ['Catalog Service'], ['Catalog Product']);

        $dealId = DB::table('deals')->insertGetId([
            'services_products' => json_encode($items),
        ]);
        $savedItems = Deal::query()->findOrFail($dealId)->services_products;

        $this->assertCount(2, $savedItems);
        $this->assertSame('Catalog Service', $savedItems[0]['name']);
        $this->assertSame(180.0, (float) $savedItems[0]['unitPrice']);
        $this->assertSame(180.0, (float) $savedItems[0]['price']);
        $this->assertSame($serviceId, $savedItems[0]['service_id']);
        $this->assertSame($versionId, $savedItems[0]['service_version_id']);
        $this->assertSame(['New Regular Area'], json_decode(json_encode($savedItems[0]['service_area']), true));
        $this->assertSame('regular', $savedItems[0]['engagement_behavior']);
        $this->assertSame('VAT Exclusive', $savedItems[0]['tax_treatment']);
        $this->assertSame('Short configured client description', $savedItems[0]['short_description']);
        $proposalItems = app(\App\Services\DealProposalAggregationService::class)
            ->resolveDealLineItems(Deal::query()->findOrFail($dealId));
        $this->assertSame($serviceId, $proposalItems[0]['service_id']);
        $this->assertSame($versionId, $proposalItems[0]['service_version_id']);
        $this->assertSame('VAT Exclusive', $proposalItems[0]['tax_treatment']);
        $this->assertSame('regular', $proposalItems[0]['engagement_behavior']);
        $this->assertSame('Catalog Product', $savedItems[1]['name']);
        $this->assertSame(55.0, (float) $savedItems[1]['unitPrice']);

        $reopenedItems = $builder->invoke($controller, ['Catalog Service'], ['Catalog Product'], $savedItems);
        $this->assertCount(2, $reopenedItems);
        $this->assertSame($savedItems[0]['id'], $reopenedItems[0]['id']);
        $this->assertSame(180.0, (float) $reopenedItems[0]['unitPrice']);
    }

    public function test_line_item_saves_the_selected_billing_term_and_returns_it_unchanged(): void
    {
        $deal = $this->createDeal([
            'payment_terms' => 'Full Payment Before Service',
            'services_products' => [],
        ]);
        $historyService = Mockery::mock(DealHistoryService::class);
        $historyService->shouldReceive('logNoteAdded')->once();
        app()->instance(DealHistoryService::class, $historyService);

        $billing = '50% Downpayment / 50% Completion';
        $request = Request::create(
            '/deals/'.$deal->id.'/line-items',
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => 'SERVICE',
                'name' => 'Selected Billing Service',
                'qty' => 1,
                'unit_price' => 100,
                'billing' => $billing,
                'route' => 'Regular',
            ], JSON_THROW_ON_ERROR)
        );

        $response = app(DealController::class)->storeLineItem($request, $deal->id);
        $payload = $response->getData(true);

        $this->assertSame($billing, $payload['item']['billing']);
        $this->assertSame($billing, $payload['items'][0]['billing']);
        $this->assertSame($billing, $deal->fresh()->services_products[0]['billing']);
        $this->assertSame($billing, $payload['paymentTerms']);
        $this->assertSame($billing, $deal->fresh()->payment_terms);
    }

    public function test_custom_line_item_saves_without_catalog_and_keeps_its_empty_description(): void
    {
        $deal = $this->createDeal([
            'scope_of_work' => 'Existing deal scope must not replace the custom description.',
            'services_products' => [],
        ]);
        $historyService = Mockery::mock(DealHistoryService::class);
        $historyService->shouldReceive('logNoteAdded')->once();
        app()->instance(DealHistoryService::class, $historyService);

        $request = Request::create(
            '/deals/'.$deal->id.'/line-items',
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'custom' => true,
                'type' => 'PRODUCT',
                'name' => 'Custom Compliance Binder',
                'description' => '',
                'qty' => 2,
                'unit' => 'set',
                'unit_price' => 150,
                'discount' => 10,
                'tax' => 5,
                'billing' => 'Milestone-Based Payment',
                'route' => 'Project',
            ], JSON_THROW_ON_ERROR)
        );

        $response = app(DealController::class)->storeLineItem($request, $deal->id);
        $saved = $deal->fresh();
        $resolved = app(\App\Services\DealProposalAggregationService::class)->resolveDealLineItems($saved);

        $this->assertTrue($response->getData(true)['item']['custom']);
        $this->assertSame('Custom Compliance Binder', $saved->services_products[0]['name']);
        $this->assertSame('', $saved->services_products[0]['description']);
        $this->assertSame('PRODUCT', $saved->services_products[0]['type']);
        $this->assertSame('Project', $saved->services_products[0]['route']);
        $this->assertSame(295.0, (float) $saved->amount);
        $this->assertSame('', $resolved[0]['description']);
        $this->assertSame(295.0, $resolved[0]['line_total']);
    }

    public function test_line_item_description_column_wraps_and_table_scrolls_on_small_screens(): void
    {
        $css = file_get_contents(public_path('css/deals-show.css'));

        $this->assertMatchesRegularExpression('/\.dli-table-responsive\s*\{[^}]*overflow-x:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.dli-table\s*\{[^}]*table-layout:\s*fixed/s', $css);
        $this->assertMatchesRegularExpression('/\.dli-col-desc\s*\{[^}]*white-space:\s*normal/s', $css);
        $this->assertMatchesRegularExpression('/\.dli-col-desc\s*\{[^}]*overflow-wrap:\s*anywhere/s', $css);
    }

    public function test_resolved_payment_terms_falls_back_to_latest_line_item_billing(): void
    {
        $deal = $this->createDeal([
            'payment_terms' => null,
            'services_products' => [
                ['id' => 'service-1', 'billing' => 'Full Payment Before Service'],
                ['id' => 'service-2', 'billing' => 'Milestone-Based Payment'],
            ],
        ]);

        $this->assertSame('Milestone-Based Payment', $deal->resolved_payment_terms);

        $deal->payment_terms = 'Monthly Retainer';
        $this->assertSame('Monthly Retainer', $deal->resolved_payment_terms);
    }

    public function test_deal_form_requirements_render_the_five_requested_status_rows(): void
    {
        $html = view('deals.partials.deal-form-document', [
            'dealFormData' => [],
        ])->render();

        $this->assertSame(5, substr_count($html, 'last:border-b-0">'));
        $this->assertStringContainsString('Client Requirements', $html);
        $this->assertStringContainsString('Timeline &amp; Assessment', $html);
        $this->assertSame(2, substr_count($html, 'bg-[#163b7a]'));

        preg_match_all('/<tr class="border-b border-\\[#203354\\] last:border-b-0">\\s*<td[^>]*>(.*?)<\\/td>\\s*<td[^>]*>(.*?)<\\/td>\\s*<td[^>]*>(.*?)<\\/td>\\s*<td[^>]*>(.*?)<\\/td>\\s*<\\/tr>/s', $html, $rows);
        $renderedRows = array_map(
            fn (int $index): array => array_map(fn (array $column): string => trim($column[$index]), array_slice($rows, 1)),
            array_keys($rows[0] ?? [])
        );
        $this->assertSame([
            ['Client Contact Form', 'Yes', '-', '-'],
            ['Deal Form', 'Yes', '-', '-'],
            ['Business Information Form', 'Yes', '-', '-'],
            ['Client Information Form', '-', 'Yes', '-'],
            ['Service Task Activation &amp; Routing Tracker (Start)', '-', 'Yes', '-'],
        ], $renderedRows);
    }

    public function test_saved_zero_price_is_not_replaced_by_a_catalog_rate(): void
    {
        $serviceId = DB::table('services')->insertGetId([
            'service_name' => 'Complimentary Service',
            'name' => 'Complimentary Service',
            'price_fee' => 250,
        ]);
        $deal = $this->createDeal([
            'services_products' => [[
                'id' => 'complimentary-service',
                'service_id' => $serviceId,
                'type' => 'SERVICE',
                'name' => 'Complimentary Service',
                'qty' => 1,
                'unitPrice' => 0,
            ]],
        ]);

        $resolvedItems = app(\App\Services\DealProposalAggregationService::class)->resolveDealLineItems($deal);
        $proposal = new DealProposal();
        $proposal->client_form_sent_at = now();
        $proposal->line_items_snapshot = $deal->services_products;
        $proposal->price_regular = 250;
        $data = $this->documentData($deal, $proposal);

        $this->assertSame(0.0, $resolvedItems[0]['unit_price']);
        $this->assertTrue($resolvedItems[0]['has_saved_price']);
        $this->assertSame(0.0, $data['service_fee_rows'][0]['price']);
        $this->assertSame(0.0, $data['price_regular']);
    }

    public function test_deleting_a_line_item_recalculates_totals_and_preserves_issued_proposal_history(): void
    {
        $items = [
            [
                'id' => 'service-1',
                'type' => 'SERVICE',
                'name' => 'AFS Preparation',
                'qty' => 2,
                'unitPrice' => 100,
                'discount' => 10,
                'tax' => 5,
                'billing' => 'Milestone-Based Payment',
            ],
            [
                'id' => 'product-1',
                'type' => 'PRODUCT',
                'name' => 'Compliance Kit',
                'qty' => 3,
                'unitPrice' => 40,
                'billing' => 'Monthly Retainer',
            ],
        ];
        $deal = $this->createDeal([
            'payment_terms' => 'Milestone-Based Payment',
            'services_products' => $items,
            'total_service_fee' => 200,
            'total_product_fee' => 120,
            'discount' => 10,
            'amount' => 315,
            'total_estimated_engagement_value' => 315,
        ]);
        $issuedSnapshot = json_encode($items);
        $proposalId = DB::table('deal_proposals')->insertGetId([
            'deal_id' => $deal->id,
            'client_form_sent_at' => now(),
            'line_items_snapshot' => $issuedSnapshot,
        ]);

        $historyService = Mockery::mock(DealHistoryService::class);
        $historyService->shouldReceive('logNoteAdded')
            ->once()
            ->withArgs(fn (Deal $loggedDeal, string $description, string $category): bool =>
                $loggedDeal->is($deal)
                && str_contains($description, 'AFS Preparation')
                && str_contains($description, 'Issued proposal snapshots and client decision history were retained.')
                && $category === 'Pricing'
            )
            ->andReturn(new DealHistory());
        $this->app->instance(DealHistoryService::class, $historyService);

        $request = Request::create('/deals/'.$deal->id.'/line-items/service-1', 'DELETE', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $response = app(DealController::class)->destroyLineItem($request, $deal->id, 'service-1');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals([
            'services_fee' => 0.0,
            'products_fee' => 120.0,
            'total_discount' => 0.0,
            'commercial_total' => 120.0,
        ], $response->getData(true)['totals']);
        $this->assertSame('Monthly Retainer', $response->getData(true)['paymentTerms']);
        $this->assertSame(['product-1'], array_column($deal->fresh()->services_products, 'id'));
        $this->assertSame('Monthly Retainer', $deal->fresh()->payment_terms);
        $this->assertSame($issuedSnapshot, DB::table('deal_proposals')->where('id', $proposalId)->value('line_items_snapshot'));
    }

    public function test_deleting_the_last_line_item_clears_deal_payment_terms(): void
    {
        $deal = $this->createDeal([
            'payment_terms' => 'Monthly Retainer',
            'services_products' => [[
                'id' => 'service-1',
                'type' => 'SERVICE',
                'name' => 'Monthly Service',
                'qty' => 1,
                'unitPrice' => 100,
                'billing' => 'Monthly Retainer',
            ]],
        ]);
        $historyService = Mockery::mock(DealHistoryService::class);
        $historyService->shouldReceive('logNoteAdded')->once()->andReturn(new DealHistory());
        $this->app->instance(DealHistoryService::class, $historyService);

        $request = Request::create('/deals/'.$deal->id.'/line-items/service-1', 'DELETE', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $response = app(DealController::class)->destroyLineItem($request, $deal->id, 'service-1');

        $this->assertSame([], $response->getData(true)['items']);
        $this->assertNull($response->getData(true)['paymentTerms']);
        $this->assertNull($deal->fresh()->payment_terms);
    }

    public function test_deleting_a_missing_line_item_returns_not_found_without_changing_deal(): void
    {
        $deal = $this->createDeal([
            'services_products' => [[
                'id' => 'service-1',
                'type' => 'SERVICE',
                'name' => 'AFS Preparation',
                'qty' => 1,
                'unitPrice' => 100,
            ]],
        ]);
        $originalItems = $deal->services_products;

        try {
            app(DealController::class)->destroyLineItem(
                Request::create('/deals/'.$deal->id.'/line-items/missing', 'DELETE'),
                $deal->id,
                'missing'
            );
            $this->fail('Deleting a missing item should return a not-found response.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertSame($originalItems, $deal->fresh()->services_products);
        }
    }

    public function test_product_configuration_reaches_saved_preview_pdf_and_remains_frozen(): void
    {
        Storage::fake('public');
        Schema::table('products', function (Blueprint $table) {
            foreach (['scope_of_work', 'deliverables', 'client_responsibilities', 'exclusions', 'service_area', 'category'] as $column) {
                $table->text($column)->nullable();
            }
        });
        Schema::dropIfExists('product_terms');
        Schema::create('product_terms', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('product_id');
            foreach (['title', 'content', 'scope', 'status', 'scope_service_area', 'scope_category'] as $column) { $table->text($column)->nullable(); }
            $table->integer('sort_order')->default(1); $table->timestamps();
        });
        DB::table('products')->insert(['id'=>91,'name'=>'Catalog Product','sku'=>'CAT-91','price'=>125,'scope_of_work'=>'Configured product scope','deliverables'=>'Configured product output','client_responsibilities'=>'Configured client duty','exclusions'=>'Configured exclusion','service_area'=>'Corporate','category'=>'Document']);
        $product=\App\Models\Product::findOrFail(91);
        $product->terms()->create(['title'=>'Product clause','content'=>'Persisted product clause','scope'=>'service_specific','status'=>'active']);
        $deal=$this->createDeal(['services_products'=>[['id'=>'product-91','type'=>'PRODUCT','product_id'=>91,'name'=>'Catalog Product','qty'=>2,'unitPrice'=>125]]]);
        $proposal=new DealProposal();
        $data=$this->documentData($deal,$proposal);
        $html=$this->resolveProposalHtml($data,'<div class="proposal-doc"><p>Existing authored content</p></div>');
        foreach (['Configured product scope','Configured product output','Configured client duty','Configured exclusion','Persisted product clause','Existing authored content'] as $text) { $this->assertStringContainsString($text,$html); }
        $this->assertSame(1,substr_count($html,'data-product-catalog="true"'));
        $second=$this->resolveProposalHtml($data,$html);
        $this->assertSame(1,substr_count($second,'data-product-catalog="true"'));
        $pdfPath = $this->generateProposalPdf($data,$html);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($pdfPath));
        $proposal->catalog_snapshot=$data['product_catalog'];
        $proposal->line_items_snapshot=[['id'=>'product-91','type'=>'PRODUCT','product_id'=>91,'name'=>'Catalog Product','qty'=>2,'unitPrice'=>125]];
        $proposal->client_form_sent_at=now();
        DB::table('products')->where('id',91)->update(['scope_of_work'=>'New catalog scope']);
        $issued=$this->resolveProposalHtml($this->documentData($deal,$proposal),$html);
        $this->assertStringContainsString('Configured product scope',$issued);
        $this->assertStringNotContainsString('New catalog scope',$issued);
    }

    private function createDeal(array $attributes): Deal
    {
        return Deal::withoutEvents(fn (): Deal => Deal::query()->create(array_merge([
            'deal_name' => 'Proposal sync test',
            'customer_type' => 'Individual',
        ], $attributes)));
    }

    private function documentData(Deal $deal, DealProposal $proposal): array
    {
        $controller = app(DealProposalController::class);
        $method = new \ReflectionMethod($controller, 'documentData');
        $method->setAccessible(true);

        return $method->invoke($controller, $deal, $proposal);
    }

    private function resolveProposalHtml(array $documentData, ?string $savedHtml): string
    {
        $controller = app(DealProposalController::class);
        $method = new \ReflectionMethod($controller, 'resolveProposalDocumentHtml');
        $method->setAccessible(true);

        return $method->invoke($controller, $documentData, $savedHtml, false);
    }

    private function snapshotDealLineItems(Deal $deal): array
    {
        $controller = app(DealProposalController::class);
        $method = new \ReflectionMethod($controller, 'snapshotDealLineItems');
        $method->setAccessible(true);

        return $method->invoke($controller, $deal);
    }

    private function generateProposalPdf(array $documentData, string $proposalHtml): ?string
    {
        $controller = app(DealProposalController::class);
        $method = new \ReflectionMethod($controller, 'generateProposalPdf');
        $method->setAccessible(true);

        return $method->invoke(
            $controller,
            array_merge($documentData, ['document_html' => $proposalHtml]),
            'proposal-sync-test.docx'
        );
    }
}
