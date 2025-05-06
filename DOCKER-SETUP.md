# Docker Setup for Local Development

## Introduction

This guide explains how to use Docker for local development of the Property Management application. Docker provides a consistent development environment across different machines and operating systems, making it easier to collaborate and onboard new developers.

## Prerequisites

- [Docker](https://www.docker.com/products/docker-desktop) installed on your machine
- [Docker Compose](https://docs.docker.com/compose/install/) installed on your machine (usually comes with Docker Desktop)
- Git for cloning the repository

## Setup Instructions

### 1. Clone the Repository

```bash
git clone <repository-url>
cd property-management
```

### 2. Environment Configuration

Copy the example environment file and modify it if needed:

```bash
cp .env.example .env
```

Update the following variables in the `.env` file to use the Docker services:

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=property_management
DB_USERNAME=pm_user
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 3. Start Docker Containers

Build and start the Docker containers:

```bash
docker-compose up -d
```

This command will:
- Build the PHP container using the Dockerfile
- Start the Nginx web server
- Start the MySQL database
- Start the Redis cache server

### 4. Install Dependencies

Install PHP dependencies using Composer:

```bash
docker-compose exec app composer install
```

### 5. Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### 6. Run Migrations and Seeders

```bash
docker-compose exec app php artisan migrate --seed
```

### 7. Access the Application

The application should now be available at [http://localhost](http://localhost)

## Docker Commands Reference

### Container Management

- **Start containers**: `docker-compose up -d`
- **Stop containers**: `docker-compose down`
- **Rebuild containers**: `docker-compose up -d --build`
- **View container logs**: `docker-compose logs`
- **View specific container logs**: `docker-compose logs app`

### Executing Commands

- **Run Artisan commands**: `docker-compose exec app php artisan <command>`
- **Run Composer commands**: `docker-compose exec app composer <command>`
- **Run NPM commands**: `docker-compose exec app npm <command>`
- **Access MySQL CLI**: `docker-compose exec db mysql -u pm_user -p property_management`
- **Access Redis CLI**: `docker-compose exec redis redis-cli`

### Database Management

- **Run migrations**: `docker-compose exec app php artisan migrate`
- **Refresh database**: `docker-compose exec app php artisan migrate:fresh --seed`

## Container Details

### Services

1. **app**: PHP 8.1 FPM container with the Laravel application
2. **webserver**: Nginx web server
3. **db**: MySQL 8.0 database server
4. **redis**: Redis cache server

### Ports

- **Nginx**: 80 (HTTP), 443 (HTTPS)
- **MySQL**: 3306
- **Redis**: 6379

### Volumes

- **Application code**: Mounted to `/var/www/html` in the app and webserver containers
- **MySQL data**: Persisted in a Docker volume
- **Configuration files**: Custom configuration for PHP, Nginx, and MySQL

## Troubleshooting

### Permission Issues

If you encounter permission issues, you may need to adjust the ownership of files:

```bash
docker-compose exec app chown -R www:www /var/www/html/storage
```

### Connection Issues

If you can't connect to the database or Redis, ensure the services are running:

```bash
docker-compose ps
```

Check that the environment variables in your `.env` file match the Docker service names and credentials.

### Container Won't Start

If a container fails to start, check the logs:

```bash
docker-compose logs <service-name>
```

## Development Workflow

1. Make changes to the code on your local machine
2. The changes are automatically reflected in the Docker containers
3. Run tests using `docker-compose exec app php artisan test`
4. Use Laravel Debugbar for performance profiling (already configured)

## Extending the Docker Setup

### Adding New Services

To add a new service, update the `docker-compose.yml` file with the new service configuration.

### Customizing PHP Configuration

Modify the `docker/php/local.ini` file to adjust PHP settings.

### Customizing Nginx Configuration

Modify the `docker/nginx/conf.d/app.conf` file to adjust Nginx settings.

## Conclusion

This Docker setup provides a consistent development environment for the Property Management application. It includes all the necessary services (PHP, Nginx, MySQL, Redis) and is configured for optimal performance and ease of use.