-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Mar 2026 pada 04.33
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_manager`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `comments`
--

INSERT INTO `comments` (`id`, `task_id`, `user_id`, `content`, `attachment`, `created_at`) VALUES
(4, 3, 2, 'Testing berhasil, semua fungsi bekerja', NULL, '2026-02-06 14:02:40'),
(5, 4, 1, 'Bug sudah diperbaiki, ready for production', NULL, '2026-02-06 14:02:40'),
(7, 3, 1, 'baguss', NULL, '2026-02-11 09:21:38'),
(8, 6, 2, 'baikk', NULL, '2026-02-11 09:40:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `default_column_settings`
--

CREATE TABLE `default_column_settings` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `column_name` enum('todo','in_progress','review','done') NOT NULL,
  `custom_title` varchar(100) DEFAULT NULL,
  `custom_color` varchar(20) DEFAULT NULL,
  `custom_icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('assignment','deadline','comment','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Tugas Baru', 'Anda ditugaskan untuk: Design Homepage', 'assignment', 0, '2026-02-06 14:02:40'),
(2, 3, 'Tugas Baru', 'Anda ditugaskan untuk: Develop Login API', 'assignment', 0, '2026-02-06 14:02:40'),
(3, 2, 'Deadline Mendekati', 'Tugas: Testing Module User deadline 2 hari lagi', 'deadline', 0, '2026-02-06 14:02:40'),
(4, 1, 'Komentar Baru', 'John Doe mengomentari tugas Anda', 'comment', 1, '2026-02-06 14:02:40'),
(5, 2, 'Komentar Baru', 'Administrator mengomentari tugas: Design Homepage', 'comment', 0, '2026-02-11 08:41:02'),
(6, 2, 'Role Diubah', 'Role Anda di proyek Website Development telah diubah menjadi Anggota oleh Administrator', 'system', 0, '2026-02-11 08:42:22'),
(7, 2, 'Tugas Dihapus', 'Tugas \"Design Homepage\" telah dihapus oleh Administrator', 'system', 0, '2026-02-11 09:10:30'),
(8, 3, 'Tugas Dihapus', 'Tugas \"Develop Login API\" telah dihapus oleh Administrator', 'system', 0, '2026-02-11 09:20:55'),
(9, 3, 'Komentar Baru', 'Administrator mengomentari tugas: Testing Module User', 'comment', 0, '2026-02-11 09:21:38'),
(10, 2, 'Komentar Baru', 'Administrator mengomentari tugas: Testing Module User', 'comment', 0, '2026-02-11 09:21:38'),
(11, 3, 'Komentar Baru', 'John Doe mengomentari tugas: Backend Development', 'comment', 0, '2026-02-11 09:40:29'),
(12, 3, 'Tugas Baru', 'Administrator menugaskan Anda: jkjkjkjkjk di proyek Website Development', 'assignment', 0, '2026-02-11 11:16:32'),
(13, 3, 'Tugas Dihapus', 'Tugas \"jkjkjkjkjk\" telah dihapus oleh Administrator', 'system', 0, '2026-02-11 11:16:51'),
(14, 3, 'Tugas Baru', 'Administrator menugaskan Anda: Membuat halaman login di proyek Website Development', 'assignment', 0, '2026-02-11 11:17:24'),
(15, 3, 'Tugas Baru', 'Administrator menugaskan Anda: desain login di proyek Website Development', 'assignment', 0, '2026-02-12 03:00:38'),
(16, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"desain login\" menjadi ', 'system', 0, '2026-02-12 03:00:42'),
(17, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"desain login\" menjadi ', 'system', 0, '2026-02-12 03:00:46'),
(18, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"Membuat halaman login\" menjadi ', 'system', 0, '2026-02-12 03:00:48'),
(19, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"Membuat halaman login\" menjadi ', 'system', 0, '2026-02-12 05:15:08'),
(20, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"Membuat halaman login\" menjadi ', 'system', 0, '2026-02-12 05:15:11'),
(21, 3, 'Status Tugas Anda Diubah', 'Administrator mengubah status tugas \"desain login\" menjadi ', 'system', 0, '2026-02-12 05:15:13'),
(22, 2, 'Tugas Baru', 'Administrator menugaskan Anda: perbaiki bug', 'assignment', 0, '2026-02-18 07:00:09'),
(23, 3, 'Tugas Baru', 'Administrator menugaskan Anda: yayaya', 'assignment', 0, '2026-02-18 09:58:31'),
(24, 2, 'Tugas Baru', 'Administrator menugaskan Anda: hhhhh', 'assignment', 0, '2026-02-19 15:47:28'),
(25, 2, 'Komentar Baru', 'Administrator mengomentari: perbaiki bug', 'comment', 0, '2026-02-20 00:49:38'),
(26, 3, 'Tugas Baru', 'Administrator menugaskan Anda: yyy', 'assignment', 0, '2026-02-20 09:26:10'),
(27, 2, 'Komentar Baru', 'Administrator mengomentari: desain login', 'comment', 0, '2026-02-20 09:26:21'),
(28, 3, 'Tugas Baru', 'Administrator menugaskan Anda: kkkkmkkk', 'assignment', 0, '2026-02-26 04:57:22'),
(29, 2, 'Tugas Baru', 'Administrator menugaskan Anda: a', 'assignment', 0, '2026-02-27 08:46:08'),
(30, 3, 'Tugas Baru', 'John Doe menugaskan Anda: kk', 'assignment', 0, '2026-02-27 08:52:15'),
(31, 2, 'Undangan Proyek', 'Administrator menambahkan Anda ke proyek: a', 'system', 0, '2026-03-04 05:16:00'),
(32, 3, 'Undangan Proyek', 'Administrator menambahkan Anda ke proyek: a', 'system', 0, '2026-03-04 05:16:11'),
(33, 2, 'Tugas Baru', 'Administrator menugaskan Anda: bbb', 'assignment', 0, '2026-03-04 05:16:44'),
(34, 2, 'Komentar Baru', 'Administrator mengomentari: bbb', 'comment', 0, '2026-03-04 05:18:49'),
(35, 3, 'Proyek Diperbarui', 'Administrator memperbarui proyek: ab', 'system', 0, '2026-03-04 05:19:09'),
(36, 2, 'Proyek Diperbarui', 'Administrator memperbarui proyek: ab', 'system', 0, '2026-03-04 05:19:09'),
(37, 2, 'Tugas Baru', 'Administrator menugaskan Anda: y', 'assignment', 0, '2026-03-04 06:05:28'),
(38, 2, 'Komentar Baru', 'Administrator mengomentari: ab', 'comment', 0, '2026-03-04 06:05:51'),
(39, 2, 'Undangan Proyek', 'Administrator menambahkan Anda ke proyek: a', 'system', 0, '2026-03-04 06:46:18'),
(40, 2, 'Tugas Baru', 'Administrator menugaskan Anda: bb', 'assignment', 0, '2026-03-04 06:46:43'),
(41, 2, 'Undangan Proyek', 'Administrator menambahkan Anda ke proyek: mm', 'system', 0, '2026-03-05 02:53:20'),
(42, 2, 'Proyek Dihapus', 'Proyek \"mm\" telah dihapus oleh Administrator', 'system', 0, '2026-03-05 02:54:28'),
(43, 2, 'Undangan Proyek', 'Administrator menambahkan Anda ke proyek: vv', 'system', 0, '2026-03-05 02:56:08'),
(44, 2, 'Tugas Baru', 'Administrator menugaskan Anda: jnbjbj', 'assignment', 0, '2026-03-05 02:56:40'),
(45, 2, 'Proyek Dihapus', 'Proyek \"vv\" telah dihapus oleh Administrator', 'system', 0, '2026-03-05 03:19:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, 'Website Development', 'Pengembangan website perusahaan e-commerce', 1, '2026-02-06 14:02:40'),
(2, 'Mobile App', 'Pembuatan aplikasi mobile untuk Android dan iOS', 2, '2026-02-06 14:02:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `project_columns`
--

CREATE TABLE `project_columns` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#64748b',
  `icon` varchar(50) DEFAULT 'bi-circle',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `project_members`
--

CREATE TABLE `project_members` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','admin','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `project_members`
--

INSERT INTO `project_members` (`project_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 'owner', '2026-02-06 14:02:40'),
(1, 2, 'member', '2026-02-06 14:02:40'),
(1, 3, 'member', '2026-02-06 14:02:40'),
(2, 2, 'owner', '2026-02-06 14:02:40'),
(2, 3, 'member', '2026-02-06 14:02:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `column_status` enum('todo','in_progress','review','done') DEFAULT 'todo',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assignee_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attachment` varchar(255) DEFAULT NULL,
  `column_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `project_id`, `column_status`, `priority`, `assignee_id`, `created_by`, `due_date`, `created_at`, `updated_at`, `attachment`, `column_id`) VALUES
(3, 'Testing Module User', 'Lakukan testing pada modul user', 1, 'review', 'low', 3, 3, '2026-03-24', '2026-02-06 14:02:40', '2026-03-04 16:10:00', NULL, NULL),
(4, 'Fix Bug Payment', 'Perbaiki bug pada proses pembayaran', 1, 'done', 'urgent', 1, 2, '2026-03-06', '2026-02-06 14:02:40', '2026-03-04 16:10:16', NULL, NULL),
(5, 'UI Design Mobile', 'Desain interface aplikasi mobile', 2, 'todo', 'high', 3, 2, '2026-03-10', '2026-02-06 14:02:40', '2026-03-04 08:30:50', NULL, NULL),
(6, 'Backend Development', 'Kembangkan backend untuk mobile app', 2, 'in_progress', 'medium', 2, 3, '2026-03-19', '2026-02-06 14:02:40', '2026-03-04 17:25:00', NULL, NULL),
(9, 'Membuat halaman login', '', 1, 'in_progress', 'urgent', 2, 1, '2026-03-19', '2026-02-11 11:17:24', '2026-03-05 02:59:23', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `role` enum('admin','member') DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `profile_picture`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@taskmanager.com', '$2y$10$Oq7fGrJc.JzG8KRhoSNqAuhSGb2w7VDC35JkK4e2bxq6vAQseek6e', 'Administrator', 'default.jpg', 'admin', '2026-02-06 14:02:40'),
(2, 'john', 'john@example.com', '$2y$10$kSqIo8haBsnJifB5HiCZJuYMw7S31vxEf4XKvEL7yZ71Vj26buYR2', 'John Doe', 'default.jpg', 'member', '2026-02-06 14:02:40'),
(3, 'jane', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'default.jpg', 'member', '2026-02-06 14:02:40'),
(5, 'latif junia', 'niaa', '$2y$10$/kzdaXodQxBwoHr4BV9Mgem0.nzgqifKMgv52afd0mt6SytCPnvBe', 'nia1234', 'default.jpg', 'member', '2026-03-05 03:29:50');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indeks untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `default_column_settings`
--
ALTER TABLE `default_column_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_column` (`project_id`,`column_name`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `project_columns`
--
ALTER TABLE `project_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assignee_id` (`assignee_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `column_id` (`column_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `default_column_settings`
--
ALTER TABLE `default_column_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT untuk tabel `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `project_columns`
--
ALTER TABLE `project_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `default_column_settings`
--
ALTER TABLE `default_column_settings`
  ADD CONSTRAINT `default_column_settings_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `project_columns`
--
ALTER TABLE `project_columns`
  ADD CONSTRAINT `project_columns_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_columns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`column_id`) REFERENCES `project_columns` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
