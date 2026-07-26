<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — ApexPOS Web Admin Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// ── SuperAdmin Guest Authentication Routes ──────────────────────────
Route::get('/admin/login', [DashboardController::class, 'loginView'])->name('admin.login');
Route::post('/admin/login', [DashboardController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [DashboardController::class, 'logout'])->name('admin.logout');

// ── Protected SuperAdmin Dashboard Routes ───────────────────────────
Route::middleware(['superadmin'])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Tenant Management
    Route::get('/tenants', [DashboardController::class, 'tenants'])->name('admin.tenants');
    Route::post('/tenants/{id}/toggle-status', [DashboardController::class, 'toggleTenantStatus'])->name('admin.tenants.toggle-status');

    // License Management
    Route::get('/licenses', [DashboardController::class, 'licenses'])->name('admin.licenses');
    Route::post('/licenses/create', [DashboardController::class, 'createLicense'])->name('admin.licenses.create');
    Route::post('/licenses/{id}/extend', [DashboardController::class, 'extendLicense'])->name('admin.licenses.extend');
    Route::post('/licenses/{id}/revoke', [DashboardController::class, 'revokeLicense'])->name('admin.licenses.revoke');

    // Sync Audit Logs
    Route::get('/sync-logs', [DashboardController::class, 'syncLogs'])->name('admin.sync-logs');
});
