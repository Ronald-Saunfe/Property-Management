<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaseController;

/*
|--------------------------------------------------------------------------
| Lease API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for lease management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Routes accessible by admins and agents only
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/leases/statistics', [LeaseController::class, 'statistics']);
    
    // Agents can create and update leases
    Route::post('/leases', [LeaseController::class, 'store']);
    Route::put('/leases/{id}', [LeaseController::class, 'update']);
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can delete leases
    Route::delete('/leases/{id}', [LeaseController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view leases
    // Note: Controller should filter results based on user role
    Route::get('/leases', [LeaseController::class, 'index']);
    Route::get('/leases/{id}', [LeaseController::class, 'show']);
});

// Note: The LeaseController should implement authorization logic to ensure
// landlords can only view leases related to their properties