FROM php:8.2-apache

# Install Node.js 18.x LTS
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install system dependencies first
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libldap2-dev \
    libssl-dev \
    libsodium-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions one by one for better error handling
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) xml
RUN docker-php-ext-install -j$(nproc) mbstring
RUN docker-php-ext-install -j$(nproc) curl

# Install intl extension
RUN docker-php-ext-install -j$(nproc) intl

# Install OPcache
RUN docker-php-ext-install -j$(nproc) opcache

# Configure and install LDAP
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/
RUN docker-php-ext-install -j$(nproc) ldap

# Install remaining extensions
RUN docker-php-ext-install -j$(nproc) sodium
RUN docker-php-ext-install -j$(nproc) bcmath
RUN docker-php-ext-install -j$(nproc) exif

# These extensions are already enabled by default in PHP 8.2
# fileinfo, filter, json, session - no need to install

# Enable Apache modules
RUN a2enmod rewrite headers

# Set recommended PHP settings for Joomla
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_vars = 3000'; \
    echo 'date.timezone = UTC'; \
    } > /usr/local/etc/php/conf.d/joomla.ini

# Configure OPcache for production
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Copy application files
COPY . /var/www/html/

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/cache /var/www/html/tmp /var/www/html/logs /var/www/html/media/vendor \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/cache /var/www/html/tmp /var/www/html/logs \
    && if [ -d "/var/www/html/images" ]; then chmod -R 775 /var/www/html/images; fi

# Create Apache configuration for Joomla
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n\
\n\
<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html\n\
    SetEnv HTTP_HOST localhost\n\
</VirtualHost>' > /etc/apache2/conf-available/joomla.conf \
    && a2enconf joomla \
    && a2dissite 000-default \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Build Joomla dependencies if composer.json and package.json exist
RUN if [ -f /var/www/html/composer.json ]; then \
        cd /var/www/html && composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction; \
    fi

# Install npm dependencies and build frontend assets for Joomla
RUN if [ -f /var/www/html/package.json ]; then \
        cd /var/www/html \
        && npm ci \
        && npm run build:css \
        && npm run build:js \
        && npm prune --production; \
    fi

# Fix HTTP_HOST undefined issue in Joomla
RUN sed -i 's/\$_SERVER\['\''HTTP_HOST'\''\]/(\$_SERVER\['\''HTTP_HOST'\''\] \?\? '\''localhost'\'')/g' /var/www/html/libraries/src/Uri/Uri.php

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]