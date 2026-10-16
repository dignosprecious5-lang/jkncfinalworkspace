<?php

use App\Http\Controllers\RecordIntake\UploadController;
use App\Http\Controllers\Records\ClassifyController;
use App\Http\Controllers\Records\RecordActivityLogController;
use App\Http\Controllers\Records\RecordArchiveController;
use App\Http\Controllers\Records\RecordClassificationController;
use App\Http\Controllers\Records\RecordController;
use App\Http\Controllers\Records\RecordDashboardController;
use App\Http\Controllers\Records\RecordFieldController;
use App\Http\Controllers\Records\RecordFileAttachmentController;
use App\Http\Controllers\Records\RecordFilePreviewController;
use App\Http\Controllers\Records\RecordFileReadingController;
use App\Http\Controllers\Records\RecordFileRenderController;
use App\Http\Controllers\Records\RecordIntegrationController;
use App\Http\Controllers\Records\RecordNumberingController;
use App\Http\Controllers\Records\RecordPickerController;
use App\Http\Controllers\Records\RecordPlanLimitsController;
use App\Http\Controllers\Records\RecordReportController;
use App\Http\Controllers\Records\RecordSetupController;
use App\Http\Controllers\Records\RecordSourceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/records/unclassified');

/*
|-------------------------------------------------------------------------------
| Records routes
|-------------------------------------------------------------------------------
|
| Every route below runs under ordo.context, which resolves the active company,
| and any route that takes a record id also runs under ordo.record, which
| resolves that record inside the company's scope. A record belonging to another
| company is a 404 there, before any controller runs, so its existence is never
| confirmed.
|
| Role permissions live in App\Policies\RecordPolicy and are registered as gates
| in AppServiceProvider. They are not yet enforced as middleware: the prototype
| has no sign-in, so an enforcing gate would deny every request. RecordSecurity
| PolicyTest is what exercises the policy today, and it is the same file to read
| to see what each role may do. Enforcement attaches by adding `can:` to a route
| once authentication exists — see the middleware table in that test.
|
*/
Route::middleware('ordo.context')->group(function (): void {
    Route::get('/records/classifications', [RecordClassificationController::class, 'index'])
        ->name('records.classifications');

    Route::post('/records/classifications', [RecordClassificationController::class, 'storeClassification'])
        ->name('records.classifications.store');

    Route::post('/records/classifications/{classification}/indexes', [RecordClassificationController::class, 'storeIndex'])
        ->name('records.classifications.indexes.store');

    // The archive listing. Named .index because records.archive is already the
    // POST that archives a record, which stays the name the policy table uses.
    Route::get('/records/archive', [RecordArchiveController::class, 'index'])
        ->name('records.archive.index');

    Route::post('/records/{record}/restore', [RecordArchiveController::class, 'restore'])
        ->middleware('ordo.record')
        ->name('records.restore');

    Route::get('/records/reports', [RecordReportController::class, 'index'])
        ->name('records.reports.index');

    Route::post('/records/reports/generate', [RecordReportController::class, 'generate'])
        ->name('records.reports.generate');

    // Recording a report that has been presented or downloaded. Rate limited
    // alongside the export, because recording one recomputes the figures.
    Route::post('/records/reports/runs', [RecordReportController::class, 'store'])
        ->middleware('throttle:exports')
        ->name('records.reports.runs.store');

    // The report history list, served as a fragment the modal injects.
    Route::get('/records/reports/history', [RecordReportController::class, 'history'])
        ->name('records.reports.history');

    // The export scans beyond a single page, so it follows the other exports.
    Route::get('/records/reports/export', [RecordReportController::class, 'export'])
        ->middleware('throttle:exports')
        ->name('records.reports.export');

    Route::get('/records/intake/upload', [UploadController::class, 'create'])
        ->name('records.intake.upload');

    Route::post('/records/intake', [UploadController::class, 'store'])
        ->name('records.intake.store');

    Route::get('/records/unclassified', [ClassifyController::class, 'index'])
        ->name('records.unclassified');

    Route::post('/records/unclassified/bulk', [ClassifyController::class, 'bulkStore'])
        ->name('records.unclassified.bulk');

    Route::post('/records/unclassified/{intake}', [ClassifyController::class, 'store'])
        ->name('records.unclassified.file');

    Route::get('/records/unclassified/{intake}/preview', [ClassifyController::class, 'preview'])
        ->name('records.unclassified.preview');

    Route::delete('/records/unclassified/{intake}', [ClassifyController::class, 'destroy'])
        ->name('records.unclassified.destroy');

    Route::get('/records', [RecordController::class, 'index'])
        ->name('records.index');

    Route::get('/records/dashboard', [RecordDashboardController::class, 'index'])
        ->name('records.dashboard');

    Route::get('/records/export', [RecordController::class, 'export'])
        ->middleware('throttle:exports')
        ->name('records.export');

    Route::get('/records/logs', [RecordActivityLogController::class, 'index'])
        ->name('records.logs');

    Route::get('/records/logs/export', [RecordActivityLogController::class, 'export'])
        ->middleware('throttle:exports')
        ->name('records.logs.export');

    Route::delete('/records/links/{link}', [RecordPickerController::class, 'destroy'])
        ->name('records.links.destroy');

    // The Integration tab's two actions. Both are real: Simulate files a record
    // through the ordinary intake and filing path, and the Global Record Picker
    // posts to the same link endpoint the record drawer uses.
    Route::post('/records/integration/simulate', [RecordIntegrationController::class, 'simulate'])
        ->name('records.integration.simulate');

    // The picker searches across the register, so it is both scoped and rate limited.
    Route::get('/records/{record}/links/picker', [RecordPickerController::class, 'index'])
        ->middleware(['ordo.record', 'throttle:picker'])
        ->name('records.links.picker');

    Route::post('/records/{record}/links', [RecordPickerController::class, 'store'])
        ->middleware('ordo.record')
        ->name('records.links.store');

    // ============ RECORDS MANAGEMENT SETUP ============
    //
    // The modal lives in the layout, so these serve its tabs to every page. Each
    // tab is fetched when opened, which is why the modal costs no queries on an
    // ordinary page load. The writes are ordinary posts that redirect back, with
    // `setup_tab` telling the modal which tab to reopen on.

    Route::get('/records/setup/{tab}', [RecordSetupController::class, 'tab'])
        ->whereIn('tab', RecordSetupController::TABS)
        ->name('records.setup.tab');

    Route::post('/records/sources', [RecordSourceController::class, 'store'])
        ->name('records.sources.store');

    Route::post('/records/fields', [RecordFieldController::class, 'store'])
        ->name('records.fields.store');

    // The Repository Usage card's ceilings. Behind the setup modal's Usage tab,
    // so it redirects back there and reopens on that tab.
    Route::put('/records/plan-limits', [RecordPlanLimitsController::class, 'update'])
        ->name('records.plan-limits.update');

    // The Record Numbering tab's settings. Affects the Record ID issued to every
    // record filed from now on; never the Document Reference, which stays
    // system-controlled.
    Route::put('/records/numbering', [RecordNumberingController::class, 'update'])
        ->name('records.numbering.update');

    Route::put('/records/{record}', [RecordController::class, 'update'])
        ->middleware('ordo.record')
        ->name('records.update');

    Route::post('/records/{record}/archive', [RecordController::class, 'archive'])
        ->middleware('ordo.record')
        ->name('records.archive');

    // ============ FILES TAB ACTIONS ============
    //
    // The three actions the reference mock's Files tab offers. Supporting files
    // and versions attach bytes to a Record already on the books and consume no
    // further Record Unit; uploading as a Record files something new and consumes
    // one. The difference is enforced in RecordAttachmentService, not here.
    Route::post('/records/{record}/files/supporting', [RecordFileAttachmentController::class, 'supportingFile'])
        ->middleware('ordo.record')
        ->name('records.files.supporting');

    Route::post('/records/{record}/files/version', [RecordFileAttachmentController::class, 'version'])
        ->middleware('ordo.record')
        ->name('records.files.version');

    Route::post('/records/files/upload-as-record', [RecordFileAttachmentController::class, 'uploadAsRecord'])
        ->name('records.files.upload-as-record');

    // ============ DOCUMENT RE-READING ============
    //
    // The same "Re-read Document" control sits in two previews: the record
    // workspace and the classify drawer. The file is what is being read, and it
    // is resolved against the active company, so the route carries the file alone
    // and serves both callers.
    //
    // Deliberately one path parameter. Every other route here takes a single id
    // and Laravel hands it to the controller by position, so a second parameter
    // would be silently swallowed — a method declaring `string $file` against
    // /records/{record}/files/{file}/… receives the record, and a model casts to
    // its own JSON when a string is demanded, so it arrives as a plausible string
    // that matches nothing. One parameter in, one parameter out, like the rest.
    Route::post('/records/files/{file}/re-read', [RecordFileReadingController::class, 'reRead'])
        ->name('records.files.re-read');

    // Preview one named file. The record and intake routes above address a
    // preview by its owner, which is only ever the current master; this one names
    // the file, so a record's supporting files and versions are openable too.
    // Single parameter, passed positionally — see RecordFilePreviewController.
    Route::get('/records/files/{file}/preview', [RecordFilePreviewController::class, 'preview'])
        ->name('records.files.preview');

    // Word and Excel documents shown as a page. Separate from the preview route
    // because this is the one response in the app that is HTML on purpose, and it
    // is sandboxed accordingly. See RecordFileRenderController.
    Route::get('/records/files/{file}/rendered', [RecordFileRenderController::class, 'rendered'])
        ->name('records.files.rendered');

    // Polled while a read is in flight so the preview shows the file's real state
    // rather than a progress bar that is only guessing.
    Route::get('/records/files/{file}/status', [RecordFileReadingController::class, 'status'])
        ->name('records.files.status');

    Route::get('/records/{record}/preview', [RecordController::class, 'preview'])
        ->middleware('ordo.record')
        ->name('records.preview');
});
