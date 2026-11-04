#!/bin/sh

cd /app || exit 1

echo "Installing MicroPowerManager in development mode..."
npm install

npm run serve
