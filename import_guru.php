<?php
session_start();
include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang diupload']);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];

// Validate extension
$extension = pathinfo($fileName, PATHINFO_EXTENSION);
if (!in_array(strtolower($extension), ['xls', 'xlsx'])) {
    echo json_encode(['status' => 'error', 'message' => 'Format file harus Excel (.xls, .xlsx)']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    // Remove header
    array_shift($rows);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($rows as $row) {
        // Skip empty rows (check required fields)
        if (empty($row[1])) continue; // Nama Guru is required
        
        $nuptk = mysqli_real_escape_string($conn, $row[0] ?? '');
        $nama = mysqli_real_escape_string($conn, $row[1] ?? '');
        $jk = strtoupper(mysqli_real_escape_string($conn, $row[2] ?? ''));
        $tempat_lahir = mysqli_real_escape_string($conn, $row[3] ?? '');
        $tgl_lahir = mysqli_real_escape_string($conn, $row[4] ?? '');
        $status = mysqli_real_escape_string($conn, $row[5] ?? '');
        
        // Format Date if needed (Excel might return different format)
        if (!empty($tgl_lahir)) {
             // Attempt to convert excel date number if numeric
             if (is_numeric($tgl_lahir)) {
                 $tgl_lahir = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tgl_lahir)->format('Y-m-d');
             }
        }

        // Insert or Update Logic
        $id_update = null;
        $is_update = false;

        // 1. Cek berdasarkan NUPTK jika ada
        if (!empty($nuptk)) {
            $check = mysqli_query($conn, "SELECT id FROM guru WHERE nuptk = '$nuptk'");
            if (mysqli_num_rows($check) > 0) {
                $r = mysqli_fetch_assoc($check);
                $id_update = $r['id'];
                $is_update = true;
            }
        }
        
        // 2. Jika tidak ditemukan berdasarkan NUPTK, cek berdasarkan Nama + Tanggal Lahir
        // Ini untuk menangani kasus NUPTK kosong atau perubahan NUPTK pada orang yang sama
        if (!$is_update && !empty($nama) && !empty($tgl_lahir)) {
             $check = mysqli_query($conn, "SELECT id FROM guru WHERE nama = '$nama' AND tgl_lahir = '$tgl_lahir'");
             if (mysqli_num_rows($check) > 0) {
                 $r = mysqli_fetch_assoc($check);
                 $id_update = $r['id'];
                 $is_update = true;
             }
        }

        if ($is_update) {
            // Update existing data
            $query = "UPDATE guru SET nuptk='$nuptk', nama='$nama', jk='$jk', tempat_lahir='$tempat_lahir', tgl_lahir='$tgl_lahir', status='$status' WHERE id='$id_update'";
        } else {
            // Insert new data
            $query = "INSERT INTO guru (nuptk, nama, jk, tempat_lahir, tgl_lahir, status) VALUES ('$nuptk', '$nama', '$jk', '$tempat_lahir', '$tgl_lahir', '$status')";
        }
        
        if (mysqli_query($conn, $query)) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    if ($successCount > 0) {
        $_SESSION['success'] = "Berhasil import $successCount data. Gagal: $errorCount";
        echo json_encode([
            'status' => 'success', 
            'message' => "Berhasil import $successCount data."
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => "Tidak ada data yang berhasil diimport. Gagal: $errorCount"
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses file: ' . $e->getMessage()]);
}
?>
