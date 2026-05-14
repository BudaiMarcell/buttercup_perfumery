#!/bin/sh
set -e

CONFIG_PATH=/usr/share/nginx/html/config.js

: "${API_URL:=http://localhost:8000/api}"
: "${SENTRY_DSN:=}"
: "${TRACKING_KEY:=}"

cat > "${CONFIG_PATH}" <<EOF
window.__CONFIG__ = {
  API_URL: "${API_URL}",
  SENTRY_DSN: "${SENTRY_DSN}",
  TRACKING_KEY: "${TRACKING_KEY}"
};
EOF

echo "[entrypoint] Wrote runtime config to ${CONFIG_PATH}: API_URL=${API_URL}"
