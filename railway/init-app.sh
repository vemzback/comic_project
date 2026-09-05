#!/usr/bin/env sh

set -eu

php artisan migrate --force

if [ "${CATALOG_BOOTSTRAP_ENABLED:-false}" = "true" ]; then
    php artisan db:seed --class='Database\Seeders\CatalogSnapshotSeeder' --force
fi

php artisan storage:link --force
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
