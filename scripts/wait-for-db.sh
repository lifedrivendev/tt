#!/bin/bash
set -e

MAX_RETRIES=30
RETRY_INTERVAL=2
RETRY_COUNT=0

echo "Waiting for database connection on ${WORDPRESS_DB_HOST}..."
DB_HOST=$(echo "$WORDPRESS_DB_HOST" | cut -d':' -f1)

until nc -z "$DB_HOST" 3306; do
  RETRY_COUNT=$((RETRY_COUNT + 1))
  if [ "$RETRY_COUNT" -ge "$MAX_RETRIES" ]; then
    echo "Error: Database is still unavailable after $MAX_RETRIES retries. Exiting."
    exit 1
  fi
  echo "Database is not available yet. Retrying in $RETRY_INTERVAL seconds..."
  sleep $RETRY_INTERVAL
done

echo "Database is available."
