<?php
include 'config.php';

if (!isset($_GET['id'])) {
    exit("ID Surat tidak ditemukan");
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM surat_keluar WHERE id='$id'");
$surat = mysqli_fetch_assoc($query);

$q_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$setting = mysqli_fetch_assoc($q_set);

if (!$surat) {
    exit("Surat tidak ditemukan");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Surat - <?php echo $surat['no_surat']; ?></title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            margin: 2cm;
        }
        .kop-surat {
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat h3, .kop-surat h2, .kop-surat p {
            margin: 0;
        }
        .kop-surat h3 { font-size: 14pt; font-weight: bold; }
        .kop-surat h2 { font-size: 16pt; font-weight: bold; }
        .kop-surat p { font-size: 11pt; }
        
        .table-header {
            width: 100%;
            margin-bottom: 20px;
        }
        .table-header td {
            vertical-align: top;
        }
        
        .content {
            text-align: justify;
            line-height: 1.5;
            min-height: 300px;
        }
        
        .ttd {
            float: right;
            text-align: center;
            width: 300px;
            margin-top: 50px;
        }
        
        .ttd img {
            height: 70px;
        }

        @media print {
            @page { margin: 0; }
            body { margin: 2cm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="kop-surat">
        <h3><?php echo strtoupper($setting['nama_yayasan']); ?></h3>
        <h2><?php echo strtoupper($setting['nama_madrasah']); ?></h2>
        <p><?php echo $setting['alamat']; ?></p>
        <p>Email: <?php echo $setting['email']; ?> | Website: <?php echo $setting['website']; ?></p>
    </div>

    <table class="table-header">
        <tr>
            <td width="10%">Nomor</td>
            <td width="2%">:</td>
            <td width="50%"><?php echo $surat['no_surat']; ?></td>
            <td width="38%" style="text-align: right;">
                Sukosono, <?php echo tgl_indo($surat['tgl_surat']); ?>
            </td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td>-</td>
            <td></td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td><b><?php echo $surat['perihal']; ?></b></td>
            <td></td>
        </tr>
    </table>

    <div style="margin-bottom: 20px;">
        Kepada Yth.<br>
        <b><?php echo $surat['penerima']; ?></b><br>
        di Tempat
    </div>

    <div class="content">
        <p><i>Assalamu'alaikum Wr. Wb.</i></p>
        
        <p>Dengan hormat,</p>
        
        <p>Sehubungan dengan <?php echo strtolower($surat['perihal']); ?>, kami mengharap kehadiran Bapak/Ibu/Saudara pada:</p>
        
        <p>[Isi detail acara disini - Edit manual atau tambahkan field detail]</p>
        
        <p>Demikian surat <?php echo strtolower($surat['jenis_surat']); ?> ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
        
        <p><i>Wassalamu'alaikum Wr. Wb.</i></p>
    </div>

    <div class="ttd">
        <p>Kepala Madrasah</p>
        <?php if (!empty($setting['ttd']) && file_exists('uploads/' . $setting['ttd'])): ?>
            <div style="position: relative;">
                <?php if (!empty($setting['stempel']) && file_exists('uploads/' . $setting['stempel'])): ?>
                    <img src="uploads/<?php echo $setting['stempel']; ?>" style="position: absolute; left: -30px; opacity: 0.7; z-index: -1;">
                <?php endif; ?>
                <img src="uploads/<?php echo $setting['ttd']; ?>">
            </div>
        <?php else: ?>
            <br><br><br>
        <?php endif; ?>
        <p><b><u><?php echo $setting['kepala_madrasah']; ?></u></b></p>
    </div>
</body>
</html>
