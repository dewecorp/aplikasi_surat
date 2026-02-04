    <!-- Jquery Core Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

    <!-- Bootstrap Core Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>

    <!-- Select Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js"></script>

    <!-- Slimscroll Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-slimScroll/1.3.8/jquery.slimscroll.min.js"></script>

    <!-- Waves Effect Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/node-waves/0.7.6/waves.min.js"></script>

    <!-- SweetAlert Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <!-- Ckeditor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.7.3/ckeditor.js"></script>

    <!-- Jquery CountTo Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-countto/1.2.0/jquery.countTo.min.js"></script>

    <!-- ChartJs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.bundle.min.js"></script>

    <!-- Jquery DataTable Plugin Js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/1.2.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/1.2.4/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.18/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.18/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/1.2.4/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/1.2.4/js/buttons.print.min.js"></script>

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
