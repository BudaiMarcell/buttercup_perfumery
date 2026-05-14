#!/bin/sh
set -e

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"

# /var/www/storage is mounted from the `api-storage` named volume in
# docker-compose.yml. Named volumes only inherit content from the image on
# FIRST creation — if the volume already existed (or if the image's
# storage tree was incomplete), the subdirectories Laravel writes into
# can be missing and we get "Please provide a valid cache path" the
# moment something tries to render a Blade view (e.g. queued mailables).
# Recreating them is idempotent and cheap, so we always do it.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    storage/app/public

echo "[entrypoint] Waiting for ${DB_HOST}:${DB_PORT}..."
attempts=0
until nc -z "${DB_HOST}" "${DB_PORT}" 2>/dev/null; do
    attempts=$((attempts + 1))
    if [ "${attempts}" -ge 60 ]; then
        echo "[entrypoint] FATAL: ${DB_HOST}:${DB_PORT} never became reachable after 60s." >&2
        exit 1
    fi
    sleep 1
done
echo "[entrypoint] ${DB_HOST}:${DB_PORT} is up."

if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    echo "[entrypoint] Caching config + routes..."
    php artisan config:cache
    php artisan route:cache

    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
fi

echo "[entrypoint] Starting: $*"
exec "$@"
