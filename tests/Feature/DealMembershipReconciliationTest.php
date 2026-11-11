<?php

namespace Tests\Feature;

use App\Services\DealMembershipReconciliation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\DealsMysqlTestCase;

class DealMembershipReconciliationTest extends DealsMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createFixtureSchema();
    }

    public function test_dry_run_matches_exact_users_preserves_dual_roles_and_holds_leads_for_admins(): void
    {
        $userId = $this->makeUser('Avery Example', 'avery@example.test');
        $this->makeUser('Shared Name', 'shared-one@example.test');
        $this->makeUser('Shared Name', 'shared-two@example.test');
        $mappedDeal = $this->makeDeal([
            'assigned_consultant' => 'Avery Example',
            'assigned_associate' => 'avery@example.test',
            'lead_consultant' => 'Avery Example',
            'lead_associate_assigned' => 'Lead needs review',
        ]);
        $ambiguousDeal = $this->makeDeal(['assigned_consultant' => 'Shared Name']);
        $unresolvedDeal = $this->makeDeal(['assigned_associate' => 'No exact match']);
        $unassignedDeal = $this->makeDeal([]);
        $before = DB::table('deal_memberships')->count();

        $report = app(DealMembershipReconciliation::class)->dryRun();

        $this->assertSame(4, $report['deal_count']);
        $this->assertCount(2, $report['proposed_memberships']);
        $this->assertSame(['consultant', 'associate'], array_column($report['proposed_memberships'], 'membership_role'));
        $this->assertSame([$mappedDeal->id, $mappedDeal->id], array_column($report['proposed_memberships'], 'deal_id'));
        $this->assertSame([$userId, $userId], array_column($report['proposed_memberships'], 'user_id'));
        $this->assertCount(2, $report['lead_assignments_requiring_admin_review']);
        $this->assertSame([], array_filter($report['proposed_memberships'], fn (array $mapping): bool => str_starts_with($mapping['membership_role'], 'lead_')));
        $this->assertCount(2, $report['unresolved_assignments']);
        $this->assertSame([$unassignedDeal->id], $report['unassigned_deal_ids']);
        $this->assertSame($before, DB::table('deal_memberships')->count());
        $this->assertNotSame($ambiguousDeal->id, $unresolvedDeal->id);
    }

    public function test_dry_run_is_idempotent_and_marks_existing_active_memberships(): void
    {
        $userId = $this->makeUser('Active Member', 'active@example.test');
        $deal = $this->makeDeal(['assigned_consultant' => 'Active Member']);
        DB::table('deal_memberships')->insert([
            'deal_id' => $deal->id,
            'user_id' => $userId,
            'membership_role' => 'consultant',
            'assignment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(DealMembershipReconciliation::class);
        $first = $service->dryRun();
        $second = $service->dryRun();

        $this->assertSame($first, $second);
        $this->assertSame([], $first['proposed_memberships']);
        $this->assertCount(1, $first['already_active_memberships']);
        $this->assertSame(1, DB::table('deal_memberships')->count());
    }

    public function test_command_requires_explicit_dry_run_and_prints_report_without_writes(): void
    {
        $this->makeDeal([]);
        $before = DB::table('deal_memberships')->count();

        $this->assertSame(1, Artisan::call('deals:memberships:reconcile'));
        $this->assertSame(0, Artisan::call('deals:memberships:reconcile', ['--dry-run' => true, '--json' => true]));
        $output = Artisan::output();

        $this->assertStringContainsString('"mode": "dry_run"', $output);
        $this->assertStringContainsString('"unassigned_deal_ids"', $output);
        $this->assertSame($before, DB::table('deal_memberships')->count());
    }

    private function createFixtureSchema(): void
    {
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
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('lead_consultant')->nullable();
            $table->string('lead_associate_assigned')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('user_id');
            $table->string('membership_role');
            $table->string('assignment_status');
            $table->timestamps();
        });
    }

    private function makeUser(string $name, string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'role' => 'Employee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeDeal(array $attributes): object
    {
        $id = DB::table('deals')->insertGetId(array_merge([
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return (object) array_merge(['id' => $id], $attributes);
    }
}
