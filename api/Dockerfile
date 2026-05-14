# syntax=docker/dockerfile:1.7

# ── Stage 1 — vendor ──
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --no-autoloader

COPY . .

RUN composer dump-autoload \
        --no-dev \
        --optimize \
        --classmap-authoritative

# ── Stage 2 — runtime ──
FROM php:8.3-fpm-alpine AS runtime

RUN set -eux; \
    apk add --no-cache \
        bash \
        icu-libs \
        libpng \
        libzip \
        oniguruma \
        netcat-openbsd \
        tzdata; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        autoconf \
        gcc \
        g++ \
        make \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        opcache \
        gd \
        zip; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/cache/apk/*

COPY <<'EOF' /usr/local/etc/php/conf.d/opcache.ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF

COPY <<'EOF' /usr/local/etc/php/conf.d/app.ini
expose_php=Off
max_execution_time=30
memory_limit=256M
upload_max_filesize=10M
post_max_size=10M
date.timezone=Europe/Budapest
EOF

WORKDIR /var/www

COPY --from=vendor /app /var/www

RUN set -eux; \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache; \
    chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK --interval=30s --timeout=10s --start-period=20s --retries=3 \
    CMD php artisan about > /dev/null 2>&1 || exit 1

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
