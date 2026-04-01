<?php
require_once 'session_init.php';
include 'config.php';

if (isset($_POST['add'])) {
    $nama_sk = mysqli_real_escape_string($conn, $_POST['nama_sk']);
    $tentang = mysqli_real_escape_string($conn, $_POST['tentang']);
    $menimbang = mysqli_real_escape_string($conn, $_POST['menimbang']);
    $mengingat = mysqli_real_escape_string($conn, $_POST['mengingat']);
    $memperhatikan = mysqli_real_escape_string($conn, $_POST['memperhatikan']);
    $menetapkan = !empty($_POST['menetapkan']) ? json_encode($_POST['menetapkan']) : NULL;
    $lampiran = mysqli_real_escape_string($conn, $_POST['lampiran']);
    $tgl_surat = date('Y-m-d');

    // Handle file upload (optional)
    $file_lampiran_name = '';
    if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] == 0) {
        $file_ext = pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $file_lampiran_name = 'Lampiran_SK_' . $nama_sk . '_' . $tahun . '.pdf';
            move_uploaded_file($_FILES['file_lampiran']['tmp_name'], 'uploads/' . $file_lampiran_name);
        }
    }

    // Generate nomor surat - reset per year, not per month
    // Latest SK gets number 001 (reverse numbering)
    $tahun = date('Y');
    
    // Count total SK this year and get the latest one
    $q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keputusan WHERE YEAR(tgl_surat) = '$tahun'");
    $count_row = mysqli_fetch_assoc($q_count);
    $total_sk = $count_row['total'] ?? 0;
    
    // Get the latest SK (highest ID) to determine next number
    $q_last_no = mysqli_query($conn, "SELECT no_surat FROM surat_keputusan WHERE YEAR(tgl_surat) = '$tahun' ORDER BY id DESC LIMIT 1");
    
    if ($q_last_no && mysqli_num_rows($q_last_no) > 0) {
        $last_no_row = mysqli_fetch_assoc($q_last_no);
        // Extract the number from format: 001/MI.SF/SK/III/2026
        $no_parts = explode('/', $last_no_row['no_surat']);
        $last_no = isset($no_parts[0]) ? (int)$no_parts[0] : 0;
        
        // New SK gets last_no + 1 (sequential forward)
        $next_no = str_pad($last_no + 1, 3, '0', STR_PAD_LEFT);
    } else {
        // First SK of the year
        $next_no = '001';
    }
    
    $no_surat = $next_no . '/MI.SF/SK/' . to_romawi(date('n')) . '/' . $tahun;

    $query = "INSERT INTO surat_keputusan (tgl_surat, no_surat, tentang, menimbang, mengingat, memperhatikan, menetapkan, lampiran, nama_sk, file_lampiran) VALUES ('$tgl_surat', '$no_surat', '$tentang', '$menimbang', '$mengingat', '$memperhatikan', '$menetapkan', '$lampiran', '$nama_sk', '$file_lampiran_name')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data berhasil ditambahkan";
        header("Location: surat_keputusan.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menambahkan data: " . mysqli_error($conn);
    }
}

if (isset($_POST['edit'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nama_sk = mysqli_real_escape_string($conn, $_POST['nama_sk']);
    $tentang = mysqli_real_escape_string($conn, $_POST['tentang']);
    $menimbang = mysqli_real_escape_string($conn, $_POST['menimbang']);
    $mengingat = mysqli_real_escape_string($conn, $_POST['mengingat']);
    $memperhatikan = mysqli_real_escape_string($conn, $_POST['memperhatikan']);
    $menetapkan = !empty($_POST['menetapkan']) ? json_encode($_POST['menetapkan']) : NULL;
    $lampiran = mysqli_real_escape_string($conn, $_POST['lampiran']);

    // Handle file upload (only if new file is uploaded)
    $file_lampiran_update = '';
    if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] == 0) {
        $file_ext = pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $tahun = date('Y');
            $file_lampiran_name = 'Lampiran_SK_' . $nama_sk . '_' . $tahun . '.pdf';
            if (move_uploaded_file($_FILES['file_lampiran']['tmp_name'], 'uploads/' . $file_lampiran_name)) {
                $file_lampiran_update = ", file_lampiran='$file_lampiran_name'";
            }
        }
    }

    $query = "UPDATE surat_keputusan SET tentang='$tentang', menimbang='$menimbang', mengingat='$mengingat', memperhatikan='$memperhatikan', menetapkan='$menetapkan', lampiran='$lampiran', nama_sk='$nama_sk' $file_lampiran_update WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data berhasil diperbarui";
        header("Location: surat_keputusan.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui data: " . mysqli_error($conn);
    }
}

if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $query = "DELETE FROM surat_keputusan WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data berhasil dihapus";
        header("Location: surat_keputusan.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menghapus data: " . mysqli_error($conn);
    }
}

if (isset($_POST['copy'])) {
    $copy_id = mysqli_real_escape_string($conn, $_POST['copy_id']);
    $nama_sk = mysqli_real_escape_string($conn, $_POST['nama_sk']);
    $tgl_surat = mysqli_real_escape_string($conn, $_POST['tgl_surat']);

    // Handle file upload (optional)
    $file_lampiran_name = '';
    if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] == 0) {
        $file_ext = pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $file_lampiran_name = 'Lampiran_SK_' . $nama_sk . '_' . $tahun . '.pdf';
            move_uploaded_file($_FILES['file_lampiran']['tmp_name'], 'uploads/' . $file_lampiran_name);
        }
    }

    // Generate nomor surat - reset per year, not per month
    // Latest SK gets number 001 (reverse numbering)
    $tahun = date('Y', strtotime($tgl_surat));
    
    // Count total SK this year and get the latest one
    $q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keputusan WHERE YEAR(tgl_surat) = '$tahun'");
    $count_row = mysqli_fetch_assoc($q_count);
    $total_sk = $count_row['total'] ?? 0;
    
    // Get the latest SK (highest ID) to determine next number
    $q_last_no = mysqli_query($conn, "SELECT no_surat FROM surat_keputusan WHERE YEAR(tgl_surat) = '$tahun' ORDER BY id DESC LIMIT 1");
    
    if ($q_last_no && mysqli_num_rows($q_last_no) > 0) {
        $last_no_row = mysqli_fetch_assoc($q_last_no);
        // Extract the number from format: 001/MI.SF/SK/III/2026
        $no_parts = explode('/', $last_no_row['no_surat']);
        $last_no = isset($no_parts[0]) ? (int)$no_parts[0] : 0;
        
        // New SK gets last_no + 1 (sequential forward)
        $next_no = str_pad($last_no + 1, 3, '0', STR_PAD_LEFT);
    } else {
        // First SK of the year
        $next_no = '001';
    }
    
    $no_surat = $next_no . '/MI.SF/SK/' . to_romawi(date('n', strtotime($tgl_surat))) . '/' . $tahun;

    $query = "INSERT INTO surat_keputusan (tgl_surat, no_surat, tentang, menimbang, mengingat, memperhatikan, menetapkan, lampiran, nama_sk, file_lampiran) SELECT '$tgl_surat', '$no_surat', tentang, menimbang, mengingat, memperhatikan, menetapkan, lampiran, '$nama_sk', '$file_lampiran_name' FROM surat_keputusan WHERE id='$copy_id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data berhasil dicopy";
        header("Location: surat_keputusan.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal menyalin data: " . mysqli_error($conn);
    }
}

// Ambil data surat keputusan - sorted by date DESC, then by ID DESC for same dates
$tahun_filter = isset($_GET['tahun']) && $_GET['tahun'] != '' ? "WHERE YEAR(tgl_surat) = '" . mysqli_real_escape_string($conn, $_GET['tahun']) . "'" : "";
$query = mysqli_query($conn, "SELECT * FROM surat_keputusan $tahun_filter ORDER BY tgl_surat DESC, id DESC");

include 'template/header.php';
include 'template/sidebar.php';
?>

<div class="container-fluid px-5">
    <div class="block-header">
        <h2>Surat Keputusan</h2>
    </div>

    <div class="row clearfix">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="card">
                <div class="header">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModal">
                                <i class="fas fa-plus"></i> Tambah Surat Keputusan
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <select id="filterTahun" class="form-control" style="width: 150px; display: inline-block;" onchange="filterByTahun()">
                                <option value="">Semua Tahun</option>
                                <?php
                                // Get available years
                                $q_tahun = mysqli_query($conn, "SELECT DISTINCT YEAR(tgl_surat) as tahun FROM surat_keputusan ORDER BY tahun DESC");
                                while ($row_tahun = mysqli_fetch_assoc($q_tahun)) {
                                    $tahun = $row_tahun['tahun'];
                                    $selected = (isset($_GET['tahun']) && $_GET['tahun'] == $tahun) ? 'selected' : '';
                                    echo "<option value='$tahun' $selected>$tahun</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                            <thead>
                                <tr>
                                    <th data-orderable="false">No</th>
                                    <th>Tanggal Surat</th>
                                    <th>Nomor Surat</th>
                                    <th>Nama SK</th>
                                    <th>SK Tentang</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($query)) :
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
                                        <td><?php echo $row['no_surat']; ?></td>
                                        <td><?php echo $row['nama_sk'] ?? '-'; ?></td>
                                        <td><?php echo $row['tentang']; ?></td>
                                        <td>
                                            <a href="print_surat_keputusan.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-success">Cetak</a>
                                            <button type="button" class="btn btn-sm btn-info copy-btn" data-id="<?php echo $row['id']; ?>"><i class="fas fa-copy"></i> Copy</button>
                                            <button type="button" class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $row['id']; ?>">Edit</button>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger" onclick="confirmDelete('surat_keputusan.php?delete=<?php echo $row['id']; ?>')">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Surat Keputusan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nama_sk">Nama SK (untuk filename)</label>
                        <input type="text" id="nama_sk" name="nama_sk" class="form-control" placeholder="Contoh: Penetapan_KKTP_2025" required>
                        <small class="text-muted">Akan menjadi: SK_Penetapan_KKTP_2025_2026.pdf</small>
                    </div>
                    <div class="form-group">
                        <label for="tentang">SK Tentang</label>
                        <textarea id="tentang" name="tentang" class="form-control ckeditor"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="menimbang">Menimbang</label>
                        <textarea id="menimbang" name="menimbang" class="form-control ckeditor"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="mengingat">Mengingat</label>
                        <textarea id="mengingat" name="mengingat" class="form-control ckeditor"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="memperhatikan">Memperhatikan</label>
                        <textarea id="memperhatikan" name="memperhatikan" class="form-control ckeditor"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Menetapkan</label>
                        <div id="menetapkan-fields">
                            <div class="input-group mb-2">
                                <textarea name="menetapkan[]" class="form-control" placeholder="Pertama" rows="3"></textarea>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger remove-field">Hapus</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-menetapkan-field" class="btn btn-sm btn-info">Tambah Field</button>
                    </div>
                    <div class="form-group">
                        <label for="lampiran">Lampiran</label>
                        <textarea id="lampiran" name="lampiran" class="form-control ckeditor"></textarea>
                        <small class="text-muted">Ketik konten lampiran di editor</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="file_lampiran">Upload File Lampiran (PDF) - Opsional</label>
                        <input type="file" id="file_lampiran" name="file_lampiran" class="form-control-file" accept=".pdf">
                        <small class="text-muted">Upload file PDF jika sudah ada file jadi (lewati jika ingin ketik di editor)</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Copy Modal -->
<div class="modal fade" id="copyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Copy Surat Keputusan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="copy_id" id="copy_id">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Copy SK ini dengan tahun berbeda. Semua konten akan disalin otomatis.
                    </div>
                    <div class="form-group">
                        <label for="copy_nama_sk">Nama SK (untuk filename)</label>
                        <input type="text" id="copy_nama_sk" name="nama_sk" class="form-control" placeholder="Contoh: Penetapan_KKTP_2027" required>
                        <small class="form-text text-muted">Gunakan nama yang berbeda dari SK asli</small>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Surat</label>
                        <input type="date" name="tgl_surat" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        <small class="form-text text-muted">Tanggal akan menentukan nomor surat & tahun</small>
                    </div>
                    <div class="form-group">
                        <label>File Lampiran (Optional)</label>
                        <input type="file" name="file_lampiran" class="form-control" accept=".pdf">
                        <small class="form-text text-muted">Upload file baru atau kosongkan untuk menggunakan file yang sama</small>
                    </div>
                    <hr>
                    <h6>Content yang akan di-copy:</h6>
                    <ul>
                        <li>SK Tentang ✓</li>
                        <li>Menimbang ✓</li>
                        <li>Mengingat ✓</li>
                        <li>Memperhatikan ✓</li>
                        <li>Menetapkan ✓</li>
                        <li>Lampiran Text ✓</li>
                    </ul>
                    <button type="submit" name="copy" class="btn btn-primary mt-3"><i class="fas fa-copy"></i> Copy SK</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Surat Keputusan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_nama_sk">Nama SK (untuk filename)</label>
                        <input type="text" id="edit_nama_sk" name="nama_sk" class="form-control" placeholder="Contoh: Penetapan_KKTP_2025" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_tentang">SK Tentang</label>
                        <textarea id="edit_tentang" name="tentang" class="form-control ckeditor-edit"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_menimbang">Menimbang</label>
                        <textarea id="edit_menimbang" name="menimbang" class="form-control ckeditor-edit"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_mengingat">Mengingat</label>
                        <textarea id="edit_mengingat" name="mengingat" class="form-control ckeditor-edit"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_memperhatikan">Memperhatikan</label>
                        <textarea id="edit_memperhatikan" name="memperhatikan" class="form-control ckeditor-edit"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Menetapkan</label>
                        <div id="edit-menetapkan-fields">
                            <!-- Fields will be populated by JS -->
                        </div>
                        <button type="button" id="edit-add-menetapkan-field" class="btn btn-sm btn-info">Tambah Field</button>
                    </div>
                    <div class="form-group">
                        <label for="edit_lampiran">Lampiran</label>
                        <textarea id="edit_lampiran" name="lampiran" class="form-control ckeditor-edit"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_file_lampiran">Upload File Lampiran (PDF) - Opsional</label>
                        <input type="file" id="edit_file_lampiran" name="file_lampiran" class="form-control-file" accept=".pdf">
                        <small class="text-muted" id="edit_file_current">Biarkan kosong jika tidak ingin mengganti file.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'template/footer.php';
?>
<!-- Gunakan CKEditor 4 Full Build -->
<script src="https://cdn.ckeditor.com/4.16.2/full/ckeditor.js"></script>
<script>
    // Konfigurasi Global CKEditor
    CKEDITOR.config.versionCheck = false;
    CKEDITOR.config.removePlugins = 'exportpdf';

    $(document).ready(function() {
        // Handle Copy button click
        $('.copy-btn').on('click', function() {
            var id = $(this).data('id');
            $('#copy_id').val(id);
            $('#copyModal').modal('show');
        });

        // Handle Edit button click
        $('.edit-btn').on('click', function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'get_sk_data.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    // 1. Simpan data ke elemen modal untuk diakses nanti
                    $('#editModal').data('sk-data', data);
                    
                    // 2. Tampilkan modal
                    $('#editModal').modal('show');
                },
                error: function(xhr, status, error) {
                    swal("Gagal!", "Terjadi kesalahan saat mengambil data.", "error");
                }
            });
        });

        // Inisialisasi CKEditor untuk form tambah
        $('.ckeditor').each(function() {
            var editorId = $(this).attr('id');
            if (editorId && !CKEDITOR.instances[editorId]) {
                CKEDITOR.replace(editorId);
            }
        });

        // Inisialisasi CKEditor untuk form edit - TUNDA sampai modal dibuka
        // Jangan initialize dulu, akan diinitialize saat modal dibuka

        // Add Menetapkan Field Logic
        function createMenetapkanField(containerId, value = '', index = null) {
            var container = document.getElementById(containerId);
            if (!container) return;
            if (index === null) index = container.children.length;
            var placeholders = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima'];
            var placeholder = placeholders[index] || 'Berikutnya';
            
            var newField = document.createElement('div');
            newField.className = 'input-group mb-2';
            newField.innerHTML = `
                <textarea name="menetapkan[]" class="form-control" placeholder="${placeholder}" rows="3">${value}</textarea>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger remove-field">Hapus</button>
                </div>
            `;
            container.appendChild(newField);
        }

        $('#add-menetapkan-field').on('click', function() { createMenetapkanField('menetapkan-fields'); });
        $('#edit-add-menetapkan-field').on('click', function() { createMenetapkanField('edit-menetapkan-fields'); });
        $(document).on('click', '.remove-field', function() { $(this).closest('.input-group').remove(); });

        // Pastikan data CKEditor diupdate ke textarea sebelum form disubmit
        $('form').on('submit', function() {
            for (var instanceName in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(instanceName)) {
                    CKEDITOR.instances[instanceName].updateElement();
                }
            }
        });

        // Destroy CKEditor instances when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            ['edit_tentang', 'edit_menimbang', 'edit_mengingat', 'edit_memperhatikan', 'edit_lampiran'].forEach(function(editorId) {
                if (CKEDITOR.instances[editorId]) {
                    CKEDITOR.instances[editorId].destroy(true);
                }
            });
        });

        // Event ini berjalan SETIAP KALI modal edit selesai ditampilkan
        $('#editModal').on('shown.bs.modal', function () {
            // Ambil data yang disimpan sebelumnya
            var data = $(this).data('sk-data');
            if (!data) return;

            // Isi field non-editor
            $('#edit_id').val(data.id);
            $('#edit_nama_sk').val(data.nama_sk || '');
            
            // Show current file info if exists
            if (data.file_lampiran) {
                $('#edit_file_current').text('File saat ini: ' + data.file_lampiran);
            } else {
                $('#edit_file_current').text('Biarkan kosong jika tidak ingin mengganti file.');
            }
            
            // Isi textarea fields DULU sebelum initialize CKEditor
            $('#edit_tentang').val(data.tentang || '');
            $('#edit_menimbang').val(data.menimbang || '');
            $('#edit_mengingat').val(data.mengingat || '');
            $('#edit_memperhatikan').val(data.memperhatikan || '');
            $('#edit_lampiran').val(data.lampiran || '');
            
            $('#edit-menetapkan-fields').empty();
            if (data.menetapkan) {
                try {
                    var menetapkan = JSON.parse(data.menetapkan);
                    if (Array.isArray(menetapkan)) {
                        menetapkan.forEach(function(val, idx) {
                            createMenetapkanField('edit-menetapkan-fields', val, idx);
                        });
                    } else { createMenetapkanField('edit-menetapkan-fields', '', 0); }
                } catch (e) { createMenetapkanField('edit-menetapkan-fields', '', 0); }
            } else { createMenetapkanField('edit-menetapkan-fields', '', 0); }

            // Destroy CKEditor instances jika sudah ada, lalu buat ulang
            
            // Helper function to replace editor and set data
            function replaceEditorWithDelay(editorId, data, delay) {
                setTimeout(function() {
                    if (CKEDITOR.instances[editorId]) {
                        CKEDITOR.instances[editorId].destroy(true);
                    }
                    var editor = CKEDITOR.replace(editorId);
                    
                    // Set data after editor is ready
                    editor.on('instanceReady', function() {
                        this.setData(data);
                    });
                }, delay);
            }
            
            // Replace each editor with slight delays to ensure proper initialization
            replaceEditorWithDelay('edit_tentang', data.tentang || '', 0);
            replaceEditorWithDelay('edit_menimbang', data.menimbang || '', 50);
            replaceEditorWithDelay('edit_mengingat', data.mengingat || '', 100);
            replaceEditorWithDelay('edit_memperhatikan', data.memperhatikan || '', 150);
            replaceEditorWithDelay('edit_lampiran', data.lampiran || '', 200);
            
            console.log('Modal setup complete');
        });
    });
    </script>

    <script>
        $(document).ready(function() {
            // Reinitialize DataTable with NO sorting - follow PHP query order
            if ($.fn.DataTable.isDataTable('.js-basic-example')) {
                $('.js-basic-example').DataTable().destroy();
            }
            
            $('.js-basic-example').DataTable({
                ordering: false, // Disable ALL sorting - use PHP query order
                columnDefs: [
                    { orderable: false, targets: [0, 5] } // No and Aksi columns
                ]
            });
            
            // Set selected year from URL parameter
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('tahun')) {
                $('#filterTahun').val(urlParams.get('tahun'));
            }
        });
        
        function filterByTahun() {
            var tahun = $('#filterTahun').val();
            var currentUrl = window.location.href.split('?')[0];
            if (tahun) {
                window.location.href = currentUrl + '?tahun=' + tahun;
            } else {
                window.location.href = currentUrl;
            }
        }
    </script>
</body>
</html>