<?php
require_once __DIR__ . '/../session_init.php';
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
/** Penutupan drawer (onclick); penutupan juga dijaga lewat delegasi jQuery di footer. */
$sims_drawer_nav_onclick = ' onclick="window.__simsCloseDrawerNav&&window.__simsCloseDrawerNav();"';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $current_page_title; ?> | SIMS</title>
    <base href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%231e88e5%22><path d=%22M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z%22/></svg>" type="image/svg+xml">
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" rel="stylesheet">
    <style>
        /* Custom Sidebar Width & Sticky Behavior for Desktop */
        @media (min-width: 768px) {
            .sidebar {
                width: 17rem !important; /* Diperlebar dari default 14rem */
            }
            .sidebar .nav-item .collapse {
                left: 17rem !important;
            }
            
            #accordionSidebar {
                position: -webkit-sticky;
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
                z-index: 50;
            }
            
            #accordionSidebar .sidebar-brand {
                position: -webkit-sticky;
                position: sticky;
                top: 0;
                z-index: 51;
                background-color: inherit;
                background-image: inherit;
                width: 100%;
            }
        }
        
        /* Sidebar: kontainer div (markup valid); tetap kolom seperti ul.navbar-nav */
        #accordionSidebar.navbar-nav {
            display: flex;
            flex-direction: column;
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
            width: 100%;
        }

        #wrapper {
            display: flex;
        }
        #content-wrapper {
            flex: 1 1 auto;
            min-width: 0;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: visible !important;
        }
        #content {
            flex: 1 0 auto;
            min-width: 0;
            width: 100%;
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
        .sweet-alert, .sweet-alert * {
            font-family: inherit !important;
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

        /* Lapisan gelap: default tidak tampil (mobile saja lewat .is-visible) */
        #sidebarBackdrop {
            display: none !important;
        }

        /* Mobile: sidebar mengambang di atas konten (tidak mendorong layout) */
        @media (max-width: 767.98px) {
            :root {
                --sims-sidebar-w: min(17rem, 88vw);
            }
            #accordionSidebar.sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                height: 100vh !important;
                width: var(--sims-sidebar-w) !important;
                max-width: 17rem;
                margin: 0 !important;
                /* Di atas backdrop agar link menu dapat diklik */
                z-index: 1052 !important;
                overflow-y: auto !important;
                transition: none;
                box-shadow: none;
            }
            #accordionSidebar.sidebar:not(.toggled) .nav-link,
            #accordionSidebar.sidebar:not(.toggled) .sidebar-brand {
                touch-action: manipulation;
            }
            /* SB Admin: .toggled = tertutup di layar kecil */
            #accordionSidebar.sidebar.toggled {
                transform: translateX(-105%);
                pointer-events: none;
                box-shadow: none;
            }
            #accordionSidebar.sidebar:not(.toggled) {
                transform: translateX(0);
                box-shadow: 0 0.25rem 1.5rem rgba(0, 0, 0, 0.35);
            }
            #content-wrapper {
                width: 100% !important;
                flex: 1 1 auto !important;
                margin-left: 0 !important;
            }
            /* Backdrop: di bawah sidebar, ketuk area gelap = tutup menu */
            #sidebarBackdrop {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 1048;
                background: rgba(0, 0, 0, 0.45);
                -webkit-tap-highlight-color: transparent;
            }
            #sidebarBackdrop.is-visible {
                display: block !important;
                visibility: visible;
                pointer-events: auto !important;
                opacity: 1;
                cursor: pointer;
                touch-action: manipulation;
                /* Di atas topbar (1050) agar ketuk “ruang kosong” termasuk strip atas = menutup (bukan tertangkap navbar) */
                z-index: 1051 !important;
            }
            body.sidebar-mobile-open {
                overflow: hidden;
            }
            #content > .navbar.topbar,
            #content > nav.navbar {
                z-index: 1050 !important;
            }
            /* Saat drawer terbuka: topbar di bawah backdrop; hamburger tetap di atas backdrop */
            body.sidebar-mobile-open #content > .navbar.topbar,
            body.sidebar-mobile-open #content > nav.navbar {
                z-index: 1046 !important;
            }
            body.sidebar-mobile-open #sidebarToggleTop {
                position: relative;
                z-index: 1053 !important;
            }
            #sidebarToggleTop {
                color: #fff !important;
            }
            #sidebarToggleTop:hover,
            #sidebarToggleTop:focus {
                color: #f8f9fc !important;
            }
            #sidebarToggleTop .fa-bars,
            #sidebarToggleTop .fas {
                color: inherit;
            }
        }
    </style>
    <script>
        (function () {
            function simsNavIsMobileViewport() {
                try {
                    if (window.matchMedia && window.matchMedia("(max-width: 767.98px)").matches) {
                        return true;
                    }
                } catch (e) { /* abaikan */ }
                var w = window.innerWidth || document.documentElement.clientWidth || 0;
                return w <= 768;
            }
            window.__simsClearDrawerNavInline = function () {
                var sb = document.getElementById("accordionSidebar");
                if (sb) {
                    sb.style.removeProperty("transform");
                    sb.style.removeProperty("pointer-events");
                }
            };
            /**
             * Tutup drawer mobile. SB Admin 2 tidak menutup otomatis saat klik menu — itu normal.
             * Penutupan harus menyamakan state dengan klik #sidebarToggleTop: body.sidebar-toggled + .sidebar.toggled
             */
            window.__simsCloseDrawerNav = function () {
                var sb = document.getElementById("accordionSidebar");
                if (!sb || sb.classList.contains("toggled")) {
                    return;
                }
                var drawerFlag = document.body.getAttribute("data-sims-drawer-open") === "1";
                if (!simsNavIsMobileViewport() && !drawerFlag) {
                    return;
                }
                var bd = document.getElementById("sidebarBackdrop");
                document.body.classList.add("sidebar-toggled");
                var sidebars = document.querySelectorAll(".sidebar");
                var i;
                for (i = 0; i < sidebars.length; i++) {
                    sidebars[i].classList.add("toggled");
                }
                sb.style.setProperty("transform", "translateX(-105%)", "important");
                sb.style.setProperty("pointer-events", "none", "important");
                if (bd) {
                    bd.classList.remove("is-visible");
                    bd.setAttribute("aria-hidden", "true");
                }
                document.body.classList.remove("sidebar-mobile-open");
                document.body.removeAttribute("data-sims-drawer-open");
            };
        })();
    </script>
</head>

<body id="page-top">
    <div id="wrapper">
        <div class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar" role="navigation">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $sims_drawer_nav_onclick; ?>>
                <div class="sidebar-brand-icon">
                    <?php if (!empty($logo_sekolah) && file_exists('assets/images/' . $logo_sekolah)): ?>
                        <img src="assets/images/<?php echo $logo_sekolah; ?>" alt="Logo" style="height: 50px;">
                    <?php else: ?>
                        <i class="fas fa-envelope-open-text"></i>
                    <?php endif; ?>
                </div>
                <div class="sidebar-brand-text mx-3">SIMS</div>
            </a>
            <hr class="sidebar-divider my-0">
            <div class="nav-item <?php echo ($page == 'index') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </div>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Menu</div>
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) == 'admin'): ?>
            <div class="nav-item <?php echo ($page == 'guru') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>guru"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-user"></i>
                    <span>Data Guru</span></a>
            </div>
            <?php endif; ?>
            <div class="nav-item <?php echo ($page == 'surat_masuk') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>surat_masuk"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-inbox"></i>
                    <span>Surat Masuk</span></a>
            </div>
            <div class="nav-item <?php echo ($page == 'surat_keluar') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>surat_keluar"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-paper-plane"></i>
                    <span>Surat Keluar</span></a>
            </div>
            <div class="nav-item <?php echo ($page == 'surat_keputusan') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>surat_keputusan"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-gavel"></i>
                    <span>Surat Keputusan</span></a>
            </div>
            <div class="nav-item <?php echo ($page == 'riwayat') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>riwayat"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span></a>
            </div>
            <?php if (strtolower(trim($_SESSION['role'] ?? '')) == 'admin'): ?>
            <div class="nav-item <?php echo ($page == 'pengguna') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>pengguna"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-users"></i>
                    <span>Pengguna</span></a>
            </div>
            <div class="nav-item <?php echo ($page == 'pengaturan') ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>pengaturan"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-cogs"></i>
                    <span>Pengaturan</span></a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
                <a class="nav-link" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>backup"<?php echo $sims_drawer_nav_onclick; ?>>
                    <i class="fas fa-database"></i>
                    <span>Backup Restore</span></a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="javascript:void(0);" onclick="window.__simsCloseDrawerNav&&window.__simsCloseDrawerNav();confirmLogout();">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span></a>
            </div>
            <!-- Sidebar Toggler (Sidebar) -->
            <!-- <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div> -->
        </div>
        <div id="sidebarBackdrop" aria-hidden="true"></div>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-dark bg-gradient-primary topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" type="button" class="btn btn-link d-md-none rounded-circle mr-3 text-white p-2" aria-label="Buka menu">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <a class="navbar-brand d-flex align-items-center" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="h6 mb-0 text-white d-none d-md-block">SISTEM MANAJEMEN SURAT | <?php echo strtoupper($nama_sekolah); ?></span>
                        <span class="h6 mb-0 text-white d-block d-md-none">SIMS</span>
                    </a>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item d-none d-sm-block">
                            <span class="nav-link text-white"><i class="far fa-clock mr-1"></i><span id="current-time"></span></span>
                        </li>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-white small"><?php echo $_SESSION['nama'] ?? 'Pengguna'; ?></span>
                                <?php 
                                $foto_profil = isset($_SESSION['foto']) ? $_SESSION['foto'] : 'default.jpg';
                                $foto_path = 'uploads/' . $foto_profil;
                                $nama_user = $_SESSION['nama'] ?? 'Pengguna';
                                
                                if ($foto_profil != 'default.jpg' && file_exists($foto_path)) {
                                    echo '<img class="img-profile rounded-circle" src="' . $foto_path . '" style="object-fit: cover; border: 2px solid white;">';
                                } else {
                                    $initials = function_exists('getInitials') ? getInitials($nama_user) : substr($nama_user, 0, 2);
                                    $bg_color = function_exists('getAvatarColor') ? getAvatarColor($nama_user) : '#4e73df';
                                    echo '<div class="img-profile rounded-circle d-inline-flex align-items-center justify-content-center text-white" style="background-color: ' . $bg_color . '; width: 2rem; height: 2rem; font-size: 0.8rem; font-weight: bold; border: 2px solid white;">' . $initials . '</div>';
                                }
                                ?>
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
                        var opt = { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric', 
                            hour: '2-digit', 
                            minute: '2-digit', 
                            second: '2-digit',
                            timeZone: 'Asia/Jakarta'
                        };
                        var el = document.getElementById('current-time');
                        if (el) el.innerText = now.toLocaleString('id-ID', opt);
                    }
                    setInterval(updateTime, 1000);
                    updateTime();
                </script>
