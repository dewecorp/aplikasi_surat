<?php
include 'config.php';

$query = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");

// School Info
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$set = mysqli_fetch_assoc($q_set);

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Pengguna.xls");
?>
<html>
<body>
    <table border="1">
        <tr>
            <td colspan="4" class="header"><?php echo isset($set['nama_madrasah']) ? strtoupper($set['nama_madrasah']) : 'SIMS'; ?></td>
        </tr>
        <tr>
            <td colspan="4" class="sub-header"><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></td>
        </tr>
        <tr>
            <td colspan="4" align="center"><h3>DATA PENGGUNA</h3></td>
        </tr>
        <tr>
            <th>No</th>
            <th>Nama Lengkap</th>
            <th>Username</th>
            <th>Role</th>
        </tr>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['nama']; ?></td>
            <td style="mso-number-format:'\@';"><?php echo $row['username']; ?></td>
            <td><?php echo ($row['role'] == 'admin') ? 'Admin' : 'Tata Usaha'; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
