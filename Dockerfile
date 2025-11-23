FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html