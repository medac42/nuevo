# Use official PHP Apache image
FROM php:8.2-apache

# Enable Apache Mod Rewrite for .htaccess support
RUN a2enmod rewrite

# Install PHP extensions required for Steam OpenID (cURL)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install curl

# Copy project files to the web server root
COPY . /var/www/html/

# Ensure safe permissions
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80 (Docker default)
EXPOSE 80
