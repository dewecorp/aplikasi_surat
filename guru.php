<?php
require_once 'session_init.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Cek Role, hanya admin yang boleh akses
if (strtolower(trim($_SESSION['role'] ?? '')) != 'admin') {
    echo "<script>window.location='index.php';</script>";
    exit();
}

// Handle Add
if (isset($_POST['add'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
    $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jk = mysqli_real_escape_string($conn, $_POST['jk']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tgl_lahir = !empty($_POST['tgl_lahir']) ? "'" . mysqli_real_escape_string($conn, $_POST['tgl_lahir']) . "'" : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "INSERT INTO guru (nuptk, nama, jk, tempat_lahir, tgl_lahir, status) VALUES ('$nuptk', '$nama', '$jk', '$tempat_lahir', $tgl_lahir, '$status')";
    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['user_id'], 'create', 'Menambahkan data guru: ' . $nama);
        $_SESSION['success'] = "Data guru berhasil ditambahkan";
    } else {
        $_SESSION['error'] = "Gagal menambahkan data: " . mysqli_error($conn);
    }
    session_write_close();
    header("Location: guru.php");
    exit();
}

// Handle Edit
if (isset($_POST['edit'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
    $id = $_POST['id'];
    $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jk = isset($_POST['jk']) ? mysqli_real_escape_string($conn, $_POST['jk']) : '';
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tgl_lahir = !empty($_POST['tgl_lahir']) ? "'" . mysqli_real_escape_string($conn, $_POST['tgl_lahir']) . "'" : "NULL";
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
    
    if (empty($jk) || empty($status)) {
        $_SESSION['error'] = "Gagal mengubah data: Jenis Kelamin dan Status harus dipilih";
        session_write_close();
        header("Location: guru.php");
        exit();
    }
    
    $query = "UPDATE guru SET nuptk='$nuptk', nama='$nama', jk='$jk', tempat_lahir='$tempat_lahir', tgl_lahir=$tgl_lahir, status='$status' WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        log_activity($_SESSION['user_id'], 'update', 'Mengubah data guru: ' . $nama);
        $_SESSION['success'] = "Data guru berhasil diubah";
    } else {
        $_SESSION['error'] = "Gagal mengubah data: " . mysqli_error($conn);
    }
    session_write_close();
    header("Location: guru.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
    $id = $_GET['delete'];
    
    // Get name for log
    $q_del = mysqli_query($conn, "SELECT nama FROM guru WHERE id='$id'");
    $d_del = mysqli_fetch_assoc($q_del);
    $nama_del = $d_del['nama'];

    if (mysqli_query($conn, "DELETE FROM guru WHERE id='$id'")) {
        log_activity($_SESSION['user_id'], 'delete', 'Menghapus data guru: ' . $nama_del);
        $_SESSION['success'] = "Data guru berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data";
    }
    session_write_close();
    header("Location: guru.php");
    exit();
}

// Handle Multiple Delete
if (isset($_POST['hapus_multiple'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
    if (!empty($_POST['pilih'])) {
        $ids = $_POST['pilih'];
        $count = count($ids);
        $ids_string = implode(',', array_map('intval', $ids));
        
        if (mysqli_query($conn, "DELETE FROM guru WHERE id IN ($ids_string)")) {
            log_activity($_SESSION['user_id'], 'delete', 'Menghapus ' . $count . ' data guru secara multiple');
            $_SESSION['success'] = "$count data guru berhasil dihapus";
        } else {
            $_SESSION['error'] = "Gagal menghapus data: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Tidak ada data yang dipilih";
    }
    session_write_close();
    header("Location: guru.php");
    exit();
}

// Handle Multiple Edit
if (isset($_POST['edit_multiple'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
    if (isset($_POST['id']) && is_array($_POST['id'])) {
        $count = 0;
        foreach ($_POST['id'] as $key => $id) {
            $id = intval($id);
            $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk'][$key]);
            $nama = mysqli_real_escape_string($conn, $_POST['nama'][$key]);
            $jk = $_POST['jk'][$key];
            $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir'][$key]);
            $tgl_lahir = $_POST['tgl_lahir'][$key];
            $status = $_POST['status'][$key];

            $query = "UPDATE guru SET nuptk='$nuptk', nama='$nama', jk='$jk', tempat_lahir='$tempat_lahir', tgl_lahir='$tgl_lahir', status='$status' WHERE id='$id'";
            if (mysqli_query($conn, $query)) {
                $count++;
            }
        }
        if ($count > 0) {
            log_activity($_SESSION['user_id'], 'update', 'Mengubah ' . $count . ' data guru secara multiple');
            $_SESSION['success'] = "$count data guru berhasil diperbarui";
        } else {
            $_SESSION['error'] = "Tidak ada data yang berubah atau terjadi kesalahan";
        }
    } else {
        $_SESSION['error'] = "Data tidak valid";
    }
    session_write_close();
    header("Location: guru.php");
    exit();
}

include 'template/header.php';
include 'template/sidebar.php';
?>

<div class="container-fluid px-5">
        <div class="block-header">
            <h2>Data Guru</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <div class="header-dropdown m-r--5 d-flex flex-wrap">
                                <button type="button" class="btn btn-warning btn-icon-split btn-sm mr-2 mb-2" id="btn-edit-multiple" onclick="showEditMultiple()" style="display:none;">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                    <span class="text">Edit Terpilih</span>
                                </button>
                                <button type="button" class="btn btn-danger mr-2 mb-2" id="btn-hapus-multiple" onclick="confirmDeleteMultiple()" style="display:none;">
                                    <i class="fas fa-trash"></i> Hapus Terpilih
                                </button>
                                <a href="export_guru_excel.php?csrf_token=<?php echo generate_csrf_token(); ?>" target="_blank" class="btn btn-success mr-2 mb-2" title="Export Excel"><i class="fas fa-file-excel"></i></a>
                                <a href="export_guru_print.php?csrf_token=<?php echo generate_csrf_token(); ?>" target="_blank" class="btn btn-warning mr-2 mb-2" title="Cetak PDF"><i class="fas fa-print"></i></a>
                                <button type="button" class="btn btn-info mr-2 mb-2" data-toggle="modal" data-target="#importModal">
                                    <i class="fas fa-file-upload"></i> Import Excel
                                </button>
                                <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#addModal">
                                    <i class="fas fa-plus"></i> Tambah Guru
                                </button>
                        </div>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <form method="POST" id="form-hapus">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th width="10" style="text-align:center;">
                                            <input type="checkbox" id="check-all" class="filled-in chk-col-red">
                                            <label for="check-all" style="margin-bottom:0; height:10px; min-height:10px; padding-left:25px;"></label>
                                        </th>
                                        <th>No</th>
                                        <th>NUPTK</th>
                                        <th>Nama Guru</th>
                                        <th>L/P</th>
                                        <th>TTL</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = mysqli_query($conn, "SELECT * FROM guru ORDER BY nama ASC");
                                    while ($row = mysqli_fetch_assoc($query)) :
                                    ?>
                                        <tr>
                                            <td align="center">
                                                <input type="checkbox" name="pilih[]" value="<?php echo $row['id']; ?>" id="chk_<?php echo $row['id']; ?>" class="filled-in chk-col-red check-item">
                                                <label for="chk_<?php echo $row['id']; ?>" style="margin-bottom:0; height:10px; min-height:10px; padding-left:25px;"></label>
                                            </td>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $row['nuptk']; ?></td>
                                            <td><?php echo $row['nama']; ?></td>
                                            <td><?php echo $row['jk']; ?></td>
                                            <td><?php echo $row['tempat_lahir'] . ', ' . (!empty($row['tgl_lahir']) ? date('d-m-Y', strtotime($row['tgl_lahir'])) : ''); ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'Guru Kelas'): ?>
                                                    <span class="badge badge-success">Guru Kelas</span>
                                                <?php elseif ($row['status'] == 'Guru Mapel'): ?>
                                                    <span class="badge badge-warning">Guru Mapel</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($row['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-circle" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="javascript:void(0);" onclick="confirmDelete('guru.php?delete=<?php echo $row['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>')" class="btn btn-danger btn-circle">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Guru</h4>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <label>NUPTK</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="nuptk" value="<?php echo $row['nuptk']; ?>">
                                                                </div>
                                                            </div>
                                                            <label>Nama Guru</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="nama" value="<?php echo $row['nama']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Jenis Kelamin</label>
                                                                <div class="demo-radio-button">
                                                                    <input name="jk" type="radio" id="radio_l_<?php echo $row['id']; ?>" value="L" <?php echo ($row['jk'] == 'L') ? 'checked' : ''; ?> required />
                                                                    <label for="radio_l_<?php echo $row['id']; ?>">Laki-laki</label>
                                                                    <input name="jk" type="radio" id="radio_p_<?php echo $row['id']; ?>" value="P" <?php echo ($row['jk'] == 'P') ? 'checked' : ''; ?> />
                                                                    <label for="radio_p_<?php echo $row['id']; ?>">Perempuan</label>
                                                                </div>
                                                            </div>
                                                            <label>Tempat Lahir</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="tempat_lahir" value="<?php echo $row['tempat_lahir']; ?>">
                                                                </div>
                                                            </div>
                                                            <label>Tanggal Lahir</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="date" class="form-control" name="tgl_lahir" value="<?php echo $row['tgl_lahir']; ?>">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Status</label>
                                                                <div class="demo-radio-button">
                                                                    <input name="status" type="radio" id="radio_s1_<?php echo $row['id']; ?>" value="Guru Kelas" <?php echo ($row['status'] == 'Guru Kelas') ? 'checked' : ''; ?> required />
                                                                    <label for="radio_s1_<?php echo $row['id']; ?>">Guru Kelas</label>
                                                                    <input name="status" type="radio" id="radio_s2_<?php echo $row['id']; ?>" value="Guru Mapel" <?php echo ($row['status'] == 'Guru Mapel') ? 'checked' : ''; ?> />
                                                                    <label for="radio_s2_<?php echo $row['id']; ?>">Guru Mapel</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" name="edit" class="btn btn-success">SIMPAN</button>
                                                            <button type="button" class="btn btn-danger" data-dismiss="modal">TUTUP</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Guru</h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <label>NUPTK</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="nuptk">
                        </div>
                    </div>
                    <label>Nama Guru</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <div class="demo-radio-button">
                            <input name="jk" type="radio" id="radio_l_add" value="L" required />
                            <label for="radio_l_add">Laki-laki</label>
                            <input name="jk" type="radio" id="radio_p_add" value="P" />
                            <label for="radio_p_add">Perempuan</label>
                        </div>
                    </div>
                    <label>Tempat Lahir</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="tempat_lahir">
                        </div>
                    </div>
                    <label>Tanggal Lahir</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="date" class="form-control" name="tgl_lahir">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="demo-radio-button">
                            <input name="status" type="radio" id="radio_s1_add" value="Guru Kelas" required />
                            <label for="radio_s1_add">Guru Kelas</label>
                            <input name="status" type="radio" id="radio_s2_add" value="Guru Mapel" />
                            <label for="radio_s2_add">Guru Mapel</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-success">SIMPAN</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Multiple Modal -->
<div class="modal fade" id="editMultipleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Guru Terpilih</h4>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-body">
                    <input type="hidden" name="edit_multiple" value="1">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>NUPTK</th>
                                    <th>Nama Guru</th>
                                    <th width="10%">L/P</th>
                                    <th>Tempat Lahir</th>
                                    <th>Tanggal Lahir</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="edit_multiple_tbody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">SIMPAN PERUBAHAN</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">TUTUP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Import Data Guru</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="csrf_token_import" value="<?php echo generate_csrf_token(); ?>">
                <div class="alert alert-info">
                    Silahkan download template excel terlebih dahulu untuk memastikan format data yang benar.<br>
                    <a href="download_template_guru.php" class="btn btn-warning m-t-10" target="_blank">Download Template</a>
                </div>
                
                <div id="drop-zone" style="border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer; background-color: #f9f9f9;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ccc;"></i>
                    <p style="margin-top: 10px; color: #777;">Drag & Drop file Excel disini atau klik untuk memilih file</p>
                    <input type="file" id="fileInput" name="file" style="display: none;" accept=".xlsx, .xls">
                </div>
                <div id="file-info" class="m-t-10 text-center" style="display: none; font-weight: bold;"></div>
                
                <div class="progress m-t-20" style="display: none;">
                    <div id="progressBar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span class="sr-only">0% Complete</span>
                    </div>
                </div>
                <div id="upload-status" class="m-t-10 text-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnUpload" disabled>UPLOAD</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">TUTUP</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var dropZone = document.getElementById('drop-zone');
        var fileInput = document.getElementById('fileInput');
        var fileInfo = document.getElementById('file-info');
        var btnUpload = document.getElementById('btnUpload');
        var progressBar = document.getElementById('progressBar');
        var progressContainer = document.querySelector('.progress');
        var uploadStatus = document.getElementById('upload-status');
        var selectedFile = null;

        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            handleFile(this.files[0]);
        });

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#2196F3';
            dropZone.style.backgroundColor = '#e3f2fd';
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#f9f9f9';
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#f9f9f9';
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });

        function handleFile(file) {
            // Check file type (allow xlsx and xls)
            // Note: MIME types can vary, checking extension is also good practice
            var fileName = file.name.toLowerCase();
            if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
                selectedFile = file;
                fileInfo.style.display = 'block';
                fileInfo.textContent = "File terpilih: " + file.name;
                btnUpload.disabled = false;
                uploadStatus.textContent = '';
            } else {
                alert("Mohon upload file Excel (.xlsx atau .xls)");
                fileInfo.style.display = 'none';
                btnUpload.disabled = true;
                selectedFile = null;
            }
        }

        btnUpload.addEventListener('click', function() {
            if (!selectedFile) return;

            var formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('csrf_token', document.getElementById('csrf_token_import').value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'import_guru.php', true);

            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            btnUpload.disabled = true;
            uploadStatus.innerHTML = '<span class="text-info">Sedang mengupload dan memproses data...</span>';

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressBar.textContent = Math.round(percentComplete) + '%';
                }
            };

            xhr.onload = function() {
                if (xhr.status == 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status == 'success') {
                            uploadStatus.innerHTML = '<span class="text-success">' + response.message + '</span>';
                            progressBar.className = 'progress-bar progress-bar-success';
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            uploadStatus.innerHTML = '<span class="text-danger">' + response.message + '</span>';
                            btnUpload.disabled = false;
                            progressBar.className = 'progress-bar progress-bar-danger';
                        }
                    } catch (e) {
                        uploadStatus.innerHTML = '<span class="text-danger">Terjadi kesalahan respon server: ' + xhr.responseText + '</span>';
                        console.error(xhr.responseText);
                        btnUpload.disabled = false;
                    }
                } else {
                    uploadStatus.innerHTML = '<span class="text-danger">Upload gagal. Status: ' + xhr.status + '</span>';
                    btnUpload.disabled = false;
                }
            };

            xhr.onerror = function() {
                uploadStatus.innerHTML = '<span class="text-danger">Terjadi kesalahan koneksi.</span>';
                btnUpload.disabled = false;
            };

            xhr.send(formData);
        });
    });
</script>

<?php include 'template/footer.php'; ?>

<script>
    $(function () {
        // Check All functionality
        $('#check-all').click(function () {
            $('.check-item').prop('checked', this.checked);
            toggleButtons();
        });

        // Individual Check functionality
        $(document).on('change', '.check-item', function () {
            var check = ($('.check-item').filter(':checked').length == $('.check-item').length);
            $('#check-all').prop('checked', check);
            toggleButtons();
        });
    });

    function toggleButtons() {
        if ($('.check-item:checked').length > 0) {
            $('#btn-hapus-multiple').show();
            $('#btn-edit-multiple').show();
        } else {
            $('#btn-hapus-multiple').hide();
            $('#btn-edit-multiple').hide();
        }
    }

    function showEditMultiple() {
        var ids = [];
        $('.check-item:checked').each(function() {
            ids.push($(this).val());
        });
        
        if (ids.length == 0) return;
        
        $.ajax({
            url: 'get_guru_data.php',
            type: 'POST',
            data: {
                ids: ids.join(','),
                csrf_token: $('#csrf_token_import').val()
            },
            dataType: 'json',
            success: function(response) {
                var tbody = '';
                $.each(response, function(i, item) {
                    tbody += '<tr>';
                    tbody += '<td align="center">' + (i+1) + '<input type="hidden" name="id[]" value="' + item.id + '"></td>';
                    tbody += '<td><div class="form-group" style="margin-bottom:0"><div class="form-line"><input type="text" class="form-control" name="nuptk[]" value="' + item.nuptk + '"></div></div></td>';
                    tbody += '<td><div class="form-group" style="margin-bottom:0"><div class="form-line"><input type="text" class="form-control" name="nama[]" value="' + item.nama + '" required></div></div></td>';
                    
                    var selL = item.jk == 'L' ? 'selected' : '';
                    var selP = item.jk == 'P' ? 'selected' : '';
                    tbody += '<td><select class="form-control" name="jk[]"><option value="L" '+selL+'>L</option><option value="P" '+selP+'>P</option></select></td>';
                    
                    tbody += '<td><div class="form-group" style="margin-bottom:0"><div class="form-line"><input type="text" class="form-control" name="tempat_lahir[]" value="' + item.tempat_lahir + '"></div></div></td>';
                    tbody += '<td><div class="form-group" style="margin-bottom:0"><div class="form-line"><input type="date" class="form-control" name="tgl_lahir[]" value="' + item.tgl_lahir + '"></div></div></td>';
                    
                    var selS1 = item.status == 'Guru Kelas' ? 'selected' : '';
                    var selS2 = item.status == 'Guru Mapel' ? 'selected' : '';
                    tbody += '<td><select class="form-control" name="status[]"><option value="Guru Kelas" '+selS1+'>Guru Kelas</option><option value="Guru Mapel" '+selS2+'>Guru Mapel</option></select></td>';
                    
                    tbody += '</tr>';
                });
                $('#edit_multiple_tbody').html(tbody);
                $('#editMultipleModal').modal('show');
            }
        });
    }

    function confirmDeleteMultiple() {
        var count = $('.check-item:checked').length;
        if (count == 0) {
            return;
        }

        swal({
            title: "Apakah anda yakin?",
            text: "Anda akan menghapus " + count + " data guru yang dipilih. Data yang dihapus tidak dapat dikembalikan!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
            closeOnConfirm: false
        }, function () {
            $('#form-hapus').append('<input type="hidden" name="hapus_multiple" value="1">');
            $('#form-hapus').submit();
        });
    }
</script>
