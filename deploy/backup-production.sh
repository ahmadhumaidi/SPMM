#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/spmm}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/spmm}"
DB_NAME="${DB_NAME:-spmm}"
DB_USER="${DB_USER:-spmm_user}"
STAMP="$(date +%Y%m%d-%H%M%S)"
TARGET_DIR="${BACKUP_DIR}/${STAMP}"

mkdir -p "$TARGET_DIR"

echo "==> Backing up PostgreSQL database"
pg_dump -U "$DB_USER" "$DB_NAME" > "${TARGET_DIR}/database.sql"

echo "==> Backing up storage uploads"
tar -czf "${TARGET_DIR}/storage-public.tar.gz" -C "$APP_DIR" storage/app/public

echo "==> Backing up environment and Nginx config"
cp "$APP_DIR/.env" "${TARGET_DIR}/env.backup"
tar -czf "${TARGET_DIR}/nginx-sites.tar.gz" -C /etc/nginx sites-available sites-enabled

chmod 600 "${TARGET_DIR}/env.backup"

echo "==> Backup finished: ${TARGET_DIR}"
