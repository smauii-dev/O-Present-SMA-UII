-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 13 Jul 2026 pada 08.32
-- Versi server: 10.11.18-MariaDB-cll-lve
-- Versi PHP: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sma35ui1_presensi_sma_uii`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_activation_attempts`
--

CREATE TABLE `auth_activation_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_groups`
--

CREATE TABLE `auth_groups` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_groups`
--

INSERT INTO `auth_groups` (`id`, `name`, `description`) VALUES
(1, 'head', 'Unit manajerial tingkat tinggi yang bertanggung jawab memimpin, mengelola, dan mengkoordinasikan berbagai tim di organisasi untuk mencapai tujuan strategis.'),
(2, 'admin', 'Mengelola dan mengawasi fungsi administratif sistem, termasuk manajemen pengguna dan hak akses, untuk mendukung operasional harian.'),
(3, 'pegawai', 'Mencakup anggota yang fokus pada kehadiran dan aktivitas presensi, dengan akses terbatas untuk memastikan pencatatan dan pemantauan presensi yang akurat.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_groups_permissions`
--

CREATE TABLE `auth_groups_permissions` (
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `permission_id` int(11) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_groups_permissions`
--

INSERT INTO `auth_groups_permissions` (`group_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(2, 2),
(3, 1),
(3, 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_groups_users`
--

CREATE TABLE `auth_groups_users` (
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_groups_users`
--

INSERT INTO `auth_groups_users` (`group_id`, `user_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(3, 9),
(3, 10),
(3, 11),
(3, 92),
(3, 93),
(3, 94),
(3, 95),
(3, 96),
(3, 97),
(3, 98),
(3, 99),
(3, 100),
(3, 101),
(3, 102),
(3, 103),
(3, 104),
(3, 105),
(3, 106),
(3, 107),
(3, 108),
(3, 109),
(3, 110),
(3, 111),
(3, 112),
(3, 113),
(3, 114),
(3, 115),
(3, 116),
(3, 117),
(3, 118),
(3, 119),
(3, 120),
(3, 121),
(3, 122),
(3, 123),
(3, 124),
(3, 125),
(3, 126),
(3, 127),
(3, 128),
(3, 137),
(3, 138),
(3, 139),
(3, 140);

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_logins`
--

CREATE TABLE `auth_logins` (
  `id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_logins`
--

INSERT INTO `auth_logins` (`id`, `ip_address`, `email`, `user_id`, `date`, `success`) VALUES
(10, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2025-09-04 08:50:45', 1),
(11, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2025-09-04 08:51:53', 1),
(12, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2025-09-04 08:53:03', 1),
(13, '114.10.153.12', 'ahmadhanif@gmail.com', 3, '2025-09-04 08:55:21', 1),
(14, '114.10.153.12', 'ahmadhanif@gmail.com', 3, '2025-09-04 09:01:58', 1),
(15, '114.10.152.40', 'ahmadhanif@gmail.com', 3, '2025-09-04 09:12:22', 1),
(16, '139.192.67.207', 'choland', NULL, '2025-09-04 21:50:14', 0),
(17, '139.192.67.207', 'ahmadhanif@gmail.com', 3, '2025-09-04 21:50:23', 1),
(18, '182.253.126.233', 'ahmadhanif@gmail.com', 3, '2025-09-06 09:24:14', 1),
(19, '104.28.156.114', 'hanif', NULL, '2025-11-21 12:13:41', 0),
(20, '104.28.156.114', 'hanif', NULL, '2025-11-21 12:13:46', 0),
(21, '103.3.220.105', 'jaya@present.com', 1, '2026-06-05 22:14:16', 1),
(22, '103.3.220.105', 'ahmadhanif', NULL, '2026-06-05 22:14:36', 0),
(23, '103.3.220.105', 'jaya@present.com', 1, '2026-06-05 22:14:52', 1),
(24, '103.3.220.105', 'ahmadhanif', NULL, '2026-06-05 22:17:00', 0),
(25, '103.3.220.105', 'ahmadhanif@gmail.com', 3, '2026-06-05 22:17:11', 1),
(26, '103.3.220.105', 'jaya@present.com', 1, '2026-06-05 22:17:40', 1),
(27, '103.3.220.105', 'ahmadhanif@gmail.com', 3, '2026-06-05 22:24:07', 1),
(28, '103.3.220.105', 'jaya@present.com', 1, '2026-06-05 23:08:04', 1),
(29, '216.9.225.88', 'wsyrsjrk@immenseignite.info', NULL, '2026-06-06 19:57:51', 0),
(30, '202.162.40.161', 'chaamid@smauiiyk.sch.id', 6, '2026-06-08 09:51:16', 1),
(31, '202.162.40.161', 'hanifhasan', NULL, '2026-06-08 09:51:38', 0),
(32, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2026-06-08 09:51:46', 1),
(33, '202.162.40.161', 'chaamid@smauiiyk.sch.id', 6, '2026-06-08 09:52:04', 1),
(34, '202.162.40.161', 'chaamid@smauiiyk.sch.id', 6, '2026-06-08 09:53:18', 1),
(35, '202.162.40.161', 'chaamid@gmail.com', 6, '2026-06-08 09:54:25', 1),
(36, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2026-06-08 10:21:16', 1),
(37, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2026-06-08 10:22:19', 1),
(38, '202.162.40.161', 'siswa1@gmail.com', 9, '2026-06-08 11:46:31', 1),
(39, '202.162.40.161', 'jaya@present.com', 1, '2026-06-08 14:51:34', 1),
(40, '182.4.103.169', 'siswa1@gmail.com', 9, '2026-06-08 14:57:01', 1),
(41, '202.162.40.161', 'ahmadhanif@gmail.com', 3, '2026-07-08 10:15:32', 1),
(42, '202.162.40.161', 'choland', NULL, '2026-07-09 08:03:21', 0),
(43, '202.162.40.161', 'tamani@present.com', 2, '2026-07-09 08:03:33', 1),
(44, '202.162.40.161', 'siswa1@gmail.com', 9, '2026-07-09 08:05:33', 1),
(45, '202.162.40.161', 'jaya@present.com', 1, '2026-07-09 08:06:27', 1),
(46, '202.162.40.161', 'siswa1@gmail.com', 9, '2026-07-09 09:03:38', 1),
(47, '202.162.40.161', 'jaya@present.com', 1, '2026-07-09 09:03:56', 1),
(48, '202.162.40.161', 'siswa2@gmail.com', 10, '2026-07-09 09:30:55', 1),
(49, '202.162.40.161', 'tamani@present.com', 2, '2026-07-09 09:36:19', 1),
(50, '202.162.40.161', 'tamani@present.com', 2, '2026-07-09 10:33:59', 1),
(51, '202.162.40.161', 'siswa1@gmail.com', 9, '2026-07-09 12:18:49', 1),
(52, '202.162.40.161', 'jaya@present.com', 1, '2026-07-09 13:55:05', 1),
(53, '202.162.40.161', 'siswa4', NULL, '2026-07-09 14:00:26', 0),
(54, '202.162.40.161', 'siswa4', NULL, '2026-07-09 14:00:35', 0),
(55, '202.162.40.161', 'siswa4@gmail.com', 92, '2026-07-09 14:00:47', 1),
(56, '157.85.212.128', 'jaya@present.com', 1, '2026-07-09 14:48:57', 1),
(57, '157.85.212.128', 'siswa4@gmail.com', 92, '2026-07-09 14:50:58', 1),
(58, '114.10.152.83', 'siswa4@gmail.com', 92, '2026-07-09 17:49:53', 1),
(59, '202.162.40.161', 'tamani@present.com', 2, '2026-07-10 09:08:23', 1),
(60, '202.162.40.161', 'jaya@present.com', 1, '2026-07-10 11:13:46', 1),
(61, '202.162.40.161', 'tamani@present.com', 2, '2026-07-10 11:16:12', 1),
(62, '202.162.40.161', 'jaya@present.com', 1, '2026-07-10 11:16:42', 1),
(63, '202.162.40.161', 'siswa1', NULL, '2026-07-10 11:27:29', 0),
(64, '202.162.40.161', 'siswa1', NULL, '2026-07-10 11:27:46', 0),
(65, '202.162.40.161', 'siswa1', NULL, '2026-07-10 11:28:18', 0),
(66, '202.162.40.161', 'siswa4@gmail.com', 92, '2026-07-10 11:28:46', 1),
(67, '202.162.40.161', 'jaya@present.com', 1, '2026-07-10 12:58:31', 1),
(68, '182.4.103.121', '2481', NULL, '2026-07-10 13:13:28', 0),
(69, '182.4.103.121', '402424', NULL, '2026-07-10 13:13:34', 0),
(70, '114.10.151.241', '2361', NULL, '2026-07-10 13:14:46', 0),
(71, '182.4.102.86', '2460', NULL, '2026-07-10 13:14:58', 0),
(72, '202.162.40.161', 'siswa', NULL, '2026-07-10 13:16:14', 0),
(73, '114.10.151.241', 'siswa', NULL, '2026-07-10 13:16:16', 0),
(74, '202.162.40.161', 'siswa', NULL, '2026-07-10 13:16:26', 0),
(75, '140.213.170.101', 'siswa9', NULL, '2026-07-10 13:16:42', 0),
(76, '202.162.40.161', 'siswa34@gmail.com', 122, '2026-07-10 13:16:49', 1),
(77, '202.162.40.161', 'siswa5@gmail.com', 93, '2026-07-10 13:16:50', 1),
(78, '202.162.40.161', 'siswa6@gmail.com', 94, '2026-07-10 13:16:50', 1),
(79, '182.4.103.65', 'siswa25@gmail.com', 113, '2026-07-10 13:16:50', 1),
(80, '202.162.40.161', 'siswa10@gmail.com', 98, '2026-07-10 13:16:51', 1),
(81, '202.162.40.161', 'siswa18@gmail.com', 106, '2026-07-10 13:16:51', 1),
(82, '114.10.151.241', 'siswa22@gmail.com', 110, '2026-07-10 13:16:51', 1),
(83, '114.10.152.155', 'siswa28@gmail.com', 116, '2026-07-10 13:16:51', 1),
(84, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:16:52', 1),
(85, '202.162.40.161', 'siswa27@gmail.com', 115, '2026-07-10 13:16:52', 1),
(86, '202.162.40.161', 'siswa4@gmail.com', 92, '2026-07-10 13:16:52', 1),
(87, '202.162.40.161', 'siswa33@gmail.com', 121, '2026-07-10 13:16:52', 1),
(88, '140.213.52.77', 'siswa8@gmail.com', 96, '2026-07-10 13:16:53', 1),
(89, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:16:53', 1),
(90, '202.162.40.161', 'siswa11@gmail.com', 99, '2026-07-10 13:16:53', 1),
(91, '182.4.102.12', 'siswa36@gmail.com', 124, '2026-07-10 13:16:54', 1),
(92, '202.162.40.161', 'siswa24@gmail.com', 112, '2026-07-10 13:16:55', 1),
(93, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:16:56', 1),
(94, '202.162.40.161', 'siswa18@gmail.com', 106, '2026-07-10 13:16:57', 1),
(95, '202.162.40.161', 'siswa23@gmail.com', 111, '2026-07-10 13:16:58', 1),
(96, '202.162.40.161', 'siswa7@gmail.com', 95, '2026-07-10 13:16:58', 1),
(97, '114.10.151.241', 'siswa22@gmail.com', 110, '2026-07-10 13:16:58', 1),
(98, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:16:58', 1),
(99, '140.213.176.137', 'siswa21@gmail.com', 109, '2026-07-10 13:17:00', 1),
(100, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:17:00', 1),
(101, '202.162.40.161', 'siswa11@gmail.com', 99, '2026-07-10 13:17:01', 1),
(102, '140.213.176.137', 'siswa21@gmail.com', 109, '2026-07-10 13:17:02', 1),
(103, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:17:03', 1),
(104, '114.10.150.55', 'siswa26', NULL, '2026-07-10 13:17:04', 0),
(105, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:17:04', 1),
(106, '202.162.40.161', '31', NULL, '2026-07-10 13:17:04', 0),
(107, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:17:05', 1),
(108, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:17:06', 1),
(109, '115.178.238.163', 'siswa35@gmail.com', 123, '2026-07-10 13:17:06', 1),
(110, '202.162.40.161', 'siswa18@gmail.com', 106, '2026-07-10 13:17:07', 1),
(111, '202.162.40.161', '31', NULL, '2026-07-10 13:17:07', 0),
(112, '202.162.40.161', 'siswa20@gmail.com', 108, '2026-07-10 13:17:21', 1),
(113, '202.162.40.161', 'siswa1@gmail.com', 9, '2026-07-10 13:17:36', 1),
(114, '182.4.100.239', '30', NULL, '2026-07-10 13:17:39', 0),
(115, '202.162.40.161', 'siswa13@gmail.com', 101, '2026-07-10 13:17:41', 1),
(116, '140.213.176.137', 'siswa21@gmail.com', 109, '2026-07-10 13:17:43', 1),
(117, '202.162.40.161', 'siswa29@gmail.com', 117, '2026-07-10 13:17:45', 1),
(118, '202.162.40.161', 'siswa29@gmail.com', 117, '2026-07-10 13:18:00', 1),
(119, '202.162.40.161', 'siswa12@gmail.com', 100, '2026-07-10 13:18:08', 1),
(120, '114.10.150.55', 'siswa26@gmail.com', 114, '2026-07-10 13:18:08', 1),
(121, '202.162.40.161', 'siswa32@gmail.com', 120, '2026-07-10 13:18:12', 1),
(122, '202.162.40.161', 'siswa16', NULL, '2026-07-10 13:18:12', 0),
(123, '114.10.150.55', 'siswa26@gmail.com', 114, '2026-07-10 13:18:13', 1),
(124, '202.162.40.161', 'siswa22@gmail.com', 110, '2026-07-10 13:18:14', 1),
(125, '202.162.40.161', 'siswa22@gmail.com', 110, '2026-07-10 13:18:18', 1),
(126, '202.162.40.161', 'siswa22@gmail.com', 110, '2026-07-10 13:18:21', 1),
(127, '202.162.40.161', 'siswa22@gmail.com', 110, '2026-07-10 13:18:25', 1),
(128, '202.162.40.161', 'siswa16', NULL, '2026-07-10 13:18:43', 0),
(129, '182.4.100.239', 'siswa31', NULL, '2026-07-10 13:19:20', 0),
(130, '114.10.150.55', 'siswa26@gmail.com', 114, '2026-07-10 13:19:26', 1),
(131, '114.10.151.171', 'siswa19@gmail.com', 107, '2026-07-10 13:19:43', 1),
(132, '202.162.40.161', 'siswa15@gmail.com', 103, '2026-07-10 13:19:44', 1),
(133, '202.162.40.161', 'siswa16@gmail.com', 104, '2026-07-10 13:19:50', 1),
(134, '202.162.40.161', 'siswa31@gmail.com', 119, '2026-07-10 13:19:55', 1),
(135, '182.4.100.239', 'siswa30@gmail.com', 118, '2026-07-10 13:19:59', 1),
(136, '202.162.40.161', 'siswa16@gmail.com', 104, '2026-07-10 13:20:01', 1),
(137, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:20:20', 1),
(138, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:20:26', 1),
(139, '202.162.40.161', 'siswa14@gmail.com', 102, '2026-07-10 13:20:36', 1),
(140, '157.85.212.128', 'siswa4@gmail.com', 92, '2026-07-10 20:29:13', 1),
(141, '157.85.212.128', 'jaya@present.com', 1, '2026-07-10 20:30:19', 1),
(142, '157.85.212.128', 'ahmadhanif@gmail.com', 3, '2026-07-10 20:33:46', 1),
(143, '157.85.212.128', 'ahmadhanif@gmail.com', 3, '2026-07-10 20:35:15', 1),
(144, '157.85.212.128', 'jaya@present.com', 1, '2026-07-10 20:36:29', 1),
(145, '157.85.212.128', 'jaya@present.com', 1, '2026-07-10 21:43:21', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_permissions`
--

CREATE TABLE `auth_permissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_permissions`
--

INSERT INTO `auth_permissions` (`id`, `name`, `description`) VALUES
(1, 'kelola_data', 'Mengelola Data Presensi, Data Lokasi Presensi, Data Jabatan, dan Data Pegawai'),
(2, 'isi_presensi', 'Izin untuk pengguna dapat melakukan dan melihat riwayat kehadiran, serta mengajukan ketidakhadiran.'),
(3, 'kelola_pengajuan_cuti', 'Izin yang memberikan akses untuk menyetujui atau memberikan persetujuan terhadap permintaan cuti yang diajukan oleh karyawan. Izin ini memungkinkan pengguna untuk mengelola dan memproses permintaan cuti melalui sistem.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_reset_attempts`
--

CREATE TABLE `auth_reset_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `auth_reset_attempts`
--

INSERT INTO `auth_reset_attempts` (`id`, `email`, `ip_address`, `user_agent`, `token`, `created_at`) VALUES
(1, 'hanif.hasan9@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'f15a4ade21b80310d2b608c6cfd3f7ae', '2025-09-04 07:30:46'),
(2, 'ahmadhanif@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'f15a4ade21b80310d2b608c6cfd3f7ae', '2025-09-04 07:31:09'),
(3, 'ahmadhanif@smauiiyk.sch.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'f15a4ade21b80310d2b608c6cfd3f7ae', '2025-09-04 07:31:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `selector` varchar(255) NOT NULL,
  `hashedValidator` varchar(255) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `auth_users_permissions`
--

CREATE TABLE `auth_users_permissions` (
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `permission_id` int(11) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `email_tokens`
--

CREATE TABLE `email_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_time` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) UNSIGNED NOT NULL,
  `jabatan` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `jabatan`
--

INSERT INTO `jabatan` (`id`, `jabatan`, `slug`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Chief Executive Officer', 'chief-executive-officer', NULL, NULL, NULL),
(2, 'Sales Lead', 'sales-lead', NULL, NULL, NULL),
(3, 'Siswa', 'siswa', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `ketidakhadiran`
--

CREATE TABLE `ketidakhadiran` (
  `id` int(11) UNSIGNED NOT NULL,
  `id_pegawai` int(11) UNSIGNED NOT NULL,
  `tipe_ketidakhadiran` varchar(255) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_berakhir` date NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `status_pengajuan` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `lokasi_presensi`
--

CREATE TABLE `lokasi_presensi` (
  `id` int(11) UNSIGNED NOT NULL,
  `nama_lokasi` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `alamat_lokasi` varchar(255) NOT NULL,
  `tipe_lokasi` varchar(255) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `radius` int(11) NOT NULL,
  `zona_waktu` varchar(100) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `lokasi_presensi`
--

INSERT INTO `lokasi_presensi` (`id`, `nama_lokasi`, `slug`, `alamat_lokasi`, `tipe_lokasi`, `latitude`, `longitude`, `radius`, `zona_waktu`, `jam_masuk`, `jam_pulang`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SMA UII Yogyakarta', 'sma-uii-yogyakarta', 'Jl. Taman Siswa No.158, Wirogunan, Kec. Mergangsan, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55151', 'Pusat', '-7.814284', '110.376044', 80, 'Asia/Jakarta', '13:20:00', '13:40:00', NULL, '2026-07-10 13:14:50', NULL),
(2, 'Rumah', 'rumah', 'Jangkang Sempu', 'Cabang', '-7.7364086163347405', '110.44314113100262', 50, 'Asia/Jakarta', '20:40:00', '21:00:00', '2026-07-10 20:32:29', '2026-07-10 20:32:29', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2024-02-02-091537', 'App\\Database\\Migrations\\CreateOPresentTables', 'default', 'App', 1756911482, 1),
(2, '2024-02-02-142048', 'App\\Database\\Migrations\\CreateAuthTables', 'default', 'App', 1756911482, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) UNSIGNED NOT NULL,
  `nip` varchar(50) NOT NULL,
  `id_jabatan` int(11) UNSIGNED NOT NULL,
  `id_lokasi_presensi` int(11) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `jenis_kelamin` varchar(10) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `no_handphone` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id`, `nip`, `id_jabatan`, `id_lokasi_presensi`, `nama`, `jenis_kelamin`, `alamat`, `no_handphone`, `foto`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PEG-0001', 1, 1, 'Jaya Wahyudi Putra', 'Laki-laki', 'Jl. Harsono RM No.1, Ragunan, Ps. Minggu, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12550', '081234567891', 'default.jpg', NULL, NULL, NULL),
(2, 'PEG-0002', 2, 1, 'Tamani Indah Permata', 'Perempuan', 'Jl. Lodan Timur No.7, Ancol, Kec. Pademangan, Jkt Utara, Daerah Khusus Ibukota Jakarta 14430', '081281010191', 'default.jpg', NULL, NULL, NULL),
(3, 'PEG-0003', 3, 2, 'Ahmad Hanif', 'Laki-laki', 'Jl. Taman Suropati No.5, RT.5/RW.5, Menteng, Kec. Menteng, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10310', '081287761290', 'default.jpg', NULL, '2026-07-10 20:32:54', NULL),
(6, 'PEG-0004', 3, 1, 'Siswa 1', 'Laki-laki', 'Jl. Taman Suropati No.5, RT.5/RW.5, Menteng, Kec. Menteng, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10310', '081287761290', 'default.jpg', NULL, '2025-09-04 08:06:16', NULL),
(7, 'PEG-0005', 3, 1, 'Siswa 2', 'Laki-laki', 'X-1', '081287761290', 'default.jpg', NULL, '2025-09-04 08:06:16', NULL),
(8, 'PEG-0006', 3, 1, 'Siswa 3', 'Laki-laki', 'X-2', '081287761290', 'default.jpg', NULL, '2025-09-04 08:06:16', NULL),
(89, 'PEG-0007', 3, 1, 'siswa4', 'Perempuan', 'X-4', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(90, 'PEG-0008', 3, 1, 'siswa5', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(91, 'PEG-0009', 3, 1, 'siswa6', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(92, 'PEG-0010', 3, 1, 'siswa7', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(93, 'PEG-0011', 3, 1, 'siswa8', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(94, 'PEG-0012', 3, 1, 'siswa9', 'Laki-laki', 'X-1', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(95, 'PEG-0013', 3, 1, 'siswa10', 'Perempuan', 'X-2', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(96, 'PEG-0014', 3, 1, 'siswa11', 'Laki-laki', 'X-3', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(97, 'PEG-0015', 3, 1, 'siswa12', 'Perempuan', 'X-4', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(98, 'PEG-0016', 3, 1, 'siswa13', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(99, 'PEG-0017', 3, 1, 'siswa14', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(100, 'PEG-0018', 3, 1, 'siswa15', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(101, 'PEG-0019', 3, 1, 'siswa16', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(102, 'PEG-0020', 3, 1, 'siswa17', 'Laki-laki', 'X-1', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(103, 'PEG-0021', 3, 1, 'siswa18', 'Perempuan', 'X-2', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(104, 'PEG-0022', 3, 1, 'siswa19', 'Laki-laki', 'X-3', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(105, 'PEG-0023', 3, 1, 'siswa20', 'Perempuan', 'X-4', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(106, 'PEG-0024', 3, 1, 'siswa21', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(107, 'PEG-0025', 3, 1, 'siswa22', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(108, 'PEG-0026', 3, 1, 'siswa23', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(109, 'PEG-0027', 3, 1, 'siswa24', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(110, 'PEG-0028', 3, 1, 'siswa25', 'Laki-laki', 'X-1', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(111, 'PEG-0029', 3, 1, 'siswa26', 'Perempuan', 'X-2', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(112, 'PEG-0030', 3, 1, 'siswa27', 'Laki-laki', 'X-3', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(113, 'PEG-0031', 3, 1, 'siswa28', 'Perempuan', 'X-4', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(114, 'PEG-0032', 3, 1, 'siswa29', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(115, 'PEG-0033', 3, 1, 'siswa30', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(116, 'PEG-0034', 3, 1, 'siswa31', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(117, 'PEG-0035', 3, 1, 'siswa32', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(118, 'PEG-0036', 3, 1, 'siswa33', 'Laki-laki', 'X-1', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(119, 'PEG-0037', 3, 1, 'siswa34', 'Perempuan', 'X-2', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(120, 'PEG-0038', 3, 1, 'siswa35', 'Laki-laki', 'X-3', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(121, 'PEG-0039', 3, 1, 'siswa36', 'Perempuan', 'X-4', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(122, 'PEG-0040', 3, 1, 'siswa37', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(123, 'PEG-0041', 3, 1, 'siswa38', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(124, 'PEG-0042', 3, 1, 'siswa39', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(125, 'PEG-0043', 3, 1, 'siswa40', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(126, 'PEG-0044', 3, 1, 'siswa41', 'Laki-laki', 'X-5', '-', 'default.jpg', '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(127, 'PEG-0045', 3, 1, 'siswa42', 'Perempuan', 'X-ICT-1', '-', 'default.jpg', '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(128, 'PEG-0046', 3, 1, 'siswa43', 'Laki-laki', 'X-ICT-2', '-', 'default.jpg', '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(129, 'PEG-0047', 3, 1, 'siswa44', 'Perempuan', 'X-ICT-3', '-', 'default.jpg', '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi`
--

CREATE TABLE `presensi` (
  `id` int(11) UNSIGNED NOT NULL,
  `id_pegawai` int(11) UNSIGNED NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `foto_masuk` varchar(255) NOT NULL,
  `tanggal_keluar` date NOT NULL,
  `jam_keluar` time NOT NULL,
  `foto_keluar` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `presensi`
--

INSERT INTO `presensi` (`id`, `id_pegawai`, `tanggal_masuk`, `jam_masuk`, `foto_masuk`, `tanggal_keluar`, `jam_keluar`, `foto_keluar`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, '2025-09-03', '22:13:07', 'masuk-2025-09-03-22-13-33-choland.png', '2025-09-03', '22:13:33', 'keluar-2025-09-03-22-13-47-choland.png', '2025-09-03 22:13:33', '2025-09-03 22:13:47', NULL),
(3, 3, '2025-09-04', '06:42:17', 'masuk-2025-09-04-06-42-26-choland.png', '2025-09-04', '08:55:47', 'keluar-2025-09-04-08-56-24-hanifhasan.png', '2025-09-04 06:42:26', '2025-09-04 08:56:24', NULL),
(4, 3, '2026-06-08', '10:24:02', 'masuk-2026-06-08-10-24-18-hanifhasan.png', '2026-06-08', '10:24:19', 'keluar-2026-06-08-10-24-26-hanifhasan.png', '2026-06-08 10:24:18', '2026-06-08 10:24:26', NULL),
(5, 6, '2026-06-08', '14:57:28', 'masuk-2026-06-08-14-57-57-siswa1.png', '2026-06-08', '14:57:57', 'keluar-2026-06-08-14-58-13-siswa1.png', '2026-06-08 14:57:57', '2026-06-08 14:58:13', NULL),
(6, 3, '2026-07-08', '10:15:32', 'masuk-2026-07-08-10-15-42-hanifhasan.png', '0000-00-00', '00:00:00', '', '2026-07-08 10:15:42', '2026-07-08 10:15:42', NULL),
(7, 2, '2026-07-09', '08:03:33', 'masuk-2026-07-09-08-03-45-tamanindah.png', '0000-00-00', '00:00:00', '', '2026-07-09 08:03:45', '2026-07-09 08:03:45', NULL),
(8, 6, '2026-07-09', '08:05:33', 'masuk-2026-07-09-08-05-39-siswa1.png', '0000-00-00', '00:00:00', '', '2026-07-09 08:05:39', '2026-07-09 08:05:39', NULL),
(9, 7, '2026-07-09', '09:30:55', 'masuk-2026-07-09-09-31-07-siswa2.png', '0000-00-00', '00:00:00', '', '2026-07-09 09:31:07', '2026-07-09 09:31:07', NULL),
(10, 89, '2026-07-09', '14:00:47', 'masuk-2026-07-09-14-01-30-siswa4.png', '0000-00-00', '00:00:00', '', '2026-07-09 14:01:30', '2026-07-09 14:01:30', NULL),
(11, 113, '2026-07-10', '13:18:47', 'masuk-2026-07-10-13-19-38-siswa28.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:38', '2026-07-10 13:19:38', NULL),
(12, 113, '2026-07-10', '13:18:47', 'masuk-2026-07-10-13-19-42-siswa28.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:42', '2026-07-10 13:19:42', NULL),
(13, 113, '2026-07-10', '13:18:47', 'masuk-2026-07-10-13-19-43-siswa28.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:43', '2026-07-10 13:19:43', NULL),
(14, 113, '2026-07-10', '13:18:47', 'masuk-2026-07-10-13-19-43-siswa28.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:43', '2026-07-10 13:19:43', NULL),
(15, 95, '2026-07-10', '13:18:08', 'masuk-2026-07-10-13-19-52-siswa10.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:52', '2026-07-10 13:19:52', NULL),
(16, 119, '2026-07-10', '13:18:18', 'masuk-2026-07-10-13-19-53-siswa34.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:53', '2026-07-10 13:19:53', NULL),
(17, 120, '2026-07-10', '13:17:21', 'masuk-2026-07-10-13-19-54-siswa35.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:54', '2026-07-10 13:19:54', NULL),
(18, 109, '2026-07-10', '13:18:12', 'masuk-2026-07-10-13-19-56-siswa24.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:56', '2026-07-10 13:19:56', NULL),
(19, 109, '2026-07-10', '13:18:12', 'masuk-2026-07-10-13-19-59-siswa24.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:19:59', '2026-07-10 13:19:59', NULL),
(20, 114, '2026-07-10', '13:18:40', 'masuk-2026-07-10-13-20-01-siswa29.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:01', '2026-07-10 13:20:01', NULL),
(21, 121, '2026-07-10', '13:16:55', 'masuk-2026-07-10-13-20-16-siswa36.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:16', '2026-07-10 13:20:16', NULL),
(22, 108, '2026-07-10', '13:18:11', 'masuk-2026-07-10-13-20-20-siswa23.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:20', '2026-07-10 13:20:20', NULL),
(23, 121, '2026-07-10', '13:16:55', 'masuk-2026-07-10-13-20-24-siswa36.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:24', '2026-07-10 13:20:24', NULL),
(24, 111, '2026-07-10', '13:19:33', 'masuk-2026-07-10-13-20-27-siswa26.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:27', '2026-07-10 13:20:27', NULL),
(25, 111, '2026-07-10', '13:19:33', 'masuk-2026-07-10-13-20-31-siswa26.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:31', '2026-07-10 13:20:31', NULL),
(26, 107, '2026-07-10', '13:19:30', 'masuk-2026-07-10-13-20-41-siswa22.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:41', '2026-07-10 13:20:41', NULL),
(27, 107, '2026-07-10', '13:19:30', 'masuk-2026-07-10-13-20-42-siswa22.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:43', '2026-07-10 13:20:43', NULL),
(28, 107, '2026-07-10', '13:19:30', 'masuk-2026-07-10-13-20-46-siswa22.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:46', '2026-07-10 13:20:46', NULL),
(29, 112, '2026-07-10', '13:19:55', 'masuk-2026-07-10-13-20-47-siswa27.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:47', '2026-07-10 13:20:47', NULL),
(30, 91, '2026-07-10', '13:19:35', 'masuk-2026-07-10-13-20-47-siswa6.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:20:47', '2026-07-10 13:20:47', NULL),
(31, 98, '2026-07-10', '13:20:22', 'masuk-2026-07-10-13-21-05-siswa13.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:05', '2026-07-10 13:21:05', NULL),
(32, 93, '2026-07-10', '13:19:53', 'masuk-2026-07-10-13-21-06-siswa8.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:06', '2026-07-10 13:21:06', NULL),
(33, 107, '2026-07-10', '13:19:30', 'masuk-2026-07-10-13-21-07-siswa22.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:07', '2026-07-10 13:21:07', NULL),
(34, 105, '2026-07-10', '13:20:19', 'masuk-2026-07-10-13-21-08-siswa20.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:08', '2026-07-10 13:21:08', NULL),
(35, 106, '2026-07-10', '13:19:53', 'masuk-2026-07-10-13-21-11-siswa21.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:11', '2026-07-10 13:21:11', NULL),
(36, 97, '2026-07-10', '13:19:58', 'masuk-2026-07-10-13-21-12-siswa12.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:12', '2026-07-10 13:21:12', NULL),
(37, 90, '2026-07-10', '13:20:37', 'masuk-2026-07-10-13-21-16-siswa5.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:16', '2026-07-10 13:21:16', NULL),
(38, 98, '2026-07-10', '13:20:22', 'masuk-2026-07-10-13-21-18-siswa13.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:18', '2026-07-10 13:21:18', NULL),
(39, 101, '2026-07-10', '13:21:11', 'masuk-2026-07-10-13-21-48-siswa16.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:21:48', '2026-07-10 13:21:48', NULL),
(40, 110, '2026-07-10', '13:21:19', 'masuk-2026-07-10-13-22-35-siswa25.png', '0000-00-00', '00:00:00', '', '2026-07-10 13:22:35', '2026-07-10 13:22:35', NULL),
(41, 3, '2026-07-10', '20:35:15', 'masuk-2026-07-10-20-35-32-hanifhasan.png', '0000-00-00', '00:00:00', '', '2026-07-10 20:35:32', '2026-07-10 20:35:32', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `id_pegawai` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_hash` varchar(255) DEFAULT NULL,
  `reset_at` datetime DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `activate_hash` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `status_message` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `force_pass_reset` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `id_pegawai`, `email`, `username`, `password_hash`, `reset_hash`, `reset_at`, `reset_expires`, `activate_hash`, `status`, `status_message`, `active`, `force_pass_reset`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'jaya@present.com', 'jayaputra', '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL),
(2, 2, 'tamani@present.com', 'tamanindah', '$2y$10$EdFcZIe2FO5BFecVBHB58e0zI1aLswPtOKZkaE.Fw2oA/caUwbm7q', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL),
(3, 3, 'ahmadhanif@gmail.com', 'hanifhasan', '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe', NULL, '2025-09-04 07:31:29', NULL, '', NULL, NULL, 1, 0, NULL, '2026-07-10 20:32:54', NULL),
(9, 6, 'siswa1@gmail.com', 'siswa1', '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe', NULL, '2025-09-04 07:31:29', NULL, '', NULL, NULL, 1, 0, NULL, '2025-09-04 08:06:16', NULL),
(10, 7, 'siswa2@gmail.com', 'siswa2', '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe', NULL, '2025-09-04 07:31:29', NULL, '', NULL, NULL, 1, 0, NULL, '2025-09-04 08:06:16', NULL),
(11, 8, 'siswa3@gmail.com', 'siswa3', '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe', NULL, '2025-09-04 07:31:29', NULL, '', NULL, NULL, 1, 0, NULL, '2025-09-04 08:06:16', NULL),
(92, 89, 'siswa4@gmail.com', 'siswa4', '$2y$10$hXfz1UeFwQxxOYY1PfiYEOs1pbeSF8Tvnt89HR8JQ2R9PZREzztnq', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(93, 90, 'siswa5@gmail.com', 'siswa5', '$2y$10$JZro9LxzMD5ITk2G3XzWo.aQS.vn2R6VCn8vJwBUFRK7ncgd9BrZO', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(94, 91, 'siswa6@gmail.com', 'siswa6', '$2y$10$8EZyxzTTn0wBiAxcR5yIR.toeyJIbXR8TI9xkFz619F0ng2IpgByC', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(95, 92, 'siswa7@gmail.com', 'siswa7', '$2y$10$Cf3IciEp2wBcx7z.AFTLiOWT4dbPstw5Hs1QMBRnZiwz5JQhhB/qS', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(96, 93, 'siswa8@gmail.com', 'siswa8', '$2y$10$wRdBM37k4dCjxnHZsW7pIO.18Fn9YnE20PIhX2DW8Pu2mxMMVczqa', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(97, 94, 'siswa9@gmail.com', 'siswa9', '$2y$10$nnH116iq8v5iF1pun.lg/Obz.qiTMjG/piv/lLTPv2tADsyOyT4ue', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(98, 95, 'siswa10@gmail.com', 'siswa10', '$2y$10$3ARVYSKj9U58IvhTHii3ZO6DNQ7AOVqxJB3gmPSOUskH2Oi7hftge', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(99, 96, 'siswa11@gmail.com', 'siswa11', '$2y$10$KZvXtBSgbXBmvxiZM3k1m.SSVXhiGals70FSp0NAvIR0GeJLezu/S', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(100, 97, 'siswa12@gmail.com', 'siswa12', '$2y$10$skqjP2LlAVTCAl3cLFEMBedWk03Q.xb39cWKXhYCbJS9xN5as8ocC', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(101, 98, 'siswa13@gmail.com', 'siswa13', '$2y$10$EaEbqWwsNHzQ.RZ2canQkOBLuTpkCPS1QujfLvZW71z23hdUFOLle', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(102, 99, 'siswa14@gmail.com', 'siswa14', '$2y$10$yKbSnDn/BJ2jwVjJcpE0U.0BuFNu6t9VN6YYtPCsUWfa.2Qv6HqGa', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(103, 100, 'siswa15@gmail.com', 'siswa15', '$2y$10$5gaEm5pbz/OMWN678HcOLO.mWFTgrzvNrqEt2PiM9hCUwfdvIJVhe', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(104, 101, 'siswa16@gmail.com', 'siswa16', '$2y$10$BkyBqlMg.sEr6k0iwABurOTO7S1dNGJN3abvW3mTEbc6YJM.lBq32', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:27', '2026-07-09 13:59:27', NULL),
(105, 102, 'siswa17@gmail.com', 'siswa17', '$2y$10$TZHZFeX5oIvvSuzHr4mOO.LBpIw5tUU2euk./Eq5Vx0F2aARuZLIq', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(106, 103, 'siswa18@gmail.com', 'siswa18', '$2y$10$lgSz1REgfc38Tjkie3nTt.bM55tnCLDwXxjhM91JV6O2YvCOPscwy', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(107, 104, 'siswa19@gmail.com', 'siswa19', '$2y$10$MFCSX0h3ERGtPqv4sdKL8uMjYX9iR8JTfd6jAboalGJzo7J8l7gOS', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(108, 105, 'siswa20@gmail.com', 'siswa20', '$2y$10$919xjldHxCzU62qU2f1ik.zT.VJvmxoL0ruY6bu8yT2rCvqc.RMU.', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(109, 106, 'siswa21@gmail.com', 'siswa21', '$2y$10$UK1xbawjmb7gCptoaanGUeWvZqYdrsCZ0KxJSRLMFWs6YwOSrc7wu', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(110, 107, 'siswa22@gmail.com', 'siswa22', '$2y$10$JRaQ7IILSM2qiPUAjbBrlua6AFr7m1rZrv9m31pOSlQZq/59zFY6q', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(111, 108, 'siswa23@gmail.com', 'siswa23', '$2y$10$N3lu3Pb7grvRditsA4dhCOZS1KiRITXka6LPGsYZxpb03KJ4Mr5Q6', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(112, 109, 'siswa24@gmail.com', 'siswa24', '$2y$10$aCVzbOQgccWLfl.FhIuAmOTnjuM.7vHRIzg3xNz7f8F38ZlOl/IAu', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(113, 110, 'siswa25@gmail.com', 'siswa25', '$2y$10$X3U2HrOr/zMTxpU2huSMOus1JKS3dFmGW0Ah/V.rqZSqUdLtmij0m', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(114, 111, 'siswa26@gmail.com', 'siswa26', '$2y$10$0uaU1bP9zEXcU7wGt6Jr7uB.och3yyLtXL3N14WNl5flwL8ov1PFm', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(115, 112, 'siswa27@gmail.com', 'siswa27', '$2y$10$Our46GyCDKoQI43h24seyeoI40EDPx0n5mm6cQ0H/JUgIKwq6ag4m', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(116, 113, 'siswa28@gmail.com', 'siswa28', '$2y$10$HClXOnFhvIrECdvox7ML8.tdB.mGP2nfiHc5piUfIjVXwiB.uWHwC', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(117, 114, 'siswa29@gmail.com', 'siswa29', '$2y$10$gXkfaSFTrZ2D5HzZU36Lku/X6.Gus/hAupZTIRKIUXLo9IaiOFLru', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(118, 115, 'siswa30@gmail.com', 'siswa30', '$2y$10$AiCmY/XIVXeGBlLVUWDSOOLrtnGHyUAQjIVaEarnCR2aisfyoJE1e', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:28', '2026-07-09 13:59:28', NULL),
(119, 116, 'siswa31@gmail.com', 'siswa31', '$2y$10$n3ydCQibXrC1F55TqO5yeOk0J93M6y5oOv7WIWTF6VOiI/CiaYqRC', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(120, 117, 'siswa32@gmail.com', 'siswa32', '$2y$10$r/XM7wUm60VAhCAjontD6uHPAOZPcgS5u5mxWG4xtLIZHXUSZy9Yq', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(121, 118, 'siswa33@gmail.com', 'siswa33', '$2y$10$pRjnQeBMiliYdcS6CPfwn.7hhgXd0h4O22dA8w3xcbZCsSOIg8C/6', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(122, 119, 'siswa34@gmail.com', 'siswa34', '$2y$10$2C7w8M1/EewaVexUjeMM5OXFZGQhFzkib.gpViQJ.0WNJu9RMStt6', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(123, 120, 'siswa35@gmail.com', 'siswa35', '$2y$10$ACUreXlGK34VthHVMpzapeM4k5nyKepPwvx0KOViK187U9gYaO/Je', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(124, 121, 'siswa36@gmail.com', 'siswa36', '$2y$10$NF5RbmdUJSg2KgvOxwCHxusnyy8.SeYbr3RjqVL/JWEEwW2Nn2cga', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(125, 122, 'siswa37@gmail.com', 'siswa37', '$2y$10$YFKPjLTjZlXowXsoWbT2qOKwyX41GjDoiRcOODskyGvrcnaeHXKgK', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(126, 123, 'siswa38@gmail.com', 'siswa38', '$2y$10$crwu3QTbF3nH04zlSXO8ee.iErKh20qRCbQ7xu91m9KvsgU4ysNh2', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(127, 124, 'siswa39@gmail.com', 'siswa39', '$2y$10$N2q/Sptd622Yi94KP2YGGexCNQ9nD/fCVtu0ing6pOeUPQJUC4QPq', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(128, 125, 'siswa40@gmail.com', 'siswa40', '$2y$10$nfo3avMxpDda3.4N8rjAK.TmThIC39P4AqrSOgPFvZvSGiUwXH4hu', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-09 13:59:29', '2026-07-09 13:59:29', NULL),
(137, 126, 'siswa41@gmail.com', 'siswa41', '$2y$10$FJE5.7FJZSHPeRkEKtNgVuijglHDq5fJ7fbQ5UF.wlm9brArAAysG', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(138, 127, 'siswa42@gmail.com', 'siswa42', '$2y$10$BKp5JPnX48JbgAcKIqoSluEAi8u9s.mW6elLw2XnMSCF7dexFZBfG', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(139, 128, 'siswa43@gmail.com', 'siswa43', '$2y$10$sljqa/zcX2c1CWY5UE6Ze.DVXaV4Wet5oNAQBXYEb0NfTT0ZEiz/2', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL),
(140, 129, 'siswa44@gmail.com', 'siswa44', '$2y$10$YpoAr52Da7v7utcaX7HzDO5t/9SRRnRhMxN4VAjs631XblGbt8RuK', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2026-07-10 12:59:58', '2026-07-10 12:59:58', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `auth_activation_attempts`
--
ALTER TABLE `auth_activation_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `auth_groups`
--
ALTER TABLE `auth_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `auth_groups_permissions`
--
ALTER TABLE `auth_groups_permissions`
  ADD KEY `auth_groups_permissions_permission_id_foreign` (`permission_id`),
  ADD KEY `group_id_permission_id` (`group_id`,`permission_id`);

--
-- Indeks untuk tabel `auth_groups_users`
--
ALTER TABLE `auth_groups_users`
  ADD KEY `auth_groups_users_user_id_foreign` (`user_id`),
  ADD KEY `group_id_user_id` (`group_id`,`user_id`);

--
-- Indeks untuk tabel `auth_logins`
--
ALTER TABLE `auth_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `auth_permissions`
--
ALTER TABLE `auth_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `auth_reset_attempts`
--
ALTER TABLE `auth_reset_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auth_tokens_user_id_foreign` (`user_id`),
  ADD KEY `selector` (`selector`);

--
-- Indeks untuk tabel `auth_users_permissions`
--
ALTER TABLE `auth_users_permissions`
  ADD KEY `auth_users_permissions_permission_id_foreign` (`permission_id`),
  ADD KEY `user_id_permission_id` (`user_id`,`permission_id`);

--
-- Indeks untuk tabel `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ketidakhadiran_id_pegawai_foreign` (`id_pegawai`);

--
-- Indeks untuk tabel `lokasi_presensi`
--
ALTER TABLE `lokasi_presensi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `pegawai_id_jabatan_foreign` (`id_jabatan`),
  ADD KEY `pegawai_id_lokasi_presensi_foreign` (`id_lokasi_presensi`);

--
-- Indeks untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `presensi_id_pegawai_foreign` (`id_pegawai`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `users_id_pegawai_foreign` (`id_pegawai`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `auth_activation_attempts`
--
ALTER TABLE `auth_activation_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `auth_groups`
--
ALTER TABLE `auth_groups`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `auth_logins`
--
ALTER TABLE `auth_logins`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT untuk tabel `auth_permissions`
--
ALTER TABLE `auth_permissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `auth_reset_attempts`
--
ALTER TABLE `auth_reset_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `lokasi_presensi`
--
ALTER TABLE `lokasi_presensi`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `auth_groups_permissions`
--
ALTER TABLE `auth_groups_permissions`
  ADD CONSTRAINT `auth_groups_permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `auth_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auth_groups_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `auth_groups_users`
--
ALTER TABLE `auth_groups_users`
  ADD CONSTRAINT `auth_groups_users_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `auth_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auth_groups_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `auth_users_permissions`
--
ALTER TABLE `auth_users_permissions`
  ADD CONSTRAINT `auth_users_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auth_users_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  ADD CONSTRAINT `ketidakhadiran_id_pegawai_foreign` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `pegawai_id_jabatan_foreign` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pegawai_id_lokasi_presensi_foreign` FOREIGN KEY (`id_lokasi_presensi`) REFERENCES `lokasi_presensi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_id_pegawai_foreign` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_id_pegawai_foreign` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
