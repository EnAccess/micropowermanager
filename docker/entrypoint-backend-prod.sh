#!/bin/sh

cd /var/www/html

echo "Running MicroPowerManager central migrations..."
php artisan migrate --force
echo "Running MicroPowerManager tenant migrations..."
php artisan migrate-tenant --force

# Check if MPM_LOAD_DEMO_DATA is set and not empty or false-y,
# only then load the demo data.
if [ -n "$MPM_LOAD_DEMO_DATA" ] && [ "$MPM_LOAD_DEMO_DATA" != "0" ] && [ "$MPM_LOAD_DEMO_DATA" != "false" ]; then
  echo "MPM_LOAD_DEMO_DATA is set. Seeding database with Demo data."
  php artisan db:seed
fi

echo "Executing command: $@"

# the main image's CMD arguments are somehow not passed to this script
# so we need to check if there are any arguments and if not, execute apache2-foreground which is the default CMD of the main image
if [ -z "$@" ]; then
    echo "No arguments supplied, executing apache2-foreground..."
    exec apache2-foreground
else
    exec "$@"
fi
