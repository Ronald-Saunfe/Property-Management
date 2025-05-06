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

// Routes accessible by admins only
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Statistics route - must be placed before resource routes to avoid conflicts
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    
    // Admin can perform all operations on users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Routes accessible by all authenticated users
// Note: Controller should implement logic to ensure users can only view/update their own profiles
// unless they are admins
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
});

// Note: Update api.php to include this routes file with:
// require __DIR__.'/api_users.php';

// Note: The UserController should be updated to use UserRequest for validation
// Replace Request with UserRequest in the store and update methods
// and use $request->validated() instead of $request->validate([...])
// 
// Important: The UserController should implement authorization logic in the show and update methods
// to ensure users can only access their own profiles unless they are admins