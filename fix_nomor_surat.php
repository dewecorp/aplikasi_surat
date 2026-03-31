<?php
include 'config.php';

// Update semua nomor surat yang berformat SP ke SF
$query = "UPDATE surat_keputusan SET no_surat = REPLACE(no_surat, '/MI.SP/', '/MI.SF/') WHERE no_surat LIKE '%/MI.SP/%'";

if (mysqli_query($conn, $query)) {
    $affected = mysqli_affected_rows($conn);
    echo "<h3>Berhasil memperbarui nomor surat!</h3>";
    echo "<p>Jumlah data yang diperbarui: <strong>" . $affected . "</strong></p>";
    echo "<p><a href='surat_keputusan.php'>Kembali ke halaman Surat Keputusan</a></p>";
} else {
    echo "<h3>Gagal memperbarui nomor surat</h3>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
}
?>
