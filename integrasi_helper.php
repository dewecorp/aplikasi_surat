<?php
function sims_ensure_integrasi_schema($conn)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $cols = [];
    $r = @mysqli_query($conn, 'SHOW COLUMNS FROM pengaturan');
    if ($r) {
        while ($x = mysqli_fetch_assoc($r)) {
            $cols[] = $x['Field'];
        }
    }
    $adds = [
        'sims_api_key' => 'VARCHAR(255) NULL DEFAULT NULL',
        'sims_api_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'simad_teachers_api_url' => 'VARCHAR(512) NULL DEFAULT NULL',
        'simad_api_key' => 'VARCHAR(255) NULL DEFAULT NULL',
        'simad_auto_sync_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'simad_auto_sync_interval_minutes' => 'INT UNSIGNED NOT NULL DEFAULT 60',
        'simad_last_auto_sync_at' => 'DATETIME NULL DEFAULT NULL',
    ];
    foreach ($adds as $c => $ddl) {
        if (!in_array($c, $cols, true)) {
            @mysqli_query($conn, 'ALTER TABLE pengaturan ADD COLUMN ' . $c . ' ' . $ddl);
        }
    }
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS api_endpoints (
        id INT(11) NOT NULL AUTO_INCREMENT,
        arah ENUM('masuk','keluar') NOT NULL DEFAULT 'masuk',
        nama VARCHAR(100) NOT NULL,
        url VARCHAR(512) NOT NULL,
        api_key VARCHAR(255) NULL DEFAULT NULL,
        aktif TINYINT(1) NOT NULL DEFAULT 1,
        last_status VARCHAR(20) NULL DEFAULT NULL,
        last_checked DATETIME NULL DEFAULT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS api_log (
        id INT(11) NOT NULL AUTO_INCREMENT,
        endpoint VARCHAR(100) NOT NULL,
        method VARCHAR(10) NOT NULL DEFAULT 'GET',
        ip VARCHAR(64) DEFAULT NULL,
        status_code INT(11) NOT NULL DEFAULT 200,
        message VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        KEY idx_api_log_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $row = @mysqli_fetch_assoc(@mysqli_query($conn, 'SELECT sims_api_key FROM pengaturan LIMIT 1'));
    if ($row && empty($row['sims_api_key'])) {
        $k = bin2hex(random_bytes(24));
        @mysqli_query($conn, "UPDATE pengaturan SET sims_api_key='" . mysqli_real_escape_string($conn, $k) . "'");
    }
}

function sims_current_base_url()
{
    $proto = 'http';
    if ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')) {
        $proto = 'https';
    }
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : 'localhost';
    if ($host === '') {
        $host = 'localhost';
    }
    $dir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname((string)$_SERVER['SCRIPT_NAME'])) : '';
    $dir = trim(rawurldecode($dir), '/');
    if ($dir !== '' && (preg_match('/^[A-Za-z]:/', $dir) || strpos($dir, ':') !== false || stripos($dir, 'laragon/www') !== false)) {
        $dir = '';
    }
    if (substr($dir, -6) === '/api/v1' || $dir === 'api/v1' || substr($dir, -5) === 'api/v1') {
        $dir = preg_replace('#/?api/v1$#', '', $dir);
        $dir = trim((string)$dir, '/');
    }
    $path = ($dir !== '' && $dir !== '.') ? '/' . $dir : '';

    return rtrim($proto . '://' . $host . $path, '/') . '/';
}

function sims_outbound_map($base = null)
{
    if ($base === null) {
        $base = sims_current_base_url();
    }
    $base = rtrim(trim((string)$base), '/') . '/';

    return [
        'surat_masuk' => $base . 'api/v1/surat-masuk',
        'surat_keluar' => $base . 'api/v1/surat-keluar',
        'surat_keputusan' => $base . 'api/v1/surat-keputusan',
    ];
}

function sims_get_integrasi($conn)
{
    sims_ensure_integrasi_schema($conn);
    $row = @mysqli_fetch_assoc(@mysqli_query($conn, 'SELECT sims_api_key, sims_api_enabled, simad_teachers_api_url, simad_api_key, simad_auto_sync_enabled, simad_auto_sync_interval_minutes, simad_last_auto_sync_at FROM pengaturan LIMIT 1'));
    if (!is_array($row)) {
        $row = [];
    }

    return [
        'sims_api_key' => trim((string)($row['sims_api_key'] ?? '')),
        'sims_api_enabled' => (int)($row['sims_api_enabled'] ?? 1) === 1,
        'simad_url' => trim((string)($row['simad_teachers_api_url'] ?? '')),
        'simad_key' => trim((string)($row['simad_api_key'] ?? '')),
        'simad_auto' => (int)($row['simad_auto_sync_enabled'] ?? 1) === 1,
        'simad_interval' => min(10080, max(15, (int)($row['simad_auto_sync_interval_minutes'] ?? 60))),
        'simad_last_auto' => trim((string)($row['simad_last_auto_sync_at'] ?? '')),
    ];
}

function sims_log_api($conn, $endpoint, $code, $msg = null)
{
    $e = mysqli_real_escape_string($conn, substr((string)$endpoint, 0, 100));
    $m = mysqli_real_escape_string($conn, substr((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), 0, 10));
    $ip = mysqli_real_escape_string($conn, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64));
    $ms = mysqli_real_escape_string($conn, substr((string)($msg ?? ''), 0, 255));
    @mysqli_query($conn, "INSERT INTO api_log (endpoint, method, ip, status_code, message) VALUES ('$e','$m','$ip'," . (int)$code . ",'$ms')");
    @mysqli_query($conn, 'DELETE FROM api_log WHERE created_at < NOW() - INTERVAL 30 DAY');
}

function sims_api_client_ip()
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}
