FROM php:8.2-apache

# Install PHP extensions required by Little Steps
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set document root to /var/www/html
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Set Apache to allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/little-steps.conf \
    && a2enconf little-steps

# Expose port (Railway will override via $PORT env var)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
