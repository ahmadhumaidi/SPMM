# Setup Repository GitHub SPMM

Panduan ini untuk menjadikan folder lokal SPMM sebagai repository GitHub.

## 1. Buat Repository di GitHub

Di GitHub, buat repository baru:

```text
Repository name: SPMM
Visibility: Private
README: jangan dicentang
.gitignore: jangan dicentang
License: none
```

Gunakan private dulu karena aplikasi masih berisi sistem internal kampus dan CRM.

## 2. Jalankan Perintah dari Folder Project

Buka CMD atau PowerShell, lalu masuk ke folder project:

```powershell
cd "C:\Users\Ahmad Humaidi PC\OneDrive\Documents\SPMM"
```

Cara paling mudah, jalankan script ini:

```powershell
powershell -ExecutionPolicy Bypass -File tools\publish-to-github.ps1
```

Script tersebut akan:

- membuat Git repository lokal jika belum ada,
- mengatur branch `main`,
- mengatur identitas commit,
- menambahkan remote GitHub,
- membuat commit pertama,
- push ke GitHub.

Jika ingin menjalankan manual, gunakan langkah di bawah ini.

Jika folder belum terdeteksi sebagai Git repository:

```powershell
git init -b main
```

Set identitas commit:

```powershell
git config user.name "ahmadhumaidi"
git config user.email "ahumaidi35@gmail.com"
```

Masukkan semua file kode ke commit pertama:

```powershell
git add .
git commit -m "Initial SPMM Laravel MVP"
```

Hubungkan ke GitHub:

```powershell
git remote add origin https://github.com/ahmadhumaidi/SPMM.git
git push -u origin main
```

Jika GitHub meminta login, ikuti instruksi yang muncul di terminal.

## 3. File yang Tidak Ikut GitHub

File berikut memang sengaja tidak dikirim:

```text
.env
vendor/
database/database.sqlite
storage/app/private/
storage/app/public/*
storage/logs/*.log
```

Alasannya:

- `.env` berisi password dan konfigurasi rahasia.
- `vendor/` bisa dibuat ulang dengan Composer.
- `database.sqlite` berisi data lokal.
- `storage/app/public/*` berisi upload lokal seperti dokumen/foto/logo.
- log hanya untuk komputer lokal.

## 4. Setelah Push Berhasil

Di halaman GitHub repository, cek tab:

```text
Actions
```

Workflow `Laravel CI` harus berjalan. Jika hijau, repository sudah sehat untuk mulai dipakai deploy VPS.

## 5. Nanti Saat Deploy ke VPS

Di VPS, project bisa diambil dengan:

```bash
cd /var/www
git clone https://github.com/ahmadhumaidi/SPMM.git spmm
```

Lalu lanjutkan panduan:

```text
docs/deploy-vps.md
```
