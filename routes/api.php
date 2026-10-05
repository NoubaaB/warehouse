<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ConsumableStockController;
use App\Http\Controllers\Api\ConsumableTypeController;
use App\Http\Controllers\Api\ContainerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FishStockController;
use App\Http\Controllers\Api\FishWarehouseController;
use App\Http\Controllers\Api\FreezingFishController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\VoucherTypeController;
use App\Http\Controllers\Api\WorkforceAssignmentController;
use App\Http\Controllers\Api\WorkforceController;
use Illuminate\Support\Facades\Route;

// Public Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Vouchers & Operations
    Route::apiResource('vouchers', VoucherController::class);

    // Fish Stock & Archive
    Route::get('/fish-stock', [FishStockController::class, 'index']);
    Route::get('/fish-stock/archive', [FishStockController::class, 'archive']);

    // Consumable Stock
    Route::get('/consumable-stock', [ConsumableStockController::class, 'index']);

    // Workforce Assignments & Monthly Summary
    Route::get('/workforce-assignments', [WorkforceAssignmentController::class, 'index']);
    Route::post('/workforce-assignments', [WorkforceAssignmentController::class, 'store']);
    Route::delete('/workforce-assignments/{workforceAssignment}', [WorkforceAssignmentController::class, 'destroy']);
    Route::get('/workforce-assignments/monthly-summary', [WorkforceAssignmentController::class, 'monthlySummary']);

    // Settings API Resources
    Route::apiResource('providers', ProviderController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('freezing-fish', FreezingFishController::class);
    Route::apiResource('consumable-types', ConsumableTypeController::class);
    Route::apiResource('fish-warehouses', FishWarehouseController::class);
    Route::apiResource('containers', ContainerController::class);
    Route::apiResource('voucher-types', VoucherTypeController::class);
    Route::apiResource('workforces', WorkforceController::class);
});
