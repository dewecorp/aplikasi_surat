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

    $query_str = "UPDATE pengaturan SET nama_yayasan='$nama_yayasan', nama_madrasah='$nama_madrasah', alamat='$alamat', email='$email', website='$website', kepala_madrasah='$kepala_madrasah'";

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
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_yayasan" value="<?php echo $data['nama_yayasan']; ?>" required>
                                            <label class="form-label">Nama Yayasan</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="nama_madrasah" value="<?php echo $data['nama_madrasah']; ?>" required>
                                            <label class="form-label">Nama Madrasah</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group form-float">
                                <div class="form-line">
                                    <textarea name="alamat" cols="30" rows="3" class="form-control no-resize" required><?php echo $data['alamat']; ?></textarea>
                                    <label class="form-label">Alamat</label>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <input type="email" class="form-control" name="email" value="<?php echo $data['email']; ?>">
                                            <label class="form-label">Email</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group form-float">
                                        <div class="form-line">
                                            <input type="text" class="form-control" name="website" value="<?php echo $data['website']; ?>">
                                            <label class="form-label">Website</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group form-float">
                                <div class="form-line">
                                    <input type="text" class="form-control" name="kepala_madrasah" value="<?php echo $data['kepala_madrasah']; ?>" required>
                                    <label class="form-label">Nama Kepala Madrasah</label>
                                </div>
                            </div>
                            <div class="row clearfix">
                                <div class="col-sm-6">
                                    <label>Upload Tanda Tangan (PNG/Transparan lebih baik)</label>
                                    <input type="file" name="ttd" class="form-control">
                                    <?php if ($data['ttd']): ?>
                                        <div class="m-t-10">
                                            <img src="uploads/<?php echo $data['ttd']; ?>" height="50" alt="TTD">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6">
                                    <label>Upload Stempel (PNG/Transparan lebih baik)</label>
                                    <input type="file" name="stempel" class="form-control">
                                    <?php if ($data['stempel']): ?>
                                        <div class="m-t-10">
                                            <img src="uploads/<?php echo $data['stempel']; ?>" height="50" alt="Stempel">
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
