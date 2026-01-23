<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Function to format file size
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

// Handle Backup
if (isset($_POST['backup_now'])) {
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $return = "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SELECT * FROM " . $table);
        $num_fields = mysqli_num_fields($result);

        $return .= "DROP TABLE IF EXISTS " . $table . ";";
        $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE " . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= "INSERT INTO " . $table . " VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= 'NULL';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    $return .= "SET FOREIGN_KEY_CHECKS=1;";

    // Save file
    $file_name = 'db_backup_' . date("Y-m-d_H-i-s") . '.sql';
    if (!is_dir('backups')) {
        mkdir('backups', 0777, true);
    }
    $handle = fopen('backups/' . $file_name, 'w+');
    fwrite($handle, $return);
    fclose($handle);

    $file_size = filesize('backups/' . $file_name);
    $size_formatted = formatSizeUnits($file_size);

    mysqli_query($conn, "INSERT INTO backup (file_name, file_size) VALUES ('$file_name', '$size_formatted')");
    
    // Log Activity
    log_activity($_SESSION['user_id'], 'backup', 'Melakukan backup database (' . $file_name . ')');
    
    $_SESSION['success'] = "Backup berhasil dibuat!";
    echo "<script>window.location='backup.php';</script>";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $q = mysqli_query($conn, "SELECT file_name FROM backup WHERE id='$id'");
    $row = mysqli_fetch_assoc($q);
    $file = 'backups/' . $row['file_name'];
    
    if (file_exists($file)) {
        unlink($file);
    }
    
    mysqli_query($conn, "DELETE FROM backup WHERE id='$id'");
    $_SESSION['success'] = "Backup berhasil dihapus!";
    echo "<script>window.location='backup.php';</script>";
}

// Handle Restore
if (isset($_POST['restore'])) {
    $id = $_POST['backup_id'];
    $q = mysqli_query($conn, "SELECT file_name FROM backup WHERE id='$id'");
    $row = mysqli_fetch_assoc($q);
    $file_path = 'backups/' . $row['file_name'];
    
    if (file_exists($file_path)) {
        $sql = file_get_contents($file_path);
        if (mysqli_multi_query($conn, $sql)) {
             do {
                // store first result set
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            
            $_SESSION['success'] = "Database berhasil direstore!";
        } else {
            $_SESSION['error'] = "Gagal restore: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "File backup tidak ditemukan!";
    }
    echo "<script>window.location='backup.php';</script>";
}

// Handle Download
if (isset($_GET['download'])) {
    $id = $_GET['download'];
    $q = mysqli_query($conn, "SELECT file_name FROM backup WHERE id='$id'");
    $row = mysqli_fetch_assoc($q);
    $file = 'backups/' . $row['file_name'];

    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
?>

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>BACKUP & RESTORE DATABASE</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            DAFTAR BACKUP DATABASE
                        </h2>
                        <ul class="header-dropdown m-r--5">
                            <li class="dropdown">
                                <form method="POST">
                                    <button type="submit" name="backup_now" class="btn btn-primary waves-effect">
                                        <i class="material-icons">backup</i> Buat Backup Baru
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal Backup</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = mysqli_query($conn, "SELECT * FROM backup ORDER BY created_at DESC");
                                    while ($row = mysqli_fetch_assoc($query)) :
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $row['file_name']; ?></td>
                                            <td><?php echo $row['file_size']; ?></td>
                                            <td><?php echo tgl_indo(date('Y-m-d', strtotime($row['created_at']))) . ' ' . date('H:i', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="backup.php?download=<?php echo $row['id']; ?>" class="btn btn-success btn-xs waves-effect">
                                                    <i class="material-icons">file_download</i> Download
                                                </a>
                                                <button type="button" class="btn btn-warning btn-xs waves-effect" data-toggle="modal" data-target="#restoreModal<?php echo $row['id']; ?>">
                                                    <i class="material-icons">restore</i> Restore
                                                </button>
                                                <a href="javascript:void(0);" onclick="confirmDelete('backup.php?delete=<?php echo $row['id']; ?>')" class="btn btn-danger btn-xs waves-effect">
                                                    <i class="material-icons">delete</i> Hapus
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Restore Modal -->
                                        <div class="modal fade" id="restoreModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Konfirmasi Restore</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin me-restore database dari file <b><?php echo $row['file_name']; ?></b>?</p>
                                                        <p class="col-red">PERINGATAN: Data saat ini akan ditimpa!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="POST">
                                                            <input type="hidden" name="backup_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="restore" class="btn btn-warning waves-effect">YA, RESTORE</button>
                                                            <button type="button" class="btn btn-link waves-effect" data-dismiss="modal">BATAL</button>
                                                        </form>
                                                    </div>
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

<?php include 'template/footer.php'; ?>
