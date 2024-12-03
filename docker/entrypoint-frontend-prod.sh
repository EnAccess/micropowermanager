#!/bin/sh

# This script enables the use of environment variables in the production container.
# See https://import-meta-env.org/guide/getting-started/runtime-transform.html

cd /app
npx import-meta-env -x .env.example -p dist/index.html

http-server dist -p 8081
