<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaseController;

// Lease routes with API resource
Route::middleware('auth:sanctum')->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/leases/statistics', [LeaseController::class, 'statistics']);
    
    // CRUD operations
    Route::get('/leases', [LeaseController::class, 'index']);
    Route::post('/leases', [LeaseController::class, 'store']);
    Route::get('/leases/{id}', [LeaseController::class, 'show']);
    Route::put('/leases/{id}', [LeaseController::class, 'update']);
    Route::delete('/leases/{id}', [LeaseController::class, 'destroy']);
});