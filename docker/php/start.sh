#!/bin/sh
set -e

composer install --no-interaction --prefer-dist

until php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' >/dev/null 2>&1; do
  echo "Waiting for MySQL..."
  sleep 2
done

php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000 &
php artisan schedule:work
