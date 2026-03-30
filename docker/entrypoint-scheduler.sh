#!/bin/sh

# MicroPowerManager may run in different environments that handle storage
# differently:
#
# For example:
#   - Local dev: Docker host bind mounts for source code
#   - Local dev or simple production: Docker volumes for storage
#   - Kubernetes production: Persistent Volume Claims (PVCs) for storage
#
# These option behaves differently when it comes to directory structure and
# permissions:
#   - PVCs and empty Docker volumes start out completely empty,
#     so Laravel’s required "storage/" subdirectories won’t exist.
#   - Host bind mounts may carry host system permissions,
#     which often don’t match the webserver (www-data) user inside the
#.    container.
#
# Since Laravel requires a specific storage structure with correct permissions
# (for logs, caches, sessions, views, etc.), we bootstrap it here at startup.
# This guarantees a consistent runtime environment regardless of how the volume
# is mounted.
#
echo "Ensuring Laravel storage structure and permissions..."

# Recreate expected folder structure (based on upstream Laravel repo)
# https://github.com/laravel/laravel/tree/12.x/storage
mkdir -p \
  /var/www/html/storage/app/private \
  /var/www/html/storage/app/public \
  /var/www/html/storage/framework/cache \
  /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/logs

# Fix ownership and permissions for Laravel internals
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage

# DEVCONTAINER PLACEHOLDER (DO NOT REMOVE THIS LINE)

# If running in production, generate Laravel caches so the app is optimized.
# We do this at container startup (not build) so runtime environment variables are available to the framework.
if [ "$APP_ENV" = "production" ] || ( [ -n "$MPM_FORCE_OPTIMIZE" ] && [ "$MPM_FORCE_OPTIMIZE" != "0" ] && [ "$MPM_FORCE_OPTIMIZE" != "false" ] ); then
  cd /var/www/html
  echo "Optimizing Laravel (caching config, events, routes, views)..."
  # Currently, we cannot run `artisan optimize` as `artisan config:cache` fails
  # due to the way we have use closures in `FilterHandler`.
  # See: https://github.com/Seldaek/monolog/issues/1965
  # gosu www-data php artisan optimize || echo "php artisan optimize failed"
  gosu www-data php artisan event:cache || echo "php artisan event:cache failed"
  gosu www-data php artisan route:cache || echo "php artisan route:cache failed"
  gosu www-data php artisan view:cache || echo "php artisan view:cache failed"
fi

printenv > /etc/environment

echo "cron starting..."
cron

# clear log file
: > /var/log/cron.log

# show logs in STDOUT
tail -f /var/log/cron.log
