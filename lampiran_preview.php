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

$pdf_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'lampiran' . DIRECTORY_SEPARATOR . $file;
if (!is_file($pdf_path)) {
    http_response_code(404);
    exit('Not Found');
}

$page_count = 0;
if (extension_loaded('imagick') && class_exists('Imagick')) {
    $imClass = 'Imagick';
    $im = new $imClass();
    $im->pingImage($pdf_path);
    $page_count = (int)$im->getNumberImages();
    $im->clear();
    $im->destroy();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lampiran</title>
    <style>
        body { margin: 0; padding: 16px; font-family: system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif; background: #f3f4f6; }
        .page { background: #fff; padding: 8px; margin: 0 auto 16px; max-width: 900px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        img { width: 100%; height: auto; display: block; }
        .note { max-width: 900px; margin: 0 auto; background: #fff; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
    </style>
</head>
<body>
<?php if ($page_count > 0): ?>
    <?php for ($i = 1; $i <= $page_count; $i++): ?>
        <div class="page">
            <img src="<?php echo 'lampiran_image.php?file=' . rawurlencode($file) . '&page=' . $i; ?>" alt="Lampiran halaman <?php echo $i; ?>">
        </div>
    <?php endfor; ?>
<?php else: ?>
    <div class="note">
        Lampiran tidak bisa ditampilkan sebagai gambar di server ini.
        <a href="<?php echo 'view_lampiran.php?file=' . rawurlencode($file); ?>" target="_blank">Unduh/Lihat PDF</a>
    </div>
<?php endif; ?>
</body>
</html>
