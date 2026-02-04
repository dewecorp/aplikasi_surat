            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; <?php echo date('Y'); ?> SIMS - <?php echo isset($nama_sekolah) ? strtoupper($nama_sekolah) : 'Sekolah'; ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>
    <script src="assets/vendor/chart.js/Chart.min.js"></script>
    <script src="assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <?php
    // Fetch settings for JS
    if (isset($conn)) {
        $q_js_set = mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1");
        $js_set = mysqli_fetch_assoc($q_js_set);
        $logo_base64 = '';
        // Check assets/images first (default/upload location)
        if (!empty($js_set['logo']) && file_exists('assets/images/' . $js_set['logo'])) {
            $logo_path = 'assets/images/' . $js_set['logo'];
            $type = pathinfo($logo_path, PATHINFO_EXTENSION);
            $data = file_get_contents($logo_path);
            $logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
    ?>
    <script>
        var simsConfig = {
            nama_sekolah: <?php echo isset($js_set['nama_madrasah']) ? json_encode($js_set['nama_madrasah']) : '""'; ?>,
            logo: <?php echo isset($logo_base64) ? json_encode($logo_base64) : '""'; ?>,
            alamat: <?php echo isset($js_set['alamat']) ? json_encode($js_set['alamat']) : '""'; ?>
        };
    </script>
    <script>
        $(function () {
            if ($.fn.DataTable) {
                $('.dataTable').DataTable();
            }
        });
    </script>

    <script>
        $(function () {
            if ($.fn.countTo) {
                $('.count-to').countTo();
            }
        });

        function confirmLogout() {
            swal({
                title: "Apakah anda yakin?",
                text: "Anda akan keluar dari aplikasi!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Ya, Logout!",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function () {
                window.location.href = "logout.php";
            });
        }

        $(function () {
            <?php if (isset($_SESSION['success'])): ?>
                swal({
                    title: "Berhasil!",
                    text: <?php echo json_encode($_SESSION['success']); ?>,
                    type: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                swal({
                    title: "Gagal!",
                    text: <?php echo json_encode($_SESSION['error']); ?>,
                    type: "error"
                });
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
        
        function confirmDelete(url) {
             swal({
                title: "Apakah anda yakin?",
                text: "Data yang dihapus tidak dapat dikembalikan!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function () {
                window.location.href = url;
            });
        }
    </script>
</body>
</html>
