<?php
session_start();
include 'config.php';

// Get Settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1"));
$bg_login = isset($settings['background_login']) && !empty($settings['background_login']) ? 'assets/images/' . $settings['background_login'] : '';
$logo = isset($settings['logo']) && !empty($settings['logo']) ? 'assets/images/' . $settings['logo'] : '';
$nama_app = isset($settings['nama_aplikasi']) && !empty($settings['nama_aplikasi']) ? $settings['nama_aplikasi'] : 'SIMS';
$nama_sekolah = isset($settings['nama_madrasah']) && !empty($settings['nama_madrasah']) ? $settings['nama_madrasah'] : 'MI Sultan Fattah Sukosono';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $user = mysqli_fetch_assoc($query);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['foto'] = $user['foto'];
            $_SESSION['success'] = "Selamat Datang, " . $user['nama'];
            
            // Log Activity
            log_activity($user['id'], 'login', 'Login ke sistem');
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <title>Login | SIMS</title>
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

    <!-- Custom Css -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .login-page {
            max-width: 100% !important; /* Fix width issue if any */
            overflow: hidden; /* Remove scroll */
            height: 100vh;
        }
        .login-box {
            width: 100%;
            max-width: 480px; /* Widened for long title */
            margin: 5% auto; /* Ensure centering */
            padding: 0 15px;
        }
        .logo img {
            display: block;
            margin: 0 auto 10px;
            max-width: 150px;
            height: auto;
        }
        .logo a {
            font-size: 26px !important; /* Adjusted font size */
            display: block; /* Ensure it wraps if needed */
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .logo small {
            display: block;
            font-size: 18px !important; /* Increased font size */
            font-weight: bold;
            color: #fff;
        }
    </style>
</head>

<body class="login-page" style="<?php echo $bg_login ? "background: url('$bg_login') no-repeat center center fixed !important; background-size: cover !important;" : "background: linear-gradient(45deg, #1e88e5, #4fc3f7) !important;"; ?>">
    <div class="login-box">
        <div class="logo">
            <?php if ($logo): ?>
                <img src="<?php echo $logo; ?>" alt="Logo">
            <?php endif; ?>
            <a href="javascript:void(0);"><?php echo $nama_app; ?></a>
            <small><?php echo $nama_sekolah; ?></small>
        </div>
        <div class="card">
            <div class="body">
                <form id="sign_in" method="POST">
                    <div class="msg">Masuk untuk memulai sesi Anda</div>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="material-icons">person</i>
                        </span>
                        <div class="form-line">
                            <input type="text" class="form-control" name="username" placeholder="Username" required autofocus>
                        </div>
                    </div>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="material-icons">lock</i>
                        </span>
                        <div class="form-line">
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6 col-xs-offset-3">
                            <button class="btn btn-block bg-blue waves-effect" type="submit" name="login">MASUK</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Jquery Core Js -->
    <script src="assets/plugins/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core Js -->
    <script src="assets/plugins/bootstrap/js/bootstrap.js"></script>

    <!-- Waves Effect Plugin Js -->
    <script src="assets/plugins/node-waves/waves.js"></script>

    <!-- Validation Plugin Js -->
    <script src="assets/plugins/jquery-validation/jquery.validate.js"></script>

    <!-- Custom Js -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/pages/examples/sign-in.js"></script>
</body>

</html>
