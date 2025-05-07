FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www:www . /var/www/html

# Set environment variable to allow Composer to run as root/superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install composer dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Setup Nginx
COPY docker/nginx/conf.d/app.conf /etc/nginx/sites-available/default

# Create startup script
RUN echo '#!/bin/bash' > /var/www/html/start.sh && \
    echo 'PORT=${PORT:-8080}' >> /var/www/html/start.sh && \
    echo 'sed -i "s/listen 80/listen $PORT/g" /etc/nginx/sites-available/default' >> /var/www/html/start.sh && \
    echo 'sed -i "s/fastcgi_pass app:9000/fastcgi_pass 127.0.0.1:9000/g" /etc/nginx/sites-available/default' >> /var/www/html/start.sh && \
    echo 'mkdir -p /var/run/nginx' >> /var/www/html/start.sh && \
    echo 'touch /var/run/nginx/nginx.pid' >> /var/www/html/start.sh && \
    echo 'chmod -R 777 /var/run/nginx' >> /var/www/html/start.sh && \
    echo '# Check if vendor directory exists, if not run composer install' >> /var/www/html/start.sh && \
    echo 'if [ ! -d "/var/www/html/vendor" ]; then' >> /var/www/html/start.sh && \
    echo '  composer install --no-interaction --optimize-autoloader' >> /var/www/html/start.sh && \
    echo 'fi' >> /var/www/html/start.sh && \
    echo 'php-fpm -D' >> /var/www/html/start.sh && \
    echo 'nginx -g "daemon off;"' >> /var/www/html/start.sh && \
    chmod +x /var/www/html/start.sh

# Change current user to www for application files
RUN chown -R www:www /var/www/html

# Switch back to root for starting services
USER root

# Expose port (will be overridden by PORT env var in Render)
EXPOSE 8080

# Set environment variables for Render
ENV PORT=8080

# Ensure Nginx can write to these directories
RUN mkdir -p /var/log/nginx /var/lib/nginx /var/run/nginx && \
    chmod -R 777 /var/log/nginx /var/lib/nginx /var/run/nginx

CMD ["/var/www/html/start.sh"]