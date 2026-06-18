param(
    [string] $RepoUrl = "https://github.com/ahmadhumaidi/SPMM.git",
    [string] $CommitMessage = "Initial SPMM Laravel MVP"
)

$ErrorActionPreference = "Stop"

Write-Host "SPMM GitHub Publisher" -ForegroundColor Cyan
Write-Host "Project: $PWD"
Write-Host "Repository: $RepoUrl"
Write-Host ""

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Git belum terinstall atau belum terbaca di PATH." -ForegroundColor Red
    Write-Host "Install Git for Windows dulu, lalu buka terminal baru."
    exit 1
}

$projectPath = (Get-Location).Path.Replace('\', '/')
Write-Host "Menandai folder project sebagai safe directory Git..."
git config --global --add safe.directory $projectPath

if (-not (Test-Path ".git")) {
    Write-Host "Membuat repository Git lokal..."
    git init -b main
}

git branch -M main

Write-Host "Mengatur identitas commit..."
git config user.name "ahmadhumaidi"
git config user.email "ahumaidi35@gmail.com"

$remoteExists = git remote 2>$null | Select-String -SimpleMatch "origin"
if ($remoteExists) {
    Write-Host "Mengupdate remote origin..."
    git remote set-url origin $RepoUrl
} else {
    Write-Host "Menambahkan remote origin..."
    git remote add origin $RepoUrl
}

Write-Host "Menambahkan file project..."
git add .

$status = git status --porcelain
if ($status) {
    Write-Host "Membuat commit..."
    git commit -m $CommitMessage
} else {
    Write-Host "Tidak ada perubahan baru untuk dicommit."
}

Write-Host "Mengirim ke GitHub..."
git push -u origin main

Write-Host ""
Write-Host "Selesai. Cek repository GitHub kamu." -ForegroundColor Green
