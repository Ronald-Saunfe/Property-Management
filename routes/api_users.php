<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for user management. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    
    // CRUD routes for users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Note: Update api.php to include this routes file with:
// require __DIR__.'/api_users.php';

// Note: The UserController should be updated to use UserRequest for validation
// Replace Request with UserRequest in the store and update methods
// and use $request->validated() instead of $request->validate([...])