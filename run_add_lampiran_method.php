<?php
include 'config.php';

echo "<h2>Migration: Add Lampiran Method Columns</h2>";

// Add file_lampiran column
$sql1 = "ALTER TABLE surat_keputusan ADD COLUMN file_lampiran VARCHAR(255) AFTER nama_sk";
$result1 = mysqli_query($conn, $sql1);

if ($result1) {
    echo "<p style='color: green;'>✓ Successfully added 'file_lampiran' column!</p>";
} else {
    if (mysqli_errno($conn) == 1060) {
        echo "<p style='color: orange;'>⚠ Column 'file_lampiran' already exists.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding file_lampiran: " . mysqli_error($conn) . "</p>";
    }
}

// Add lampiran_method column
$sql2 = "ALTER TABLE surat_keputusan ADD COLUMN lampiran_method ENUM('ckeditor', 'file') DEFAULT 'ckeditor' AFTER file_lampiran";
$result2 = mysqli_query($conn, $sql2);

if ($result2) {
    echo "<p style='color: green;'>✓ Successfully added 'lampiran_method' column!</p>";
} else {
    if (mysqli_errno($conn) == 1060) {
        echo "<p style='color: orange;'>⚠ Column 'lampiran_method' already exists.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding lampiran_method: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='surat_keputusan.php'>← Back to Surat Keputusan</a></p>";
?>
