#!/bin/sh

cd /var/www/html

echo "Running MicroPowerManager central migrations..."
php artisan migrate
echo "Running MicroPowerManager tenant migrations..."
php artisan migrate-tenant

echo "Executing command: $@"

# the main image's CMD arguments are somehow not passed to this script
# so we need to check if there are any arguments and if not, execute apache2-foreground which is the default CMD of the main image
if [ -z "$@" ]; then
    echo "No arguments supplied, executing apache2-foreground..."
    exec apache2-foreground
else
    exec "$@"
fi
