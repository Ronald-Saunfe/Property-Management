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

// Routes accessible by admins and agents only
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/units/statistics', [UnitController::class, 'statistics']);
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can perform all operations on units
    Route::post('/units', [UnitController::class, 'store']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view units
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
});

// Routes accessible by admins and agents
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Admins and agents can update units
    Route::put('/units/{id}', [UnitController::class, 'update']);
});


// Note: The UnitController needs to be updated to use UnitRequest for validation
// Replace the Request type hints with UnitRequest in the store and update methods
// and use $request->validated() instead of $request->validate([...])