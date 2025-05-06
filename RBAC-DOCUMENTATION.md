# Role-Based Access Control (RBAC) Documentation

## Overview

This document explains how to use the Role-Based Access Control (RBAC) system implemented in the application. The RBAC system restricts access to certain routes and resources based on user roles.

## Available Roles

The application currently supports the following roles:

- `admin`: System administrators with full access to all features
- `agent`: Property agents who manage properties and tenants
- `landlord`: Property owners who have access to their properties

## How RBAC Works

The RBAC system is implemented using a custom middleware called `CheckRole`. This middleware checks if the authenticated user has one of the required roles to access a specific route.

### Middleware Registration

The middleware is registered in `app/Http/Kernel.php` with the alias `role`:

```php
protected $routeMiddleware = [
    // Other middleware...
    'role' => \App\Http\Middleware\CheckRole::class,
];
```

## Using RBAC in Routes

### Protecting Routes for a Single Role

To restrict a route to users with a specific role, use the `role` middleware followed by the role name:

```php
Route::get('/admin-dashboard', [AdminController::class, 'dashboard'])
    ->middleware(['auth:sanctum', 'role:admin']);
```

This route can only be accessed by authenticated users with the `admin` role.

### Protecting Routes for Multiple Roles

To allow multiple roles to access a route, separate the role names with commas:

```php
Route::get('/property-management', [PropertyController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin,agent']);
```

This route can be accessed by users with either the `admin` or `agent` role.

## Example Routes

The application includes example routes in `routes/api.php` that demonstrate how to use the RBAC middleware:

1. **Admin Only Route**:
   ```php
   Route::get('/admin-only', [RbacExampleController::class, 'adminOnly'])
       ->middleware(['auth:sanctum', 'role:admin']);
   ```

2. **Agent and Landlord Route**:
   ```php
   Route::get('/agent-landlord', [RbacExampleController::class, 'agentLandlord'])
       ->middleware(['auth:sanctum', 'role:agent,landlord']);
   ```

3. **All Authenticated Users Route**:
   ```php
   Route::get('/all-authenticated', [RbacExampleController::class, 'allAuthenticated'])
       ->middleware('auth:sanctum');
   ```

## Best Practices

1. **Always Use Authentication First**: The `role` middleware should always be used after the `auth:sanctum` middleware to ensure the user is authenticated before checking roles.

2. **Group Routes by Role**: Use route groups to apply role middleware to multiple routes:

   ```php
   Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
       Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
       Route::get('/admin/users', [AdminController::class, 'users']);
       // More admin routes...
   });
   ```

3. **Check Roles in Controllers**: For more complex authorization logic, you can also check roles directly in your controllers:

   ```php
   public function update(Request $request, $id)
   {
       $user = Auth::user();
       
       // Only admins can update any property
       if ($user->role === 'admin') {
           // Update any property
       }
       // Agents can only update properties they manage
       elseif ($user->role === 'agent' && $this->managesProperty($user, $id)) {
           // Update property
       }
       else {
           return response()->json(['message' => 'Unauthorized'], 403);
       }
   }
   ```

## Testing RBAC

You can test the RBAC implementation using the Swagger UI at `/api/documentation`. The example endpoints are grouped under the "RBAC Examples" tag.

## Extending RBAC

To add new roles or modify existing ones:

1. Update the validation rules in `app/Http/Requests/UserRequest.php`
2. Update the `role` field in the `User` model
3. Update any role-specific logic in your controllers

## Troubleshooting

- If a user receives a 403 Forbidden response, it means they are authenticated but don't have the required role.
- If a user receives a 401 Unauthorized response, it means they are not authenticated at all.