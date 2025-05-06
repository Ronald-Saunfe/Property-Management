# Swagger/OpenAPI Documentation for Property Management API

This document provides instructions on how to use, maintain, and extend the Swagger/OpenAPI documentation for the Property Management API.

## Accessing the Documentation

The Swagger UI documentation is available at:

```
http://127.0.0.1:8000/api/documentation
```

You need to have the Laravel development server running to access this URL. Start the server with:

```bash
php artisan serve
```

## How the Documentation Works

The API documentation is generated using the [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) package, which is a Laravel wrapper for [Swagger-PHP](https://github.com/zircote/swagger-php) and [Swagger UI](https://swagger.io/tools/swagger-ui/).

The documentation is generated from annotations in your PHP code. These annotations are processed to create an OpenAPI specification file, which is then displayed in the Swagger UI.

## Adding Documentation to Controllers

To document a new controller or endpoint, add OpenAPI annotations to your controller methods. Here's an example:

```php
/**
 * @OA\Get(
 *     path="/your-endpoint",
 *     operationId="getYourEndpoint",
 *     tags={"YourTag"},
 *     summary="Short description",
 *     description="Longer description",
 *     security={{
 *       "bearerAuth": {}
 *     }},
 *     @OA\Parameter(
 *         name="parameter_name",
 *         in="query",
 *         description="Parameter description",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(...)
 *     )
 * )
 */
public function yourMethod() {
    // Your code here
}
```

### Key Annotation Components

1. **Operation Annotation**: `@OA\Get`, `@OA\Post`, `@OA\Put`, `@OA\Delete`, etc.
2. **Path**: The URL path for the endpoint
3. **Tags**: Used to group endpoints in the Swagger UI
4. **Parameters**: Query parameters, path parameters, headers, etc.
5. **Request Body**: For POST/PUT requests
6. **Responses**: The possible responses with status codes and content
7. **Security**: Authentication requirements

## Regenerating the Documentation

After adding or modifying annotations, regenerate the documentation with:

```bash
php artisan l5-swagger:generate
```

## Configuration

The L5-Swagger configuration is located in `config/l5-swagger.php`. You can customize various aspects of the documentation generation and display here.

Key configuration options include:

- Documentation routes and middleware
- API version information
- Security definitions
- Documentation UI customization

## Best Practices

1. **Group Related Endpoints**: Use consistent tags to group related endpoints
2. **Be Descriptive**: Provide clear summaries and descriptions
3. **Document All Parameters**: Include all possible query parameters, path variables, and request body fields
4. **Include Response Examples**: Show what the API returns for different scenarios
5. **Document Error Responses**: Include possible error responses and their meanings
6. **Keep Documentation Updated**: Regenerate documentation when API changes

## Example Controllers

The following controllers have been documented as examples:

- `UserController`: Complete CRUD operations with filtering, pagination, and statistics
- `PropertyController`: Property listing with filtering and pagination

Refer to these controllers for examples of how to document different types of endpoints.

## Additional Resources

- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger-PHP Documentation](https://zircote.github.io/swagger-php/)
- [L5-Swagger GitHub Repository](https://github.com/DarkaOnLine/L5-Swagger)