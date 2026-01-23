@echo off
set "REPO_URL=https://github.com/dewecorp/aplikasi_surat.git"
set "BACKUP_FILE=backup_project.zip"
set "BRANCH=main"

echo === Memulai Proses Deploy & Backup ===

:: 1. Cek & Inisialisasi Git
if not exist .git (
    echo Inisialisasi repository Git...
    git init
    git branch -M %BRANCH%
)

:: 2. Konfigurasi Remote
git remote get-url origin >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Menambahkan remote origin...
    git remote add origin %REPO_URL%
) else (
    echo Mengupdate remote origin...
    git remote set-url origin %REPO_URL%
)

:: 3. Git Add & Commit
echo Menambahkan file ke staging...
git add .

set /p CUSTOM_MSG="Masukkan pesan commit (kosongkan untuk default timestamp): "

:: Ambil timestamp menggunakan PowerShell agar format konsisten
for /f "usebackq delims=" %%a in (`powershell -Command "Get-Date -Format 'yyyy-MM-dd HH:mm:ss'"`) do set TIMESTAMP=%%a

if "%CUSTOM_MSG%"=="" (
    set "MESSAGE=Backup & Update: %TIMESTAMP%"
) else (
    set "MESSAGE=%CUSTOM_MSG% (%TIMESTAMP%)"
)

echo Melakukan commit: %MESSAGE%
git commit -m "%MESSAGE%"

:: 4. Git Push
echo Mengirim ke GitHub (%REPO_URL%)...
git push -u origin %BRANCH%

:: 5. Buat Zip Backup (Overwrite) menggunakan PowerShell
echo Membuat file backup zip...
powershell -Command "Compress-Archive -Path (Get-ChildItem -Path . -Exclude '.git', '%BACKUP_FILE%') -DestinationPath '%BACKUP_FILE%' -Force"

echo === Selesai! ===
echo Backup tersimpan di: %CD%\%BACKUP_FILE%
pause
