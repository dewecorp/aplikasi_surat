SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tu') NOT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `foto`) VALUES
(1, 'Administrator', 'admin', '$2y$10$0M.l.d.d.d.d.d.d.d.d.e.d.d.d.d.d.d.d.d.d.d.d.d.d.d.d', 'admin', 'default.jpg');
-- Note: Password hash needs to be generated properly. I will update this via PHP later or use a simple one.
-- Let's use a known hash for 'admin': $2y$10$7/O5sM.v.v.v.v.v.v.v.e.v.v.v.v.v.v.v.v.v.v.v.v.v.v.v (example)
-- Actually, I will make a 'seed.php' to handle this correctly.

CREATE TABLE `guru` (
  `id` int(11) NOT NULL,
  `nuptk` varchar(20) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `jk` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `status` enum('Guru Kelas','Guru Mapel') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `surat_keluar` (
  `id` int(11) NOT NULL,
  `tgl_surat` date NOT NULL,
  `no_surat` varchar(100) NOT NULL,
  `jenis_surat` varchar(50) NOT NULL,
  `perihal` text NOT NULL,
  `penerima` varchar(100) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `surat_masuk` (
  `id` int(11) NOT NULL,
  `tgl_terima` date NOT NULL,
  `no_surat` varchar(100) NOT NULL,
  `tgl_surat` date NOT NULL,
  `perihal` text NOT NULL,
  `pengirim` varchar(100) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_yayasan` varchar(100) DEFAULT NULL,
  `nama_madrasah` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `kepala_madrasah` varchar(100) DEFAULT NULL,
  `ttd` varchar(255) DEFAULT NULL,
  `stempel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `backup` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`);
ALTER TABLE `guru` ADD PRIMARY KEY (`id`);
ALTER TABLE `surat_keluar` ADD PRIMARY KEY (`id`);
ALTER TABLE `surat_masuk` ADD PRIMARY KEY (`id`);
ALTER TABLE `pengaturan` ADD PRIMARY KEY (`id`);
ALTER TABLE `backup` ADD PRIMARY KEY (`id`);

ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `guru` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `surat_keluar` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `surat_masuk` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pengaturan` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `backup` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;
