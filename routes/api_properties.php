<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyController;

/*
|--------------------------------------------------------------------------
| Property API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for property management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/properties/statistics', [PropertyController::class, 'statistics']);
    
    // CRUD routes for properties
    Route::apiResource('properties', PropertyController::class);
});