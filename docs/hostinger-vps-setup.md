# Setup Hostinger VPS untuk SPMM

Data VPS awal:

```text
Provider: Hostinger
IP VPS: 187.77.121.111
SSH: ssh root@187.77.121.111
OS: Ubuntu
Repository: git@github.com:ahmadhumaidi/SPMM.git
```

## Rekomendasi Domain

Gunakan subdomain dulu:

```text
spmm.domainanda.com
```

Alasannya:

- lebih aman untuk tahap uji coba,
- domain utama tetap bisa dipakai untuk portal publik nanti,
- SSL dan deploy lebih mudah dipisah,
- SIAKAD dan LMS nanti bisa dibuat:

```text
siakad.domainanda.com
lms.domainanda.com
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
APP_URL=https://spmm.domainanda.com
SANCTUM_STATEFUL_DOMAINS=spmm.domainanda.com
```

## 8. Deploy Aplikasi

Jika belum ada domain:

```bash
DOMAIN='187.77.121.111' REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' bash deploy/deploy-app.sh
```

Jika sudah ada subdomain:

```bash
DOMAIN='spmm.domainanda.com' REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' bash deploy/deploy-app.sh
```

## 9. Buka Website

Sementara via IP:

```text
http://187.77.121.111
http://187.77.121.111/admin
```

Jika sudah pakai subdomain:

```text
https://spmm.domainanda.com
https://spmm.domainanda.com/admin
```

## 10. SSL Setelah Domain Aktif

Jika subdomain sudah mengarah ke IP VPS:

```bash
certbot --nginx -d spmm.domainanda.com
```

## 11. Update Aplikasi Berikutnya

Jika nanti ada update kode:

```bash
cd /var/www/spmm
REPO_URL='git@github.com:ahmadhumaidi/SPMM.git' DOMAIN='spmm.domainanda.com' bash deploy/deploy-app.sh
```
