<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaseTenantController;

/*
|--------------------------------------------------------------------------
| Lease Tenant API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for lease tenant relationship management.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Routes accessible by admins and agents only
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/lease-tenants/statistics', [LeaseTenantController::class, 'statistics']);
    
    // Agents can create and update lease tenant relationships
    Route::post('/lease-tenants', [LeaseTenantController::class, 'store']);
    Route::put('/lease-tenants/{id}', [LeaseTenantController::class, 'update']);
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can delete lease tenant relationships
    Route::delete('/lease-tenants/{id}', [LeaseTenantController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view lease tenant relationships
    // Note: Controller should filter results based on user role
    Route::get('/lease-tenants', [LeaseTenantController::class, 'index']);
    Route::get('/lease-tenants/{id}', [LeaseTenantController::class, 'show']);
});

// Note: The LeaseTenantController should implement authorization logic to ensure
// landlords can only view lease tenant relationships related to their properties