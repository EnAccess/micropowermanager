#!/bin/sh

# Inject environment variables
/usr/local/bin/import-meta-env-alpine -x /usr/share/nginx/html/.env.example -p /usr/share/nginx/html/index.html

# Run nginx
nginx -g "daemon off;"
