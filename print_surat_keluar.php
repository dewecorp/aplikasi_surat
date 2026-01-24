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
        
        /* Landscape Mode CSS */
        body.landscape-mode #print-area {
            width: 100%;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            position: relative;
        }
        body.landscape-mode #print-area::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 0;
            height: 100%;
            border-right: 1px dashed #ccc;
            transform: translateX(-50%);
        }
        body.landscape-mode .letter-container {
            width: 42%;
            padding: 0 15px;
            box-sizing: border-box;
            /* border-right removed */
            float: left; /* Fallback */
        }
        /* body.landscape-mode .letter-container:last-child style removed */
        body.landscape-mode .kop-surat {
            padding-left: 70px; /* Adjust padding for landscape */
            min-height: 70px;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        body.landscape-mode .logo {
            width: 60px; /* Smaller logo for landscape */
        }
        body.landscape-mode .kop-surat h2 {
            font-size: 16pt; /* Smaller font for landscape */
            margin: 2px 0;
        }
        body.landscape-mode .kop-surat h3 {
            font-size: 12pt;
        }
        body.landscape-mode .kop-surat .sub-header {
            font-size: 12pt;
        }
        body.landscape-mode .content, 
        body.landscape-mode .meta-table, 
        body.landscape-mode .detail-table {
            font-size: 10pt; /* Scale down content */
            margin-bottom: 5px;
        }
        body.landscape-mode .content {
            line-height: 1.2;
        }
        body.landscape-mode .ttd {
            margin-top: 5px;
            width: 260px;
            align-self: flex-end; /* Required because parent is display: flex */
            float: right; /* Fallback */
            margin-right: 0px;
            page-break-inside: avoid; /* Prevent breaking across pages */
        }
        body.landscape-mode .letter-container {
            height: 100%; /* Ensure full height usage */
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        body.landscape-mode .ttd img.stempel {
            width: 150px;
            left: 10px;
            top: -10px;
        }
        body.landscape-mode .ttd img.ttd-img {
            height: 80px;
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
            top: 0px;
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
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center; background: #f0f0f0; padding: 15px; border-bottom: 1px solid #ccc;">
        <button onclick="setMode('portrait')" style="padding: 10px 20px; cursor: pointer; margin-right: 10px;">Cetak Portrait (1 Surat)</button>
        <button onclick="setMode('landscape')" style="padding: 10px 20px; cursor: pointer;">Cetak Landscape (2 Surat)</button>
    </div>

    <div id="print-area">
        <div class="letter-container" id="original-letter">
            <div class="kop-surat">
                <!-- Logo -->
                <?php
                $logo_src = "assets/images/logo.png";
                // Check if logo is uploaded in assets/images (from previous toolcall logic) or uploads folder?
                // Wait, the upload logic in pengaturan.php puts it in assets/images/ for logo.
                // Let's stick to assets/images as per my previous implementation.
                if (!empty($setting['logo']) && file_exists("assets/images/" . $setting['logo'])) {
                    $logo_src = "assets/images/" . $setting['logo'];
                }
                ?>
                <img src="<?php echo $logo_src; ?>" class="logo" alt="Logo" onerror="this.style.display='none'">
                
                <h3><?php echo strtoupper($setting['nama_yayasan']); ?></h3>
                <h2><?php echo strtoupper($setting['nama_madrasah']); ?></h2>
                <p style="font-style: italic;"><?php echo $setting['alamat']; ?></p>
            </div>

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
                    
                <?php elseif ($surat['jenis_surat'] == 'Tugas'): ?>
                    <p style="text-indent: 50px;">Yang bertanda tangan di bawah ini Kepala Madrasah menugaskan kepada:</p>
                    <table class="detail-table">
                        <tr>
                            <td width="20%">Nama</td>
                            <td width="2%">:</td>
                            <td><b><?php echo $surat['penerima']; ?></b></td>
                        </tr>
                    </table>

                    <p style="text-indent: 50px;">Untuk melaksanakan tugas:</p>
                    <table class="detail-table">
                        <tr>
                            <td width="20%">Nama Kegiatan</td>
                            <td width="2%">:</td>
                            <td><?php echo $surat['perihal']; ?></td>
                        </tr>
                        <tr>
                            <td>Waktu</td>
                            <td>:</td>
                            <td><?php echo $surat['acara_waktu']; ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>
                                <?php 
                                $tgl = $surat['acara_hari_tanggal'];
                                if (strpos($tgl, ' s.d ') !== false) {
                                    $parts = explode(' s.d ', $tgl);
                                    echo tgl_indo($parts[0]) . ' s.d ' . tgl_indo($parts[1]);
                                } else {
                                    echo tgl_indo($tgl);
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Tempat</td>
                            <td>:</td>
                            <td><?php echo $surat['acara_tempat']; ?></td>
                        </tr>
                    </table>

                    <p style="text-indent: 50px;">Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>

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
        </div>
    </div>

    <script>
        function setMode(mode) {
            const printArea = document.getElementById('print-area');
            const originalLetter = document.getElementById('original-letter');
            
            // Reset
            document.body.classList.remove('landscape-mode');
            const clones = document.querySelectorAll('.cloned-letter');
            clones.forEach(el => el.remove());
            
            // Remove injected style if any
            const oldStyle = document.getElementById('page-style');
            if (oldStyle) oldStyle.remove();

            if (mode === 'landscape') {
                document.body.classList.add('landscape-mode');
                
                // Clone letter
                const clone = originalLetter.cloneNode(true);
                clone.id = '';
                clone.classList.add('cloned-letter');
                printArea.appendChild(clone);
                
                // Set page size to A4 Landscape
                const style = document.createElement('style');
                style.id = 'page-style';
                style.innerHTML = '@page { size: A4 landscape; margin: 1cm; }';
                document.head.appendChild(style);
            } else {
                 // Set page size to A4 Portrait
                const style = document.createElement('style');
                style.id = 'page-style';
                style.innerHTML = '@page { size: A4 portrait; margin: 2cm; }';
                document.head.appendChild(style);
            }
            
            setTimeout(() => {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
