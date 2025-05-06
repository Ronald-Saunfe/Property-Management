# Horizontal Scaling Strategies for Property Management Application

## Introduction

As our property management application grows in terms of users, properties, and data volume, we need to implement horizontal scaling strategies to ensure the application remains performant, reliable, and available. This document outlines a comprehensive approach to scaling the application horizontally across multiple dimensions.

## Current Architecture Limitations

Our current architecture has several potential bottlenecks:

1. **Single Database Instance**: All read and write operations go to a single database, creating a potential bottleneck.
2. **Synchronous Processing**: Long-running tasks are processed synchronously, blocking the request-response cycle.
3. **Local File Storage**: Files are stored on the local filesystem, limiting scalability.
4. **In-Memory Session Storage**: Sessions are stored in memory, making horizontal scaling challenging.
5. **Limited Caching Strategy**: Our current caching implementation is basic and not distributed.

## Horizontal Scaling Strategies

### 1. Database Scaling

#### Primary-Replica Replication

Implement a primary-replica (master-slave) database architecture:

- **Primary Database**: Handles all write operations and critical read operations.
- **Replica Databases**: Multiple read-only replicas to handle read-heavy operations.

```php
// config/database.php
return [
    'connections' => [
        'mysql' => [
            'read' => [
                'host' => [
                    env('DB_READ_HOST1', '127.0.0.1'),
                    env('DB_READ_HOST2', '127.0.0.1'),
                ],
            ],
            'write' => [
                'host' => env('DB_WRITE_HOST', '127.0.0.1'),
            ],
            'sticky' => true,
            // Other configuration...
        ],
    ],
];
```

#### Database Sharding

For future growth, implement database sharding based on tenant or property ID:

- Divide data across multiple database instances based on a sharding key (e.g., property_id).
- Use a sharding manager to route queries to the appropriate database shard.

```php
// Example sharding middleware
public function handle($request, Closure $next)
{
    $propertyId = $request->route('property_id');
    $shardId = $this->getShardId($propertyId);
    
    Config::set('database.default', "shard_{$shardId}");
    
    return $next($request);
}
```

### 2. Queue System Implementation

Move time-consuming and non-critical operations to background processing using Laravel's queue system:

#### Queue Workers

Implement multiple queue workers across different servers to process jobs in parallel:

```bash
# Run multiple queue workers on different servers
php artisan queue:work --queue=high,default,low --tries=3
```

#### Job Types to Implement

1. **Report Generation**:

```php
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $reportType;
    protected $parameters;
    protected $userId;
    
    public function __construct($reportType, $parameters, $userId)
    {
        $this->reportType = $reportType;
        $this->parameters = $parameters;
        $this->userId = $userId;
    }
    
    public function handle()
    {
        $report = ReportGenerator::generate($this->reportType, $this->parameters);
        
        // Store report and notify user
        Storage::put("reports/{$this->userId}/{$this->reportType}.pdf", $report);
        
        // Notify user
        $user = User::find($this->userId);
        $user->notify(new ReportGeneratedNotification($this->reportType));
    }
}
```

2. **Email Notifications**:

```php
class SendBulkNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $notificationType;
    protected $recipientIds;
    
    public function __construct($notificationType, array $recipientIds)
    {
        $this->notificationType = $notificationType;
        $this->recipientIds = $recipientIds;
    }
    
    public function handle()
    {
        foreach ($this->recipientIds as $id) {
            // Process each notification in chunks
            dispatch(new SendSingleNotificationJob($this->notificationType, $id));
        }
    }
}
```

3. **Data Import/Export**:

```php
class ImportPropertiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $filePath;
    protected $userId;
    
    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }
    
    public function handle()
    {
        // Process CSV/Excel file
        $importer = new PropertyImporter($this->filePath);
        $result = $importer->import();
        
        // Notify user
        $user = User::find($this->userId);
        $user->notify(new ImportCompletedNotification($result));
    }
}
```

#### Queue Drivers

Use Redis or Amazon SQS for production queue drivers:

```php
// .env
QUEUE_CONNECTION=redis
REDIS_HOST=redis.example.com
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Distributed Caching

Implement a distributed caching strategy using Redis:

#### Cache Configuration

```php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'redis'),
    
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],
    
    'prefix' => env('CACHE_PREFIX', 'pm_cache'),
];
```

#### Cache Tags for Efficient Invalidation

```php
// Controller example with cache tags
public function index(Request $request)
{
    $propertyId = $request->property_id;
    
    return Cache::tags(['properties', "property_{$propertyId}"])
        ->remember("property_{$propertyId}_details", now()->addMinutes(30), function () use ($propertyId) {
            return Property::with(['units', 'managers'])->findOrFail($propertyId);
        });
}

// Efficient cache invalidation when a property is updated
public function update(Request $request, $id)
{
    // Update property
    $property = Property::findOrFail($id);
    $property->update($request->validated());
    
    // Invalidate only related cache
    Cache::tags(["property_{$id}"])->flush();
    
    return response()->json($property);
}
```

#### Implement Cache Warming

```php
class WarmPropertyCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle()
    {
        // Warm cache for frequently accessed properties
        $popularProperties = Property::withCount('views')
            ->orderBy('views_count', 'desc')
            ->limit(100)
            ->get();
            
        foreach ($popularProperties as $property) {
            Cache::tags(['properties', "property_{$property->id}"])
                ->remember("property_{$property->id}_details", now()->addHours(1), function () use ($property) {
                    return $property->load(['units', 'managers']);
                });
        }
    }
}
```

### 4. Load Balancing

Implement load balancing to distribute traffic across multiple application servers:

#### Nginx Load Balancer Configuration

```nginx
upstream pm_app_servers {
    server app1.example.com weight=3;
    server app2.example.com weight=3;
    server app3.example.com weight=3;
    server backup.example.com backup;
}

server {
    listen 80;
    server_name pm.example.com;
    
    location / {
        proxy_pass http://pm_app_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

#### Session Management

Store sessions in Redis to enable stateless application servers:

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'connection' => 'session',
    // Other configuration...
];
```

### 5. Distributed File Storage

Move from local file storage to a distributed solution like Amazon S3:

```php
// config/filesystems.php
return [
    'default' => env('FILESYSTEM_DRIVER', 's3'),
    
    'disks' => [
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
    ],
];
```
