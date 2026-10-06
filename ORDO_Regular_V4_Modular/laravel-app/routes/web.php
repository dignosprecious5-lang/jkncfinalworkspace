<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'create'])->name('projects.create');
Route::get('/reports/export', [ProjectController::class, 'export'])->name('projects.export');
Route::post('/preferences', [ProjectController::class, 'settings'])->name('preferences.save');
Route::get('/portfolio/{module}', [ProjectController::class, 'index'])->name('portfolio.module');
Route::get('/projects/{project}', [ProjectController::class, 'workspace'])->whereNumber('project')->name('projects.workspace');
Route::get('/projects/{project}/{section}', [ProjectController::class, 'workspace'])->whereNumber('project')->name('projects.section');
Route::post('/projects/{project}/actions/{action}', [ProjectController::class, 'action'])->whereNumber('project')->name('projects.action');
Route::get('/projects/{project}/files/{file}', [ProjectController::class, 'download'])->whereNumber('project')->name('projects.file');
