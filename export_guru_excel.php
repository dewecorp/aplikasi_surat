<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("CSRF Token Verification Failed");
}

// Check for sorting or filtering if added later (currently all data)
$query = mysqli_query($conn, "SELECT * FROM guru ORDER BY nama ASC");

// School Info
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$set = mysqli_fetch_assoc($q_set);

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Guru.xls");
?>
<html>
<body>
    <table border="1">
        <tr>
            <td colspan="6" class="header"><?php echo isset($set['nama_madrasah']) ? strtoupper($set['nama_madrasah']) : 'SIMS'; ?></td>
        </tr>
        <tr>
            <td colspan="6" class="sub-header"><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></td>
        </tr>
        <tr>
            <td colspan="6" align="center"><h3>DATA GURU</h3></td>
        </tr>
        <tr>
            <th>No</th>
            <th>NUPTK</th>
            <th>Nama Guru</th>
            <th>L/P</th>
            <th>TTL</th>
            <th>Status</th>
        </tr>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td style="mso-number-format:'\@';"><?php echo $row['nuptk']; ?></td>
            <td><?php echo $row['nama']; ?></td>
            <td><?php echo $row['jk']; ?></td>
            <td><?php echo $row['tempat_lahir'] . ', ' . (!empty($row['tgl_lahir']) ? date('d-m-Y', strtotime($row['tgl_lahir'])) : ''); ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
