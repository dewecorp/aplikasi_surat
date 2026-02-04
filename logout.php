<?php
require_once 'session_init.php';
include 'config.php';

if (isset($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'logout', 'Logout dari sistem');
}

session_destroy();
session_name('SIMS_OK_APP_SESSION');
session_start();
$_SESSION['success'] = "Anda berhasil logout.";
header("Location: login.php");
exit();
?>