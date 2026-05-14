# syntax=docker/dockerfile:1.7

# ── Stage 1 — build ──
FROM node:22-alpine AS build

WORKDIR /app

COPY package.json package-lock.json* ./

# Use `npm install` (not `npm ci`) because we deliberately don't ship
# a frozen lock file — the `sass` dev dep and the dropped
# `@popperjs/core` would force a manual local re-lock otherwise.
# Slightly slower first build, but each rebuild is still fast thanks
# to Docker's layer cache on this RUN.
RUN npm install --no-audit --no-fund --prefer-offline

COPY . .

RUN npm run build

# ── Stage 2 — runtime ──
FROM nginx:1.27-alpine AS runtime

RUN rm -f /etc/nginx/conf.d/default.conf

COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/nginx.conf /etc/nginx/conf.d/buttercup.conf
COPY docker/docker-entrypoint.sh /docker-entrypoint.d/40-write-runtime-config.sh

RUN chmod +x /docker-entrypoint.d/40-write-runtime-config.sh

EXPOSE 80
