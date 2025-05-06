<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\AuthController;


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

// Register authentication routes (login, register, logout)
AuthController::routes();

// Register password reset routes
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

