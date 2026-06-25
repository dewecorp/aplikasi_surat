<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$file = '';
$display_name = '';

if ($id > 0) {
    $q = mysqli_query($conn, "SELECT no_surat, perihal, file FROM surat_masuk WHERE id='$id' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    if ($row) {
        $file = basename((string)$row['file']);
        $display_name = trim((string)$row['perihal']);
    }
}

if ($file === '') {
    $file = $_GET['file'] ?? '';
    $file = is_string($file) ? rawurldecode($file) : '';
    $file = basename($file);
}

$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if ($file === '' || !in_array($extension, $allowed_extensions, true)) {
    http_response_code(400);
    exit('File tidak valid.');
}

$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

function surat_masuk_original_filename($file) {
    $name = pathinfo($file, PATHINFO_FILENAME);
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    $name = preg_replace('/^\d{10,13}[_-]+/', '', $name);
    $name = preg_replace('/^[a-f0-9]{8,32}[_-]+/i', '', $name);
    $name = trim(str_replace('_', ' ', $name));

    if ($name === '') {
        $name = pathinfo($file, PATHINFO_FILENAME);
    }

    return $name . ($ext !== '' ? '.' . $ext : '');
}

$original_filename = surat_masuk_original_filename($file);
$document_title = $display_name !== '' ? $display_name : $original_filename;
$mime_type = $extension === 'pdf' ? 'application/pdf' : (in_array($extension, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png');
$file_data = base64_encode((string)file_get_contents($path));
$file_src = 'data:' . $mime_type . ';base64,' . $file_data;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($document_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        html,
        body {
            margin: 0;
            height: 100%;
            background: #f2f4f8;
            color: #111827;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            background: #ffffff;
            border-bottom: 1px solid #d9dee8;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        }
        .filename {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 700;
        }
        .print-button {
            border: 0;
            border-radius: 4px;
            background: #f6c23e;
            color: #1f2937;
            cursor: pointer;
            font-weight: 700;
            padding: 8px 14px;
        }
        .pdf-frame {
            flex: 1;
            width: 100%;
            min-height: 0;
            border: 0;
            background: #ffffff;
        }
        .preview {
            flex: 1;
            padding: 22px;
            text-align: center;
            overflow: auto;
        }
        .preview img {
            display: inline-block;
            max-width: 100%;
            height: auto;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .14);
        }
        @media print {
            body { background: #ffffff; }
            .toolbar { display: none; }
            .pdf-frame { height: 100vh; }
            .preview { padding: 0; }
            .preview img { width: 100%; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="filename"><?php echo htmlspecialchars($original_filename, ENT_QUOTES, 'UTF-8'); ?></div>
        <button type="button" class="print-button" onclick="window.print()">Cetak</button>
    </div>
    <?php if ($extension === 'pdf'): ?>
        <iframe class="pdf-frame" src="<?php echo $file_src; ?>#toolbar=1&navpanes=0" title="<?php echo htmlspecialchars($original_filename, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 800);
            });
        </script>
    <?php else: ?>
    <div class="preview">
        <img src="<?php echo $file_src; ?>" alt="Preview surat masuk">
    </div>
    <?php endif; ?>
</body>
</html>
