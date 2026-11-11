<?php

namespace Tests\Feature;

use App\Http\Controllers\DealController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Mail, Notification, Schema};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\DealsMysqlTestCase;

/**
 * Controller-boundary tests using the guarded disposable MySQL harness.
 * These do not prove live web/session/CSRF wiring; actual HTTP middleware and Blade/browser checks
 * remain separate activation prerequisites.
 */
class DealCatalogConfigurationEndpointTest extends DealsMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createBaseCatalogSchema();
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('array', config('cache.default'));
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            foreach (['created_by', 'customer_type', 'services', 'products', 'scope_of_work'] as $column) {
                $table->text($column)->nullable();
            }
        });
        DB::table('services')->insert([
            'id' => 1, 'service_id' => 'S0001', 'service_name' => 'Consulting',
            'service_description' => 'Synthetic service scope', 'service_area' => '["Tax"]',
            'price_fee' => 10, 'tax_type' => 'VAT', 'status' => 'Active',
            'engagement_structure' => '["Project Engagement"]',
        ]);
        DB::table('products')->insert([
            'id' => 2, 'product_id' => 'P0002', 'product_name' => 'Deliverable',
            'product_description' => 'Synthetic product scope', 'price' => 20,
            'tax_type' => 'VAT', 'status' => 'Active', 'linked_service_ids' => '[1]',
        ]);
        DB::table('deals')->insert([
            'id' => 8, 'created_by' => 'Owner', 'customer_type' => 'Individual',
            'services' => 'Consulting', 'products' => '', 'scope_of_work' => 'Saved scope',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function identity(string $role = 'employee', array $attributes = []): CatalogPreviewIdentity
    {
        $user = new CatalogPreviewIdentity;
        $user->setRawAttributes(array_merge([
            'id' => 41, 'name' => 'Owner', 'email' => 'owner@example.invalid',
            'role' => $role, 'is_active' => true, 'disabled_at' => null, 'archived_at' => null,
        ], $attributes), true);
        return $user;
    }

    private function invoke(?User $user, array $changes = []): JsonResponse
    {
        $this->assertDealsMysqlIsolationIdentity();
        $request = Request::create('/deals/catalog-configuration', 'POST', array_replace([
            'catalog_selection_present' => 1, 'service_ids' => [1], 'product_ids' => [],
            'customer_type' => 'Individual', 'deal_id' => null,
        ], $changes));
        $request->setUserResolver(fn () => $user);
        return app(DealController::class)->catalogConfiguration($request);
    }

    private function denied(?User $user, array $changes = []): void
    {
        try {
            $this->invoke($user, $changes);
            $this->fail('Unauthorized preview was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_guest_client_disabled_and_archived_users_are_denied(): void
    {
        $this->denied(null);
        $this->denied($this->identity('client'));
        $this->denied($this->identity('admin', ['is_active' => false]));
        $this->denied($this->identity('admin', ['disabled_at' => '2026-01-01 00:00:00']));
        $this->denied($this->identity('superadmin', ['archived_at' => '2026-01-01 00:00:00']));
    }

    public function test_reader_permissions_are_required(): void
    {
        $user = $this->identity();
        $user->previewPermissions = false;
        $this->denied($user);
    }

    public function test_existing_deal_access_is_enforced(): void
    {
        $this->denied($this->identity('employee', ['name' => 'Unrelated']), ['deal_id' => 8]);
        $this->assertSame(200, $this->invoke($this->identity(), ['deal_id' => 8])->getStatusCode());
        $this->assertSame(200, $this->invoke($this->identity('admin', ['name' => 'Unrelated']), ['deal_id' => 8])->getStatusCode());
    }

    public function test_authorization_precedes_validation_and_catalog_queries(): void
    {
        DB::connection()->enableQueryLog();
        DB::connection()->flushQueryLog();
        $this->denied(null, ['service_ids' => 'malformed']);
        $catalogQueries = array_filter(
            DB::connection()->getQueryLog(),
            static fn (array $query): bool => preg_match('/\b(from|join)\s+[`"]?(services|products|deals)[`"]?\b/i', $query['query']) === 1
        );
        $this->assertSame([], array_values($catalogQueries));
    }

    public function test_missing_deal_is_not_silently_treated_as_create(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->invoke($this->identity(), ['deal_id' => 999]);
    }

    public function test_response_contract_and_no_store_header(): void
    {
        $response = $this->invoke($this->identity(), ['product_ids' => [2]]);
        $data = $response->getData(true);
        $this->assertSame(['items', 'conflicts', 'diagnostics', 'compatible'], array_keys($data));
        $this->assertIsBool($data['compatible']);
        $this->assertIsArray($data['conflicts']);
        $this->assertIsArray($data['diagnostics']);
        $this->assertCount(2, $data['items']);
        $this->assertSame('SERVICE', $data['items'][0]['type']);
        $this->assertSame(1, $data['items'][0]['service_id']);
        $this->assertSame('PRODUCT', $data['items'][1]['type']);
        $this->assertSame(2, $data['items'][1]['product_id']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_empty_lists_are_valid_but_not_missing_lists(): void
    {
        $this->assertSame([], $this->invoke($this->identity(), ['service_ids' => []])->getData(true)['items']);
        $this->expectException(ValidationException::class);
        $this->invoke($this->identity(), ['service_ids' => null]);
    }

    public function test_malformed_and_unsafe_ids_and_customer_types_are_rejected(): void
    {
        foreach ([['service_ids' => [0]], ['service_ids' => [-1]], ['service_ids' => [1.5]],
            ['service_ids' => ['Consulting']], ['service_ids' => [true]], ['service_ids' => [9007199254740992]],
            ['service_ids' => ['named' => 1]], ['product_ids' => '2'], ['service_ids' => array_fill(0, 201, 1)],
            ['customer_type' => 'business'], ['customer_type' => 'Other'], ['catalog_selection_present' => 0],
            ['deal_id' => 0]] as $changes) {
            try {
                $this->invoke($this->identity(), $changes);
                $this->fail('Malformed preview accepted: '.json_encode($changes));
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_unknown_and_inactive_ids_are_rejected(): void
    {
        try {
            $this->invoke($this->identity(), ['service_ids' => [999]]);
            $this->fail('Unknown ID accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_ids', $exception->errors());
        }
        DB::table('services')->where('id', 1)->update(['status' => 'Inactive']);
        $this->expectException(ValidationException::class);
        $this->invoke($this->identity());
    }

    public function test_duplicate_names_including_inactive_records_require_review(): void
    {
        DB::table('services')->insert( ['id' => 3, 'service_id' => 'S0003', 'service_name' => ' consulting ', 'status' => 'Inactive']);
        $this->expectException(ValidationException::class);
        $this->invoke($this->identity());
    }

    public function test_unvalidated_alternate_name_inputs_cannot_expand_preview(): void
    {
        $data = $this->invoke($this->identity(), ['services' => ['Unexpected'], 'other_service' => 'Custom', 'compatible' => true])->getData(true);
        $this->assertCount(1, $data['items']);
        $this->assertSame('Consulting', $data['items'][0]['name']);
    }

    public function test_repeated_saved_instances_keep_order_and_prices(): void
    {
        DB::table('deals')->where('id', 8)->update(['services' => json_encode([
            ['service_id' => 1, 'name' => 'Consulting', 'unit_price' => 15],
            ['service_id' => 1, 'name' => 'Consulting', 'unit_price' => 30],
        ])]);
        $items = $this->invoke($this->identity(), ['deal_id' => 8])->getData(true)['items'];
        $this->assertCount(2, $items);
        $this->assertSame([15.0, 30.0], array_map(fn ($item) => (float) $item['commercial']['price'], $items));
    }

    public function test_preview_never_updates_catalog_or_deal_contents(): void
    {
        $before = [];
        foreach (['services', 'products', 'deals'] as $table) $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        DB::connection()->enableQueryLog();
        DB::connection()->flushQueryLog();
        $this->invoke($this->identity(), ['deal_id' => 8, 'product_ids' => [2]]);
        foreach (DB::connection()->getQueryLog() as $query) {
            $sql = trim($query['query']);
            $isReadOnly = preg_match('/^\s*(select|pragma)\b/i', $sql) === 1
                || preg_match('/^\s*show\s+grants\s*;?\s*$/i', $sql) === 1;
            $this->assertTrue($isReadOnly, 'Unexpected non-read query during catalog preview: '.$sql);
        }
        foreach ($before as $table => $rows) $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson());
        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }
}

/** Synthetic identity only; permissions never query a real user-permission table. */
class CatalogPreviewIdentity extends User
{
    public bool $previewPermissions = true;

    public function hasPermission(string $permission): bool
    {
        return $this->previewPermissions && in_array($permission, ['access_deals', 'create_sales_marketing'], true);
    }
}
