<?php
if (session_status() == PHP_SESSION_NONE) {
    // Idle timeout aplikasi: user baru dipaksa login ulang setelah tidak aktif > 2 jam.
    $timeout_duration = 2 * 60 * 60;

    // Samakan umur session PHP di server agar tidak terhapus lebih cepat dari aturan idle aplikasi.
    @ini_set('session.gc_maxlifetime', (string)$timeout_duration);

    // Deteksi HTTPS yang lebih aman (cegah false positive pada beberapa konfigurasi server)
    $secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';

    // Konfigurasi Keamanan Sesi dengan Kompatibilitas Versi PHP
    if (PHP_VERSION_ID >= 70300) {
        // PHP 7.3+ mendukung parameter array (termasuk SameSite)
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        // Fallback untuk PHP versi lama (< 7.3)
        session_set_cookie_params(0, '/', '', $secure, true);
    }
    
    // Set nama session unik untuk mencegah konflik
    session_name('SIMS_OK_APP_SESSION');
    session_start();

    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
            // Activity timeout occurred
            session_unset();
            session_destroy();
            
            // Re-initialize session to show message
            session_name('SIMS_OK_APP_SESSION');
            session_start();
            $_SESSION['error'] = "Sesi Anda telah berakhir karena tidak ada aktivitas selama 2 jam.";
            
            // Tidak redirect untuk endpoint JSON (XHR) — kembalikan ke script agar bisa merespons JSON
            $current_file = isset($_SERVER['PHP_SELF']) ? basename((string)$_SERVER['PHP_SELF']) : '';
            $no_login_redirect = in_array($current_file, ['login.php', 'logout.php', 'sync_guru_simad.php'], true);
            if (!$no_login_redirect) {
                header("Location: login.php");
                exit();
            }
        }
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}
?>
