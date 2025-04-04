FROM ubuntu:24.04

# Stage 1: Builder
FROM ubuntu:24.04 AS builder

# Install build-time dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    php php-mysql php-xml php-gd wget unzip curl git composer \
    apache2 netcat-openbsd ca-certificates fuse3 libfuse2 && \
    ln -s /usr/bin/fuse3 /usr/bin/fuse && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Ensure /usr/local/bin exists and install WP-CLI
RUN mkdir -p /usr/local/bin && \
    curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x /usr/local/bin/wp

# install WordPress
RUN wp core download --path=/var/www/html --allow-root

# Ensure /etc/fuse.conf exists
RUN touch /etc/fuse.conf && echo "user_allow_other" >> /etc/fuse.conf

# Composer
WORKDIR /var/www/html/wp-content/plugins/deployment-plugin
COPY deployment-plugin/composer.json /var/www/html/wp-content/plugins/deployment-plugin/
RUN composer install --no-dev --prefer-dist --no-interaction
COPY deployment-plugin /var/www/html/wp-content/plugins/deployment-plugin
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/deployment-plugin

WORKDIR /var/www/html/wp-content/plugins/custom-event-id-plugin
COPY custom-event-id-plugin/composer.json /var/www/html/wp-content/plugins/custom-event-id-plugin/
RUN composer install --no-dev --prefer-dist --no-interaction
COPY custom-event-id-plugin /var/www/html/wp-content/plugins/custom-event-id-plugin
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/custom-event-id-plugin

WORKDIR /var/www/html/wp-content/plugins/custom-file-upload-plugin
COPY custom-file-upload-plugin/composer.json /var/www/html/wp-content/plugins/custom-file-upload-plugin/
RUN composer install --no-dev --prefer-dist --no-interaction
COPY custom-file-upload-plugin /var/www/html/wp-content/plugins/custom-file-upload-plugin
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/custom-file-upload-plugin


# Set up file permissions, .htaccess, and S3 mount point
RUN mkdir -p /var/www/html/wp-content/uploads && \
    touch /var/www/html/.htaccess && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html && \
    chown www-data:www-data /var/www/html/.htaccess && chmod 664 /var/www/html/.htaccess && \
    chown -R www-data:www-data /var/www/html/wp-content/uploads && chmod -R 775 /var/www/html/wp-content/uploads

# Copy entrypoint scripts
COPY scripts/ /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

# Stage 2: Runtime
FROM ubuntu:24.04
ENV DEBIAN_FRONTEND=noninteractive

# Install only the required runtime dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    apache2 php php-mysql php-xml php-gd wget unzip curl netcat-openbsd ca-certificates fuse3 libfuse2 && \
    ln -s /usr/bin/fuse3 /usr/bin/fuse && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Mountpoint
# Determine target arch to install correct package
ARG TARGETARCH
RUN if [ "$TARGETARCH" = "amd64" ]; then \
    ARCH="x86_64"; \
    else \
    ARCH="$TARGETARCH"; \
    fi && \
    echo "Building for architecture: $ARCH" && \
    wget --no-check-certificate https://s3.amazonaws.com/mountpoint-s3-release/latest/${ARCH}/mount-s3.deb -O /tmp/mount-s3.deb && \
    apt-get update && apt-get install -y /tmp/mount-s3.deb && \
    rm /tmp/mount-s3.deb

# Copy built files from builder
COPY --from=builder /usr/local/bin/wp /usr/local/bin/wp
COPY --from=builder /var/www/html /var/www/html
COPY --from=builder /etc/fuse.conf /etc/fuse.conf
COPY --from=builder /usr/local/bin/ /usr/local/bin/

RUN rm -f /var/www/html/index.html
COPY acf-pro.zip /tmp/acf-pro.zip
COPY htaccess /var/www/html/.htaccess

RUN PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") && \
    echo "upload_max_filesize = 100M" > /etc/php/${PHP_VERSION}/apache2/conf.d/99-upload.ini && \
    echo "post_max_size = 100M" >> /etc/php/${PHP_VERSION}/apache2/conf.d/99-upload.ini && \
    echo "memory_limit = 1024M" >> /etc/php/${PHP_VERSION}/apache2/conf.d/99-upload.ini

EXPOSE 80

# Use the modular main entrypoint script
ENTRYPOINT ["/usr/local/bin/main-entrypoint.sh"]
