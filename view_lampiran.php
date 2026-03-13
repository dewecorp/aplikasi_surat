<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$file = $_GET['file'] ?? '';
$file = is_string($file) ? $file : '';
$file = rawurldecode($file);
$file = basename($file);

if ($file === '' || !preg_match('/\.pdf$/i', $file)) {
    http_response_code(400);
    exit('Bad Request');
}

$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'lampiran' . DIRECTORY_SEPARATOR . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . str_replace('"', '', $file) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($path);
exit;

