<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

// Filter Logic
$where_masuk = "WHERE 1=1";
$where_keluar = "WHERE 1=1";
$filters = [];

if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = mysqli_real_escape_string($conn, $_GET['filter_tahun']);
    $where_masuk .= " AND YEAR(tgl_surat) = '$ft'";
    $where_keluar .= " AND YEAR(tgl_surat) = '$ft'";
    $filters[] = "Tahun: $ft";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = mysqli_real_escape_string($conn, $_GET['filter_bulan']);
    $where_masuk .= " AND MONTH(tgl_surat) = '$fb'";
    $where_keluar .= " AND MONTH(tgl_surat) = '$fb'";
    $filters[] = "Bulan: $fb";
}
if (isset($_GET['filter_pihak']) && !empty($_GET['filter_pihak'])) {
    $fp = mysqli_real_escape_string($conn, $_GET['filter_pihak']);
    $where_masuk .= " AND pengirim LIKE '%$fp%'";
    $where_keluar .= " AND penerima LIKE '%$fp%'";
    $filters[] = "Pihak: $fp";
}

// Get Data
$query_sql = "SELECT 'Masuk' as tipe, id, tgl_surat, no_surat, perihal, pengirim as pihak_lain, file, created_at FROM surat_masuk $where_masuk
              UNION ALL
              SELECT 'Keluar' as tipe, id, tgl_surat, no_surat, perihal, penerima as pihak_lain, '' as file, created_at FROM surat_keluar $where_keluar
              ORDER BY tgl_surat DESC, id DESC";
$query = mysqli_query($conn, $query_sql);

// Get Settings
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$set = mysqli_fetch_assoc($q_set);

// Excel Headers
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Riwayat_Surat.xls");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 5px; }
        .header { text-align: center; font-weight: bold; font-size: 14pt; }
        .sub-header { text-align: center; font-size: 11pt; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="6" class="header"><?php echo isset($set['nama_madrasah']) ? strtoupper($set['nama_madrasah']) : 'SIMS'; ?></td>
        </tr>
        <tr>
            <td colspan="6" class="sub-header"><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></td>
        </tr>
        <tr>
            <td colspan="6" class="header">DATA RIWAYAT SURAT</td>
        </tr>
        <tr>
            <td colspan="6">Filter: <?php echo !empty($filters) ? implode(', ', $filters) : 'Semua Data'; ?></td>
        </tr>
        <tr>
            <th>No</th>
            <th>Tipe</th>
            <th>No Surat</th>
            <th>Tgl Surat</th>
            <th>Perihal</th>
            <th>Penerima/Pengirim</th>
        </tr>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['tipe']; ?></td>
            <td><?php echo $row['no_surat']; ?></td>
            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
            <td><?php echo $row['perihal']; ?></td>
            <td><?php echo $row['pihak_lain']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>