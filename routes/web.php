<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use JTD\AdminPanel\Http\Controllers\AuthController;
use JTD\AdminPanel\Http\Controllers\DashboardController;
use JTD\AdminPanel\Http\Controllers\ResourceController;
use JTD\AdminPanel\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the admin panel. These routes are loaded by the
| AdminPanelServiceProvider and are assigned the "admin-panel" route name
| prefix and the configured middleware group.
|
*/

// Test route (no middleware) - Simple HTML test for asset pipeline
Route::get('/test', function () {
    return view('admin-panel::test-assets');
})->name('test');

// Test route for self-contained Inertia
Route::get('/inertia-test', function () {
    return Inertia::render('Auth/Login', [
        'canResetPassword' => true,
        'status' => null,
        'testMessage' => 'Self-contained Inertia working!',
    ]);
})->name('inertia-test');

// Authentication routes (no auth middleware, but need Inertia)
Route::middleware(['admin.inertia'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware(['admin.inertia', 'admin.auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    // User management
    Route::resource('users', UserController::class)->names([
        'index' => 'users.index',
        'create' => 'users.create',
        'store' => 'users.store',
        'show' => 'users.show',
        'edit' => 'users.edit',
        'update' => 'users.update',
        'destroy' => 'users.destroy',
    ]);

    // Resource routes
    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/{resource}', [ResourceController::class, 'index'])->name('index');
        Route::get('/{resource}/create', [ResourceController::class, 'create'])->name('create');
        Route::post('/{resource}', [ResourceController::class, 'store'])->name('store');
        Route::get('/{resource}/{id}', [ResourceController::class, 'show'])->name('show');
        Route::get('/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('edit');
        Route::put('/{resource}/{id}', [ResourceController::class, 'update'])->name('update');
        Route::delete('/{resource}/{id}', [ResourceController::class, 'destroy'])->name('destroy');
    });
});
