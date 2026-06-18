# Sistem Pusat Mahera Media & CRM

Laravel-ready MVP foundation for lead registration, PMB CRM, payment invoices, pemberkasan, NIM issuance, and active-student export.

## Current State

This workspace contains the first implementation layer:

- Domain migrations.
- Eloquent models and enums.
- Payment gateway abstraction with mock provider.
- WhatsApp abstraction with log provider.
- Public registration, campus listing, student portal, SIAKAD/LMS prototype login pages, and admin dashboard pages.
- Seeder for roles and first Super Admin.
- Technical specification and backlog in `docs/`.

Current local runtime has been verified with PHP 8.2 and Laravel 12.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open the app at:

```text
http://127.0.0.1:8000
http://127.0.0.1:8000/admin
```

## Deploy ke VPS

Panduan pemasangan server ada di:

```text
docs/deploy-vps.md
```

Panduan khusus Hostinger VPS ada di:

```text
docs/hostinger-vps-setup.md
```

Panduan membuat dan menghubungkan repository GitHub ada di:

```text
docs/github-repository-setup.md
```

Shortcut publish ke GitHub dari Windows:

```powershell
powershell -ExecutionPolicy Bypass -File tools\publish-to-github.ps1
```

File pendukung deploy:

```text
.env.production.example
deploy/nginx-spmm.conf.example
deploy/supervisor-spmm-worker.conf.example
```

Gunakan file tersebut saat aplikasi siap dipasang ke domain/VPS produksi.

## First Admin Seeder

Set these values in `.env` before running seeders if needed:

```env
SUPER_ADMIN_NAME="Super Admin"
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PHONE=6280000000000
SUPER_ADMIN_PASSWORD=password
```
