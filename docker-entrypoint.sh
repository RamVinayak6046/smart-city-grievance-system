#!/bin/bash
set -e

PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure database and uploads permissions
chown -R www-data:www-data /var/www/html/database /var/www/html/uploads 2>/dev/null || true
chmod -R 775 /var/www/html/database /var/www/html/uploads 2>/dev/null || true

exec apache2-foreground
