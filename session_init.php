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

    // Session Timeout Logic: 2 hours (7200 seconds)
    $timeout_duration = 7200; 

    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
            // Activity timeout occurred
            session_unset();
            session_destroy();
            
            // Re-initialize session to show message
            session_name('SIMS_OK_APP_SESSION');
            session_start();
            $_SESSION['error'] = "Sesi Anda telah berakhir karena tidak ada aktivitas selama 2 jam.";
            
            // If not on login.php or logout.php, redirect to login
            $current_file = basename($_SERVER['PHP_SELF']);
            if ($current_file !== 'login.php' && $current_file !== 'logout.php') {
                header("Location: login.php");
                exit();
            }
        }
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}
?>