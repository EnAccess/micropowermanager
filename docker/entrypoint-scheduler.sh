#!/bin/sh

echo "Ensure correct filesystem permissions are set..."
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage

# DEVCONTAINER PLACEHOLDER (DO NOT REMOVE THIS LINE)

printenv > /etc/environment

echo "cron starting..."
cron

# clear log file
: > /var/log/cron.log

# show logs in STDOUT
tail -f /var/log/cron.log
