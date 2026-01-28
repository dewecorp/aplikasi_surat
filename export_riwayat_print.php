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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Riwayat Surat</title>
    <link href="assets/plugins/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; padding: 20px; color: #000; background: #fff; }
        .header-kop { border-bottom: 3px double black; margin-bottom: 20px; padding-bottom: 10px; text-align: center; position: relative; min-height: 70px; }
        .header-kop img { width: 60px; position: absolute; left: 10px; top: 0; }
        .header-kop h2 { margin: 0; font-weight: bold; font-size: 24px; text-transform: uppercase; }
        .header-kop p { margin: 0; font-size: 14px; }
        .table-data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-data th, .table-data td { border: 1px solid black; padding: 4px; font-size: 11px; }
        .table-data th { background-color: #eee; text-align: center; font-weight: bold; }
        .filter-info { margin-bottom: 10px; font-size: 12px; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header-kop">
        <?php if(!empty($set['logo']) && file_exists('assets/images/'.$set['logo'])): ?>
            <img src="assets/images/<?php echo $set['logo']; ?>" alt="Logo">
        <?php endif; ?>
        <h2><?php echo isset($set['nama_madrasah']) ? $set['nama_madrasah'] : 'SIMS'; ?></h2>
        <p><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></p>
        <div style="clear: both;"></div>
    </div>
    
    <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 18px; font-weight: bold; text-decoration: underline;">DATA RIWAYAT SURAT</h3>
        <?php if(!empty($filters)): ?>
            <p style="margin: 5px 0 0 0; font-size: 12px;">Filter: <?php echo implode(', ', $filters); ?></p>
        <?php endif; ?>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Tipe</th>
                <th width="20%">No Surat</th>
                <th width="15%">Tgl Surat</th>
                <th width="25%">Perihal</th>
                <th width="25%">Penerima/Pengirim</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td align="center"><?php echo $row['tipe']; ?></td>
                <td><?php echo $row['no_surat']; ?></td>
                <td align="center"><?php echo tgl_indo($row['tgl_surat']); ?></td>
                <td><?php echo $row['perihal']; ?></td>
                <td><?php echo $row['pihak_lain']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>