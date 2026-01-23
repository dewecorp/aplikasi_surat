<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Filter Logic
$where_masuk = "WHERE 1=1";
$where_keluar = "WHERE 1=1";

if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = $_GET['filter_tahun'];
    $where_masuk .= " AND YEAR(tgl_surat) = '$ft'";
    $where_keluar .= " AND YEAR(tgl_surat) = '$ft'";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = $_GET['filter_bulan'];
    $where_masuk .= " AND MONTH(tgl_surat) = '$fb'";
    $where_keluar .= " AND MONTH(tgl_surat) = '$fb'";
}

?>

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>RIWAYAT SURAT</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            DATA RIWAYAT SURAT (MASUK & KELUAR)
                        </h2>
                    </div>
                    <div class="body">
                         <!-- Filter -->
                        <form method="GET" class="row clearfix">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type="number" class="form-control" name="filter_tahun" placeholder="Tahun Surat" value="<?php echo isset($_GET['filter_tahun']) ? $_GET['filter_tahun'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <select class="form-control show-tick" name="filter_bulan">
                                        <option value="">-- Bulan --</option>
                                        <?php for($i=1;$i<=12;$i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-info waves-effect">Cari</button>
                                <a href="riwayat.php" class="btn btn-default waves-effect">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover js-basic-example dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tipe</th>
                                        <th>No Surat</th>
                                        <th>Tgl Surat</th>
                                        <th>Perihal</th>
                                        <th>Pihak Lain</th>
                                        <th>File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query_sql = "SELECT 'Masuk' as tipe, id, tgl_surat, no_surat, perihal, pengirim as pihak_lain, file FROM surat_masuk $where_masuk
                                                  UNION ALL
                                                  SELECT 'Keluar' as tipe, id, tgl_surat, no_surat, perihal, penerima as pihak_lain, file FROM surat_keluar $where_keluar
                                                  ORDER BY tgl_surat DESC";
                                    
                                    $query = mysqli_query($conn, $query_sql);
                                    if (!$query) {
                                        echo "<tr><td colspan='7'>Error: " . mysqli_error($conn) . "</td></tr>";
                                    } else {
                                        while ($row = mysqli_fetch_assoc($query)) :
                                            $label_class = ($row['tipe'] == 'Masuk') ? 'label-success' : 'label-warning';
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><span class="label <?php echo $label_class; ?>"><?php echo $row['tipe']; ?></span></td>
                                            <td><?php echo $row['no_surat']; ?></td>
                                            <td><?php echo tgl_indo($row['tgl_surat']); ?></td>
                                            <td><?php echo $row['perihal']; ?></td>
                                            <td><?php echo $row['pihak_lain']; ?></td>
                                            <td>
                                                <?php if (!empty($row['file']) && file_exists('uploads/' . $row['file'])): ?>
                                                    <a href="uploads/<?php echo $row['file']; ?>" target="_blank" class="btn btn-primary btn-xs waves-effect">
                                                        <i class="material-icons">file_download</i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile; 
                                    }
                                    ?>
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
