<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KhataController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ApexPOS API Routes
|--------------------------------------------------------------------------
*/

// ── Health Check ─────────────────────────────────────────────────────
Route::get('/', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'ApexPOS Cloud API Server is online.',
        'version'   => '1.0.0',
        'timestamp' => now()->toISOString(),
        'endpoints' => [
            'ping'     => '/api/ping',
            'admin'    => '/admin',
            'activate' => '/api/license/activate',
            'login'    => '/api/auth/login',
        ],
    ]);
});

Route::get('/ping', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'ApexPOS API is online.',
        'version'   => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// ── Public License Key Routes ────────────────────────────────────────
Route::post('/license/activate', [LicenseController::class, 'activate']);
Route::post('/license/validate', [LicenseController::class, 'validateKey']);

// ── Authentication Routes ───────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ── Protected Authentication & Profile ──────────────────────────────
Route::middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// ── Protected Tenant-Scoped CRUD & Sync Routes ──────────────────────
Route::middleware(['auth:sanctum', 'tenant.scope', 'license.check'])->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/products/delete-multiple', [ProductController::class, 'deleteMultiple']);

    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    Route::post('/customers/delete-multiple', [CustomerController::class, 'deleteMultiple']);

    // Orders / Receipts
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
    Route::post('/suppliers/delete-multiple', [SupplierController::class, 'deleteMultiple']);

    // Khata / Store Credit
    Route::get('/khata/ledger', [KhataController::class, 'getLedger']);
    Route::post('/khata/collect-repayment', [KhataController::class, 'collectRepayment']);
    Route::post('/khata/record-debt-add', [KhataController::class, 'recordDebtAdd']);
    Route::get('/khata/statement/{customerId}', [KhataController::class, 'getStatement']);

    // Register Shifts & Cash Movements
    Route::get('/shift/active', [ShiftController::class, 'getActive']);
    Route::post('/shift/start', [ShiftController::class, 'startShift']);
    Route::post('/shift/record-movement', [ShiftController::class, 'recordCashMovement']);
    Route::post('/shift/close', [ShiftController::class, 'closeShift']);
    Route::get('/shift/history', [ShiftController::class, 'getHistory']);

    // Settings
    Route::get('/settings', [SettingsController::class, 'getSettings']);
    Route::post('/settings', [SettingsController::class, 'saveSettings']);

    // Users / Staff
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Push/Pull Sync Engine Routes
    Route::post('/sync/push', [SyncController::class, 'push']);
    Route::get('/sync/pull', [SyncController::class, 'pull']);
    Route::get('/sync/logs', [SyncController::class, 'logs']);
});
