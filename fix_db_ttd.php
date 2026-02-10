<?php
include 'config.php';

// Cek apakah kolom ttd_tipe sudah ada
$check = mysqli_query($conn, "SHOW COLUMNS FROM pengaturan LIKE 'ttd_tipe'");
if (mysqli_num_rows($check) == 0) {
    // Kolom belum ada, tambahkan
    $query = "ALTER TABLE pengaturan ADD COLUMN ttd_tipe ENUM('image', 'qr') DEFAULT 'image'";
    if (mysqli_query($conn, $query)) {
        echo "Berhasil menambahkan kolom 'ttd_tipe' ke tabel 'pengaturan'.<br>";
        echo "Silakan kembali ke halaman Pengaturan dan simpan ulang.";
    } else {
        echo "Gagal menambahkan kolom: " . mysqli_error($conn);
    }
} else {
    echo "Kolom 'ttd_tipe' sudah ada. Tidak perlu perbaikan.";
}
?>