<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Check if data exists, if not create default
$check = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan (nama_yayasan, nama_madrasah) VALUES ('Yayasan', 'Madrasah')");
    $check = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
}
$data = mysqli_fetch_assoc($check);

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $nama_yayasan = mysqli_real_escape_string($conn, $_POST['nama_yayasan']);
    $nama_madrasah = mysqli_real_escape_string($conn, $_POST['nama_madrasah']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $kepala_madrasah = mysqli_real_escape_string($conn, $_POST['kepala_madrasah']);
    $nama_aplikasi = mysqli_real_escape_string($conn, $_POST['nama_aplikasi']);

    $query_str = "UPDATE pengaturan SET nama_yayasan='$nama_yayasan', nama_madrasah='$nama_madrasah', alamat='$alamat', email='$email', website='$website', kepala_madrasah='$kepala_madrasah', nama_aplikasi='$nama_aplikasi'";

    // Upload Logo
    if ($_FILES['logo']['name']) {
        $logo = time() . '_logo_' . basename($_FILES["logo"]["name"]);
        move_uploaded_file($_FILES["logo"]["tmp_name"], "assets/images/" . $logo);
        $query_str .= ", logo='$logo'";
    }

    // Upload TTD
    if ($_FILES['ttd']['name']) {
        $ttd = time() . '_ttd_' . basename($_FILES["ttd"]["name"]);
        move_uploaded_file($_FILES["ttd"]["tmp_name"], "uploads/" . $ttd);
        $query_str .= ", ttd='$ttd'";
    }

    // Upload Stempel
    if ($_FILES['stempel']['name']) {
        $stempel = time() . '_stempel_' . basename($_FILES["stempel"]["name"]);
        move_uploaded_file($_FILES["stempel"]["tmp_name"], "uploads/" . $stempel);
        $query_str .= ", stempel='$stempel'";
    }

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

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>PENGATURAN</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            INFORMASI MADRASAH
                        </h2>
                    </div>
                    <div class="body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label>Nama Yayasan</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_yayasan" value="<?php echo $data['nama_yayasan']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label>Nama Madrasah</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_madrasah" value="<?php echo $data['nama_madrasah']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label>Alamat</label>
                            <div class="form-group">
                                <div class="form-line">
                                    <textarea name="alamat" cols="30" rows="3" class="form-control no-resize" required><?php echo $data['alamat']; ?></textarea>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label>Email</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="email" class="form-control" name="email" value="<?php echo $data['email']; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label>Website</label>
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="website" value="<?php echo $data['website']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label>Nama Kepala Madrasah</label>
                            <div class="form-group">
                                <div class="form-line">
                                    <input type="text" class="form-control" name="kepala_madrasah" value="<?php echo $data['kepala_madrasah']; ?>" required>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-4">
                                    <label>Upload Logo Madrasah</label>
                                    <input type="file" name="logo" class="form-control">
                                    <?php if (isset($data['logo']) && $data['logo']): ?>
                                        <div class="m-t-10">
                                            <img src="assets/images/<?php echo $data['logo']; ?>" height="50" alt="Logo">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-4">
                                    <label>Upload Tanda Tangan (PNG/Transparan)</label>
                                    <input type="file" name="ttd" class="form-control">
                                    <?php if ($data['ttd']): ?>
                                        <div class="m-t-10">
                                            <img src="uploads/<?php echo $data['ttd']; ?>" height="50" alt="TTD">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-4">
                                    <label>Upload Stempel (PNG/Transparan)</label>
                                    <input type="file" name="stempel" class="form-control">
                                    <?php if ($data['stempel']): ?>
                                        <div class="m-t-10">
                                            <img src="uploads/<?php echo $data['stempel']; ?>" height="50" alt="Stempel">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <label>Background Login</label>
                                    <input type="file" name="background_login" class="form-control">
                                    <?php if (isset($data['background_login']) && $data['background_login']): ?>
                                        <div class="m-t-10">
                                            <img src="assets/images/<?php echo $data['background_login']; ?>" height="100" alt="Background Login">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button type="submit" name="update" class="btn btn-primary m-t-15 waves-effect">SIMPAN PENGATURAN</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'template/footer.php'; ?>
