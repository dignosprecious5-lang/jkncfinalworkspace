<?php

namespace Tests\Feature;

use App\Http\Controllers\DealController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\DealsMysqlTestCase;

class DealStageManagementAuthorizationTest extends DealsMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createBaseSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_stage_mutation_endpoints_deny_without_permission_and_do_not_write(): void
    {
        $this->createStageSchema();
        DB::table('deal_stages')->insert([
            ['id' => 1, 'name' => 'Inquiry', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Qualification', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actingAs($this->makeUser());

        $this->postJson(route('deals.stages.store'), ['name' => 'New Stage'])->assertForbidden();
        $this->patchJson(route('deals.stages.update', 1), ['name' => 'Renamed'])->assertForbidden();
        $this->patchJson(route('deals.stages.move', 1), ['direction' => 'right'])->assertForbidden();
        $this->deleteJson(route('deals.stages.destroy', 2))->assertForbidden();

        $this->assertDatabaseCount('deal_stages', 2);
        $this->assertSame('Inquiry', DB::table('deal_stages')->where('id', 1)->value('name'));
        $this->assertSame(1, (int) DB::table('deal_stages')->where('id', 1)->value('order'));
    }

    public function test_index_stage_loader_does_not_create_or_seed_stages_for_an_unauthorized_reader(): void
    {
        $this->actingAs($this->makeUser());
        $controller = app(DealController::class);
        $loader = new \ReflectionMethod(DealController::class, 'dealStages');
        $loader->setAccessible(true);

        $stages = $loader->invoke($controller);

        $this->assertCount(9, $stages);
        $this->assertFalse(Schema::hasTable('deal_stages'));
    }

    public function test_index_stage_loader_does_not_seed_an_existing_empty_stage_table_for_an_unauthorized_reader(): void
    {
        $this->createStageSchema();
        $this->actingAs($this->makeUser());
        $controller = app(DealController::class);
        $loader = new \ReflectionMethod(DealController::class, 'dealStages');
        $loader->setAccessible(true);

        $stages = $loader->invoke($controller);

        $this->assertCount(9, $stages);
        $this->assertDatabaseCount('deal_stages', 0);
    }

    public function test_index_does_not_write_stages_for_deals_the_reader_cannot_access(): void
    {
        $this->createStageSchema();
        DB::table('deal_stages')->insert([
            'id' => 1,
            'name' => 'Current Stage Name',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actor = $this->makeUser();
        $hiddenDealId = DB::table('deals')->insertGetId([
            'deal_code' => 'CONDEAL-2026-001',
            'stage' => 'Stale Hidden Stage',
            'stage_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $visibleDealId = DB::table('deals')->insertGetId([
            'deal_code' => 'CONDEAL-2026-002',
            'stage' => 'Legacy Visible Stage',
            'stage_id' => 1,
            'assigned_consultant' => $actor->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $archivedDealId = DB::table('deals')->insertGetId([
            'deal_code' => 'CONDEAL-2026-003',
            'stage' => 'Archived Stage',
            'stage_id' => 1,
            'archived_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor);
        $view = app(DealController::class)->index(\Illuminate\Http\Request::create('/deals', 'GET'));
        $dealRecords = $view->getData()['dealRecords'];

        $this->assertSame('Stale Hidden Stage', DB::table('deals')->where('id', $hiddenDealId)->value('stage'));
        $this->assertSame('Legacy Visible Stage', DB::table('deals')->where('id', $visibleDealId)->value('stage'));
        $this->assertNotContains($hiddenDealId, array_column($dealRecords, 'id'));
        $this->assertNotContains($archivedDealId, array_column($dealRecords, 'id'));
        $this->assertContains($visibleDealId, array_column($dealRecords, 'id'));
        $this->assertSame('Current Stage Name', $dealRecords[(string) $visibleDealId]['stage']);
    }

    public function test_index_get_does_not_write_stages_even_for_stage_manager(): void
    {
        $this->createStageSchema();
        DB::table('deal_stages')->insert([
            'id' => 1,
            'name' => 'Current Stage Name',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actor = $this->makeUser(true);
        $dealId = DB::table('deals')->insertGetId([
            'deal_code' => 'CONDEAL-2026-004',
            'stage' => 'Stale Stage',
            'stage_id' => 1,
            'assigned_consultant' => $actor->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor);
        app(DealController::class)->index(\Illuminate\Http\Request::create('/deals', 'GET'));

        $this->assertSame('Stale Stage', DB::table('deals')->where('id', $dealId)->value('stage'));
        $this->assertSame('Current Stage Name', DB::table('deal_stages')->where('id', 1)->value('name'));
    }

    public function test_stage_mutations_succeed_for_a_user_with_the_dedicated_permission(): void
    {
        $this->createStageSchema();
        DB::table('deal_stages')->insert([
            ['id' => 1, 'name' => 'Inquiry', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Qualification', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actingAs($this->makeUser(true));

        $created = $this->postJson(route('deals.stages.store'), ['name' => 'Proposal']);
        $created->assertOk();
        $stageId = (int) $created->json('stage.id');
        $this->patchJson(route('deals.stages.update', $stageId), ['name' => 'Proposal Revised'])->assertOk();
        $this->patchJson(route('deals.stages.move', $stageId), ['direction' => 'left'])->assertOk();
        $this->deleteJson(route('deals.stages.destroy', $stageId))->assertOk();
    }

    private function createBaseSchema(): void
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
            $table->boolean('manage_deal_stages')->default(false);
        });
        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('manage_deal_stages')->default(false);
        });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('deal_code')->nullable();
            $table->string('deal_name')->nullable();
            $table->string('stage')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->string('delete_request_status')->nullable();
            $table->unsignedBigInteger('assigned_finance_user_id')->nullable();
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('created_by')->nullable();
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->decimal('total_estimated_engagement_value', 12, 2)->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });
    }

    private function createStageSchema(): void
    {
        Schema::create('deal_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('order')->default(0);
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    private function makeUser(bool $canManageStages = false): User
    {
        DB::table('role_permissions')->insert(['role' => 'Employee', 'manage_deal_stages' => false]);
        $id = DB::table('users')->insertGetId([
            'name' => 'Stage User',
            'email' => uniqid().'@example.test',
            'role' => 'Employee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_permissions')->insert(['user_id' => $id, 'manage_deal_stages' => $canManageStages]);

        return User::query()->findOrFail($id);
    }
}
