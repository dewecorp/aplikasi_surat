<?php
session_start();
include 'config.php';

// Handle Add
if (isset($_POST['add'])) {
    $tgl_surat = $_POST['tgl_surat'];
    $jenis_surat = $_POST['jenis_surat'];
    
    // Prepare variables based on Type
    $penerima_list = [];
    
    if ($jenis_surat == 'Tugas') {
        if (isset($_POST['penerima']) && is_array($_POST['penerima'])) {
            $penerima_list = $_POST['penerima'];
        } else {
             $penerima_list[] = $_POST['penerima'];
        }
        
        $perihal = mysqli_real_escape_string($conn, $_POST['nama_kegiatan']);
        $acara_tempat = mysqli_real_escape_string($conn, $_POST['lokasi_kegiatan']);
        $acara_waktu = mysqli_real_escape_string($conn, $_POST['waktu_kegiatan']);
        
        $durasi = $_POST['durasi_kegiatan'];
        if ($durasi == '1') {
            $acara_hari_tanggal = $_POST['tgl_kegiatan_single'];
        } else {
            $acara_hari_tanggal = $_POST['tgl_kegiatan_start'] . ' s.d ' . $_POST['tgl_kegiatan_end'];
        }
        
        $keperluan = NULL;
        $keterangan = NULL;
        $pembuka_surat = NULL;
        $isi_surat = NULL;
        $penutup_surat = NULL;
        
    } else {
        // Standard mapping for other types
        $penerima_list[] = $_POST['penerima'];
        $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
        
        $acara_hari_tanggal = !empty($_POST['acara_hari_tanggal']) ? $_POST['acara_hari_tanggal'] : NULL;
        $acara_waktu = !empty($_POST['acara_waktu']) ? $_POST['acara_waktu'] : NULL;
        $acara_tempat = !empty($_POST['acara_tempat']) ? mysqli_real_escape_string($conn, $_POST['acara_tempat']) : NULL;
        $keperluan = !empty($_POST['keperluan']) ? mysqli_real_escape_string($conn, $_POST['keperluan']) : NULL;
        $keterangan = !empty($_POST['keterangan']) ? mysqli_real_escape_string($conn, $_POST['keterangan']) : NULL;
        $pembuka_surat = !empty($_POST['pembuka_surat']) ? mysqli_real_escape_string($conn, $_POST['pembuka_surat']) : NULL;
        $isi_surat = !empty($_POST['isi_surat']) ? mysqli_real_escape_string($conn, $_POST['isi_surat']) : NULL;
        $penutup_surat = !empty($_POST['penutup_surat']) ? mysqli_real_escape_string($conn, $_POST['penutup_surat']) : NULL;
    }

    // Special handling for Tugas: Merge recipients into one letter
    if ($jenis_surat == 'Tugas') {
        $merged_penerima = implode('; ', $penerima_list);
        $penerima_list = [$merged_penerima];
    }

    $success_count = 0;
    foreach ($penerima_list as $penerima_name) {
        $penerima = mysqli_real_escape_string($conn, $penerima_name);
        
        // Generate Nomor Surat
        $tahun = date('Y', strtotime($tgl_surat));
        $bulan = date('n', strtotime($tgl_surat));
        $romawi = getRomawi($bulan);
        
        // Ambil nomor terakhir di tahun ini
        $q_last = mysqli_query($conn, "SELECT no_surat FROM surat_keluar WHERE YEAR(tgl_surat) = '$tahun' ORDER BY id DESC LIMIT 1");
        if (mysqli_num_rows($q_last) > 0) {
            $last_data = mysqli_fetch_assoc($q_last);
            $parts = explode('/', $last_data['no_surat']);
            $last_no = intval($parts[0]);
            $next_no = $last_no + 1;
        } else {
            $next_no = 1;
        }
        
        $no_surat = sprintf('%03d', $next_no) . '/MI.SF/' . $romawi . '/' . $tahun;
    
        $query = "INSERT INTO surat_keluar (tgl_surat, no_surat, jenis_surat, perihal, penerima, acara_hari_tanggal, acara_waktu, acara_tempat, keperluan, keterangan, pembuka_surat, isi_surat, penutup_surat) 
                  VALUES ('$tgl_surat', '$no_surat', '$jenis_surat', '$perihal', '$penerima', " . 
                  ($acara_hari_tanggal ? "'$acara_hari_tanggal'" : "NULL") . ", " . 
                  ($acara_waktu ? "'$acara_waktu'" : "NULL") . ", " . 
                  ($acara_tempat ? "'$acara_tempat'" : "NULL") . ", " . 
                  ($keperluan ? "'$keperluan'" : "NULL") . ", " . 
                  ($keterangan ? "'$keterangan'" : "NULL") . ", " . 
                  ($pembuka_surat ? "'$pembuka_surat'" : "NULL") . ", " . 
                  ($isi_surat ? "'$isi_surat'" : "NULL") . ", " . 
                  ($penutup_surat ? "'$penutup_surat'" : "NULL") . ")";
        
        if (mysqli_query($conn, $query)) {
            log_activity($_SESSION['user_id'], 'create', 'Membuat surat keluar no: ' . $no_surat);
            $success_count++;
        }
    }

    if ($success_count > 0) {
        $_SESSION['success'] = "Berhasil membuat $success_count surat keluar";
    } else {
        $_SESSION['error'] = "Gagal membuat surat: " . mysqli_error($conn);
    }
    session_write_close();
    header("Location: surat_keluar.php");
    exit();
}

// Handle Edit
if (isset($_POST['edit'])) {
    // DEBUG: Log POST data
    file_put_contents('debug_surat_keluar.txt', print_r($_POST, true));

    $id = $_POST['id'];
    $tgl_surat = $_POST['tgl_surat'];
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    
    // Handle Penerima (String or Array)
    $penerima_input = isset($_POST['penerima']) ? $_POST['penerima'] : '';
    
    // Fix: Validasi dan Format Penerima
    if (is_array($penerima_input)) {
        // Hapus nilai kosong
        $penerima_input = array_filter($penerima_input, function($v) { return !empty(trim($v)); });
        
        if (empty($penerima_input)) {
             $_SESSION['error'] = "Penerima tidak boleh kosong!";
             session_write_close();
             header("Location: surat_keluar.php");
             exit();
        }
        $penerima = mysqli_real_escape_string($conn, implode('; ', $penerima_input));
    } else {
        if (trim($penerima_input) == '') {
             $_SESSION['error'] = "Penerima tidak boleh kosong!";
             session_write_close();
             header("Location: surat_keluar.php");
             exit();
        }
        $penerima = mysqli_real_escape_string($conn, $penerima_input);
    }
    
    // Additional fields
    $acara_hari_tanggal = !empty($_POST['acara_hari_tanggal']) ? $_POST['acara_hari_tanggal'] : NULL;
    
    // Logic for Tugas Date (Override acara_hari_tanggal if durasi is set)
    if (isset($_POST['durasi_kegiatan'])) {
        $durasi = $_POST['durasi_kegiatan'];
        if ($durasi == '1') {
             $acara_hari_tanggal = $_POST['tgl_kegiatan_single'];
        } else {
             $acara_hari_tanggal = $_POST['tgl_kegiatan_start'] . ' s.d ' . $_POST['tgl_kegiatan_end'];
        }
    }

    $acara_waktu = !empty($_POST['acara_waktu']) ? $_POST['acara_waktu'] : NULL;
    $acara_tempat = !empty($_POST['acara_tempat']) ? mysqli_real_escape_string($conn, $_POST['acara_tempat']) : NULL;
    $keperluan = !empty($_POST['keperluan']) ? mysqli_real_escape_string($conn, $_POST['keperluan']) : NULL;
    $keterangan = !empty($_POST['keterangan']) ? mysqli_real_escape_string($conn, $_POST['keterangan']) : NULL;
    $pembuka_surat = !empty($_POST['pembuka_surat']) ? mysqli_real_escape_string($conn, $_POST['pembuka_surat']) : NULL;
    $isi_surat = !empty($_POST['isi_surat']) ? mysqli_real_escape_string($conn, $_POST['isi_surat']) : NULL;
    $penutup_surat = !empty($_POST['penutup_surat']) ? mysqli_real_escape_string($conn, $_POST['penutup_surat']) : NULL;

    // Nomor surat tidak berubah saat edit untuk menjaga konsistensi urutan
    
    $query = "UPDATE surat_keluar SET 
              tgl_surat='$tgl_surat', 
              perihal='$perihal', 
              penerima='$penerima',
              acara_hari_tanggal=" . ($acara_hari_tanggal ? "'$acara_hari_tanggal'" : "NULL") . ",
              acara_waktu=" . ($acara_waktu ? "'$acara_waktu'" : "NULL") . ",
              acara_tempat=" . ($acara_tempat ? "'$acara_tempat'" : "NULL") . ",
              keperluan=" . ($keperluan ? "'$keperluan'" : "NULL") . ",
              keterangan=" . ($keterangan ? "'$keterangan'" : "NULL") . ",
              pembuka_surat=" . ($pembuka_surat ? "'$pembuka_surat'" : "NULL") . ",
              isi_surat=" . ($isi_surat ? "'$isi_surat'" : "NULL") . ",
              penutup_surat=" . ($penutup_surat ? "'$penutup_surat'" : "NULL") . "
              WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        // Ambil no surat untuk log
        $q_log = mysqli_query($conn, "SELECT no_surat FROM surat_keluar WHERE id='$id'");
        $d_log = mysqli_fetch_assoc($q_log);
        log_activity($_SESSION['user_id'], 'update', 'Mengubah surat keluar no: ' . $d_log['no_surat']);

        $_SESSION['success'] = "Surat keluar berhasil diubah";
    } else {
        $_SESSION['error'] = "Gagal mengubah surat: " . mysqli_error($conn);
    }
    session_write_close();
    header("Location: surat_keluar.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $q_del = mysqli_query($conn, "SELECT no_surat FROM surat_keluar WHERE id='$id'");
    $d_del = mysqli_fetch_assoc($q_del);
    $no_surat_del = $d_del['no_surat'];

    if (mysqli_query($conn, "DELETE FROM surat_keluar WHERE id='$id'")) {
        log_activity($_SESSION['user_id'], 'delete', 'Menghapus surat keluar no: ' . $no_surat_del);
        $_SESSION['success'] = "Surat keluar berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus surat";
    }
    session_write_close();
    header("Location: surat_keluar.php");
    exit();
}

include 'template/header.php';
include 'template/sidebar.php';

// Filter Logic
$where = "WHERE 1=1";
if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = $_GET['filter_tahun'];
    $where .= " AND YEAR(tgl_surat) = '$ft'";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = $_GET['filter_bulan'];
    $where .= " AND MONTH(tgl_surat) = '$fb'";
}
if (isset($_GET['filter_penerima']) && !empty($_GET['filter_penerima'])) {
    $fp = $_GET['filter_penerima'];
    $where .= " AND penerima LIKE '%$fp%'";
}
if (isset($_GET['filter_tanggal']) && !empty($_GET['filter_tanggal'])) {
    $ftgl = $_GET['filter_tanggal'];
    $where .= " AND tgl_surat = '$ftgl'";
}

?>

<section class="content">
    <style>
        /* Fix Dropdown Truncation */
        .bootstrap-select .dropdown-menu {
            margin-left: 0 !important;
            padding-left: 0 !important;
            left: 0 !important;
            min-width: 100% !important;
            box-sizing: border-box !important;
        }
        .bootstrap-select .dropdown-menu ul {
            padding-left: 0 !important;
            margin-left: 0 !important;
        }
        .bootstrap-select .dropdown-menu li {
            list-style: none !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
        .bootstrap-select .dropdown-menu li a {
            padding-left: 15px !important;
            padding-right: 15px !important;
            margin-left: 0 !important;
            display: block !important;
            width: 100% !important;
        }
        .bootstrap-select .dropdown-menu .text {
            display: inline-block !important;
            white-space: normal !important; /* Allow wrapping if needed */
        }
    </style>
    <div class="container-fluid">
        <div class="block-header">
            <h2>SURAT KELUAR</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            DATA SURAT KELUAR
                        </h2>
                        <ul class="header-dropdown m-r--5">
                            <li class="dropdown">
                                <button type="button" class="btn btn-primary waves-effect" data-toggle="modal" data-target="#modalUndangan">Surat Undangan</button>
                                <button type="button" class="btn btn-info waves-effect" data-toggle="modal" data-target="#modalPemberitahuan">Surat Pemberitahuan</button>
                                <button type="button" class="btn btn-warning waves-effect" data-toggle="modal" data-target="#modalTugas">Surat Tugas</button>
                                <button type="button" class="btn btn-success waves-effect" data-toggle="modal" data-target="#modalPindah">Surat Pindah</button>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <!-- Filter -->
                        <form method="GET" class="row clearfix">
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <div class="form-line">
                                        <select class="form-control" name="filter_tahun">
                                            <option value="">-- Tahun --</option>
                                            <?php
                                            $q_tahun = mysqli_query($conn, "SELECT DISTINCT YEAR(tgl_surat) as tahun FROM surat_keluar ORDER BY tahun DESC");
                                            while($r_tahun = mysqli_fetch_assoc($q_tahun)){
                                                $selected = (isset($_GET['filter_tahun']) && $_GET['filter_tahun'] == $r_tahun['tahun']) ? 'selected' : '';
                                                echo "<option value='".$r_tahun['tahun']."' $selected>".$r_tahun['tahun']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <select class="form-control" name="filter_bulan">
                                        <option value="">-- Bulan --</option>
                                        <?php
                                        $bulan_indo = [
                                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                        ];
                                        for($i=1;$i<=12;$i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : ''; ?>><?php echo $bulan_indo[$i]; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <select class="form-control" name="filter_penerima">
                                            <option value="">-- Penerima --</option>
                                            <?php
                                            $q_penerima = mysqli_query($conn, "SELECT DISTINCT penerima FROM surat_keluar ORDER BY penerima ASC");
                                            while($r_penerima = mysqli_fetch_assoc($q_penerima)){
                                                $selected = (isset($_GET['filter_penerima']) && $_GET['filter_penerima'] == $r_penerima['penerima']) ? 'selected' : '';
                                                echo "<option value='".$r_penerima['penerima']."' $selected>".$r_penerima['penerima']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="date" class="form-control" name="filter_tanggal" value="<?php echo isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-info waves-effect" title="Cari"><i class="material-icons">search</i></button>
                                <a href="surat_keluar.php" class="btn btn-default waves-effect" title="Reset"><i class="material-icons">refresh</i></a>
                                <a href="export_surat_keluar_excel.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-success waves-effect" title="Export Excel"><i class="material-icons">grid_on</i></a>
                                <a href="export_surat_keluar_print.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-warning waves-effect" title="Cetak PDF"><i class="material-icons">print</i></a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Nomor Surat</th>
                                        <th>Perihal</th>
                                        <th>Penerima</th>
                                        <th>File</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Pre-fetch Guru data
                                    $guru_data = [];
                                    $q_guru_all = mysqli_query($conn, "SELECT nama FROM guru ORDER BY nama ASC");
                                    while($r_g = mysqli_fetch_assoc($q_guru_all)) {
                                        $guru_data[] = $r_g['nama'];
                                    }

                                    $no = 1;
                                    $query = mysqli_query($conn, "SELECT * FROM surat_keluar $where ORDER BY id DESC");
                                    while ($row = mysqli_fetch_assoc($query)) :
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
                                            <td><?php echo $row['no_surat']; ?></td>
                                            <td><?php echo $row['perihal']; ?></td>
                                            <td><?php echo $row['penerima']; ?></td>
                                            <td>
                                                <a href="print_surat_keluar.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-primary btn-xs waves-effect">
                                                    <i class="material-icons">print</i> Lihat/Unduh
                                                </a>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-circle waves-effect waves-circle waves-float" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                <a href="javascript:void(0);" onclick="confirmDelete('surat_keluar.php?delete=<?php echo $row['id']; ?>')" class="btn btn-danger btn-circle waves-effect waves-circle waves-float">
                                                    <i class="material-icons">delete</i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit <?php echo $row['jenis_surat']; ?></h4>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            
                                                            <!-- Common Fields -->
                                                            <label>Tanggal Surat</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="date" class="form-control" name="tgl_surat" value="<?php echo $row['tgl_surat']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <label><?php echo ($row['jenis_surat'] == 'Tugas') ? 'Nama Kegiatan' : 'Perihal'; ?></label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <textarea name="perihal" class="form-control no-resize" required><?php echo $row['perihal']; ?></textarea>
                                                                </div>
                                                            </div>
                                                            <label>
                                                                <?php 
                                                                if ($row['jenis_surat'] == 'Tugas') echo 'Ditugaskan Kepada';
                                                                elseif ($row['jenis_surat'] == 'Keterangan Pindah') echo 'Nama Siswa / Penerima';
                                                                else echo 'Penerima';
                                                                ?>
                                                            </label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <?php if ($row['jenis_surat'] == 'Tugas'): ?>
                                                                        <?php
                                                                        // Handle multiple selection for Edit
                                                                        $selected_penerima = [];
                                                                        if (strpos($row['penerima'], ';') !== false) {
                                                                            $parts = explode(';', $row['penerima']);
                                                                            $selected_penerima = array_map('trim', $parts);
                                                                        } else {
                                                                            // Legacy Comma Handling
                                                                            
                                                                            // 1. Fuzzy Match: Check if Master Name exists in the Saved String
                                                                            foreach($guru_data as $g_nama) {
                                                                                if (strpos($row['penerima'], $g_nama) !== false) {
                                                                                    $selected_penerima[] = $g_nama;
                                                                                }
                                                                            }
                                                                            
                                                                            // 2. Prefix Match: Split Saved String by comma and check if parts match start of Master Name
                                                                            $parts = explode(',', $row['penerima']);
                                                                            $parts = array_map('trim', $parts);
                                                                            
                                                                            foreach ($parts as $part) {
                                                                                if (empty($part)) continue;
                                                                                foreach ($guru_data as $g_nama) {
                                                                                    // Check if Master Name starts with the Part (Case insensitive)
                                                                                    if (stripos($g_nama, $part) === 0) {
                                                                                        $selected_penerima[] = $g_nama;
                                                                                    }
                                                                                }
                                                                            }
                                                                            
                                                                            // Remove duplicates
                                                                            $selected_penerima = array_unique($selected_penerima);
                                                                        }
                                        ?>
                                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                            <?php foreach($guru_data as $index => $g_nama): ?>
                                                <div class="demo-checkbox">
                                                    <input type="checkbox" id="md_checkbox_<?php echo $row['id']; ?>_<?php echo $index; ?>" name="penerima[]" value="<?php echo htmlspecialchars($g_nama); ?>" class="filled-in chk-col-blue" <?php echo (in_array($g_nama, $selected_penerima)) ? 'checked' : ''; ?> />
                                                    <label for="md_checkbox_<?php echo $row['id']; ?>_<?php echo $index; ?>"><?php echo htmlspecialchars($g_nama); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                                                    <?php else: ?>
                                                                        <input type="text" class="form-control" name="penerima" value="<?php echo $row['penerima']; ?>" required>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Type Specific Fields -->
                                                            <?php if ($row['jenis_surat'] == 'Undangan'): ?>
                                                                <label>Hari/Tanggal Acara</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <input type="date" class="form-control" name="acara_hari_tanggal" value="<?php echo $row['acara_hari_tanggal']; ?>">
                                                                    </div>
                                                                </div>
                                                                <label>Waktu Acara</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <input type="time" class="form-control" name="acara_waktu" value="<?php echo $row['acara_waktu']; ?>">
                                                                    </div>
                                                                </div>
                                                                <label>Tempat Acara</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <input type="text" class="form-control" name="acara_tempat" value="<?php echo $row['acara_tempat']; ?>">
                                                                    </div>
                                                                </div>
                                                                <label>Keperluan</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="keperluan" class="form-control no-resize"><?php echo $row['keperluan']; ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <label>Keterangan</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="keterangan" class="form-control no-resize"><?php echo $row['keterangan']; ?></textarea>
                                                                    </div>
                                                                </div>

                                                            <?php elseif ($row['jenis_surat'] == 'Pemberitahuan'): ?>
                                                                <label>Pembuka Surat</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="pembuka_surat" class="form-control no-resize" rows="4"><?php echo $row['pembuka_surat']; ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <label>Isi Surat</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="isi_surat" class="form-control ckeditor"><?php echo !empty($row['isi_surat']) ? $row['isi_surat'] : $row['keterangan']; ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <label>Penutup Surat</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="penutup_surat" class="form-control no-resize" rows="4"><?php echo $row['penutup_surat']; ?></textarea>
                                                                    </div>
                                                                </div>

                                                            <?php elseif ($row['jenis_surat'] == 'Tugas'): ?>
                                                                <label>Lokasi Kegiatan</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <input type="text" class="form-control" name="acara_tempat" value="<?php echo $row['acara_tempat']; ?>" required>
                                                                    </div>
                                                                </div>

                                                                <label>Waktu Kegiatan</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <input type="text" class="form-control" name="acara_waktu" value="<?php echo $row['acara_waktu']; ?>" required>
                                                                    </div>
                                                                </div>

                                                                <?php
                                                                    $is_range = (strpos($row['acara_hari_tanggal'], ' s.d ') !== false);
                                                                    $tgl_single = $is_range ? '' : $row['acara_hari_tanggal'];
                                                                    $parts = $is_range ? explode(' s.d ', $row['acara_hari_tanggal']) : [];
                                                                    $tgl_start = $is_range ? $parts[0] : '';
                                                                    $tgl_end = $is_range ? $parts[1] : '';
                                                                ?>

                                                                <label>Durasi Kegiatan</label>
                                                                <div class="form-group">
                                                                    <input name="durasi_kegiatan" type="radio" id="radio_1_<?php echo $row['id']; ?>" value="1" <?php echo !$is_range ? 'checked' : ''; ?> class="with-gap radio-col-blue durasi-radio" data-id="<?php echo $row['id']; ?>" />
                                                                    <label for="radio_1_<?php echo $row['id']; ?>">1 Hari</label>
                                                                    
                                                                    <input name="durasi_kegiatan" type="radio" id="radio_2_<?php echo $row['id']; ?>" value="more" <?php echo $is_range ? 'checked' : ''; ?> class="with-gap radio-col-blue durasi-radio" data-id="<?php echo $row['id']; ?>" />
                                                                    <label for="radio_2_<?php echo $row['id']; ?>">Lebih dari 1 Hari</label>
                                                                </div>

                                                                <div id="date_single_<?php echo $row['id']; ?>" style="<?php echo $is_range ? 'display: none;' : ''; ?>">
                                                                    <label>Tanggal Kegiatan</label>
                                                                    <div class="form-group">
                                                                        <div class="form-line">
                                                                            <input type="date" class="form-control" name="tgl_kegiatan_single" value="<?php echo $tgl_single; ?>" <?php echo !$is_range ? 'required' : ''; ?>>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="date_range_<?php echo $row['id']; ?>" style="<?php echo !$is_range ? 'display: none;' : ''; ?>">
                                                                    <div class="row clearfix">
                                                                        <div class="col-sm-6">
                                                                            <label>Mulai Tanggal</label>
                                                                            <div class="form-group">
                                                                                <div class="form-line">
                                                                                    <input type="date" class="form-control" name="tgl_kegiatan_start" value="<?php echo $tgl_start; ?>" <?php echo $is_range ? 'required' : ''; ?>>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label>Sampai Tanggal</label>
                                                                            <div class="form-group">
                                                                                <div class="form-line">
                                                                                    <input type="date" class="form-control" name="tgl_kegiatan_end" value="<?php echo $tgl_end; ?>" <?php echo $is_range ? 'required' : ''; ?>>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            <?php elseif ($row['jenis_surat'] == 'Keterangan Pindah'): ?>
                                                                <label>Keterangan / Tujuan Pindah</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="keterangan" class="form-control no-resize"><?php echo $row['keterangan']; ?></textarea>
                                                                    </div>
                                                                </div>

                                                            <?php else: // Default fallback for other types ?>
                                                                <label>Keterangan</label>
                                                                <div class="form-group">
                                                                    <div class="form-line">
                                                                        <textarea name="keterangan" class="form-control no-resize"><?php echo $row['keterangan']; ?></textarea>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" name="edit" class="btn btn-link waves-effect">SIMPAN</button>
                                                            <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Surat Undangan -->
<div class="modal fade" id="modalUndangan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Surat Undangan</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="jenis_surat" value="Undangan">
                    <label>Tanggal Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_surat" required>
                        </div>
                    </div>
                    <label>Perihal</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="perihal" class="form-control no-resize" required></textarea>
                        </div>
                    </div>
                    <label>Penerima</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="penerima" required>
                        </div>
                    </div>
                    <label>Hari/Tanggal Acara</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="acara_hari_tanggal">
                        </div>
                    </div>
                    <label>Waktu Acara</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="time" class="form-control" name="acara_waktu">
                        </div>
                    </div>
                    <label>Tempat Acara</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="acara_tempat">
                        </div>
                    </div>
                    <label>Keperluan</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="keperluan" class="form-control no-resize"></textarea>
                        </div>
                    </div>
                    <label>Keterangan</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="keterangan" class="form-control no-resize"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-link waves-effect">SIMPAN</button>
                    <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Surat Pemberitahuan -->
<div class="modal fade" id="modalPemberitahuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Surat Pemberitahuan</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="jenis_surat" value="Pemberitahuan">
                    <label>Tanggal Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_surat" required>
                        </div>
                    </div>
                    <label>Perihal</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="perihal" class="form-control no-resize" required></textarea>
                        </div>
                    </div>
                    <label>Penerima</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="penerima" required>
                        </div>
                    </div>
                    <label>Pembuka Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="pembuka_surat" class="form-control no-resize" rows="4">Assalamu'alaikum Wr. Wb.

Dengan ini kami memberitahukan bahwa:</textarea>
                        </div>
                    </div>
                    <label>Isi Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="isi_surat" class="form-control ckeditor"></textarea>
                        </div>
                    </div>
                    <label>Penutup Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="penutup_surat" class="form-control no-resize" rows="4">Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.

Wassalamu'alaikum Wr. Wb.</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-link waves-effect">SIMPAN</button>
                    <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Surat Tugas -->
<div class="modal fade" id="modalTugas" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Surat Tugas</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="jenis_surat" value="Tugas">
                    <label>Tanggal Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_surat" required>
                        </div>
                    </div>
                    
                    <label>Nama Kegiatan</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="nama_kegiatan" class="form-control no-resize" required placeholder="Contoh: Mengikuti Kegiatan Workshop..."></textarea>
                        </div>
                    </div>
                    
                    <label>Ditugaskan Kepada</label>
                    <div class="form-group">
                        <div class="form-line">
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                <?php
                                foreach($guru_data as $index => $g_nama) {
                                    echo '<div class="demo-checkbox">
                                            <input type="checkbox" id="add_checkbox_'.$index.'" name="penerima[]" value="'.htmlspecialchars($g_nama).'" class="filled-in chk-col-blue" />
                                            <label for="add_checkbox_'.$index.'">'.htmlspecialchars($g_nama).'</label>
                                          </div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <label>Lokasi Kegiatan</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="lokasi_kegiatan" required>
                        </div>
                    </div>

                    <label>Waktu Kegiatan</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="waktu_kegiatan" placeholder="Contoh: 08.00 WIB - Selesai" required>
                        </div>
                    </div>

                    <label>Durasi Kegiatan</label>
                    <div class="form-group">
                        <input name="durasi_kegiatan" type="radio" id="radio_1" value="1" checked class="with-gap radio-col-blue" />
                        <label for="radio_1">1 Hari</label>
                        <input name="durasi_kegiatan" type="radio" id="radio_2" value="more" class="with-gap radio-col-blue" />
                        <label for="radio_2">Lebih dari 1 Hari</label>
                    </div>

                    <div id="date_single">
                        <label>Tanggal Kegiatan</label>
                        <div class="form-group">
                            <div class="form-line">
                                <input type="date" class="form-control" name="tgl_kegiatan_single">
                            </div>
                        </div>
                    </div>

                    <div id="date_range" style="display: none;">
                        <div class="row clearfix">
                            <div class="col-sm-6">
                                <label>Mulai Tanggal</label>
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="date" class="form-control" name="tgl_kegiatan_start">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label>Sampai Tanggal</label>
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="date" class="form-control" name="tgl_kegiatan_end">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-link waves-effect">SIMPAN</button>
                    <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Surat Pindah -->
<div class="modal fade" id="modalPindah" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Surat Keterangan Pindah</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="jenis_surat" value="Keterangan Pindah">
                    <label>Tanggal Surat</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_surat" required>
                        </div>
                    </div>
                    <label>Perihal</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="perihal" class="form-control no-resize" required></textarea>
                        </div>
                    </div>
                    <label>Nama Siswa / Penerima</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="penerima" required>
                        </div>
                    </div>
                    <label>Keterangan / Tujuan Pindah</label>
                    <div class="form-group">
                        <div class="form-line">
                            <textarea name="keterangan" class="form-control no-resize"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-link waves-effect">SIMPAN</button>
                    <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'template/footer.php'; ?>
<script>
    // Fix CKEditor in Bootstrap Modal
    $.fn.modal.Constructor.prototype.enforceFocus = function() {
        modal_this = this
        $(document).on('focusin.modal', function(e) {
            if (modal_this.$element[0] !== e.target && !modal_this.$element.has(e.target).length &&
                !$(e.target).closest('.cke_dialog, .cke').length) {
                modal_this.$element.trigger('focus')
            }
        })
    };

    // Toggle Date Logic for Surat Tugas (Add Modal)
    $('#modalTugas input[name="durasi_kegiatan"]').on('change', function() {
        if ($(this).val() == '1') {
            $('#date_single').show();
            $('#date_range').hide();
            $('#modalTugas input[name="tgl_kegiatan_single"]').prop('required', true);
            $('#modalTugas input[name="tgl_kegiatan_start"]').prop('required', false);
            $('#modalTugas input[name="tgl_kegiatan_end"]').prop('required', false);
        } else {
            $('#date_single').hide();
            $('#date_range').show();
            $('#modalTugas input[name="tgl_kegiatan_single"]').prop('required', false);
            $('#modalTugas input[name="tgl_kegiatan_start"]').prop('required', true);
            $('#modalTugas input[name="tgl_kegiatan_end"]').prop('required', true);
        }
    });
    // Initialize required state
    $('#modalTugas input[name="tgl_kegiatan_single"]').prop('required', true);

    // Dynamic Date Logic for Edit Modals
    $(document).on('change', '.durasi-radio', function() {
        var id = $(this).data('id');
        var val = $(this).val();
        if (val == '1') {
            $('#date_single_' + id).show();
            $('#date_range_' + id).hide();
            $('#date_single_' + id + ' input').prop('required', true);
            $('#date_range_' + id + ' input').prop('required', false);
        } else {
            $('#date_single_' + id).hide();
            $('#date_range_' + id).show();
            $('#date_single_' + id + ' input').prop('required', false);
            $('#date_range_' + id + ' input').prop('required', true);
        }
    });

    // Fix: Re-initialize/Refresh Selectpicker on Modal Show to ensure it renders correctly and value is linked
    $('.modal').on('shown.bs.modal', function () {
        // Destroy first to ensure clean state, then re-initialize
        // $(this).find('select.selectpicker').selectpicker('destroy'); 
        // Note: Destroy might remove the button, so just refresh is usually safer if already initialized
        $(this).find('select.selectpicker').selectpicker('refresh');
    });

    // Validasi form sebelum submit
     $('form').on('submit', function() {
         var jenis = $(this).find('input[name="jenis_surat"]').val();
         if (jenis == 'Tugas') {
             // Cek apakah ada checkbox yang dicentang
             var checked = $(this).find('input[name="penerima[]"]:checked').length;
             if (checked === 0) {
                 alert('Mohon pilih setidaknya satu penerima tugas!');
                 return false; // Prevent submit
             }
         }
     });
</script>
