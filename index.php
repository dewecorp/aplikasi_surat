<?php
include 'config.php';
include 'template/header.php';
include 'template/sidebar.php';

// Count Data
$q_surat_masuk = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk");
$surat_masuk = mysqli_fetch_assoc($q_surat_masuk)['total'];

$q_surat_keluar = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar");
$surat_keluar = mysqli_fetch_assoc($q_surat_keluar)['total'];

$total_surat = $surat_masuk + $surat_keluar;

$q_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$users = mysqli_fetch_assoc($q_users)['total'];

// Activity Count
$q_activity_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_log");
$total_activity = mysqli_fetch_assoc($q_activity_count)['total'];

// Chart Data
$tahun_ini = date('Y');
$chart_sm = [];
$chart_sk = [];
for($i=1; $i<=12; $i++){
    $q = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk WHERE MONTH(tgl_terima)='$i' AND YEAR(tgl_terima)='$tahun_ini'");
    $chart_sm[] = mysqli_fetch_assoc($q)['total'];
    
    $q = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar WHERE MONTH(tgl_surat)='$i' AND YEAR(tgl_surat)='$tahun_ini'");
    $chart_sk[] = mysqli_fetch_assoc($q)['total'];
}
$chart_sm_json = json_encode($chart_sm);
$chart_sk_json = json_encode($chart_sk);

// Activity Data
$q_activity = mysqli_query($conn, "SELECT a.*, u.nama FROM activity_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC LIMIT 50");

?>

<section class="content">
    <div class="container-fluid">
        <div class="block-header">
            <h2>DASHBOARD</h2>
        </div>

        <!-- Widgets -->
        <div class="row clearfix">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-pink hover-expand-effect">
                    <div class="icon">
                        <i class="material-icons">mail_outline</i>
                    </div>
                    <div class="content">
                        <div class="text">SURAT KELUAR</div>
                        <div class="number count-to" data-from="0" data-to="<?php echo $surat_keluar; ?>" data-speed="1000" data-fresh-interval="20"><?php echo $surat_keluar; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-cyan hover-expand-effect">
                    <div class="icon">
                        <i class="material-icons">mail</i>
                    </div>
                    <div class="content">
                        <div class="text">SURAT MASUK</div>
                        <div class="number count-to" data-from="0" data-to="<?php echo $surat_masuk; ?>" data-speed="1000" data-fresh-interval="20"><?php echo $surat_masuk; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-light-green hover-expand-effect">
                    <div class="icon">
                        <i class="material-icons">forum</i>
                    </div>
                    <div class="content">
                        <div class="text">TOTAL SURAT</div>
                        <div class="number count-to" data-from="0" data-to="<?php echo $total_surat; ?>" data-speed="1000" data-fresh-interval="20"><?php echo $total_surat; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-orange hover-expand-effect">
                    <div class="icon">
                        <i class="material-icons">person_add</i>
                    </div>
                    <div class="content">
                        <div class="text">PENGGUNA</div>
                        <div class="number count-to" data-from="0" data-to="<?php echo $users; ?>" data-speed="1000" data-fresh-interval="20"><?php echo $users; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- #END# Widgets -->
        
        <div class="row clearfix">
            <!-- Line Chart -->
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>GRAFIK SURAT TAHUN <?php echo $tahun_ini; ?></h2>
                    </div>
                    <div class="body">
                        <canvas id="line_chart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <!-- #END# Line Chart -->
        </div>

        <div class="row clearfix">
            <!-- Timeline -->
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header">
                        <h2>
                            AKTIVITAS TERBARU
                            <small>Total Aktivitas: <?php echo $total_activity; ?></small>
                        </h2>
                    </div>
                    <div class="body">
                        <div class="activity-scroll">
                            <ul class="activity-timeline">
                                <?php while($act = mysqli_fetch_assoc($q_activity)): ?>
                                <li>
                                    <?php 
                                        $icon = 'info';
                                        $bg_color = 'bg-blue';
                                        if($act['activity_type'] == 'login') { $icon = 'input'; $bg_color = 'bg-green'; }
                                        if($act['activity_type'] == 'logout') { $icon = 'output'; $bg_color = 'bg-orange'; }
                                        if($act['activity_type'] == 'backup') { $icon = 'backup'; $bg_color = 'bg-purple'; }
                                        if($act['activity_type'] == 'create') { $icon = 'add'; $bg_color = 'bg-cyan'; }
                                        if($act['activity_type'] == 'update') { $icon = 'edit'; $bg_color = 'bg-amber'; }
                                        if($act['activity_type'] == 'delete') { $icon = 'delete'; $bg_color = 'bg-red'; }
                                    ?>
                                    <div class="timeline-badge <?php echo $bg_color; ?>">
                                        <i class="material-icons"><?php echo $icon; ?></i>
                                    </div>
                                    <div class="timeline-panel">
                                        <div class="timeline-heading">
                                            <h4 class="media-heading">
                                                <?php echo $act['nama'] ? $act['nama'] : 'System'; ?> 
                                                <small style="float: right; text-align: right;">
                                                    <?php echo time_ago($act['timestamp']); ?>
                                                    <br>
                                                    <span style="font-size: 11px;"><?php echo date('d M Y H:i', strtotime($act['timestamp'])); ?></span>
                                                </small>
                                            </h4>
                                        </div>
                                        <div class="timeline-body">
                                            <p><?php echo $act['description']; ?></p>
                                        </div>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($q_activity) == 0): ?>
                                    <li>
                                        <div class="timeline-panel">
                                            <div class="timeline-body">
                                                <p>Belum ada aktivitas.</p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- #END# Timeline -->
        </div>
    </div>
</section>

<script>
window.onload = function() {
    new Chart(document.getElementById("line_chart").getContext("2d"), {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"],
            datasets: [{
                label: "Surat Masuk",
                data: <?php echo $chart_sm_json; ?>,
                borderColor: 'rgba(0, 188, 212, 0.75)',
                backgroundColor: 'rgba(0, 188, 212, 0.3)',
                pointBorderColor: 'rgba(0, 188, 212, 0)',
                pointBackgroundColor: 'rgba(0, 188, 212, 0.9)',
                pointBorderWidth: 1
            }, {
                label: "Surat Keluar",
                data: <?php echo $chart_sk_json; ?>,
                borderColor: 'rgba(233, 30, 99, 0.75)',
                backgroundColor: 'rgba(233, 30, 99, 0.3)',
                pointBorderColor: 'rgba(233, 30, 99, 0)',
                pointBackgroundColor: 'rgba(233, 30, 99, 0.9)',
                pointBorderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                display: true,
                position: 'bottom'
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });
}
</script>

<?php include 'template/footer.php'; ?>