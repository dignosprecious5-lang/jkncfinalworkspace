<?php

namespace Tests\Feature;

use App\Http\Controllers\DealController;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\DealsMysqlTestCase;

class DealFinanceAssignmentAuthorizationTest extends DealsMysqlTestCase
{
    private int $contactId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createSchema();
        $this->contactId = DB::table('contacts')->insertGetId([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_unauthorized_create_assignment_is_rejected_before_any_deal_write(): void
    {
        $actor = $this->makeUser();
        $finance = $this->makeUser();

        $this->assertAuthorizationDenied(fn () => $this->store($actor, [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $finance->id,
            'deal_name' => 'Tampered assignment',
        ]));

        $this->assertSame(0, Deal::query()->count());
        $this->assertSame(1, DB::table('contacts')->where('id', $this->contactId)->count());
    }

    public function test_unauthorized_actor_can_create_without_finance_assignment(): void
    {
        $actor = $this->makeUser();

        app(DealController::class)->store($this->request($actor, 'POST', [
            'contact_id' => $this->contactId,
            'deal_name' => 'No Finance assignment',
        ]));

        $this->assertSame(1, Deal::query()->count());
        $this->assertNull(Deal::query()->firstOrFail()->assigned_finance_user_id);
    }

    public function test_authorized_actor_can_assign_finance_user_during_creation(): void
    {
        $actor = $this->makeUser();
        $finance = $this->makeUser();
        DB::table('user_permissions')->insert([
            'user_id' => $actor->id,
            'manage_deals_finance_settings' => true,
        ]);

        app(DealController::class)->store($this->request($actor, 'POST', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $finance->id,
        ]));

        $this->assertSame($finance->id, (int) Deal::query()->firstOrFail()->assigned_finance_user_id);
    }

    public function test_unauthorized_editor_cannot_replace_or_clear_finance_assignment(): void
    {
        $actor = $this->makeUser('Admin');
        $firstFinance = $this->makeUser();
        $secondFinance = $this->makeUser();
        $deal = $this->makeDeal($firstFinance->id, ['stage' => 'Qualification']);

        foreach ([$secondFinance->id, null] as $requestedFinanceId) {
            $this->assertAuthorizationDenied(fn () => $this->update($actor, $deal->id, [
                'contact_id' => $this->contactId,
                'assigned_finance_user_id' => $requestedFinanceId,
                'stage' => 'Must not persist',
            ]));
            $this->assertSame('Qualification', $deal->fresh()->stage);
            $this->assertSame($firstFinance->id, (int) $deal->fresh()->assigned_finance_user_id);
        }

        $this->assertSame(1, Deal::query()->count());
    }

    public function test_unchanged_or_omitted_assignment_does_not_block_ordinary_edit_or_clear_assignment(): void
    {
        $actor = $this->makeUser('Admin');
        $finance = $this->makeUser();
        $deal = $this->makeDeal($finance->id, ['stage' => 'Qualification']);

        app(DealController::class)->update($this->request($actor, 'PUT', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => (string) $finance->id,
            'stage' => 'Proposal',
        ]), $deal->id);
        $this->assertSame('Proposal', $deal->fresh()->stage);
        $this->assertSame($finance->id, (int) $deal->fresh()->assigned_finance_user_id);

        app(DealController::class)->update($this->request($actor, 'PUT', [
            'contact_id' => $this->contactId,
            'stage' => 'Negotiation',
        ]), $deal->id);
        $this->assertSame('Negotiation', $deal->fresh()->stage);
        $this->assertSame($finance->id, (int) $deal->fresh()->assigned_finance_user_id);
    }

    public function test_admin_user_level_false_overrides_role_grant_but_superadmin_bypasses_permission(): void
    {
        $firstFinance = $this->makeUser();
        $secondFinance = $this->makeUser();
        DB::table('role_permissions')->insert([
            'role' => 'Admin',
            'manage_deals_finance_settings' => true,
        ]);

        $admin = $this->makeUser('Admin');
        DB::table('user_permissions')->insert([
            'user_id' => $admin->id,
            'manage_deals_finance_settings' => false,
        ]);
        $deal = $this->makeDeal($firstFinance->id);

        $this->assertAuthorizationDenied(fn () => $this->update($admin, $deal->id, [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $secondFinance->id,
        ]));
        $this->assertSame($firstFinance->id, (int) $deal->fresh()->assigned_finance_user_id);

        $superAdmin = $this->makeUser('SuperAdmin');
        app(DealController::class)->update($this->request($superAdmin, 'PUT', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $secondFinance->id,
        ]), $deal->id);
        $this->assertSame($secondFinance->id, (int) $deal->fresh()->assigned_finance_user_id);
    }

    public function test_user_and_role_grants_authorize_assignment_and_clearing(): void
    {
        $firstFinance = $this->makeUser();
        $secondFinance = $this->makeUser();
        $userGranted = $this->makeUser();
        DB::table('user_permissions')->insert([
            'user_id' => $userGranted->id,
            'manage_deals_finance_settings' => true,
        ]);
        $deal = $this->makeDeal($firstFinance->id);
        $this->addActiveMembership($deal->id, $userGranted->id);

        app(DealController::class)->update($this->request($userGranted, 'PUT', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $secondFinance->id,
        ]), $deal->id);
        $this->assertSame($secondFinance->id, (int) $deal->fresh()->assigned_finance_user_id);

        DB::table('role_permissions')->insert([
            'role' => 'Finance Manager',
            'manage_deals_finance_settings' => true,
        ]);
        $roleGranted = $this->makeUser('Finance Manager');
        $unassignedDeal = $this->makeDeal(null);
        $this->addActiveMembership($unassignedDeal->id, $roleGranted->id);
        app(DealController::class)->update($this->request($roleGranted, 'PUT', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $firstFinance->id,
        ]), $unassignedDeal->id);
        $this->assertSame($firstFinance->id, (int) $unassignedDeal->fresh()->assigned_finance_user_id);

        app(DealController::class)->update($this->request($roleGranted, 'PUT', [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => null,
        ]), $unassignedDeal->id);
        $this->assertNull($unassignedDeal->fresh()->assigned_finance_user_id);
    }

    public function test_deal_membership_and_finance_permission_are_independently_required(): void
    {
        $firstFinance = $this->makeUser();
        $secondFinance = $this->makeUser();
        $memberWithoutFinancePermission = $this->makeUser();
        $memberDeal = $this->makeDeal($firstFinance->id);
        $this->addActiveMembership($memberDeal->id, $memberWithoutFinancePermission->id);

        $this->assertAuthorizationDenied(fn () => $this->update($memberWithoutFinancePermission, $memberDeal->id, [
            'contact_id' => $this->contactId,
            'assigned_finance_user_id' => $secondFinance->id,
        ]));
        $this->assertSame($firstFinance->id, (int) $memberDeal->fresh()->assigned_finance_user_id);

        $financeManagerWithoutMembership = $this->makeUser();
        DB::table('user_permissions')->insert([
            'user_id' => $financeManagerWithoutMembership->id,
            'manage_deals_finance_settings' => true,
        ]);
        $unassignedDeal = $this->makeDeal(null);

        try {
            $this->update($financeManagerWithoutMembership, $unassignedDeal->id, [
                'contact_id' => $this->contactId,
                'assigned_finance_user_id' => $firstFinance->id,
            ]);
            $this->fail('Finance settings permission must not bypass Deal edit membership.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertNull($unassignedDeal->fresh()->assigned_finance_user_id);
    }

    public function test_archived_deal_remains_noneditable_even_for_superadmin(): void
    {
        $finance = $this->makeUser();
        $deal = $this->makeDeal($finance->id, ['archived_at' => now(), 'stage' => 'Qualification']);
        $superAdmin = $this->makeUser('SuperAdmin');

        try {
            $this->update($superAdmin, $deal->id, [
                'contact_id' => $this->contactId,
                'assigned_finance_user_id' => null,
                'stage' => 'Must not persist',
            ]);
            $this->fail('Archived Deals must remain noneditable.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame('Qualification', $deal->fresh()->stage);
            $this->assertSame($finance->id, (int) $deal->fresh()->assigned_finance_user_id);
        }
    }

    private function createSchema(): void
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
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->boolean('manage_deals_finance_settings')->default(false);
        });
        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('manage_deals_finance_settings')->default(false);
        });
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            foreach (['customer_type', 'salutation', 'first_name', 'middle_name', 'last_name', 'email', 'phone', 'contact_address', 'company_name', 'company_address', 'position'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            foreach (['deal_code', 'deal_name', 'deal_status', 'stage', 'assigned_finance_user_id', 'internal_finance', 'created_by', 'customer_type', 'salutation', 'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'address', 'company_name', 'company_address', 'position', 'qualification_result', 'approved_by_name', 'rejected_by_name', 'rejection_reason'] as $column) {
                $table->string($column)->nullable();
            }
            $table->decimal('estimated_professional_fee', 12, 2)->nullable();
            $table->decimal('estimated_government_fees', 12, 2)->nullable();
            $table->decimal('estimated_service_support_fee', 12, 2)->nullable();
            $table->decimal('total_estimated_engagement_value', 12, 2)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_role', 32);
            $table->string('assignment_status', 16)->default('active');
            $table->unsignedBigInteger('assigned_by_user_id');
            $table->timestamp('assigned_at');
            $table->timestamps();
        });
    }

    private function makeUser(string $role = 'Employee'): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $role.' '.uniqid(),
            'email' => uniqid().'@example.test',
            'role' => $role,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function makeDeal(?int $financeUserId, array $attributes = []): Deal
    {
        $id = DB::table('deals')->insertGetId($attributes + [
            'contact_id' => $this->contactId,
            'deal_code' => 'FIN-'.uniqid(),
            'assigned_finance_user_id' => $financeUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Deal::query()->findOrFail($id);
    }

    private function addActiveMembership(int $dealId, int $userId): void
    {
        DB::table('deal_memberships')->insert([
            'deal_id' => $dealId,
            'user_id' => $userId,
            'membership_role' => 'consultant',
            'assignment_status' => 'active',
            'assigned_by_user_id' => $userId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function store(User $actor, array $payload): mixed
    {
        return app(DealController::class)->store($this->request($actor, 'POST', $payload));
    }

    private function update(User $actor, int $dealId, array $payload): mixed
    {
        return app(DealController::class)->update($this->request($actor, 'PUT', $payload), $dealId);
    }

    private function request(User $actor, string $method, array $payload): Request
    {
        $request = Request::create('/deals', $method, $payload);
        $request->setUserResolver(fn () => $actor);

        return $request;
    }

    private function assertAuthorizationDenied(callable $action): void
    {
        try {
            $action();
            $this->fail('The Finance assignment change must be denied.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }
}
