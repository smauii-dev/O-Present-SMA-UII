#!/bin/bash
set -e

cd /var/www/html

# Install composer dependencies if vendor/ missing (bind mount override)
if [ ! -d "vendor" ]; then
    echo "vendor/ not found — running composer install..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Install frontend dependencies + build assets if node_modules/ missing
if [ ! -d "node_modules" ]; then
    echo "node_modules/ not found — running bun install..."
    bun install --frozen-lockfile 2>/dev/null || bun install
fi

# Build frontend assets if public/build/ is missing
if [ ! -d "public/build" ]; then
    echo "public/build/ not found — running bun run build..."
    bun run build
fi

# CI4 writable permissions
chown -R www-data:www-data writable
chmod -R 775 writable

# Start Apache
exec apache2-foreground
