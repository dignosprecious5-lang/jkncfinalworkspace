<?php

use App\Http\Controllers\RecordIntake\UploadController;
use App\Http\Controllers\Records\ClassifyController;
use App\Http\Controllers\Records\RecordActivityLogController;
use App\Http\Controllers\Records\RecordArchiveController;
use App\Http\Controllers\Records\RecordClassificationController;
use App\Http\Controllers\Records\RecordController;
use App\Http\Controllers\Records\RecordLabelController;
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

Route::get('/login', function () {
    abort(401, 'Authentication required to access JK&C ORDO Records Management.');
})->name('login');

/*
|-------------------------------------------------------------------------------
| Records routes
|-------------------------------------------------------------------------------
|
| Every route below runs under auth and ordo.context, which requires an
| authenticated user and resolves the active company. Any route taking a record
| id also runs under ordo.record, which resolves that record inside company scope.
| Server-side authorization gates (can:...) enforce granular role permissions.
|
*/
Route::middleware('ordo.context')->group(function (): void {
    Route::get('/records/classifications', [RecordClassificationController::class, 'index'])
        ->middleware('can:viewAny,App\Models\RecordClassification')
        ->name('records.classifications');

    Route::post('/records/classifications', [RecordClassificationController::class, 'storeClassification'])
        ->middleware('can:manageScheme')
        ->name('records.classifications.store');

    Route::post('/records/classifications/{classification}/indexes', [RecordClassificationController::class, 'storeIndex'])
        ->middleware('can:manageScheme')
        ->name('records.classifications.indexes.store');

    Route::match(['put', 'patch', 'post'], '/records/classifications/{classification}/update', [RecordClassificationController::class, 'updateClassification'])
        ->middleware('can:manageScheme')
        ->name('records.classifications.update');

    Route::match(['put', 'patch', 'post'], '/records/classifications/{classification}/indexes/{index}/update', [RecordClassificationController::class, 'updateIndex'])
        ->middleware('can:manageScheme')
        ->name('records.classifications.indexes.update');

    // The archive listing. Named .index because records.archive is already the
    // POST that archives a record, which stays the name the policy table uses.
    Route::get('/records/archive', [RecordArchiveController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.archive.index');

    Route::post('/records/{record}/restore', [RecordArchiveController::class, 'restore'])
        ->middleware(['ordo.record', 'can:archive,record'])
        ->name('records.restore');

    Route::get('/records/reports', [RecordReportController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.reports.index');

    Route::post('/records/reports/generate', [RecordReportController::class, 'generate'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.reports.generate');

    // Recording a report that has been presented or downloaded. Rate limited
    // alongside the export, because recording one recomputes the figures.
    Route::post('/records/reports/runs', [RecordReportController::class, 'store'])
        ->middleware('throttle:exports')
        ->name('records.reports.runs.store');

    // The report history list, served as a fragment the modal injects.
    Route::get('/records/reports/history', [RecordReportController::class, 'history'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.reports.history');

    // The export scans beyond a single page, so it follows the other exports.
    Route::get('/records/reports/export', [RecordReportController::class, 'export'])
        ->middleware('throttle:exports')
        ->name('records.reports.export');

    Route::get('/records/intake/upload', [UploadController::class, 'create'])
        ->middleware('can:viewAny,App\Models\RecordIntakeItem')
        ->name('records.intake.upload');

    Route::post('/records/intake', [UploadController::class, 'store'])
        ->middleware('can:viewAny,App\Models\RecordIntakeItem')
        ->name('records.intake.store');

    Route::get('/records/unclassified', [ClassifyController::class, 'index'])
        ->middleware('can:viewAny,App\Models\RecordIntakeItem')
        ->name('records.unclassified');

    Route::post('/records/unclassified/bulk', [ClassifyController::class, 'bulkStore'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.unclassified.bulk');

    Route::post('/records/unclassified/{intake}', [ClassifyController::class, 'store'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.unclassified.file');

    Route::get('/records/unclassified/{intake}/preview', [ClassifyController::class, 'preview'])
        ->middleware('can:viewAny,App\Models\RecordIntakeItem')
        ->name('records.unclassified.preview');

    Route::delete('/records/unclassified/{intake}', [ClassifyController::class, 'destroy'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.unclassified.destroy');

    Route::get('/records', [RecordController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.index');

    Route::get('/records/dashboard', [RecordDashboardController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.dashboard');

    Route::get('/records/export', [RecordController::class, 'export'])
        ->middleware(['throttle:exports', 'can:export'])
        ->name('records.export');

    Route::get('/records/logs', [RecordActivityLogController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.logs');

    Route::get('/records/logs/export', [RecordActivityLogController::class, 'export'])
        ->middleware(['throttle:exports', 'can:export'])
        ->name('records.logs.export');

    Route::delete('/records/links/{link}', [RecordPickerController::class, 'destroy'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.links.destroy');

    // The Integration tab's two actions. Both are real: Simulate files a record
    // through the ordinary intake and filing path, and the Global Record Picker
    // posts to the same link endpoint the record drawer uses.
    Route::post('/records/integration/simulate', [RecordIntegrationController::class, 'simulate'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.integration.simulate');

    // The picker searches across the register, so it is both scoped and rate limited.
    Route::get('/records/{record}/links/picker', [RecordPickerController::class, 'index'])
        ->middleware(['ordo.record', 'throttle:picker', 'can:viewAny,App\Models\Record'])
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
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.setup.tab');

    Route::post('/records/sources', [RecordSourceController::class, 'store'])
        ->middleware('can:manageScheme')
        ->name('records.sources.store');

    Route::post('/records/fields', [RecordFieldController::class, 'store'])
        ->middleware('can:manageScheme')
        ->name('records.fields.store');

    // The Repository Usage card's ceilings. Behind the setup modal's Usage tab,
    // so it redirects back there and reopens on that tab.
    Route::put('/records/plan-limits', [RecordPlanLimitsController::class, 'update'])
        ->middleware('can:manageScheme')
        ->name('records.plan-limits.update');

    // The Record Numbering tab's settings. Affects the Record ID issued to every
    // record filed from now on; never the Document Reference, which stays
    // system-controlled.
    Route::put('/records/numbering', [RecordNumberingController::class, 'update'])
        ->middleware('can:manageScheme')
        ->name('records.numbering.update');

    Route::put('/records/{record}', [RecordController::class, 'update'])
        ->middleware(['ordo.record', 'can:update,record'])
        ->name('records.update');

    Route::post('/records/{record}/archive', [RecordController::class, 'archive'])
        ->middleware(['ordo.record', 'can:archive,record'])
        ->name('records.archive');

    // ============ FILES TAB ACTIONS ============
    //
    // The three actions the reference mock's Files tab offers. Supporting files
    // and versions attach bytes to a Record already on the books and consume no
    // further Record Unit; uploading as a Record files something new and consumes
    // one. The difference is enforced in RecordAttachmentService, not here.
    Route::post('/records/{record}/files/supporting', [RecordFileAttachmentController::class, 'supportingFile'])
        ->middleware(['ordo.record', 'can:update,record'])
        ->name('records.files.supporting');

    Route::post('/records/{record}/files/version', [RecordFileAttachmentController::class, 'version'])
        ->middleware(['ordo.record', 'can:update,record'])
        ->name('records.files.version');

    Route::post('/records/files/upload-as-record', [RecordFileAttachmentController::class, 'uploadAsRecord'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.files.upload-as-record');

    // ============ DOCUMENT RE-READING ============
    Route::post('/records/files/{file}/re-read', [RecordFileReadingController::class, 'reRead'])
        ->middleware('can:create,App\Models\Record')
        ->name('records.files.re-read');

    // Preview one named file.
    Route::get('/records/files/{file}/preview', [RecordFilePreviewController::class, 'preview'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.files.preview');

    Route::get('/records/files/{file}/download', [RecordFilePreviewController::class, 'download'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.files.download');

    // Word and Excel documents shown as a page.
    Route::get('/records/files/{file}/rendered', [RecordFileRenderController::class, 'rendered'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.files.rendered');

    // Polled while a read is in flight so the preview shows the file's real state
    Route::get('/records/files/{file}/status', [RecordFileReadingController::class, 'status'])
        ->middleware('can:viewAny,App\Models\Record')
        ->name('records.files.status');

    Route::get('/records/{record}/preview', [RecordController::class, 'preview'])
        ->middleware(['ordo.record', 'can:view,record'])
        ->name('records.preview');

    Route::get('/records/{record}/label', [RecordLabelController::class, 'show'])
        ->middleware(['ordo.record', 'can:view,record'])
        ->name('records.label');
});
