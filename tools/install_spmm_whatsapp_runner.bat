@echo off
setlocal
set "RUNNER_DIR=%LOCALAPPDATA%\SPMMWhatsAppRunner"
set "HELPER_URL=https://spmm.maheramedia.com/admin/whatsapp-broadcasts/python-helper/download"

if not exist "%RUNNER_DIR%" mkdir "%RUNNER_DIR%"

echo Mengunduh helper WhatsApp SPMM...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri '%HELPER_URL%' -OutFile '%RUNNER_DIR%\whatsapp_web_auto_sender.py'"
if errorlevel 1 (
    echo Gagal mengunduh helper. Pastikan internet aktif.
    pause
    exit /b 1
)

echo Membuat runner lokal...
(
    echo @echo off
    echo py "%%LOCALAPPDATA%%\SPMMWhatsAppRunner\whatsapp_web_auto_sender.py" "%%~1"
    echo pause
) > "%RUNNER_DIR%\run_spmm_whatsapp.bat"

echo Memasang pyautogui jika belum ada...
py -m pip install pyautogui

echo Mendaftarkan protocol spmm-wa:// ...
reg add "HKCU\Software\Classes\spmm-wa" /ve /d "URL:SPMM WhatsApp Runner" /f
reg add "HKCU\Software\Classes\spmm-wa" /v "URL Protocol" /d "" /f
reg add "HKCU\Software\Classes\spmm-wa\shell\open\command" /ve /d "\"%RUNNER_DIR%\run_spmm_whatsapp.bat\" \"%%1\"" /f

echo.
echo Selesai. Sekarang tombol Jalankan Auto Send Lokal di SPMM bisa dipakai.
pause