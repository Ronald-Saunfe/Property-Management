<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PerformanceMonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/performance-monitoring.php', 'performance-monitoring'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Only enable if performance monitoring is enabled in config
        if (!config('performance-monitoring.enabled')) {
            return;
        }

        // Create performance log channel if it doesn't exist
        if (!array_key_exists('performance', config('logging.channels'))) {
            Config::set('logging.channels.performance', [
                'driver' => 'daily',
                'path' => storage_path('logs/performance.log'),
                'level' => 'info',
                'days' => config('performance-monitoring.retention_days', 7),
            ]);
        }

        // Enable query logging if we're not in production or if explicitly enabled
        if (app()->environment('local', 'development', 'testing') || 
            config('performance-monitoring.log_query_bindings')) {
            DB::enableQueryLog();
        }

        // Register dashboard routes if enabled
        if (config('performance-monitoring.dashboard.enabled')) {
            $this->registerDashboardRoutes();
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Register any console commands here
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__.'/../../config/performance-monitoring.php' => config_path('performance-monitoring.php'),
            ], 'performance-monitoring-config');
        }
    }

    /**
     * Register dashboard routes.
     *
     * @return void
     */
    protected function registerDashboardRoutes()
    {
        $route = config('performance-monitoring.dashboard.route');
        $middleware = config('performance-monitoring.dashboard.middleware');

        // This would typically point to a controller that renders the dashboard
        // For now, we'll just create a simple route that shows the latest logs
        $this->app['router']->group(['middleware' => $middleware], function ($router) use ($route) {
            $router->get($route, function () {
                // Simple dashboard that displays the latest performance logs
                $logPath = storage_path('logs/performance.log');
                $logs = [];
                
                if (file_exists($logPath)) {
                    $logs = array_slice(file($logPath), -100);
                    $logs = array_reverse($logs);
                }
                
                return view('performance.dashboard', ['logs' => $logs]);
            })->name('performance.dashboard');
        });
    }
}