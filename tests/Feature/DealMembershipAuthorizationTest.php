<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\User;
use App\Policies\DealPolicy;
use App\Services\DealMembershipService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\DealsMysqlTestCase;

class DealMembershipAuthorizationTest extends DealsMysqlTestCase
{
    private Deal $deal;
    private DealMembershipService $memberships;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createFixtureSchema();
        $this->deal = $this->makeDeal();
        $this->memberships = app(DealMembershipService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_normalized_memberships_authorize_assigned_roles_but_text_assignments_do_not(): void
    {
        $consultant = $this->makeUser();
        $associate = $this->makeUser();
        $lead = $this->makeUser();
        $unassigned = $this->makeUser();

        $this->addMembership($consultant, DealMembership::ROLE_CONSULTANT);
        $this->addMembership($associate, DealMembership::ROLE_ASSOCIATE);
        $this->addMembership($lead, DealMembership::ROLE_LEAD_CONSULTANT);
        $this->deal->assigned_consultant = $unassigned->name;
        $this->deal->save();

        $policy = app(DealPolicy::class);
        $this->assertTrue($policy->update($consultant, $this->deal));
        $this->assertTrue($policy->update($associate, $this->deal));
        $this->assertTrue($policy->update($lead, $this->deal));
        $this->assertFalse($policy->update($unassigned, $this->deal));
        $this->assertTrue($policy->update($this->makeUser(['role' => 'Admin']), $this->deal));
        $this->assertTrue($policy->update($this->makeUser(['role' => 'SuperAdmin']), $this->deal));

        $consultantMembership = DealMembership::query()->where('user_id', $consultant->id)->firstOrFail();
        $consultantMembership->update(['assignment_status' => DealMembership::STATUS_INACTIVE]);
        $this->assertFalse($policy->update($consultant, $this->deal));
    }

    public function test_assigned_lead_can_manage_nonlead_roles_but_cannot_assign_lead_roles(): void
    {
        $lead = $this->makeUser();
        $target = $this->makeUser();
        $this->addMembership($lead, DealMembership::ROLE_LEAD_CONSULTANT);

        $created = $this->memberships->assign($this->deal, $lead, $target->id, DealMembership::ROLE_CONSULTANT);
        $this->assertSame($target->id, $created->user_id);
        $this->assertFalse(app(DealPolicy::class)->manageMemberships($lead, $this->deal, DealMembership::ROLE_LEAD_ASSOCIATE));

        try {
            $this->memberships->assign($this->deal, $lead, $target->id, DealMembership::ROLE_LEAD_ASSOCIATE);
            $this->fail('Lead assignment must be restricted to Admin/SuperAdmin.');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $this->assertDatabaseCount('deal_memberships', 2);
        }
    }

    public function test_membership_targets_must_be_active_nonclient_accounts(): void
    {
        $admin = $this->makeUser(['role' => 'Admin']);
        foreach ([
            ['is_active' => false],
            ['disabled_at' => now()],
            ['archived_at' => now()],
            ['role' => 'Client'],
        ] as $invalidAccount) {
            $target = $this->makeUser($invalidAccount);
            try {
                $this->memberships->assign($this->deal, $admin, $target->id, DealMembership::ROLE_ASSOCIATE);
                $this->fail('Inactive, disabled, archived, and Client accounts must be rejected.');
            } catch (ValidationException) {
                $this->assertSame(0, DealMembership::query()->count());
            }
        }
    }

    public function test_lead_cannot_assign_on_another_deal_or_assign_self(): void
    {
        $lead = $this->makeUser();
        $this->addMembership($lead, DealMembership::ROLE_LEAD_CONSULTANT);
        $otherDealId = DB::table('deals')->insertGetId(['deal_code' => 'TEST-OTHER', 'created_at' => now(), 'updated_at' => now()]);
        $otherDeal = Deal::query()->findOrFail($otherDealId);
        $target = $this->makeUser();
        $this->assertFalse(app(DealPolicy::class)->update($lead, $otherDeal));

        try {
            $this->memberships->assign($otherDeal, $lead, $target->id, DealMembership::ROLE_ASSOCIATE);
            $this->fail('A lead cannot manage membership on a different Deal.');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $this->assertSame(1, DealMembership::query()->count());
        }

        try {
            $this->memberships->assign($this->deal, $lead, $lead->id, DealMembership::ROLE_ASSOCIATE);
            $this->fail('A lead cannot self-assign another role.');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $this->assertSame(1, DealMembership::query()->count());
        }
    }

    public function test_multiple_roles_revocation_reassignment_and_last_lead_protection(): void
    {
        $lead = $this->makeUser();
        $admin = $this->makeUser(['role' => 'Admin']);
        $this->addMembership($lead, DealMembership::ROLE_LEAD_CONSULTANT);

        $this->memberships->assign($this->deal, $admin, $lead->id, DealMembership::ROLE_ASSOCIATE);
        $this->assertDatabaseCount('deal_memberships', 2);

        $leadEpisode = DealMembership::query()->where('membership_role', DealMembership::ROLE_LEAD_CONSULTANT)->firstOrFail();
        try {
            $this->memberships->revoke($this->deal, $admin, $leadEpisode->id);
            $this->fail('The final active lead must not be removed.');
        } catch (ValidationException) {
            $this->assertSame(DealMembership::STATUS_ACTIVE, $leadEpisode->fresh()->assignment_status);
        }

        $replacement = $this->makeUser();
        $this->memberships->assign($this->deal, $admin, $replacement->id, DealMembership::ROLE_LEAD_ASSOCIATE);
        $revoked = $this->memberships->revoke($this->deal, $admin, $leadEpisode->id);
        $this->assertSame(DealMembership::STATUS_INACTIVE, $revoked->assignment_status);
        $this->assertSame($admin->id, $revoked->status_changed_by_user_id);
        $this->assertNotNull($revoked->status_changed_at);

        $newEpisode = $this->memberships->assign($this->deal, $admin, $lead->id, DealMembership::ROLE_LEAD_CONSULTANT);
        $this->assertNotSame($leadEpisode->id, $newEpisode->id);
    }

    public function test_duplicate_active_assignment_is_rejected_without_partial_write(): void
    {
        $admin = $this->makeUser(['role' => 'Admin']);
        $target = $this->makeUser();
        $this->memberships->assign($this->deal, $admin, $target->id, DealMembership::ROLE_CONSULTANT);

        try {
            $this->memberships->assign($this->deal, $admin, $target->id, DealMembership::ROLE_CONSULTANT);
            $this->fail('Duplicate active role assignment should fail.');
        } catch (ValidationException) {
            $this->assertSame(1, DealMembership::query()->count());
        }
    }

    public function test_membership_from_another_deal_cannot_be_revoked_through_this_deal(): void
    {
        $admin = $this->makeUser(['role' => 'Admin']);
        $otherDealId = DB::table('deals')->insertGetId(['deal_code' => 'TEST-OTHER', 'created_at' => now(), 'updated_at' => now()]);
        $target = $this->makeUser();
        $membership = DealMembership::query()->create([
            'deal_id' => $otherDealId,
            'user_id' => $target->id,
            'membership_role' => DealMembership::ROLE_CONSULTANT,
            'assignment_status' => DealMembership::STATUS_ACTIVE,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->memberships->revoke($this->deal, $admin, $membership->id);
    }

    public function test_new_permission_keys_are_default_deny_and_honor_user_override(): void
    {
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->boolean('manage_deal_stages')->default(false);
            $table->boolean('manage_deals_finance_settings')->default(false);
        });
        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('manage_deal_stages')->default(false);
            $table->boolean('manage_deals_finance_settings')->default(false);
        });

        $admin = $this->makeUser(['role' => 'Admin']);
        DB::table('role_permissions')->insert(['role' => 'Admin', 'manage_deal_stages' => false, 'manage_deals_finance_settings' => false]);
        $this->assertFalse($admin->hasPermission('manage_deal_stages'));
        $this->assertFalse($admin->hasPermission('manage_deals_finance_settings'));

        DB::table('user_permissions')->insert(['user_id' => $admin->id, 'manage_deal_stages' => true, 'manage_deals_finance_settings' => false]);
        $admin->unsetRelation('userPermission');
        $this->assertTrue($admin->hasPermission('manage_deal_stages'));
        $this->assertFalse($admin->hasPermission('manage_deals_finance_settings'));
        $this->assertTrue($this->makeUser(['role' => 'SuperAdmin'])->hasPermission('manage_deal_stages'));
    }

    public function test_disabled_administrators_cannot_view_or_update_deals(): void
    {
        $policy = app(DealPolicy::class);

        foreach (['Admin', 'SuperAdmin'] as $role) {
            foreach ([
                ['is_active' => false],
                ['disabled_at' => now()],
                ['archived_at' => now()],
            ] as $accountStatus) {
                $user = $this->makeUser(array_merge(
                    ['role' => $role],
                    $accountStatus
                ));

                $this->assertTrue(
                    $user->isDisabled(),
                    "Expected {$role} to be disabled."
                );

                $this->assertFalse(
                    $policy->view($user, $this->deal),
                    "Disabled {$role} must not view Deals."
                );

                $this->assertFalse(
                    $policy->update($user, $this->deal),
                    "Disabled {$role} must not update Deals."
                );
            }
        }
    }

    public function test_active_administrators_retain_deal_access(): void
    {
        $policy = app(DealPolicy::class);

        foreach (['Admin', 'SuperAdmin'] as $role) {
            $user = $this->makeUser(['role' => $role]);

            $this->assertTrue(
                $policy->view($user, $this->deal)
            );

            $this->assertTrue(
                $policy->update($user, $this->deal)
            );
        }
    }

    private function createFixtureSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('Employee');
            $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->string('deal_code')->nullable();
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('lead_consultant')->nullable();
            $table->string('lead_associate_assigned')->nullable();
            $table->timestamps();
        });
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_role', 32);
            $table->string('assignment_status', 16)->default('active');
            $table->unsignedBigInteger('assigned_by_user_id');
            $table->unsignedBigInteger('status_changed_by_user_id')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('status_changed_at')->nullable();
            $table->string('active_assignment_key', 128)->nullable()->unique();
            $table->timestamps();
        });
        DB::table('deals')->insert(['id' => 10, 'deal_code' => 'TEST-10', 'created_at' => now(), 'updated_at' => now()]);
        $this->deal = Deal::query()->findOrFail(10);
    }

    private function makeUser(array $attributes = []): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $attributes['name'] ?? 'Employee '.uniqid(),
            'email' => ($attributes['email'] ?? uniqid().'@example.test'),
            'role' => $attributes['role'] ?? 'Employee',
            'is_active' => $attributes['is_active'] ?? true,
            'disabled_at' => $attributes['disabled_at'] ?? null,
            'archived_at' => $attributes['archived_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function makeDeal(): Deal
    {
        $id = DB::table('deals')->insertGetId([
            'deal_code' => 'TEST-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Deal::query()->findOrFail($id);
    }

    private function addMembership(User $user, string $role): DealMembership
    {
        return DealMembership::query()->create([
            'deal_id' => $this->deal->id,
            'user_id' => $user->id,
            'membership_role' => $role,
            'assignment_status' => DealMembership::STATUS_ACTIVE,
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);
    }
}
