#!/bin/sh
set -eu

cd /var/www/html

# Cache dilakukan di semua container: env dari platform baru tersedia saat runtime.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Hanya web yang menjalankan migrasi agar worker/scheduler tidak balapan.
if [ "${CONTAINER_ROLE:-web}" = "web" ]; then
    php artisan migrate --force
    php artisan storage:link || true
fi

if [ "${CONTAINER_ROLE:-web}" = "worker" ]; then
    exec php artisan queue:work --sleep=2 --tries=3 --max-time=3600 --timeout=60
fi

if [ "${CONTAINER_ROLE:-web}" = "scheduler" ]; then
    exec php artisan schedule:work
fi

exec "$@"
