<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// Payment routes with API resource
Route::middleware('auth:sanctum')->group(function () {
    // Statistics route (must be before the show route to avoid conflict)
    Route::get('/payments/statistics', [PaymentController::class, 'statistics']);
    
    // CRUD operations
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
});