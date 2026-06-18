# Setup Hostinger VPS untuk SPMM

Data VPS awal:

```text
Provider: Hostinger
IP VPS: 187.77.121.111
SSH: ssh root@187.77.121.111
OS: Ubuntu
Repository: git@github.com:ahmadhumaidi/SPMM.git
```

## Pengaturan Domain

Domain untuk website Kampus Media:

```text
kampusmedia.cloud
```

Subdomain untuk web kampus mitra:

```text
stieindocakti.kampusmedia.cloud
unigajayana.kampusmedia.cloud
stiepemuda.kampusmedia.cloud
```

Sistem pusat SPMM bisa tetap memakai domain Mahera Media:

```text
spmm.maheramedia.com
```

SIAKAD dan LMS nanti bisa dibuat:

```text
siakad.maheramedia.com
lms.maheramedia.com
```

Jika belum ada domain, aplikasi tetap bisa diuji sementara lewat IP:

```text
http://187.77.121.111
```

Tetapi untuk SSL dan payment gateway/webhook, domain tetap dibutuhkan.

## 1. Login ke VPS

Jalankan dari CMD/PowerShell:

```powershell
ssh root@187.77.121.111
```

Masukkan password root dari panel Hostinger.

## 2. Buat SSH Key di VPS untuk GitHub

Karena repository `SPMM` private, VPS perlu izin baca repository.

Di dalam VPS, jalankan:

```bash
ssh-keygen -t ed25519 -C "hostinger-spmm" -f ~/.ssh/spmm_github -N ""
cat ~/.ssh/spmm_github.pub
```

Copy hasil public key yang muncul.

## 3. Tambahkan Deploy Key ke GitHub

Buka GitHub repository:

```text
https://github.com/ahmadhumaidi/SPMM
```

Masuk ke:

```text
Settings > Deploy keys > Add deploy key
```

Isi:

```text
Title: Hostinger VPS SPMM
Key: paste public key dari VPS
Allow write access: jangan dicentang
```

Klik **Add key**.

## 4. Atur SSH Config di VPS

Di dalam VPS, jalankan:

```bash
cat > ~/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/spmm_github
    IdentitiesOnly yes
EOF

chmod 600 ~/.ssh/config
ssh -T git@github.com
```

Jika muncul pesan berhasil autentikasi GitHub, lanjut.

## 5. Clone Repository

```bash
cd /var/www
git clone git@github.com:ahmadhumaidi/SPMM.git spmm
cd /var/www/spmm
```

## 6. Jalankan Bootstrap Server

Ganti password database dengan password kuat.

```bash
DB_PASSWORD='ganti_password_database_yang_kuat' bash deploy/bootstrap-ubuntu.sh
```

## 7. Atur .env Produksi

```bash
cd /var/www/spmm
cp .env.production.example .env
nano .env
```

Untuk sementara jika belum ada domain:

```env
APP_URL=http://187.77.121.111
APP_DEBUG=false
DB_PASSWORD=ganti_password_database_yang_kuat
SUPER_ADMIN_EMAIL=admin@spmm.local
SUPER_ADMIN_PASSWORD=ganti_password_admin_yang_kuat
```

Jika sudah ada subdomain:

```env
APP_URL=https://kampusmedia.cloud
SANCTUM_STATEFUL_DOMAINS=kampusmedia.cloud,www.kampusmedia.cloud,spmm.maheramedia.com
```

## 8. Deploy Aplikasi

Jika belum ada domain:

```bash
DOMAIN='187.77.121.111' REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' bash deploy/deploy-app.sh
```

Jika sudah ada subdomain:

```bash
DOMAIN='kampusmedia.cloud' SERVER_NAMES='kampusmedia.cloud www.kampusmedia.cloud *.kampusmedia.cloud spmm.maheramedia.com' REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' bash deploy/deploy-app.sh
```

## 9. Buka Website

Sementara via IP:

```text
http://187.77.121.111
http://187.77.121.111/admin
```

Jika sudah pakai subdomain:

```text
https://kampusmedia.cloud
https://stieindocakti.kampusmedia.cloud
https://spmm.maheramedia.com/admin
```

## 10. SSL Setelah Domain Aktif

Jika subdomain sudah mengarah ke IP VPS:

```bash
certbot --nginx -d kampusmedia.cloud -d www.kampusmedia.cloud -d spmm.maheramedia.com
```

## 11. Update Aplikasi Berikutnya

Jika nanti ada update kode:

```bash
cd /var/www/spmm
REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' DOMAIN='kampusmedia.cloud' SERVER_NAMES='kampusmedia.cloud www.kampusmedia.cloud *.kampusmedia.cloud spmm.maheramedia.com' bash deploy/deploy-app.sh
```
