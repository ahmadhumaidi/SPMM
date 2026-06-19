#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/spmm}"
REPO_URL="${REPO_URL:-https://github.com/ahmadhumaidi/SPMM.git}"
BRANCH="${BRANCH:-main}"
DOMAIN="${DOMAIN:-domain-utama-anda.com}"
SERVER_NAMES="${SERVER_NAMES:-${DOMAIN} www.${DOMAIN} *.${DOMAIN}}"
PHP_VERSION="${PHP_VERSION:-8.3}"
INSTALL_NGINX="${INSTALL_NGINX:-false}"
INSTALL_SUPERVISOR="${INSTALL_SUPERVISOR:-true}"

echo "==> Deploying SPMM"
echo "App dir: $APP_DIR"
echo "Repo: $REPO_URL"
echo "Branch: $BRANCH"
echo "Domain: $DOMAIN"
echo "Server names: $SERVER_NAMES"
echo "Install Nginx config: $INSTALL_NGINX"

if [ ! -d "$APP_DIR/.git" ]; then
    echo "==> Cloning repository"
    rm -rf "$APP_DIR"
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
else
    echo "==> Pulling latest code"
    cd "$APP_DIR"
    git fetch origin "$BRANCH"
    git reset --hard "origin/$BRANCH"
fi

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "==> Creating .env from production example"
    cp .env.production.example .env
sed -i "s#https://domain-utama-anda.com#https://${DOMAIN}#g" .env
fi

echo "==> Installing Composer dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "==> Generating application key"
    php artisan key:generate --force
fi

echo "==> Running database migrations"
php artisan migrate --seed --force

echo "==> Preparing storage"
php artisan storage:link || true

echo "==> Caching Laravel"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Fixing permissions"
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

if [ "$INSTALL_NGINX" = "true" ]; then
    echo "==> Installing Nginx config"
    cp "$APP_DIR/deploy/nginx-spmm.conf.example" /etc/nginx/sites-available/spmm
    sed -i "s#__SERVER_NAMES__#${SERVER_NAMES}#g" /etc/nginx/sites-available/spmm
    sed -i "s/domain-utama-anda.com/${DOMAIN}/g" /etc/nginx/sites-available/spmm
    sed -i "s/www.domain-utama-anda.com/www.${DOMAIN}/g" /etc/nginx/sites-available/spmm
    sed -i "s#unix:/var/run/php/php8.3-fpm.sock#unix:/var/run/php/php${PHP_VERSION}-fpm.sock#g" /etc/nginx/sites-available/spmm
    ln -sfn /etc/nginx/sites-available/spmm /etc/nginx/sites-enabled/spmm
    nginx -t
    systemctl reload nginx
else
    echo "==> Skipping Nginx config install"
    echo "    Set INSTALL_NGINX=true only for first-time installs. Production currently uses separate manual configs for kampusmedia.cloud and spmm.maheramedia.com."
fi

if [ "$INSTALL_SUPERVISOR" = "true" ]; then
    echo "==> Installing Supervisor worker"
    mkdir -p /etc/supervisor/conf.d
    cp "$APP_DIR/deploy/supervisor-spmm-worker.conf.example" /etc/supervisor/conf.d/spmm-worker.conf
    supervisorctl reread
    supervisorctl update
    supervisorctl restart spmm-worker:* || supervisorctl start spmm-worker:*
else
    echo "==> Skipping Supervisor worker install"
fi

echo "==> Ensuring scheduler cron exists"
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -l 2>/dev/null | grep -Fv "$APP_DIR" || true; echo "$CRON_LINE") | crontab -

echo "==> Deploy finished"
echo "Open: https://${DOMAIN}"
