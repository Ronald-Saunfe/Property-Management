<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaseTenantController;

// Lease Tenant routes with API resource
Route::middleware('auth:sanctum')->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/lease-tenants/statistics', [LeaseTenantController::class, 'statistics']);
    
    // CRUD operations
    Route::get('/lease-tenants', [LeaseTenantController::class, 'index']);
    Route::post('/lease-tenants', [LeaseTenantController::class, 'store']);
    Route::get('/lease-tenants/{id}', [LeaseTenantController::class, 'show']);
    Route::put('/lease-tenants/{id}', [LeaseTenantController::class, 'update']);
    Route::delete('/lease-tenants/{id}', [LeaseTenantController::class, 'destroy']);
});