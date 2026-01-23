<?php
$page = basename($_SERVER['PHP_SELF'], ".php");

// Fetch latest user info if logged in
$user_nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Pengunjung';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$user_foto = isset($_SESSION['foto']) ? $_SESSION['foto'] : 'default.jpg';

if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    $u_query = mysqli_query($conn, "SELECT nama, role, foto FROM users WHERE id='$uid'");
    if ($u_query && mysqli_num_rows($u_query) > 0) {
        $u_row = mysqli_fetch_assoc($u_query);
        $user_nama = $u_row['nama'];
        $user_role = $u_row['role'];
        $user_foto = $u_row['foto'];
        
        // Sync session (optional but good for consistency across requests)
        $_SESSION['nama'] = $user_nama;
        $_SESSION['role'] = $user_role;
        $_SESSION['foto'] = $user_foto;
    }
}
?>
<section>
    <!-- Left Sidebar -->
    <aside id="leftsidebar" class="sidebar">
        <!-- User Info -->
        <div class="user-info">
            <div class="image">
                <?php if ($user_foto != 'default.jpg' && file_exists('uploads/' . $user_foto)): ?>
                    <img src="uploads/<?php echo $user_foto; ?>" width="48" height="48" alt="User" style="border-radius: 50%; object-fit: cover;" />
                <?php else: ?>
                    <div style="width: 48px; height: 48px; background-color: <?php echo getAvatarColor($user_nama); ?>; color: white; border-radius: 50%; text-align: center; line-height: 48px; font-weight: bold; font-size: 20px; display: inline-block;">
                        <?php echo getInitials($user_nama); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="info-container">
                <div class="name" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $user_nama; ?></div>
                <div class="email"><?php echo $user_role; ?></div>
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
                &copy; <?php echo date('Y'); ?> 
                <a href="javascript:void(0);">
                    SIMS - 
                    <?php 
                    if (isset($nama_sekolah)) {
                        $display_nama = ucwords(strtolower($nama_sekolah));
                        $display_nama = str_replace(
                            ['Mi ', 'Mts ', 'Ma ', 'Sd ', 'Smp ', 'Sma ', 'Smk '], 
                            ['MI ', 'MTs ', 'MA ', 'SD ', 'SMP ', 'SMA ', 'SMK '], 
                            $display_nama
                        );
                        echo $display_nama;
                    } else {
                        echo 'MI Sultan Fattah';
                    }
                    ?>
                </a>.
            </div>
        </div>
        <!-- #Footer -->
    </aside>
    <!-- #END# Left Sidebar -->
</section>
