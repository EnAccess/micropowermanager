#!/bin/sh

printenv > /etc/environment

echo "cron starting..."
cron

# clear log file
: > /var/log/cron.log

tail -f /var/log/cron.log
