# Script Deploy & Backup SIMS
# Menjalankan Git Commit, Push, dan Zip Backup

$repoUrl = "https://github.com/dewecorp/aplikasi_surat.git"
$backupFile = "backup_project.zip"
$branch = "main"

Write-Host "=== Memulai Proses Deploy & Backup ===" -ForegroundColor Cyan

# 1. Cek & Inisialisasi Git
if (-not (Test-Path ".git")) {
    Write-Host "Inisialisasi repository Git..."
    git init
    git branch -M $branch
}

# 2. Konfigurasi Remote
$currentRemote = git remote get-url origin 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Menambahkan remote origin..."
    git remote add origin $repoUrl
} elseif ($currentRemote -ne $repoUrl) {
    Write-Host "Mengupdate remote origin..."
    git remote set-url origin $repoUrl
}

# 3. Git Add & Commit
Write-Host "Menambahkan file ke staging..."
git add .

$customMsg = Read-Host "Masukkan pesan commit (kosongkan untuk default timestamp)"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

if ([string]::IsNullOrWhiteSpace($customMsg)) {
    $message = "Backup & Update: $timestamp"
} else {
    $message = "$customMsg ($timestamp)"
}

Write-Host "Melakukan commit: $message"
git commit -m "$message"

# 4. Git Push
Write-Host "Mengirim ke GitHub ($repoUrl)..."
git push -u origin $branch

# 5. Buat Zip Backup (Overwrite)
Write-Host "Membuat file backup zip..."
# Ambil semua item kecuali folder .git dan file backup itu sendiri
$items = Get-ChildItem -Path . -Exclude ".git", $backupFile

# Compress-Archive dengan -Force akan menimpa file lama
Compress-Archive -Path $items -DestinationPath $backupFile -Force

Write-Host "=== Selesai! ===" -ForegroundColor Green
Write-Host "Backup tersimpan di: $(Resolve-Path $backupFile)"
