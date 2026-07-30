#!/bin/bash
set -e

cd /var/www/html

# Install composer dependencies if vendor/ missing (bind mount override)
if [ ! -d "vendor" ]; then
    echo "vendor/ not found — running composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# CI4 writable permissions
chown -R www-data:www-data writable
chmod -R 775 writable

# Start Apache
exec apache2-foreground
