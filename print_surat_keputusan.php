<?php
include 'config.php';

if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM surat_keputusan WHERE id = '$id'");
$sk = mysqli_fetch_assoc($query);

if (!$sk) {
    die("Data tidak ditemukan");
}

$q_instansi = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$instansi = mysqli_fetch_assoc($q_instansi);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Surat Keputusan</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        .container { width: 80%; margin: 0 auto; }
        .header { text-align: center; }
        .header h3, .header h4 { margin: 0; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .label { width: 15%; }
        .separator { width: 5%; }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="header">
            <h3>KEPUTUSAN</h3>
            <h4>KEPALA <?php echo strtoupper($instansi['nama_madrasah']); ?></h4>
            <p>NOMOR : <?php echo $sk['no_surat']; ?></p>
            <br>
            <h4>TENTANG</h4>
            <div style="text-transform: uppercase; font-weight: bold;"><?php echo $sk['tentang']; ?></div>
            <br>
            <h4>KEPALA <?php echo strtoupper($instansi['nama_madrasah']); ?></h4>
        </div>

        <div class="content">
            <table>
                <tr>
                    <td class="label">Menimbang</td>
                    <td class="separator">:</td>
                    <td><?php echo $sk['menimbang']; ?></td>
                </tr>
                <tr>
                    <td class="label">Mengingat</td>
                    <td class="separator">:</td>
                    <td><?php echo $sk['mengingat']; ?></td>
                </tr>
                <tr>
                    <td class="label">Memperhatikan</td>
                    <td class="separator">:</td>
                    <td><?php echo $sk['memperhatikan']; ?></td>
                </tr>
            </table>

            <br>
            <h4 style="text-align:center;">MEMUTUSKAN</h4>
            <br>

            <table>
                <tr>
                    <td class="label">Menetapkan</td>
                    <td class="separator">:</td>
                    <td>
                        <?php 
                        $menetapkan = json_decode($sk['menetapkan']);
                        if ($menetapkan) {
                            foreach ($menetapkan as $key => $value) {
                                echo ($key+1) . '. ' . $value . '<br>';
                            }
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <?php if ($sk['lampiran']) : ?>
                <page_break></page_break>
                <div><?php echo $sk['lampiran']; ?></div>
            <?php endif; ?>

            <br><br>

            <div style="width: 30%; float: right; text-align: center;">
                <p>Ditetapkan di : <?php echo $instansi['alamat']; ?></p>
                <p>Pada tanggal : <?php echo tgl_indo($sk['tgl_surat']); ?></p>
                <p>Kepala Madrasah</p>
                <br><br><br>
                <p><b><u><?php echo $instansi['kepala_madrasah']; ?></u></b></p>
            </div>
        </div>
    </div>
</body>
</html>