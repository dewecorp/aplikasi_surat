<?php
/**
 * Update Sistem - Download zip dari GitHub & extract
 * Hanya bisa diakses oleh admin (cek session)
 */
require_once 'session_init.php';

// Cek login & role admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin.']));
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

header('Content-Type: application/json');

$github_repo = 'https://github.com/dewecorp/aplikasi_surat/archive/refs/heads/main.zip';

// 1. Buat folder temp
$temp_dir = __DIR__ . '/temp_update';
$zip_file = $temp_dir . '/update.zip';

try {
    // Bersihkan folder temp lama jika ada
    if (is_dir($temp_dir)) {
        deleteDirectory($temp_dir);
    }
    if (!mkdir($temp_dir, 0755, true)) {
        throw new Exception('Gagal membuat folder sementara.');
    }

    // 2. Download zip dari GitHub
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 120,
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
        throw new Exception('Gagal mengunduh file update dari GitHub. Periksa koneksi internet.');
    }

    if (file_put_contents($zip_file, $zip_content) === false) {
        throw new Exception('Gagal menyimpan file update.');
    }

    // 3. Buka zip
    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) {
        throw new Exception('File zip rusak atau tidak bisa dibuka.');
    }

    // 4. Extract ke folder temp
    $extract_dir = $temp_dir . '/extracted';
    if (!mkdir($extract_dir, 0755, true)) {
        throw new Exception('Gagal membuat folder extract.');
    }

    if ($zip->extractTo($extract_dir) === false) {
        throw new Exception('Gagal mengekstrak file update.');
    }
    $zip->close();

    // 5. Cari folder hasil extract (biasanya ada 1 subfolder: aplikasi_surat-main)
    $extracted_folders = glob($extract_dir . '/*', GLOB_ONLYDIR);
    if (empty($extracted_folders)) {
        throw new Exception('Folder hasil extract kosong.');
    }
    $source_dir = $extracted_folders[0];

    // 6. Salin file dari source ke root aplikasi (timpa file lama)
    $protected_items = ['uploads', 'temp_update', 'config.php', 'config_local.php', 'session_init.php'];
    $success_count = 0;
    $skip_count = 0;

    copyDirectory($source_dir, __DIR__, $protected_items, $success_count, $skip_count);

    // 7. Bersihkan folder temp
    deleteDirectory($temp_dir);

    echo json_encode([
        'success' => true,
        'message' => 'Update berhasil! ' . $success_count . ' file diperbarui, ' . $skip_count . ' file dilewati (dilindungi).'
    ]);

} catch (Exception $e) {
    // Bersihkan folder temp jika gagal
    if (is_dir($temp_dir)) {
        @deleteDirectory($temp_dir);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Gagal update: ' . $e->getMessage()
    ]);
}

/**
 * Salin isi folder ke folder tujuan
 */
function copyDirectory($src, $dst, $protected, &$success_count, &$skip_count) {
    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $src_path = $src . DIRECTORY_SEPARATOR . $item;
        $dst_path = $dst . DIRECTORY_SEPARATOR . $item;

        // Lewati file/folder yang dilindungi
        if (in_array($item, $protected)) {
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

/**
 * Hapus folder beserta isinya
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : @unlink($path);
    }
    @rmdir($dir);
}
