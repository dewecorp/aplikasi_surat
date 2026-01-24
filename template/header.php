<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get Data Sekolah
$q_instansi = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
$instansi = mysqli_fetch_assoc($q_instansi);
$nama_sekolah = $instansi['nama_madrasah'];
$logo_sekolah = $instansi['logo'];

// Title Page Logic
$page = basename($_SERVER['PHP_SELF'], ".php");
$titles = [
    'index' => 'Dashboard',
    'guru' => 'Data Guru',
    'surat_keluar' => 'Surat Keluar',
    'surat_masuk' => 'Surat Masuk',
    'riwayat' => 'Riwayat',
    'pengguna' => 'Data Pengguna',
    'pengaturan' => 'Pengaturan',
    'backup' => 'Backup & Restore',
    'login' => 'Login'
];
$current_page_title = isset($titles[$page]) ? $titles[$page] : ucwords(str_replace('_', ' ', $page));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title><?php echo $current_page_title; ?> | SIMS</title>
    <!-- Favicon-->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%231e88e5%22><path d=%22M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z%22/></svg>" type="image/svg+xml">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" type="text/css">

    <!-- Bootstrap Core Css -->
    <link href="assets/plugins/bootstrap/css/bootstrap.css" rel="stylesheet">

    <!-- Waves Effect Css -->
    <link href="assets/plugins/node-waves/waves.css" rel="stylesheet" />

    <!-- Animation Css -->
    <link href="assets/plugins/animate-css/animate.css" rel="stylesheet" />

    <!-- Sweetalert Css -->
    <link href="assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" />

    <!-- JQuery DataTable Css -->
    <link href="assets/plugins/jquery-datatable/skin/bootstrap/css/dataTables.bootstrap.css" rel="stylesheet">

    <!-- Bootstrap Select Css -->
    <link href="assets/plugins/bootstrap-select/css/bootstrap-select.css" rel="stylesheet" />

    <!-- Custom Css -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- AdminBSB Themes. You can choose a theme from css/themes instead of get all themes -->
    <link href="assets/css/themes/all-themes.css" rel="stylesheet" />

    <style>
        .sidebar .user-info {
            background: linear-gradient(45deg, #0d47a1, #1976d2) !important;
        }
        /* Custom Gradient Blue Theme */
        .navbar {
            background: linear-gradient(45deg, #0d47a1, #1976d2) !important;
        }
        .sidebar .menu .list li.active > :first-child i, 
        .sidebar .menu .list li.active > :first-child span {
            color: #0d47a1 !important;
        }
        .page-loader-wrapper {
            background: #eee;
        }
    </style>
</head>

<body class="theme-blue">
    <!-- Page Loader -->
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="preloader">
                <div class="spinner-layer pl-blue">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div>
                    <div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
            <p>Please wait...</p>
        </div>
    </div>
    <!-- #END# Page Loader -->
    <!-- Overlay For Sidebars -->
    <div class="overlay"></div>
    <!-- #END# Overlay For Sidebars -->
    
    <!-- Top Bar -->
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-header">
                <a href="javascript:void(0);" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse" aria-expanded="false"></a>
                <a href="javascript:void(0);" class="bars"></a>
                <a class="navbar-brand" href="index.php" style="display: flex; align-items: center;">
                    <?php if (!empty($logo_sekolah) && file_exists('assets/images/' . $logo_sekolah)): ?>
                        <img src="assets/images/<?php echo $logo_sekolah; ?>" alt="Logo" style="height: 35px; margin-right: 10px; filter: drop-shadow(0 0 4px #fff);">
                    <?php endif; ?>
                    <span style="font-size: 18px;">SISTEM MANAJEMEN SURAT | <?php echo strtoupper($nama_sekolah); ?></span>
                </a>
            </div>
            <div class="collapse navbar-collapse" id="navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="javascript:void(0);">
                            <i class="material-icons">access_time</i>
                            <span id="current-time"></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- #Top Bar -->
    
    <script>
        function updateTime() {
            var now = new Date();
            var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('current-time').innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateTime, 1000);
        window.onload = updateTime;
    </script>
