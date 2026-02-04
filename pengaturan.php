<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Cek Role, hanya admin yang boleh akses
if (strtolower(trim($_SESSION['role'])) != 'admin') {
    echo "<script>window.location='index.php';</script>";
    exit();
}

// Check if data exists, if not create default
$check = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan (nama_yayasan, nama_madrasah) VALUES ('Yayasan', 'Madrasah')");
    $check = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
}
$data = mysqli_fetch_assoc($check);

// Handle Update
if (isset($_POST['update'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }

    $id = $_POST['id'];
    $nama_yayasan = mysqli_real_escape_string($conn, $_POST['nama_yayasan']);
    $nama_madrasah = mysqli_real_escape_string($conn, $_POST['nama_madrasah']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $kepala_madrasah = mysqli_real_escape_string($conn, $_POST['kepala_madrasah']);
    $nama_aplikasi = isset($_POST['nama_aplikasi']) ? mysqli_real_escape_string($conn, $_POST['nama_aplikasi']) : '';

    $query_str = "UPDATE pengaturan SET nama_yayasan='$nama_yayasan', nama_madrasah='$nama_madrasah', alamat='$alamat', email='$email', website='$website', kepala_madrasah='$kepala_madrasah', nama_aplikasi='$nama_aplikasi'";

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    // Function to handle upload securely
    function process_upload_pengaturan($input_name, $prefix, $target_dir, $allowed_ext) {
        if (!empty($_FILES[$input_name]['name'])) {
            // Check for upload errors
            if ($_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = "Upload failed with error code: " . $_FILES[$input_name]['error'];
                return false;
            }
            
            $ext = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) {
                // Ensure target directory exists
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $filename = uniqid() . '_' . $prefix . '.' . $ext;
                if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_dir . $filename)) {
                    return $filename;
                } else {
                    $_SESSION['error'] = "Failed to move uploaded file to $target_dir";
                }
            } else {
                $_SESSION['error'] = "Invalid file type. Allowed: " . implode(', ', $allowed_ext);
            }
        }
        return false;
    }

    // Upload Logo
    $logo = process_upload_pengaturan('logo', 'logo', 'assets/images/', $allowed_ext);
    if ($logo) $query_str .= ", logo='$logo'";

    // Upload TTD
    $ttd = process_upload_pengaturan('ttd', 'ttd', 'uploads/', $allowed_ext);
    if ($ttd) $query_str .= ", ttd='$ttd'";

    // Upload Stempel
    $stempel = process_upload_pengaturan('stempel', 'stempel', 'uploads/', $allowed_ext);
    if ($stempel) $query_str .= ", stempel='$stempel'";

    // Upload Background Login
    $bg_login = process_upload_pengaturan('background_login', 'bg', 'assets/images/', $allowed_ext);
    if ($bg_login) $query_str .= ", background_login='$bg_login'";

    $query_str .= " WHERE id='$id'";

    if (mysqli_query($conn, $query_str)) {
        $_SESSION['success'] = "Pengaturan berhasil disimpan";
    } else {
        $_SESSION['error'] = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
    }
    echo "<script>window.location='pengaturan.php';</script>";
    exit();
}
?>

<div class="container-fluid px-5">
        <div class="block-header">
            <h2>Pengaturan</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                    </div>
                    <div class="body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            
                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <label>Nama Aplikasi</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_aplikasi" value="<?php echo isset($data['nama_aplikasi']) ? htmlspecialchars($data['nama_aplikasi']) : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label>Nama Yayasan</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_yayasan" value="<?php echo htmlspecialchars($data['nama_yayasan']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label>Nama Madrasah</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_madrasah" value="<?php echo htmlspecialchars($data['nama_madrasah']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label>Alamat</label>
                            <div class="form-group">
                                <div class="form-line">
                                    <textarea name="alamat" cols="30" rows="3" class="form-control no-resize" required><?php echo htmlspecialchars($data['alamat']); ?></textarea>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label>Email</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($data['email']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label>Website</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($data['website']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label>Nama Kepala Madrasah</label>
                            <div class="form-group">
                                <div class="form-line">
                                    <input type="text" class="form-control" name="kepala_madrasah" value="<?php echo htmlspecialchars($data['kepala_madrasah']); ?>" required>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-4 mb-3">
                                    <label>Upload Logo Madrasah</label>
                                    <input type="file" name="logo" class="form-control">
                                    <?php if (isset($data['logo']) && $data['logo']): ?>
                                        <div class="mt-2">
                                            <img src="assets/images/<?php echo $data['logo']; ?>" class="img-thumbnail" style="max-height: 100px; max-width: 100%;" alt="Logo">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-4 mb-3">
                                    <label>Upload Tanda Tangan (PNG/Transparan)</label>
                                    <input type="file" name="ttd" class="form-control">
                                    <?php if ($data['ttd']): ?>
                                        <div class="mt-2">
                                            <img src="uploads/<?php echo $data['ttd']; ?>" class="img-thumbnail" style="max-height: 100px; max-width: 100%;" alt="TTD">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-4 mb-3">
                                    <label>Upload Stempel (PNG/Transparan)</label>
                                    <input type="file" name="stempel" class="form-control">
                                    <?php if ($data['stempel']): ?>
                                        <div class="mt-2">
                                            <img src="uploads/<?php echo $data['stempel']; ?>" class="img-thumbnail" style="max-height: 100px; max-width: 100%;" alt="Stempel">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row clearfix">
                                <div class="col-sm-12 mb-3">
                                    <label>Background Login</label>
                                    <input type="file" name="background_login" class="form-control">
                                    <?php if (isset($data['background_login']) && $data['background_login']): ?>
                                        <div class="mt-2">
                                            <img src="assets/images/<?php echo $data['background_login']; ?>" class="img-thumbnail" style="max-height: 200px; max-width: 100%;" alt="Background Login">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button type="submit" name="update" class="btn btn-primary m-t-15"><i class="fas fa-save"></i> SIMPAN PENGATURAN</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>

<?php include 'template/footer.php'; ?>
