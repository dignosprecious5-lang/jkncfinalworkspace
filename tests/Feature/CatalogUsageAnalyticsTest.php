<?php

namespace Tests\Feature;

use App\Services\CatalogUsageAnalytics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogUsageAnalyticsTest extends TestCase
{
    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['deal_proposal_item_responses', 'deal_proposals', 'deals', 'engagements', 'billings', 'collections'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->json('services_products')->nullable();
        });
        Schema::create('deal_proposals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedInteger('proposal_version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('status')->nullable();
            $table->timestamp('client_approved_at')->nullable();
            $table->json('line_items_snapshot')->nullable();
        });
        Schema::create('deal_proposal_item_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_proposal_id');
            $table->string('line_item_id');
            $table->string('decision');
            $table->timestamp('responded_at')->nullable();
        });
        Schema::create('engagements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->timestamps();
        });
        Schema::create('billings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('status')->nullable();
        });
    }

    public function test_metrics_are_item_specific_and_use_only_current_proposal_items(): void
    {
        DB::table('deals')->insert([
            ['id' => 1, 'account_id' => 10, 'services_products' => json_encode([
                ['id' => 'line-service-a', 'type' => 'SERVICE', 'service_id' => 7, 'name' => 'Shared'],
                ['id' => 'line-service-b', 'type' => 'SERVICE', 'service_id' => 7, 'name' => 'Shared'],
                ['id' => 'line-product', 'type' => 'PRODUCT', 'product_id' => 9, 'name' => 'Product'],
            ])],
            ['id' => 2, 'account_id' => 11, 'services_products' => json_encode([
                ['id' => 'line-service-c', 'type' => 'SERVICE', 'service_id' => 7, 'name' => 'Shared'],
            ])],
            ['id' => 3, 'account_id' => 12, 'services_products' => json_encode([
                ['id' => 'line-other', 'type' => 'SERVICE', 'service_id' => 8, 'name' => 'Other'],
            ])],
        ]);

        DB::table('deal_proposals')->insert([
            ['id' => 1, 'deal_id' => 1, 'proposal_version' => 1, 'is_current' => false, 'status' => 'approved', 'line_items_snapshot' => json_encode([
                ['id' => 'line-service-a', 'type' => 'SERVICE', 'service_id' => 7, 'qty' => 1, 'unitPrice' => 900, 'line_total' => 900],
            ])],
            ['id' => 2, 'deal_id' => 1, 'proposal_version' => 2, 'is_current' => true, 'status' => 'sent', 'line_items_snapshot' => json_encode([
                ['id' => 'line-service-a', 'type' => 'SERVICE', 'service_id' => 7, 'qty' => 1, 'unitPrice' => 100, 'discount' => 10, 'tax' => 20, 'line_total' => 110],
                ['id' => 'line-product', 'type' => 'PRODUCT', 'product_id' => 9, 'qty' => 1, 'unitPrice' => 50, 'line_total' => 50],
            ])],
            ['id' => 3, 'deal_id' => 2, 'proposal_version' => 1, 'is_current' => true, 'status' => 'sent', 'line_items_snapshot' => json_encode([
                ['id' => 'line-service-c', 'type' => 'SERVICE', 'service_id' => 7, 'qty' => 1, 'unitPrice' => 200, 'line_total' => 200],
            ])],
        ]);
        DB::table('deal_proposal_item_responses')->insert([
            ['deal_proposal_id' => 2, 'line_item_id' => 'line-service-a', 'decision' => 'accepted', 'responded_at' => '2026-10-01 10:00:00'],
            ['deal_proposal_id' => 3, 'line_item_id' => 'line-service-c', 'decision' => 'declined', 'responded_at' => '2026-10-01 10:00:00'],
        ]);
        DB::table('engagements')->insert(['service_id' => 7]);
        DB::table('billings')->insert(['product_id' => 9, 'total_amount' => 75, 'status' => 'posted']);
        DB::table('collections')->insert(['product_id' => 9, 'paid_amount' => 25, 'status' => 'posted']);

        $analytics = app(CatalogUsageAnalytics::class);
        $service = $analytics->forCatalogItem('service', 7);
        $product = $analytics->forCatalogItem('product', 9);

        $this->assertSame(2, $service['metrics']['dealsCount']);
        $this->assertSame(2, $service['metrics']['uniqueClientsCount']);
        $this->assertSame(2, $service['metrics']['proposalsCount']);
        $this->assertSame(1, $service['metrics']['acceptedCount']);
        $this->assertSame(310.0, $service['metrics']['proposedValue']);
        $this->assertSame(110.0, $service['metrics']['acceptedContractedValue']);
        $this->assertSame(10.0, $service['metrics']['discountValue']);
        $this->assertSame(1.0, $service['metrics']['engagementsCreatedCount']);
        $this->assertNull($service['metrics']['billedRevenue']);
        $this->assertSame('unavailable', $service['sources']['billing']['state']);

        $this->assertSame(1, $product['metrics']['dealsCount']);
        $this->assertSame(1, $product['metrics']['proposalsCount']);
        $this->assertSame(50.0, $product['metrics']['proposedValue']);
        $this->assertSame(75.0, $product['metrics']['billedRevenue']);
        $this->assertSame(25.0, $product['metrics']['collectedRevenue']);
        $this->assertNull($product['metrics']['engagementsCreatedCount']);
        $this->assertSame('unavailable', $product['sources']['engagements']['state']);
    }

    public function test_product_counts_do_not_cross_match_line_ids_and_keep_removed_product_history(): void
    {
        DB::table('deals')->insert([
            ['id'=>1,'services_products'=>json_encode([['id'=>'1','type'=>'PRODUCT','product_id'=>9]])],
            ['id'=>2,'services_products'=>json_encode([['id'=>'1','type'=>'PRODUCT','product_id'=>10]])],
        ]);
        DB::table('deal_proposals')->insert([
            ['id'=>1,'deal_id'=>1,'status'=>'approved','line_items_snapshot'=>json_encode([['id'=>'1','type'=>'PRODUCT','product_id'=>9,'qty'=>2,'unitPrice'=>30],['id'=>'2','type'=>'PRODUCT','product_id'=>9,'qty'=>1,'unitPrice'=>10]])],
            ['id'=>2,'deal_id'=>2,'status'=>'approved','line_items_snapshot'=>json_encode([['id'=>'1','type'=>'PRODUCT','product_id'=>10,'unitPrice'=>900]])],
        ]);
        $before=app(CatalogUsageAnalytics::class)->forCatalogItem('product',9);
        $this->assertSame(1,$before['metrics']['dealsCount']);
        $this->assertSame(1,$before['metrics']['proposalsCount']);
        $this->assertSame(1,$before['metrics']['approvedProposalsCount']);
        $this->assertSame(70.0,$before['metrics']['proposedValue']);
        DB::table('deals')->where('id',1)->update(['services_products'=>'[]']);
        $after=app(CatalogUsageAnalytics::class)->forCatalogItem('product',9);
        $this->assertSame(0,$after['metrics']['dealsCount']);
        $this->assertSame(1,$after['metrics']['proposalsCount']);
    }

    public function test_usage_dates_monthly_counts_and_engagement_statuses_use_linked_records(): void
    {
        Schema::table('deals',fn (Blueprint $table) => $table->timestamps());
        Schema::table('deal_proposals',fn (Blueprint $table) => $table->timestamps());
        Schema::table('engagements',function (Blueprint $table) { $table->unsignedBigInteger('product_id')->nullable();$table->string('status')->nullable(); });
        DB::table('deals')->insert([
            ['id'=>1,'created_at'=>'2026-09-01','services_products'=>json_encode([['type'=>'PRODUCT','product_id'=>9]])],
            ['id'=>2,'created_at'=>'2026-10-02','services_products'=>json_encode([['type'=>'PRODUCT','product_id'=>9],['type'=>'PRODUCT','product_id'=>9]])],
        ]);
        DB::table('engagements')->insert([
            ['product_id'=>9,'status'=>'active','created_at'=>'2026-10-02'],
            ['product_id'=>9,'status'=>'completed','created_at'=>'2026-10-03'],
            ['product_id'=>10,'status'=>'active','created_at'=>'2026-10-02'],
        ]);
        $result=app(CatalogUsageAnalytics::class)->forCatalogItem('product',9,'2026-10-01','2026-10-31');
        $this->assertSame(1,$result['metrics']['dealsCount']);
        $this->assertSame(['2026-10'=>1],$result['usageOverTime']);
        $this->assertSame(1,$result['metrics']['activeEngagementsCount']);
        $this->assertSame(1,$result['metrics']['completedEngagementsCount']);
        $this->assertNull($result['metrics']['billedRevenue']);
    }

    public function test_saved_draft_proposals_use_actual_deal_items_before_issuance(): void
    {
        DB::table('deals')->insert(['id'=>1,'services_products'=>json_encode([['id'=>'a','type'=>'PRODUCT','product_id'=>9,'qty'=>2,'unitPrice'=>25]])]);
        DB::table('deal_proposals')->insert(['deal_id'=>1,'status'=>'draft','line_items_snapshot'=>null]);
        $result=app(CatalogUsageAnalytics::class)->forCatalogItem('product',9);
        $this->assertSame(1,$result['metrics']['proposalsCount']);
        $this->assertSame(0,$result['metrics']['approvedProposalsCount']);
        $this->assertSame(50.0,$result['metrics']['proposedValue']);
    }
}
