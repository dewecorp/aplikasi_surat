    <!-- Jquery Core Js -->
    <script src="assets/plugins/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core Js -->
    <script src="assets/plugins/bootstrap/js/bootstrap.js"></script>

    <!-- Select Plugin Js -->
    <script src="assets/plugins/bootstrap-select/js/bootstrap-select.js"></script>

    <!-- Slimscroll Plugin Js -->
    <script src="assets/plugins/jquery-slimscroll/jquery.slimscroll.js"></script>

    <!-- Waves Effect Plugin Js -->
    <script src="assets/plugins/node-waves/waves.js"></script>

    <!-- SweetAlert Plugin Js -->
    <script src="assets/plugins/sweetalert/sweetalert.min.js"></script>

    <!-- Ckeditor -->
    <script src="assets/plugins/ckeditor/ckeditor.js"></script>

    <!-- Jquery CountTo Plugin Js -->
    <script src="assets/plugins/jquery-countto/jquery.countTo.js"></script>

    <!-- ChartJs -->
    <script src="assets/plugins/chartjs/Chart.bundle.js"></script>

    <!-- Jquery DataTable Plugin Js -->
    <script src="assets/plugins/jquery-datatable/jquery.dataTables.js"></script>
    <script src="assets/plugins/jquery-datatable/skin/bootstrap/js/dataTables.bootstrap.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/dataTables.buttons.min.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/buttons.flash.min.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/jszip.min.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/pdfmake.min.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/vfs_fonts.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/buttons.html5.min.js"></script>
    <script src="assets/plugins/jquery-datatable/extensions/export/buttons.print.min.js"></script>

    <!-- Custom Js -->
    <script src="assets/js/admin.js"></script>
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
    <script src="assets/js/pages/tables/jquery-datatable.js"></script>

    <!-- Demo Js -->
    <script src="assets/js/demo.js"></script>

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
