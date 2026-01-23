<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Handle Add
if (isset($_POST['add'])) {
    $tgl_surat = $_POST['tgl_surat'];
    $jenis_surat = $_POST['jenis_surat'];
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $penerima = mysqli_real_escape_string($conn, $_POST['penerima']);
    
    // Generate Nomor Surat
    $tahun = date('Y', strtotime($tgl_surat));
    $bulan = date('n', strtotime($tgl_surat));
    $romawi = getRomawi($bulan);
    
    // Ambil nomor terakhir di tahun ini
    $q_last = mysqli_query($conn, "SELECT no_surat FROM surat_keluar WHERE YEAR(tgl_surat) = '$tahun' ORDER BY id DESC LIMIT 1");
    if (mysqli_num_rows($q_last) > 0) {
        $last_data = mysqli_fetch_assoc($q_last);
        $last_no = intval(substr($last_data['no_surat'], 0, 4));
        $next_no = $last_no + 1;
    } else {
        $next_no = 1;
    }
    
    $no_surat = sprintf('%04d', $next_no) . '/MI.SF/' . $romawi . '/' . $tahun;

    $query = "INSERT INTO surat_keluar (tgl_surat, no_surat, jenis_surat, perihal, penerima) VALUES ('$tgl_surat', '$no_surat', '$jenis_surat', '$perihal', '$penerima')";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['user_id'], 'create', 'Membuat surat keluar no: ' . $no_surat);
        $_SESSION['success'] = "Surat keluar berhasil dibuat";
    } else {
        $_SESSION['error'] = "Gagal membuat surat: " . mysqli_error($conn);
    }
    echo "<script>window.location='surat_keluar.php';</script>";
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $tgl_surat = $_POST['tgl_surat'];
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $penerima = mysqli_real_escape_string($conn, $_POST['penerima']);
    
    // Nomor surat tidak berubah saat edit untuk menjaga konsistensi urutan, kecuali diminta. 
    // Di sini kita asumsikan nomor surat tetap, hanya konten yang diedit.
    
    $query = "UPDATE surat_keluar SET tgl_surat='$tgl_surat', perihal='$perihal', penerima='$penerima' WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        // Ambil no surat untuk log
        $q_log = mysqli_query($conn, "SELECT no_surat FROM surat_keluar WHERE id='$id'");
        $d_log = mysqli_fetch_assoc($q_log);
        log_activity($_SESSION['user_id'], 'update', 'Mengubah surat keluar no: ' . $d_log['no_surat']);

        $_SESSION['success'] = "Surat keluar berhasil diubah";
    } else {
        $_SESSION['error'] = "Gagal mengubah surat: " . mysqli_error($conn);
    }
    echo "<script>window.location='surat_keluar.php';</script>";
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
    echo "<script>window.location='surat_keluar.php';</script>";
}

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
                                <button type="button" class="btn btn-primary waves-effect" data-toggle="modal" data-target="#addModal">
                                    <i class="material-icons">add</i> Buat Surat Baru
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <!-- Filter -->
                        <form method="GET" class="row clearfix">
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="number" class="form-control" name="filter_tahun" placeholder="Tahun" value="<?php echo isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <select class="form-control show-tick" name="filter_bulan">
                                        <option value="">-- Semua Bulan --</option>
                                        <?php for($i=1;$i<=12;$i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="text" class="form-control" name="filter_penerima" placeholder="Penerima" value="<?php echo isset($_GET['filter_penerima']) ? $_GET['filter_penerima'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="date" class="form-control" name="filter_tanggal" value="<?php echo isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-info waves-effect">Cari</button>
                                <a href="surat_keluar.php" class="btn btn-default waves-effect">Reset</a>
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
                                                        <h4 class="modal-title">Edit Surat Keluar</h4>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <div class="form-group form-float">
                                                                <div class="form-line">
                                                                    <input type="date" class="form-control" name="tgl_surat" value="<?php echo $row['tgl_surat']; ?>" required>
                                                                    <label class="form-label">Tanggal Surat</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group form-float">
                                                                <div class="form-line">
                                                                    <textarea name="perihal" class="form-control no-resize" required><?php echo $row['perihal']; ?></textarea>
                                                                    <label class="form-label">Perihal</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group form-float">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="penerima" value="<?php echo $row['penerima']; ?>" required>
                                                                    <label class="form-label">Penerima</label>
                                                                </div>
                                                            </div>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Surat Baru</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Jenis Surat</label>
                        <select class="form-control show-tick" name="jenis_surat" required>
                            <option value="Undangan">Surat Undangan</option>
                            <option value="Pemberitahuan">Surat Pemberitahuan</option>
                            <option value="Tugas">Surat Tugas</option>
                            <option value="Keterangan Pindah">Surat Keterangan Pindah</option>
                        </select>
                    </div>
                    <div class="form-group form-float">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_surat" required>
                            <label class="form-label">Tanggal Surat</label>
                        </div>
                    </div>
                    <div class="form-group form-float">
                        <div class="form-line">
                            <textarea name="perihal" class="form-control no-resize" required></textarea>
                            <label class="form-label">Perihal</label>
                        </div>
                    </div>
                    <div class="form-group form-float">
                        <div class="form-line">
                            <input type="text" class="form-control" name="penerima" required>
                            <label class="form-label">Penerima</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-link waves-effect">GENERATE / SIMPAN</button>
                    <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'template/footer.php'; ?>
