<?php
require_once 'session_init.php';
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';
require_once 'integrasi_helper.php';

if (strtolower(trim($_SESSION['role'] ?? '')) != 'admin') {
    echo "<script>window.location=" . json_encode($base_url) . ";</script>";
    exit();
}

sims_ensure_integrasi_schema($conn);
$cfg = sims_get_integrasi($conn);
$outbound = sims_outbound_map();
$msg_ok = '';
$msg_err = '';

function integrasi_test_url($url, $key)
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'msg' => 'cURL tidak tersedia.'];
    }
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    $ch = curl_init($url . $sep . 'limit=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-API-KEY: ' . $key],
    ]);
    $body = curl_exec($ch);
    $cerr = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return ['ok' => false, 'msg' => 'Koneksi gagal: ' . $cerr];
    }
    $d = json_decode(trim($body), true);
    if (!is_array($d)) {
        return ['ok' => false, 'msg' => 'Bukan JSON (HTTP ' . $code . ').'];
    }
    if (($d['status'] ?? '') === 'success') {
        return ['ok' => true, 'msg' => 'OK (HTTP ' . $code . ', total: ' . (int)($d['total_data'] ?? 0) . ').'];
    }

    return ['ok' => false, 'msg' => 'Error: ' . ($d['message'] ?? 'unknown') . ' (HTTP ' . $code . ').'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF Token Verification Failed');
    }
    $aksi = (string)$_POST['aksi'];
    if ($aksi === 'save_inbound') {
        $url = trim((string)($_POST['simad_url'] ?? ''));
        $key = trim((string)($_POST['simad_key'] ?? ''));
        $auto = isset($_POST['simad_auto']) ? 1 : 0;
        $intv = min(10080, max(15, (int)($_POST['simad_interval'] ?? 60)));
        if ($url !== '' && !preg_match('#^https?://[^\s]+$#', $url)) {
            $msg_err = 'URL endpoint masuk tidak valid.';
        } else {
            $u = mysqli_real_escape_string($conn, $url);
            $k = mysqli_real_escape_string($conn, $key);
            if (mysqli_query($conn, "UPDATE pengaturan SET simad_teachers_api_url='$u', simad_api_key='$k', simad_auto_sync_enabled=$auto, simad_auto_sync_interval_minutes=$intv")) {
                $msg_ok = 'Endpoint masuk tersimpan. Domain baru langsung dipakai tanpa bongkar backend.';
                log_activity((int)$_SESSION['user_id'], 'integrasi', 'Ubah endpoint masuk SIMAD.');
            } else {
                $msg_err = 'Gagal simpan: ' . mysqli_error($conn);
            }
        }
    } elseif ($aksi === 'save_outbound') {
        $en = isset($_POST['sims_api_enabled']) ? 1 : 0;
        $reqhttps = isset($_POST['sims_require_https']) ? 1 : 0;
        $key = trim((string)($_POST['sims_api_key'] ?? ''));
        $ips = trim((string)($_POST['sims_allowed_ips'] ?? ''));
        if ($key === '') {
            $msg_err = 'API key keluar tidak boleh kosong. Gunakan Generate.';
        } else {
            sims_ensure_integrasi_schema($conn);
            $k = mysqli_real_escape_string($conn, $key);
            $ipse = mysqli_real_escape_string($conn, $ips);
            if (mysqli_query($conn, "UPDATE pengaturan SET sims_api_key='$k', sims_api_enabled=$en, sims_api_allowed_ips='$ipse', sims_api_require_https=$reqhttps")) {
                $msg_ok = 'Endpoint keluar tersimpan. Copy URL + key ke web lain.';
                log_activity((int)$_SESSION['user_id'], 'integrasi', 'Ubah API keluar SIMS (key/IP/HTTPS).');
            } else {
                $msg_err = 'Gagal simpan: ' . mysqli_error($conn);
            }
        }
    } elseif ($aksi === 'regen_key') {
        $k = bin2hex(random_bytes(24));
        $ke = mysqli_real_escape_string($conn, $k);
        if (mysqli_query($conn, "UPDATE pengaturan SET sims_api_key='$ke'")) {
            $msg_ok = 'API key baru dibuat. Update key di semua web lain.';
            log_activity((int)$_SESSION['user_id'], 'integrasi', 'Regenerate API key keluar.');
        } else {
            $msg_err = 'Gagal generate key.';
        }
    } elseif ($aksi === 'add_endpoint') {
        $arah = (($_POST['arah'] ?? 'masuk') === 'keluar') ? 'keluar' : 'masuk';
        $nama = trim((string)($_POST['nama'] ?? ''));
        $url = trim((string)($_POST['url'] ?? ''));
        $key = trim((string)($_POST['api_key'] ?? ''));
        if ($nama === '' || $url === '' || !preg_match('#^https?://[^\s]+$#', $url)) {
            $msg_err = 'Nama dan URL valid wajib diisi.';
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO api_endpoints (arah, nama, url, api_key, aktif) VALUES (?,?,?,?,1)');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssss', $arah, $nama, $url, $key);
                if (mysqli_stmt_execute($stmt)) {
                    $msg_ok = 'Endpoint "' . htmlspecialchars($nama) . '" tercatat.';
                } else {
                    $msg_err = 'Gagal tambah endpoint.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($aksi === 'del_endpoint') {
        $id = (int)($_POST['id'] ?? 0);
        @mysqli_query($conn, 'DELETE FROM api_endpoints WHERE id=' . $id);
        $msg_ok = 'Endpoint dihapus.';
    } elseif ($aksi === 'toggle_endpoint') {
        $id = (int)($_POST['id'] ?? 0);
        @mysqli_query($conn, 'UPDATE api_endpoints SET aktif = 1 - aktif WHERE id=' . $id);
        $msg_ok = 'Status endpoint diubah.';
    } elseif ($aksi === 'sync_now') {
        require_once 'sync_guru_simad.php';
        $sc = simad_config();
        $res = simad_run_teacher_merge($conn, $sc['api_urls'], $sc['api_key'], __DIR__, [
            'use_incremental_sync' => $sc['use_incremental_sync'],
            'hub_fetch_limit' => $sc['hub_fetch_limit'],
        ]);
        if ($res['ok']) {
            simad_touch_last_success(__DIR__, $res['hub_last_sync'] ?? null);
            @mysqli_query($conn, "UPDATE pengaturan SET simad_last_auto_sync_at=NOW()");
            log_activity((int)$_SESSION['user_id'], 'simad_sync', 'Sinkron manual dari Integrasi: ' . (int)$res['inserted'] . ' baru, ' . (int)$res['updated'] . ' update.');
            $msg_ok = 'Sinkron selesai. Baru: ' . (int)$res['inserted'] . ', Update: ' . (int)$res['updated'] . ', Lewat: ' . (int)$res['skipped'] . '.';
        } else {
            $msg_err = 'Sinkron gagal: ' . ($res['error'] ?? 'unknown');
        }
    }
    $cfg = sims_get_integrasi($conn);
    $outbound = sims_outbound_map();
}

if (isset($_GET['test']) && isset($_GET['csrf_token']) && verify_csrf_token($_GET['csrf_token'])) {
    $tid = (int)$_GET['test'];
    $er = @mysqli_fetch_assoc(@mysqli_query($conn, 'SELECT * FROM api_endpoints WHERE id=' . $tid . ' LIMIT 1'));
    if ($er) {
        $t = integrasi_test_url($er['url'], (string)($er['api_key'] ?? ''));
        $st = $t['ok'] ? 'ok' : 'gagal';
        $ste = mysqli_real_escape_string($conn, $st);
        @mysqli_query($conn, "UPDATE api_endpoints SET last_status='$ste', last_checked=NOW() WHERE id=" . $tid);
        if ($t['ok']) {
            $msg_ok = 'Tes "' . htmlspecialchars($er['nama']) . '": ' . htmlspecialchars($t['msg']);
        } else {
            $msg_err = 'Tes "' . htmlspecialchars($er['nama']) . '": ' . htmlspecialchars($t['msg']);
        }
    }
}

if (isset($_GET['test_inbound']) && isset($_GET['csrf_token']) && verify_csrf_token($_GET['csrf_token'])) {
    if ($cfg['simad_url'] === '') {
        $msg_err = 'URL endpoint masuk masih kosong.';
    } else {
        $t = integrasi_test_url($cfg['simad_url'], $cfg['simad_key']);
        if ($t['ok']) {
            $msg_ok = 'Endpoint masuk OK: ' . htmlspecialchars($t['msg']);
        } else {
            $msg_err = 'Endpoint masuk gagal: ' . htmlspecialchars($t['msg']);
        }
    }
}

$endpoints = [];
$qe = @mysqli_query($conn, 'SELECT * FROM api_endpoints ORDER BY arah, id DESC');
if ($qe) {
    while ($r = mysqli_fetch_assoc($qe)) {
        $endpoints[] = $r;
    }
}
$logs = [];
$ql = @mysqli_query($conn, 'SELECT endpoint, method, ip, status_code, message, created_at FROM api_log ORDER BY id DESC LIMIT 20');
if ($ql) {
    while ($r = mysqli_fetch_assoc($ql)) {
        $logs[] = $r;
    }
}
$csrf = generate_csrf_token();
?>

<div class="container-fluid px-4">
    <div class="block-header"><h2>Integrasi API</h2><p class="text-muted">Endpoint masuk (tarik dari web lain, mis. SIMAD guru) dan endpoint keluar (ditarik web lain: surat masuk/keluar). Ganti domain cukup edit URL di sini.</p></div>
    <?php if ($msg_ok): ?><div class="alert alert-success"><?php echo $msg_ok; ?></div><?php endif; ?>
    <?php if ($msg_err): ?><div class="alert alert-danger"><?php echo $msg_err; ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><strong>Endpoint Masuk — SIMAD Guru</strong></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="aksi" value="save_inbound">
                        <label>URL Endpoint Guru (web lain)</label>
                        <div class="form-group"><input type="text" class="form-control" name="simad_url" value="<?php echo htmlspecialchars($cfg['simad_url']); ?>" placeholder="https://simad.domain/api/v1/teachers"></div>
                        <label>API Key SIMAD</label>
                        <div class="form-group"><input type="text" class="form-control" name="simad_key" value="<?php echo htmlspecialchars($cfg['simad_key']); ?>"></div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="simad_auto" id="simad_auto" <?php echo $cfg['simad_auto'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="simad_auto">Auto-sync saat buka Data Guru</label>
                        </div>
                        <label>Interval auto (menit, min 15)</label>
                        <div class="form-group"><input type="number" class="form-control" name="simad_interval" value="<?php echo (int)$cfg['simad_interval']; ?>" min="15" max="10080"></div>
                        <?php if ($cfg['simad_last_auto']): ?><p class="small text-muted">Sinkron terakhir: <?php echo htmlspecialchars($cfg['simad_last_auto']); ?></p><?php endif; ?>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> SIMPAN</button>
                        <a class="btn btn-info" href="integrasi.php?test_inbound=1&csrf_token=<?php echo $csrf; ?>"><i class="fas fa-plug"></i> TES KONEKSI</a>
                    </form>
                    <hr>
                    <form method="POST" onsubmit="return confirm('Jalankan sinkron guru sekarang?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="aksi" value="sync_now">
                        <button class="btn btn-success" type="submit"><i class="fas fa-sync"></i> SINKRON SEKARANG</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><strong>Endpoint Keluar — SIMS (copy ke web lain)</strong></div>
                <div class="card-body">
                    <label>Surat Masuk (GET)</label>
                    <div class="input-group mb-2"><input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($outbound['surat_masuk']); ?>" id="out_masuk"><div class="input-group-append"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('out_masuk').value)">Copy</button></div></div>
                    <label>Surat Keluar (GET)</label>
                    <div class="input-group mb-2"><input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($outbound['surat_keluar']); ?>" id="out_keluar"><div class="input-group-append"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('out_keluar').value)">Copy</button></div></div>
                    <label>Surat Keputusan (GET)</label>
                    <div class="input-group mb-3"><input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($outbound['surat_keputusan']); ?>" id="out_sk"><div class="input-group-append"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('out_sk').value)">Copy</button></div></div>
                    <p class="small text-muted">Base URL ikut domain aktif otomatis. Auth: header <code>X-API-KEY</code> saja (<code>?key=</code> ditolak). Param: <code>updated_since=Y-m-d H:i:s</code>, <code>limit</code>, <code>search</code>.</p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="aksi" value="save_outbound">
                        <label>API Key Keluar</label>
                        <div class="input-group mb-2"><input type="text" class="form-control" name="sims_api_key" value="<?php echo htmlspecialchars($cfg['sims_api_key']); ?>"><div class="input-group-append"><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(this.closest('form').querySelector('[name=sims_api_key]').value)">Copy</button></div></div>
                        <label>Whitelist IP (opsional, pisah koma/spasi — kosong = semua IP)</label>
                        <div class="form-group"><input type="text" class="form-control" name="sims_allowed_ips" value="<?php echo htmlspecialchars($cfg['sims_allowed_ips'] ?? ''); ?>" placeholder="cth: 103.147.9.12, 202.152.44.10"></div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="sims_api_enabled" id="sims_en" <?php echo $cfg['sims_api_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sims_en">API keluar aktif</label>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="sims_require_https" id="sims_https" <?php echo !empty($cfg['sims_require_https']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sims_https">Wajib HTTPS di hosting (lokal .test tetap boleh HTTP)</label>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> SIMPAN</button>
                    </form>
                    <form method="POST" class="mt-2" onsubmit="return confirm('Generate key baru? Key lama di web lain harus diupdate.');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="aksi" value="regen_key">
                        <button class="btn btn-warning btn-sm" type="submit"><i class="fas fa-key"></i> GENERATE KEY BARU</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Daftar Endpoint Terdaftar</strong> <span class="small text-muted">— catat endpoint web lain di sini, domain ganti tinggal edit, tanpa bongkar backend.</span></div>
        <div class="card-body">
            <form method="POST" class="form-inline mb-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="aksi" value="add_endpoint">
                <select name="arah" class="form-control mr-2"><option value="masuk">Masuk (kita tarik)</option><option value="keluar">Keluar (web lain tarik)</option></select>
                <input name="nama" class="form-control mr-2" placeholder="Nama mis. SIMAD Guru" required>
                <input name="url" class="form-control mr-2" style="min-width:280px" placeholder="https://domain/api/..." required>
                <input name="api_key" class="form-control mr-2" placeholder="API key (opsional)">
                <button class="btn btn-success" type="submit">+ TAMBAH</button>
            </form>
            <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Arah</th><th>Nama</th><th>URL</th><th>Aktif</th><th>Status Tes</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($endpoints as $e): ?>
                <tr>
                    <td><span class="badge badge-<?php echo $e['arah'] === 'masuk' ? 'info' : 'primary'; ?>"><?php echo htmlspecialchars($e['arah']); ?></span></td>
                    <td><?php echo htmlspecialchars($e['nama']); ?></td>
                    <td style="max-width:320px;word-break:break-all"><?php echo htmlspecialchars($e['url']); ?></td>
                    <td><?php echo ((int)$e['aktif'] === 1) ? 'Ya' : 'Tidak'; ?></td>
                    <td><?php echo htmlspecialchars((string)($e['last_status'] ?? '-')); ?><?php if (!empty($e['last_checked'])) echo '<br><small>' . htmlspecialchars($e['last_checked']) . '</small>'; ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-info btn-sm" href="integrasi.php?test=<?php echo (int)$e['id']; ?>&csrf_token=<?php echo $csrf; ?>">Tes</a>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="aksi" value="toggle_endpoint"><input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>"><button class="btn btn-secondary btn-sm" type="submit">On/Off</button></form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus endpoint?');"><input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="aksi" value="del_endpoint"><input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$endpoints): ?><tr><td colspan="6" class="text-center text-muted">Belum ada endpoint tambahan.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Log Akses Endpoint Keluar</strong> <span class="small text-muted">(20 terakhir — siapa tarik data)</span></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Waktu</th><th>Endpoint</th><th>IP</th><th>Kode</th><th>Pesan</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                <tr><td><?php echo htmlspecialchars($l['created_at']); ?></td><td><?php echo htmlspecialchars($l['endpoint']); ?></td><td><?php echo htmlspecialchars((string)($l['ip'] ?? '')); ?></td><td><?php echo (int)$l['status_code']; ?></td><td><?php echo htmlspecialchars((string)($l['message'] ?? '')); ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?><tr><td colspan="5" class="text-center text-muted">Belum ada hit.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'template/footer.php'; ?>
