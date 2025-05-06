# Rate Limiting Documentation

## Overview

This document describes the rate limiting implementation in the Property Management application. Rate limiting helps protect the API from abuse and ensures fair usage of resources.

## Implementation

The application uses a custom rate limiting middleware (`CustomThrottleRequests`) that extends Laravel's built-in `ThrottleRequests` middleware. This custom implementation applies different rate limits based on the endpoint being accessed.

## Rate Limit Rules

The following rate limits are applied:

1. **Authentication Endpoints** (login, register, password reset):
   - 10 requests per minute
   - These strict limits help prevent brute force attacks

2. **Property Management Endpoints** (properties, units, tenants, leases):
   - 60 requests per minute
   - Moderate limits for regular API operations

3. **All Other API Endpoints**:
   - 120 requests per minute
   - Default limit for general API usage

## Response Headers

When rate limiting is applied, the following headers are included in API responses:

- `X-RateLimit-Limit`: Maximum number of requests allowed per time window
- `X-RateLimit-Remaining`: Number of requests remaining in the current time window
- `X-RateLimit-Reset`: Time in seconds until the rate limit resets

## Rate Limit Exceeded Response

When a client exceeds the rate limit, they will receive a `429 Too Many Requests` response with a JSON body:

```json
{
    "message": "Too Many Attempts."
}
```

## Customizing Rate Limits

To modify the rate limits, edit the `CustomThrottleRequests` middleware in `app/Http/Middleware/CustomThrottleRequests.php`.

The rate limit format is `{max requests},{minutes}`. For example:

- `10,1` means 10 requests per 1 minute
- `100,60` means 100 requests per 60 minutes (1 hour)

## Best Practices

1. **Client-Side Implementation**:
   - Implement exponential backoff when receiving 429 responses
   - Cache frequently accessed data to reduce API calls
   - Batch API requests when possible

2. **API Key Authentication**:
   - Consider implementing API key authentication for different rate limit tiers
   - Premium clients could receive higher rate limits

3. **Monitoring**:
   - Regularly monitor rate limit hits to identify potential abuse
   - Adjust limits based on actual usage patterns