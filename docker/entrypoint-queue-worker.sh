#!/bin/sh

echo "Ensure correct filesystem permissions are set..."
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage

# DEVCONTAINER PLACEHOLDER (DO NOT REMOVE THIS LINE)

/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
