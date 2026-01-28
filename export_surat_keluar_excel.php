<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

// Filter Logic
$where = "WHERE 1=1";
if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = mysqli_real_escape_string($conn, $_GET['filter_tahun']);
    $where .= " AND YEAR(tgl_surat) = '$ft'";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = mysqli_real_escape_string($conn, $_GET['filter_bulan']);
    $where .= " AND MONTH(tgl_surat) = '$fb'";
}
if (isset($_GET['filter_penerima']) && !empty($_GET['filter_penerima'])) {
    $fp = mysqli_real_escape_string($conn, $_GET['filter_penerima']);
    $where .= " AND penerima LIKE '%$fp%'";
}
if (isset($_GET['filter_tanggal']) && !empty($_GET['filter_tanggal'])) {
    $ftgl = mysqli_real_escape_string($conn, $_GET['filter_tanggal']);
    $where .= " AND tgl_surat = '$ftgl'";
}

$query = mysqli_query($conn, "SELECT * FROM surat_keluar $where ORDER BY id DESC");

// School Info
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$set = mysqli_fetch_assoc($q_set);

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Surat_Keluar.xls");
?>
<html>
<body>
    <table border="1">
        <tr>
            <td colspan="5" class="header"><?php echo isset($set['nama_madrasah']) ? strtoupper($set['nama_madrasah']) : 'SIMS'; ?></td>
        </tr>
        <tr>
            <td colspan="5" class="sub-header"><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></td>
        </tr>
        <tr>
            <td colspan="5" align="center"><h3>DATA SURAT KELUAR</h3></td>
        </tr>
        <tr>
            <th>No</th>
            <th>No Surat</th>
            <th>Tgl Surat</th>
            <th>Penerima</th>
            <th>Perihal</th>
        </tr>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['no_surat']; ?></td>
            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
            <td><?php echo $row['penerima']; ?></td>
            <td><?php echo $row['perihal']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
