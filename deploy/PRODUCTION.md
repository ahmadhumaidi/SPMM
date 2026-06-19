# Production Notes

Current production layout:

- `kampusmedia.cloud` serves the public Kampus Media portal.
- `*.kampusmedia.cloud` serves partner campus landing pages from the same Laravel app.
- `affiliate.kampusmedia.cloud` is handled by Hostinger Website Builder DNS and should not point to this VPS.
- `spmm.maheramedia.com` serves the SPMM admin panel from the same Laravel app.

The app lives at:

```bash
/var/www/spmm
```

The production VPS uses PHP 8.3 and PostgreSQL.

## Safe Deploy

Use the deploy script without installing Nginx:

```bash
cd /var/www/spmm
DOMAIN='kampusmedia.cloud' \
SERVER_NAMES='kampusmedia.cloud www.kampusmedia.cloud *.kampusmedia.cloud' \
REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' \
PHP_VERSION='8.3' \
INSTALL_NGINX='false' \
bash deploy/deploy-app.sh
```

Do not set `INSTALL_NGINX=true` on the current VPS unless you intentionally want to replace the manual multi-domain Nginx configuration.

## Nginx Recovery

If Kampus Media or partner campus subdomains show the default Nginx page, restore:

```bash
cp /var/www/spmm/deploy/nginx-kampusmedia.conf.example /etc/nginx/sites-available/kampusmedia.conf
ln -sf /etc/nginx/sites-available/kampusmedia.conf /etc/nginx/sites-enabled/kampusmedia.conf
nginx -t
systemctl reload nginx
```

If `spmm.maheramedia.com` has redirect loops or stops serving admin, restore:

```bash
cp /var/www/spmm/deploy/nginx-spmm-maheramedia.conf.example /etc/nginx/sites-available/spmm-maheramedia.conf
ln -sf /etc/nginx/sites-available/spmm-maheramedia.conf /etc/nginx/sites-enabled/spmm-maheramedia.conf
nginx -t
systemctl reload nginx
```

Keep old broken configs outside `/etc/nginx/sites-enabled`; Nginx reads every file in that directory.

## After Changes

```bash
cd /var/www/spmm
php artisan optimize:clear
systemctl restart php8.3-fpm
```

## Quick Checks

```bash
curl -Ik https://kampusmedia.cloud
curl -Ik https://uniga.kampusmedia.cloud
curl -Ik https://spmm.maheramedia.com/admin
```

## Manual Backup

Run this before large deploys or payment/email configuration changes:

```bash
cd /var/www/spmm
DB_USER='spmm_user' DB_NAME='spmm' bash deploy/backup-production.sh
```

Backups are stored under:

```bash
/var/backups/spmm
```
