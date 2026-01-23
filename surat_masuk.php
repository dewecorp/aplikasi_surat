<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Handle Add
if (isset($_POST['add'])) {
    $tgl_terima = $_POST['tgl_terima'];
    $no_surat = mysqli_real_escape_string($conn, $_POST['no_surat']);
    $tgl_surat = $_POST['tgl_surat'];
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $pengirim = mysqli_real_escape_string($conn, $_POST['pengirim']);
    
    // Upload File
    $file_surat = '';
    if ($_FILES['file_surat']['name']) {
        $target_dir = "uploads/";
        $file_surat = time() . '_' . basename($_FILES["file_surat"]["name"]);
        $target_file = $target_dir . $file_surat;
        move_uploaded_file($_FILES["file_surat"]["tmp_name"], $target_file);
    }

    $query = "INSERT INTO surat_masuk (tgl_terima, no_surat, tgl_surat, perihal, pengirim, file) VALUES ('$tgl_terima', '$no_surat', '$tgl_surat', '$perihal', '$pengirim', '$file_surat')";
    
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['user_id'], 'create', 'Menambahkan surat masuk no: ' . $no_surat);
        $_SESSION['success'] = "Surat masuk berhasil disimpan";
    } else {
        $_SESSION['error'] = "Gagal menyimpan surat: " . mysqli_error($conn);
    }
    echo "<script>window.location='surat_masuk.php';</script>";
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $tgl_terima = $_POST['tgl_terima'];
    $no_surat = mysqli_real_escape_string($conn, $_POST['no_surat']);
    $tgl_surat = $_POST['tgl_surat'];
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $pengirim = mysqli_real_escape_string($conn, $_POST['pengirim']);
    
    $query_str = "UPDATE surat_masuk SET tgl_terima='$tgl_terima', no_surat='$no_surat', tgl_surat='$tgl_surat', perihal='$perihal', pengirim='$pengirim'";

    if ($_FILES['file_surat']['name']) {
        $target_dir = "uploads/";
        $file_surat = time() . '_' . basename($_FILES["file_surat"]["name"]);
        $target_file = $target_dir . $file_surat;
        move_uploaded_file($_FILES["file_surat"]["tmp_name"], $target_file);
        $query_str .= ", file='$file_surat'";
    }

    $query_str .= " WHERE id='$id'";

    if (mysqli_query($conn, $query_str)) {
        log_activity($_SESSION['user_id'], 'update', 'Mengubah surat masuk no: ' . $no_surat);
        $_SESSION['success'] = "Surat masuk berhasil diubah";
    } else {
        $_SESSION['error'] = "Gagal mengubah surat: " . mysqli_error($conn);
    }
    echo "<script>window.location='surat_masuk.php';</script>";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $q_del = mysqli_query($conn, "SELECT no_surat FROM surat_masuk WHERE id='$id'");
    $d_del = mysqli_fetch_assoc($q_del);
    $no_surat_del = $d_del['no_surat'];

    if (mysqli_query($conn, "DELETE FROM surat_masuk WHERE id='$id'")) {
        log_activity($_SESSION['user_id'], 'delete', 'Menghapus surat masuk no: ' . $no_surat_del);
        $_SESSION['success'] = "Surat masuk berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus surat";
    }
    echo "<script>window.location='surat_masuk.php';</script>";
}

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
    $fp = $_GET['filter_pengirim'];
    $where .= " AND pengirim LIKE '%$fp%'";
}
if (isset($_GET['filter_tanggal']) && !empty($_GET['filter_tanggal'])) {
    $ftgl = $_GET['filter_tanggal'];
    $where .= " AND tgl_terima = '$ftgl'";
}

?>

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>SURAT MASUK</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            DATA SURAT MASUK
                        </h2>
                        <ul class="header-dropdown m-r--5">
                            <li class="dropdown">
                                <button type="button" class="btn btn-primary waves-effect" data-toggle="modal" data-target="#addModal">
                                    <i class="material-icons">add</i> Tambah Surat Masuk
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
                                        <input type="number" class="form-control" name="filter_tahun" placeholder="Tahun Terima" value="<?php echo isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <select class="form-control show-tick" name="filter_bulan">
                                        <option value="">-- Bulan --</option>
                                        <?php for($i=1;$i<=12;$i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="text" class="form-control" name="filter_pengirim" placeholder="Pengirim" value="<?php echo isset($_GET['filter_pengirim']) ? $_GET['filter_pengirim'] : ''; ?>">
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
                                <a href="surat_masuk.php" class="btn btn-default waves-effect">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tgl Terima</th>
                                        <th>No Surat</th>
                                        <th>Tgl Surat</th>
                                        <th>Perihal</th>
                                        <th>Pengirim</th>
                                        <th>File</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = mysqli_query($conn, "SELECT * FROM surat_masuk $where ORDER BY id DESC");
                                    while ($row = mysqli_fetch_assoc($query)) :
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo tgl_indo($row['tgl_terima']); ?></td>
                                            <td><?php echo $row['no_surat']; ?></td>
                                            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
                                            <td><?php echo $row['perihal']; ?></td>
                                            <td><?php echo $row['pengirim']; ?></td>
                                            <td>
                                                <?php if (!empty($row['file']) && file_exists('uploads/' . $row['file'])): ?>
                                                    <a href="uploads/<?php echo $row['file']; ?>" target="_blank" class="btn btn-primary btn-xs waves-effect">
                                                        <i class="material-icons">file_download</i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-circle waves-effect waves-circle waves-float" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                <a href="javascript:void(0);" onclick="confirmDelete('surat_masuk.php?delete=<?php echo $row['id']; ?>')" class="btn btn-danger btn-circle waves-effect waves-circle waves-float">
                                                    <i class="material-icons">delete</i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Surat Masuk</h4>
                                                    </div>
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <div class="form-group form-float">
                                                                <div class="form-line">
                                                                    <input type="date" class="form-control" name="tgl_terima" value="<?php echo $row['tgl_terima']; ?>" required>
                                                                    <label class="form-label">Tanggal Terima</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group form-float">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="no_surat" value="<?php echo $row['no_surat']; ?>" required>
                                                                    <label class="form-label">Nomor Surat</label>
                                                                </div>
                                                            </div>
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
                                                                    <input type="text" class="form-control" name="pengirim" value="<?php echo $row['pengirim']; ?>" required>
                                                                    <label class="form-label">Pengirim</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>File Surat (Biarkan kosong jika tidak diubah)</label>
                                                                <input type="file" name="file_surat" class="form-control">
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
                <h4 class="modal-title">Tambah Surat Masuk</h4>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group form-float">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_terima" required>
                            <label class="form-label">Tanggal Terima</label>
                        </div>
                    </div>
                    <div class="form-group form-float">
                        <div class="form-line">
                            <input type="text" class="form-control" name="no_surat" required>
                            <label class="form-label">Nomor Surat</label>
                        </div>
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
                            <input type="text" class="form-control" name="pengirim" required>
                            <label class="form-label">Pengirim</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>File Surat</label>
                        <input type="file" name="file_surat" class="form-control">
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
