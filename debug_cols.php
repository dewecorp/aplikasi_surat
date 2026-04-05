<?php
include 'config.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM surat_keputusan LIKE 'created_at'");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>