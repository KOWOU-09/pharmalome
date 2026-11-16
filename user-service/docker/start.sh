#!/bin/sh
set -e

cd /var/www/html

php artisan migrate --force

php artisan users:create-admin

exec apache2-foreground