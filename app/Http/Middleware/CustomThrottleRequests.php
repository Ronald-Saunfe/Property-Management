<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Cache\RateLimiter;
use Closure;
use Illuminate\Http\Request;

class CustomThrottleRequests extends ThrottleRequests
{
    /**
     * Create a new rate limiter middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        parent::__construct($limiter);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        // Determine which rate limit to apply based on the route
        $path = $request->path();
        
        // Apply stricter rate limits for authentication endpoints
        if (str_contains($path, 'login') || str_contains($path, 'register') || str_contains($path, 'password/email')) {
            // 10 requests per minute for auth endpoints
            return parent::handle($request, $next, 10, 1, 'auth');
        }
        
        // Apply moderate rate limits for property management endpoints
        if (str_contains($path, 'properties') || str_contains($path, 'units') || 
            str_contains($path, 'tenants') || str_contains($path, 'leases')) {
            // 60 requests per minute for property management endpoints
            return parent::handle($request, $next, 60, 1, 'property');
        }
        
        // Default rate limit for all other API endpoints
        // 120 requests per minute
        return parent::handle($request, $next, 120, 1, 'api');
    }
}