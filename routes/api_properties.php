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

// Routes accessible by all authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Statistics route - accessible by admins and agents only
    Route::get('/properties/statistics', [PropertyController::class, 'statistics'])
        ->middleware('role:admin,agent');
});

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin can perform all operations on properties
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
});

// Routes accessible by admins, agents, and landlords
Route::middleware(['auth:sanctum', 'role:admin,agent,landlord'])->group(function () {
    // All authenticated users can view properties
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
});

// Routes accessible by admins and landlords (property owners)
Route::middleware(['auth:sanctum', 'role:admin,landlord'])->group(function () {
    // Landlords can update their own properties
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
});