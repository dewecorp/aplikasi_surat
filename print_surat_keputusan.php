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
        @page {
            size: 21.5cm 33cm; /* F4/Folio */
            margin: 1.5cm; /* All sides: top, right, bottom, left */
        }
        body { 
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif; 
            font-size: 12pt;
            margin: 0;
            padding: 0;
        }
        .container { 
            width: 100%; 
            margin: 0 auto;
            max-width: 18cm; /* Ensure content doesn't exceed printable area */
        }
        
        /* Kop Surat Styles */
        .kop-surat {
            position: relative;
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 10px;
            margin-bottom: 20px;
            padding-left: 90px;
            min-height: 90px;
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: auto;
        }
        .kop-surat h3 { 
            font-size: 12pt; 
            font-weight: normal; 
            margin: 0;
            text-transform: uppercase;
        }
        .kop-surat p {
            margin: 0;
            font-size: 11pt;
        }
        .kop-surat h2 { 
            font-size: 14pt; 
            font-weight: bold; 
            margin: 5px 0;
            font-family: serif; 
        }
        
        .header { text-align: center; }
        .header h3, .header h4 { 
            margin: 0;
            font-size: 12pt;
        }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .label { 
            width: 15%;
            text-align: left;
        }
        .separator { 
            width: 2%;
            text-align: left;
        }
        .content-cell {
            width: 83%;
        }
        /* Style for menimbang, mengingat, memperhatikan content */
        .content-cell ol {
            margin: 0;
            padding-left: 20px;
        }
        .content-cell ol li {
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        
        /* Style for menetapkan section */
        .menetapkan-content {
            text-align: justify;
        }
        .menetapkan-content ol {
            margin: 0;
            padding-left: 20px;
        }
        .menetapkan-content ol li {
            margin: 0;
            padding: 0;
            line-height: 1.5;
            text-align: justify;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <!-- Kop Surat -->
        <div class="kop-surat">
            <?php
            $logo_src = "assets/images/logo.png";
            if (!empty($instansi['logo']) && file_exists("assets/images/" . $instansi['logo'])) {
                $logo_src = "assets/images/" . $instansi['logo'];
            }
            ?>
            <img src="<?php echo $logo_src; ?>" class="logo" alt="Logo" onerror="this.style.display='none'">
            
            <h3><?php echo strtoupper($instansi['nama_yayasan']); ?></h3>
            <h2><?php echo strtoupper($instansi['nama_madrasah']); ?></h2>
            <p style="font-style: italic;"><?php echo $instansi['alamat']; ?></p>
        </div>
        
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
                    <td class="content-cell"><?php echo $sk['menimbang']; ?></td>
                </tr>
                <tr>
                    <td class="label">Mengingat</td>
                    <td class="separator">:</td>
                    <td class="content-cell"><?php echo $sk['mengingat']; ?></td>
                </tr>
                <tr>
                    <td class="label">Memperhatikan</td>
                    <td class="separator">:</td>
                    <td class="content-cell"><?php echo $sk['memperhatikan']; ?></td>
                </tr>
            </table>

            <br>
            <h4 style="text-align:center;">MEMUTUSKAN</h4>
            <br>

            <table>
                <tr>
                    <td class="label">Menetapkan</td>
                    <td class="separator">:</td>
                    <td class="content-cell">
                        <div class="menetapkan-content">
                            <?php 
                            $menetapkan = json_decode($sk['menetapkan']);
                            if ($menetapkan && is_array($menetapkan)) {
                                echo '<ol>';
                                foreach ($menetapkan as $value) {
                                    echo '<li>' . $value . '</li>';
                                }
                                echo '</ol>';
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            </table>

            <br><br>
            
            <!-- Ditetapkan Section -->
            <div style="text-align: right; margin-bottom: 20px;">
                <p style="margin: 0;">Ditetapkan di : Sukosono</p>
                <p style="margin: 0;">Pada tanggal : <?php echo tgl_indo($sk['tgl_surat']); ?></p>
                <br>
                <p style="margin: 0;">Kepala Madrasah,</p>
                <?php 
                $ttd_tipe = isset($instansi['ttd_tipe']) ? $instansi['ttd_tipe'] : 'image';
                if ($ttd_tipe == 'qr'): 
                    $qr_content = "Validasi Surat Keputusan\nNomor: " . $sk['no_surat'] . "\nTanggal: " . tgl_indo($sk['tgl_surat']) . "\nKepala: " . $instansi['kepala_madrasah'];
                    $qr_url = "https://quickchart.io/qr?text=" . urlencode($qr_content) . "&size=120";
                ?>
                    <br>
                    <img src="<?php echo $qr_url; ?>" style="width: 100px; height: 100px; margin: 0 auto; display: block;">
                    <br>
                <?php elseif (!empty($instansi['ttd']) && file_exists('uploads/' . $instansi['ttd'])): ?>
                    <img src="uploads/<?php echo $instansi['ttd']; ?>" style="width: 120px; height: auto; margin-top: 5px; display: block; margin-left: auto; margin-right: auto;">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>

                <?php if ($ttd_tipe != 'qr' && !empty($instansi['stempel']) && file_exists('uploads/' . $instansi['stempel'])): ?>
                    <img src="uploads/<?php echo $instansi['stempel']; ?>" style="width: 80px; height: 80px; position: absolute; margin-left: -40px; margin-top: -70px; opacity: 0.8;">
                <?php endif; ?>

                <p style="margin: 0; font-weight: bold; text-decoration: underline;"><?php echo strtoupper($instansi['kepala_madrasah']); ?></p>
            </div>

            <?php if ($sk['lampiran']) : ?>
                <page_break></page_break>
                <div><?php echo $sk['lampiran']; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>