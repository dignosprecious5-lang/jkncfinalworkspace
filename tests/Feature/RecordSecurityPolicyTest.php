<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Models\User;
use App\Policies\RecordIntakeItemPolicy;
use App\Policies\RecordPolicy;
use App\Services\CompanyContext;
use App\Services\Records\RecordCapacityService;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordSearchService;
use App\Support\Auth;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Role permissions, confidential isolation, cross-company guards and rate limits.
 *
 * The prototype has no sign-in, so most of this exercises the policy and the gates
 * directly rather than through HTTP. Route-level enforcement is not attached yet;
 * the middleware table at the bottom records what each route will use when it is.
 */
class RecordSecurityPolicyTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    /** @var array<string, array<string, RecordClassification>> */
    private array $classifications = [];

    /** @var array<string, array<string, RecordIndex>> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    public function test_a_role_is_read_from_the_user_when_there_is_one(): void
    {
        $user = User::factory()->create(['role' => UserRole::Auditor]);

        $this->assertSame(UserRole::Auditor, UserRole::for($user));
        $this->assertSame(UserRole::Auditor, $user->roleOrDefault());
    }

    public function test_a_user_with_no_role_falls_back_to_the_configured_default(): void
    {
        config(['ordo.default_role' => 'records_manager']);

        $this->assertSame(UserRole::RecordsManager, UserRole::for(User::factory()->create()));
        $this->assertSame(UserRole::RecordsManager, UserRole::for(null));
    }

    public function test_an_unrecognised_role_does_not_grant_permissions(): void
    {
        config(['ordo.default_role' => 'standard_user']);

        // Not a User: the model casts role to the enum, which would reject this.
        $user = new class
        {
            public string $role = 'superuser';
        };

        $this->assertSame(UserRole::StandardUser, UserRole::for($user));
    }

    public function test_an_unconfigured_default_falls_back_to_the_most_restricted_role(): void
    {
        config(['ordo.default_role' => null]);

        $this->assertSame(UserRole::StandardUser, UserRole::for(null));
    }

    /**
     * @return array<string, array{UserRole, bool, bool, bool, bool, bool, bool, bool}>
     */
    public static function roleMatrix(): array
    {
        return [
            'system admin' => [UserRole::SystemAdmin, true, true, true, true, true, true, true],
            'records manager' => [UserRole::RecordsManager, true, true, false, true, true, true, true],
            'auditor' => [UserRole::Auditor, false, false, false, false, false, true, true],
            'standard user' => [UserRole::StandardUser, false, false, false, false, false, false, false],
        ];
    }

    #[DataProvider('roleMatrix')]
    public function test_each_role_holds_the_expected_permissions(
        UserRole $role,
        bool $edit,
        bool $archive,
        bool $hold,
        bool $discard,
        bool $link,
        bool $export,
        bool $confidential,
    ): void {
        $user = User::factory()->create(['role' => $role]);
        $record = $this->record();
        $item = RecordIntakeItem::query()->firstOrFail();
        $gate = Gate::forUser($user);

        $this->assertSame($edit, $gate->allows('update', $record), 'update');
        $this->assertSame($archive, $gate->allows('archive', $record), 'archive');
        $this->assertSame($hold, $gate->allows('applyLegalHold', $record), 'legal hold');
        $this->assertSame($discard, $gate->allows('discard', $item), 'discard');
        $this->assertSame($link, $gate->allows('link', $record), 'link');
        $this->assertSame($export, $gate->allows('export'), 'export');
        $this->assertSame($confidential, $gate->allows('viewConfidential'), 'confidential');
    }

    public function test_every_role_can_view_records(): void
    {
        $record = $this->record();

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue(Gate::forUser($user)->allows('view', $record));
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', Record::class));
        }
    }

    public function test_a_record_in_another_company_is_denied_for_every_role(): void
    {
        $foreign = $this->recordIn($this->seedSecondCompany($this->company));

        $this->actingInCompany($this->company);

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $gate = Gate::forUser($user);

            // Even a System Admin: scope is checked before role.
            $this->assertFalse($gate->allows('view', $foreign));
            $this->assertFalse($gate->allows('update', $foreign));
            $this->assertFalse($gate->allows('archive', $foreign));
            $this->assertFalse($gate->allows('link', $foreign));
            $this->assertFalse($gate->allows('applyLegalHold', $foreign));
        }
    }

    public function test_an_intake_item_in_another_company_is_denied_for_every_role(): void
    {
        $second = $this->seedSecondCompany($this->company);
        $foreign = $this->createIntakeIn($second);

        $this->actingInCompany($this->company);

        $policy = $this->app->make(RecordIntakeItemPolicy::class);

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertFalse($policy->view($user, $foreign));
            $this->assertFalse($policy->discard($user, $foreign));
            $this->assertFalse($policy->file($user, $foreign));
        }
    }

    public function test_no_company_context_denies_every_record_permission(): void
    {
        $record = $this->record();

        $this->app->make(CompanyContext::class)->forget();

        $policy = $this->app->make(RecordPolicy::class);
        $admin = User::factory()->create(['role' => UserRole::SystemAdmin]);

        $this->assertFalse($policy->view($admin, $record));
        $this->assertFalse($policy->update($admin, $record));
    }

    public function test_a_standard_user_cannot_use_the_picker(): void
    {
        $standard = User::factory()->create(['role' => UserRole::StandardUser]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        $this->assertFalse(Gate::forUser($standard)->allows('usePicker'));
        $this->assertTrue(Gate::forUser($auditor)->allows('usePicker'));
    }

    /**
     * Confidential was written but never enforced before Day 8. These cover the
     * query layer, not just the policy decision.
     */
    public function test_confidential_records_are_hidden_from_search_by_a_standard_user(): void
    {
        $open = $this->record(['title' => 'Ordinary Record']);
        $secret = $this->record(['title' => 'Board Resolution']);

        $secret->forceFill(['is_confidential' => true])->save();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $titles = Auth::as($standard, fn () => $this->app->make(RecordSearchService::class)
            ->search([])
            ->pluck('title')
            ->all());

        $this->assertContains('Ordinary Record', $titles);
        $this->assertNotContains('Board Resolution', $titles);

        $this->assertNotSame($open->id, $secret->id);
    }

    public function test_confidential_records_are_visible_to_an_auditor(): void
    {
        $this->record(['title' => 'Board Resolution'])
            ->forceFill(['is_confidential' => true])
            ->save();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        $titles = Auth::as($auditor, fn () => $this->app->make(RecordSearchService::class)
            ->search([])
            ->pluck('title')
            ->all());

        $this->assertContains('Board Resolution', $titles);
    }

    public function test_confidential_records_are_hidden_from_the_dashboard_counts(): void
    {
        $this->record(['title' => 'Ordinary Record']);
        $this->record(['title' => 'Board Resolution'])
            ->forceFill(['is_confidential' => true])
            ->save();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $counts = Auth::as($standard, fn () => $this->app->make(RecordCapacityService::class)->counts());

        $this->assertSame(1, $counts['records']);

        $admin = User::factory()->create(['role' => UserRole::SystemAdmin]);

        $adminCounts = Auth::as($admin, fn () => $this->app->make(RecordCapacityService::class)->counts());

        $this->assertSame(2, $adminCounts['records']);
    }

    public function test_confidential_records_are_hidden_from_the_classification_distribution(): void
    {
        $this->record();
        $this->record()
            ->forceFill(['is_confidential' => true])
            ->save();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $distribution = Auth::as($standard, fn () => $this->app->make(RecordCapacityService::class)->classificationDistribution());

        $this->assertSame(1, collect($distribution)->sum('records'));
    }

    public function test_confidential_records_still_consume_their_record_unit(): void
    {
        $secret = $this->record();
        $secret->forceFill(['is_confidential' => true])->save();

        // Hiding a record from a listing must not erase it from metering.
        $this->assertSame(1, (int) $secret->record_units);
    }

    public function test_the_register_route_404s_for_another_companys_record(): void
    {
        $foreign = $this->recordIn($this->seedSecondCompany($this->company));

        $this->actingInCompany($this->company);

        $this->get(route('records.preview', ['record' => $foreign->id]))->assertNotFound();
        $this->put(route('records.update', ['record' => $foreign->id]), ['title' => 'Hijacked'])
            ->assertNotFound();
        $this->post(route('records.archive', ['record' => $foreign->id]))->assertNotFound();
        $this->get(route('records.links.picker', ['record' => $foreign->id]))->assertNotFound();
        $this->post(route('records.links.store', ['record' => $foreign->id]), [
            'module' => 'Finance',
            'reference_id' => 'INV-1',
            'link_type' => 'related',
        ])->assertNotFound();
    }

    public function test_a_foreign_record_is_404_not_403_so_its_existence_stays_hidden(): void
    {
        $foreign = $this->recordIn($this->seedSecondCompany($this->company));

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.preview', ['record' => $foreign->id]));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_a_missing_record_is_also_404(): void
    {
        $this->get(route('records.preview', ['record' => '01JZZZZZZZZZZZZZZZZZZZZZZ']))->assertNotFound();
    }

    public function test_the_export_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('exports'), 'The exports rate limiter is not registered.');
    }

    public function test_the_picker_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('picker'), 'The picker rate limiter is not registered.');
    }

    public function test_the_export_route_is_throttled(): void
    {
        $middleware = collect($this->route('records.export')->gatherMiddleware())
            ->map(fn ($m) => is_string($m) ? $m : $m::class);

        $this->assertTrue($middleware->contains('throttle:exports'), 'records.export is not throttled');
    }

    public function test_the_picker_route_is_throttled(): void
    {
        $middleware = collect($this->route('records.links.picker')->gatherMiddleware())
            ->map(fn ($m) => is_string($m) ? $m : $m::class);

        $this->assertTrue($middleware->contains('throttle:picker'), 'records.links.picker is not throttled');
    }

    public function test_the_audit_export_route_is_throttled(): void
    {
        $middleware = collect($this->route('records.logs.export')->gatherMiddleware())
            ->map(fn ($m) => is_string($m) ? $m : $m::class);

        $this->assertTrue($middleware->contains('throttle:exports'));
    }

    /**
     * The report export scans beyond a single page, so it is bounded the same way
     * the other two exports are.
     */
    public function test_the_report_export_route_is_throttled(): void
    {
        $middleware = collect($this->route('records.reports.export')->gatherMiddleware())
            ->map(fn ($m) => is_string($m) ? $m : $m::class);

        $this->assertTrue($middleware->contains('throttle:exports'));
    }

    public function test_repeated_picker_requests_eventually_return_429(): void
    {
        config(['ordo.rate_limits.picker' => 3]);

        $record = $this->record();

        for ($i = 0; $i < 3; $i++) {
            $this->get(route('records.links.picker', ['record' => $record->id]))->assertOk();
        }

        $this->get(route('records.links.picker', ['record' => $record->id]))->assertStatus(429);
    }

    public function test_repeated_exports_eventually_return_429(): void
    {
        config(['ordo.rate_limits.exports' => 3]);

        $this->record();

        for ($i = 0; $i < 3; $i++) {
            $this->get(route('records.export'))->assertOk();
        }

        $this->get(route('records.export'))->assertStatus(429);
    }

    public function test_the_picker_rate_limit_does_not_apply_to_the_register(): void
    {
        config(['ordo.rate_limits.picker' => 1]);

        $this->record();

        // The picker limiter is keyed separately, so browsing the register is free.
        for ($i = 0; $i < 5; $i++) {
            $this->get(route('records.index'))->assertOk();
        }
    }

    public function test_every_record_route_resolves_its_record_under_company_scope(): void
    {
        $routes = ['records.update', 'records.archive', 'records.preview', 'records.links.store', 'records.links.picker'];

        foreach ($routes as $name) {
            $middleware = collect($this->route($name)->gatherMiddleware())
                ->map(fn ($m) => is_string($m) ? $m : $m::class);

            $this->assertTrue(
                $middleware->contains('ordo.record'),
                "{$name} does not resolve its record under company scope",
            );
        }
    }

    /**
     * The middleware each route will carry once authentication lands. Kept here so
     * the intended policy is stated in one place while it is not yet enforced.
     */
    public function test_the_policy_covers_every_permission_the_app_uses(): void
    {
        // The intended route-to-ability map, recorded here because it is not yet
        // wired as middleware. Read this list to see what each role may do; it is
        // what gets attached to the routes once authentication exists.
        $expected = [
            'records.index' => 'can:viewAny,record',
            'records.dashboard' => 'can:viewAny,record',
            'records.logs' => 'can:viewAny,record',
            'records.export' => 'can:export,record',
            'records.logs.export' => 'can:export,record',
            'records.preview' => 'can:view,record',
            'records.update' => 'can:update,record',
            'records.archive' => 'can:archive,record',
            'records.links.picker' => 'can:usePicker,record',
            'records.links.store' => 'can:link,record',
            'records.unclassified' => 'can:viewAny,intake',
            'records.unclassified.file' => 'can:file,intake',
            'records.unclassified.destroy' => 'can:discard,intake',
        ];

        foreach ($expected as $name => $middleware) {
            // Every mapped route must actually exist, so the list cannot drift
            // away from the route file without this failing.
            $this->assertNotNull(
                app('router')->getRoutes()->getByName($name),
                "Route [{$name}] is named in the policy map but not registered.",
            );

            [$ability, $subject] = explode(',', str_replace('can:', '', $middleware));

            $policy = $subject === 'intake'
                ? RecordIntakeItemPolicy::class
                : RecordPolicy::class;

            $this->assertTrue(
                method_exists($policy, $ability),
                "{$name} maps to [{$ability}], which {$policy} does not define.",
            );
        }
    }

    private function route(string $name): RoutingRoute
    {
        return app('router')->getRoutes()->getByName($name) ?? $this->fail("Route [{$name}] is not registered.");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Record
    {
        return $this->recordIn($this->company, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordIn(Company $company, array $attributes = []): Record
    {
        $this->actingInCompany($company);

        $classification = $this->classifications[$company->id]['301'] ??= $this->seedClassification($company);
        $index = $this->indexes[$company->id]['301'] ??= $this->seedIndex($company, $classification);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => (string) ($attributes['title'] ?? 'Untitled')],
        );

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);
    }

    private function createIntakeIn(Company $company): RecordIntakeItem
    {
        $this->actingInCompany($company);

        return $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'Foreign Intake'],
        );
    }

    private function seedClassification(Company $company): RecordClassification
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();

        return $classification;
    }

    private function seedIndex(Company $company, RecordClassification $classification): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => '301-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }
}
