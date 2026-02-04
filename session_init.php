<?php
if (session_status() == PHP_SESSION_NONE) {
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
}
?>