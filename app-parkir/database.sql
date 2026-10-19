-- Database Schema & Data Dump for Aplikasi Manajemen Parkir
-- Compatible with MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS `db_parkir` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_parkir`;

-- 1. Tabel User
CREATE TABLE IF NOT EXISTS `tb_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','petugas','owner') NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `tb_user_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabel Tarif
CREATE TABLE IF NOT EXISTS `tb_tarif` (
  `id_tarif` int(11) NOT NULL AUTO_INCREMENT,
  `jenis_kendaraan` enum('motor','mobil','lainnya') NOT NULL,
  `tarif_per_jam` decimal(10,0) NOT NULL,
  PRIMARY KEY (`id_tarif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabel Area Parkir
CREATE TABLE IF NOT EXISTS `tb_area_parkir` (
  `id_area` int(11) NOT NULL AUTO_INCREMENT,
  `nama_area` varchar(50) NOT NULL,
  `kapasitas` int(5) NOT NULL,
  `terisi` int(5) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tabel Kendaraan
CREATE TABLE IF NOT EXISTS `tb_kendaraan` (
  `id_kendaraan` int(11) NOT NULL AUTO_INCREMENT,
  `plat_nomor` varchar(15) NOT NULL,
  `jenis_kendaraan` varchar(20) NOT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `pemilik` varchar(100) DEFAULT NULL,
  `id_user` int(11) NOT NULL,
  PRIMARY KEY (`id_kendaraan`),
  KEY `tb_kendaraan_id_user_foreign` (`id_user`),
  CONSTRAINT `tb_kendaraan_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Tabel Transaksi
CREATE TABLE IF NOT EXISTS `tb_transaksi` (
  `id_parkir` int(11) NOT NULL AUTO_INCREMENT,
  `id_kendaraan` int(11) NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `id_tarif` int(11) NOT NULL,
  `durasi_jam` int(5) DEFAULT NULL,
  `biaya_total` decimal(10,0) DEFAULT NULL,
  `status` enum('masuk','keluar') NOT NULL DEFAULT 'masuk',
  `id_user` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  PRIMARY KEY (`id_parkir`),
  KEY `tb_transaksi_id_kendaraan_foreign` (`id_kendaraan`),
  KEY `tb_transaksi_id_tarif_foreign` (`id_tarif`),
  KEY `tb_transaksi_id_area_foreign` (`id_area`),
  KEY `tb_transaksi_id_user_foreign` (`id_user`),
  CONSTRAINT `tb_transaksi_id_area_foreign` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`),
  CONSTRAINT `tb_transaksi_id_kendaraan_foreign` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`),
  CONSTRAINT `tb_transaksi_id_tarif_foreign` FOREIGN KEY (`id_tarif`) REFERENCES `tb_tarif` (`id_tarif`),
  CONSTRAINT `tb_transaksi_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Tabel Log Aktivitas
CREATE TABLE IF NOT EXISTS `tb_log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `aktivitas` varchar(100) NOT NULL,
  `waktu_aktivitas` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `tb_log_aktivitas_id_user_foreign` (`id_user`),
  CONSTRAINT `tb_log_aktivitas_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default Data (Seed)
-- Password uses sha256:
-- admin123  => 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
-- petugas123=> 2dad904f71aa0dcf6ea1addaa084a5865ffe448e4d3f900668e1cc7e7b6153d7
-- owner123  => 43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9

INSERT IGNORE INTO `tb_user` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `status_aktif`) VALUES
(1, 'Excell', 'admin1', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', 1),
(2, 'Siti Aminah', 'petugas1', '2dad904f71aa0dcf6ea1addaa084a5865ffe448e4d3f900668e1cc7e7b6153d7', 'petugas', 1),
(3, 'Pak Hendra', 'owner1', '43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9', 'owner', 1);

INSERT IGNORE INTO `tb_tarif` (`id_tarif`, `jenis_kendaraan`, `tarif_per_jam`) VALUES
(1, 'motor', 2000),
(2, 'mobil', 5000),
(3, 'lainnya', 3000);

INSERT IGNORE INTO `tb_area_parkir` (`id_area`, `nama_area`, `kapasitas`, `terisi`) VALUES
(1, 'Area A - Motor', 50, 0),
(2, 'Area B - Mobil', 30, 0);

