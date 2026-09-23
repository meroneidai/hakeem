#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link --force
fi

if [ ! -f public/build/manifest.json ]; then
    echo "==> Vite assets missing; running npm install && npm run build"
    if [ ! -d node_modules ]; then
        if [ -f package-lock.json ]; then
            npm ci --no-fund --no-audit
        else
            npm install --no-fund --no-audit
        fi
    fi
    npm run build
    test -f public/build/manifest.json
fi

if [ "${SKIP_STARTUP_MIGRATE:-0}" != "1" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
