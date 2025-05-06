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

# Setup Nginx
COPY docker/nginx/conf.d/app.conf /etc/nginx/sites-available/default

# Create startup script
RUN echo '#!/bin/bash' > /var/www/html/start.sh && \
    echo 'PORT=${PORT:-80}' >> /var/www/html/start.sh && \
    echo 'sed -i "s/listen 80/listen $PORT/g" /etc/nginx/sites-available/default' >> /var/www/html/start.sh && \
    echo 'service nginx start' >> /var/www/html/start.sh && \
    echo 'php-fpm' >> /var/www/html/start.sh && \
    chmod +x /var/www/html/start.sh

# Change current user to www for application files
RUN chown -R www:www /var/www/html

# Switch back to root for starting services
USER root

# Expose port (will be overridden by PORT env var in Render)
EXPOSE 8080

CMD ["/var/www/html/start.sh"]