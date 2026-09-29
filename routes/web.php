<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DealController;
use App\Http\Controllers\StartController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =====================================================
// HOME
// =====================================================

Route::get('/', function () {
    return redirect()->route('deals.index');
});


// =====================================================
// DEALS
// =====================================================

// Deals Dashboard
Route::get('/deals', [DealController::class, 'index'])
    ->name('deals.index');

// Quick Purchase for Existing Clients
Route::get('/deals/quick-purchase', [DealController::class, 'quickPurchase'])
    ->name('deals.quick-purchase');

Route::post('/deals/quick-purchase', [DealController::class, 'storeQuickPurchase'])
    ->name('deals.quick-purchase.store');

// Create Deal Page
Route::get('/deals/create', [DealController::class, 'create'])
    ->name('deals.create');

// Quick Store Contact for Deal Form
Route::post('/contacts/quick-store', [DealController::class, 'quickStoreContact'])
    ->name('contacts.quick-store');

// Quick Store Account for Inquiry / Deal Form
Route::post('/accounts/quick-store', [DealController::class, 'quickStoreAccount'])
    ->name('accounts.quick-store');

// Save New Deal
Route::post('/deals', [DealController::class, 'store'])
    ->name('deals.store');

// Search Autocomplete (Typeahead)
Route::get('/deals/autocomplete', [DealController::class, 'autocomplete'])
    ->name('deals.autocomplete');

// View Single Deal
Route::get('/deals/{id}', [DealController::class, 'show'])
    ->name('deals.show');

// Update Deal
Route::put('/deals/{id}', [DealController::class, 'update'])
    ->name('deals.update');

// Delete Deal
Route::delete('/deals/{id}', [DealController::class, 'destroy'])
    ->name('deals.destroy');

// Update Deal Stage
Route::patch('/deals/{id}/stage', [DealController::class, 'updateStage'])
    ->name('deals.stage.update');

// Save Stage Workflow & Sequential Progression
Route::post('/deals/{id}/stage-workflow', [DealController::class, 'saveStageWorkflow'])
    ->name('deals.stage-workflow.save');

// History & Traceability AJAX Endpoint
Route::get('/deals/{id}/histories', [DealController::class, 'getHistories'])
    ->name('deals.histories');

// Add Deal Note
Route::post('/deals/{id}/notes', [DealController::class, 'storeNote'])
    ->name('deals.notes.store');

// Deal Inquiry AJAX Endpoints
Route::post('/deals/{id}/inquiries', [DealController::class, 'storeInquiry'])
    ->name('deals.inquiries.store');
Route::delete('/deals/{id}/inquiries/{inquiryId}', [DealController::class, 'destroyInquiry'])
    ->name('deals.inquiries.destroy');

// Deal Consultation AJAX Endpoints
Route::post('/deals/{id}/consultations', [DealController::class, 'storeConsultation'])
    ->name('deals.consultations.store');
Route::delete('/deals/{id}/consultations/{consultationId}', [DealController::class, 'destroyConsultation'])
    ->name('deals.consultations.destroy');

// Deal Line Items (Services & Pricing) AJAX Endpoints
Route::post('/deals/{id}/line-items', [DealController::class, 'storeLineItem'])
    ->name('deals.line-items.store');
Route::delete('/deals/{id}/line-items/{itemId}', [DealController::class, 'destroyLineItem'])
    ->name('deals.line-items.destroy');


// =====================================================
// PROJECT WORKSPACE
// =====================================================
Route::get('/deals/{id}/regular', [DealController::class, 'regular'])
    ->name('deals.regular');

Route::get('/deals/{id}/project', [DealController::class, 'project'])
    ->name('deals.project');

Route::post('/deals/{id}/project/scope', [DealController::class, 'saveScope'])
    ->name('deals.project.scope.save');
    
Route::get('/projects', [DealController::class, 'projects'])
    ->name('projects.index');

Route::get('/projects/{project}', [DealController::class, 'project'])
    ->name('projects.show');

Route::get('/project', [DealController::class, 'projects'])
    ->name('project.index');

Route::get('/project/{project}', [DealController::class, 'project'])
    ->name('project.show');

// =====================================================
// START WORKSPACE & PROGRESSIVE ACTIVATION BATCHES
// =====================================================

Route::get('/deals/{id}/start', [StartController::class, 'show'])
    ->name('deals.start');
Route::post('/deals/{id}/start/batches', [StartController::class, 'storeBatch'])
    ->name('deals.start.batches.store');
Route::get('/deals/{id}/start/batches/{batchId}', [StartController::class, 'getBatch'])
    ->name('deals.start.batches.show');
Route::put('/deals/{id}/start/batches/{batchId}', [StartController::class, 'updateBatch'])
    ->name('deals.start.batches.update');
Route::delete('/deals/{id}/start/batches/{batchId}', [StartController::class, 'destroyBatch'])
    ->name('deals.start.batches.destroy');
Route::post('/deals/{id}/start/batches/{batchId}/assignments', [StartController::class, 'storeBatchAssignment'])
    ->name('deals.start.batches.assignments.store');
Route::post('/deals/{id}/start/assignments/{assignmentId}/acknowledge', [StartController::class, 'acknowledgeAssignment'])
    ->name('deals.start.assignments.acknowledge');
Route::post('/deals/{id}/start/assignments/{assignmentId}/decline', [StartController::class, 'declineAssignment'])
    ->name('deals.start.assignments.decline');
Route::post('/deals/{id}/start/assignments/{assignmentId}/reassign', [StartController::class, 'reassignAssignment'])
    ->name('deals.start.assignments.reassign');
Route::post('/deals/{id}/start/batches/{batchId}/send-confirmation', [StartController::class, 'sendTeamConfirmation'])
    ->name('deals.start.batches.send-confirmation');
Route::post('/deals/{id}/start/batches/{batchId}/submit-review', [StartController::class, 'submitFinalReview'])
    ->name('deals.start.batches.submit-review');
Route::post('/deals/{id}/start/batches/{batchId}/submit-final-review', [StartController::class, 'submitFinalReview'])
    ->name('deals.start.batches.submit-final-review');
Route::post('/deals/{id}/start/batches/{batchId}/issue-memo', [StartController::class, 'issueServiceMemo'])
    ->name('deals.start.batches.issue-memo');
Route::post('/deals/{id}/start/batches/{batchId}/amend-memo', [StartController::class, 'createMemoRevision'])
    ->name('deals.start.batches.amend-memo');
Route::post('/deals/{id}/start/batches/{batchId}/return', [StartController::class, 'returnBatch'])
    ->name('deals.start.batches.return');
Route::post('/deals/{id}/start/batches/{batchId}/cancel', [StartController::class, 'cancelBatch'])
    ->name('deals.start.batches.cancel');

Route::put(
    '/deals/{id}/start/groups/{groupId}',
    [StartController::class, 'updateGroup']
)->name('deals.start.groups.update');
Route::post('/deals/{id}/start/groups', [StartController::class, 'storeGroup'])
    ->name('deals.start.groups.store');
Route::post(
    '/deals/{id}/start/groups/{groupId}/assignments',
    [StartController::class, 'storeAssignment']
)->name('deals.start.assignments.store');

// =====================================================
// PROPOSAL
// =====================================================

// Save Proposal
Route::post('/deals/{id}/proposal', [DealController::class, 'storeProposal'])
    ->name('deals.proposal.store');

// Send Proposal
Route::post('/deals/{id}/proposal/send', [DealController::class, 'sendProposal'])
    ->name('deals.proposal.send');


// =====================================================
// NOTIFICATIONS
// =====================================================

Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
    ->name('notifications.read');

Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
    ->name('notifications.read-all');


// =====================================================
// UNIVERSAL CLIENT ACTION LAYER
// =====================================================

use App\Http\Controllers\ClientActionController;
use App\Http\Controllers\PublicClientActionController;

// Public Secure No-Login Link Routes (Client facing)
Route::get('/client-action/{token}', [PublicClientActionController::class, 'show'])
    ->name('client-action.public.show');
Route::post('/client-action/{token}/otp', [PublicClientActionController::class, 'requestOtp'])
    ->name('client-action.public.otp');
Route::post('/client-action/{token}/verify-otp', [PublicClientActionController::class, 'verifyOtp'])
    ->name('client-action.public.verify-otp');
Route::post('/client-action/{token}/respond', [PublicClientActionController::class, 'submitResponse'])
    ->name('client-action.public.respond');

// Staff / Internal Management Routes
Route::get('/client-actions', [ClientActionController::class, 'index'])
    ->name('client-actions.index');
Route::post('/client-actions', [ClientActionController::class, 'store'])
    ->name('client-actions.store');
Route::get('/client-actions/portal', [ClientActionController::class, 'portalActions'])
    ->name('client-actions.portal');
Route::get('/client-actions/{id}', [ClientActionController::class, 'show'])
    ->name('client-actions.show');
Route::post('/client-actions/{id}/record-response', [ClientActionController::class, 'recordResponse'])
    ->name('client-actions.record-response');
Route::post('/client-actions/{id}/send', [ClientActionController::class, 'send'])
    ->name('client-actions.send');
Route::post('/client-actions/{id}/verify', [ClientActionController::class, 'verify'])
    ->name('client-actions.verify');
Route::get('/client-actions/{id}/controlled-document', [ClientActionController::class, 'downloadControlledDocument'])
    ->name('client-actions.controlled-document');

// =====================================================
// PROJECTS PAGE
// =====================================================

