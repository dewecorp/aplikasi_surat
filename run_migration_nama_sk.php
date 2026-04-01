<?php
include 'config.php';

echo "<h2>Migration: Add nama_sk Column</h2>";

$sql = "ALTER TABLE surat_keputusan ADD COLUMN nama_sk VARCHAR(255) AFTER file";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✓ Successfully added 'nama_sk' column to surat_keputusan table!</p>";
} else {
    if (mysqli_errno($conn) == 1060) {
        echo "<p style='color: orange;'>⚠ Column 'nama_sk' already exists.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='surat_keputusan.php'>← Back to Surat Keputusan</a></p>";
?>
