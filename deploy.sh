#!/bin/bash
set -e

cd /var/www/website-trison-topup

echo "==> Pulling latest code"
sudo git pull origin main

echo "==> Fixing ownership"
sudo chown -R www-data:www-data /var/www/website-trison-topup

echo "==> Installing composer dependencies"
sudo -u www-data composer install --no-dev --optimize-autoloader

echo "==> Clearing caches before build (wayfinder needs fresh routes)"
sudo -u www-data php artisan optimize:clear

echo "==> Installing npm dependencies"
sudo -u www-data npm --cache /tmp/npm-cache-www install

echo "==> Building frontend assets"
sudo -u www-data npm --cache /tmp/npm-cache-www run build

echo "==> Running migrations"
sudo -u www-data php artisan migrate --force

echo "==> Clearing and rebuilding caches"
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

echo "DEPLOY_SUCCESS"
