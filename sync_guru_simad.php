<?php
/**
 * Sinkron data guru dari Central Hub SIMAD — GET api/v1/teachers (atau teachers.php fallback).
 * Banyak hosting mem-301 dari .php ke URL tanpa ekstensi; query string lebih aman tidak melalui 301 salah.
 * - Sinkron inkremental: query updated_since=Y-m-d H:i:s — kursor disimpan dari field last_sync respons terakhir (config matikan SIMAD_INCREMENTAL_SYNC=0 jika tidak dipakai).
 */

$SIMAD_SCRIPT_DIR = __DIR__;
$simad_is_cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

const SIMAD_HUB_API_KEY = 'SIS_CENTRAL_HUB_SECRET_2026';

/**
 * URL GET teacher API jika env SIMAD_TEACHERS_API_URL & variabel global $SIMAD_TEACHERS_API_URL (config) kosong.
 * Override: set env SIMAD_TEACHERS_API_URL (disarankan untuk server lain) atau $SIMAD_TEACHERS_API_URL di config.
 */
const SIMAD_DEFAULT_TEACHERS_API_URL = 'https://simad.misultanfattah.sch.id/api/v1/teachers';

/** Kanonis dulu (hindari 301 LiteSpeed/nginx); .php untuk instal lama */
const SIMAD_TEACHERS_PATH_CANDIDATES = ['/api/v1/teachers', '/api/v1/teachers.php'];

/**
 * Bangun kandidat endpoint guru dari URL basis.
 *
 * @param bool $pathIsHubRoot true = pathname di URL dianggap root instalasi Central Hub (kolom Pengaturan → Website)
 * @param bool $pathIsHubRoot false = basis dari $base_url SIMS: jangan tempel path folder SIMS ke API kecuali terlihat folder SIMAD
 */
function simad_append_teacher_api_urls(?string $baseLike, array &$urls, bool $pathIsHubRoot): void
{
    $s = trim((string)$baseLike);
    if ($s === '') {
        return;
    }
    $withScheme = $s;
    if (!preg_match('#^https?://#i', $withScheme)) {
        $withScheme = 'http://' . preg_replace('#^[/]+#', '', $withScheme);
    }
    $p = parse_url($withScheme);
    if (!is_array($p) || empty($p['host'])) {
        return;
    }
    $scheme = strtolower($p['scheme'] ?? 'http');
    $host = $p['host'];
    $port = isset($p['port']) ? ':' . (int)$p['port'] : '';
    $pathname = isset($p['path']) ? rtrim($p['path'], '/') : '';

    $origin = $scheme . '://' . $host . $port;

    foreach (SIMAD_TEACHERS_PATH_CANDIDATES as $apath) {
        if ($pathIsHubRoot && $pathname !== '' && $pathname !== '/') {
            $urls[] = $origin . $pathname . $apath;
        }
        if (!$pathIsHubRoot && $pathname !== '' && $pathname !== '/' && stripos($pathname, 'simad') !== false) {
            $urls[] = $origin . $pathname . $apath;
        }
        $urls[] = $origin . $apath;
        $urls[] = $origin . '/simad' . $apath;
    }
}

/**
 * URL endpoint — urutan: override penuh → basis hub eksplisit (config/env) → Pengaturan → heuristik lokal.
 */
function simad_hub_urls(): array
{
    $env = getenv('SIMAD_TEACHERS_API_URL');
    if ($env !== false && trim((string)$env) !== '') {
        return [trim((string)$env)];
    }

    global $SIMAD_TEACHERS_API_URL, $SIMAD_CENTRAL_HUB_BASE_URL, $conn, $base_url;
    if (!empty($SIMAD_TEACHERS_API_URL) && is_string($SIMAD_TEACHERS_API_URL) && trim($SIMAD_TEACHERS_API_URL) !== '') {
        return [trim($SIMAD_TEACHERS_API_URL)];
    }
    $def = trim((string)SIMAD_DEFAULT_TEACHERS_API_URL);
    if ($def !== '' && simad_is_usable_hub_url($def)) {
        return [$def];
    }

    $urls = [];
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[\r\n\s]/', '', (string)$_SERVER['HTTP_HOST']) : '';

    /** Utama saat SIMS di lokal tetapi hub di hosting (hindari sims_ok.test/simad...) */
    $hubBases = [];
    $eBase = getenv('SIMAD_CENTRAL_HUB_BASE_URL');
    if ($eBase !== false && trim((string)$eBase) !== '') {
        $hubBases[] = trim((string)$eBase);
    }
    if (
        isset($SIMAD_CENTRAL_HUB_BASE_URL)
        && is_string($SIMAD_CENTRAL_HUB_BASE_URL)
        && trim($SIMAD_CENTRAL_HUB_BASE_URL) !== ''
    ) {
        $hubBases[] = trim($SIMAD_CENTRAL_HUB_BASE_URL);
    }
    foreach (array_values(array_unique($hubBases)) as $hb) {
        simad_append_teacher_api_urls($hb, $urls, true);
    }

    /**
     * Basis hub eksplisit (env / config): jangan gabung Pengaturan → Website —
     * sering salah (domain induk tanpa subdomain simad. → …/simad/api 404 dan menimpa error asli).
     */
    $strictHubOnly = $hubBases !== [];
    if (!$strictHubOnly) {
        /**
         * Laragon / dev: vhost SIMS `sims_ok.test` → coba `simad.test` bila ada.
         */
        if ($host !== '' && preg_match('/sims[_-]?ok/i', $host)) {
            $altHost = preg_replace('/sims[_-]?ok/i', 'simad', $host, 1);
            if ($altHost !== $host && $altHost !== '') {
                simad_append_teacher_api_urls('http://' . $altHost, $urls, false);
                simad_append_teacher_api_urls('https://' . $altHost, $urls, false);
            }
        }

        /** 1) Website madrasah / portal (Pengaturan) */
        if (isset($conn) && $conn instanceof mysqli) {
            $rq = @mysqli_query($conn, 'SELECT website FROM pengaturan LIMIT 1');
            if ($rq && mysqli_num_rows($rq) > 0) {
                $row = mysqli_fetch_assoc($rq);
                if (!empty($row['website'])) {
                    simad_append_teacher_api_urls($row['website'], $urls, true);
                }
            }
        }

        /** 2) Base URL SIMS */
        if (isset($base_url) && is_string($base_url) && trim($base_url) !== '') {
            simad_append_teacher_api_urls(trim($base_url), $urls, false);
        }

        /** 3) Host permintaan saat ini */
        if ($host !== '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $o = ($https ? 'https' : 'http') . '://' . $host;
            simad_append_teacher_api_urls($o, $urls, false);
        }

        /** CLI / cadangan localhost */
        $cli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        if ($urls === [] || $cli) {
            foreach (['127.0.0.1', 'localhost'] as $h) {
                simad_append_teacher_api_urls('http://' . $h, $urls, false);
            }
        }
    }

    $out = array_values(array_unique(array_filter(array_map(static function ($u) {
        return trim((string)$u);
    }, $urls))));

    /** simad_is_usable_hub_url didefinisikan di bawah; PHP mengizinkan pemanggilan */
    return array_values(array_filter($out, 'simad_is_usable_hub_url'));
}

function simad_config(): array
{
    $ck = getenv('SIMAD_SYNC_CRON_KEY');
    $ak = getenv('SIMAD_API_KEY');

    /** Opsional: global $SIMAD_INCREMENTAL_SYNC, $SIMAD_HUB_FETCH_LIMIT di config; atau env SIMAD_INCREMENTAL_SYNC / SIMAD_TEACHERS_LIMIT */
    global $SIMAD_INCREMENTAL_SYNC, $SIMAD_HUB_FETCH_LIMIT;

    $incr = true;
    $incrEnv = getenv('SIMAD_INCREMENTAL_SYNC');
    if ($incrEnv !== false && strtolower(trim((string)$incrEnv)) === '0') {
        $incr = false;
    }
    if (isset($SIMAD_INCREMENTAL_SYNC) && $SIMAD_INCREMENTAL_SYNC === false) {
        $incr = false;
    }

    $lim = 0;
    $gle = getenv('SIMAD_TEACHERS_LIMIT');
    if ($gle !== false && trim((string)$gle) !== '') {
        $lim = max(0, min(1000, (int)$gle));
    }
    if (isset($SIMAD_HUB_FETCH_LIMIT)) {
        $lim = max(0, min(1000, (int)$SIMAD_HUB_FETCH_LIMIT));
    }

    return [
        'api_urls' => simad_hub_urls(),
        'api_key' => ($ak !== false && trim((string)$ak) !== '') ? trim((string)$ak) : SIMAD_HUB_API_KEY,
        'auto_when_admin_opens_guru_page' => true,
        'auto_interval_minutes' => min(10080, max(15, 60)),
        'cron_http_secret' => ($ck !== false && trim((string)$ck) !== '') ? trim((string)$ck) : '',
        'use_incremental_sync' => $incr,
        'hub_fetch_limit' => min(1000, $lim),
    ];
}

function simad_state_path($dir)
{
    return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'simad_sync_state.json';
}

function simad_last_success_unix($dir)
{
    return (int)simad_read_sync_state_full($dir)['last_success_unix'];
}

function simad_read_sync_state_full($dir)
{
    $p = simad_state_path($dir);
    if (!is_readable($p)) {
        return ['last_success_unix' => 0, 'hub_updated_since_cursor' => null];
    }
    $raw = @file_get_contents($p);
    if ($raw === false || $raw === '') {
        return ['last_success_unix' => 0, 'hub_updated_since_cursor' => null];
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        return ['last_success_unix' => 0, 'hub_updated_since_cursor' => null];
    }

    return [
        'last_success_unix' => isset($j['last_success_unix']) ? (int)$j['last_success_unix'] : 0,
        'hub_updated_since_cursor' => isset($j['hub_updated_since_cursor']) && is_string($j['hub_updated_since_cursor'])
            ? trim($j['hub_updated_since_cursor']) : null,
    ];
}

/** Format Y-m-d H:i:s (sama validator di hub) */
function simad_hub_valid_datetime_ymdhis(?string $s): bool
{
    if ($s === null || trim($s) === '') {
        return false;
    }
    $ts = trim($s);
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ts);

    return $dt !== false && $dt->format('Y-m-d H:i:s') === $ts;
}

function simad_write_sync_state($dir, array $patch)
{
    $cur = simad_read_sync_state_full($dir);
    foreach ($patch as $k => $v) {
        $cur[$k] = $v;
    }
    @file_put_contents(simad_state_path($dir), json_encode($cur, JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function simad_touch_last_success($dir, ?string $hub_last_sync = null)
{
    $patch = ['last_success_unix' => time()];
    if ($hub_last_sync !== null && simad_hub_valid_datetime_ymdhis($hub_last_sync)) {
        $patch['hub_updated_since_cursor'] = trim($hub_last_sync);
    }
    simad_write_sync_state($dir, $patch);
}

// ── Inti bisnis ──────────────────────────────────────────────────────────────

/** filter_var gagal untuk host sah seperti sims_ok.test (underscore); cURL tidak masalah dengan itu */
function simad_is_usable_hub_url($u)
{
    $u = trim((string)$u);
    if ($u === '') {
        return false;
    }
    if (filter_var($u, FILTER_VALIDATE_URL) !== false) {
        return true;
    }

    return (bool)preg_match('#^https?://[^\s]+$#', $u);
}

function simad_normalize_tgl($tanggal_raw)
{
    $v = trim((string)$tanggal_raw);
    if ($v === '') {
        return null;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $v, $m)) {
        return $m[1];
    }
    $ts = strtotime(str_replace('/', '-', $v));
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return null;
}

function simad_map_jk_to_enum($jk_raw)
{
    $s = trim((string)$jk_raw);
    if ($s === '') {
        return 'L';
    }
    $u = function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
    if ($u === 'P' || $u === 'W' || strpos($u, 'PEREMP') !== false || strpos($u, 'WANITA') !== false) {
        return 'P';
    }
    if ($u === 'L' || strpos($u, 'LAKI') !== false) {
        return 'L';
    }

    return 'L';
}

/** Untuk mencocokan NUPTK dengan/tanpa pemisah. */
function simad_nuptk_digits(?string $s): string
{
    return preg_replace('/\D+/', '', (string)$s);
}

/** Normalisasi ringan nama untuk padanan sekunder (hilangkan spasi ganda). */
function simad_nama_normalized_key(?string $s): string
{
    $t = preg_replace('/\s+/u', ' ', trim((string)$s));
    if ($t === '') {
        return '';
    }

    return function_exists('mb_strtolower') ? mb_strtolower($t, 'UTF-8') : strtolower($t);
}

/**
 * Nama lokal dan nama hub kemungkinan orang yang sama (typo/impor salah) — digunakan untuk FIND + dedupe.
 */
function simad_nama_prob_same(string $namaLokal, string $namaHub): bool
{
    $namaLokal = preg_replace('/\s+/u', ' ', trim($namaLokal));
    $namaHub = preg_replace('/\s+/u', ' ', trim($namaHub));
    if ($namaLokal === '' || $namaHub === '') {
        return false;
    }
    $lk = simad_nama_normalized_key($namaLokal);
    $hk = simad_nama_normalized_key($namaHub);
    if ($lk !== '' && $lk === $hk) {
        return true;
    }
    $enc = 'UTF-8';
    $lenL = function_exists('mb_strlen') ? (int)mb_strlen($lk, $enc) : strlen($lk);
    $lenH = function_exists('mb_strlen') ? (int)mb_strlen($hk, $enc) : strlen($hk);
    if ($lenL === 0 || $lenH === 0) {
        return false;
    }
    $ratio = $lenL > $lenH ? $lenL / $lenH : $lenH / $lenL;
    if ($ratio > 1.45) {
        return false;
    }
    $d = levenshtein($lk, $hk);
    $tol = max(2, (int)ceil(min($lenL, $lenH) * 0.14));

    return $d <= $tol;
}

/**
 * Respons Central Hub bisa memakai kunci nama field berbeda — samakan ke id_guru, nama_guru, dll.
 */
function simad_hub_teacher_normalize(array $raw): array
{
    $t = $raw;
    $id = isset($raw['id_guru']) ? (int)$raw['id_guru'] : 0;
    if ($id <= 0) {
        foreach (['id', 'guru_id', 'teacher_id', 'id_staff', 'id_pegawai', 'staff_id', 'nip_id'] as $k) {
            if (!empty($raw[$k]) || (isset($raw[$k]) && (string)$raw[$k] === '0')) {
                $id = (int)$raw[$k];
                break;
            }
        }
    }
    $t['id_guru'] = $id;

    $nama = isset($raw['nama_guru']) ? trim((string)$raw['nama_guru']) : '';
    foreach (['nama', 'nama_lengkap', 'name', 'full_name'] as $k) {
        if ($nama === '' && isset($raw[$k]) && trim((string)$raw[$k]) !== '') {
            $nama = trim((string)$raw[$k]);
        }
    }
    $t['nama_guru'] = $nama;

    $kode = isset($raw['kode_guru']) ? trim((string)$raw['kode_guru']) : '';
    foreach (['kode', 'nip', 'nik'] as $k) {
        if ($kode === '' && isset($raw[$k]) && trim((string)$raw[$k]) !== '') {
            $vc = trim((string)$raw[$k]);
            /** jangan salah pakai nama panjang sebagai kode guru */
            if (strlen($vc) <= 32) {
                $kode = $vc;
            }
        }
    }
    $t['kode_guru'] = $kode;

    $nuptk = isset($raw['nuptk']) ? trim((string)$raw['nuptk']) : '';
    foreach (['nuks', 'no_nuks', 'no_nuks_tendik', 'nip_nuks'] as $k) {
        if ($nuptk === '' && isset($raw[$k])) {
            $nuptk = trim((string)$raw[$k]);
        }
    }
    $t['nuptk'] = $nuptk;

    $tgl = isset($raw['tanggal_lahir']) ? trim((string)$raw['tanggal_lahir']) : '';
    foreach (['tgl_lahir', 'tglahir', 'birth_date'] as $k) {
        if ($tgl === '' && isset($raw[$k])) {
            $tgl = trim((string)$raw[$k]);
        }
    }
    $t['tanggal_lahir'] = $tgl;

    $tpl = isset($raw['tempat_lahir']) ? trim((string)$raw['tempat_lahir']) : '';
    foreach (['tmp_lahir', 'tempatlahir', 'birth_place'] as $k) {
        if ($tpl === '' && isset($raw[$k])) {
            $tpl = trim((string)$raw[$k]);
        }
    }
    $t['tempat_lahir'] = $tpl;

    $jk = isset($raw['jenis_kelamin']) ? trim((string)$raw['jenis_kelamin']) : '';
    foreach (['jk', 'gender', 'kelamin', 'jenisKelamin'] as $k) {
        if ($jk === '' && isset($raw[$k])) {
            $jk = trim((string)$raw[$k]);
        }
    }
    $t['jenis_kelamin'] = $jk;

    return $t;
}

/** Tarik satu format tanggal konsisten yyyy-mm-dd dari nilai DATE/DATETIME/varchar guru lokal */
function simad_guru_normalize_tgl_sql(?string $dbVal): ?string
{
    if ($dbVal === null || trim((string)$dbVal) === '') {
        return null;
    }
    $raw = preg_replace('#\.\d+#', '', trim((string)$dbVal)); /* microseconds */
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
        $d = $m[1];
        if (strpos($d, '-00') !== false) {
            return null;
        }

        return $d;
    }

    return null;
}

/** Tambah kolom penaut ke SIMAD sekali-jalan agar pergantian nama/NUPTK menemukan baris yang sama */
function simad_guru_hub_sync_ensure_columns($conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (
        ['simad_id_guru' => 'INT NULL DEFAULT NULL', 'kode_guru' => 'VARCHAR(64) NULL DEFAULT NULL'] as $col => $ddl
    ) {
        $chk = @mysqli_query($conn, "SHOW COLUMNS FROM guru LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            @mysqli_query($conn, 'ALTER TABLE guru ADD COLUMN ' . $col . ' ' . $ddl);
        }
    }

    $chkS = @mysqli_query($conn, "SHOW COLUMNS FROM guru LIKE 'simad_id_guru'");
    if ($chkS && mysqli_num_rows($chkS) > 0) {
        try {
            mysqli_query($conn, 'CREATE INDEX idx_guru_simad_id ON guru (simad_id_guru)');
        } catch (Throwable $t) {
            /* indeks sudah ada (sinkron kedua dll.) — abaikan */
        }
    }
    $chkK = @mysqli_query($conn, "SHOW COLUMNS FROM guru LIKE 'kode_guru'");
    if ($chkK && mysqli_num_rows($chkK) > 0) {
        try {
            mysqli_query($conn, 'CREATE INDEX idx_guru_simad_kode ON guru (kode_guru)');
        } catch (Throwable $t) {
            /* indeks sudah ada */
        }
    }
}

function simad_guru_column_exists($conn, string $name): bool
{
    $n = mysqli_real_escape_string($conn, $name);
    $r = @mysqli_query($conn, "SHOW COLUMNS FROM guru LIKE '$n'");

    return $r && mysqli_num_rows($r) > 0;
}

/**
 * Cocokkan baris guru lokal dengan payload hub — urutan dari yang paling stabil.
 *
 * @return array<string,mixed>|null
 */
function simad_find_existing_guru($conn, array $teacher, ?string $tgl_sql, string $nama_esc, bool $colSimad, bool $colKode)
{
    $hubId = isset($teacher['id_guru']) ? (int)$teacher['id_guru'] : 0;
    $nuptk = trim((string)($teacher['nuptk'] ?? ''));
    $kode = trim((string)($teacher['kode_guru'] ?? ''));

    $fields = 'id, nama, nuptk, jk, tempat_lahir, tgl_lahir, status';
    if ($colSimad) {
        $fields .= ', simad_id_guru';
    }
    if ($colKode) {
        $fields .= ', kode_guru';
    }
    $select = 'SELECT ' . $fields . ' FROM guru ';

    if ($colSimad && $hubId > 0) {
        $hid = (int)$hubId;
        $q = mysqli_query($conn, $select . "WHERE simad_id_guru = $hid LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q);
        }
    }

    if ($colKode && $kode !== '') {
        $k_e = mysqli_real_escape_string($conn, $kode);
        $q = mysqli_query($conn, $select . "WHERE kode_guru = '$k_e' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q);
        }
    }

    if ($nuptk !== '') {
        $n_e = mysqli_real_escape_string($conn, $nuptk);
        $q = mysqli_query($conn, $select . "WHERE nuptk = '$n_e' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q);
        }
        $digitsHub = simad_nuptk_digits($nuptk);
        if (strlen($digitsHub) >= 6) {
            $orderBy = 'ORDER BY id ASC';
            if ($colSimad && $hubId > 0) {
                $orderBy = 'ORDER BY (simad_id_guru = ' . (int)$hubId . ') DESC, id ASC';
            }
            $qAll = mysqli_query(
                $conn,
                $select . 'WHERE nuptk IS NOT NULL AND TRIM(nuptk) <> \'\' ' . $orderBy
            );
            if ($qAll) {
                while ($row = mysqli_fetch_assoc($qAll)) {
                    if (simad_nuptk_digits((string)$row['nuptk']) === $digitsHub) {
                        return $row;
                    }
                }
            }
        }
    }

    if ($tgl_sql !== null && $tgl_sql !== '') {
        $t_esc = mysqli_real_escape_string($conn, $tgl_sql);
        $q = mysqli_query(
            $conn,
            $select . "WHERE tgl_lahir = '$t_esc' AND nama = '$nama_esc' LIMIT 1"
        );
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q);
        }
        /** TTL sama tetapi nama beda typo — gabungkan hanya jika tunggal jelas atau satu padanan nama */
        $keyHub = simad_nama_normalized_key((string)($teacher['nama_guru'] ?? ''));
        if ($keyHub === '') {
            return null;
        }
        $qAlt = mysqli_query($conn, $select . "WHERE tgl_lahir = '$t_esc'");
        if (!$qAlt) {
            return null;
        }
        $rowsTtl = [];
        while ($row = mysqli_fetch_assoc($qAlt)) {
            $rowsTtl[] = $row;
        }
        $nameHits = [];
        foreach ($rowsTtl as $row) {
            if (simad_nama_normalized_key((string)$row['nama']) === $keyHub) {
                $nameHits[] = $row;
            }
        }
        if (count($nameHits) === 1) {
            return $nameHits[0];
        }
        if (count($rowsTtl) === 1) {
            return $rowsTtl[0];
        }
    }

    /* Kelahiran beda bentuk/normalisasi tapi sama dengan SIMAD → bandingkan Y-m-d per baris */
    if ($tgl_sql !== null && $tgl_sql !== '') {
        $namaHubGuess = trim((string)($teacher['nama_guru'] ?? ''));
        $jkQ = mysqli_real_escape_string($conn, simad_map_jk_to_enum((string)($teacher['jenis_kelamin'] ?? '')));
        $qLoose = mysqli_query(
            $conn,
            $select . "WHERE jk='$jkQ' AND tgl_lahir IS NOT NULL AND TRIM(CAST(tgl_lahir AS CHAR)) <> '' LIMIT 3500"
        );
        $looseHits = [];
        if ($qLoose) {
            while ($lw = mysqli_fetch_assoc($qLoose)) {
                if (simad_guru_normalize_tgl_sql(isset($lw['tgl_lahir']) ? (string)$lw['tgl_lahir'] : null) === $tgl_sql) {
                    $looseHits[] = $lw;
                }
            }
        }
        if ($namaHubGuess !== '') {
            $lhName = [];
            foreach ($looseHits as $lw) {
                if (!simad_nama_prob_same((string)$lw['nama'], $namaHubGuess)) {
                    continue;
                }
                $lhName[] = $lw;
            }
            /** Hanya bila ada nama untuk dipadankan — gabung TTL+JK saja sangat ambigu (kembar, salah input) */
            if (count($lhName) === 1) {
                return $lhName[0];
            }
        }
    }

    /* Nama salah / impor berganda tanpa TTL/NUPTK yang cocok: cocok nama serupa + JK */
    $namaHubRaw = trim((string)($teacher['nama_guru'] ?? ''));
    if ($namaHubRaw !== '') {
        $jkStrict = mysqli_real_escape_string($conn, simad_map_jk_to_enum((string)($teacher['jenis_kelamin'] ?? '')));
        $digitsHub = simad_nuptk_digits($nuptk);
        $qNj = mysqli_query($conn, $select . 'WHERE jk=\'' . $jkStrict . '\' ORDER BY id ASC LIMIT 2500');
        $best = null;
        $bestScore = PHP_INT_MAX;
        if ($qNj) {
            while ($rj = mysqli_fetch_assoc($qNj)) {
                if (!simad_nama_prob_same((string)$rj['nama'], $namaHubRaw)) {
                    continue;
                }
                $rid = isset($rj['id']) ? (int)$rj['id'] : 0;
                if ($rid <= 0) {
                    continue;
                }
                $rsi = ($colSimad && isset($rj['simad_id_guru'])) ? (int)$rj['simad_id_guru'] : 0;
                if ($hubId > 0 && $rsi > 0 && $rsi !== $hubId) {
                    continue;
                }
                $digRow = simad_nuptk_digits((string)($rj['nuptk'] ?? ''));
                $digOk = $digitsHub !== '' && strlen($digitsHub) >= 6 && $digRow === $digitsHub;
                /** Skor rendah = lebih diutamakan: tertaut id hub, NUPTK cocok, id kecil */
                $score = 0;
                if ($hubId > 0 && $rsi === $hubId) {
                    $score -= 10000;
                }
                if ($digOk) {
                    $score -= 500;
                }
                if ($rsi === 0) {
                    $score -= 10;
                }
                $score += $rid;
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $best = $rj;
                }
            }
        }
        if ($best !== null) {
            return $best;
        }
    }

    return null;
}

/**
 * Satukan duplikat: hapus baris lain yang jelas guru SIMAD sama (nama salah / impor berganda).
 * Baris canonical = $keepId (barusan di-insert/di-update).
 *
 * @return int jumlah baris terhapus
 */
function simad_guru_hub_remove_other_duplicates(
    $conn,
    int $keepId,
    int $hubPk,
    string $hubNuptk,
    string $hubKode,
    string $hubNamaGuru,
    string $jkEnum,
    bool $colSimad,
    bool $colKode
): int {
    $keepId = max(1, $keepId);
    $del = [];

    if ($colSimad && $hubPk > 0) {
        $hid = (int)$hubPk;
        $rq = mysqli_query($conn, "SELECT id FROM guru WHERE simad_id_guru=$hid AND id <> $keepId");
        if ($rq) {
            while ($r = mysqli_fetch_assoc($rq)) {
                $del[] = (int)$r['id'];
            }
        }
    }
    if ($colKode && trim($hubKode) !== '') {
        $k = mysqli_real_escape_string($conn, trim($hubKode));
        $rq = mysqli_query($conn, "SELECT id FROM guru WHERE kode_guru='$k' AND id <> $keepId");
        if ($rq) {
            while ($r = mysqli_fetch_assoc($rq)) {
                $del[] = (int)$r['id'];
            }
        }
    }

    $dHub = simad_nuptk_digits($hubNuptk);
    if (strlen($dHub) >= 6) {
        $rq = mysqli_query(
            $conn,
            "SELECT id, nuptk FROM guru WHERE id <> $keepId AND nuptk IS NOT NULL AND TRIM(nuptk) <> ''"
        );
        if ($rq) {
            while ($r = mysqli_fetch_assoc($rq)) {
                if (simad_nuptk_digits((string)$r['nuptk']) === $dHub) {
                    $del[] = (int)$r['id'];
                }
            }
        }
    }

    /** Nama serupa → hanya bila ada id guru hub (identitas SIMAD pasti); hindari salah hapus dua orang sama JK */
    $hidPkRow = max(0, $hubPk);
    $namaT = preg_replace('/\s+/u', ' ', trim($hubNamaGuru));
    if ($hidPkRow > 0 && $namaT !== '' && ($jkEnum === 'L' || $jkEnum === 'P')) {
        $jke = mysqli_real_escape_string($conn, $jkEnum);
        $cols = $colSimad ? 'id, nama, simad_id_guru' : 'id, nama';
        $rq = mysqli_query(
            $conn,
            'SELECT ' . $cols . " FROM guru WHERE jk='$jke' AND id <> $keepId ORDER BY id ASC LIMIT 3500"
        );
        if ($rq) {
            while ($r = mysqli_fetch_assoc($rq)) {
                if (!simad_nama_prob_same((string)$r['nama'], $namaT)) {
                    continue;
                }
                $rsi = ($colSimad && isset($r['simad_id_guru'])) ? (int)$r['simad_id_guru'] : 0;
                if ($rsi > 0 && $rsi !== $hidPkRow) {
                    continue;
                }
                $del[] = (int)$r['id'];
            }
        }
    }

    $del = array_values(array_unique(array_filter($del, static function ($n) use ($keepId) {
        return $n > 0 && $n !== $keepId;
    })));
    if ($del === []) {
        return 0;
    }
    $sql = 'DELETE FROM guru WHERE id IN (' . implode(',', $del) . ')';
    if (!mysqli_query($conn, $sql)) {
        return 0;
    }
    return max(0, (int)mysqli_affected_rows($conn));
}

/** @return array{0:resource|null,1:callable|null} */
function simad_acquire_lock($base_dir)
{
    $dir = rtrim($base_dir, '/\\');
    $path = $dir . DIRECTORY_SEPARATOR . 'simad_guru_sync.lock';
    $fp = @fopen($path, 'c+');
    if (!$fp) {
        return [null, null];
    }
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);

        return [null, null];
    }

    return [$fp, static function () use ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }];
}

/** Pasang parameter GET sesuai teachers.php (?updated_since=…&limit=…) */
function simad_hub_teachers_full_url(string $base, array $query_params): string
{
    $base = trim($base);
    if ($base === '') {
        return '';
    }
    $q = [];
    foreach ($query_params as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $q[$k] = $v;
    }
    if ($q === []) {
        return $base;
    }

    return $base . (strpos($base, '?') !== false ? '&' : '?') . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}

/** Respons error hub ketika incremental tidak mendukung (kolom updated_at, dll.). */
function simad_hub_must_retry_without_incremental(?array $decoded, int $http_code): bool
{
    if ($http_code !== 400 || !is_array($decoded)) {
        return false;
    }
    $m = strtolower((string)($decoded['message'] ?? ''));

    return strpos($m, 'updated_since') !== false
        || (strpos($m, 'updated_at') !== false && strpos($m, 'incremental') !== false);
}

/** Decode JSON respons hub; antisipasi BOM / teks pra-JSON dari notice PHP. */
function simad_hub_decode_json(string $response_body): ?array
{
    $raw = trim($response_body);
    if ($raw === '') {
        return null;
    }
    if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
        $raw = substr($raw, 3);
    }
    $d = json_decode($raw, true);
    if (is_array($d)) {
        return $d;
    }
    $start = false;
    if (preg_match('/\{\s*"status"\s*:/', $raw, $mm, PREG_OFFSET_CAPTURE)) {
        $start = (int)$mm[0][1];
    }
    if ($start === false) {
        foreach (['{"status"', '{"sync_mode"', '{"total_data"'] as $mk) {
            $pos = strpos($raw, $mk);
            if ($pos !== false) {
                $start = $start === false ? $pos : min($start, $pos);
            }
        }
    }
    if ($start === false) {
        $start = strpos($raw, '{');
    }
    if ($start === false) {
        return null;
    }
    $slice = substr($raw, $start);
    while ($slice !== '') {
        $dec = json_decode($slice, true);
        if (is_array($dec) && array_key_exists('status', $dec)) {
            return $dec;
        }
        if (strlen($slice) <= 24) {
            break;
        }
        $slice = substr($slice, 0, -1);
    }

    return null;
}

/** Ringkas isi untuk pesan gagal sinkron agar bisa diagnosis tanpa membocorkan terlalu banyak. */
function simad_hub_preview_body_failure(string $body): string
{
    $trim = trim(preg_replace('/\s+/', ' ', $body));
    if ($trim === '') {
        return '[tubuh kosong]';
    }
    $low = strtolower(substr($trim, 0, 900));
    if (strpos($low, '<!doctype') === 0 || strpos($low, '<html') !== false) {
        return 'HTML (halaman www), bukan API JSON — kemungkinan salah URL/path atau blok firewall.';
    }
    return strlen($trim) > 280 ? substr($trim, 0, 280) . '…' : $trim;
}

/**
 * @param string[]     $query_params kolom untuk teachers.php saja (updated_since, limit)
 *
 * @return array{ok:bool,decoded?:array,http_code?:int,teachers?:array,error?:string,req_url?:string}
 */
function simad_hub_try_fetch_teachers(array $api_urls, string $api_key, array $query_params)
{
    $api_urls = array_values(array_filter(array_map(static function ($u) {
        return trim((string)$u);
    }, $api_urls), 'simad_is_usable_hub_url'));

    if ($api_urls === [] || trim($api_key) === '') {
        return ['ok' => false, 'error' => 'URL atau API key SIMAD tidak sah.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL tidak tersedia. Aktifkan extension curl.'];
    }

    $lastErr = '';
    foreach ($api_urls as $base) {
        $url = simad_hub_teachers_full_url($base, $query_params);
        if ($url === '') {
            continue;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-KEY: ' . trim($api_key),
            ],
        ]);

        $response_body = curl_exec($ch);
        $curl_err = curl_error($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_body === false) {
            $lastErr = 'Gagal menghubungi SIMAD: ' . ($curl_err ?: 'koneksi tidak diketahui') . ' | ' . $url;
            continue;
        }

        $data = simad_hub_decode_json((string)$response_body);
        if (!is_array($data)) {
            $httpBit = $http_code > 0 ? 'HTTP ' . $http_code . ' · ' : '';
            $lastErr = 'Respons bukan JSON valid (' . $httpBit . $url . '). ' . simad_hub_preview_body_failure((string)$response_body);
            continue;
        }

        if (($data['status'] ?? '') !== 'success') {
            $msg = isset($data['message']) ? (string)$data['message'] : 'SIMAD mengembalikan status error.';
            $hint = '';
            if (simad_hub_must_retry_without_incremental($data, $http_code)) {
                $hint = '|__FULL_SYNC__';
            }
            $lastErr = $msg . ($http_code && $http_code !== 200 ? ' (HTTP ' . $http_code . ')' : '') . ' — ' . $url . $hint;
            continue;
        }

        if (!array_key_exists('data', $data) || !is_array($data['data'])) {
            $lastErr = 'Respons tidak memuat array data guru (central hub).' . ' — ' . $url;

            continue;
        }

        $list = array_values(array_filter($data['data'], 'is_array'));

        return [
            'ok' => true,
            'decoded' => $data,
            'http_code' => $http_code,
            'teachers' => $list,
            'req_url' => $url,
        ];
    }

    return [
        'ok' => false,
        'error' => $lastErr ?: 'Tidak bisa mengambil data guru dari Central Hub.',
    ];
}

/**
 * Merge ke tabel guru lokal sesuai skema kolom API teacher (nama_guru, nuptk, dll.).
 *
 * @param array{use_incremental_sync?:bool,hub_fetch_limit?:int}|array $hub_opts dari simad_config()
 *
 * @return array{
 *     ok:bool,
 *     inserted?:int,
 *     updated?:int,
 *     skipped?:int,
 *     from_simad_total?:int,
 *     hub_last_sync?:?string,
 *     sync_mode?:string,
 *     removed_duplicates?:int,
 *     error?:string,
 * }
 */
function simad_run_teacher_merge($conn, array $api_urls, string $api_key, string $state_dir, array $hub_opts = [])
{
    $api_urls = array_values(array_filter(array_map(static function ($u) {
        return trim((string)$u);
    }, $api_urls), 'simad_is_usable_hub_url'));

    if ($api_urls === [] || trim($api_key) === '') {
        return ['ok' => false, 'error' => 'URL atau API key SIMAD tidak sah.'];
    }

    $incr = isset($hub_opts['use_incremental_sync']) ? (bool)$hub_opts['use_incremental_sync'] : true;
    $limit = isset($hub_opts['hub_fetch_limit']) ? (int)$hub_opts['hub_fetch_limit'] : 0;
    $cursor = '';
    $st = simad_read_sync_state_full($state_dir);
    if ($incr && !empty($st['hub_updated_since_cursor'])) {
        $c = trim((string)$st['hub_updated_since_cursor']);
        $cursor = simad_hub_valid_datetime_ymdhis($c) ? $c : '';
    }

    $qp = [];
    if ($incr && $cursor !== '') {
        $qp['updated_since'] = $cursor;
    }
    if ($limit > 0) {
        $qp['limit'] = (string)$limit;
    }

    $hit = simad_hub_try_fetch_teachers($api_urls, $api_key, $qp);
    if ((!$hit['ok']) && strpos((string)$hit['error'], '|__FULL_SYNC__') !== false && $incr && $cursor !== '') {
        $fallback = [];
        if ($limit > 0) {
            $fallback['limit'] = (string)$limit;
        }
        $hit = simad_hub_try_fetch_teachers($api_urls, $api_key, $fallback);
    }

    if (!$hit['ok']) {
        return ['ok' => false, 'error' => str_replace('|__FULL_SYNC__', '', (string)$hit['error'])];
    }

    $decoded = $hit['decoded'];
    $teachers = $hit['teachers'];

    $hub_last_sync = null;
    if (isset($decoded['last_sync']) && is_string($decoded['last_sync']) && simad_hub_valid_datetime_ymdhis(trim($decoded['last_sync']))) {
        $hub_last_sync = trim($decoded['last_sync']);
    }
    $sync_mode = isset($decoded['sync_mode']) ? (string)$decoded['sync_mode'] : '';

    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $removed_duplicates = 0;

    simad_guru_hub_sync_ensure_columns($conn);
    $colSimad = simad_guru_column_exists($conn, 'simad_id_guru');
    $colKode = simad_guru_column_exists($conn, 'kode_guru');

    try {
        foreach ($teachers as $teacher) {
        if (!is_array($teacher)) {
            $skipped++;
            continue;
        }
        $teacher = simad_hub_teacher_normalize($teacher);

        /* Central Hub: id_guru, kode_guru, nama_guru, nuptk, tempat_lahir, tanggal_lahir, jenis_kelamin */
        $nama_guru = trim((string)($teacher['nama_guru'] ?? ''));
        if ($nama_guru === '') {
            $skipped++;
            continue;
        }

        $hubPk = isset($teacher['id_guru']) ? (int)$teacher['id_guru'] : 0;
        $kodeGuruHub = trim((string)($teacher['kode_guru'] ?? ''));
        $nuptk = trim((string)($teacher['nuptk'] ?? ''));
        $tempat = trim((string)($teacher['tempat_lahir'] ?? ''));
        $tanggal_raw = (string)($teacher['tanggal_lahir'] ?? '');
        $tgl_sql = simad_normalize_tgl($tanggal_raw);

        $jk_raw = (string)($teacher['jenis_kelamin'] ?? '');
        $jk_enum = simad_map_jk_to_enum($jk_raw);
        $nama_esc = mysqli_real_escape_string($conn, $nama_guru);
        $tempat_esc = mysqli_real_escape_string($conn, $tempat);
        $nuptk_esc = mysqli_real_escape_string($conn, $nuptk);
        $jk_esc = mysqli_real_escape_string($conn, $jk_enum);
        $tgl_fragment = ($tgl_sql !== null && $tgl_sql !== '') ? "'" . mysqli_real_escape_string($conn, $tgl_sql) . "'" : 'NULL';

        $existing = simad_find_existing_guru($conn, $teacher, $tgl_sql, $nama_esc, $colSimad, $colKode);

        $sid_sql = ($hubPk > 0 ? (string)(int)$hubPk : 'NULL');
        $kode_sql = ($kodeGuruHub !== '' ? "'" . mysqli_real_escape_string($conn, $kodeGuruHub) . "'" : 'NULL');

        if ($existing === null) {
            $status_default_esc = mysqli_real_escape_string($conn, 'Guru Mapel');
            if ($colSimad || $colKode) {
                $ins = 'INSERT INTO guru (nuptk, nama, jk, tempat_lahir, tgl_lahir, status';
                $insV = "VALUES ('$nuptk_esc', '$nama_esc', '$jk_esc', '$tempat_esc', $tgl_fragment, '$status_default_esc'";
                if ($colSimad) {
                    $ins .= ', simad_id_guru';
                    $insV .= ', ' . $sid_sql;
                }
                if ($colKode) {
                    $ins .= ', kode_guru';
                    $insV .= ', ' . $kode_sql;
                }
                $ins = $ins . ') ' . $insV . ')';
            } else {
                $ins = "INSERT INTO guru (nuptk, nama, jk, tempat_lahir, tgl_lahir, status) "
                    . "VALUES ('$nuptk_esc', '$nama_esc', '$jk_esc', '$tempat_esc', $tgl_fragment, '$status_default_esc')";
            }
            if (mysqli_query($conn, $ins)) {
                $inserted++;
                $nid = (int)mysqli_insert_id($conn);
                if ($nid > 0) {
                    $removed_duplicates += simad_guru_hub_remove_other_duplicates($conn, $nid, $hubPk, $nuptk, $kodeGuruHub, $nama_guru, $jk_enum, $colSimad, $colKode);
                }
            } else {
                $skipped++;
            }

            continue;
        }

        $id = (int)$existing['id'];
        $same_nuptk = ((string)$existing['nuptk']) === $nuptk;
        $same_nama = ((string)$existing['nama']) === $nama_guru;
        $same_jk = ((string)$existing['jk']) === $jk_enum;
        $same_tempat = ((string)$existing['tempat_lahir']) === $tempat;

        $ex_tgl = $existing['tgl_lahir'] ?? '';
        $norm_ex = '';
        if ($ex_tgl !== null && $ex_tgl !== '' && strpos((string)$ex_tgl, '0000') !== 0) {
            $norm_ex = preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$ex_tgl, $mm) ? $mm[1] : '';
        }
        $same_tgl = ($tgl_sql === null || $tgl_sql === '')
            ? ($norm_ex === '' || $norm_ex === null)
            : ($norm_ex === $tgl_sql);

        $ex_sid = ($colSimad && isset($existing['simad_id_guru'])) ? (int)$existing['simad_id_guru'] : 0;
        $same_simad_pk = ($hubPk > 0 && $colSimad ? ($ex_sid === $hubPk) : true);

        $ex_kode = ($colKode && isset($existing['kode_guru'])) ? trim((string)$existing['kode_guru']) : '';
        $same_kode = ($kodeGuruHub === '' ? true : ($ex_kode === $kodeGuruHub));

        if ($same_nuptk && $same_nama && $same_jk && $same_tempat && $same_tgl && $same_simad_pk && $same_kode) {
            $removed_duplicates += simad_guru_hub_remove_other_duplicates($conn, $id, $hubPk, $nuptk, $kodeGuruHub, $nama_guru, $jk_enum, $colSimad, $colKode);

            continue;
        }

        $frag_sid_up = '';
        if ($colSimad && $hubPk > 0) {
            $frag_sid_up = ', simad_id_guru=' . (int)$hubPk;
        }
        $frag_kode_up = '';
        if ($colKode && $kodeGuruHub !== '') {
            $frag_kode_up = ", kode_guru='" . mysqli_real_escape_string($conn, $kodeGuruHub) . "'";
        }

        $up = 'UPDATE guru SET nuptk=\'' . $nuptk_esc . "', nama='$nama_esc', jk='$jk_esc', tempat_lahir='$tempat_esc', "
            . 'tgl_lahir=' . $tgl_fragment . $frag_sid_up . $frag_kode_up . " WHERE id=$id";
        if (mysqli_query($conn, $up)) {
            $updated++;
            $removed_duplicates += simad_guru_hub_remove_other_duplicates($conn, $id, $hubPk, $nuptk, $kodeGuruHub, $nama_guru, $jk_enum, $colSimad, $colKode);
        } else {
            $skipped++;
        }
    }
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Penyimpanan data: ' . $e->getMessage()];
    }

    $total_report = isset($decoded['total_data']) ? (int)$decoded['total_data'] : count($teachers);

    return [
        'ok' => true,
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
        'from_simad_total' => $total_report,
        'hub_last_sync' => $hub_last_sync,
        'sync_mode' => $sync_mode,
        'removed_duplicates' => $removed_duplicates,
    ];
}

function simad_cron_plain_response($exit_code, $is_cli, $stdout, $stderr = null)
{
    if ($is_cli) {
        if ($stderr !== null && $stderr !== '') {
            fwrite(STDERR, $stderr);
        }
        if ($stdout !== null) {
            echo $stdout;
        }
    } else {
        if ($exit_code === 403) {
            http_response_code(403);
        } elseif ($exit_code !== 0) {
            http_response_code(500);
        }
        header('Content-Type: text/plain; charset=UTF-8');
        echo $stdout ?? ($stderr ?? '');
    }
    exit((int)$exit_code);
}

function simad_execute_cron_path($conn, $SIMAD_SCRIPT_DIR, $simad_is_cli)
{
    $cfg = simad_config();
    if (empty($cfg['api_urls']) || $cfg['api_key'] === '') {
        simad_cron_plain_response(1, $simad_is_cli, '', "SIMAD tidak dapat dijangkau (URL/key).\n");
    }

    list($lock_fp, $lock_release) = simad_acquire_lock($SIMAD_SCRIPT_DIR);
    if ($lock_fp === null) {
        simad_cron_plain_response(2, $simad_is_cli, '', "Sinkron SIMAD sedang berjalan di proses lain.\n");
    }

    $hub_exec = [
        'use_incremental_sync' => $cfg['use_incremental_sync'],
        'hub_fetch_limit' => $cfg['hub_fetch_limit'],
    ];

    try {
        $result = simad_run_teacher_merge($conn, $cfg['api_urls'], $cfg['api_key'], $SIMAD_SCRIPT_DIR, $hub_exec);
    } finally {
        if (is_callable($lock_release)) {
            $lock_release();
        }
    }

    if (!$result['ok']) {
        simad_cron_plain_response(3, $simad_is_cli, '', 'Gagal: ' . $result['error'] . "\n");
    }

    simad_touch_last_success($SIMAD_SCRIPT_DIR, $result['hub_last_sync'] ?? null);

    $uid = 0;
    $uq = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
    if ($uq && mysqli_num_rows($uq) > 0) {
        $uid = (int)mysqli_fetch_assoc($uq)['id'];
    }
    if ($uid > 0) {
        log_activity(
            $uid,
            'update',
            'Cron sinkron guru SIMAD: ' . (int)$result['inserted'] . ' baru, ' . (int)$result['updated'] . ' diperbarui, '
                . (int)($result['removed_duplicates'] ?? 0) . ' duplikat dihapus'
        );
    }

    simad_cron_plain_response(
        0,
        $simad_is_cli,
        sprintf(
            "OK: ditambah=%d diperbarui=%d dilewati=%d duplikat=%d dari_simad=%d\n",
            (int)$result['inserted'],
            (int)$result['updated'],
            (int)$result['skipped'],
            (int)($result['removed_duplicates'] ?? 0),
            (int)$result['from_simad_total']
        )
    );
}

$simad_sn = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
$simad_am_main_script = $simad_is_cli
    || ($simad_sn !== '' && substr($simad_sn, -strlen('sync_guru_simad.php')) === 'sync_guru_simad.php');

if (!$simad_am_main_script) {
    return;
}

// ── Cron CLI ─────────────────────────────────────────────────────────────────
if ($simad_is_cli) {
    include $SIMAD_SCRIPT_DIR . '/config.php';
    simad_execute_cron_path($conn, $SIMAD_SCRIPT_DIR, true);
}

// ── Cron HTTP ───────────────────────────────────────────────────────────────
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cron_key_g = isset($_GET['key']) ? (string)$_GET['key'] : '';
    $wants_cron = isset($_GET['cron']) && ($_GET['cron'] === '1' || $_GET['cron'] === 'true');
    if ($wants_cron) {
        include $SIMAD_SCRIPT_DIR . '/config.php';
        $cfg_w = simad_config();
        if ($cfg_w['cron_http_secret'] !== '' && hash_equals($cfg_w['cron_http_secret'], $cron_key_g)) {
            simad_execute_cron_path($conn, $SIMAD_SCRIPT_DIR, false);
        }
    }

    http_response_code(405);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Gunakan POST untuk sinkron dari browser, atau CLI. Cron HTTP aktif jika cron_http_secret / SIMAD_SYNC_CRON_KEY diisi.';
    exit(0);
}

// ── POST (admin, JSON) ────────────────────────────────────────────────────────
require_once $SIMAD_SCRIPT_DIR . '/session_init.php';
include $SIMAD_SCRIPT_DIR . '/config.php';
header('Content-Type: application/json; charset=UTF-8');

function simad_browser_json(array $payload)
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    simad_browser_json(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
}

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    simad_browser_json(['status' => 'error', 'message' => 'Akses ditolak.']);
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    simad_browser_json(['status' => 'error', 'message' => 'Token CSRF tidak valid.']);
}

$post_auto = isset($_POST['automatic']) && ($_POST['automatic'] === '1' || $_POST['automatic'] === 'true' || $_POST['automatic'] === 1 || $_POST['automatic'] === true);

$cfg = simad_config();

if ($post_auto) {
    if (!$cfg['auto_when_admin_opens_guru_page']) {
        simad_browser_json([
            'status' => 'noop',
            'message' => 'Sinkron otomatis saat membuka halaman guru dimatikan.',
            'reason' => 'disabled',
        ]);
    }

    if (empty($cfg['api_urls']) || $cfg['api_key'] === '') {
        simad_browser_json([
            'status' => 'noop',
            'message' => 'Alamat atau key tidak tersedia.',
            'reason' => 'no_credentials',
        ]);
    }

    $interval = $cfg['auto_interval_minutes'];
    $last_u = simad_last_success_unix($SIMAD_SCRIPT_DIR);
    if ($last_u > 0) {
        $elapsed = time() - $last_u;
        if ($elapsed < ($interval * 60)) {
            simad_browser_json([
                'status' => 'noop',
                'message' => 'Belum sampai interval sinkron otomatis.',
                'reason' => 'throttle',
                'next_in_seconds' => ($interval * 60) - $elapsed,
            ]);
        }
    }

    list($lfp, $lrel) = simad_acquire_lock($SIMAD_SCRIPT_DIR);
    if ($lfp === null) {
        simad_browser_json(['status' => 'noop', 'message' => 'Sinkron lain sedang berjalan.', 'reason' => 'locked']);
    }

    try {
        $result = simad_run_teacher_merge($conn, $cfg['api_urls'], $cfg['api_key'], $SIMAD_SCRIPT_DIR, [
            'use_incremental_sync' => $cfg['use_incremental_sync'],
            'hub_fetch_limit' => $cfg['hub_fetch_limit'],
        ]);
    } finally {
        if (is_callable($lrel)) {
            $lrel();
        }
    }

    if (!$result['ok']) {
        simad_browser_json(['status' => 'error', 'message' => $result['error']]);
    }

    simad_touch_last_success($SIMAD_SCRIPT_DIR, $result['hub_last_sync'] ?? null);

    $iu = (int)$_SESSION['user_id'];
    log_activity(
        $iu,
        'update',
        'Sinkron otomatis guru SIMAD: ' . (int)$result['inserted'] . ' baru, ' . (int)$result['updated'] . ' diperbarui, '
            . (int)($result['removed_duplicates'] ?? 0) . ' duplikat dihapus, ' . (int)$result['skipped'] . ' dilewati/gagal'
    );

    simad_browser_json([
        'status' => 'success',
        'message' => 'Sinkron otomatis dari SIMAD selesai.',
        'detail' => 'Ditambah: ' . (int)$result['inserted'] . ' · Diperbarui: ' . (int)$result['updated'] . ' · Duplikat dihapus: '
            . (int)($result['removed_duplicates'] ?? 0) . ' · Dilewati/gagal: ' . (int)$result['skipped'] . ' · total_data hub: ' . (int)$result['from_simad_total'],
        'inserted' => (int)$result['inserted'],
        'updated' => (int)$result['updated'],
        'skipped' => (int)$result['skipped'],
        'removed_duplicates' => (int)($result['removed_duplicates'] ?? 0),
        'from_simad_total' => (int)$result['from_simad_total'],
        'sync_mode' => (string)($result['sync_mode'] ?? ''),
        'hub_last_sync' => isset($result['hub_last_sync']) ? (string)$result['hub_last_sync'] : null,
    ]);
}

if (empty($cfg['api_urls']) || $cfg['api_key'] === '') {
    simad_browser_json([
        'status' => 'error',
        'message' => 'SIMAD tidak dijangkau dari host ini.',
    ]);
}

list($mfpl, $mrel) = simad_acquire_lock($SIMAD_SCRIPT_DIR);
if ($mfpl === null) {
    simad_browser_json(['status' => 'error', 'message' => 'Sinkron sedang berjalan di proses lain. Coba lagi sebentar lagi.']);
}

try {
    $result = simad_run_teacher_merge($conn, $cfg['api_urls'], $cfg['api_key'], $SIMAD_SCRIPT_DIR, [
        'use_incremental_sync' => $cfg['use_incremental_sync'],
        'hub_fetch_limit' => $cfg['hub_fetch_limit'],
    ]);
} finally {
    if (is_callable($mrel)) {
        $mrel();
    }
}

if (!$result['ok']) {
    simad_browser_json(['status' => 'error', 'message' => $result['error']]);
}

simad_touch_last_success($SIMAD_SCRIPT_DIR, $result['hub_last_sync'] ?? null);

$inserted = (int)$result['inserted'];
$updated = (int)$result['updated'];
$skipped = (int)$result['skipped'];
$total = (int)$result['from_simad_total'];
$removed_dup = (int)($result['removed_duplicates'] ?? 0);

log_activity(
    (int)$_SESSION['user_id'],
    'update',
    'Sinkron manual guru SIMAD: ' . $inserted . ' baru, ' . $updated . ' diperbarui, ' . $removed_dup . ' duplikat dihapus, '
        . $skipped . ' dilewati/gagal'
);

simad_browser_json([
    'status' => 'success',
    'message' => 'Sinkronisasi SIMAD selesai.',
    'detail' => "Ditambah: $inserted · Diperbarui: $updated · Duplikat dihapus: $removed_dup · Dilewati/gagal: $skipped · total_data hub: $total",
    'inserted' => $inserted,
    'updated' => $updated,
    'skipped' => $skipped,
    'removed_duplicates' => $removed_dup,
    'from_simad_total' => $total,
    'sync_mode' => (string)($result['sync_mode'] ?? ''),
    'hub_last_sync' => isset($result['hub_last_sync']) ? (string)$result['hub_last_sync'] : null,
]);
