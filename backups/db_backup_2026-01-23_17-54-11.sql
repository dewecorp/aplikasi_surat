SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS backup;

CREATE TABLE `backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




DROP TABLE IF EXISTS guru;

CREATE TABLE `guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nuptk` varchar(20) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `jk` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `status` enum('Guru Kelas','Guru Mapel') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




DROP TABLE IF EXISTS pengaturan;

CREATE TABLE `pengaturan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_yayasan` varchar(100) DEFAULT NULL,
  `nama_madrasah` varchar(100) DEFAULT NULL,
  `alamat` text,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `kepala_madrasah` varchar(100) DEFAULT NULL,
  `ttd` varchar(255) DEFAULT NULL,
  `stempel` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO pengaturan VALUES("1","Yayasan","Madrasah","","","","","","");



DROP TABLE IF EXISTS surat_keluar;

CREATE TABLE `surat_keluar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tgl_surat` date NOT NULL,
  `no_surat` varchar(100) NOT NULL,
  `jenis_surat` varchar(50) NOT NULL,
  `perihal` text NOT NULL,
  `penerima` varchar(100) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




DROP TABLE IF EXISTS surat_masuk;

CREATE TABLE `surat_masuk` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tgl_terima` date NOT NULL,
  `no_surat` varchar(100) NOT NULL,
  `tgl_surat` date NOT NULL,
  `perihal` text NOT NULL,
  `pengirim` varchar(100) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tu') NOT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO users VALUES("1","Administrator","admin","$2y$10$qWA7mN7CKrMRFl16gQ4kbu6DQkpnxMzelxrJaEnzkFRi1ZrRdyVEi","admin","default.jpg");



SET FOREIGN_KEY_CHECKS=1;