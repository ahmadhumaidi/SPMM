# Panduan Install SPMM ke VPS

Panduan ini untuk memasang Sistem Pusat Mahera Media pada VPS Ubuntu dengan Nginx, PHP-FPM, PostgreSQL, queue worker, scheduler, dan SSL.

## 1. Yang Perlu Disiapkan

- VPS Ubuntu 22.04 atau 24.04.
- Domain portal Kampus Media: `kampusmedia.cloud`.
- Subdomain web kampus mitra: `*.kampusmedia.cloud`.
- Domain sistem pusat SPMM, misalnya `spmm.maheramedia.com`.
- Akses SSH ke VPS.
- Database PostgreSQL.
- Email SMTP untuk kirim verifikasi dan password mahasiswa.
- Akun payment gateway dan WhatsApp provider bila sudah siap live.

## 2. Arahkan Domain ke VPS

Di panel DNS domain, buat record:

Untuk `kampusmedia.cloud`:

```text
A     @      IP_VPS_ANDA
A     www    IP_VPS_ANDA
A     *      IP_VPS_ANDA
```

Jika sistem pusat tetap memakai `maheramedia.com`, tambahkan di DNS `maheramedia.com`:

```text
A     spmm   IP_VPS_ANDA
```

Wildcard `*` di `kampusmedia.cloud` berguna agar subdomain kampus seperti `stieindocakti.kampusmedia.cloud` bisa masuk ke aplikasi yang sama.

## 3. Login ke VPS

```bash
ssh root@IP_VPS_ANDA
```

Lalu update server:

```bash
apt update && apt upgrade -y
```

## 4. Install Kebutuhan Server

Cara cepat, setelah file project tersedia di server, gunakan script:

```bash
DB_PASSWORD='ganti_password_database_yang_kuat' bash deploy/bootstrap-ubuntu.sh
```

Atau jalankan manual:

```bash
apt install -y nginx postgresql postgresql-contrib php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl unzip git supervisor cron certbot python3-certbot-nginx
```

Install Composer:

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
```

## 5. Buat Database

Masuk ke PostgreSQL:

```bash
sudo -u postgres psql
```

Jalankan:

```sql
CREATE DATABASE spmm;
CREATE USER spmm_user WITH PASSWORD 'ganti_password_database_yang_kuat';
GRANT ALL PRIVILEGES ON DATABASE spmm TO spmm_user;
\q
```

## 6. Upload Project

Pilihan paling mudah:

1. Zip folder project dari komputer lokal.
2. Upload ke VPS lewat FileZilla/SFTP ke `/var/www`.
3. Extract menjadi `/var/www/spmm`.

Atau jika sudah memakai Git:

```bash
cd /var/www
git clone URL_REPOSITORY_ANDA spmm
```

Pastikan isi foldernya seperti ini:

```text
/var/www/spmm/artisan
/var/www/spmm/app
/var/www/spmm/public
/var/www/spmm/routes
```

## 7. Atur File Environment

Masuk ke folder project:

```bash
cd /var/www/spmm
```

Buat `.env` produksi:

```bash
cp .env.production.example .env
nano .env
```

Ubah bagian penting ini:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kampusmedia.cloud

DB_DATABASE=spmm
DB_USERNAME=spmm_user
DB_PASSWORD=password_database_anda

MAIL_USERNAME=info@maheramedia.com
MAIL_PASSWORD=app_password_email_anda

SUPER_ADMIN_EMAIL=admin@maheramedia.com
SUPER_ADMIN_PASSWORD=password_admin_yang_kuat
```

Untuk tahap live awal, `PAYMENT_PROVIDER=mock` dan `WHATSAPP_PROVIDER=log` masih bisa dipakai untuk uji coba internal. Saat payment gateway dan WhatsApp sudah siap, nilainya diganti sesuai provider yang akan diintegrasikan.

## 8. Install Aplikasi

Cara cepat:

```bash
DOMAIN='kampusmedia.cloud' SERVER_NAMES='kampusmedia.cloud www.kampusmedia.cloud *.kampusmedia.cloud spmm.maheramedia.com' bash deploy/deploy-app.sh
```

Atau jalankan manual:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika `php artisan key:generate` mengubah `APP_KEY`, jangan jalankan lagi setelah aplikasi sudah dipakai live.

## 9. Atur Permission

```bash
chown -R www-data:www-data /var/www/spmm
chmod -R 775 /var/www/spmm/storage /var/www/spmm/bootstrap/cache
```

## 10. Pasang Nginx

Salin contoh konfigurasi:

```bash
cp /var/www/spmm/deploy/nginx-spmm.conf.example /etc/nginx/sites-available/spmm
nano /etc/nginx/sites-available/spmm
```

Ubah:

```text
server_name kampusmedia.cloud www.kampusmedia.cloud *.kampusmedia.cloud spmm.maheramedia.com;
root /var/www/spmm/public;
```

Aktifkan:

```bash
ln -s /etc/nginx/sites-available/spmm /etc/nginx/sites-enabled/spmm
nginx -t
systemctl reload nginx
```

Jika PHP-FPM di VPS memakai versi selain `php8.2-fpm`, sesuaikan socket di file Nginx.

## 11. Aktifkan Queue Worker

```bash
cp /var/www/spmm/deploy/supervisor-spmm-worker.conf.example /etc/supervisor/conf.d/spmm-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start spmm-worker:*
```

Queue worker dipakai untuk proses background seperti email, reminder, dan proses otomatis lain.

## 12. Aktifkan Scheduler

```bash
crontab -e
```

Tambahkan:

```text
* * * * * cd /var/www/spmm && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler dipakai untuk invoice expired, reminder, dan pekerjaan berkala lain.

## 13. Pasang SSL

```bash
certbot --nginx -d kampusmedia.cloud -d www.kampusmedia.cloud -d spmm.maheramedia.com
```

Untuk wildcard subdomain kampus, SSL wildcard biasanya perlu validasi DNS. Itu bisa disiapkan setelah domain utama sudah stabil.

## 14. Cek Setelah Deploy

Buka:

```text
https://kampusmedia.cloud
https://spmm.maheramedia.com/admin
```

Login admin memakai email dan password dari `.env`:

```env
SUPER_ADMIN_EMAIL
SUPER_ADMIN_PASSWORD
```

Checklist uji awal:

- Halaman utama terbuka.
- Admin bisa login.
- Bisa membuat kampus.
- Bisa membuat program studi.
- Bisa submit form pendaftaran.
- Email mahasiswa terkirim.
- Invoice mock muncul.
- Queue worker aktif.
- Scheduler tidak error.
- Upload logo/dokumen masuk ke storage.

## 15. Saat Payment Gateway Sudah Siap

Mekanisme live nanti:

1. Calon mahasiswa submit form.
2. Sistem membuat invoice ke payment gateway.
3. Mahasiswa membayar lewat VA/QRIS/payment link.
4. Payment gateway mengirim webhook ke:

```text
https://kampusmedia.cloud/api/webhooks/payment/NAMA_PROVIDER
```

5. Sistem menandai invoice paid.
6. Status pembayaran mahasiswa ikut berubah.
7. Mahasiswa bisa lanjut biodata/pemberkasan sesuai aturan bisnis.

Sebelum live, webhook harus diuji dari dashboard payment gateway.

## 16. Catatan Domain Terpisah

SPMM, SIAKAD, dan LMS nanti bisa dipisah domain:

```text
spmm.domain-anda.com
siakad.domain-anda.com
lms.domain-anda.com
```

Untuk tahap awal, semuanya masih bisa berjalan dalam satu aplikasi sebagai prototype. Saat sudah stabil, SIAKAD dan LMS dapat dipisah menjadi aplikasi sendiri dan terhubung melalui API.
