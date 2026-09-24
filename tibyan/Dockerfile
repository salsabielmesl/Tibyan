FROM php:8.3-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Configure Apache to listen on port 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-enabled/000-default.conf

# Create Apache config with AcceptPathInfo + AllowOverride
RUN printf '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    AcceptPathInfo On\n\
    Require all granted\n\
    php_flag display_errors off\n\
    php_value error_reporting 6135\n\
</Directory>\n' > /etc/apache2/conf-available/codeigniter.conf \
    && a2enconf codeigniter

# Copy project files
COPY . /var/www/html/

# Load custom php.ini
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Create session directory and fix permissions
RUN mkdir -p /tmp/ci_sessions \
    && chmod 777 /tmp/ci_sessions \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 777 /var/www/html/application/cache \
    && chmod -R 777 /var/www/html/application/logs

EXPOSE 8080

CMD ["apache2-foreground"]