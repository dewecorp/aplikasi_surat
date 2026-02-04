<?php
include 'config.php';
include 'template/header.php';


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
    $chart_sm[] = (int)mysqli_fetch_assoc($q)['total'];
    
    $q = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar WHERE MONTH(tgl_surat)='$i' AND YEAR(tgl_surat)='$tahun_ini'");
    $chart_sk[] = (int)mysqli_fetch_assoc($q)['total'];
}
$chart_sm_json = json_encode($chart_sm);
$chart_sk_json = json_encode($chart_sk);

// Activity Data
$q_activity = mysqli_query($conn, "SELECT a.*, u.nama FROM activity_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC LIMIT 50");

?>
<style>
.timeline {
    position: relative;
    padding: 20px 0;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}
.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: 11px;
    top: 0;
    bottom: -20px;
    width: 2px;
    background-color: #e3e6f0;
}
.timeline-item:last-child:before {
    display: none;
}
.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #4e73df;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #eaecf4;
    text-align: center;
    line-height: 18px;
    color: white;
    font-size: 10px;
}
.timeline-content {
    padding-bottom: 10px;
}
.timeline-date {
    font-size: 0.85rem;
    color: #858796;
    margin-bottom: 0.5rem;
}
</style>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Surat Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $surat_keluar; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paper-plane fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Surat Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $surat_masuk; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-inbox fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Surat</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_surat; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pengguna</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Surat Tahun <?php echo $tahun_ini; ?></h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="line_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terbaru <small class="text-muted">Total: <?php echo $total_activity; ?></small></h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php while($act = mysqli_fetch_assoc($q_activity)): 
                            $icon = 'fa-bell';
                            $bg = 'bg-info';
                            switch($act['activity_type']) {
                                case 'login': $icon = 'fa-sign-in-alt'; $bg = 'bg-primary'; break;
                                case 'logout': $icon = 'fa-sign-out-alt'; $bg = 'bg-secondary'; break;
                                case 'create': $icon = 'fa-plus'; $bg = 'bg-success'; break;
                                case 'update': $icon = 'fa-edit'; $bg = 'bg-warning'; break;
                                case 'delete': $icon = 'fa-trash'; $bg = 'bg-danger'; break;
                                case 'backup': $icon = 'fa-database'; $bg = 'bg-info'; break;
                                case 'delete_backup': $icon = 'fa-trash-alt'; $bg = 'bg-danger'; break;
                            }
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?php echo $bg; ?> d-flex align-items-center justify-content-center">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?php echo time_ago($act['timestamp']); ?></div>
                                    <h6 class="font-weight-bold text-dark"><?php echo $act['nama'] ? $act['nama'] : 'System'; ?></h6>
                                    <p class="mb-0 text-gray-800"><?php echo $act['description']; ?></p>
                                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($act['timestamp'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($q_activity) == 0): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-secondary"></div>
                                <div class="timeline-content">
                                    <p class="mb-0">Belum ada aktivitas.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    new Chart(document.getElementById("line_chart"), {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"],
            datasets: [{
                label: "Surat Masuk",
                data: <?php echo $chart_sm_json; ?>,
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                pointRadius: 5,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderWidth: 2,
                lineTension: 0
            }, {
                label: "Surat Keluar",
                data: <?php echo $chart_sk_json; ?>,
                borderColor: 'rgba(28, 200, 138, 1)',
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                pointRadius: 5,
                pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(28, 200, 138, 1)',
                pointBorderWidth: 2,
                lineTension: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                xAxes: [{
                    time: {
                        unit: 'date'
                    },
                    gridLines: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 12
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        beginAtZero: true,
                        stepSize: 1,
                        callback: function(value, index, values) {
                            if (Math.floor(value) === value) {
                                return value;
                            }
                        }
                    },
                    gridLines: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    }
                }],
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                titleMarginBottom: 10,
                titleFontColor: '#6e707e',
                titleFontSize: 14,
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                intersect: false,
                mode: 'index',
                caretPadding: 10,
            }
        }
    });
}
</script>

<?php include 'template/footer.php'; ?>
