#!/bin/sh

printenv > /etc/environment

echo "cron starting..."
cron

# clear log file
: > /var/log/cron.log

# show logs in STDOUT
tail -f /var/log/cron.log
