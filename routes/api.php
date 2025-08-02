<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Admin Panel API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the admin panel. These routes handle AJAX
| requests for search, field suggestions, actions, and other dynamic
| functionality within the admin panel.
|
*/

// Global search
Route::get('/search', [ApiController::class, 'search'])->name('search');

// Field suggestions
Route::get('/resources/{resource}/fields/{field}/suggestions', [ApiController::class, 'fieldSuggestions'])
    ->name('field-suggestions');

// Resource data for relationships
Route::get('/resources/{resource}/data', [ApiController::class, 'resourceData'])
    ->name('resource-data');

// Execute resource actions
Route::post('/resources/{resource}/actions/{action}', [ApiController::class, 'executeAction'])
    ->name('execute-action');

// Dashboard metrics
Route::get('/metrics', [ApiController::class, 'metrics'])->name('metrics');

// System actions
Route::post('/system/clear-cache', [ApiController::class, 'clearCache'])->name('clear-cache');
