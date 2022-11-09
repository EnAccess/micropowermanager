#!/bin/sh
# copy from the image backup location to the volume mount
cp -a /app_backup/dist/* /app/dist/
# this next line runs the docker command
exec "$@"
