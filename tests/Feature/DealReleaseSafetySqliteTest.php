<?php

namespace Tests\Feature;

use App\Http\Controllers\CompanyDealController;
use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\User;
use App\Policies\DealPolicy;
use App\Services\DealMembershipReconciliation;
use App\Services\DealMembershipService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealReleaseSafetySqliteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Release safety tests require a fresh SQLite in-memory database.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('Employee');
            $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('stage')->nullable();
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('lead_consultant')->nullable();
            $table->string('lead_associate_assigned')->nullable();
            $table->string('created_by')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->string('internal_name')->nullable();
            $table->string('internal_finance')->nullable();
            $table->unsignedBigInteger('assigned_finance_user_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->decimal('total_estimated_engagement_value', 12, 2)->nullable();
            $table->date('confirmed_delivery_date')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->date('client_preferred_completion_date')->nullable();
            $table->timestamps();
        });
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_role');
            $table->string('assignment_status')->default('active');
            $table->unsignedBigInteger('assigned_by_user_id');
            $table->unsignedBigInteger('status_changed_by_user_id')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('status_changed_at')->nullable();
            $table->string('active_assignment_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->timestamps();
        });
    }

    public function test_legacy_access_survives_until_reconciliation_but_revocation_blocks_stale_assignment(): void
    {
        $legacyUser = $this->makeUser('Legacy Member', 'legacy@example.test');
        $legacyDeal = $this->makeDeal(['assigned_consultant' => $legacyUser->name]);
        $policy = app(DealPolicy::class);

        $this->assertTrue($policy->view($legacyUser, $legacyDeal));
        $this->assertTrue($policy->update($legacyUser, $legacyDeal));

        $admin = $this->makeUser('Admin User', 'admin@example.test', 'Admin');
        $member = $this->makeUser('Normalized Member', 'normalized@example.test');
        $normalizedDeal = $this->makeDeal(['assigned_consultant' => $member->name]);
        $memberships = app(DealMembershipService::class);
        $membership = $memberships->assign($normalizedDeal, $admin, $member->id, DealMembership::ROLE_CONSULTANT);

        $this->assertTrue($policy->view($member, $normalizedDeal));
        $this->assertTrue($policy->update($member, $normalizedDeal));

        $memberships->revoke($normalizedDeal, $admin, $membership->id);

        $this->assertFalse($policy->view($member, $normalizedDeal));
        $this->assertFalse($policy->update($member, $normalizedDeal));
    }

    public function test_company_deal_listing_filters_before_summary_and_hides_revoked_or_unrelated_deals(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'company_name' => 'Example Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = $this->makeUser('Company Member', 'company-member@example.test');
        $visible = $this->makeDeal(['company_name' => 'Example Company', 'deal_name' => 'Visible Deal', 'assigned_consultant' => $user->name]);
        $revoked = $this->makeDeal(['company_name' => 'Example Company', 'deal_name' => 'Revoked Deal', 'assigned_consultant' => $user->name]);
        $this->makeDeal(['company_name' => 'Example Company', 'deal_name' => 'Unrelated Deal', 'assigned_consultant' => 'Another Person']);
        DB::table('deal_memberships')->insert([
            'deal_id' => $revoked->id,
            'user_id' => $user->id,
            'membership_role' => DealMembership::ROLE_CONSULTANT,
            'assignment_status' => DealMembership::STATUS_INACTIVE,
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/company/'.$companyId.'/deals');
        $request->setUserResolver(fn () => $user);

        $view = app(CompanyDealController::class)->index($request, $companyId);

        $this->assertCount(1, $view->getData()['deals']);
        $this->assertSame($visible->id, $view->getData()['deals']->first()['id']);
        $this->assertSame(1, $view->getData()['summary']['total']);
    }

    public function test_admin_and_superadmin_keep_access_and_disabled_accounts_are_denied(): void
    {
        $deal = $this->makeDeal([]);
        $policy = app(DealPolicy::class);

        foreach (['Admin', 'SuperAdmin'] as $role) {
            $administrator = $this->makeUser($role.' Account', strtolower($role).'@example.test', $role);
            $this->assertTrue($policy->view($administrator, $deal));
            $this->assertTrue($policy->update($administrator, $deal));
        }

        $disabledAdmin = $this->makeUser('Disabled Admin', 'disabled-admin@example.test', 'Admin', false);
        $this->assertFalse($policy->view($disabledAdmin, $deal));
        $this->assertFalse($policy->update($disabledAdmin, $deal));
    }

    public function test_finance_only_assignment_does_not_authorize_ordinary_deal_updates(): void
    {
        $finance = $this->makeUser('Finance Only', 'finance@example.test');
        $deal = $this->makeDeal(['assigned_finance_user_id' => $finance->id]);
        $policy = app(DealPolicy::class);

        $this->assertTrue($policy->view($finance, $deal));
        $this->assertFalse($policy->update($finance, $deal));
    }

    public function test_reconciliation_dry_run_is_deterministic_and_does_not_write(): void
    {
        $user = $this->makeUser('Avery Example', 'avery@example.test');
        $deal = $this->makeDeal([
            'assigned_consultant' => 'Avery Example',
            'assigned_associate' => 'avery@example.test',
            'lead_consultant' => 'Avery Example',
        ]);
        $emptyDeal = $this->makeDeal([]);
        $before = DB::table('deal_memberships')->count();
        $service = app(DealMembershipReconciliation::class);

        $first = $service->dryRun();
        $second = $service->dryRun();

        $this->assertSame($first, $second);
        $this->assertSame(2, $first['summary']['proposed']);
        $this->assertSame(1, $first['summary']['lead_review']);
        $this->assertSame([$emptyDeal->id], $first['unassigned_deal_ids']);
        $this->assertSame([$user->id, $user->id], array_column($first['proposed_memberships'], 'user_id'));
        $this->assertSame($before, DB::table('deal_memberships')->count());
        $this->assertSame($deal->id, $first['proposed_memberships'][0]['deal_id']);
    }

    private function makeUser(string $name, string $email, string $role = 'Employee', bool $active = true): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function makeDeal(array $attributes): Deal
    {
        $id = DB::table('deals')->insertGetId(array_merge([
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Deal::query()->findOrFail($id);
    }
}
