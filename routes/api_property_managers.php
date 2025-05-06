<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyManagerController;

/*
|--------------------------------------------------------------------------
| Property Manager API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for property manager management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/property-managers/statistics', [PropertyManagerController::class, 'statistics']);
    
    // Admin can perform all operations on property managers
    Route::post('/property-managers', [PropertyManagerController::class, 'store']);
    Route::put('/property-managers/{id}', [PropertyManagerController::class, 'update']);
    Route::delete('/property-managers/{id}', [PropertyManagerController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view property managers
    // Note: Controller should filter results based on user role
    Route::get('/property-managers', [PropertyManagerController::class, 'index']);
    Route::get('/property-managers/{id}', [PropertyManagerController::class, 'show']);
});

// Note: The PropertyManagerController should implement authorization logic to ensure
// landlords can only view property managers related to their properties
// and agents can only view property managers for properties they manage