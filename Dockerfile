FROM php:8.2-apache

# Install system dependencies & libraries
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        gd \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module & directory index priority
RUN a2enmod rewrite headers \
    && echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy & set permissions on entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/database /var/www/html/uploads

# Expose default HTTP port
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
