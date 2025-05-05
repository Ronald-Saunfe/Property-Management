<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Add login route
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Add before Auth::attempt
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 401);
    }

    if (Auth::attempt($credentials)) {
        // Get a fresh instance of the User model to ensure all traits are properly loaded
        $user = User::find(Auth::id());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'token_type' => 'Bearer'
        ]);
    }

    return response()->json([
        'message' => 'Invalid credentials'
    ], 401);
});

// Add registration route
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'role' => 'required|string',
        'phone' => 'nullable|string'
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => bcrypt($validated['password']),
        'role' => $validated['role'],
        'phone' => $validated['phone'] ?? null
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'token_type' => 'Bearer'
    ], 201);
});

// Add logout route
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    
    return response()->json([
        'message' => 'Successfully logged out'
    ]);
});

// Register password reset routes
use App\Http\Controllers\Auth\ResetPasswordController;

ResetPasswordController::routes();

// Include lease routes
require __DIR__.'/api_leases.php';

// Include lease tenant routes
require __DIR__.'/api_lease_tenants.php';

// Include payment routes
require __DIR__.'/api_payments.php';

// Include property routes
require __DIR__.'/api_properties.php';

// Include property manager routes
require __DIR__.'/api_property_managers.php';

// Include tenant routes
require __DIR__.'/api_tenants.php';

// Include unit routes
require __DIR__.'/api_units.php';

// Include user routes
require __DIR__.'/api_users.php';
