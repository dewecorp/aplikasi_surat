<?php
include 'config.php';

echo "<h2>Migration: Remove file Column</h2>";

$sql = "ALTER TABLE surat_keputusan DROP COLUMN file";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✓ Successfully removed 'file' column from surat_keputusan table!</p>";
} else {
    if (mysqli_errno($conn) == 1091) {
        echo "<p style='color: orange;'>⚠ Column 'file' doesn't exist or already removed.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='surat_keputusan.php'>← Back to Surat Keputusan</a></p>";
?>
