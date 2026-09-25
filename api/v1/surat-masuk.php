<?php
require_once __DIR__ . '/../../session_init.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../integrasi_helper.php';

header('Content-Type: application/json; charset=UTF-8');

function sims_api_out_json($code, $payload)
{
    global $conn;
    http_response_code($code);
    sims_log_api($conn, 'surat-masuk', $code, $payload['message'] ?? null);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$gate = sims_api_gate($conn, 'surat-masuk');
if (!$gate['ok']) {
    sims_api_out_json($gate['code'], ['status' => 'error', 'message' => $gate['message']]);
}

$where = [];
$updated_since = isset($_GET['updated_since']) ? trim((string)$_GET['updated_since']) : '';
if ($updated_since !== '') {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $updated_since);
    if ($dt === false || $dt->format('Y-m-d H:i:s') !== $updated_since) {
        sims_api_out_json(400, ['status' => 'error', 'message' => 'Format updated_since harus Y-m-d H:i:s.']);
    }
    $where[] = "updated_at >= '" . mysqli_real_escape_string($conn, $updated_since) . "'";
}
if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, trim((string)$_GET['search']));
    $where[] = "(no_surat LIKE '%$s%' OR perihal LIKE '%$s%' OR pengirim LIKE '%$s%')";
}
$limit = isset($_GET['limit']) ? max(0, min(1000, (int)$_GET['limit'])) : 0;

$has_updated = false;
$rc = @mysqli_query($conn, "SHOW COLUMNS FROM surat_masuk LIKE 'updated_at'");
if ($rc && mysqli_num_rows($rc) > 0) {
    $has_updated = true;
} else {
    $where = array_values(array_filter($where, static function ($w) {
        return stripos($w, 'updated_at') === false;
    }));
}
$sql = 'SELECT id, tgl_terima, no_surat, tgl_surat, perihal, pengirim, file, created_at FROM surat_masuk';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}
$q = @mysqli_query($conn, $sql);
if (!$q) {
    sims_api_out_json(500, ['status' => 'error', 'message' => 'Query gagal.']);
}
$base = sims_current_base_url();
$rows = [];
while ($r = mysqli_fetch_assoc($q)) {
    if (!empty($r['file'])) {
        $r['file_url'] = $base . 'uploads/' . ltrim((string)$r['file'], '/');
    } else {
        $r['file_url'] = null;
    }
    $rows[] = $r;
}
$mode = 'full';
if ($updated_since !== '' && $has_updated) {
    $mode = 'incremental';
}
sims_api_out_json(200, [
    'status' => 'success',
    'sync_mode' => $mode,
    'total_data' => count($rows),
    'last_sync' => date('Y-m-d H:i:s'),
    'data' => $rows,
]);
