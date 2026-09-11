<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\ClientRequirementController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\ImportExportController;
use App\Models\Service;
use App\Models\ServiceActivity;
use App\Models\ContentLibrary;
use App\Models\ClientRequirement;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root Redirect to Services Directory
Route::get('/', function () {
    return redirect()->route('services.index');
});

// Direct Database Verification Route
Route::get('/check-count', function () {
    $count = Service::count();

    return "<h1>Total Services in Database: {$count}</h1>
            <br>
            <a href='" . route('services.index') . "'
               style='font-size:16px;color:blue;'>
               Go to Services Dashboard
            </a>";
});

// QUICK SEEDER ROUTE FOR TERMS
Route::get('/seed-terms-now', function () {

    ContentLibrary::create([
        'scope_level' => 'global',
        'content_type' => 'terms_and_conditions',
        'title' => 'Global Standard Terms',
        'body_text' => 'This applies to all engagements across the entire organization.',
        'is_active' => true,
    ]);

    ContentLibrary::create([
        'scope_level' => 'service_area',
        'scope_value' => 'Tax Services',
        'content_type' => 'terms_and_conditions',
        'title' => 'Tax Services Specific Terms',
        'body_text' => 'Special terms for tax compliance and BIR-related filings.',
        'is_active' => true,
    ]);

    return "<h1>Success! Sample Terms Seeded to Database.</h1>
            <br>
            <a href='/services/1/inherited-terms'
               style='font-size:18px;font-weight:bold;color:blue;'>
               Click here to view Inherited Terms Output
            </a>";
});

// Force Seed Route to populate the entire services catalog from FullServiceSeeder
Route::get('/force-seed-now', function () {

    Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--seeder' => 'FullServiceSeeder',
        '--force' => true,
    ]);

    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');

    return "<h1>Success! Database re-seeded with FullServiceSeeder.</h1>
            <br>
            <a href='/check-count'
               style='font-size:18px;font-weight:bold;color:blue;'>
               Click here to verify total count
            </a>";
});

// =======================================================
// AUTHENTICATION FALLBACK & NAMED LOGIN ROUTE
// =======================================================
Route::get('/login', function () {
    if (!Auth::check()) {
        return redirect('/login-manager-now');
    }
    return redirect()->route('services.index');
})->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');


// =======================================================
// AUTHENTICATED ROUTES GROUP
// =======================================================
Route::middleware(['auth'])->group(function () {

    // ==========================================
    // AUDIT TRAIL DASHBOARD ROUTE
    // ==========================================
    Route::get('/audit-logs', function () {
        $logs = AuditLog::latest()->paginate(15);
        return view('audit_logs.index', compact('logs'));
    })->name('audit_logs.index');

    // ==========================================
    // IMPORT & EXPORT ROUTES (EXCEL / PDF)
    // ==========================================
    Route::get('/export/engagements/excel', [ImportExportController::class, 'exportEngagementsExcel'])->name('export.engagements.excel');
    Route::get('/export/engagements/pdf', [ImportExportController::class, 'exportEngagementsPDF'])->name('export.engagements.pdf');
    Route::get('/export/requirements/excel', [ImportExportController::class, 'exportRequirementsExcel'])->name('export.requirements.excel');
    
    // Requirements Import Handler
    Route::post('/requirements/import', [RequirementController::class, 'import'])->name('import.requirements');

    // ==========================================
    // NOTIFICATION SYSTEM ROUTES
    // ==========================================
    Route::post('/notifications/mark-all-read', function () {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    })->name('notifications.markAllRead');

    Route::post('/notifications/{id}/mark-read', function ($id) {
        if (auth()->check()) {
            $notification = auth()->user()->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        }
        return response()->json(['success' => true]);
    })->name('notifications.markRead');

    // ==========================================
    // 1. MAIN SERVICE CONTROLLER ROUTES GROUP
    // ==========================================
    Route::controller(ServiceController::class)->group(function () {

        Route::get('/services', 'index')->name('services.index');
        Route::post('/services', 'store')->name('services.store');

        Route::get('/services/{service}', function ($service) {
            return redirect()->route('services.workspace', [
                'service' => $service,
                'mode' => 'view'
            ]);
        })->name('services.show');

        Route::get('/services/{service}/workspace', 'workspace')->name('services.workspace');

        Route::match(
            ['put', 'post'],
            '/services/{service}/version-update',
            'updateVersion'
        )->name('services.version.update');

        Route::get('/services/{service}/audit-logs', 'getAuditLogs')->name('services.audit_logs');
        
        // WORKSPACE APPROVAL ACTIONS
        Route::post('/services/{service}/submit-approval', 'submitForApproval')->name('services.submit_approval');
        Route::post('/services/{service}/approve', 'approve')->name('services.approve');
        Route::post('/services/{service}/reject', 'reject')->name('services.reject');

        // WORKFLOW & ACTIVITY MANAGEMENT
        Route::post('/services/{service}/activities', 'storeActivity')->name('services.activities.store');
        Route::put('/services/activities/{activity}', 'updateActivity')->name('services.activities.update');
        Route::delete('/services/activities/{activity}', 'destroyActivity')->name('services.activities.destroy');

        Route::match(
            ['get', 'post'],
            '/services/{service}/activities/bulk-import',
            'bulkImportActivities'
        )->name('services.activities.bulk_import');

        Route::post('/services/{service}/activities/bulk-action', 'bulkActionActivities')->name('services.activities.bulk_action');
        Route::delete('/services/{service}/activities/bulk-destroy', 'bulkDestroyActivities')->name('services.activities.bulk_destroy');
        Route::delete('/services/{service}/activities/destroy-all', 'destroyAllActivities')->name('services.activities.destroy_all');

        // TERMS & REQUIREMENTS MANAGEMENT
        Route::get('/services/{service}/inherited-terms', 'getInheritedTerms')->name('services.inherited_terms');
        Route::post('/services/{service}/requirements', 'storeRequirement')->name('services.requirements.store');
        Route::put('/services/requirements/{requirement}', 'updateRequirement')->name('services.requirements.update');
        Route::delete('/services/requirements/{requirement}', 'destroyRequirement')->name('services.requirements.destroy');

        // UTILITY OPERATIONS
        Route::post('/services/{service}/duplicate', 'duplicate')->name('services.duplicate');
        Route::get('/services/{service}/usage', 'usage')->name('services.usage');
        Route::get('/services/{service}/export', 'export')->name('services.export');
        Route::post('/services/import', 'import')->name('services.import');
        
        Route::match(['get', 'post', 'patch'], '/services/{service}/archive', 'archive')->name('services.archive');
    });

    // =======================================================
    // 2. CLIENT REQUIREMENTS MANAGEMENT ROUTES
    // =======================================================
    Route::get('/requirements', [RequirementController::class, 'index'])->name('requirements.index');
    Route::post('/requirements', [RequirementController::class, 'store'])->name('requirements.store');
    Route::get('/requirements/{id}/view', [RequirementController::class, 'viewDocument'])->name('requirements.view');
    Route::post('/requirements/{id}/upload', [RequirementController::class, 'upload'])->name('requirements.upload');
    Route::post('/requirements/{id}/verify', [RequirementController::class, 'verify'])->name('requirements.verify');
    Route::delete('/requirements/{id}', [RequirementController::class, 'destroy'])->name('requirements.destroy');

    // =======================================================
    // 3. PROPOSALS & CONTRACTS INTEGRATION ROUTES
    // =======================================================
    Route::prefix('proposals')->name('proposals.')->group(function () {
        Route::get('/', [ProposalController::class, 'index'])->name('index');
        Route::post('/', [ProposalController::class, 'store'])->name('store');
        Route::get('/{proposal}', [ProposalController::class, 'show'])->name('show');
        Route::post('/{id}/status', [ProposalController::class, 'updateStatus'])->name('updateStatus');
        Route::delete('/{proposal}', [ProposalController::class, 'destroy'])->name('destroy');
    });

    // =======================================================
    // 4. OPERATIONAL TASKS & ENGAGEMENTS WORKSPACE ROUTES
    // =======================================================
    Route::get('/engagements', function () {

        if (Schema::hasTable('engagements')) {
            $query = DB::table('engagements');
            $selects = ['engagements.*'];

            if (
                Schema::hasTable('services') &&
                Schema::hasColumn('engagements', 'service_id')
            ) {
                $query->leftJoin('services', 'engagements.service_id', '=', 'services.id');
                $selects[] = 'services.name as service_name';
            }

            $engagements = $query
                ->select($selects)
                ->latest('engagements.created_at')
                ->get();

        } else {
            $engagements = collect([]);
        }

        $tasks = Schema::hasTable('operational_tasks')
            ? DB::table('operational_tasks')->latest()->get()
            : collect([]);

        return view('engagements.index', compact('engagements', 'tasks'));

    })->name('engagements.index');

    // STORE NEW CUSTOM OPERATIONAL TASK WITH AUDIT LOG
    Route::post('/engagements/tasks/store', function () {

        $validated = request()->validate([
            'engagement_id'          => 'required|integer',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'expected_days'          => 'required|integer|min:1',
            'expected_working_hours' => 'required|integer|min:1',
        ]);

        if (Schema::hasTable('operational_tasks')) {

            DB::table('operational_tasks')->insert([
                'engagement_id'          => $validated['engagement_id'],
                'title'                  => $validated['title'],
                'description'            => $validated['description'] ?? null,
                'expected_days'          => $validated['expected_days'],
                'expected_working_hours' => $validated['expected_working_hours'],
                'is_billable'            => request()->has('is_billable'),
                'status'                 => 'pending',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            if (class_exists(AuditLog::class)) {
                AuditLog::log(
                    'TASK_CREATED',
                    'Engagements',
                    "Created new operational task '{$validated['title']}' for Engagement #{$validated['engagement_id']}."
                );
            }

            if (Schema::hasTable('engagements')) {
                DB::table('engagements')
                    ->where('id', $validated['engagement_id'])
                    ->update([
                        'status' => 'active',
                        'updated_at' => now()
                    ]);
            }
        }

        return back()->with('success', 'Custom task added successfully!');

    })->name('engagements.tasks.store');

    // FIX TASK POPULATION
    Route::get('/fix-tasks-now', function () {
        if (!Schema::hasTable('operational_tasks')) {
            Schema::create('operational_tasks', function ($table) {
                $table->id();
                $table->unsignedBigInteger('engagement_id')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->integer('expected_days')->default(1);
                $table->integer('expected_working_hours')->default(8);
                $table->boolean('is_billable')->default(true);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('engagements')) {
            return "Engagements table does not exist yet. Please accept a proposal first.";
        }

        DB::table('operational_tasks')->truncate();

        $engagements = DB::table('engagements')->get();
        $insertedCount = 0;

        foreach ($engagements as $eng) {
            DB::table('operational_tasks')->insert([
                'engagement_id'          => $eng->id,
                'title'                  => 'Service Delivery & Core Execution Phase',
                'description'            => 'Execute core deliverables agreed upon in proposal contract.',
                'expected_days'          => 5,
                'expected_working_hours' => 40,
                'is_billable'            => true,
                'status'                 => 'pending',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
            $insertedCount++;
        }

        return "<h1>Success! Reset tasks to exactly 1 per engagement (Total tasks created: {$insertedCount}).</h1>
                <br>
                <a href='/engagements' style='font-size:18px;color:blue;'>Click here to view Engagements Dashboard</a>";
    });

    // UPDATE OPERATIONAL TASK STATUS
    Route::post('/engagements/tasks/{task}/status', [WorkspaceController::class, 'updateStatus'])
        ->name('engagements.tasks.updateStatus');

});

// =======================================================
// 5. PUBLIC CLIENT PORTAL ROUTES
// =======================================================
Route::get('/p/proposal/{token}', [ClientPortalController::class, 'showProposal'])->name('client.proposal.show');
Route::post('/p/proposal/{token}/accept', [ClientPortalController::class, 'acceptProposal'])->name('client.proposal.accept');
Route::post('/p/proposal/{token}/upload-requirement', [ClientPortalController::class, 'uploadRequirement'])->name('client.proposal.uploadRequirement');

// =======================================================
// 6. SAFE FALLBACK GET ROUTES
// =======================================================
Route::get('/services/import', function () {
    return redirect()->route('services.index');
});

Route::get('/services/{service}/version-update', function ($service) {
    return redirect()->route('services.workspace', [
        'service' => $service,
        'mode' => 'edit',
        'tab' => 'versions_history'
    ]);
});

Route::get('/services/{service}/activities', function ($service) {
    return redirect()->route('services.workspace', [
        'service' => $service,
        'mode' => 'edit',
        'tab' => 'workflow'
    ]);
});

// SAFE ACTIVITY FALLBACK ROUTE
Route::get('/services/activities/{activity}', function ($activity) {
    $act = \App\Models\ServiceActivity::find($activity);

    if ($act) {
        $serviceId = $act->serviceVersion?->service_id ?? $act->service_id;

        if ($serviceId) {
            return redirect()->route('services.workspace', [
                'service' => $serviceId,
                'mode'    => 'edit',
                'tab'     => 'workflow'
            ]);
        }
    }

    return redirect()->route('services.index');
});

Route::get('/services/{service}/activities/bulk-import', function ($service) {
    return redirect()->route('services.workspace', [
        'service' => $service,
        'mode' => 'edit',
        'tab' => 'workflow'
    ]);
});

Route::get('/services/{service}/requirements', function ($service) {
    return redirect()->route('services.workspace', [
        'service' => $service,
        'mode' => 'edit',
        'tab' => 'requirements'
    ]);
});

Route::get('/import/requirements', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/export', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/import', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/{requirement}/upload', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/{requirement}/verify', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/{requirement}/approve', function () {
    return redirect()->route('requirements.index');
});

Route::get('/requirements/{requirement}/reject', function () {
    return redirect()->route('requirements.index');
});

Route::get('/proposals/{proposal}/status', function () {
    return redirect()->route('proposals.index');
});

// =======================================================
// 7. TEST UTILITY ROUTES FOR ROLE SWITCHING
// =======================================================
Route::get('/login-client-now', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    $user = \App\Models\User::firstOrCreate(
        ['email' => 'client@example.com'],
        [
            'name' => 'Client Test User',
            'password' => bcrypt('password123'),
            'role' => 'client'
        ]
    );

    $user->update(['role' => 'client']);

    if (ClientRequirement::count() === 0) {
        $service = Service::first();
        if ($service) {
            ClientRequirement::create([
                'service_id' => $service->id,
                'document_name' => 'BIR Form 2303 (Certificate of Registration)',
                'description' => 'Mandatory document for business tax verification.',
                'client_type' => 'All',
                'source' => 'Client-supplied',
                'is_mandatory' => true,
                'file_required' => true,
                'status' => 'pending',
            ]);
        }
    }

    Auth::login($user);
    request()->session()->regenerate();

    return redirect()->route('requirements.index');
});

Route::get('/login-manager-now', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    $user = \App\Models\User::firstOrCreate(
        ['email' => 'manager@example.com'],
        [
            'name' => 'Manager Test User',
            'password' => bcrypt('password123'),
            'role' => 'manager'
        ]
    );

    $user->update(['role' => 'manager']);

    Auth::login($user);
    request()->session()->regenerate();

    return redirect()->route('requirements.index');
});