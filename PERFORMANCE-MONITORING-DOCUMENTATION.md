# Performance Monitoring Documentation

## Overview

This document outlines the performance monitoring solution implemented for the property management application. The system tracks execution time, database queries, memory usage, and other metrics to help identify performance bottlenecks.

## Implementation Details

The performance monitoring system consists of:

1. **PerformanceMonitoring Middleware**: Measures request execution time, counts queries, and logs performance metrics
2. **Configuration File**: Customizable settings for thresholds, logging, and monitoring behavior
3. **Logging System**: Dedicated log channel for performance metrics

## Setup Instructions

### 1. Register the Log Channel

Add the following to your `config/logging.php` file in the `channels` array:

```php
'performance' => [
    'driver' => 'daily',
    'path' => storage_path('logs/performance.log'),
    'level' => 'info',
    'days' => 14,
],
```

### 2. Register the Middleware

Add the middleware to your `app/Http/Kernel.php` file:

```php
// In the $middleware array for global middleware
protected $middleware = [
    // ... other middleware
    \App\Http\Middleware\PerformanceMonitoring::class,
];

// Or in the $middlewareGroups array for specific groups
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \App\Http\Middleware\PerformanceMonitoring::class,
    ],
];
```

### 3. Publish the Configuration

The configuration file is located at `config/performance-monitoring.php`. You can customize the thresholds, logging behavior, and other settings in this file.

## Usage

### Viewing Performance Metrics

Performance metrics are logged to the `performance` log channel. You can view these logs in the `storage/logs/performance-*.log` files.

Each log entry contains:
- Endpoint (HTTP method and path)
- Execution time (in milliseconds)
- Query count
- Query time (in milliseconds)
- Memory usage (in MB)
- Timestamp
- Slow queries (if any)

### Response Headers

The middleware adds the following headers to HTTP responses:

- `X-Execution-Time`: Total execution time in milliseconds
- `X-Query-Count`: Number of database queries executed
- `X-Query-Time`: Total query execution time in milliseconds
- `X-Memory-Usage`: Peak memory usage in MB

You can use these headers for client-side monitoring or debugging.

### Monitoring Specific Routes

By default, all routes are monitored. You can specify which routes to monitor or exclude in the configuration file:

```php
'monitor_routes' => [
    'api/payments',
    'api/leases',
    'api/tenants',
],

'exclude_routes' => [
    'api/docs',
    'telescope/*',
],
```

## Analyzing Performance Data

### Identifying Slow Endpoints

Endpoints with execution times exceeding the configured threshold (default: 500ms) are considered slow. Review the performance logs to identify these endpoints.

### Optimizing Database Queries

The monitoring system logs slow queries (default threshold: 100ms). Review these queries and consider:

1. Adding appropriate indexes (see the `2023_06_01_000001_add_performance_indexes.php` migration)
2. Optimizing query structure
3. Implementing caching for frequently accessed data

### Memory Usage Optimization

High memory usage can indicate inefficient code or memory leaks. If memory usage exceeds the configured threshold (default: 128MB), consider:

1. Optimizing data structures
2. Using pagination for large datasets
3. Implementing lazy loading for relationships

## Integration with Other Tools

### Laravel Telescope

For more detailed monitoring, consider installing Laravel Telescope:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Telescope provides a comprehensive dashboard for monitoring requests, queries, exceptions, and more.

### Database Query Analyzer

For in-depth analysis of database performance, consider using tools like:

- MySQL Slow Query Log
- PostgreSQL pg_stat_statements
- Database profiling tools

## Best Practices

1. **Set Appropriate Thresholds**: Adjust the thresholds in the configuration file based on your application's requirements
2. **Regular Monitoring**: Review performance logs regularly to identify trends and potential issues
3. **Performance Testing**: Conduct load testing to identify performance bottlenecks before they affect users
4. **Continuous Improvement**: Use the monitoring data to guide optimization efforts

## Troubleshooting

### High Query Counts

If you notice a high number of queries for a specific endpoint, check for:

1. N+1 query problems (use eager loading with `with()` to resolve)
2. Redundant queries (implement caching)
3. Inefficient query patterns

### Slow Execution Times

If an endpoint has slow execution times, consider:

1. Profiling the code to identify bottlenecks
2. Implementing caching for expensive operations
3. Optimizing database queries
4. Using queue jobs for time-consuming tasks

## Conclusion

The performance monitoring system provides valuable insights into your application's performance. Use this data to identify and resolve bottlenecks, ensuring a fast and responsive user experience.