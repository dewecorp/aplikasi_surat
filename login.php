<?php
require_once 'session_init.php';
include 'config.php';

// Get Settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1"));
$bg_login = isset($settings['background_login']) && !empty($settings['background_login']) ? 'assets/images/' . $settings['background_login'] : '';
$logo = isset($settings['logo']) && !empty($settings['logo']) ? 'assets/images/' . $settings['logo'] : '';
$nama_app = isset($settings['nama_aplikasi']) && !empty($settings['nama_aplikasi']) ? $settings['nama_aplikasi'] : 'SIMS';
$nama_sekolah = isset($settings['nama_madrasah']) && !empty($settings['nama_madrasah']) ? $settings['nama_madrasah'] : 'MI Sultan Fattah Sukosono';

if (isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }
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
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistem Manajemen Surat">
    <meta name="author" content="">

    <title>Login | SIMS</title>

    <!-- Custom fonts for this template-->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-login-image {
            background: url('<?php echo $logo ? $logo : "assets/img/undraw_posting_photo.svg"; ?>');
            background-position: center;
            background-size: 50%; /* Adjusted for better proportion */
            background-repeat: no-repeat;
            background-color: #fff;
            padding: 2rem;
        }
        /* Custom transparent card logic if background image is set */
        <?php if($bg_login): ?>
        .card {
            background-color: rgba(255, 255, 255, 0.95);
        }
        <?php endif; ?>
    </style>

</head>

<body class="bg-gradient-primary" style="<?php echo $bg_login ? "background: url('$bg_login'); background-size: cover; background-position: center;" : ""; ?>">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">SISTEM MANAJEMEN SURAT</h1>
                                        <p class="mb-4 text-gray-800 font-weight-bold"><?php echo $nama_sekolah; ?></p>
                                    </div>
                                    <form class="user" id="sign_in" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                name="username" aria-describedby="emailHelp"
                                                placeholder="Username" required autofocus>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                name="password" placeholder="Password" required>
                                        </div>
                                        <button type="submit" name="login" class="btn btn-primary btn-user btn-block">
                                            MASUK
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center small text-gray-600">
                                        &copy; <?php echo date('Y'); ?> SIMS - <?php echo $nama_sekolah; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="assets/js/sb-admin-2.min.js"></script>
    
    <!-- SweetAlert -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <?php if (isset($error)): ?>
        <script>
            $(document).ready(function() {
                swal({
                    title: "Gagal Login!",
                    text: "<?php echo $error; ?>",
                    type: "error",
                    confirmButtonText: "OK"
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>
