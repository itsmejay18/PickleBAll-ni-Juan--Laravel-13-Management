#!/usr/bin/env bash
# Pickle Ballan ni Juan - production deploy script.
# Run from a freshly-pulled code directory on the server.
#   bash deploy.sh
set -euo pipefail

echo "==> Installing PHP dependencies (no dev)..."
composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader

echo "==> Installing JS dependencies and building assets..."
npm ci
npm run build

if [ ! -f .env ]; then
    echo "==> No .env found, copying from .env.example. Edit before re-running."
    cp .env.example .env
    php artisan key:generate
    exit 1
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding roles + permissions if missing..."
php artisan db:seed --class=RoleSeeder --force || true

echo "==> Linking storage..."
php artisan storage:link || true

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Clearing scheduled task cache..."
php artisan schedule:clear-cache || true

echo "==> Restarting queue workers..."
php artisan queue:restart || true

echo "==> Done. Make sure cron is running:"
echo "    * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "    and a supervised queue worker:"
echo "    php artisan queue:work --tries=3 --backoff=10"
