#!/bin/bash
set -e

echo "Starting container initialization..."

# Wait for the database to be ready
echo "Waiting for database..."
/usr/local/bin/wait-for-db.sh

cd /var/www/html

echo "Running runtime configuration..."
/usr/local/bin/runtime-config.sh

echo "Starting Apache..."
exec apache2ctl -D FOREGROUND
