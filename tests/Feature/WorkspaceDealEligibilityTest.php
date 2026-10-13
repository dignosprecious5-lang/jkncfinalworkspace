<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegularController;
use App\Services\ProjectProvisioner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
        throw new RuntimeException('Workspace eligibility tests require SQLite :memory:.');
    }

    Schema::create('deals', function (Blueprint $table): void {
        $table->id();
        $table->string('deal_code')->nullable();
        $table->string('engagement_type')->nullable();
        $table->string('deal_status')->nullable();
        $table->timestamps();
    });
    Schema::create('projects', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('deal_id')->nullable();
        $table->foreignId('company_id')->nullable();
        $table->string('name')->nullable();
        $table->string('engagement_type')->nullable();
        $table->string('status')->nullable();
        $table->string('current_phase')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
    Schema::create('project_starts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('project_id');
        $table->string('status');
        $table->timestamps();
    });
});

function workspaceDeal(string $code, string $type, ?string $status = null): int
{
    return DB::table('deals')->insertGetId([
        'deal_code' => $code,
        'engagement_type' => $type,
        'deal_status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function linkedWorkspace(int $dealId, string $type, bool $shell = false): void
{
    DB::table('projects')->insert([
        'deal_id' => $dealId,
        'name' => 'Existing workspace',
        'engagement_type' => $type,
        'status' => $shell ? 'shell' : 'SOW',
        'metadata' => json_encode(['is_shell' => $shell]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('project and regular deal lists include only unlinked deals of the corresponding type', function (): void {
    $project = workspaceDeal('PROJECT-OPEN', 'Project Engagement');
    $regular = workspaceDeal('REGULAR-OPEN', 'Regular Retainer');
    $hybrid = workspaceDeal('HYBRID-OPEN', 'Hybrid Project');
    $linkedToRegular = workspaceDeal('PROJECT-LINKED', 'Project Engagement');
    $linkedToProject = workspaceDeal('REGULAR-LINKED', 'Regular Retainer');
    $shell = workspaceDeal('REGULAR-SHELL', 'Regular Retainer');

    linkedWorkspace($linkedToRegular, 'Regular Retainer');
    linkedWorkspace($linkedToProject, 'Project Engagement');
    linkedWorkspace($shell, 'Regular Retainer', true);

    $projectDeals = (new ReflectionMethod(ProjectController::class, 'projectDealRecords'))
        ->invoke(new ProjectController);
    $regularDeals = (new ReflectionMethod(RegularController::class, 'regularDealRecords'))
        ->invoke(new RegularController(app(ProjectProvisioner::class)));

    expect(array_column($projectDeals, 'id'))->toEqualCanonicalizing([$project, $hybrid]);
    expect(array_column($regularDeals, 'id'))->toEqualCanonicalizing([$regular]);
    expect($projectDeals[0])->toHaveKeys(['search_blob', 'client_name', 'business_name', 'service_area']);
    expect($regularDeals[0])->toHaveKeys(['search_blob', 'client_name', 'business_name', 'services']);
});

test('opening regular index does not provision an unlinked deal', function (): void {
    $deal = workspaceDeal('CONDEAL-2026-077', 'Regular (Retainer) Engagement', 'pending');
    $provisioner = Mockery::mock(ProjectProvisioner::class);
    $provisioner->shouldNotReceive('createOrSyncFromDeal');

    $view = (new RegularController($provisioner))->index(Request::create('/regular'));

    expect(DB::table('projects')->count())->toBe(0);
    expect(array_column($view->getData()['dealRecords'], 'id'))->toBe([$deal]);
});

test('opening regular index keeps an existing full workspace with pending START linked to its deal', function (): void {
    $deal = workspaceDeal('CONDEAL-2026-077', 'Regular (Retainer) Engagement', 'pending');
    linkedWorkspace($deal, 'Regular (Retainer) Engagement');
    $projectId = DB::table('projects')->where('deal_id', $deal)->value('id');
    DB::table('projects')->where('id', $projectId)->update([
        'status' => 'RSAT',
        'current_phase' => 'RSAT',
        'metadata' => json_encode(['is_shell' => false, 'created_from' => 'deal_approval']),
    ]);
    DB::table('project_starts')->insert([
        'project_id' => $projectId,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $provisioner = Mockery::mock(ProjectProvisioner::class);
    $provisioner->shouldNotReceive('createOrSyncFromDeal');

    $view = (new RegularController($provisioner))->index(Request::create('/regular'));

    expect(array_column($view->getData()['dealRecords'], 'id'))->not->toContain($deal);
    expect(DB::table('projects')->where('deal_id', $deal)->count())->toBe(1);
    expect(DB::table('projects')->where('id', $projectId)->value('status'))->toBe('RSAT');
    expect(DB::table('project_starts')->where('project_id', $projectId)->value('status'))->toBe('pending');
});

test('opening regular index preserves a linked shell and its pending START', function (): void {
    $deal = workspaceDeal('CONDEAL-2026-077', 'Regular (Retainer) Engagement');
    linkedWorkspace($deal, 'Regular (Retainer) Engagement', true);
    $projectId = DB::table('projects')->where('deal_id', $deal)->value('id');
    DB::table('project_starts')->insert([
        'project_id' => $projectId,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $provisioner = Mockery::mock(ProjectProvisioner::class);
    $provisioner->shouldNotReceive('createOrSyncFromDeal');

    $view = (new RegularController($provisioner))->index(Request::create('/regular'));

    expect(array_column($view->getData()['dealRecords'], 'id'))->not->toContain($deal);
    expect(DB::table('projects')->where('deal_id', $deal)->count())->toBe(1);
    expect(DB::table('projects')->where('id', $projectId)->value('status'))->toBe('shell');
    expect(DB::table('projects')->where('id', $projectId)->value('metadata'))->toBe(json_encode(['is_shell' => true]));
    expect(DB::table('project_starts')->where('project_id', $projectId)->value('status'))->toBe('pending');
});
