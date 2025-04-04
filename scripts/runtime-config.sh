#!/bin/bash
set -e

# Mount the S3 bucket if S3_BUCKET is provided
if [ -z "$S3_BUCKET" ]; then
  echo "S3_BUCKET environment variable not provided. Skipping S3 mount."
else
  echo "Mounting S3 bucket '$S3_BUCKET' to /var/www/html/wp-content/uploads..."

  if mount-s3 "$S3_BUCKET" /var/www/html/wp-content/uploads \
    --allow-other \
    --allow-delete \
    --allow-overwrite \
    --uid "$(id -u www-data)" \
    --gid "$(id -g www-data)" \
    --dir-mode 0775 \
    --file-mode 0664 \
    ${AWS_REGION:+--region "$AWS_REGION"}; then
    echo "Successfully mounted S3 bucket."
  else
    echo "Warning: Failed to mount S3 bucket. WordPress may not function correctly."
  fi
fi

# Create wp-config.php if it does not exist and set debug options
if [ ! -f wp-config.php ]; then
  echo "Creating wp-config.php..."
  wp config create \
    --dbname="$WORDPRESS_DB_NAME" \
    --dbuser="$WORDPRESS_DB_USER" \
    --dbpass="$WORDPRESS_DB_PASSWORD" \
    --dbhost="$WORDPRESS_DB_HOST" \
    --allow-root

  wp config set WP_DEBUG true --raw --allow-root
  wp config set WP_DEBUG_LOG true --raw --allow-root
  wp config set WP_DEBUG_DISPLAY true --raw --allow-root

fi

# Install WordPress if it is not already installed
if ! wp core is-installed --allow-root; then
  echo "Installing WordPress..."
  wp core install \
    --url="$WORDPRESS_URL" \
    --title="$WORDPRESS_TITLE" \
    --admin_user="$WORDPRESS_ADMIN_USER" \
    --admin_password="$WORDPRESS_ADMIN_PASSWORD" \
    --admin_email="$WORDPRESS_ADMIN_EMAIL" \
    --allow-root
fi

# Update site URL and home options
echo "Updating site URL and home options..."
wp option update siteurl "$WORDPRESS_URL" --allow-root
wp option update home "$WORDPRESS_URL" --allow-root

# Set the permalink structure to "Day and name"
echo "Setting permalink structure to 'Day and name'..."
wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/' --hard --allow-root
wp rewrite flush --hard --allow-root

# --- Plugin Installation Section ---

# Install and activate ACF Pro plugin from zip
if [ -f /tmp/acf-pro.zip ]; then
  echo "Installing ACF Pro plugin..."
  wp plugin install /tmp/acf-pro.zip --activate --allow-root
  echo "Setting ACF Pro license key..."
  wp config set ACF_PRO_LICENSE "'${ACF_PRO_LICENSE}'" --raw --allow-root
else
  echo "ACF Pro zip file not found at /tmp/acf-pro.zip. Skipping ACF Pro installation."
fi

# Other plugins from store
wp plugin install classic-editor post-types-order --activate --allow-root

# Custom plugins
wp plugin activate deployment-plugin --allow-root
wp plugin activate custom-event-id-plugin --allow-root
wp plugin activate custom-file-upload-plugin --allow-root


# Fix file permissions for non-upload directories
echo "Fixing file permissions for non-uploads directories..."
find /var/www/html -mindepth 1 -maxdepth 1 ! -name "wp-content" -exec chown -R www-data:www-data {} +

# Fix file permissions for wp-content (excluding uploads)
echo "Fixing file permissions for wp-content (excluding uploads)..."
find /var/www/html/wp-content -mindepth 1 -maxdepth 1 ! -name "uploads" -exec chown -R www-data:www-data {} +
