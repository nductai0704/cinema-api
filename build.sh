#!/bin/bash
set -e

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Generating app key..."
php artisan key:generate --force

echo "Running migrations..."
php artisan migrate --force

echo "Building complete!"
