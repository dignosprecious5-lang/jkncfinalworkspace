<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ServiceTermController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTermController;
use App\Http\Controllers\ProductVersionController;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ProductImportController;


use App\Models\Service;
use App\Models\ServiceActivity;
use App\Models\ContentLibrary;
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


// ==========================================================================
// ROOT
// ==========================================================================

Route::get('/', function () {
    return redirect()->route('services.index');
});


// ==========================================================================
// DASHBOARD FALLBACK
// ==========================================================================

Route::get('/dashboard', function () {
    return redirect()->route('services.index');
})->name('dashboard');


// ==========================================================================
// DIRECT DATABASE VERIFICATION
// ==========================================================================

Route::get('/check-count', function () {

    $count = Service::count();

    return "<h1>Total Services in Database: {$count}</h1>
            <br>
            <a href='" . route('services.index') . "'
               style='font-size:16px;color:blue;'>
               Go to Services Dashboard
            </a>";
});


// ==========================================================================
// QUICK SEEDER ROUTE FOR TERMS
// ==========================================================================

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


// ==========================================================================
// FORCE SEED ROUTE
// ==========================================================================

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


// ==========================================================================
// AUTHENTICATION FALLBACK
// ==========================================================================

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


// ==========================================================================
// AUTHENTICATED ROUTES
// ==========================================================================

Route::middleware(['auth'])->group(function () {


    // ======================================================================
    // CLIENTS / ACCOUNTS
    // ======================================================================

    Route::get('/clients', function () {

        return redirect()->route('services.index');

    })->name('clients.index');


    // ======================================================================
    // AUDIT LOGS
    // ======================================================================

    Route::get('/audit-logs', function () {

        $logs = AuditLog::latest()->paginate(15);

        return view(
            'audit_logs.index',
            compact('logs')
        );

    })->name('audit_logs.index');


    // ======================================================================
    // IMPORT / EXPORT
    // ======================================================================

    Route::get(
        '/export/engagements/excel',
        [ImportExportController::class, 'exportEngagementsExcel']
    )->name('export.engagements.excel');


    Route::get(
        '/export/engagements/pdf',
        [ImportExportController::class, 'exportEngagementsPDF']
    )->name('export.engagements.pdf');


    /*
    |--------------------------------------------------------------------------
    | NOTE:
    | Standalone Requirements Export / Import routes removed.
    |
    | Requirements are now managed inside:
    | Service Workspace → Requirements Tab
    |--------------------------------------------------------------------------
    */


    // ======================================================================
    // NOTIFICATION SYSTEM
    // ======================================================================

    Route::post(
        '/notifications/mark-all-read',
        function () {

            if (auth()->check()) {
                auth()->user()->unreadNotifications->markAsRead();
            }

            return response()->json([
                'success' => true
            ]);
        }
    )->name('notifications.markAllRead');


    Route::post(
        '/notifications/{id}/mark-read',
        function ($id) {

            if (auth()->check()) {

                $notification = auth()
                    ->user()
                    ->notifications()
                    ->where('id', $id)
                    ->first();

                if ($notification) {
                    $notification->markAsRead();
                }
            }

            return response()->json([
                'success' => true
            ]);
        }
    )->name('notifications.markRead');


    // ======================================================================
    // MAIN SERVICE CONTROLLER ROUTES
    // ======================================================================

    Route::controller(ServiceController::class)->group(function () {


        // ------------------------------------------------------------------
        // SERVICES DIRECTORY
        // ------------------------------------------------------------------

        Route::get(
            '/services',
            'index'
        )->name('services.index');


        Route::post(
            '/services',
            'store'
        )->name('services.store');


        // ------------------------------------------------------------------
        // SERVICES REPORTS
        // ------------------------------------------------------------------

        Route::get(
            '/services/reports',
            'reports'
        )->name('services.reports');
// ------------------------------------------------------------------
        // SERVICES PDF
        // ------------------------------------------------------------------


      Route::get('/services/export-all', [ServiceController::class, 'exportReportsPdf'])->name('services.export_all');

        // ------------------------------------------------------------------
        // SERVICE SHOW
        // ------------------------------------------------------------------

        Route::get(
            '/services/{service}',
            function ($service) {

                return redirect()->route(
                    'services.workspace',
                    [
                        'service' => $service,
                        'mode' => 'view',
                    ]
                );

            }
        )->name('services.show');


        // ------------------------------------------------------------------
        // SERVICE WORKSPACE
        // ------------------------------------------------------------------

        Route::get(
            '/services/{service}/workspace',
            'workspace'
        )->name('services.workspace');


        // ------------------------------------------------------------------
        // VERSION UPDATE
        // ------------------------------------------------------------------

        Route::match(
            ['put', 'post'],
            '/services/{service}/version-update',
            'updateVersion'
        )->name('services.version.update');


        // ------------------------------------------------------------------
        // AUDIT LOGS
        // ------------------------------------------------------------------

        Route::get(
            '/services/{service}/audit-logs',
            'getAuditLogs'
        )->name('services.audit_logs');


        // ------------------------------------------------------------------
        // APPROVAL ACTIONS
        // ------------------------------------------------------------------

       Route::match(
            ['get', 'post', 'put'],
            '/services/{service}/submit-approval',
            'submitForApproval'
        )->name('services.submit_approval');


        Route::post(
            '/services/{service}/approve',
            'approve'
        )->name('services.approve');


        Route::post(
            '/services/{service}/reject',
            'reject'
        )->name('services.reject');


        // ------------------------------------------------------------------
        // WORKFLOW & ACTIVITY MANAGEMENT
        // ------------------------------------------------------------------

        Route::post(
            '/services/{service}/activities',
            'storeActivity'
        )->name('services.activities.store');


        Route::put(
            '/services/activities/{activity}',
            'updateActivity'
        )->name('services.activities.update');


        Route::delete(
            '/services/activities/{activity}',
            'destroyActivity'
        )->name('services.activities.destroy');


        // ------------------------------------------------------------------
        // BULK ACTIVITY IMPORT
        // ------------------------------------------------------------------

        Route::match(
            ['get', 'post'],
            '/services/{service}/activities/bulk-import',
            function (
                \Illuminate\Http\Request $request,
                Service $service
            ) {

                $activeVersion =
                    $service->activeVersion;


                if (!$activeVersion) {

                    $activeVersion =
                        \App\Models\ServiceVersion::query()
                            ->where(
                                'service_id',
                                $service->id
                            )
                            ->latest('created_at')
                            ->first();
                }


                if (!$activeVersion) {

                    return back()->with(
                        'error',
                        'No active version found for this service.'
                    );
                }


                $defaultDays =
                    $request->input(
                        'default_expected_days',
                        1
                    );


                $defaultHours =
                    $request->input(
                        'default_working_hours',
                        1.0
                    );


                $rawText = '';


                if (
                    $request->hasFile(
                        'import_file'
                    )
                ) {

                    $file =
                        $request->file(
                            'import_file'
                        );

                    $rawText =
                        file_get_contents(
                            $file->getRealPath()
                        );

                } elseif (
                    $request->filled(
                        'outline_text'
                    )
                ) {

                    $rawText =
                        $request->input(
                            'outline_text'
                        );
                }


                if (
                    !empty($rawText)
                ) {

                    $lines =
                        explode(
                            "\n",
                            $rawText
                        );


                    $sequence = 1;

                    $currentParent = null;


                    foreach (
                        $lines
                        as $line
                    ) {

                        $trimmed =
                            trim($line);


                        if (
                            empty($trimmed)
                        ) {
                            continue;
                        }


                        $isSub =
                            str_starts_with(
                                $line,
                                "    "
                            )
                            ||
                            str_starts_with(
                                $line,
                                "\t"
                            );


                        $activity =
                            new ServiceActivity();


                        $activity->service_version_id =
                            $activeVersion->id;


                        $activity->name =
                            $trimmed;


                        $activity->sequence =
                            $sequence++;


                        $activity->expected_days =
                            $defaultDays;


                        $activity->expected_working_hours =
                            $defaultHours;


                        $activity->is_mandatory =
                            true;


                        $activity->is_billable =
                            true;


                        if (
                            $isSub
                            &&
                            $currentParent
                        ) {

                            $activity->parent_id =
                                $currentParent->id;

                        } else {

                            $activity->parent_id =
                                null;

                            $currentParent =
                                $activity;
                        }


                        $activity->save();
                    }
                }


                return back()->with(
                    'success',
                    'Activities imported successfully from file/text!'
                );

            }
        )->name(
            'services.activities.bulk_import'
        );


        Route::post(
            '/services/{service}/activities/bulk-action',
            'bulkActionActivities'
        )->name(
            'services.activities.bulk_action'
        );


        Route::delete(
            '/services/{service}/activities/bulk-destroy',
            'bulkDestroyActivities'
        )->name(
            'services.activities.bulk_destroy'
        );


        Route::delete(
            '/services/{service}/activities/destroy-all',
            'destroyAllActivities'
        )->name(
            'services.activities.destroy_all'
        );


        // ------------------------------------------------------------------
        // TERMS & REQUIREMENTS
        // ------------------------------------------------------------------

        Route::get(
            '/services/{service}/inherited-terms',
            'getInheritedTerms'
        )->name(
            'services.inherited_terms'
        );


        /*
        |--------------------------------------------------------------------------
        | REQUIREMENTS INSIDE SERVICE WORKSPACE
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | These routes are KEPT.
        |
        | This is the Requirements Tab 3 of Service Workspace.
        |--------------------------------------------------------------------------
        */


        Route::post(
            '/services/{service}/requirements',
            'storeRequirement'
        )->name(
            'services.requirements.store'
        );


        Route::put(
            '/services/requirements/{requirement}',
            'updateRequirement'
        )->name(
            'services.requirements.update'
        );


        Route::delete(
            '/services/requirements/{requirement}',
            'destroyRequirement'
        )->name(
            'services.requirements.destroy'
        );


        Route::post(
            '/services/{service}/requirements/bulk-template',
            'bulkToTemplateLibrary'
        )->name(
            'services.requirements.bulk-template'
        );


        // ------------------------------------------------------------------
        // SERVICE UTILITY OPERATIONS
        // ------------------------------------------------------------------

        Route::post(
            '/services/{service}/duplicate',
            'duplicate'
        )->name(
            'services.duplicate'
        );


        Route::get(
            '/services/{service}/usage',
            'usage'
        )->name(
            'services.usage'
        );


        Route::get(
            '/services/{service}/export',
            'export'
        )->name(
            'services.export'
        );


        Route::get(
            '/services/{service}/export-single',
            'export'
        )->name(
            'services.export_single'
        );


        Route::post(
            '/services/import',
            'import'
        )->name(
            'services.import'
        );


        Route::match(
            ['get', 'post', 'patch'],
            '/services/{service}/archive',
            'archive'
        )->name(
            'services.archive'
        );

    });


    // ======================================================================
    // SERVICE TERMS & TEMPLATES
    // ======================================================================

    Route::post(
        '/service-versions/{serviceVersion}/terms',
        [ServiceTermController::class, 'store']
    )->name(
        'service-terms.store'
    );


    Route::put(
        '/service-terms/{serviceTerm}',
        [ServiceTermController::class, 'update']
    )->name(
        'service-terms.update'
    );


    Route::post(
        '/service-versions/{serviceVersion}/terms/{serviceTerm}/duplicate',
        [ServiceTermController::class, 'duplicate']
    )->name(
        'service-terms.duplicate'
    );


    Route::patch(
        '/service-terms/{serviceTerm}/disable',
        [ServiceTermController::class, 'disable']
    )->name(
        'service-terms.disable'
    );


    Route::patch(
        '/terms-templates/{termsTemplate}/disable',
        [ServiceTermController::class, 'disableTemplate']
    )->name(
        'service-terms.template.disable'
    );


    Route::match(
        ['get', 'post'],
        '/service-versions/{serviceVersion}/terms/template',
        [ServiceTermController::class, 'storeTemplate']
    )->name(
        'service-terms.template.store'
    );


    Route::match(
        ['get', 'post'],
        '/service-versions/{serviceVersion}/terms/apply-template',
        [ServiceTermController::class, 'applyTemplate']
    )->name(
        'service-terms.template.apply'
    );


    // ======================================================================
    // OPERATIONAL TASKS & ENGAGEMENTS
    // ======================================================================

    Route::get(
        '/engagements',
        function () {

            if (
                Schema::hasTable(
                    'engagements'
                )
            ) {

                $query =
                    DB::table(
                        'engagements'
                    );


                $selects = [
                    'engagements.*'
                ];


                if (
                    Schema::hasTable(
                        'services'
                    )
                    &&
                    Schema::hasColumn(
                        'engagements',
                        'service_id'
                    )
                ) {

                    $query->leftJoin(
                        'services',
                        'engagements.service_id',
                        '=',
                        'services.id'
                    );


                    $selects[] =
                        'services.name as service_name';
                }


                $engagements =
                    $query
                        ->select(
                            $selects
                        )
                        ->latest(
                            'engagements.created_at'
                        )
                        ->get();

            } else {

                $engagements =
                    collect([]);
            }


            $tasks =
                Schema::hasTable(
                    'operational_tasks'
                )
                ? DB::table(
                    'operational_tasks'
                )->latest()->get()
                : collect([]);


            return view(
                'engagements.index',
                compact(
                    'engagements',
                    'tasks'
                )
            );

        }
    )->name(
        'engagements.index'
    );


    // ----------------------------------------------------------------------
    // STORE OPERATIONAL TASK
    // ----------------------------------------------------------------------

    Route::post(
        '/engagements/tasks/store',
        function () {

            $validated =
                request()->validate([
                    'engagement_id' =>
                        'required|integer',

                    'title' =>
                        'required|string|max:255',

                    'description' =>
                        'nullable|string',

                    'expected_days' =>
                        'required|integer|min:1',

                    'expected_working_hours' =>
                        'required|integer|min:1',
                ]);


            if (
                Schema::hasTable(
                    'operational_tasks'
                )
            ) {

                DB::table(
                    'operational_tasks'
                )->insert([

                    'engagement_id' =>
                        $validated['engagement_id'],

                    'title' =>
                        $validated['title'],

                    'description' =>
                        $validated['description']
                        ?? null,

                    'expected_days' =>
                        $validated['expected_days'],

                    'expected_working_hours' =>
                        $validated['expected_working_hours'],

                    'is_billable' =>
                        request()->has(
                            'is_billable'
                        ),

                    'status' =>
                        'pending',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


                if (
                    class_exists(
                        AuditLog::class
                    )
                ) {

                    AuditLog::log(
                        'TASK_CREATED',
                        'Engagements',
                        "Created new operational task '{$validated['title']}' for Engagement #{$validated['engagement_id']}."
                    );
                }


                if (
                    Schema::hasTable(
                        'engagements'
                    )
                ) {

                    DB::table(
                        'engagements'
                    )
                    ->where(
                        'id',
                        $validated['engagement_id']
                    )
                    ->update([
                        'status' =>
                            'active',

                        'updated_at' =>
                            now(),
                    ]);
                }
            }


            return back()->with(
                'success',
                'Custom task added successfully!'
            );

        }
    )->name(
        'engagements.tasks.store'
    );


    // ----------------------------------------------------------------------
    // FIX TASK POPULATION
    // ----------------------------------------------------------------------

    Route::get(
        '/fix-tasks-now',
        function () {

            if (
                !Schema::hasTable(
                    'operational_tasks'
                )
            ) {

                Schema::create(
                    'operational_tasks',
                    function ($table) {

                        $table->id();

                        $table
                            ->unsignedBigInteger(
                                'engagement_id'
                            )
                            ->nullable();

                        $table
                            ->string('title')
                            ->nullable();

                        $table
                            ->text('description')
                            ->nullable();

                        $table
                            ->integer(
                                'expected_days'
                            )
                            ->default(1);

                        $table
                            ->integer(
                                'expected_working_hours'
                            )
                            ->default(8);

                        $table
                            ->boolean(
                                'is_billable'
                            )
                            ->default(true);

                        $table
                            ->string('status')
                            ->default('pending');

                        $table->timestamps();
                    }
                );
            }


            if (
                !Schema::hasTable(
                    'engagements'
                )
            ) {

                return "Engagements table does not exist yet. Please accept a proposal first.";
            }


            DB::table(
                'operational_tasks'
            )->truncate();


            $engagements =
                DB::table(
                    'engagements'
                )->get();


            $insertedCount = 0;


            foreach (
                $engagements
                as $eng
            ) {

                DB::table(
                    'operational_tasks'
                )->insert([

                    'engagement_id' =>
                        $eng->id,

                    'title' =>
                        'Service Delivery & Core Execution Phase',

                    'description' =>
                        'Execute core deliverables agreed upon in proposal contract.',

                    'expected_days' =>
                        5,

                    'expected_working_hours' =>
                        40,

                    'is_billable' =>
                        true,

                    'status' =>
                        'pending',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);


                $insertedCount++;
            }


            return "<h1>
                        Success!
                        Reset tasks to exactly 1 per engagement
                        (Total tasks created: {$insertedCount}).
                    </h1>

                    <br>

                    <a href='/engagements'
                       style='font-size:18px;color:blue;'>
                        Click here to view Engagements Dashboard
                    </a>";

        }
    );


    // ----------------------------------------------------------------------
    // UPDATE OPERATIONAL TASK STATUS
    // ----------------------------------------------------------------------

    Route::post(
        '/engagements/tasks/{task}/status',
        [WorkspaceController::class, 'updateStatus']
    )->name(
        'engagements.tasks.updateStatus'
    );

});


// ==========================================================================
// SAFE FALLBACK ROUTES
// ==========================================================================

Route::get(
    '/services/import',
    function () {

        return redirect()->route(
            'services.index'
        );

    }
);


Route::get(
    '/services/{service}/version-update',
    function ($service) {

        return redirect()->route(
            'services.workspace',
            [
                'service' => $service,
                'mode' => 'edit',
                'tab' => 'versions_history',
            ]
        );

    }
);


Route::get(
    '/services/{service}/activities',
    function ($service) {

        return redirect()->route(
            'services.workspace',
            [
                'service' => $service,
                'mode' => 'edit',
                'tab' => 'workflow',
            ]
        );

    }
);


// ==========================================================================
// SAFE ACTIVITY FALLBACK
// ==========================================================================

Route::get(
    '/services/activities/{activity}',
    function ($activity) {

        $act =
            ServiceActivity::find(
                $activity
            );


        if ($act) {

            $serviceId =
                $act->serviceVersion?->service_id
                ??
                $act->service_id;


            if ($serviceId) {

                return redirect()->route(
                    'services.workspace',
                    [
                        'service' => $serviceId,
                        'mode' => 'edit',
                        'tab' => 'workflow',
                    ]
                );
            }
        }


        return redirect()->route(
            'services.index'
        );

    }
);


// ==========================================================================
// ACTIVITY BULK IMPORT FALLBACK
// ==========================================================================

Route::get(
    '/services/{service}/activities/bulk-import',
    function ($service) {

        return redirect()->route(
            'services.workspace',
            [
                'service' => $service,
                'mode' => 'edit',
                'tab' => 'workflow',
            ]
        );

    }
);


// ==========================================================================
// SERVICE REQUIREMENTS FALLBACK
// ==========================================================================

Route::get(
    '/services/{service}/requirements',
    function ($service) {

        return redirect()->route(
            'services.workspace',
            [
                'service' => $service,
                'mode' => 'edit',
                'tab' => 'requirements',
            ]
        );

    }
);


// ==========================================================================
// NOTE:
// Standalone /requirements routes REMOVED.
// Standalone /proposals routes REMOVED.
// Public /p/proposal routes REMOVED.
// ==========================================================================


// ==========================================================================
// TEST UTILITY ROUTE - MANAGER LOGIN
// ==========================================================================

Route::get(
    '/login-manager-now',
    function () {

        Auth::logout();

        request()
            ->session()
            ->invalidate();

        request()
            ->session()
            ->regenerateToken();


        $user =
            \App\Models\User::firstOrCreate(
                [
                    'email' =>
                        'manager@example.com',
                ],
                [
                    'name' =>
                        'Manager Test User',

                    'password' =>
                        bcrypt(
                            'password123'
                        ),

                    'role' =>
                        'manager',
                ]
            );


        $user->update([
            'role' =>
                'manager',
        ]);


        Auth::login(
            $user
        );


        request()
            ->session()
            ->regenerate();


        return redirect()->route(
            'services.index'
        );

    }
);
// ======================================================================
// PRODUCTS MODULE ROUTES
// ======================================================================

Route::middleware(['auth'])->group(function () {

    Route::get('/products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::post('/products', [ProductController::class, 'store'])
        ->name('products.store');

    Route::get('/products/{id}/workspace', [ProductController::class, 'workspace'])
        ->name('products.workspace');

Route::get('/products/reports', [ProductController::class, 'reports'])->name('products.reports');

        Route::match(['get', 'post'], '/products/{id}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
Route::patch('/products/{id}/archive', [ProductController::class, 'archive'])->name('products.archive');

Route::match(['get', 'patch'], '/products/{id}/archive', [ProductController::class, 'archive'])->name('products.archive');
        
    // ==============================================================
    // STEP 1 — PRODUCT OVERVIEW
    // ==============================================================

    Route::put('/products/{id}/overview', [ProductController::class, 'updateOverview'])
        ->name('products.overview.update');

    // ==============================================================
    // STEP 2 — PRODUCT CATALOG
    // ==============================================================

    Route::put('/products/{id}/catalog', [ProductController::class, 'updateCatalog'])
        ->name('products.catalog.update');

    // ==============================================================
    // STEP 3 — INVENTORY SPECIFICATIONS
    // ==============================================================

    Route::put('/products/{id}/inventory', [ProductController::class, 'updateInventory'])
        ->name('products.inventory.update');

    // ==============================================================
    // STEP 4 — PRODUCT REQUIREMENTS
    // ==============================================================

    Route::post('/products/{id}/requirements', [ProductController::class, 'storeRequirement'])
        ->name('products.requirements.store');

    Route::put('/products/{id}/requirements/{requirementId}', [ProductController::class, 'updateRequirement'])
        ->name('products.requirements.update');

    Route::delete('/products/{id}/requirements/{requirementId}', [ProductController::class, 'destroyRequirement'])
        ->name('products.requirements.destroy');

    // ==============================================================
    // STEP 5 — PRODUCT WORKFLOW
    // ==============================================================

    Route::post('/products/{id}/activities', [ProductController::class, 'storeActivity'])
        ->name('products.activities.store');
 Route::post('/products/{id}/activities', [ProductController::class, 'storeActivity'])->name('products.activities.store');
    Route::put('/products/{id}/activities/{activityId}', [ProductController::class, 'updateActivity'])
        ->name('products.activities.update');

    Route::delete('/products/{id}/activities/{activityId}', [ProductController::class, 'destroyActivity'])
        ->name('products.activities.destroy');

    // ==============================================================
    // STEP 5 — BULK IMPORT ACTIVITIES
    // ==============================================================

    Route::post('/products/{id}/activities/bulk-import', [ProductController::class, 'bulkImportActivities'])
        ->name('products.activities.bulk-import');
});

// ==========================================================================
// TEMPLATE LIBRARY / GLOBAL REQUIREMENTS DELETE ROUTE
// ==========================================================================
Route::middleware(['auth'])->group(function () {
    Route::delete('/global-requirements/{id}', [ServiceController::class, 'destroyGlobalRequirement'])
        ->name('global-requirements.destroy');

// ==============================================================
    // STEP 6 - COMMERCIALS
    // ==============================================================

Route::put(
    '/products/{id}/commercials',
    [ProductController::class, 'updateCommercials']
)->name('products.commercials.update');

// ==============================================================
    // STEP 7 & 8 — ENGAGEMENT & REPORTING
    // ==============================================================
    Route::put('/products/{id}/engagement', [ProductController::class, 'updateEngagement'])->name('products.updateEngagement');
    Route::put('/products/{id}/reporting', [ProductController::class, 'updateReporting'])->name('products.updateReporting');

//==============================================================
    // STEP 9 - AUTOMATION
    // ==============================================================
    
    Route::put('/products/{id}/automation', [ProductController::class, 'updateAutomation'])->name('products.updateAutomation');

// =========================================================
// STEP 10- PRODUCT TERMS & AGREEMENTS
// =========================================================

Route::post('/products/{product}/terms', [ProductTermController::class, 'store'])
    ->name('product-terms.store');

Route::put('/products/{productTerm}/terms', [ProductTermController::class, 'update'])
    ->name('product-terms.update');

Route::post('/products/{product}/terms/{productTerm}/duplicate', [ProductTermController::class, 'duplicate'])
    ->name('product-terms.duplicate');

Route::patch('/products/{productTerm}/terms/disable', [ProductTermController::class, 'disable'])
    ->name('product-terms.disable');

Route::delete('/products/{productTerm}/terms', [ProductTermController::class, 'destroy'])
    ->name('product-terms.destroy');

Route::post('/products/{product}/terms/templates', [ProductTermController::class, 'storeTemplate'])
    ->name('product-terms.templates.store');

Route::post('/products/{product}/terms/apply-template', [ProductTermController::class, 'applyTemplate'])
    ->name('product-terms.apply-template');
Route::post('/products/{id}/terms', [App\Http\Controllers\ProductController::class, 'storeTerm'])->name('products.terms.store');

// Idagdag ang route na ito:
Route::patch('/terms-templates/{id}/disable', [ProductTermController::class, 'disableTemplate'])->name('terms-templates.disable');

Route::patch('/product-terms/{productTerm}/disable', [ProductTermController::class, 'disable'])->name('product-terms.disable');
Route::put('/product-terms/{term}', [ProductTermController::class, 'update'])->name('product-terms.update');

Route::post('/products/{product}/terms/{productTerm}/save-as-template', [ProductTermController::class, 'saveAsTemplate'])->name('product-terms.save-as-template');
// =========================================================
// CVS & PDF EXPORT & import
// =========================================================

    Route::get('/products/export-csv', [ProductController::class, 'exportCsv'])->name('products.export.csv');
Route::get('/products/export-pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');
Route::get('/products/export-all', [ProductExportController::class, 'exportAll']);

Route::get('/products/import', [ProductImportController::class, 'showImportForm'])->name('products.import.form');

// Para tanggapin at i-process ang in-upload na file (CSV/Excel)
Route::post('/products/import', [ProductImportController::class, 'storeImport'])->name('products.import.store');


// =========================================================
// PRODUCT VERSION / HISTORY
// =========================================================

Route::get(
    '/products/{product}/versions',
    [ProductVersionController::class, 'index']
)->name('product-versions.index');

Route::post(
    '/products/{product}/versions',
    [ProductVersionController::class, 'store']
)->name('product-versions.store');

Route::patch(
    '/product-versions/{productVersion}/activate',
    [ProductVersionController::class, 'activate']
)->name('product-versions.activate');

Route::patch(
    '/product-versions/{productVersion}/status',
    [ProductVersionController::class, 'updateStatus']
)->name('product-versions.status');


// =========================================================
// costum field
// =========================================================


Route::post(
    '/products/{id}/custom-fields',
    [ProductController::class, 'storeCustomField']
)->name('products.custom-fields.store');




// =========================================================
// submit for approval
// =========================================================

Route::post('/products/{id}/submit-approval', [ProductController::class, 'submitApproval'])->name('products.submit-approval');

    });

    