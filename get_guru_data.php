<?php
include 'config.php';

if (isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    // Validasi IDs agar aman dari SQL Injection
    $id_array = explode(',', $ids);
    $clean_ids = implode(',', array_map('intval', $id_array));

    $query = mysqli_query($conn, "SELECT * FROM guru WHERE id IN ($clean_ids)");
    $data = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    echo json_encode($data);
}
?>
