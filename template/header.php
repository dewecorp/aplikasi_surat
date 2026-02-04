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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $current_page_title; ?> | SIMS</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%231e88e5%22><path d=%22M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z%22/></svg>" type="image/svg+xml">
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" rel="stylesheet">
    <style>
        #wrapper {
            display: flex;
        }
        #content-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #content {
            flex: 1 0 auto;
        }
        #content-wrapper footer.sticky-footer {
            margin-top: auto;
        }
        #content > .container-fluid {
            padding-left: 3rem;
            padding-right: 3rem;
        }
        .card .header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e3e6f0;
            background-color: #fff;
        }
        .card .body {
            padding: 1.25rem;
            background-color: #fff;
        }
        .block-header {
            margin-bottom: 1rem;
        }
        /* Override default text colors to black for better visibility */
        body {
            color: #000 !important;
        }
        .text-gray-100, .text-gray-200, .text-gray-300, .text-gray-400, .text-gray-500, .text-gray-600, .text-gray-700, .text-gray-800, .text-gray-900, .text-muted {
            color: #000 !important;
        }
        .table {
            color: #000 !important;
        }
        /* Ensure form labels and inputs are black */
        label, .form-control, input, select, textarea {
            color: #000 !important;
        }

        /* STICKY SIDEBAR & NAVBAR */
        /* Enable sticky behavior by removing overflow clipping from ancestors */
        #content-wrapper {
            overflow: visible !important; 
        }

        #accordionSidebar {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        
        /* Minimalist scrollbar for sidebar */
        #accordionSidebar::-webkit-scrollbar {
            width: 5px;
            display: none; /* Hide scrollbar for cleaner look */
        }
        #accordionSidebar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        #accordionSidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        #content > .navbar {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        /* Fixed Sidebar Header */
        #accordionSidebar .sidebar-brand {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 51;
            background-color: inherit; /* Fallback */
            background-image: inherit; /* Ensure gradient matches */
            width: 100%;
        }
        /* Ensure the background covers the scrolling content */
        .bg-gradient-primary {
            background-color: #4e73df;
            background-image: linear-gradient(180deg,#4e73df 10%,#224abe 100%);
            background-size: cover;
        }
        /* Specifically for the sticky header to match the sidebar background */
        #accordionSidebar .sidebar-brand {
             background: #4e73df; /* Match the top color of the gradient */
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="sidebar-brand-text mx-3">SIMS</div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item <?php echo ($page == 'index') ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Menu</div>
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) == 'admin'): ?>
            <li class="nav-item <?php echo ($page == 'guru') ? 'active' : ''; ?>">
                <a class="nav-link" href="guru.php">
                    <i class="fas fa-user"></i>
                    <span>Data Guru</span></a>
            </li>
            <?php endif; ?>
            <li class="nav-item <?php echo ($page == 'surat_masuk') ? 'active' : ''; ?>">
                <a class="nav-link" href="surat_masuk.php">
                    <i class="fas fa-inbox"></i>
                    <span>Surat Masuk</span></a>
            </li>
            <li class="nav-item <?php echo ($page == 'surat_keluar') ? 'active' : ''; ?>">
                <a class="nav-link" href="surat_keluar.php">
                    <i class="fas fa-paper-plane"></i>
                    <span>Surat Keluar</span></a>
            </li>
            <li class="nav-item <?php echo ($page == 'riwayat') ? 'active' : ''; ?>">
                <a class="nav-link" href="riwayat.php">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span></a>
            </li>
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) == 'admin'): ?>
            <li class="nav-item <?php echo ($page == 'pengguna') ? 'active' : ''; ?>">
                <a class="nav-link" href="pengguna.php">
                    <i class="fas fa-users"></i>
                    <span>Pengguna</span></a>
            </li>
            <li class="nav-item <?php echo ($page == 'pengaturan') ? 'active' : ''; ?>">
                <a class="nav-link" href="pengaturan.php">
                    <i class="fas fa-cogs"></i>
                    <span>Pengaturan</span></a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="backup.php">
                    <i class="fas fa-database"></i>
                    <span>Backup Restore</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0);" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span></a>
            </li>
            <!-- Sidebar Toggler (Sidebar) -->
            <!-- <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div> -->
        </ul>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-dark bg-gradient-primary topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <a class="navbar-brand d-none d-sm-inline-block" href="index.php">
                        <?php if (!empty($logo_sekolah) && file_exists('assets/images/' . $logo_sekolah)): ?>
                            <img src="assets/images/<?php echo $logo_sekolah; ?>" alt="Logo" style="height: 32px; margin-right: 10px;">
                        <?php endif; ?>
                        <span class="h6 mb-0 text-white">SISTEM MANAJEMEN SURAT | <?php echo strtoupper($nama_sekolah); ?></span>
                    </a>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item d-none d-sm-block">
                            <span class="nav-link text-white"><i class="far fa-clock mr-1"></i><span id="current-time"></span></span>
                        </li>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-white small"><?php echo $_SESSION['nama'] ?? 'Pengguna'; ?></span>
                                <i class="fas fa-user-circle fa-lg text-white"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="pengaturan.php"><i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>Pengaturan</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout()"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <script>
                    function updateTime() {
                        var now = new Date();
                        var opt = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                        var el = document.getElementById('current-time');
                        if (el) el.innerText = now.toLocaleDateString('id-ID', opt);
                    }
                    setInterval(updateTime, 1000);
                    updateTime();
                </script>
