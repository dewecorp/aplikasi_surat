<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Handle Add
if (isset($_POST['add'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Upload Foto
    $foto = 'default.jpg';
    if ($_FILES['foto']['name']) {
        $target_dir = "uploads/";
        $foto = time() . '_' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $foto;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
    }

    $query = "INSERT INTO users (nama, username, password, role, foto) VALUES ('$nama', '$username', '$password', '$role', '$foto')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data pengguna berhasil ditambahkan";
    } else {
        $_SESSION['error'] = "Gagal menambahkan data: " . mysqli_error($conn);
    }
    echo "<script>window.location='pengguna.php';</script>";
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = $_POST['role'];
    
    $query_str = "UPDATE users SET nama='$nama', username='$username', role='$role'";
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query_str .= ", password='$password'";
    }

    if ($_FILES['foto']['name']) {
        $target_dir = "uploads/";
        $foto = time() . '_' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $foto;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
        $query_str .= ", foto='$foto'";
    }

    $query_str .= " WHERE id='$id'";

    if (mysqli_query($conn, $query_str)) {
        // Update Session jika user yang diedit adalah user yang sedang login
        if ($id == $_SESSION['user_id']) {
            $_SESSION['nama'] = $nama;
            if (isset($foto)) {
                $_SESSION['foto'] = $foto;
            }
        }
        $_SESSION['success'] = "Data pengguna berhasil diubah";
    } else {
        $_SESSION['error'] = "Gagal mengubah data: " . mysqli_error($conn);
    }
    echo "<script>window.location='pengguna.php';</script>";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Cek admin
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE id='$id'"));
    if ($cek['role'] == 'admin') {
        $_SESSION['error'] = "Akun Admin tidak bisa dihapus!";
    } else {
        if (mysqli_query($conn, "DELETE FROM users WHERE id='$id'")) {
            $_SESSION['success'] = "Data pengguna berhasil dihapus";
        } else {
            $_SESSION['error'] = "Gagal menghapus data";
        }
    }
    echo "<script>window.location='pengguna.php';</script>";
}
?>

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>DATA PENGGUNA</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            DAFTAR PENGGUNA
                        </h2>
                        <ul class="header-dropdown m-r--5">
                            <li class="dropdown">
                                <a href="export_pengguna_excel.php" target="_blank" class="btn btn-success waves-effect" title="Export Excel"><i class="material-icons">grid_on</i></a>
                                <a href="export_pengguna_print.php" target="_blank" class="btn btn-warning waves-effect" title="Cetak PDF"><i class="material-icons">print</i></a>
                                <button type="button" class="btn btn-primary waves-effect" data-toggle="modal" data-target="#addModal">
                                    <i class="material-icons">add</i> Tambah Pengguna
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
                                    while ($row = mysqli_fetch_assoc($query)) :
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <?php if ($row['foto'] != 'default.jpg' && file_exists('uploads/' . $row['foto'])): ?>
                                                    <img src="uploads/<?php echo $row['foto']; ?>" width="50" height="50" alt="User" style="border-radius: 50%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background-color: <?php echo getAvatarColor($row['nama']); ?>; color: white; border-radius: 50%; text-align: center; line-height: 50px; font-weight: bold; font-size: 20px; display: inline-block;">
                                                        <?php echo getInitials($row['nama']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['nama']; ?></td>
                                            <td><?php echo $row['username']; ?></td>
                                            <td>
                                                <?php if ($row['role'] == 'admin'): ?>
                                                    <span class="label label-success">Admin</span>
                                                <?php else: ?>
                                                    <span class="label label-info">Tata Usaha</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-circle waves-effect waves-circle waves-float" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                <?php if ($row['role'] != 'admin'): ?>
                                                    <a href="javascript:void(0);" onclick="confirmDelete('pengguna.php?delete=<?php echo $row['id']; ?>')" class="btn btn-danger btn-circle waves-effect waves-circle waves-float">
                                                        <i class="material-icons">delete</i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Pengguna</h4>
                                                    </div>
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <label>Nama Lengkap</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="nama" value="<?php echo $row['nama']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <label>Username</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="text" class="form-control" name="username" value="<?php echo $row['username']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <label>Password (Kosongkan jika tidak diubah)</label>
                                                            <div class="form-group">
                                                                <div class="form-line">
                                                                    <input type="password" class="form-control" name="password">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Role</label>
                                                                <select class="form-control show-tick" name="role" required>
                                                                    <option value="admin" <?php echo ($row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="tu" <?php echo ($row['role'] == 'tu') ? 'selected' : ''; ?>>Tata Usaha</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Foto</label>
                                                                <input type="file" name="foto" class="form-control">
                                                                <small>Biarkan kosong jika tidak ingin mengubah foto</small>
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
                <h4 class="modal-title">Tambah Pengguna</h4>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <label>Nama Lengkap</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                    </div>
                    <label>Username</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" name="username" required>
                        </div>
                    </div>
                    <label>Password</label>
                    <div class="form-group">
                        <div class="form-line">
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control show-tick" name="role" required>
                            <option value="tu">Tata Usaha</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" class="form-control">
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
