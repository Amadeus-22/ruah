#!/bin/sh
# Prepara o Laravel a cada subida do container: migrações e caches de produção.
set -e
cd /app
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec "$@"
