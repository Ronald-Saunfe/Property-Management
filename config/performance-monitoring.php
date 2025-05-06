<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the performance monitoring system.
    | You can customize the thresholds, logging channels, and other settings here.
    |
    */

    // Enable or disable performance monitoring
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
    
    // Log channel to use for performance metrics
    'log_channel' => env('PERFORMANCE_MONITORING_CHANNEL', 'performance'),
    
    // Thresholds for identifying slow operations
    'thresholds' => [
        // Request execution time threshold in milliseconds
        'request_time' => env('PERFORMANCE_THRESHOLD_REQUEST', 500),
        
        // Query execution time threshold in milliseconds
        'query_time' => env('PERFORMANCE_THRESHOLD_QUERY', 100),
        
        // Maximum acceptable number of queries per request
        'query_count' => env('PERFORMANCE_THRESHOLD_QUERY_COUNT', 50),
        
        // Memory usage threshold in MB
        'memory_usage' => env('PERFORMANCE_THRESHOLD_MEMORY', 128),
    ],
    
    // Routes to monitor (empty array means all routes)
    'monitor_routes' => [
        // Examples:
        // 'api/payments',
        // 'api/leases',
        // 'api/tenants',
    ],
    
    // Routes to exclude from monitoring
    'exclude_routes' => [
        'api/docs',
        'telescope/*',
        'horizon/*',
        'debugbar/*',
    ],
    
    // Whether to include query bindings in logs (may contain sensitive data)
    'log_query_bindings' => env('PERFORMANCE_LOG_QUERY_BINDINGS', false),
    
    // Whether to add performance headers to HTTP responses
    'add_headers' => env('PERFORMANCE_ADD_HEADERS', true),
    
    // Dashboard access configuration
    'dashboard' => [
        'enabled' => env('PERFORMANCE_DASHBOARD_ENABLED', true),
        'route' => env('PERFORMANCE_DASHBOARD_ROUTE', 'performance-dashboard'),
        'middleware' => ['web', 'auth', 'role:admin'],
    ],
    
    // Database for storing performance metrics
    // Options: 'database', 'file', 'null'
    'storage' => env('PERFORMANCE_STORAGE', 'file'),
    
    // Number of days to keep performance logs
    'retention_days' => env('PERFORMANCE_RETENTION_DAYS', 7),
];