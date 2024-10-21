#!/bin/sh

# This script builds the production application at container runtime before serving it
# via webserver.
# This is enable the use of environment variables in the production container.
# As VueJS bundles the environment variables value into the application,
# see https://cli.vuejs.org/guide/mode-and-env.html#environment-variables

# An alternative approach would be to use a tool like:
# https://import-meta-env.org/

cd /app
npm run build

http-server dist -p 8081
