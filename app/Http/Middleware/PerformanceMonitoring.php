<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Start measuring execution time
        $startTime = microtime(true);
        
        // Count queries before request processing
        $queryCountBefore = count(DB::getQueryLog());
        
        // Enable query logging for this request
        DB::enableQueryLog();
        
        // Process the request
        $response = $next($request);
        
        // Calculate execution time
        $executionTime = microtime(true) - $startTime;
        
        // Get queries executed during this request
        $queries = DB::getQueryLog();
        $queryCount = count($queries) - $queryCountBefore;
        
        // Calculate total query time
        $queryTime = 0;
        foreach ($queries as $query) {
            $queryTime += $query['time'] / 1000; // Convert to seconds
        }
        
        // Get memory usage
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // Convert to MB
        
        // Log performance metrics
        $endpoint = $request->method() . ' ' . $request->path();
        $metrics = [
            'endpoint' => $endpoint,
            'execution_time' => round($executionTime * 1000, 2) . 'ms', // Convert to milliseconds
            'query_count' => $queryCount,
            'query_time' => round($queryTime * 1000, 2) . 'ms', // Convert to milliseconds
            'memory_usage' => round($memoryUsage, 2) . 'MB',
            'timestamp' => now()->toDateTimeString(),
        ];
        
        // Add performance headers to the response
        if ($response instanceof Response) {
            $response->headers->set('X-Execution-Time', $metrics['execution_time']);
            $response->headers->set('X-Query-Count', $metrics['query_count']);
            $response->headers->set('X-Query-Time', $metrics['query_time']);
            $response->headers->set('X-Memory-Usage', $metrics['memory_usage']);
        }
        
        // Log slow queries (over 100ms)
        $slowQueries = [];
        foreach ($queries as $query) {
            if ($query['time'] > 100) { // 100ms threshold
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'] . 'ms',
                ];
            }
        }
        
        if (!empty($slowQueries)) {
            $metrics['slow_queries'] = $slowQueries;
        }
        
        // Log performance metrics
        Log::channel('performance')->info('Performance metrics', $metrics);
        
        return $response;
    }
}