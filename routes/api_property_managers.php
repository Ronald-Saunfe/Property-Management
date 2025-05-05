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

Route::middleware('auth:sanctum')->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/property-managers/statistics', [PropertyManagerController::class, 'statistics']);
    
    // CRUD routes for property managers
    Route::get('/property-managers', [PropertyManagerController::class, 'index']);
    Route::post('/property-managers', [PropertyManagerController::class, 'store']);
    Route::get('/property-managers/{id}', [PropertyManagerController::class, 'show']);
    Route::put('/property-managers/{id}', [PropertyManagerController::class, 'update']);
    Route::delete('/property-managers/{id}', [PropertyManagerController::class, 'destroy']);
});