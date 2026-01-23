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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Data Surat Masuk</title>
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
        <h2><?php echo isset($set['nama_madrasah']) ? strtoupper($set['nama_madrasah']) : 'SIMS'; ?></h2>
        <p><?php echo isset($set['alamat']) ? $set['alamat'] : ''; ?></p>
    </div>

    <h3 class="text-center" style="margin-bottom: 20px; font-weight: bold; text-decoration: underline;">DATA SURAT MASUK</h3>

    <table class="table-data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">No Surat</th>
                <th width="15%">Tgl Terima</th>
                <th width="15%">Tgl Surat</th>
                <th width="20%">Pengirim</th>
                <th width="25%">Perihal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td><?php echo $row['no_surat']; ?></td>
                <td align="center"><?php echo tgl_indo($row['tgl_terima']); ?></td>
                <td align="center"><?php echo tgl_indo($row['tgl_surat']); ?></td>
                <td><?php echo $row['pengirim']; ?></td>
                <td><?php echo $row['perihal']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
