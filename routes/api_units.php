<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;

/*
|--------------------------------------------------------------------------
| Unit API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for unit management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/units/statistics', [UnitController::class, 'statistics']);
    
    // CRUD routes for units
    Route::get('/units', [UnitController::class, 'index']);
    Route::post('/units', [UnitController::class, 'store']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
    Route::put('/units/{id}', [UnitController::class, 'update']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);
});

// Note: The UnitController needs to be updated to use UnitRequest for validation
// Replace the Request type hints with UnitRequest in the store and update methods
// and use $request->validated() instead of $request->validate([...])