<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Payment API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for payment management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Routes accessible by admins and agents only
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/payments/statistics', [PaymentController::class, 'statistics']);
    
    // Agents can create and update payments
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can delete payments
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view payments
    // Note: Controller should filter results based on user role
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
});

// Note: The PaymentController should implement authorization logic to ensure
// landlords can only view payments related to their properties