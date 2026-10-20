#!/bin/sh
set -e

cd /var/www/html

if [ ! -d "vendor" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "MySQL ready."

php artisan migrate --force

# Seeding is opt-in: demo accounts use well-known passwords.
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    php artisan db:seed --force
fi
[ -L public/storage ] || php artisan storage:link
chmod -R 775 storage bootstrap/cache

exec php-fpm
