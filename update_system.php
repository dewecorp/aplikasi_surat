<?php
/**
 * Update Sistem - Download zip dari GitHub & extract
 * Hanya bisa diakses oleh admin (cek session)
 */
require_once 'session_init.php';

// Matikan output error agar tidak mengganggu JSON
error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
// Naikkan batas waktu & memori untuk proses download/extract
@set_time_limit(0);
@ini_set('memory_limit', '512M');

// Tangkap semua output tak terduga
ob_start();

header('Content-Type: application/json; charset=utf-8');

function respond($success, $message, $extra = []) {
    // Buang output sampah apapun sebelum JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function log_update($msg) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    @file_put_contents(__DIR__ . '/update_log.txt', $log, FILE_APPEND);
}

// Cek login & role admin
if (!isset($_SESSION['user_id'])) {
    respond(false, 'Unauthorized');
}
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    respond(false, 'Akses ditolak. Hanya admin.');
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed');
}

log_update('Mulai update sistem oleh user ID: ' . ($_SESSION['user_id'] ?? '?'));

// Cek ekstensi yang dibutuhkan
if (!class_exists('ZipArchive')) {
    log_update('GAGAL: Ekstensi ZipArchive tidak tersedia di server.');
    respond(false, 'Server tidak mendukung ZipArchive. Hubungi hosting untuk mengaktifkan ekstensi zip.');
}

$github_repo = 'https://github.com/dewecorp/aplikasi_surat/archive/refs/heads/main.zip';
$temp_dir = __DIR__ . '/temp_update';
$zip_file = $temp_dir . '/update.zip';

try {
    // Bersihkan folder temp lama
    if (is_dir($temp_dir)) {
        deleteDirectory($temp_dir);
    }
    if (!mkdir($temp_dir, 0755, true)) {
        throw new Exception('Gagal membuat folder sementara. Periksa permission folder.');
    }

    // ===== Download dengan cURL (utama) atau file_get_contents (fallback) =====
    $zip_content = false;

    if (function_exists('curl_init')) {
        log_update('Mengunduh via cURL: ' . $github_repo);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $github_repo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'SIMS-Updater/1.0',
        ]);
        $zip_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($zip_content === false || $http_code >= 400) {
            log_update('cURL gagal (HTTP ' . $http_code . '): ' . $curl_err);
            $zip_content = false;
        } else {
            log_update('cURL sukses, ukuran: ' . strlen($zip_content) . ' bytes');
        }
    }

    // Fallback ke file_get_contents jika cURL tidak ada/gagal
    if ($zip_content === false) {
        if ((bool) ini_get('allow_url_fopen')) {
            log_update('Mengunduh via file_get_contents (allow_url_fopen aktif)');
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 300,
                    'follow_location' => true,
                    'user_agent' => 'SIMS-Updater/1.0'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            $zip_content = @file_get_contents($github_repo, false, $ctx);
            if ($zip_content === false) {
                log_update('file_get_contents gagal.');
            }
        } else {
            log_update('allow_url_fopen dimatikan & cURL gagal.');
        }
    }

    if ($zip_content === false || empty($zip_content)) {
        throw new Exception('Gagal mengunduh file update. Hosting mungkin memblokir akses keluar ke github.com. Pastikan cURL aktif atau allow_url_fopen = On.');
    }

    if (file_put_contents($zip_file, $zip_content) === false) {
        throw new Exception('Gagal menyimpan file update ke disk. Periksa permission folder.');
    }

    // ===== Buka & extract zip =====
    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) {
        throw new Exception('File zip rusak atau tidak bisa dibuka.');
    }

    $extract_dir = $temp_dir . '/extracted';
    if (!mkdir($extract_dir, 0755, true)) {
        $zip->close();
        throw new Exception('Gagal membuat folder extract.');
    }

    if ($zip->extractTo($extract_dir) === false) {
        $zip->close();
        throw new Exception('Gagal mengekstrak file update.');
    }
    $zip->close();
    log_update('Extract zip selesai.');

    // Cari folder hasil extract (aplikasi_surat-main)
    $extracted_folders = glob($extract_dir . '/*', GLOB_ONLYDIR);
    if (empty($extracted_folders)) {
        throw new Exception('Folder hasil extract kosong.');
    }
    $source_dir = $extracted_folders[0];

    // Salin file ke root aplikasi, kecuali yang dilindungi
    $protected_items = ['uploads', 'temp_update', 'config.php', 'config_local.php', 'session_init.php', 'update_log.txt'];
    $success_count = 0;
    $skip_count = 0;

    copyDirectory($source_dir, __DIR__, $protected_items, $success_count, $skip_count);
    log_update('Copy selesai. ' . $success_count . ' file diupdate, ' . $skip_count . ' dilewati.');

    // Bersihkan temp
    deleteDirectory($temp_dir);

    respond(true, 'Update berhasil! ' . $success_count . ' file diperbarui, ' . $skip_count . ' file dilindungi dilewati.');

} catch (Throwable $e) {
    log_update('EXCEPTION: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (is_dir($temp_dir)) {
        @deleteDirectory($temp_dir);
    }
    respond(false, 'Gagal update: ' . $e->getMessage());
}

// ===== Fungsi helper =====
function copyDirectory($src, $dst, $protected, &$success_count, &$skip_count) {
    $items = @scandir($src);
    if ($items === false) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $src_path = $src . DIRECTORY_SEPARATOR . $item;
        $dst_path = $dst . DIRECTORY_SEPARATOR . $item;

        if (in_array($item, $protected, true)) {
            $skip_count++;
            continue;
        }

        if (is_dir($src_path)) {
            if (!is_dir($dst_path)) {
                @mkdir($dst_path, 0755, true);
            }
            copyDirectory($src_path, $dst_path, $protected, $success_count, $skip_count);
        } else {
            @copy($src_path, $dst_path);
            $success_count++;
        }
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $items = @scandir($dir);
    if ($items === false) { @rmdir($dir); return; }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : @unlink($path);
    }
    @rmdir($dir);
}
