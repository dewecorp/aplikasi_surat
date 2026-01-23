<?php
include 'config.php';
$q = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$r = mysqli_fetch_assoc($q);
print_r($r);
?>