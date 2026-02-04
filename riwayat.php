<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Filter Logic
$where_masuk = "WHERE 1=1";
$where_keluar = "WHERE 1=1";

if (isset($_GET['filter_tahun']) && !empty($_GET['filter_tahun'])) {
    $ft = mysqli_real_escape_string($conn, $_GET['filter_tahun']);
    $where_masuk .= " AND YEAR(tgl_surat) = '$ft'";
    $where_keluar .= " AND YEAR(tgl_surat) = '$ft'";
}
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $fb = mysqli_real_escape_string($conn, $_GET['filter_bulan']);
    $where_masuk .= " AND MONTH(tgl_surat) = '$fb'";
    $where_keluar .= " AND MONTH(tgl_surat) = '$fb'";
}
if (isset($_GET['filter_pihak']) && !empty($_GET['filter_pihak'])) {
    $fp = mysqli_real_escape_string($conn, $_GET['filter_pihak']);
    $where_masuk .= " AND pengirim LIKE '%$fp%'";
    $where_keluar .= " AND penerima LIKE '%$fp%'";
}

?>

<div class="container-fluid px-5">
        <div class="block-header">
            <h2>Riwayat Surat</h2>
        </div>

        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                    </div>
                    <div class="body">
                         <!-- Filter -->
                        <form method="GET" class="row clearfix">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <select class="form-control" name="filter_tahun">
                                            <option value="">-- Tahun Surat --</option>
                                            <?php
                                            // Get distinct years from both tables
                                            $q_tahun = mysqli_query($conn, "
                                                SELECT DISTINCT YEAR(tgl_surat) as tahun FROM surat_masuk 
                                                UNION 
                                                SELECT DISTINCT YEAR(tgl_surat) as tahun FROM surat_keluar 
                                                ORDER BY tahun DESC
                                            ");
                                            while($r_tahun = mysqli_fetch_assoc($q_tahun)){
                                                $selected = (isset($_GET['filter_tahun']) && $_GET['filter_tahun'] == $r_tahun['tahun']) ? 'selected' : '';
                                                echo "<option value='".$r_tahun['tahun']."' $selected>".$r_tahun['tahun']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <select class="form-control" name="filter_bulan">
                                            <option value="">-- Bulan --</option>
                                            <?php
                                            $bulan_indo = [
                                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                            ];
                                            for($i=1;$i<=12;$i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : ''; ?>><?php echo $bulan_indo[$i]; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <div class="form-line">
                                        <select class="form-control" name="filter_pihak">
                                            <option value="">-- Penerima/Pengirim --</option>
                                            <?php
                                            $q_pihak = mysqli_query($conn, "
                                                SELECT DISTINCT pengirim as nama FROM surat_masuk 
                                                UNION 
                                                SELECT DISTINCT penerima as nama FROM surat_keluar 
                                                ORDER BY nama ASC
                                            ");
                                            while($r_pihak = mysqli_fetch_assoc($q_pihak)){
                                                $selected = (isset($_GET['filter_pihak']) && $_GET['filter_pihak'] == $r_pihak['nama']) ? 'selected' : '';
                                                echo "<option value='".$r_pihak['nama']."' $selected>".$r_pihak['nama']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-info" title="Cari"><i class="fas fa-search"></i></button>
                                <a href="riwayat.php" class="btn btn-secondary" title="Reset"><i class="fas fa-sync"></i></a>
                                <a href="export_riwayat_excel.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-success" title="Export Excel"><i class="fas fa-file-excel"></i></a>
                                <a href="export_riwayat_print.php?<?php echo http_build_query($_GET); ?>" target="_blank" class="btn btn-warning" title="Cetak PDF"><i class="fas fa-print"></i></a>
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
                                        <th>Penerima/Pengirim</th>
                                        <th>File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query_sql = "SELECT 'Masuk' as tipe, id, tgl_surat, no_surat, perihal, pengirim as pihak_lain, file, created_at FROM surat_masuk $where_masuk
                                                  UNION ALL
                                                  SELECT 'Keluar' as tipe, id, tgl_surat, no_surat, perihal, penerima as pihak_lain, '' as file, created_at FROM surat_keluar $where_keluar
                                                  ORDER BY created_at DESC";
                                    
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
                                                <?php if ($row['tipe'] == 'Keluar'): ?>
                                                    <a href="print_surat_keluar.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-print"></i> Lihat PDF
                                                    </a>
                                                <?php elseif (!empty($row['file']) && file_exists('uploads/' . $row['file'])): ?>
                                                    <a href="uploads/<?php echo $row['file']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-download"></i> Lihat
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

<?php include 'template/footer.php'; ?>
