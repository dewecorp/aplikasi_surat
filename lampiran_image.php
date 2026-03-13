<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (!extension_loaded('imagick') || !class_exists('Imagick')) {
    http_response_code(501);
    exit('Imagick not available');
}

$file = $_GET['file'] ?? '';
$file = is_string($file) ? $file : '';
$file = rawurldecode($file);
$file = basename($file);

$page = $_GET['page'] ?? '';
$page = is_string($page) ? $page : '';
$page_num = (int)$page;

if ($file === '' || !preg_match('/\.pdf$/i', $file) || $page_num < 1) {
    http_response_code(400);
    exit('Bad Request');
}

$pdf_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'lampiran' . DIRECTORY_SEPARATOR . $file;
if (!is_file($pdf_path)) {
    http_response_code(404);
    exit('Not Found');
}

$cache_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'lampiran_render' . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME);
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}
$cache_path = $cache_dir . DIRECTORY_SEPARATOR . 'page_' . $page_num . '.png';

if (!is_file($cache_path)) {
    $index = $page_num - 1;
    $imClass = 'Imagick';
    $img = new $imClass();
    $img->setResolution(150, 150);
    $img->readImage($pdf_path . '[' . $index . ']');
    $img->setImageFormat('png');
    if (method_exists($img, 'flattenImages')) {
        $img = $img->flattenImages();
    }
    $img->writeImage($cache_path);
    $img->clear();
    $img->destroy();
}

header('Content-Type: image/png');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($cache_path));
header('Cache-Control: private, max-age=86400');
readfile($cache_path);
exit;
