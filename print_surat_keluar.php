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

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'portrait';
// Only allow landscape for Undangan and Pemberitahuan
if ($mode == 'landscape' && !in_array($surat['jenis_surat'], ['Undangan', 'Pemberitahuan'])) {
    $mode = 'portrait';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Surat - <?php echo $surat['no_surat']; ?></title>
    <style>
        <?php if ($mode == 'landscape'): ?>
        @page {
            size: 33cm 21.5cm; /* F4 Landscape */
            margin: 0.5cm;
            margin-left: 1.5cm;
            margin-top: 0.3cm;
            margin-bottom: 0;
        }
        .letter-container {
            width: 44%;
            float: left;
            margin-right: 12%;
            font-size: 12pt;
            page-break-inside: avoid;
            /* border: 1px dashed #ccc; */
            padding: 10px;
            box-sizing: border-box;
        }
        .letter-container:nth-of-type(2n) {
            margin-right: 0;
        }
        /* Adjust scaling for landscape */
        .kop-surat h2 { font-size: 16pt; }
        .kop-surat h3 { font-size: 12pt; }
        .logo { width: 60px; }
        .kop-surat { 
            padding-left: 70px; 
            min-height: 70px; 
            margin-bottom: 5px !important;
            padding-bottom: 5px !important;
        }
        .meta-table { margin-bottom: 5px !important; }
        .content { line-height: 1.3 !important; }
        .ttd { margin-top: 5px !important; }
        .detail-table { margin-bottom: 5px !important; }
        <?php else: ?>
        @page {
            size: 21.5cm 33cm; /* F4 Portrait */
            margin: 1cm;
            margin-top: <?php echo ($surat['jenis_surat'] == 'Keterangan Pindah' || $surat['jenis_surat'] == 'Tugas') ? '1.5cm' : '0.3cm'; ?>;
        }
        .letter-container {
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
        }
        <?php endif; ?>
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
            background: white;
            overflow: hidden; /* Prevent extra blank page */
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
            padding: 0;
            line-height: 1.0;
        }
        
        .ttd {
            float: right;
            text-align: center;
            width: 300px;
            margin-top: 10px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ttd img.stempel {
            position: absolute;
            left: 20px;
            top: -20px;
            width: 170px;
            opacity: 0.8;
            transform: rotate(-5deg);
            z-index: 2;
        }
        .ttd img.ttd-img {
            height: 130px;
            margin-top: -25px;
            margin-bottom: -25px;
            position: relative;
            z-index: 1;
        }
        .ttd p {
            margin: 2px 0;
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
        
        
        // Capture Principal TTD for reuse
        ob_start();
        ?>
        <div class="ttd">
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
        <?php 
        $principal_ttd = ob_get_clean();

        foreach ($penerima_list as $idx => $p_loop_item):
            // Use original string for non-Tugas to preserve formatting if any
            if ($surat['jenis_surat'] != 'Tugas') {
                $p_nama = $surat['penerima'];
            } else {
                $p_nama = $surat['penerima'];
            }
            
            if ($mode == 'landscape') ob_start();
        ?>
        <div class="letter-container" <?php if ($mode != 'landscape' && $idx < count($penerima_list) - 1) echo 'style="page-break-after: always;"'; ?>>
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
                            <td><?php echo ucwords(strtolower($setting['nama_madrasah'])); ?></td>
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
                    
                    <div style="page-break-inside: avoid;">
                        <p style="text-indent: 50px; text-align: justify;">Demikian surat penugasan ini dikeluarkan untuk dapat dilaksanakan dengan baik dan penuh rasa tanggung jawab</p>
                        <?php echo $principal_ttd; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- NON TUGAS HEADER -->
                <?php if ($surat['jenis_surat'] == 'Keterangan Pindah'): ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; text-decoration: underline; font-weight: bold;">SURAT KETERANGAN PINDAH SEKOLAH</h3>
                        <p style="margin: 0;">Nomor : <?php echo $surat['no_surat']; ?></p>
                        
                        <p style="margin-top: 20px; text-align: justify;">
                            Yang bertanda tangan di bawah ini Kepala <?php echo ucwords(strtolower($setting['nama_madrasah'])); ?> Menerangkan:
                        </p>
                    </div>
                <?php else: ?>
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
                            <td style="font-weight: bold;">
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
                <?php endif; ?>
                
                <!-- CONTENT NON TUGAS -->
                <div class="content">
                    <?php if ($surat['jenis_surat'] != 'Pemberitahuan' && $surat['jenis_surat'] != 'Keterangan Pindah'): ?>
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

                        <div style="page-break-inside: avoid;">
                            <?php if (!empty($surat['penutup_surat'])): ?>
                                <p style="text-indent: 50px;"><?php echo nl2br($surat['penutup_surat']); ?></p>
                            <?php endif; ?>
                            <?php echo $principal_ttd; ?>
                        </div>
                        
                    <?php elseif ($surat['jenis_surat'] == 'Keterangan Pindah'): ?>
                        <table class="detail-table">
                            <tr>
                                <td width="30%">Nama</td>
                                <td width="2%">:</td>
                                <td><b><?php echo strtoupper($surat['penerima']); ?></b></td>
                            </tr>
                            <tr>
                                <td>NIS/NISN</td>
                                <td>:</td>
                                <td><?php echo isset($surat['nis_siswa']) ? $surat['nis_siswa'] : '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Tempat. Tgl Lahir</td>
                                <td>:</td>
                                <td>
                                    <?php 
                                    $ttl = [];
                                    if (!empty($surat['tempat_lahir_siswa'])) $ttl[] = $surat['tempat_lahir_siswa'];
                                    if (!empty($surat['tgl_lahir_siswa'])) $ttl[] = tgl_indo($surat['tgl_lahir_siswa']);
                                    echo !empty($ttl) ? implode(', ', $ttl) : '-';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Jenis Kelamin</td>
                                <td>:</td>
                                <td><?php echo isset($surat['jenis_kelamin_siswa']) ? $surat['jenis_kelamin_siswa'] : '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Kelas</td>
                                <td>:</td>
                                <td><?php echo isset($surat['kelas_siswa']) ? $surat['kelas_siswa'] : '-'; ?></td>
                            </tr>
                        </table>
                        
                        <p>Sesuai dengan surat permohonan pindah sekolah oleh orang tua/wali murid :</p>
                        <table class="detail-table">
                            <tr>
                                <td width="30%">Nama</td>
                                <td width="2%">:</td>
                                <td><?php echo isset($surat['nama_wali']) ? strtoupper($surat['nama_wali']) : '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Pekerjaan</td>
                                <td>:</td>
                                <td><?php echo isset($surat['pekerjaan_wali']) ? $surat['pekerjaan_wali'] : '-'; ?></td>
                            </tr>
                            <tr>
                                <td>Alamat</td>
                                <td>:</td>
                                <td><?php echo isset($surat['alamat_wali']) ? $surat['alamat_wali'] : '-'; ?></td>
                            </tr>
                        </table>

                        <p>Telah mengajukan pindah sekolah ke SD/MI : <?php echo isset($surat['tujuan_pindah']) ? $surat['tujuan_pindah'] : '...................................................................................'; ?></p>
                        <div style="page-break-inside: avoid;">
                            <p style="text-indent: 50px;">Demikian surat ini kami buat dengan sebenarnya, agar dapat digunakan sebagaimana mestinya.</p>
                            <?php echo $principal_ttd; ?>
                        </div>

                    <?php else: ?>
                        <p style="text-indent: 50px;">Sehubungan dengan <?php echo strtolower($surat['perihal']); ?>, kami sampaikan:</p>
                        <?php if (!empty($surat['keterangan'])): ?>
                            <p><?php echo nl2br($surat['keterangan']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($surat['jenis_surat'] != 'Pemberitahuan' && $surat['jenis_surat'] != 'Keterangan Pindah'): ?>
                    <div style="page-break-inside: avoid;">
                        <p style="text-indent: 50px;">Demikian undangan kami sampaikan, atas kehadiran Bapak / Ibu <?php echo $surat['penerima']; ?> kami ucapkan terima kasih.</p>
                        
                        <p style="font-style: italic; font-weight: bold;">Wassalamu'alaikum Wr. Wb.</p>
                        <?php echo $principal_ttd; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- TTD REMOVED (Moved inside sections) -->

            <?php if ($surat['jenis_surat'] == 'Keterangan Pindah'): ?>
                <div style="clear: both;"></div>
                <div style="height: 100px;"></div>
                
                <div style="margin-bottom: 20px; position: relative;">
                    <div style="border-top: 1px dashed black;"></div>
                    <div style="position: absolute; top: -12px; left: 0; background-color: #fff; padding-right: 10px; font-style: italic;">potong di sini....</div>
                </div>

                <div style="margin-top: 20px; margin-bottom: 20px; page-break-inside: avoid; break-inside: avoid;">
                    <p>Setelah anak tersebut diterima di sekolah tujuan, isian ini harap diisi dan dikirimkan kembali kepada kami.</p>
                    <table class="detail-table" style="width: 100%;">
                        <tr><td width="30%">NSM</td><td width="2%">:</td><td>............................................................</td></tr>
                        <tr><td>Nama Sekolah</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Status Sekolah</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Alamat</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Desa / Kelurahan</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Kecamatan</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Kabupaten / Kota</td><td>:</td><td>............................................................</td></tr>
                        <tr><td>Propinsi</td><td>:</td><td>............................................................</td></tr>
                    </table>
                </div>
                
                <div style="page-break-before: always;"></div>
                
                <!-- PAGE 2: Surat Permohonan -->
                <div style="text-align: right; margin-bottom: 20px;">
                    <?php 
                    echo tgl_indo($surat['tgl_surat']); 
                    ?>
                </div>
                <div style="margin-bottom: 20px;">
                    Perihal : Permohonan Pindah Sekolah
                </div>
                <div style="margin-bottom: 20px;">
                    Kepada :<br>
                    <br>
                    Yth. Kepala <?php echo $setting['nama_madrasah']; ?><br>
                    di-<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;Tempat
                </div>
                
                <div class="content">
                    <p>Dengan Hormat, Saya:</p>
                    <table class="detail-table">
                        <tr><td width="30%">Nama</td><td width="2%">:</td><td><?php echo isset($surat['nama_wali']) ? strtoupper($surat['nama_wali']) : '-'; ?></td></tr>
                        <tr><td>Pekerjaan</td><td>:</td><td><?php echo isset($surat['pekerjaan_wali']) ? $surat['pekerjaan_wali'] : '-'; ?></td></tr>
                        <tr><td>Alamat</td><td>:</td><td><?php echo isset($surat['alamat_wali']) ? $surat['alamat_wali'] : '-'; ?></td></tr>
                    </table>
                    
                    <p style="text-align: justify;">Dengan ini Mengajukan permohonan pindah sekolah kehadapan bapak/ibu Kepala <?php echo $setting['nama_madrasah']; ?> untuk memberikan surat pindah sekolah anak kami :</p>
                    
                    <table class="detail-table">
                        <tr><td width="30%">Nama</td><td width="2%">:</td><td><?php echo strtoupper($surat['penerima']); ?></td></tr>
                        <tr><td>NIS/NISN</td><td>:</td><td><?php echo isset($surat['nis_siswa']) ? $surat['nis_siswa'] : '-'; ?></td></tr>
                        <tr><td>Tempat, Tgl Lahir</td><td>:</td><td>
                            <?php 
                            $ttl = [];
                            if (!empty($surat['tempat_lahir_siswa'])) $ttl[] = $surat['tempat_lahir_siswa'];
                            if (!empty($surat['tgl_lahir_siswa'])) $ttl[] = tgl_indo($surat['tgl_lahir_siswa']);
                            echo !empty($ttl) ? implode(', ', $ttl) : '-';
                            ?>
                        </td></tr>
                        <tr><td>Jenis Kelamin</td><td>:</td><td><?php echo isset($surat['jenis_kelamin_siswa']) ? $surat['jenis_kelamin_siswa'] : '-'; ?></td></tr>
                        <tr><td>Kelas</td><td>:</td><td><?php echo isset($surat['kelas_siswa']) ? $surat['kelas_siswa'] : '-'; ?></td></tr>
                    </table>
                    
                    <p>Demikian surat permohonan ini kami buat dengan sebenarnya, kami ucapkan terima kasih</p>
                </div>
                
                <div class="ttd">
                    <p>Hormat Kami,</p>
                    <p>Orang Tua / Wali</p>
                    <br><br><br>
                    <p style="text-decoration: underline; font-weight: bold;"><?php echo isset($surat['nama_wali']) ? strtoupper($surat['nama_wali']) : '..........................'; ?></p>
                </div>
            <?php endif; ?>
            <div style="clear: both;"></div>
        </div>
        <?php 
            if ($mode == 'landscape') {
                $content = ob_get_clean();
                echo $content;
                echo $content;
            }
        endforeach; 
        ?>
    </div>
</body>
</html>