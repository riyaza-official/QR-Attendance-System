-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 29, 2026 at 06:36 PM
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
-- Database: `qr_attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('present','absent') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `session_id`, `student_id`, `marked_at`, `status`) VALUES
(1, 3, 1, '2026-05-24 13:39:37', 'present'),
(2, 7, 1, '2026-06-24 08:03:44', 'present'),
(3, 9, 1, '2026-06-24 09:14:21', 'present'),
(4, 11, 2, '2026-06-28 14:36:09', 'present'),
(5, 11, 8, '2026-06-28 14:36:33', 'present'),
(6, 11, 1, '2026-06-28 14:36:52', 'present'),
(7, 11, 9, '2026-06-28 14:37:05', 'present'),
(8, 11, 10, '2026-06-28 14:39:09', 'present'),
(9, 11, 11, '2026-06-28 14:39:23', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `lecturer_attendance`
--

CREATE TABLE `lecturer_attendance` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `entry_time` datetime DEFAULT NULL,
  `exit_time` datetime DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturer_attendance`
--

INSERT INTO `lecturer_attendance` (`id`, `lecturer_id`, `entry_time`, `exit_time`, `date`, `created_at`) VALUES
(1, 5, '2026-06-23 05:49:07', '2026-06-23 05:49:21', '2026-06-23', '2026-06-23 00:19:07'),
(2, 9, '2026-06-24 13:26:11', '2026-06-24 13:26:22', '2026-06-24', '2026-06-24 07:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_date` date DEFAULT NULL,
  `session_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `subject`, `lecturer_id`, `qr_token`, `expires_at`, `created_at`, `session_date`, `session_time`) VALUES
(1, 'web Development', 3, 'd89ade8e628d568d11e94bfdc4b71bf3', '2026-05-18 10:45:08', '2026-05-18 08:40:08', '2026-04-15', '06:46:00'),
(2, 'web technology', 3, 'deb376d6a0cd8a92afff5ad4b8a3a368', '2026-05-18 11:05:07', '2026-05-18 08:55:07', '2026-05-18', '10:53:00'),
(3, 'Software Engineering', 3, '44ca515c496d618e915d9cc249c0dda2', '2026-05-24 15:47:58', '2026-05-24 13:32:58', '2026-05-24', '15:32:00'),
(4, 'IT Project Management', 3, '6390a18a69428a9e2289045f8b1afc0a', '2026-05-24 15:58:22', '2026-05-24 13:48:22', '2026-05-24', '15:47:00'),
(5, 'project managment', 3, 'f902f6100091d086d1fcb08314f9bc8a', '2026-05-26 10:29:20', '2026-05-26 08:19:20', '2026-05-26', '10:18:00'),
(6, 'web develpment', 3, '1074e9c9ffc957b9cb2076d36603cf84', '2026-05-26 12:00:21', '2026-05-26 09:50:21', '2026-05-26', '11:49:00'),
(7, 'professional World', 3, 'a5a9f1772cf9a2aba2d74a9c428c43bc', '2026-06-24 10:12:33', '2026-06-24 08:02:33', '2026-06-24', '10:02:00'),
(8, 'mobile app', 3, 'befdc4c330c47434ec42f9174d00539e', '2026-06-24 10:34:54', '2026-06-24 08:24:54', '2026-06-24', '10:19:00'),
(9, 'software quaality asurance ', 3, 'bf444fed7930e0a502a9021f78118477', '2026-06-24 11:21:43', '2026-06-24 09:11:43', '2026-06-24', '11:05:00'),
(10, 'Mobile app development', 16, 'f5f9f3aecbc6083bed01ffb71d3fd749', '2026-06-28 16:27:07', '2026-06-28 14:17:07', '2026-06-28', '16:17:00'),
(11, 'Software Engineering', 3, '828a8ba83cf98eb0df359387b66c1368', '2026-06-28 16:41:13', '2026-06-28 14:31:13', '2026-06-28', '16:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `batch` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `batch`) VALUES
(1, 'KAN/IT/2324/F/0204', 'Riyaza mahir', 'riyazamahir@gmail.com', '2023'),
(2, 'KAN/IT/2324/F/0123', 'Dulani Hemachandra', 'dulanisliate@gmail.com', '2023'),
(8, 'KAN/IT/2324/F/113', 'nipuni', 'nipuni@gmail.com', '2023'),
(9, 'KAN/IT/2324/F/123', 'eshani', 'eshani@gmail.com', '2023'),
(10, 'KAN/IT/2324/F/203', 'ashfa', 'ashfa@gmail.com', '2024'),
(11, 'KAN/IT/2324/F/202', 'hasna', 'hasna@gmail.com', '2023');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','lecturer') NOT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `qr_token`, `subject`, `phone`) VALUES
(3, 'admin', 'admin@ati.ac.lk', '$2y$10$fSD20fodJkcK/RHRHQoA3uSdGu3l9JkoHtav0YmAH4j0KaEaarW1y', 'admin', NULL, 'Mr. Kumara', NULL),
(11, 'Ms.piyumika', 'piyumika@gmail.com', '$2y$10$rbKTv5dxLOf6XIL2TiPVkOjbLn9ElT7Cx8Oj.S41B1NxbcVIkBH/O', 'lecturer', NULL, 'Software Engineering', '0770732056'),
(12, 'Ms.dinithi', 'dinithi@gmail.com', '$2y$10$Jx35x8l/hZg.Ne9NjXxybuduX9RNLv83ga.LWHHs0MDiu/oSrEMIW', 'lecturer', NULL, 'quality assurance', '0770732056'),
(13, 'Mr.Nishantha', 'nishantha@gmail.com', '$2y$10$6P4bMPeK2f.qHoQMA016iuB474p51u79bYrHQI.ksI2IKRiL4ZmXG', 'lecturer', NULL, 'IT Project Management', '0770732056'),
(14, 'Ms.pradeepika', 'pradeepika@gmail.com', '$2y$10$NExg129rZ.bCrYtj6hCWy.BbMf2mvFhx8htGV2rJGWQ0kWGElp81i', 'lecturer', NULL, 'individual project', '0770732056'),
(15, 'Mr.bosco', 'bosco@gmail.com', '$2y$10$1WGH5tVVv6208JQOzHxrl.lhZ7gRR/UrdPCAZM.tPfiK3kpCm8Ds6', 'lecturer', NULL, 'machine learning', '0770732056'),
(16, 'Mr.nuwan', 'nuwan@gmail.com', '$2y$10$McTXjHjDhb/WWByDtVHPeOSCcRfWKHvp2Lv7abfUv/E1I1aK1j2e.', 'lecturer', NULL, 'Mobile app development', '0770732056');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lecturer_attendance`
--
ALTER TABLE `lecturer_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lecturer_attendance`
--
ALTER TABLE `lecturer_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
