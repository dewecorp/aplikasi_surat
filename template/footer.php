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
    <script>
        (function ($) {
            function simsIsMobileDrawer() {
                try {
                    if (window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches) {
                        return true;
                    }
                } catch (e) { /* abaikan */ }
                var w = window.innerWidth || document.documentElement.clientWidth || 0;
                return w <= 768;
            }

            function simsSyncMobileSidebar() {
                var $bd = $('#sidebarBackdrop');
                var $sb = $('#accordionSidebar');
                if (!$bd.length || !$sb.length) {
                    return;
                }
                if (!simsIsMobileDrawer()) {
                    $bd.removeClass('is-visible').attr('aria-hidden', 'true');
                    $('body').removeClass('sidebar-mobile-open').removeAttr('data-sims-drawer-open');
                    simsClearMobileDrawerInlineStyles();
                    return;
                }
                if ($sb.hasClass('toggled')) {
                    $bd.removeClass('is-visible').attr('aria-hidden', 'true');
                    $('body').removeClass('sidebar-mobile-open').removeAttr('data-sims-drawer-open');
                } else {
                    simsClearMobileDrawerInlineStyles();
                    $bd.addClass('is-visible').attr('aria-hidden', 'false');
                    $('body').addClass('sidebar-mobile-open').attr('data-sims-drawer-open', '1');
                }
            }

            /** Didefinisikan di header.php sebagai window.__simsCloseDrawerNav (onclick tautan menu). */
            function simsCloseMobileMenu() {
                if (typeof window.__simsCloseDrawerNav === 'function') {
                    window.__simsCloseDrawerNav();
                }
                if ($.fn.collapse) {
                    try {
                        $('#accordionSidebar .collapse').collapse('hide');
                    } catch (err) {
                        /* abaikan */
                    }
                }
            }

            function simsClearMobileDrawerInlineStyles() {
                if (typeof window.__simsClearDrawerNavInline === 'function') {
                    window.__simsClearDrawerNavInline();
                    return;
                }
                var el = document.getElementById('accordionSidebar');
                if (!el) {
                    return;
                }
                el.style.removeProperty('transform');
                el.style.removeProperty('pointer-events');
            }

            /**
             * Hanya capture "click". Menutup drawer di "pointerdown" memindahkan DOM/layout sebelum
             * click disintesis (iOS/WebKit) sehingga navigasi <a href> sering hilang — hanya satu halaman kebetulan jalan.
             * Penanda data-sims-drawer-open tetap dipakai bersama lebar mobile.
             */
            function simsCaptureCloseDrawerFromSidebarNav(ev) {
                if (typeof ev.button === 'number' && ev.button !== 0) {
                    return;
                }
                var t = ev.target;
                if (!t || typeof t.closest !== 'function') {
                    return;
                }
                var a = t.closest('a[href]');
                if (!a) {
                    return;
                }
                var sb = document.getElementById('accordionSidebar');
                if (!sb || !sb.contains(a)) {
                    return;
                }
                if (a.getAttribute('target') === '_blank') {
                    return;
                }
                var href = (a.getAttribute('href') || '').trim();
                if (!href || href === '#') {
                    return;
                }
                if (a.getAttribute('data-toggle') === 'collapse' && href === '#') {
                    return;
                }
                if (sb.classList.contains('toggled')) {
                    return;
                }
                var drawerFlag = document.body.getAttribute('data-sims-drawer-open') === '1';
                if (!drawerFlag && !simsIsMobileDrawer()) {
                    return;
                }
                simsCloseMobileMenu();
            }
            document.addEventListener('click', simsCaptureCloseDrawerFromSidebarNav, true);

            $(function () {
                $('#sidebarToggleTop, #sidebarToggle').on('click', function () {
                    window.setTimeout(simsSyncMobileSidebar, 0);
                });
                $(window).on('resize orientationchange', simsSyncMobileSidebar);
                $(window).on('pageshow', function (ev) {
                    if (ev.originalEvent && ev.originalEvent.persisted) {
                        simsSyncMobileSidebar();
                    }
                });
                /** Hosting/HP: jangan hanya andalkan lebar; cukup backdrop terbuka / penanda drawer. */
                function simsBackdropCloseIfOpen() {
                    if ($('#accordionSidebar').hasClass('toggled')) {
                        return;
                    }
                    var open =
                        $('#sidebarBackdrop').hasClass('is-visible') ||
                        $('body').attr('data-sims-drawer-open') === '1';
                    if (!open) {
                        return;
                    }
                    simsCloseMobileMenu();
                }
                $('#sidebarBackdrop').on('click touchend pointerup', function (ev) {
                    if (ev.type === 'pointerup') {
                        var btn = typeof ev.button === 'number' ? ev.button : (ev.originalEvent && ev.originalEvent.button);
                        if (typeof btn === 'number' && btn !== 0) {
                            return;
                        }
                    }
                    simsBackdropCloseIfOpen();
                });

                simsSyncMobileSidebar();
            });
        })(jQuery);
    </script>
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
                var appBase = <?php echo json_encode($base_url); ?>;
                window.location.href = new URL("logout", appBase).toString();
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
                    type: "error",
                    timer: 3000,
                    showConfirmButton: true
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
                var appBase = <?php echo json_encode($base_url); ?>;
                window.location.href = new URL(url, appBase).toString();
            });
        }
    </script>
</body>
</html>
