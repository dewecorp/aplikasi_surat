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
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
            background: white;
        }
        
        .letter-container {
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
        }
        
        .kop-surat {
            position: relative;
            text-align: center;
            border-bottom: 3px double black;
            padding-bottom: 10px;
            margin-bottom: 20px;
            padding-left: 90px; /* Add padding to prevent text overlap with logo */
            min-height: 90px; /* Ensure minimum height for logo */
        }
        .logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
            height: auto;
        }
        .kop-surat h3 { 
            font-size: 14pt; 
            font-weight: normal; 
            margin: 0;
            text-transform: uppercase;
        }
        .kop-surat p {
            margin: 0;
            font-size: 11pt;
        }
        .kop-surat h2 { 
            font-size: 20pt; 
            font-weight: bold; 
            margin: 5px 0;
            font-family: serif; 
        }
        .kop-surat .sub-header {
            font-size: 14pt;
            font-weight: bold;
            font-style: italic;
            margin: 0;
        }
        
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-table td {
            vertical-align: top;
        }
        
        .content {
            text-align: justify;
            line-height: 1.5;
        }
        
        .detail-table {
            width: 100%;
            margin-left: 30px;
            margin-bottom: 10px;
        }
        .detail-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        
        .ttd {
            float: right;
            text-align: center;
            width: 300px;
            margin-top: 20px;
            position: relative;
        }
        .ttd img.stempel {
            position: absolute;
            left: 20px;
            top: 20px;
            width: 170px;
            opacity: 0.8;
            transform: rotate(-5deg);
            z-index: 2;
        }
        .ttd img.ttd-img {
            height: 100px;
            margin-top: 5px;
            margin-bottom: 0px;
            position: relative;
            z-index: 1;
        }
        .ttd p {
            margin: 5px 0;
        }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }

        /* Styling for tables inside content (CKEditor) */
        .content table:not(.detail-table) {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .content table:not(.detail-table) th, 
        .content table:not(.detail-table) td {
            border: 1px solid black;
            padding: 5px;
            vertical-align: top;
        }
    </style>
</head>
<body onload="window.print()">
    <div id="print-area">
        <?php
        $penerima_list = [$surat['penerima']];
        $tugas_recipients = [];
        
        if ($surat['jenis_surat'] == 'Tugas') {
            // Check delimiter: try semicolon first (new format), then comma (old format)
            if (strpos($surat['penerima'], ';') !== false) {
                 $split_penerima = explode(';', $surat['penerima']);
            } else {
                 // Fallback for old data or single entry without delimiter
                 $split_penerima = explode(',', $surat['penerima']);
            }
            $tugas_recipients = array_filter(array_map('trim', $split_penerima));
            if (empty($tugas_recipients)) {
                $tugas_recipients = [$surat['penerima']];
            }
            // For Tugas, we want ONE letter with ALL recipients
            $penerima_list = ['ALL_RECIPIENTS'];
        }
        
        foreach ($penerima_list as $idx => $p_loop_item):
            // Use original string for non-Tugas to preserve formatting if any
            if ($surat['jenis_surat'] != 'Tugas') {
                $p_nama = $surat['penerima'];
            } else {
                $p_nama = $surat['penerima'];
            }
        ?>
        <div class="letter-container" <?php if ($idx < count($penerima_list) - 1) echo 'style="page-break-after: always;"'; ?>>
            <div class="kop-surat">
                <!-- Logo -->
                <?php
                $logo_src = "assets/images/logo.png";
                if (!empty($setting['logo']) && file_exists("assets/images/" . $setting['logo'])) {
                    $logo_src = "assets/images/" . $setting['logo'];
                }
                ?>
                <img src="<?php echo $logo_src; ?>" class="logo" alt="Logo" onerror="this.style.display='none'">
                
                <h3><?php echo strtoupper($setting['nama_yayasan']); ?></h3>
                <h2><?php echo strtoupper($setting['nama_madrasah']); ?></h2>
                <p style="font-style: italic;"><?php echo $setting['alamat']; ?></p>
            </div>

            <?php if ($surat['jenis_surat'] == 'Tugas'): ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; text-decoration: underline; font-weight: bold;">SURAT TUGAS</h3>
                    <p style="margin: 0;">Nomor : <?php echo $surat['no_surat']; ?></p>
                </div>
                
                <div style="margin-left: 0;">
                    <p>Yang bertanda tangan di bawah ini:</p>
                    <table class="detail-table" style="margin-left: 20px;">
                        <tr>
                            <td width="25%">Nama</td>
                            <td width="2%">:</td>
                            <td><b><?php echo $setting['kepala_madrasah']; ?></b></td>
                        </tr>
                        <tr>
                            <td>NIP</td>
                            <td>:</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>:</td>
                            <td>Kepala Madrasah</td>
                        </tr>
                        <tr>
                            <td>Unit Kerja</td>
                            <td>:</td>
                            <td><?php echo $setting['nama_madrasah']; ?></td>
                        </tr>
                    </table>

                    <p>Dengan ini menugaskan Kepada :</p>
                    
                    <div style="margin-bottom: 15px; margin-left: 0;">
                        <table class="detail-table" style="width: 100%; border-collapse: collapse; margin-left: 0;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid black; padding: 5px; text-align: center; width: 5%;">No</th>
                                    <th style="border: 1px solid black; padding: 5px; text-align: center;">Nama</th>
                                    <th style="border: 1px solid black; padding: 5px; text-align: center;">NIP/NUPTK</th>
                                    <th style="border: 1px solid black; padding: 5px; text-align: center;">Jabatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($tugas_recipients)) {
                                    echo '<tr><td colspan="4" style="text-align:center; padding: 10px;"><i>Data penerima kosong/belum diinput. Silakan edit surat ini.</i></td></tr>';
                                }
                                $no_tugas = 1;
                                foreach ($tugas_recipients as $t_nama): 
                                    $q_guru = mysqli_query($conn, "SELECT * FROM guru WHERE nama = '".mysqli_real_escape_string($conn, $t_nama)."'");
                                    $d_guru = mysqli_fetch_assoc($q_guru);
                                    $nip = ($d_guru && !empty($d_guru['nuptk'])) ? $d_guru['nuptk'] : '-';
                                    $jabatan = ($d_guru && !empty($d_guru['status'])) ? $d_guru['status'] : 'Guru';
                                ?>
                                <tr>
                                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $no_tugas++; ?></td>
                                    <td style="border: 1px solid black; padding: 5px;"><?php echo $t_nama; ?></td>
                                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $nip; ?></td>
                                    <td style="border: 1px solid black; padding: 5px; text-align: center;"><?php echo $jabatan; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <p style="text-indent: 50px; text-align: justify;">
                        Orang tersebut diatas kami tugaskan untuk mengikuti <?php echo $surat['perihal']; ?> 
                        di <?php echo $surat['acara_tempat']; ?> 
                        Pada Hari 
                        <?php 
                        $tgl = $surat['acara_hari_tanggal'];
                        if (strpos($tgl, ' s.d ') !== false) {
                            $parts = explode(' s.d ', $tgl);
                            echo hari_indo($parts[0]) . ', ' . tgl_indo($parts[0]) . ' s.d ' . hari_indo($parts[1]) . ', ' . tgl_indo($parts[1]);
                        } else {
                            echo hari_indo($tgl) . ', ' . tgl_indo($tgl);
                        }
                        ?>.
                    </p>
                    
                    <p style="text-indent: 50px; text-align: justify;">Demikian surat penugasan ini dikeluarkan untuk dapat dilaksanakan dengan baik dan penuh rasa tanggung jawab</p>
                </div>
            <?php else: ?>
                <!-- NON TUGAS HEADER -->
                <table class="meta-table">
                    <tr>
                        <td width="10%">Nomor</td>
                        <td width="2%">:</td>
                        <td width="48%"><?php echo $surat['no_surat']; ?></td>
                        <td width="40%" style="text-align: right;">
                            <?php echo tgl_indo($surat['tgl_surat']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Perihal</td>
                        <td>:</td>
                        <td style="text-decoration: underline; font-weight: bold;">
                            <?php echo $surat['perihal']; ?>
                        </td>
                        <td></td>
                    </tr>
                </table>

                <div style="margin-left: 50px; margin-bottom: 20px;">
                    Kepada<br>
                    Yth. <?php echo $surat['penerima']; ?><br>
                    <br>
                    Di<br>
                    - Tempat
                </div>
                
                <!-- CONTENT NON TUGAS -->
                <div class="content">
                    <?php if ($surat['jenis_surat'] != 'Pemberitahuan'): ?>
                    <p style="font-style: italic; font-weight: bold;">Assalamu'alaikum Wr. Wb.</p>
                    <?php endif; ?>
                    
                    <?php if ($surat['jenis_surat'] == 'Undangan'): ?>
                        <p style="text-indent: 50px;">Di harap dengan hormat, atas kehadiran Bapak / Ibu <?php echo $surat['penerima']; ?> <?php echo $setting['nama_madrasah']; ?> untuk dapat menghadiri acara yang Insya Allah akan dilaksanakan pada :</p>
                        
                        <table class="detail-table">
                            <tr>
                                <td width="20%">Hari / tgl.</td>
                                <td width="2%">:</td>
                                <td><b><?php echo $surat['acara_hari_tanggal'] ? hari_indo($surat['acara_hari_tanggal']) . ', ' . tgl_indo($surat['acara_hari_tanggal']) : '-'; ?></b></td>
                            </tr>
                            <tr>
                                <td>Waktu</td>
                                <td>:</td>
                                <td><?php echo $surat['acara_waktu'] ? $surat['acara_waktu'] . ' WIB' : '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Tempat</td>
                                <td>:</td>
                                <td><?php echo $surat['acara_tempat'] ? $surat['acara_tempat'] : $setting['nama_madrasah']; ?></td>
                            </tr>
                            <tr>
                                <td>Keperluan</td>
                                <td>:</td>
                                <td><b><?php echo $surat['keperluan'] ? $surat['keperluan'] : '-'; ?></b></td>
                            </tr>
                            <tr>
                                <td>Keterangan</td>
                                <td>:</td>
                                <td><?php echo $surat['keterangan'] ? $surat['keterangan'] : 'Dimohon Dengan Sangat Atas Kehadirannya.'; ?></td>
                            </tr>
                        </table>
                        
                    <?php elseif ($surat['jenis_surat'] == 'Pemberitahuan'): ?>
                        <?php if (!empty($surat['pembuka_surat'])): ?>
                            <p style="text-indent: 50px;"><?php echo nl2br($surat['pembuka_surat']); ?></p>
                        <?php else: ?>
                            <p style="text-indent: 50px;">Memberitahukan bahwa:</p>
                        <?php endif; ?>

                        <div style="margin-left: 50px;">
                            <?php 
                            if (!empty($surat['isi_surat'])) {
                                echo $surat['isi_surat']; 
                            } elseif (!empty($surat['keterangan'])) {
                                echo nl2br($surat['keterangan']);
                            }
                            ?>
                        </div>

                        <?php if (!empty($surat['penutup_surat'])): ?>
                            <p style="text-indent: 50px;"><?php echo nl2br($surat['penutup_surat']); ?></p>
                        <?php endif; ?>
                        
                    <?php elseif ($surat['jenis_surat'] == 'Keterangan Pindah'): ?>
                        <p style="text-indent: 50px;">Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
                        <table class="detail-table">
                            <tr>
                                <td width="20%">Nama Siswa</td>
                                <td width="2%">:</td>
                                <td><b><?php echo $surat['penerima']; ?></b></td>
                            </tr>
                        </table>
                        <p style="text-indent: 50px;">Telah mengajukan permohonan pindah sekolah dengan alasan/tujuan:</p>
                        <p style="text-indent: 50px;"><?php echo !empty($surat['keterangan']) ? nl2br($surat['keterangan']) : '-'; ?></p>

                    <?php else: ?>
                        <p style="text-indent: 50px;">Sehubungan dengan <?php echo strtolower($surat['perihal']); ?>, kami sampaikan:</p>
                        <?php if (!empty($surat['keterangan'])): ?>
                            <p><?php echo nl2br($surat['keterangan']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($surat['jenis_surat'] != 'Pemberitahuan'): ?>
                    <p style="text-indent: 50px;">Demikian undangan kami sampaikan, atas kehadiran Bapak / Ibu <?php echo $surat['penerima']; ?> kami ucapkan terima kasih.</p>
                    
                    <p style="font-style: italic; font-weight: bold;">Wassalamu'alaikum Wr. Wb.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- TTD -->
            <div class="ttd">
                <p><?php echo tgl_indo($surat['tgl_surat']); ?></p>
                <p>Kepala Madrasah,</p>
                <?php if (!empty($setting['stempel']) && file_exists('uploads/' . $setting['stempel'])): ?>
                    <img src="uploads/<?php echo $setting['stempel']; ?>" class="stempel">
                <?php endif; ?>
                
                <?php if (!empty($setting['ttd']) && file_exists('uploads/' . $setting['ttd'])): ?>
                    <img src="uploads/<?php echo $setting['ttd']; ?>" class="ttd-img">
                <?php else: ?>
                    <br><br><br>
                <?php endif; ?>
                
                <p style="text-decoration: underline; font-weight: bold;"><?php echo $setting['kepala_madrasah']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>