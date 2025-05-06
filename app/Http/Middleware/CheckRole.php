<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Get authenticated user
        $user = Auth::user();
        
        // Check if user has one of the required roles
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have the required role to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}