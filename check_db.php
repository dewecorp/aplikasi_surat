<?php
include 'config.php';

echo "Table: surat_keluar\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM surat_keluar");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nTable: pengaturan\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM pengaturan");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>