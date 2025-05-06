# Deploying to Render

## Overview

This guide explains how to deploy the Property Management application to Render, a cloud platform that offers easy deployment of web services, databases, and static sites.

## Prerequisites

- A Render account (sign up at [render.com](https://render.com))
- Your application code pushed to a Git repository (GitHub, GitLab, or Bitbucket)

## Deployment Configuration

The application includes a `render.yaml` file that defines the infrastructure needed to run the application on Render:

1. **Web Service**: The main Laravel application
2. **Redis Service**: For caching, sessions, and queues
3. **MySQL Database**: For persistent data storage

## How to Deploy

### Option 1: Blueprint Deployment (Recommended)

1. Log in to your Render dashboard
2. Click on the "New" button and select "Blueprint"
3. Connect your Git repository
4. Render will automatically detect the `render.yaml` file and create all the required services
5. Review the configuration and click "Apply"
6. Render will provision all services and deploy your application

### Option 2: Manual Deployment

If you prefer to set up services manually:

1. **Create a MySQL Database**:
   - In your Render dashboard, go to "New" > "PostgreSQL"
   - Name: `property-management-db`
   - Database: `property_management`
   - User: `pm_user`
   - Select a plan and region

2. **Create a Redis Service**:
   - Go to "New" > "Redis"
   - Name: `property-management-redis`
   - Select a plan and region

3. **Create a Web Service**:
   - Go to "New" > "Web Service"
   - Connect your repository
   - Name: `property-management`
   - Environment: "Docker"
   - Configure environment variables (see below)

## Environment Variables

If deploying manually, set these environment variables for your web service:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

DB_CONNECTION=mysql
DB_HOST=<Your DB Internal Hostname>
DB_PORT=3306
DB_DATABASE=property_management
DB_USERNAME=pm_user
DB_PASSWORD=<Your DB Password>

REDIS_HOST=<Your Redis Internal Hostname>
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## How It Works

### Port Configuration

Render dynamically assigns a port to your application through the `PORT` environment variable. Our Docker configuration has been updated to:

1. Read the `PORT` environment variable at startup
2. Configure Nginx to listen on that port
3. Start both Nginx and PHP-FPM services

### Database Connection

The application connects to the Render-managed MySQL database using internal network addresses, which are automatically injected as environment variables.

### Redis Connection

Similarly, the Redis connection uses internal network addresses provided by Render.

## Post-Deployment Steps

After successful deployment:

1. **Run Migrations**:
   - Go to your web service in the Render dashboard
   - Click on "Shell"
   - Run: `php artisan migrate --seed`

2. **Generate Application Key** (if not already set):
   - In the shell, run: `php artisan key:generate`
   - Copy the generated key
   - Add it as an environment variable: `APP_KEY=<generated key>`

3. **Configure Storage**:
   - Run: `php artisan storage:link`

## Troubleshooting

### Common Issues

1. **Application Error Page**:
   - Check logs in the Render dashboard
   - Verify environment variables are set correctly
   - Ensure migrations have been run

2. **Database Connection Issues**:
   - Verify database credentials
   - Check if the database service is running
   - Ensure your web service has access to the database

3. **No HTTP Ports Detected**:
   - This is usually due to the application not binding to the correct port
   - Verify the `PORT` environment variable is being used correctly
   - Check the startup script in the Dockerfile

## Scaling

To scale your application on Render:

1. **Vertical Scaling**: Upgrade your service plan for more resources
2. **Horizontal Scaling**: Increase the number of instances in your web service settings

## Monitoring

Render provides basic monitoring for all services:

- CPU and memory usage
- Request logs
- Error logs

For more advanced monitoring, consider integrating with a third-party service like New Relic or Datadog.

## Conclusion

With the provided configuration, your Property Management application should deploy smoothly to Render. The platform handles infrastructure management, allowing you to focus on developing and improving your application.