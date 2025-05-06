# Laravel Debugbar Usage Guide

## Introduction

Laravel Debugbar is a powerful package that provides a debugging and profiling toolbar for your Laravel application. It helps you identify performance bottlenecks, track database queries, monitor memory usage, and analyze request execution time.

## Installation

1. Install Laravel Debugbar via Composer:

```bash
composer require barryvdh/laravel-debugbar --dev
```

2. Laravel will automatically discover the service provider. For Laravel versions before 5.5, add the service provider to your `config/app.php`:

```php
Barryvdh\Debugbar\ServiceProvider::class,
```

3. Optionally, publish the configuration file:

```bash
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Basic Usage

Once installed, Debugbar will automatically appear at the bottom of your browser window when you're viewing your Laravel application in a browser. It's only enabled in development environments by default.

### Enabling/Disabling Debugbar

You can enable or disable Debugbar in your `.env` file:

```
DEBUGBAR_ENABLED=true
```

Or programmatically:

```php
// Enable
\Debugbar::enable();

// Disable
\Debugbar::disable();
```

## Key Features

### 1. Database Query Monitoring

Debugbar tracks all database queries executed during a request, showing:

- SQL query with bindings
- Execution time
- Connection used
- Stack trace (where the query was called from)

This helps identify:
- N+1 query problems
- Slow queries
- Duplicate queries

### 2. Request and Response Information

Debugbar shows detailed information about:

- Request headers and parameters
- Response status and headers
- Session data
- Route information

### 3. Performance Metrics

- Total execution time
- Memory usage
- Timeline of events during request processing

### 4. Exception Tracking

Debugbar captures and displays exceptions that occur during request processing.

## Advanced Usage

### Adding Custom Messages

You can add custom messages to Debugbar for tracking specific parts of your code:

```php
// Simple message
\Debugbar::info('Info message');

// Warning
\Debugbar::warning('Warning message');

// Error
\Debugbar::error('Error message');

// Add to a specific collector
\Debugbar::addMessage('Message', 'custom_label');
```

### Measuring Execution Time

You can measure the execution time of specific code blocks:

```php
\Debugbar::startMeasure('render', 'Time for rendering');

// Your code here

\Debugbar::stopMeasure('render');
```

Or use the convenient timing helper:

```php
\Debugbar::measure('My long operation', function() {
    // Your code here
});
```

### Tracking Database Queries in Specific Code Blocks

To track queries in a specific part of your code:

```php
\Debugbar::startMeasure('get_payments', 'Retrieving payments');

$payments = Payment::with('lease')->get();

\Debugbar::stopMeasure('get_payments');
```

## Using Debugbar for API Requests

For API requests, you can collect data without rendering the toolbar:

1. Set `'capture_ajax' => true` in your debugbar config
2. Use the `debugbar.capture` middleware on your API routes

```php
Route::middleware(['debugbar.capture'])->group(function () {
    Route::get('/api/payments', 'PaymentController@index');
});
```

3. View the collected data at `/_debugbar`

## Profiling Specific Controllers

To profile the controllers mentioned in the performance report:

### Payment Controller

```php
public function index(Request $request)
{
    \Debugbar::startMeasure('payment_index', 'Payment Index Method');
    
    // Track relationship loading
    \Debugbar::startMeasure('payment_relationships', 'Loading Payment Relationships');
    $query = Payment::query();
    
    if ($request->has('include_lease')) {
        $query->with('lease');
    }
    
    if ($request->has('include_tenant')) {
        $query->with('lease.tenant');
    }
    
    \Debugbar::stopMeasure('payment_relationships');
    
    // Track search execution
    \Debugbar::startMeasure('payment_search', 'Payment Search');
    if ($request->has('search')) {
        // Search implementation
    }
    \Debugbar::stopMeasure('payment_search');
    
    // Track pagination
    \Debugbar::startMeasure('payment_pagination', 'Payment Pagination');
    $payments = $query->paginate($request->per_page ?? 15);
    \Debugbar::stopMeasure('payment_pagination');
    
    \Debugbar::stopMeasure('payment_index');
    
    return response()->json($payments);
}
```

## Analyzing Debugbar Data

When analyzing Debugbar data, look for:

1. **Database tab**:
   - Queries taking more than 10ms
   - Repeated similar queries (potential N+1 issues)
   - High query counts (more than 10-15 for a simple page)

2. **Timeline tab**:
   - Operations taking excessive time
   - Bottlenecks in request processing

3. **Memory usage**:
   - Spikes in memory consumption
   - Gradual memory increases (potential memory leaks)

## Best Practices

1. **Always disable Debugbar in production**:
   ```php
   'enabled' => env('APP_DEBUG', false) && env('APP_ENV') !== 'production',
   ```

2. **Use selective data collection** when profiling heavy pages:
   ```php
   // In config/debugbar.php
   'collectors' => [
       'phpinfo' => true,  // PHP info
       'messages' => true,  // Messages
       'time' => true,  // Time Datalogger
       'memory' => true,  // Memory usage
       'exceptions' => true,  // Exception displayer
       'log' => true,  // Logs from Monolog
       'db' => true,  // Show database (PDO) queries and bindings
       'views' => false,  // Disable for heavy pages
       'route' => true,  // Current route information
       'auth' => false,  // Disable if not needed
       'gate' => false,  // Disable if not needed
       'session' => true,  // Display session data
   ],
   ```

3. **Use startMeasure/stopMeasure** to track specific operations

4. **Combine with Laravel Telescope** for more comprehensive monitoring

## Troubleshooting

### Debugbar Not Showing

1. Check that `APP_DEBUG=true` in your `.env` file
2. Verify that you're not in production mode
3. Check that JavaScript is enabled in your browser
4. Look for JavaScript errors in your browser console

### High Memory Usage

If Debugbar is causing high memory usage:

1. Disable heavy collectors in `config/debugbar.php`
2. Increase PHP memory limit in `php.ini`
3. Use `\Debugbar::disable()` for memory-intensive operations

## Conclusion

Laravel Debugbar is an essential tool for identifying and resolving performance issues in your Laravel application. By using it to profile your controllers, you can pinpoint bottlenecks, optimize database queries, and improve overall application performance.

For the specific performance issues identified in the profiling report, Debugbar can help verify that the implemented optimizations (database indexing, query optimization, caching) are effectively resolving the issues.