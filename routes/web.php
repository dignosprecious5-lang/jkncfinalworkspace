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

// Create Deal Page
Route::get('/deals/create', [DealController::class, 'create'])
    ->name('deals.create');

// Save New Deal
Route::post('/deals', [DealController::class, 'store'])
    ->name('deals.store');

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
// START WORKSPACE
// =====================================================

Route::get('/deals/{id}/start', [StartController::class, 'show'])
    ->name('deals.start');
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

// View Proposal
Route::get('/deals/{id}/proposal', [DealController::class, 'proposal'])
    ->name('deals.proposal');

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
// PROJECTS PAGE
// =====================================================
