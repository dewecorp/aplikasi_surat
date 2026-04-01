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

// Set filename for download/print
$tahun = date('Y', strtotime($sk['tgl_surat']));
$nama_sk = !empty($sk['nama_sk']) ? $sk['nama_sk'] : 'SK_' . $sk['id'];
$filename = 'SK_' . $nama_sk . '_' . $tahun;

$q_instansi = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$instansi = mysqli_fetch_assoc($q_instansi);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $filename; ?></title>
    <style>
        @page {
            size: 21.5cm 33cm; /* F4/Folio */
            margin: 1.5cm; /* All sides: top, right, bottom, left */
        }
        
        /* Force lampiran to new page */
        .lampiran-section {
            page-break-before: always;
            page-break-inside: avoid;
        }
        
        body { 
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif; 
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        /* Force Bookman Old Style for all content */
        * {
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif !important;
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
        table { 
            width: 100%; 
            border-collapse: collapse;
            table-layout: fixed;
        }
        td { 
            vertical-align: top;
            padding: 0;
            margin: 0;
        }
        .label { 
            width: 20%;
            text-align: left;
            font-weight: normal;
        }
        .separator { 
            width: 2%;
            text-align: left;
        }
        .content-cell {
            width: 78%;
            font-size: 12pt;
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif;
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
            font-size: 12pt;
            font-family: 'Bookman Old Style', 'Times New Roman', Times, serif;
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
            <div style="text-transform: uppercase; font-weight: bold;"><?php echo html_entity_decode($sk['tentang'], ENT_QUOTES, 'UTF-8'); ?></div>
            <br>
            <h4>KEPALA <?php echo strtoupper($instansi['nama_madrasah']); ?></h4>
        </div>

        <div class="content">
            <table>
                <tr>
                    <td class="label">Menimbang</td>
                    <td class="separator">:</td>
                    <td class="content-cell">
                        <?php 
                        $decoded = stripslashes($sk['menimbang']);
                        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $decoded = str_replace(['–', '—', '‐', '‑'], '-', $decoded);
                        echo $decoded;
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Mengingat</td>
                    <td class="separator">:</td>
                    <td class="content-cell">
                        <?php 
                        $decoded = stripslashes($sk['mengingat']);
                        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $decoded = str_replace(['–', '—', '‐', '‑'], '-', $decoded);
                        echo $decoded;
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Memperhatikan</td>
                    <td class="separator">:</td>
                    <td class="content-cell">
                        <?php 
                        $decoded = stripslashes($sk['memperhatikan']);
                        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $decoded = str_replace(['–', '—', '‐', '‑'], '-', $decoded);
                        echo $decoded;
                        ?>
                    </td>
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
                                    // Handle multiple encoding layers from CKEditor
                                    $decoded_value = stripslashes($value);
                                    $decoded_value = html_entity_decode($decoded_value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    // Replace Unicode dashes with regular dash
                                    $decoded_value = str_replace(['–', '—', '‐', '‑'], '-', $decoded_value);
                                    echo '<li>' . $decoded_value . '</li>';
                                }
                                echo '</ol>';
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            </table>

            <br><br>
            
            <!-- Ditetapkan Section - Right Side (No Page Break Inside) -->
            <div style="width: 100%; margin-bottom: 30px; page-break-inside: avoid;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;"></td>
                        <td style="width: 50%; vertical-align: top; text-align: left;">
                            <table style="width: auto; page-break-inside: avoid;">
                                <tr>
                                    <td style="width: 120px;">Ditetapkan di</td>
                                    <td style="width: 10px;">:</td>
                                    <td>Sukosono</td>
                                </tr>
                                <tr>
                                    <td>Pada tanggal</td>
                                    <td>:</td>
                                    <td><?php echo tgl_indo($sk['tgl_surat']); ?></td>
                                </tr>
                            </table>
                            <br>
                            <p style="margin: 0;">Kepala Madrasah,</p>
                            
                            <!-- Regular Signature Image -->
                            <?php if (!empty($instansi['ttd']) && file_exists('uploads/' . $instansi['ttd'])): ?>
                                <img src="uploads/<?php echo $instansi['ttd']; ?>" style="width: 150px; height: auto; display: block; margin-left: 0; margin-right: auto;">
                            <?php else: ?>
                                <br><br><br>
                            <?php endif; ?>

                            <!-- Stampel - Larger Size, Front of Text -->
                            <?php if (!empty($instansi['stempel']) && file_exists('uploads/' . $instansi['stempel'])): ?>
                                <img src="uploads/<?php echo $instansi['stempel']; ?>" style="width: 150px; height: 150px; position: absolute; margin-left: -85px; margin-top: -120px; opacity: 0.8; z-index: 10;">
                            <?php endif; ?>

                            <p style="margin: 0; font-weight: bold; text-decoration: underline;"><?php echo properCaseName($instansi['kepala_madrasah']); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="clear: both;"></div>
            <br><br><br>

            <?php 
            // Check if lampiran has content or file
            $has_lampiran = !empty($sk['lampiran']) || !empty($sk['file_lampiran']);
            ?>
            
            <?php if ($has_lampiran) : ?>
                <div class="lampiran-section" style="padding-top: 20px;">
                    <!-- Lampiran Header -->
                    <table style="width: 100%; margin-bottom: 20px;">
                        <tr>
                            <td style="width: 20%; font-weight: bold;">Lampiran</td>
                            <td style="width: 5%;">:</td>
                            <td style="width: 75%;">Keputusan Kepala <?php echo properCaseName($instansi['nama_madrasah']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Nomor</td>
                            <td>:</td>
                            <td><?php echo $sk['no_surat']; ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Tentang</td>
                            <td>:</td>
                            <td><?php echo strip_tags(html_entity_decode($sk['tentang'], ENT_QUOTES, 'UTF-8')); ?></td>
                        </tr>
                    </table>
                    
                    <!-- Display CKEditor lampiran first (if exists) -->
                    <?php if (!empty($sk['lampiran'])): ?>
                        <div style="margin-bottom: 40px;">
                            <?php 
                            // Decode HTML entities from CKEditor - handle multiple encoding layers
                            $lampiran_content = $sk['lampiran'];
                            
                            // First, handle any escaped characters
                            $lampiran_content = stripslashes($lampiran_content);
                            
                            // Decode HTML entities (handles &ndash;, &#8211;, etc.)
                            $lampiran_content = html_entity_decode($lampiran_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            
                            // Replace any remaining Unicode dash characters with regular dash
                            $lampiran_content = str_replace(['–', '—', '‐', '‑'], '-', $lampiran_content);
                            
                            echo $lampiran_content;
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display PDF file attachment (if exists) -->
                    <?php if (!empty($sk['file_lampiran'])): ?>
                        <div style="margin-bottom: 80px; text-align: center; page-break-inside: avoid;">
                            <p style="font-style: italic; color: #666; margin-bottom: 10px;">
                                <strong>Lampiran File PDF:</strong> <?php echo htmlspecialchars($sk['file_lampiran']); ?>
                            </p>
                            <embed src="uploads/<?php echo htmlspecialchars($sk['file_lampiran']); ?>" type="application/pdf" width="100%" height="600px" />
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 80px;"></div>
                    <?php endif; ?>
                    
                    <!-- Signature Block at Bottom of Lampiran -->
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%;"></td>
                            <td style="width: 50%; vertical-align: top; text-align: left;">
                                <table style="width: auto;">
                                    <tr>
                                        <td style="width: 120px;">Ditetapkan di</td>
                                        <td style="width: 10px;">:</td>
                                        <td>Sukosono</td>
                                    </tr>
                                    <tr>
                                        <td>Pada tanggal</td>
                                        <td>:</td>
                                        <td><?php echo tgl_indo($sk['tgl_surat']); ?></td>
                                    </tr>
                                </table>
                                <br>
                                <p style="margin: 0;">Kepala Madrasah,</p>
                                
                                <!-- Regular Signature Image -->
                                <?php if (!empty($instansi['ttd']) && file_exists('uploads/' . $instansi['ttd'])): ?>
                                    <img src="uploads/<?php echo $instansi['ttd']; ?>" style="width: 150px; height: auto; display: block; margin-left: 0; margin-right: auto;">
                                <?php else: ?>
                                    <br><br><br>
                                <?php endif; ?>

                                <!-- Stampel - Larger Size, Front of Text -->
                                <?php if (!empty($instansi['stempel']) && file_exists('uploads/' . $instansi['stempel'])): ?>
                                    <img src="uploads/<?php echo $instansi['stempel']; ?>" style="width: 150px; height: 150px; position: absolute; margin-left: -85px; margin-top: -120px; opacity: 0.8; z-index: 10;">
                                <?php endif; ?>

                                <p style="margin: 0; font-weight: bold; text-decoration: underline;"><?php echo properCaseName($instansi['kepala_madrasah']); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>