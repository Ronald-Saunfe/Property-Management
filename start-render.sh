#!/bin/bash

# Create necessary directories
mkdir -p /var/run/php
mkdir -p /var/run/nginx

# Start PHP-FPM
php-fpm8.2 --fpm-config /etc/php/8.2/fpm/php-fpm.conf

# Start Nginx
nginx -g "daemon off;"