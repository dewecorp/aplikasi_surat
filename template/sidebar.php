<?php
$page = basename($_SERVER['PHP_SELF'], ".php");
?>
<section>
    <!-- Left Sidebar -->
    <aside id="leftsidebar" class="sidebar">
        <!-- User Info -->
        <div class="user-info">
            <div class="image">
                <img src="assets/images/user.png" width="48" height="48" alt="User" />
            </div>
            <div class="info-container">
                <div class="name" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $_SESSION['nama']; ?></div>
                <div class="email"><?php echo $_SESSION['role']; ?></div>
            </div>
        </div>
        <!-- #User Info -->
        <!-- Menu -->
        <div class="menu">
            <ul class="list">
                <li class="header">MAIN NAVIGATION</li>
                <li class="<?php echo ($page == 'index') ? 'active' : ''; ?>">
                    <a href="index.php">
                        <i class="material-icons">dashboard</i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'guru') ? 'active' : ''; ?>">
                    <a href="guru.php">
                        <i class="material-icons">person</i>
                        <span>Data Guru</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'surat_keluar') ? 'active' : ''; ?>">
                    <a href="surat_keluar.php">
                        <i class="material-icons">mail_outline</i>
                        <span>Surat Keluar</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'surat_masuk') ? 'active' : ''; ?>">
                    <a href="surat_masuk.php">
                        <i class="material-icons">mail</i>
                        <span>Surat Masuk</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'riwayat') ? 'active' : ''; ?>">
                    <a href="riwayat.php">
                        <i class="material-icons">history</i>
                        <span>Riwayat</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'pengguna') ? 'active' : ''; ?>">
                    <a href="pengguna.php">
                        <i class="material-icons">people</i>
                        <span>Pengguna</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'pengaturan') ? 'active' : ''; ?>">
                    <a href="pengaturan.php">
                        <i class="material-icons">settings</i>
                        <span>Pengaturan</span>
                    </a>
                </li>
                <li class="<?php echo ($page == 'backup') ? 'active' : ''; ?>">
                    <a href="backup.php">
                        <i class="material-icons">backup</i>
                        <span>Backup Restore</span>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="confirmLogout()">
                        <i class="material-icons">input</i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- #Menu -->
        <!-- Footer -->
        <div class="legal">
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <a href="javascript:void(0);">SIMS - MI Sultan Fattah</a>.
            </div>
        </div>
        <!-- #Footer -->
    </aside>
    <!-- #END# Left Sidebar -->
</section>
