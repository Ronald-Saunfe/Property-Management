<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for tenant management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Routes accessible by admins and agents only
Route::middleware(['auth:sanctum', 'role:admin,agent'])->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/tenants/statistics', [TenantController::class, 'statistics']);
    
    // Agents can create and update tenants
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::put('/tenants/{id}', [TenantController::class, 'update']);
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can delete tenants
    Route::delete('/tenants/{id}', [TenantController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view tenants
    // Note: Controller should filter results based on user role
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::get('/tenants/{id}', [TenantController::class, 'show']);
});