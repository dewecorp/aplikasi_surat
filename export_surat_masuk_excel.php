<?php
include 'config.php';

// Filter Logic
$where = "WHERE 1=1";
if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = $_GET['filter_tahun'];
    $where .= " AND YEAR(tgl_terima) = '$ft'";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = $_GET['filter_bulan'];
    $where .= " AND MONTH(tgl_terima) = '$fb'";
}
if (isset($_GET['filter_pengirim']) && !empty($_GET['filter_pengirim'])) {
    $fp = mysqli_real_escape_string($conn, $_GET['filter_pengirim']);
    $where .= " AND pengirim LIKE '%$fp%'";
}

$query = mysqli_query($conn, "SELECT * FROM surat_masuk $where ORDER BY id DESC");

// School Info
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$set = mysqli_fetch_assoc($q_set);

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Surat_Masuk.xls");
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
            <td colspan="6" align="center"><h3>DATA SURAT MASUK</h3></td>
        </tr>
        <tr>
            <th>No</th>
            <th>No Surat</th>
            <th>Tgl Terima</th>
            <th>Tgl Surat</th>
            <th>Pengirim</th>
            <th>Perihal</th>
        </tr>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['no_surat']; ?></td>
            <td><?php echo tgl_indo($row['tgl_terima']); ?></td>
            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
            <td><?php echo $row['pengirim']; ?></td>
            <td><?php echo $row['perihal']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
