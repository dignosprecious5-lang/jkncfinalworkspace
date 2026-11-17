<?php

use App\Http\Controllers\TransmittalV2\ExternalAcknowledgmentController;
use App\Http\Controllers\TransmittalV2\TransmittalController;
use App\Http\Controllers\TransmittalV2\TransmittalUploadController;
use App\Http\Controllers\TransmittalV2\TransmittalProviderEventController;
use App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Support\Facades\Route;

/*
 * Registered for local/staging integration, but unavailable unless the V2
 * feature flag is explicitly enabled. Keep these separate from V1 routes.
 */
Route::middleware(EnsureTransmittalV2Enabled::class)->group(function (): void {
    // Authenticated CRM Staff Routes
    Route::middleware(['auth', 'prevent-back-history', EnsurePasswordChanged::class])->group(function (): void {
        Route::get('/transmittals', [TransmittalController::class, 'index'])->name('transmittals.index');
        Route::get('/transmittals/create', [TransmittalController::class, 'create'])->name('transmittals.create');
        Route::get('/transmittals/smart-upload', [TransmittalController::class, 'smartUpload'])->middleware(['throttle:30,1', \App\Http\Middleware\TransmittalV2\EnsureTransmittalV2OptionalFeatureEnabled::class.':smart_upload'])->name('transmittals.smart-upload');
        Route::post('/transmittals/smart-upload', [TransmittalController::class, 'createFromSmartUpload'])->middleware(['throttle:10,1', \App\Http\Middleware\TransmittalV2\EnsureTransmittalV2OptionalFeatureEnabled::class.':smart_upload'])->name('transmittals.smart-upload.store');
        Route::get('/transmittals/{transmittal}/workspace', [TransmittalController::class, 'workspace'])->name('transmittals.workspace');
        Route::get('/transmittals/{transmittal}/edit', [TransmittalController::class, 'edit'])->name('transmittals.edit');
        Route::put('/transmittals/{transmittal}', [TransmittalController::class, 'update'])->middleware('throttle:10,1')->name('transmittals.update');
        Route::post('/transmittals/analyze', [TransmittalController::class, 'analyze'])->middleware(['throttle:10,1', \App\Http\Middleware\TransmittalV2\EnsureTransmittalV2OptionalFeatureEnabled::class.':ocr'])->name('transmittals.analyze');
        Route::post('/transmittals/uploads', [TransmittalUploadController::class, 'start'])->middleware('throttle:30,1')->name('transmittals.uploads.start');
        Route::post('/transmittals/uploads/{uploadId}/chunks/{index}', [TransmittalUploadController::class, 'putChunk'])->middleware('throttle:120,1')->name('transmittals.uploads.chunks.store');
        Route::post('/transmittals/uploads/{uploadId}/complete', [TransmittalUploadController::class, 'complete'])->middleware('throttle:30,1')->name('transmittals.uploads.complete');
        Route::post('/transmittals/uploads/{uploadId}/analyze', [TransmittalUploadController::class, 'analyze'])->middleware(['throttle:10,1', \App\Http\Middleware\TransmittalV2\EnsureTransmittalV2OptionalFeatureEnabled::class.':ocr'])->name('transmittals.uploads.analyze');
        Route::post('/transmittals', [TransmittalController::class, 'store'])->middleware('throttle:10,1')->name('transmittals.store');
        Route::post('/transmittals/{transmittal}/recall', [TransmittalController::class, 'recall'])->middleware('throttle:10,1')->name('transmittals.recall');
        Route::post('/transmittals/{transmittal}/cancel', [TransmittalController::class, 'cancel'])->middleware('throttle:10,1')->name('transmittals.cancel');
        Route::get('/transmittals/{transmittal}', [TransmittalController::class, 'show'])->name('transmittals.show');
        Route::get('/transmittals/{transmittal}/receipts/{receipt}/pdf', [TransmittalController::class, 'receiptPdf'])->name('transmittals.receipts.pdf');
        Route::get('/transmittals/{transmittal}/documents/{document}', [TransmittalController::class, 'download'])->name('transmittals.documents.download');
        Route::get('/transmittals/{transmittal}/pdf', [TransmittalController::class, 'pdf'])->name('transmittals.pdf');
        Route::post('/transmittals/{transmittal}/proofs', [TransmittalController::class, 'uploadProof'])->middleware('throttle:10,1')->name('transmittals.proofs.store');
        Route::post('/transmittals/{transmittal}/transmit', [TransmittalController::class, 'transmit'])->middleware('throttle:10,1')->name('transmittals.transmit');
        Route::post('/transmittal-dispatches/{dispatch}/manual-resend', [TransmittalController::class, 'manualResend'])->middleware('throttle:5,1')->name('transmittal-v2.dispatches.manual-resend');
        Route::post('/transmittals/{transmittal}/approve', [TransmittalController::class, 'approve'])->middleware('throttle:10,1')->name('transmittals.approve');
        Route::post('/transmittals/{transmittal}/reject', [TransmittalController::class, 'reject'])->middleware('throttle:10,1')->name('transmittals.reject');
        Route::put('/transmittals/{transmittal}/proofs/{proof}', [TransmittalController::class, 'updateProof'])->middleware('throttle:10,1')->name('transmittals.proofs.update');
        Route::post('/transmittals/{transmittal}/recipients/{recipient}/revoke-link', [TransmittalController::class, 'revokeRecipientLink'])->middleware('throttle:10,1')->name('transmittals.recipients.revoke-link');
        Route::post('/transmittals/{transmittal}/submit-for-approval', [TransmittalController::class, 'submitForApproval'])->middleware('throttle:10,1')->name('transmittals.submit-for-approval');
    });

    // Webhooks & External Public Client Routes (No auth required)
    Route::post('/transmittal-provider-events/{provider}', TransmittalProviderEventController::class)
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->middleware('throttle:60,1')->name('transmittal-v2.provider-events.store');

    Route::get('/acknowledgments/{token}', [ExternalAcknowledgmentController::class, 'show'])->middleware('throttle:20,1')->name('transmittal-v2.acknowledgments.show');
    Route::post('/acknowledgments/{token}', [ExternalAcknowledgmentController::class, 'store'])->middleware('throttle:6,1')->name('transmittal-v2.acknowledgments.store');
    Route::get('/acknowledgments/{token}/documents/{document}', [TransmittalController::class, 'downloadForRecipient'])->middleware('throttle:30,1')->name('transmittal-v2.acknowledgments.documents.download');
    Route::get('/acknowledgments/{token}/receipts/{receipt}/pdf', [TransmittalController::class, 'receiptPdfForRecipient'])->middleware('throttle:20,1')->name('transmittal-v2.acknowledgments.receipts.pdf');
});
