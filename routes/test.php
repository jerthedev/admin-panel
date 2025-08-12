<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Http\Controllers\TestDataController;

/*
|--------------------------------------------------------------------------
| Test Data API Routes
|--------------------------------------------------------------------------
|
| These routes are only loaded in testing environments and provide
| automated test data creation and cleanup for Playwright and other
| automated testing scenarios.
|
| SECURITY: These routes are restricted to testing environment only.
|
*/

// Test data setup and cleanup endpoints
Route::prefix('test')->name('test.')->middleware(['test-only'])->group(function () {
    // Setup admin demo data using existing factories
    Route::post('/setup-admin-demo', [TestDataController::class, 'setupAdminDemo'])
        ->name('setup-admin-demo');

    // Cleanup all test data
    Route::post('/cleanup', [TestDataController::class, 'cleanup'])
        ->name('cleanup');

    // Seed comprehensive field examples for all 30+ field types
    Route::post('/seed-field-examples', [TestDataController::class, 'seedFieldExamples'])
        ->name('seed-field-examples');

    // Get current test data status
    Route::get('/status', [TestDataController::class, 'status'])
        ->name('status');

    // Create a single test user for E2E testing
    Route::post('/create-user', [TestDataController::class, 'createUser'])
        ->name('create-user');

    // Create specific test scenarios
    Route::post('/scenarios/{scenario}', [TestDataController::class, 'createScenario'])
        ->name('create-scenario');
});
