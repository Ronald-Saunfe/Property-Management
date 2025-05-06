# Laravel Application Profiling Documentation

## Overview

This document outlines the performance profiling conducted on key endpoints in the property management application using Laravel Debugbar. The profiling focused on identifying performance bottlenecks and suggesting improvements.

## Profiled Endpoints

### 1. Payment Controller - Index Method

**Endpoint:** `GET /api/payments`

**Performance Issues:**
- Multiple eager loading relationships (`lease`, `lease.tenant`, `lease.unit`) causing N+1 query problems
- Complex search functionality across multiple related tables
- No query caching for frequently accessed data

**Improvements:**
- Optimize eager loading by using `with()` more selectively
- Add database indexing on frequently filtered columns (`lease_id`, `status`, `due_date`, `payment_date`)
- Implement query caching for common filter combinations
- Consider pagination optimization by using cursor pagination for large datasets

**Code Improvements:**
```php
// Before
$query = Payment::with(['lease', 'lease.tenant', 'lease.unit']);

// After
// Only load relationships when needed
$query = Payment::query();
if ($request->has('include_relationships')) {
    $query->with(['lease', 'lease.tenant', 'lease.unit']);
}

// Add caching for common queries
$cacheKey = 'payments_' . md5(json_encode($request->all()));
$payments = Cache::remember($cacheKey, now()->addMinutes(15), function() use ($query, $perPage) {
    return $query->paginate($perPage);
});
```

### 2. Lease Controller - Index Method

**Endpoint:** `GET /api/leases`

**Performance Issues:**
- Loading too many relationships by default (`unit`, `tenant`, `tenants`, `payments`)
- Complex search functionality with multiple joins
- No limit on the number of related records loaded

**Improvements:**
- Make relationship loading optional based on query parameters
- Add database indexes on frequently filtered columns (`status`, `unit_id`, `tenant_id`, `start_date`, `end_date`)
- Limit the number of related records (especially payments) using `limit()` or custom pagination
- Consider implementing API resource classes for better response formatting

**Code Improvements:**
```php
// Before
$query = Lease::with(['unit', 'tenant', 'tenants', 'payments']);

// After
$query = Lease::query();
$relationships = [];

// Only load relationships that are needed
if ($request->has('include_unit')) {
    $relationships[] = 'unit';
}
if ($request->has('include_tenant')) {
    $relationships[] = 'tenant';
}
if ($request->has('include_tenants')) {
    $relationships[] = 'tenants';
}
if ($request->has('include_payments')) {
    // Limit the number of payments loaded
    $relationships[] = 'payments:id,lease_id,amount,due_date,status';
}

if (!empty($relationships)) {
    $query->with($relationships);
}
```

### 3. Tenant Controller - Index Method

**Endpoint:** `GET /api/tenants`

**Performance Issues:**
- Complex filtering with many conditional clauses
- Inefficient search across multiple columns
- Loading all tenant fields when only a subset is needed for listing

**Improvements:**
- The existing caching implementation is good but could be optimized
- Use database indexes on frequently filtered and sorted columns
- Consider implementing a search service for more efficient text searching
- Use select() to limit the columns retrieved from the database

**Code Improvements:**
```php
// The tenant controller already implements caching, which is good practice
// Additional improvements:

// Use a search service for more efficient text searching
if ($request->has('search')) {
    $search = $request->search;
    // Use full-text search if available
    if (config('database.default') === 'mysql') {
        // Assuming full-text indexes are set up
        $query->whereRaw(
            "MATCH(first_name, last_name, email, occupation) AGAINST(? IN BOOLEAN MODE)", 
            [$search]
        );
    } else {
        // Fall back to LIKE searches
        $query->where(function (Builder $query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('occupation', 'like', "%{$search}%");
        });
    }
}
```
