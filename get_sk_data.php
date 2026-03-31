<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = mysqli_query($conn, "SELECT * FROM surat_keputusan WHERE id = '$id'");
    $data = mysqli_fetch_assoc($query);
    
    if ($data) {
        echo json_encode($data);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
} else {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'ID tidak diberikan']);
}
?>