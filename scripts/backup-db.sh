#!/usr/bin/env bash
#
# Nightly MySQL backup for the Buttercup stack.
# ─────────────────────────────────────────────────────────────────────
# Designed to be safe to drop on a Hetzner Ubuntu host and forget about.
# Run it from cron — it dumps the `mysql` container's database, gzips
# the result into /var/backups/buttercup, and keeps the last N days.
#
# It is intentionally NOT a one-liner — having explicit, named steps
# matters more than terseness when you wake up at 2am to a backup
# alarm and need to read this script under stress.
#
# Usage:
#   ./backup-db.sh              # uses defaults
#   ./backup-db.sh /tmp 14      # different dir, keep 14 days
#
# Recommended cron entry (as the deploy user, not root):
#   30 3 * * * /home/marci/buttercup/beadandók/scripts/backup-db.sh \
#       >> /var/log/buttercup-backup.log 2>&1
#
# Off-site copy (optional, separate step — keep this script focused):
#   Install rclone, configure a Backblaze B2 / S3 remote, then add a
#   second cron at 3:45 that does:
#       rclone copy /var/backups/buttercup b2:buttercup-backups \
#                   --max-age 25h --transfers 2
# ─────────────────────────────────────────────────────────────────────

set -euo pipefail

# ── Configuration ─────────────────────────────────────────────────────
BACKUP_DIR="${1:-/var/backups/buttercup}"
KEEP_DAYS="${2:-7}"

# Docker compose project name (folder name by default). If your project
# was started with `docker compose -p somename up`, set PROJECT_NAME.
PROJECT_NAME="${PROJECT_NAME:-beadandók}"
MYSQL_SERVICE="${MYSQL_SERVICE:-mysql}"

# ── Resolve the .env so we can read MYSQL_DATABASE / MYSQL_ROOT_PASSWORD
# Looks for .env in the parent directory of this script (the repo root),
# then in the current working directory as a fallback.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE=""
for candidate in "${SCRIPT_DIR}/../.env" "./.env"; do
    if [ -f "${candidate}" ]; then
        ENV_FILE="${candidate}"
        break
    fi
done
if [ -z "${ENV_FILE}" ]; then
    echo "[backup] FATAL: could not find a .env file (looked in script dir and CWD)" >&2
    exit 2
fi

# shellcheck disable=SC1090
set -a
. "${ENV_FILE}"
set +a

: "${MYSQL_DATABASE:?MYSQL_DATABASE is unset in .env}"
: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is unset in .env}"

# ── Prep ──────────────────────────────────────────────────────────────
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OUTPUT_FILE="${BACKUP_DIR}/buttercup-${TIMESTAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

echo "[backup] $(date -Iseconds) starting dump of ${MYSQL_DATABASE}"

# ── Dump ──────────────────────────────────────────────────────────────
# mysqldump runs INSIDE the mysql container so we don't need a mysql
# client on the host. --single-transaction makes the dump consistent
# without locking InnoDB tables for the duration of the backup.
# --no-tablespaces silences a warning on MySQL 8 about a privilege we
# don't have and don't need.
docker compose -p "${PROJECT_NAME}" exec -T "${MYSQL_SERVICE}" \
    mysqldump \
        -u root \
        -p"${MYSQL_ROOT_PASSWORD}" \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        --no-tablespaces \
        --default-character-set=utf8mb4 \
        "${MYSQL_DATABASE}" \
    | gzip -9 > "${OUTPUT_FILE}"

SIZE_HUMAN="$(du -h "${OUTPUT_FILE}" | cut -f1)"
echo "[backup] $(date -Iseconds) wrote ${OUTPUT_FILE} (${SIZE_HUMAN})"

# Sanity: refuse to keep a backup smaller than 1 KB — anything that
# tiny is almost certainly an error message captured as if it were data.
ACTUAL_BYTES="$(stat -c%s "${OUTPUT_FILE}" 2>/dev/null || stat -f%z "${OUTPUT_FILE}")"
if [ "${ACTUAL_BYTES}" -lt 1024 ]; then
    echo "[backup] FATAL: dump file is suspiciously small (${ACTUAL_BYTES} bytes), removing" >&2
    rm -f "${OUTPUT_FILE}"
    exit 3
fi

# ── Rotation ──────────────────────────────────────────────────────────
# Use atime/mtime, not ctime — file rename should not reset retention.
echo "[backup] pruning files older than ${KEEP_DAYS} days"
find "${BACKUP_DIR}" -maxdepth 1 -type f -name 'buttercup-*.sql.gz' \
    -mtime "+${KEEP_DAYS}" -print -delete

echo "[backup] $(date -Iseconds) done"
