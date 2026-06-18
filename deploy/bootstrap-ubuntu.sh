#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/spmm}"
DB_NAME="${DB_NAME:-spmm}"
DB_USER="${DB_USER:-spmm_user}"
DB_PASSWORD="${DB_PASSWORD:-change_this_database_password}"
PHP_VERSION="${PHP_VERSION:-8.2}"

echo "==> Updating Ubuntu packages"
apt update
apt upgrade -y

echo "==> Installing server packages"
apt install -y \
    nginx \
    postgresql postgresql-contrib \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-pgsql" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    unzip git supervisor cron certbot python3-certbot-nginx

if ! command -v composer >/dev/null 2>&1; then
    echo "==> Installing Composer"
    cd /tmp
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
fi

echo "==> Creating PostgreSQL database and user"
sudo -u postgres psql <<SQL
DO
\$\$
BEGIN
   IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}') THEN
      CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}';
   END IF;
END
\$\$;

SELECT 'CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${DB_NAME}')\gexec
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
SQL

echo "==> Preparing application directory"
mkdir -p "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"

echo "==> Server bootstrap finished"
echo "Next: clone the repository into $APP_DIR and run deploy/deploy-app.sh"
