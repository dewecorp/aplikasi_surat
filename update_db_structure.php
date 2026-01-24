<?php
include 'config.php';

function add_column_if_not_exists($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE `$table` ADD `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "Added column $column to $table.<br>";
        } else {
            echo "Error adding $column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Column $column already exists in $table.<br>";
    }
}

// Add columns for Surat Pindah
add_column_if_not_exists($conn, 'surat_keluar', 'nis_siswa', "VARCHAR(50) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'tempat_lahir_siswa', "VARCHAR(100) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'tgl_lahir_siswa', "DATE DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'jenis_kelamin_siswa', "VARCHAR(20) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'kelas_siswa', "VARCHAR(20) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'nama_wali', "VARCHAR(100) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'pekerjaan_wali', "VARCHAR(100) DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'alamat_wali', "TEXT DEFAULT NULL");
add_column_if_not_exists($conn, 'surat_keluar', 'tujuan_pindah', "VARCHAR(255) DEFAULT NULL");

echo "Database structure update completed.";
?>
