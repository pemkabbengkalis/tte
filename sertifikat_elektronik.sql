-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 01:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sertifikat_elektronik`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_permohonan`
--

CREATE TABLE `dokumen_permohonan` (
  `id` char(36) NOT NULL,
  `permohonan_id` char(36) NOT NULL,
  `jenis_dokumen` enum('surat_permohonan','sk_jabatan','sk_pangkat','ktp','email_dinas') NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `path_file` varchar(500) NOT NULL,
  `ukuran_file` int(10) UNSIGNED NOT NULL,
  `mime_type` varchar(50) NOT NULL,
  `versi` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_05_28_073446_create_permohonan_table', 1),
(5, '2026_05_28_073502_create_dokumen_permohonan_table', 1),
(6, '2026_05_28_073517_create_riwayat_verifikasi_table', 1),
(7, '2026_05_28_073525_create_notifikasi_table', 1),
(8, '2026_05_28_073538_create_template_dokumen_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `permohonan_id` char(36) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('permohonan_baru','pengajuan_ulang','diterima','ditolak') NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permohonan`
--

CREATE TABLE `permohonan` (
  `id` char(36) NOT NULL,
  `nomor_permohonan` varchar(30) DEFAULT NULL,
  `pemohon_id` char(36) NOT NULL,
  `jenis_permohonan` enum('TTE','Pengamanan Dokumen','Pengamanan Email','Pengamanan Web') NOT NULL,
  `status` enum('draft','menunggu_verifikasi','diterima','ditolak','selesai') NOT NULL DEFAULT 'draft',
  `tanggal_pengajuan` timestamp NULL DEFAULT NULL,
  `tanggal_verifikasi` timestamp NULL DEFAULT NULL,
  `verifikator_id` char(36) DEFAULT NULL,
  `catatan_verifikator` text DEFAULT NULL,
  `jumlah_pengajuan` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_verifikasi`
--

CREATE TABLE `riwayat_verifikasi` (
  `id` char(36) NOT NULL,
  `permohonan_id` char(36) NOT NULL,
  `verifikator_id` char(36) NOT NULL,
  `aksi` enum('diterima','ditolak') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_dokumen`
--

CREATE TABLE `template_dokumen` (
  `id` char(36) NOT NULL,
  `nama_template` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `nama_file` varchar(255) NOT NULL,
  `path_file` varchar(500) NOT NULL,
  `versi` varchar(10) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `pangkat_gol` varchar(50) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `unit_kerja` varchar(150) DEFAULT NULL,
  `role` enum('pemohon','verifikator','admin') NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `nip`, `nik`, `email`, `password`, `no_hp`, `pangkat_gol`, `jabatan`, `instansi`, `unit_kerja`, `role`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
('019e804b-a7d1-73ff-b8b4-fef8b4255dd4', 'Administrator Dinas Kominfo', '197001011990031001', '1403011101700001', 'admin@bengkaliskab.go.id', '$2y$12$V7e6lHPZBwlRqBDR7jecBu1Foww43BQZEsJ8XBJ6Cykasv/FdMR9C', '081200000001', 'Pembina / IV-a', 'Administrator Sistem', 'Dinas Komunikasi, Informatika dan Statistik', 'Bidang Persandian dan Statistik', 'admin', '2026-05-31 16:08:22', NULL, '2026-05-31 16:08:22', '2026-05-31 16:08:22', NULL),
('019e804b-a88a-72be-b00f-10429acdd04f', 'Budi Santoso, S.Kom', '198505102010011002', '1403011005850002', '198505102010011002@bengkaliskab.go.id', '$2y$12$fWF3qA.jWBqFcbvqoH6EeO1zXHFpzVd/6JmGsd1wrbaClSKMLH2Ka', '081200000002', 'Penata / III-c', 'Pranata Komputer Ahli Muda', 'Dinas Komunikasi, Informatika dan Statistik', 'Bidang Persandian dan Statistik', 'verifikator', '2026-05-31 16:08:22', NULL, '2026-05-31 16:08:22', '2026-05-31 16:08:22', NULL),
('019e804b-a940-70bb-b10d-f873bf4bddd1', 'Siti Aminah, S.T', '199002152015032003', '1403015502900003', '199002152015032003@bengkaliskab.go.id', '$2y$12$IItbYjxgKneLFhqY8UY.2.6LNXrXTP670ws5Y1FKJGoOVbdiPR5OG', '081200000003', 'Penata Muda Tk.I / III-b', 'Analis Sistem Informasi', 'Dinas Komunikasi, Informatika dan Statistik', 'Bidang Persandian dan Statistik', 'verifikator', '2026-05-31 16:08:22', NULL, '2026-05-31 16:08:22', '2026-05-31 16:08:22', NULL),
('019e804b-a9f9-716e-963b-3a0f21e2e938', 'Andi Wijaya', '198803122012011004', '1403011203880004', '198803122012011004@bengkaliskab.go.id', '$2y$12$Z0q3pNdYRnjTRs7fWrftTeNQV5boAnSndZTQV2RQ3g61TLDsal8Oq', '081200000101', 'Penata Muda / III-a', 'Staf Administrasi', 'Dinas Pendidikan dan Kebudayaan', 'Sekretariat', 'pemohon', '2026-05-31 16:08:22', NULL, '2026-05-31 16:08:22', '2026-05-31 16:08:22', NULL),
('019e804b-aaad-71fa-b74a-e46a4cf3cae0', 'Dewi Lestari', '199105202018012005', '1403016005910005', '199105202018012005@bengkaliskab.go.id', '$2y$12$zX6xhjA.S35S5mmSMRPPCuQzYrR8qeVk33PaeiA9wA03q1mNh4Ec.', '081200000102', 'Penata Muda / III-a', 'Bendahara Pengeluaran', 'Dinas Kesehatan', 'Bidang Keuangan', 'pemohon', '2026-05-31 16:08:22', NULL, '2026-05-31 16:08:23', '2026-05-31 16:08:23', NULL),
('019e804b-ab65-7208-a890-e94bccdf0fbe', 'Rudi Hartono', '198007152006041006', '1403011507800006', '198007152006041006@bengkaliskab.go.id', '$2y$12$1I6dQ9/lX25G49QPrhuUDO6OEOljA/epuvPB3J3j9Yy0R29pWgcmW', '081200000103', 'Penata Tk.I / III-d', 'Kepala Seksi', 'Badan Kepegawaian Daerah', 'Bidang Mutasi', 'pemohon', '2026-05-31 16:08:23', NULL, '2026-05-31 16:08:23', '2026-05-31 16:08:23', NULL),
('019e804b-ac1a-71a1-bbdd-8148f49a3678', 'Nurul Hidayah', '199306102019032007', '1403012006930007', '199306102019032007@bengkaliskab.go.id', '$2y$12$9bvA62bAZs/YbdmOJ5ElquDWOYHrBFN66FIBsrOT2rqNA4HXiGWhi', '081200000104', 'Penata Muda / III-a', 'Analis Kepegawaian', 'Sekretariat Daerah', 'Bagian Organisasi', 'pemohon', '2026-05-31 16:08:23', NULL, '2026-05-31 16:08:23', '2026-05-31 16:08:23', NULL),
('019e804b-acd0-7179-b00e-923d3fb07cdc', 'Eko Prasetyo', '198909012014031008', '1403010109890008', '198909012014031008@bengkaliskab.go.id', '$2y$12$7qCCasvr0gey1pWxLEiUre//Lhk6cWrdVoUYHWMzBubYlg5/c4yEG', '081200000105', 'Penata Muda Tk.I / III-b', 'Pengelola Aset', 'Badan Pengelola Keuangan dan Aset', 'Bidang Aset', 'pemohon', '2026-05-31 16:08:23', NULL, '2026-05-31 16:08:23', '2026-05-31 16:08:23', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `dokumen_permohonan`
--
ALTER TABLE `dokumen_permohonan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dokumen_permohonan_permohonan_id_foreign` (`permohonan_id`),
  ADD KEY `dokumen_permohonan_jenis_dokumen_index` (`jenis_dokumen`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifikasi_user_id_foreign` (`user_id`),
  ADD KEY `notifikasi_permohonan_id_foreign` (`permohonan_id`),
  ADD KEY `notifikasi_is_read_index` (`is_read`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permohonan`
--
ALTER TABLE `permohonan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permohonan_nomor_permohonan_unique` (`nomor_permohonan`),
  ADD KEY `permohonan_pemohon_id_foreign` (`pemohon_id`),
  ADD KEY `permohonan_verifikator_id_foreign` (`verifikator_id`),
  ADD KEY `permohonan_status_index` (`status`);

--
-- Indexes for table `riwayat_verifikasi`
--
ALTER TABLE `riwayat_verifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `riwayat_verifikasi_permohonan_id_foreign` (`permohonan_id`),
  ADD KEY `riwayat_verifikasi_verifikator_id_foreign` (`verifikator_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `template_dokumen`
--
ALTER TABLE `template_dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_dokumen_uploaded_by_foreign` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_nip_unique` (`nip`),
  ADD UNIQUE KEY `users_nik_unique` (`nik`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen_permohonan`
--
ALTER TABLE `dokumen_permohonan`
  ADD CONSTRAINT `dokumen_permohonan_permohonan_id_foreign` FOREIGN KEY (`permohonan_id`) REFERENCES `permohonan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_permohonan_id_foreign` FOREIGN KEY (`permohonan_id`) REFERENCES `permohonan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifikasi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permohonan`
--
ALTER TABLE `permohonan`
  ADD CONSTRAINT `permohonan_pemohon_id_foreign` FOREIGN KEY (`pemohon_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permohonan_verifikator_id_foreign` FOREIGN KEY (`verifikator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `riwayat_verifikasi`
--
ALTER TABLE `riwayat_verifikasi`
  ADD CONSTRAINT `riwayat_verifikasi_permohonan_id_foreign` FOREIGN KEY (`permohonan_id`) REFERENCES `permohonan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_verifikasi_verifikator_id_foreign` FOREIGN KEY (`verifikator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_dokumen`
--
ALTER TABLE `template_dokumen`
  ADD CONSTRAINT `template_dokumen_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
