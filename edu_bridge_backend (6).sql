-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2026 at 11:53 PM
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
-- Database: `edu_bridge_backend`
--

-- --------------------------------------------------------

--
-- Table structure for table `absence_requests`
--

CREATE TABLE `absence_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` enum('important','student_activity','academic','administrative','general') NOT NULL DEFAULT 'general',
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `type` enum('general','course_specific') NOT NULL DEFAULT 'general',
  `target_audience` varchar(255) NOT NULL DEFAULT 'all',
  `target_role` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `user_id`, `title`, `content`, `image_path`, `category`, `image`, `link_url`, `type`, `target_audience`, `target_role`, `department_id`, `academic_year`, `course_id`, `created_at`, `updated_at`) VALUES
(27, 73, 'اعلان مهم', 'يرجى من جميع الطلاب الالتزام بأوقات الدوام', NULL, 'general', NULL, NULL, 'general', 'all', NULL, NULL, NULL, NULL, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(28, 73, 'برنامج الامتحانات', 'يرجى من جميع الطلاب الالتزام بالبرنامج', NULL, 'general', 'announcements/T3dl2pndfCA19Sp04tktKTgVAhQjf5wKyXyxx9zM.jpg', NULL, 'general', 'all', NULL, NULL, NULL, NULL, '2026-07-05 18:38:06', '2026-07-05 18:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `max_points` int(11) NOT NULL DEFAULT 100,
  `type` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `teacher_id`, `title`, `description`, `file_path`, `file_name`, `file_type`, `due_date`, `max_points`, `type`, `attachment_path`, `created_at`, `updated_at`) VALUES
(3, 12, NULL, 'تمرين', 'يرجى حل التمرين', NULL, NULL, NULL, '2026-06-01 23:59:00', 100, NULL, NULL, '2026-05-25 06:03:04', '2026-05-25 06:03:04'),
(4, 12, NULL, 'تجربه الملف', '..', NULL, NULL, NULL, '2026-06-03 18:59:00', 100, NULL, NULL, '2026-05-26 23:51:01', '2026-05-26 23:51:01'),
(5, 12, NULL, 'تجربة الواجب', 'واجب واحب', NULL, NULL, NULL, '2026-06-04 10:59:00', 100, NULL, NULL, '2026-05-28 21:20:21', '2026-05-28 21:20:21'),
(6, 12, NULL, 'تجربة الواجب', 'واجب واحب', NULL, NULL, NULL, '2026-06-04 10:59:00', 100, NULL, NULL, '2026-05-28 21:20:28', '2026-05-28 21:20:28'),
(7, 12, NULL, 'تجربة الواجب', 'واجب واحب', NULL, NULL, NULL, '2026-06-04 10:59:00', 100, NULL, NULL, '2026-05-28 21:21:48', '2026-05-28 21:21:48'),
(8, 12, NULL, 'حل مستئل نيوتن', 'تفاصيل الواجب', NULL, NULL, NULL, '2026-06-05 23:59:00', 100, NULL, NULL, '2026-05-29 07:29:55', '2026-05-29 07:29:55'),
(9, 12, NULL, 'حل الحل', 'تفاصيل', NULL, NULL, NULL, '2026-06-05 23:59:00', 100, NULL, NULL, '2026-05-29 07:57:38', '2026-05-29 07:57:38'),
(10, 12, NULL, 'حل', 'وصف', NULL, NULL, NULL, '2026-06-05 23:59:00', 100, NULL, 'assignments/1780055262_أدعية ليوم عرفة.pdf', '2026-05-29 08:47:42', '2026-05-29 08:47:42'),
(11, 12, NULL, '..', '..', NULL, NULL, NULL, '2026-06-05 23:59:00', 100, NULL, 'assignments/1780055769_المحاضرة الخامسة.pdf', '2026-05-29 08:56:09', '2026-05-29 08:56:09'),
(12, 12, NULL, 'حل', 'زز', NULL, NULL, NULL, '2026-06-06 23:59:00', 100, NULL, 'assignments/1780091281_S06-ASPRESTful API.pdf', '2026-05-29 18:48:01', '2026-05-29 18:48:01'),
(16, 1, 5, 'تمرين منزلي', 'يرجى حل', NULL, NULL, NULL, '2026-07-18 23:59:00', 100, NULL, 'assignments/1783265298_electricity_receipt_REF1779294361468.pdf', '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(17, 1, 5, 'تمرين مسائل', 'مسائل وتمرينات مهمة', NULL, NULL, NULL, '2026-07-09 13:59:00', 100, NULL, 'assignments/1783265363_internet_receipt_REF1779300231564.pdf', '2026-07-05 13:29:23', '2026-07-05 13:29:23');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `submission_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `student_notes` text DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`submission_id`, `assignment_id`, `student_id`, `file_path`, `student_notes`, `grade`, `feedback`, `submitted_at`, `created_at`, `updated_at`) VALUES
(8, 17, 31, 'assignments/17/1783265406_31_electricity_receipt_REF1779294728570.pdf', NULL, NULL, NULL, '2026-07-05 15:30:06', '2026-07-05 13:30:06', '2026-07-05 13:30:06'),
(9, 16, 31, 'assignments/16/1783265422_31_JPEG_20260705_183018_6088037005948865680.jpg', NULL, NULL, NULL, '2026-07-05 15:30:22', '2026-07-05 13:30:22', '2026-07-05 13:30:22');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `device_id` varchar(255) DEFAULT NULL COMMENT 'معرّف الجهاز الذي سجّل الحضور',
  `latitude` decimal(10,7) DEFAULT NULL COMMENT 'خط عرض موقع الطالب لحظة المسح',
  `longitude` decimal(10,7) DEFAULT NULL COMMENT 'خط طول موقع الطالب لحظة المسح',
  `reject_reason` enum('expired_qr','device_mismatch','location_too_far','already_marked','session_closed','face_mismatch') DEFAULT NULL,
  `face_image` mediumtext DEFAULT NULL,
  `face_score` double DEFAULT NULL,
  `face_status` enum('first_time','verified','suspicious','rejected') DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `excuse_text` text DEFAULT NULL,
  `excuse_attachment` varchar(255) DEFAULT NULL,
  `excuse_status` enum('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `lesson_id`, `status`, `device_id`, `latitude`, `longitude`, `reject_reason`, `face_image`, `face_score`, `face_status`, `attendance_date`, `excuse_text`, `excuse_attachment`, `excuse_status`, `created_at`, `updated_at`) VALUES
(61, 22, 60, 'present', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10', NULL, NULL, 'none', '2026-06-10 04:15:59', '2026-06-10 04:15:59'),
(69, 4, 64, 'present', NULL, NULL, NULL, NULL, NULL, 100, 'first_time', '2026-06-17', NULL, NULL, 'none', '2026-06-17 10:47:58', '2026-06-17 10:47:58'),
(70, 4, 65, 'present', NULL, NULL, NULL, NULL, NULL, 98.6, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 10:51:55', '2026-06-17 10:51:55'),
(71, 4, 66, 'present', NULL, NULL, NULL, NULL, NULL, 94.1, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 10:52:18', '2026-06-17 10:52:18'),
(72, 4, 67, 'present', NULL, NULL, NULL, NULL, NULL, 90, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 10:52:44', '2026-06-17 10:52:44'),
(73, 4, 69, 'present', NULL, NULL, NULL, NULL, NULL, 99.6, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 11:13:12', '2026-06-17 11:13:12'),
(75, 4, 71, 'present', NULL, NULL, NULL, NULL, NULL, 99.7, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 11:14:09', '2026-06-17 11:14:09'),
(76, 4, 72, 'present', NULL, NULL, NULL, NULL, NULL, 100, 'first_time', '2026-06-17', NULL, NULL, 'none', '2026-06-17 11:42:54', '2026-06-17 11:42:54'),
(77, 4, 73, 'absent', NULL, NULL, NULL, 'face_mismatch', NULL, 0, 'rejected', '2026-06-17', NULL, NULL, 'none', '2026-06-17 11:43:20', '2026-06-17 11:43:46'),
(78, 4, 74, 'absent', NULL, NULL, NULL, 'face_mismatch', NULL, 34.6, 'rejected', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:17:07', '2026-06-17 12:17:21'),
(79, 3, 75, 'present', NULL, NULL, NULL, NULL, NULL, 100, 'first_time', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:29:09', '2026-06-17 12:29:09'),
(80, 3, 76, 'absent', NULL, NULL, NULL, 'face_mismatch', NULL, 0, 'rejected', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:29:32', '2026-06-17 12:29:32'),
(81, 3, 77, 'present', NULL, NULL, NULL, NULL, NULL, 90.9, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:29:51', '2026-06-17 12:29:51'),
(82, 3, 78, 'present', NULL, NULL, NULL, NULL, NULL, 89.7, 'verified', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:36:57', '2026-06-17 12:36:57'),
(83, 3, 81, 'present', NULL, NULL, NULL, NULL, NULL, 66.6, 'suspicious', '2026-06-17', NULL, NULL, 'none', '2026-06-17 12:45:52', '2026-06-17 12:45:52'),
(84, 3, 84, 'present', NULL, NULL, NULL, NULL, NULL, 57.9, 'suspicious', '2026-06-19', NULL, NULL, 'none', '2026-06-19 10:03:35', '2026-06-19 10:03:35'),
(85, 3, 85, 'absent', NULL, NULL, NULL, 'face_mismatch', NULL, 36.3, 'rejected', '2026-06-19', NULL, NULL, 'none', '2026-06-19 10:05:20', '2026-06-19 10:05:42'),
(132, 28, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(133, 29, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(134, 31, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(135, 24, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(136, 25, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(137, 23, 104, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 12:50:16', '2026-07-05 12:50:16'),
(138, 29, 114, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:13:37', '2026-07-05 17:13:37'),
(139, 31, 114, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:13:37', '2026-07-05 17:13:37'),
(140, 29, 115, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:38:09', '2026-07-05 17:38:09'),
(141, 31, 115, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:38:09', '2026-07-05 17:38:09'),
(142, 24, 115, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:38:09', '2026-07-05 17:38:09'),
(143, 23, 115, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-05', NULL, NULL, 'none', '2026-07-05 17:38:09', '2026-07-05 17:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `qr_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `closed_at` timestamp NULL DEFAULT NULL COMMENT 'وقت إغلاق الجلسة من المعلم',
  `latitude` decimal(10,7) DEFAULT NULL COMMENT 'خط عرض موقع المعلم عند فتح الجلسة',
  `longitude` decimal(10,7) DEFAULT NULL COMMENT 'خط طول موقع المعلم عند فتح الجلسة',
  `radius_meters` smallint(5) UNSIGNED NOT NULL DEFAULT 50 COMMENT 'الحد الأقصى للمسافة المسموح بها بالمتر (افتراضي 50م)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `lesson_id`, `qr_token`, `expires_at`, `session_expires_at`, `is_active`, `closed_at`, `latitude`, `longitude`, `radius_meters`, `created_at`, `updated_at`) VALUES
(19, 28, 'on0PKdB5Yg4wNwUEcWHQROFt3mLhY2l2', '2026-05-23 18:12:02', NULL, 0, NULL, NULL, NULL, 50, '2026-05-23 15:11:30', '2026-05-23 15:12:02'),
(25, 37, 'yIddrkWIgtlEIPpmIBuvXYmVsu7Fgx4P', '2026-05-25 06:17:34', NULL, 1, NULL, NULL, NULL, 50, '2026-05-25 05:17:34', '2026-05-25 05:17:34'),
(30, 47, 'x9YN7bYWSw2xhU7kJw1AAWYdREUJqH4T', '2026-05-30 12:01:46', NULL, 0, NULL, NULL, NULL, 50, '2026-05-30 09:01:41', '2026-05-30 09:01:46'),
(40, 60, 'f8198bf2c2a852e0eaca44fcea812a6b', '2026-06-10 04:16:22', NULL, 0, '2026-06-10 04:16:22', NULL, NULL, 50, '2026-06-10 04:15:52', '2026-06-10 04:16:22'),
(45, 64, 'nKDjyLJ0IUnx4Vz3vmTPpt91SoPsm7Cy', '2026-06-17 13:51:43', '2026-06-17 10:57:46', 0, NULL, NULL, NULL, 50, '2026-06-17 10:47:46', '2026-06-17 10:51:43'),
(46, 65, 'JgY3u3LRQOsCkp9qMztI8eEp5F0EXRo4', '2026-06-17 13:52:06', '2026-06-17 11:01:47', 0, NULL, NULL, NULL, 50, '2026-06-17 10:51:47', '2026-06-17 10:52:06'),
(47, 66, 'pTY776qCZLsTrVQp8ZZeq4ukrQ8qMBp9', '2026-06-17 13:52:34', '2026-06-17 11:02:10', 0, NULL, NULL, NULL, 50, '2026-06-17 10:52:10', '2026-06-17 10:52:34'),
(48, 67, 'SxXqxIYHSxUUpOhYJtrkImfATg3ALQ7j', '2026-06-17 10:59:39', '2026-06-17 11:02:37', 1, NULL, NULL, NULL, 50, '2026-06-17 10:52:37', '2026-06-17 10:59:09'),
(49, 68, 'm34SymqRZZ7sEYianPwCggGQyTRXrAfT', '2026-06-17 11:09:01', '2026-06-17 11:09:59', 1, NULL, NULL, NULL, 50, '2026-06-17 10:59:59', '2026-06-17 11:08:31'),
(50, 69, 'juTbglkMYb5q6cVCrsFkJVioW9w3YtcK', '2026-06-17 14:13:20', '2026-06-17 11:22:56', 0, NULL, NULL, NULL, 50, '2026-06-17 11:12:56', '2026-06-17 11:13:20'),
(52, 71, 'hppwkPMn1pPTZQ6CteHUBKKPet4e1dh0', '2026-06-17 11:20:33', '2026-06-17 11:24:01', 1, NULL, NULL, NULL, 50, '2026-06-17 11:14:01', '2026-06-17 11:20:03'),
(53, 72, 'gNV2ebL7Rv3EvYCP8OTyuzrdXupFuFuH', '2026-06-17 14:43:03', '2026-06-17 11:52:30', 0, NULL, NULL, NULL, 50, '2026-06-17 11:42:30', '2026-06-17 11:43:03'),
(54, 73, 'Pinobfqs2ZmVMA4vKcsEuDw7Lu8AhdhH', '2026-06-17 14:48:07', '2026-06-17 11:53:11', 0, NULL, NULL, NULL, 50, '2026-06-17 11:43:11', '2026-06-17 11:48:07'),
(55, 74, '6ZNlc1L6C5YLGVscHBiVxQTNCbxrCigq', '2026-06-17 12:26:02', '2026-06-17 12:27:01', 1, NULL, NULL, NULL, 50, '2026-06-17 12:17:01', '2026-06-17 12:25:32'),
(56, 75, 'euZVMFSPkFIXTTVTT4AGOabmQZ09VeWj', '2026-06-17 15:29:16', '2026-06-17 12:39:00', 0, NULL, NULL, NULL, 50, '2026-06-17 12:29:00', '2026-06-17 12:29:16'),
(57, 76, '8ww25q3LCRNP3vNcLXLRR7RR7Z0sD5Lr', '2026-06-17 15:29:40', '2026-06-17 12:39:26', 0, NULL, NULL, NULL, 50, '2026-06-17 12:29:26', '2026-06-17 12:29:40'),
(58, 77, '4rboRGpA1JM7I7eJ8ieS8hCWkpst8Lci', '2026-06-17 12:34:46', '2026-06-17 12:39:44', 1, NULL, NULL, NULL, 50, '2026-06-17 12:29:44', '2026-06-17 12:34:16'),
(59, 78, '0lUc8bA0QanbkVxrM2SkEZXLw0qLMRuc', '2026-06-17 15:37:08', '2026-06-17 12:46:48', 0, NULL, NULL, NULL, 50, '2026-06-17 12:36:48', '2026-06-17 12:37:08'),
(60, 79, '1aGTeiLhBFiymDRIPYEyr98m5nqyo67R', '2026-06-17 15:43:09', '2026-06-17 12:53:07', 0, NULL, NULL, NULL, 50, '2026-06-17 12:43:07', '2026-06-17 12:43:09'),
(61, 80, '2GDrOtSqCpEDmweYDAxS4agzjcEmYcYU', '2026-06-17 15:44:51', '2026-06-17 12:54:48', 0, NULL, NULL, NULL, 50, '2026-06-17 12:44:48', '2026-06-17 12:44:51'),
(62, 81, 'jgE7EMHRgprPn34wIm3C40tMSnXju8jh', '2026-06-17 15:46:03', '2026-06-17 12:55:45', 0, NULL, NULL, NULL, 50, '2026-06-17 12:45:45', '2026-06-17 12:46:03'),
(63, 82, 'tkZTGXGs75s7JYUXYHEirywoSrx0eXOS', '2026-06-17 15:47:46', '2026-06-17 12:57:40', 0, NULL, NULL, NULL, 50, '2026-06-17 12:47:40', '2026-06-17 12:47:46'),
(64, 83, 'DLJLQ2Dhu8jnRweU1Bnh2JucDsWfhaw1', '2026-06-17 16:14:23', '2026-06-17 13:24:21', 0, NULL, NULL, NULL, 50, '2026-06-17 13:14:21', '2026-06-17 13:14:23'),
(65, 84, 'AgIaOOJLYIRR83nwVhQQWAl6XTq0X2XR', '2026-06-19 13:03:54', '2026-06-19 10:13:24', 0, NULL, NULL, NULL, 50, '2026-06-19 10:03:24', '2026-06-19 10:03:54'),
(66, 85, 'FMXqvAWtJrFVwqHUAGi1i3O9707a6lbP', '2026-06-19 13:14:04', '2026-06-19 10:14:00', 0, NULL, NULL, NULL, 50, '2026-06-19 10:04:00', '2026-06-19 10:14:04'),
(81, 104, '4Pv5CJhEyklFiBHENGDUbGRjLTByTd0u', '2026-07-05 14:50:16', '2026-07-05 13:00:06', 0, NULL, NULL, NULL, 50, '2026-07-05 12:50:06', '2026-07-05 12:50:16'),
(82, 114, '5BxPOvgUVzYqE0ICsDZkp3IjWwi35QjP', '2026-07-05 19:13:37', '2026-07-05 17:23:33', 0, NULL, NULL, NULL, 50, '2026-07-05 17:13:33', '2026-07-05 17:13:37'),
(83, 115, 'vEWrmKPIcIPf95vGOtjxf2H4RGIIIMVm', '2026-07-05 19:38:09', '2026-07-05 17:46:40', 0, NULL, NULL, NULL, 50, '2026-07-05 17:36:40', '2026-07-05 17:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `user_id`, `title`, `event_date`, `event_time`, `location`, `created_at`, `updated_at`) VALUES
(1, 28, 'نشاط', '2026-05-30', '09:40:00', 'ساحة المعهد', '2026-05-29 10:45:38', '2026-05-29 10:45:38'),
(2, 28, 'حدث راح', '2026-05-28', '00:00:00', 'jh', '2026-05-29 10:46:18', '2026-05-29 10:46:18');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `chat_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `level` varchar(255) NOT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `year` tinyint(4) DEFAULT 1,
  `semester_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `title`, `description`, `level`, `hours`, `year`, `semester_id`, `created_at`, `updated_at`) VALUES
(1, 'C#', 'اساسيات C#', 'متوسط', 40, 1, 1, '2026-05-30 21:57:29', '2026-05-30 21:57:29'),
(2, 'Fluteer', NULL, 'متوسط', 40, 2, 2, '2026-05-30 22:00:40', '2026-05-30 22:00:40'),
(3, 'شبكات CCNA(IT)', 'قواعد بيانات', 'متوسط', 40, 1, 2, '2026-05-30 22:02:57', '2026-06-09 15:20:31'),
(4, 'تحليل نظم', 'MSProject', 'متوسط', 40, 2, 1, '2026-05-30 22:03:41', '2026-05-30 22:03:41'),
(5, 'laravel', 'تطوير مواقع ويب', 'متقدم', 40, 2, 1, '2026-05-30 22:05:17', '2026-05-30 22:05:17'),
(6, 'خوارزميات', NULL, 'متوسط', 40, 1, 1, '2026-05-30 22:07:35', '2026-05-30 22:07:35'),
(7, 'ASP', 'لغة Asp', 'متوسط', 40, 2, 2, '2026-05-30 22:10:01', '2026-05-30 22:10:01'),
(8, 'مواقع ويب', 'HTML , CSS , JavaScript', 'مبتدئ', 40, 1, 2, '2026-05-30 22:11:21', '2026-05-30 22:11:21'),
(9, 'اسس كهرباء 1', '.', 'متوسط', 40, 1, 1, '2026-05-30 22:15:02', '2026-06-09 15:22:45'),
(10, 'دارات منطقية', '..', 'متقدم', 40, 1, 2, '2026-05-30 22:15:35', '2026-06-09 15:23:26'),
(11, 'ادارة الشبكات', '...', 'متقدم', 40, 2, 1, '2026-05-30 22:16:01', '2026-06-09 15:30:41'),
(12, 'هاتف ارضي ومقاسم', '....', 'متقدم', 40, 2, 2, '2026-05-30 22:16:27', '2026-06-09 15:25:06'),
(13, 'انظمة مراقبة 1', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 22:17:09', '2026-06-09 15:22:53'),
(14, 'برمجة دارات', '..', 'متوسط', 40, 1, 2, '2026-05-30 22:17:33', '2026-06-09 15:23:37'),
(15, 'انظمة مراقبة 2', '...', 'متوسط', 40, 2, 1, '2026-05-30 22:17:56', '2026-06-09 15:29:26'),
(16, 'شبكات CCNA(COM)', '....', 'متقدم', 40, 2, 2, '2026-05-30 22:18:26', '2026-06-09 15:27:30'),
(17, 'مبادئ ادارة 1', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 22:39:22', '2026-05-30 22:39:22'),
(18, 'مبادئ ادارة 2', '..', 'مبتدئ', 40, 1, 2, '2026-05-30 22:39:48', '2026-05-30 22:39:48'),
(19, 'مبادئ ادارة 3', '...', 'متوسط', 40, 2, 2, '2026-05-30 22:40:16', '2026-05-30 22:40:16'),
(20, 'مبادئ ادارة 3(1)', '..', 'متوسط', 40, 2, 1, '2026-05-30 22:41:09', '2026-05-30 22:41:09'),
(21, 'محاسبة 1', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 22:42:14', '2026-05-30 22:42:14'),
(22, 'محاسبة 2', '..', 'متوسط', 40, 1, 2, '2026-05-30 22:42:47', '2026-05-30 22:42:47'),
(23, 'محاسبة 3', '...', 'متقدم', 40, 2, 1, '2026-05-30 22:43:23', '2026-05-30 22:43:23'),
(24, 'محاسبة 4', '...', 'متقدم', 40, 2, 2, '2026-05-30 22:43:43', '2026-05-30 22:43:43'),
(25, 'ادوية1', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 22:44:05', '2026-05-30 22:44:05'),
(26, 'ادوية 2', '..', 'متوسط', 40, 1, 2, '2026-05-30 22:44:28', '2026-05-30 22:44:28'),
(27, 'ادوية 3', '...', 'متوسط', 40, 2, 1, '2026-05-30 22:44:47', '2026-05-30 22:44:47'),
(28, 'ادوية 4', '....', 'متقدم', 40, 2, 2, '2026-05-30 22:45:16', '2026-05-30 22:45:16'),
(29, 'تحاليل1', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 22:46:27', '2026-05-30 22:46:27'),
(30, 'تحاليل 2', '...', 'متوسط', 40, 1, 2, '2026-05-30 22:47:03', '2026-05-30 22:47:03'),
(31, 'تحاليل 3', '...', 'متوسط', 40, 2, 1, '2026-05-30 22:47:24', '2026-05-30 22:47:24'),
(32, 'تحاليل 4', '....', 'متقدم', 40, 2, 2, '2026-05-30 22:47:42', '2026-05-30 22:47:42'),
(33, 'التصميم1', '..', 'مبتدئ', 40, 1, 1, '2026-05-30 23:18:44', '2026-05-30 23:18:44'),
(34, 'تصميم2', '..', 'متوسط', 40, 1, 2, '2026-05-30 23:20:04', '2026-05-30 23:20:04'),
(35, 'تصميم 3', '...', 'متوسط', 40, 2, 1, '2026-05-30 23:20:32', '2026-05-30 23:20:32'),
(36, 'تصميم 4', '....', 'متقدم', 40, 2, 2, '2026-05-30 23:21:00', '2026-05-30 23:21:00'),
(37, 'الاضاءة  والالوان', '.', 'مبتدئ', 40, 1, 1, '2026-05-30 23:23:15', '2026-05-30 23:23:15'),
(38, 'مواد الرسم', '..', 'متوسط', 40, 1, 2, '2026-05-30 23:23:43', '2026-05-30 23:23:43'),
(39, 'الاضاءة والالوان 2', '..', 'متقدم', 40, 2, 1, '2026-05-30 23:24:18', '2026-05-30 23:24:18'),
(40, 'مواد الرسم2', '..', 'متقدم', 40, 2, 2, '2026-05-30 23:24:40', '2026-05-30 23:24:40'),
(42, 'python1', NULL, 'مبتدئ', 40, 1, 1, '2026-06-09 14:54:37', '2026-06-09 14:58:37'),
(43, 'شبكات CCNA(AI)', NULL, 'متوسط', 40, 1, 2, '2026-06-09 14:55:24', '2026-06-09 15:11:07'),
(44, 'فيزياء', NULL, 'متوسط', 40, 1, 2, '2026-06-09 14:55:49', '2026-06-09 15:11:24'),
(45, 'python2', NULL, 'متقدم', 40, 2, 1, '2026-06-09 14:56:14', '2026-06-09 15:12:02'),
(46, 'robotic2', NULL, 'متقدم', 40, 2, 1, '2026-06-09 14:56:37', '2026-06-09 15:12:07'),
(47, 'تصميم العاب', NULL, 'متقدم', 40, 2, 2, '2026-06-09 14:57:02', '2026-06-09 15:12:53'),
(48, 'Flutter(AI)', NULL, 'متقدم', 40, 2, 2, '2026-06-09 14:57:19', '2026-06-09 15:15:04'),
(49, 'robotic1', NULL, 'مبتدئ', 40, 1, 1, '2026-06-09 14:58:58', '2026-06-09 14:58:58'),
(50, 'c++1', NULL, 'مبتدئ', 40, 1, 1, '2026-06-09 14:59:57', '2026-06-09 15:00:14'),
(51, 'c++2', NULL, 'متوسط', 40, 1, 2, '2026-06-09 15:00:41', '2026-06-09 15:00:41'),
(52, 'دارات رقمية 1', NULL, 'متوسط', 40, 2, 1, '2026-06-09 15:01:17', '2026-06-09 15:01:31'),
(53, 'دارات رقمية 2', NULL, 'متقدم', 40, 2, 2, '2026-06-09 15:01:50', '2026-06-09 15:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `course_program`
--

CREATE TABLE `course_program` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_program`
--

INSERT INTO `course_program` (`id`, `course_id`, `program_id`, `created_at`, `updated_at`) VALUES
(5, 1, 15, NULL, NULL),
(6, 2, 15, NULL, NULL),
(16, 12, 2, NULL, '2026-06-09 15:25:06'),
(17, 13, 2, NULL, '2026-06-09 15:22:53'),
(18, 14, 2, NULL, '2026-06-09 15:23:37'),
(19, 15, 2, NULL, '2026-06-09 15:29:26'),
(20, 1, 1, '2026-05-30 21:57:29', '2026-05-30 21:57:29'),
(21, 2, 1, '2026-05-30 22:00:40', '2026-05-30 22:00:40'),
(22, 3, 1, '2026-05-30 22:02:57', '2026-06-09 15:20:31'),
(23, 4, 1, '2026-05-30 22:03:41', '2026-05-30 22:03:41'),
(24, 5, 1, '2026-05-30 22:05:17', '2026-05-30 22:05:17'),
(25, 6, 1, '2026-05-30 22:07:35', '2026-05-30 22:07:35'),
(26, 7, 1, '2026-05-30 22:10:01', '2026-05-30 22:10:01'),
(27, 8, 1, '2026-05-30 22:11:21', '2026-05-30 22:11:21'),
(28, 9, 2, '2026-05-30 22:15:02', '2026-06-09 15:22:45'),
(29, 10, 2, '2026-05-30 22:15:35', '2026-06-09 15:23:26'),
(30, 11, 2, '2026-05-30 22:16:01', '2026-06-09 15:30:41'),
(31, 12, 2, '2026-05-30 22:16:28', '2026-06-09 15:25:06'),
(32, 13, 2, '2026-05-30 22:17:09', '2026-06-09 15:22:53'),
(33, 14, 2, '2026-05-30 22:17:33', '2026-06-09 15:23:37'),
(34, 15, 2, '2026-05-30 22:17:56', '2026-06-09 15:29:26'),
(35, 16, 2, '2026-05-30 22:18:27', '2026-06-09 15:27:30'),
(36, 17, 5, '2026-05-30 22:39:22', '2026-05-30 22:39:22'),
(37, 18, 5, '2026-05-30 22:39:48', '2026-05-30 22:39:48'),
(38, 19, 5, '2026-05-30 22:40:16', '2026-05-30 22:40:16'),
(39, 20, 5, '2026-05-30 22:41:09', '2026-05-30 22:41:09'),
(40, 21, 6, '2026-05-30 22:42:14', '2026-05-30 22:42:14'),
(41, 22, 6, '2026-05-30 22:42:47', '2026-05-30 22:42:47'),
(42, 23, 6, '2026-05-30 22:43:24', '2026-05-30 22:43:24'),
(43, 24, 6, '2026-05-30 22:43:43', '2026-05-30 22:43:43'),
(44, 25, 3, '2026-05-30 22:44:05', '2026-05-30 22:44:05'),
(45, 26, 3, '2026-05-30 22:44:28', '2026-05-30 22:44:28'),
(46, 27, 3, '2026-05-30 22:44:47', '2026-05-30 22:44:47'),
(47, 28, 3, '2026-05-30 22:45:16', '2026-05-30 22:45:16'),
(48, 29, 4, '2026-05-30 22:46:27', '2026-05-30 22:46:27'),
(49, 30, 4, '2026-05-30 22:47:03', '2026-05-30 22:47:03'),
(50, 31, 4, '2026-05-30 22:47:24', '2026-05-30 22:47:24'),
(51, 32, 4, '2026-05-30 22:47:42', '2026-05-30 22:47:42'),
(52, 33, 7, '2026-05-30 23:18:44', '2026-05-30 23:18:44'),
(53, 34, 7, '2026-05-30 23:20:04', '2026-05-30 23:20:04'),
(54, 35, 7, '2026-05-30 23:20:32', '2026-05-30 23:20:32'),
(55, 36, 7, '2026-05-30 23:21:00', '2026-05-30 23:21:00'),
(56, 37, 8, '2026-05-30 23:23:15', '2026-05-30 23:23:15'),
(57, 38, 8, '2026-05-30 23:23:43', '2026-05-30 23:23:43'),
(58, 39, 8, '2026-05-30 23:24:18', '2026-05-30 23:24:18'),
(59, 40, 8, '2026-05-30 23:24:40', '2026-05-30 23:24:40'),
(61, 42, 9, '2026-06-09 14:54:37', '2026-06-09 14:58:37'),
(62, 43, 9, '2026-06-09 14:55:24', '2026-06-09 15:11:07'),
(63, 44, 9, '2026-06-09 14:55:49', '2026-06-09 15:11:24'),
(64, 45, 9, '2026-06-09 14:56:14', '2026-06-09 15:12:02'),
(65, 46, 9, '2026-06-09 14:56:37', '2026-06-09 15:12:07'),
(66, 47, 9, '2026-06-09 14:57:02', '2026-06-09 15:12:53'),
(67, 48, 9, '2026-06-09 14:57:19', '2026-06-09 15:15:04'),
(68, 49, 9, '2026-06-09 14:58:58', '2026-06-09 14:58:58'),
(69, 50, 10, '2026-06-09 14:59:57', '2026-06-09 15:00:14'),
(70, 51, 10, '2026-06-09 15:00:41', '2026-06-09 15:00:41'),
(71, 52, 10, '2026-06-09 15:01:17', '2026-06-09 15:01:31'),
(72, 53, 10, '2026-06-09 15:01:50', '2026-06-09 15:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `course_teachers`
--

CREATE TABLE `course_teachers` (
  `course_teacher_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_teachers`
--

INSERT INTO `course_teachers` (`course_teacher_id`, `course_id`, `teacher_id`, `role`, `created_at`, `updated_at`) VALUES
(1, 25, 1, NULL, '2026-06-09 13:18:25', '2026-06-09 13:18:25'),
(2, 27, 1, NULL, '2026-06-09 13:18:25', '2026-06-09 13:18:25'),
(3, 26, 2, NULL, '2026-06-09 13:19:19', '2026-06-09 13:19:19'),
(4, 28, 2, NULL, '2026-06-09 13:19:19', '2026-06-09 13:19:19'),
(5, 29, 3, NULL, '2026-06-09 14:16:09', '2026-06-09 14:16:09'),
(6, 30, 3, NULL, '2026-06-09 14:16:09', '2026-06-09 14:16:09'),
(7, 31, 4, NULL, '2026-06-09 14:16:49', '2026-06-09 14:16:49'),
(8, 32, 4, NULL, '2026-06-09 14:16:49', '2026-06-09 14:16:49'),
(21, 1, 5, NULL, NULL, NULL),
(22, 6, 5, NULL, NULL, NULL),
(23, 47, 5, NULL, NULL, NULL),
(24, 48, 7, NULL, '2026-06-09 15:21:43', '2026-06-09 15:21:43'),
(25, 2, 7, NULL, '2026-06-09 15:21:43', '2026-06-09 15:21:43'),
(26, 5, 7, NULL, '2026-06-09 15:21:43', '2026-06-09 15:21:43'),
(27, 8, 7, NULL, '2026-06-09 15:21:43', '2026-06-09 15:21:43'),
(28, 12, 8, NULL, '2026-06-09 15:29:51', '2026-06-09 15:29:51'),
(29, 13, 8, NULL, '2026-06-09 15:29:51', '2026-06-09 15:29:51'),
(30, 15, 8, NULL, '2026-06-09 15:29:51', '2026-06-09 15:29:51'),
(31, 16, 8, NULL, '2026-06-09 15:29:51', '2026-06-09 15:29:51'),
(32, 3, 6, NULL, NULL, NULL),
(33, 11, 6, NULL, NULL, NULL),
(34, 43, 6, NULL, NULL, NULL),
(35, 42, 9, NULL, '2026-06-09 15:32:53', '2026-06-09 15:32:53'),
(36, 44, 9, NULL, '2026-06-09 15:32:53', '2026-06-09 15:32:53'),
(37, 46, 9, NULL, '2026-06-09 15:32:53', '2026-06-09 15:32:53'),
(38, 49, 9, NULL, '2026-06-09 15:32:53', '2026-06-09 15:32:53'),
(39, 50, 10, NULL, '2026-06-09 15:34:16', '2026-06-09 15:34:16'),
(40, 51, 10, NULL, '2026-06-09 15:34:16', '2026-06-09 15:34:16'),
(41, 52, 10, NULL, '2026-06-09 15:34:16', '2026-06-09 15:34:16'),
(42, 53, 10, NULL, '2026-06-09 15:34:16', '2026-06-09 15:34:16'),
(43, 45, 11, NULL, '2026-06-09 15:35:13', '2026-06-09 15:35:13'),
(44, 4, 11, NULL, '2026-06-09 15:35:13', '2026-06-09 15:35:13'),
(45, 7, 11, NULL, '2026-06-09 15:35:13', '2026-06-09 15:35:13'),
(46, 14, 12, NULL, '2026-06-09 15:35:46', '2026-06-09 15:35:46'),
(47, 9, 12, NULL, '2026-06-09 15:35:46', '2026-06-09 15:35:46'),
(48, 10, 12, NULL, '2026-06-09 15:35:46', '2026-06-09 15:35:46'),
(49, 37, 13, NULL, '2026-06-09 15:56:38', '2026-06-09 15:56:38'),
(50, 38, 13, NULL, '2026-06-09 15:56:38', '2026-06-09 15:56:38'),
(51, 39, 14, NULL, '2026-06-09 15:57:21', '2026-06-09 15:57:21'),
(52, 40, 14, NULL, '2026-06-09 15:57:21', '2026-06-09 15:57:21'),
(53, 33, 15, NULL, '2026-06-09 15:57:57', '2026-06-09 15:57:57'),
(54, 34, 15, NULL, '2026-06-09 15:57:57', '2026-06-09 15:57:57'),
(55, 35, 16, NULL, '2026-06-09 15:58:32', '2026-06-09 15:58:32'),
(56, 36, 16, NULL, '2026-06-09 15:58:32', '2026-06-09 15:58:32'),
(57, 17, 17, NULL, '2026-06-09 16:03:23', '2026-06-09 16:03:23'),
(58, 18, 17, NULL, '2026-06-09 16:03:23', '2026-06-09 16:03:23'),
(59, 19, 18, NULL, '2026-06-09 16:04:10', '2026-06-09 16:04:10'),
(60, 20, 18, NULL, '2026-06-09 16:04:10', '2026-06-09 16:04:10'),
(61, 21, 19, NULL, '2026-06-09 16:05:04', '2026-06-09 16:05:04'),
(62, 22, 19, NULL, '2026-06-09 16:05:04', '2026-06-09 16:05:04'),
(63, 23, 20, NULL, '2026-06-09 16:05:43', '2026-06-09 16:05:43'),
(64, 24, 20, NULL, '2026-06-09 16:05:43', '2026-06-09 16:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `offline_sync_policy` varchar(255) NOT NULL DEFAULT 'anytime',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `description`, `offline_sync_policy`, `created_at`, `updated_at`) VALUES
(1, 'نظم معلومات', 'قسم نظم المعلومات والحاسوب', 'anytime', '2026-05-30 21:37:25', '2026-05-30 21:37:25'),
(2, 'تجاري', 'قسم العلوم التجارية والإدارية', 'anytime', '2026-05-30 21:37:25', '2026-05-30 21:37:25'),
(3, 'طبي', 'قسم العلوم الطبية والصحية', 'anytime', '2026-05-30 21:37:25', '2026-05-30 21:37:25'),
(4, 'هندسي', 'قسم الهندسة والتصميم', 'anytime', '2026-05-30 21:37:25', '2026-05-30 21:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `enrollment_date`, `status`, `created_at`, `updated_at`) VALUES
(12, 28, 1, '2026-05-31', 'active', '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(13, 28, 25, '2026-05-31', 'active', '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(14, 28, 26, '2026-05-31', 'active', '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(15, 28, 27, '2026-05-31', 'active', '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(16, 28, 28, '2026-05-31', 'active', '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(17, 29, 1, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(18, 29, 2, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(19, 29, 3, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(20, 29, 4, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(21, 29, 5, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(22, 29, 6, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(23, 29, 7, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(24, 29, 8, '2026-05-31', 'active', '2026-05-31 06:18:08', '2026-05-31 06:18:08'),
(29, 31, 1, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(30, 31, 2, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(31, 31, 3, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(32, 31, 4, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(33, 31, 5, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(34, 31, 6, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(35, 31, 7, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(36, 31, 8, '2026-06-07', 'active', '2026-06-07 21:04:46', '2026-06-07 21:04:46'),
(37, 32, 17, '2026-06-07', 'active', '2026-06-07 21:35:46', '2026-06-07 21:35:46'),
(38, 32, 18, '2026-06-07', 'active', '2026-06-07 21:35:46', '2026-06-07 21:35:46'),
(39, 32, 19, '2026-06-07', 'active', '2026-06-07 21:35:46', '2026-06-07 21:35:46'),
(40, 32, 20, '2026-06-07', 'active', '2026-06-07 21:35:46', '2026-06-07 21:35:46'),
(43, 10, 42, '2026-06-09', 'active', '2026-06-09 14:54:37', '2026-06-09 14:54:37'),
(44, 14, 42, '2026-06-09', 'active', '2026-06-09 14:54:37', '2026-06-09 14:54:37'),
(45, 10, 43, '2026-06-09', 'active', '2026-06-09 14:55:24', '2026-06-09 14:55:24'),
(46, 14, 43, '2026-06-09', 'active', '2026-06-09 14:55:24', '2026-06-09 14:55:24'),
(47, 10, 44, '2026-06-09', 'active', '2026-06-09 14:55:49', '2026-06-09 14:55:49'),
(48, 14, 44, '2026-06-09', 'active', '2026-06-09 14:55:49', '2026-06-09 14:55:49'),
(49, 10, 45, '2026-06-09', 'active', '2026-06-09 14:56:14', '2026-06-09 14:56:14'),
(50, 10, 46, '2026-06-09', 'active', '2026-06-09 14:56:37', '2026-06-09 14:56:37'),
(51, 10, 47, '2026-06-09', 'active', '2026-06-09 14:57:02', '2026-06-09 14:57:02'),
(52, 10, 48, '2026-06-09', 'active', '2026-06-09 14:57:19', '2026-06-09 14:57:19'),
(53, 10, 49, '2026-06-09', 'active', '2026-06-09 14:58:58', '2026-06-09 14:58:58'),
(54, 14, 49, '2026-06-09', 'active', '2026-06-09 14:58:58', '2026-06-09 14:58:58'),
(55, 9, 50, '2026-06-09', 'active', '2026-06-09 14:59:57', '2026-06-09 14:59:57'),
(56, 13, 50, '2026-06-09', 'active', '2026-06-09 14:59:57', '2026-06-09 14:59:57'),
(57, 9, 51, '2026-06-09', 'active', '2026-06-09 15:00:41', '2026-06-09 15:00:41'),
(58, 13, 51, '2026-06-09', 'active', '2026-06-09 15:00:41', '2026-06-09 15:00:41'),
(59, 9, 52, '2026-06-09', 'active', '2026-06-09 15:01:17', '2026-06-09 15:01:17'),
(60, 9, 53, '2026-06-09', 'active', '2026-06-09 15:01:50', '2026-06-09 15:01:50'),
(61, 24, 1, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(62, 24, 6, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(63, 25, 1, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(64, 26, 47, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(65, 27, 47, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(66, 28, 47, '2026-06-19', 'active', '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(67, 23, 1, '2026-06-19', 'active', '2026-06-19 10:24:39', '2026-06-19 10:24:39'),
(68, 23, 6, '2026-06-19', 'active', '2026-06-19 10:24:39', '2026-06-19 10:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `exam_date` datetime NOT NULL,
  `room` varchar(255) DEFAULT NULL,
  `class_group` varchar(255) DEFAULT NULL,
  `max_score` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `course_id`, `exam_name`, `exam_date`, `room`, `class_group`, `max_score`, `created_at`, `updated_at`) VALUES
(1, 1, 'الامتحان النصفي', '2026-05-29 09:00:00', NULL, NULL, 40, '2026-05-18 22:11:17', '2026-05-18 22:11:17'),
(2, 1, 'الامتحان النهائي', '2026-06-13 10:00:00', NULL, NULL, 60, '2026-05-18 22:11:17', '2026-05-18 22:11:17'),
(3, 2, 'الامتحان النصفي', '2026-05-29 09:00:00', NULL, NULL, 40, '2026-05-18 22:11:17', '2026-05-18 22:11:17'),
(4, 2, 'الامتحان النهائي', '2026-06-13 10:00:00', NULL, NULL, 60, '2026-05-18 22:11:17', '2026-05-18 22:11:17'),
(5, 12, 'الامتحان النصفي - أساسيات الشبكات', '2026-06-05 09:00:00', 'قاعة D', NULL, 40, '2026-05-25 03:19:36', '2026-05-25 03:19:36'),
(6, 12, 'الامتحان النهائي - أساسيات الشبكات', '2026-06-25 10:00:00', 'قاعة D', NULL, 60, '2026-05-25 03:19:36', '2026-05-25 03:19:36'),
(7, 1, 'الامتحان النهائي - أساسيات البرمجة', '2026-06-20 09:00:00', 'قاعة A', NULL, 60, '2026-05-25 03:19:36', '2026-05-25 03:19:36'),
(8, 2, 'الامتحان النهائي - قواعد البيانات', '2026-06-22 11:00:00', 'قاعة B', NULL, 60, '2026-05-25 03:19:36', '2026-05-25 03:19:36');

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
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_entries`
--

CREATE TABLE `grade_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grade_event_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_entries`
--

INSERT INTO `grade_entries` (`id`, `grade_event_id`, `student_id`, `score`, `notes`, `created_at`, `updated_at`) VALUES
(10, 6, 28, 75.00, NULL, '2026-06-19 10:29:51', '2026-06-19 10:30:22'),
(11, 6, 29, 40.00, NULL, '2026-06-19 10:29:51', '2026-06-19 10:30:22'),
(12, 6, 24, 50.00, NULL, '2026-06-19 10:29:51', '2026-06-19 10:30:22'),
(13, 6, 25, 70.00, NULL, '2026-06-19 10:29:51', '2026-06-19 10:30:22'),
(14, 6, 23, 20.00, NULL, '2026-06-19 10:29:51', '2026-06-19 10:30:22'),
(15, 7, 29, 0.00, NULL, '2026-06-19 10:33:57', '2026-06-19 10:39:49'),
(16, 7, 24, 0.00, NULL, '2026-06-19 10:33:57', '2026-06-19 10:39:49'),
(17, 7, 23, 0.00, NULL, '2026-06-19 10:33:57', '2026-06-19 10:39:49'),
(18, 8, 10, 60.00, NULL, '2026-06-19 10:45:20', '2026-06-19 10:45:41'),
(19, 8, 26, 20.00, NULL, '2026-06-19 10:45:20', '2026-06-19 10:45:41'),
(20, 8, 27, 50.00, NULL, '2026-06-19 10:45:20', '2026-06-19 10:45:41'),
(21, 8, 28, 80.00, NULL, '2026-06-19 10:45:20', '2026-06-19 10:45:41'),
(28, 10, 29, 24.00, NULL, '2026-06-24 22:17:03', '2026-06-24 22:17:41'),
(29, 10, 24, 15.00, NULL, '2026-06-24 22:17:03', '2026-06-24 22:17:40'),
(30, 10, 25, 11.00, NULL, '2026-06-24 22:17:03', '2026-06-24 22:17:41'),
(31, 10, 23, 25.00, NULL, '2026-06-24 22:17:03', '2026-06-24 22:17:40'),
(32, 11, 29, 10.00, NULL, '2026-06-26 16:42:03', '2026-06-26 16:42:17'),
(44, 15, 29, 25.00, NULL, '2026-06-26 18:30:44', '2026-06-26 18:31:39'),
(45, 15, 24, 50.00, NULL, '2026-06-26 18:30:44', '2026-06-26 18:31:39'),
(46, 15, 23, 20.00, NULL, '2026-06-26 18:30:44', '2026-06-26 18:31:39'),
(47, 16, 10, 25.00, NULL, '2026-06-26 18:47:15', '2026-06-26 18:48:59'),
(48, 16, 26, 10.00, NULL, '2026-06-26 18:47:15', '2026-06-26 18:48:59'),
(49, 16, 27, 25.00, NULL, '2026-06-26 18:47:15', '2026-06-26 18:48:59'),
(50, 16, 28, 25.00, NULL, '2026-06-26 18:47:15', '2026-06-26 18:48:59'),
(62, 20, 29, 50.00, NULL, '2026-07-05 17:11:00', '2026-07-05 17:11:24'),
(63, 20, 31, 15.00, NULL, '2026-07-05 17:11:00', '2026-07-05 17:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `grade_events`
--

CREATE TABLE `grade_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `program_id` bigint(20) UNSIGNED DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT NULL,
  `type` enum('exam','quiz','oral') NOT NULL,
  `title` varchar(255) NOT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `notes` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_events`
--

INSERT INTO `grade_events` (`id`, `teacher_id`, `course_id`, `program_id`, `year_level`, `type`, `title`, `max_score`, `notes`, `date`, `time`, `duration`, `created_at`, `updated_at`) VALUES
(6, 5, 1, NULL, NULL, 'quiz', 'مذاكرة', 100.00, NULL, '2026-06-19', NULL, NULL, '2026-06-19 10:29:51', '2026-06-19 10:29:51'),
(7, 5, 6, NULL, NULL, 'exam', '..', 100.00, NULL, '2026-06-19', NULL, NULL, '2026-06-19 10:33:57', '2026-06-19 10:33:57'),
(8, 5, 47, NULL, NULL, 'exam', 'زز', 100.00, NULL, '2026-06-19', NULL, NULL, '2026-06-19 10:45:20', '2026-06-19 10:45:20'),
(10, 5, NULL, 1, 1, 'oral', 'تقييم شفهي', 25.00, 'تقيمات', '2026-06-25', NULL, NULL, '2026-06-24 22:17:03', '2026-06-24 22:17:03'),
(11, 5, NULL, 1, 2, 'oral', '..', 25.00, '.l.l.l', '2026-06-26', NULL, NULL, '2026-06-26 16:42:03', '2026-06-26 16:42:03'),
(15, 5, 6, NULL, NULL, 'exam', 'امتحان', 100.00, 'زز', '2026-06-27', NULL, NULL, '2026-06-26 18:30:44', '2026-06-26 18:30:44'),
(16, 5, 47, NULL, NULL, 'quiz', 'مذاكرة', 25.00, 'زز', '2026-06-27', NULL, NULL, '2026-06-26 18:47:15', '2026-06-26 18:47:15'),
(20, 7, 5, NULL, NULL, 'exam', 'exam', 100.00, NULL, '2026-07-06', NULL, NULL, '2026-07-05 17:11:00', '2026-07-05 17:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `grade_report_requests`
--

CREATE TABLE `grade_report_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `boss_user_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_report_requests`
--

INSERT INTO `grade_report_requests` (`id`, `boss_user_id`, `teacher_user_id`, `course_id`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 73, 112, 1, 'completed', 'يرجى الاسراع', '2026-06-26 19:40:39', '2026-06-26 19:40:39'),
(2, 73, 112, 47, 'completed', 'اا', '2026-06-26 19:51:28', '2026-06-26 19:51:28'),
(3, 73, 112, 6, 'completed', NULL, '2026-06-27 19:16:46', '2026-06-27 19:16:46'),
(4, 73, 112, 1, 'completed', NULL, '2026-07-05 17:07:33', '2026-07-05 17:07:33'),
(5, 73, 114, 2, 'completed', NULL, '2026-07-05 17:07:56', '2026-07-05 17:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_user`
--

CREATE TABLE `group_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `heads`
--

CREATE TABLE `heads` (
  `head_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `heads`
--

INSERT INTO `heads` (`head_id`, `user_id`, `department_id`, `created_at`, `updated_at`) VALUES
(3, 60, 3, '2026-06-09 07:07:06', '2026-06-09 07:07:06'),
(4, 62, 2, '2026-06-09 07:37:36', '2026-06-09 07:37:36'),
(5, 63, 4, '2026-06-09 07:39:13', '2026-06-09 07:39:13'),
(6, 73, 1, '2026-06-09 08:58:34', '2026-06-09 08:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `head_schedule_entries`
--

CREATE TABLE `head_schedule_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `head_user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `day` varchar(50) NOT NULL DEFAULT '',
  `class_name` varchar(100) NOT NULL DEFAULT '',
  `content` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('full_day','hourly') NOT NULL,
  `leave_category` enum('hourly','daily') NOT NULL DEFAULT 'daily',
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('pending','pending_hod','pending_affairs','pending_parent','approved','rejected') NOT NULL DEFAULT 'pending_hod',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `student_id`, `teacher_id`, `type`, `leave_category`, `date`, `reason`, `attachment`, `status`, `created_at`, `updated_at`) VALUES
(25, 46, NULL, 'hourly', 'daily', '2026-05-31', 'مرض', NULL, 'approved', '2026-05-31 03:57:07', '2026-05-31 04:01:10'),
(26, 46, NULL, 'full_day', 'daily', '2026-05-31', 'موعد طبيب', NULL, 'pending_parent', '2026-05-31 04:05:08', '2026-05-31 04:05:08'),
(27, 51, NULL, 'full_day', 'daily', '2026-05-31', 'مرض', NULL, 'approved', '2026-05-31 06:31:13', '2026-05-31 06:52:02'),
(29, 147, NULL, 'full_day', 'daily', '2026-07-05', 'مرض', NULL, 'approved', '2026-07-05 12:48:29', '2026-07-05 12:49:26');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `content_url` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `file_size` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `course_id`, `teacher_id`, `title`, `description`, `file_path`, `file_name`, `file_type`, `content_url`, `type`, `file_size`, `duration`, `created_at`, `updated_at`, `department_id`) VALUES
(28, 12, 1, '..', NULL, NULL, NULL, NULL, 'lectures/1779654533_محاضرة 14.pdf', 'session', NULL, NULL, '2026-05-23 15:11:30', '2026-05-24 17:28:53', NULL),
(29, 14, 1, 'c#', 'jj', NULL, NULL, NULL, 'lectures/1779698085_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-24 05:51:28', '2026-05-25 05:34:45', NULL),
(31, 12, 1, 'tcyvybb', 'fxf f', NULL, NULL, NULL, 'lectures/1779620475_محاضرة 13.pdf', NULL, NULL, NULL, '2026-05-24 08:01:15', '2026-05-24 08:01:15', NULL),
(37, 14, 1, 'قواعد', NULL, NULL, NULL, NULL, 'lectures/1779698040_محاضرة 14.pdf', 'session', NULL, NULL, '2026-05-25 05:17:34', '2026-05-25 05:34:00', NULL),
(39, 12, 1, 'تجربة', 'تتتت', NULL, NULL, NULL, 'lectures/1780013951_محاضرة 13.pdf', NULL, NULL, NULL, '2026-05-25 06:10:57', '2026-05-28 21:19:11', NULL),
(42, 12, 1, 'ASP', 'محاضرة مهمة', NULL, NULL, NULL, 'lectures/1780073526_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-29 13:52:06', '2026-05-29 13:52:06', NULL),
(43, 12, 1, 'تا', 'في', NULL, NULL, NULL, 'lectures/1780074039_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-29 14:00:39', '2026-05-29 14:00:39', NULL),
(44, 13, 1, 'kk', 'hg', NULL, NULL, NULL, 'lectures/1780076372_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-29 14:39:32', '2026-05-29 14:39:32', NULL),
(45, 12, 1, 'نن', 'ال', NULL, NULL, NULL, 'lectures/1780090853_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-29 18:40:53', '2026-05-29 18:40:53', NULL),
(47, 12, 1, 'حصة 2026-05-30 12:01', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-05-30 09:01:41', '2026-05-30 09:01:41', NULL),
(49, 1, 4, 'c#', 'محاضرة', NULL, NULL, NULL, 'lectures/1780212123_cv_1.pdf', NULL, NULL, NULL, '2026-05-31 04:22:03', '2026-05-31 04:22:03', NULL),
(51, 1, 4, 'اساسيات', '..', NULL, NULL, NULL, 'lectures/1780213800_المحاضرة التاسعة.pdf', NULL, NULL, NULL, '2026-05-31 04:50:00', '2026-05-31 04:50:00', NULL),
(54, 1, 4, '..', '..', NULL, NULL, NULL, 'lectures/1780214374_S05-ASP.net C#-Entity Framework Core.pdf', NULL, NULL, NULL, '2026-05-31 04:59:34', '2026-05-31 04:59:34', NULL),
(60, 2, 7, 'جلسة حضور - 2026-06-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-10 04:15:52', '2026-06-10 04:15:52', NULL),
(64, 18, 17, 'حصة 2026-06-17 13:47', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 10:47:46', '2026-06-17 10:47:46', NULL),
(65, 18, 17, 'حصة 2026-06-17 13:51', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 10:51:47', '2026-06-17 10:51:47', NULL),
(66, 18, 17, 'حصة 2026-06-17 13:52', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 10:52:10', '2026-06-17 10:52:10', NULL),
(67, 18, 17, 'حصة 2026-06-17 13:52', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 10:52:37', '2026-06-17 10:52:37', NULL),
(68, 18, 17, 'حصة 2026-06-17 13:59', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 10:59:59', '2026-06-17 10:59:59', NULL),
(69, 19, 18, 'حصة 2026-06-17 14:12', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 11:12:56', '2026-06-17 11:12:56', NULL),
(71, 19, 18, 'حصة 2026-06-17 14:14', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 11:14:01', '2026-06-17 11:14:01', NULL),
(72, 19, 18, 'حصة 2026-06-17 14:42', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 11:42:30', '2026-06-17 11:42:30', NULL),
(73, 19, 18, 'حصة 2026-06-17 14:43', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 11:43:11', '2026-06-17 11:43:11', NULL),
(74, 19, 18, 'حصة 2026-06-17 15:17', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:17:01', '2026-06-17 12:17:01', NULL),
(75, 20, 18, 'حصة 2026-06-17 15:29', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:29:00', '2026-06-17 12:29:00', NULL),
(76, 20, 18, 'حصة 2026-06-17 15:29', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:29:26', '2026-06-17 12:29:26', NULL),
(77, 20, 18, 'حصة 2026-06-17 15:29', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:29:44', '2026-06-17 12:29:44', NULL),
(78, 19, 18, 'حصة 2026-06-17 15:36', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:36:48', '2026-06-17 12:36:48', NULL),
(79, 19, 18, 'حصة 2026-06-17 15:43', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:43:07', '2026-06-17 12:43:07', NULL),
(80, 19, 18, 'حصة 2026-06-17 15:44', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:44:48', '2026-06-17 12:44:48', NULL),
(81, 19, 18, 'حصة 2026-06-17 15:45', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:45:45', '2026-06-17 12:45:45', NULL),
(82, 19, 18, 'حصة 2026-06-17 15:47', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 12:47:40', '2026-06-17 12:47:40', NULL),
(83, 19, 18, 'حصة 2026-06-17 16:14', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-17 13:14:21', '2026-06-17 13:14:21', NULL),
(84, 19, 18, 'حصة 2026-06-19 13:03', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-19 10:03:24', '2026-06-19 10:03:24', NULL),
(85, 19, 18, 'حصة 2026-06-19 13:04', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-06-19 10:04:00', '2026-06-19 10:04:00', NULL),
(87, 11, 6, 'ccna1', '....', NULL, NULL, NULL, 'lectures/1782502235_محاضرة 1-1 (2).pdf', NULL, NULL, NULL, '2026-06-26 16:30:35', '2026-06-26 16:30:35', NULL),
(101, 6, 5, 'خوارزميات', 'خوارزميات', NULL, NULL, NULL, 'lectures/1783119838_grades_C#_مذاكرة.pdf', NULL, NULL, NULL, '2026-07-03 21:03:58', '2026-07-03 21:03:58', NULL),
(104, 1, 5, 'حصة 2026-07-05 14:50', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-07-05 12:50:06', '2026-07-05 12:50:06', NULL),
(105, 3, 6, 'اساسيات الشبكات', 'يرجى حضور الفيديو المرفق', NULL, NULL, NULL, 'https://youtu.be/H8W9oMNSuwo?si=AIZqv-UTPqFi-zk-', 'link', NULL, NULL, '2026-07-05 12:53:55', '2026-07-05 12:53:55', NULL),
(106, 3, 6, 'محاضرة 1', 'محاضرة مهمة', NULL, NULL, NULL, 'lectures/1783263310_attendance_report_2026-06-17_1781713164793.pdf', 'pdf', NULL, NULL, '2026-07-05 12:55:10', '2026-07-05 12:55:10', NULL),
(107, 8, 7, 'css', 'محاضرة 2', NULL, NULL, NULL, 'lectures/1783263571_electricity_receipt_REF1779294361468.pdf', 'pdf', NULL, NULL, '2026-07-05 12:59:31', '2026-07-05 12:59:31', NULL),
(108, 8, 7, 'html', 'محاضرة مهمة', NULL, NULL, NULL, 'https://youtu.be/916GWv2Qs08?si=FVeeLvo3ZEjfe87-', 'link', NULL, NULL, '2026-07-05 13:00:22', '2026-07-05 13:00:22', NULL),
(109, 2, 7, 'اساسيات flutter', 'dart', NULL, NULL, NULL, 'lectures/1783263692_topup_receipt_REF1780388901687.pdf', 'pdf', NULL, NULL, '2026-07-05 13:01:32', '2026-07-05 13:01:32', NULL),
(110, 5, 7, 'php', 'محاضرة', NULL, NULL, NULL, 'lectures/1783264340_internet_receipt_REF1779300231564.pdf', 'pdf', NULL, NULL, '2026-07-05 13:12:20', '2026-07-05 13:12:20', NULL),
(111, 4, 11, 'محاضرة1', 'مهم', NULL, NULL, NULL, 'lectures/1783264662_electricity_receipt_REF1779294361468.pdf', 'pdf', NULL, NULL, '2026-07-05 13:17:42', '2026-07-05 13:17:42', NULL),
(112, 7, 11, 'محاضرة 2', 'محاضرة اساسيات', NULL, NULL, NULL, 'lectures/1783264690_topup_receipt_REF1780388901687.pdf', 'pdf', NULL, NULL, '2026-07-05 13:18:10', '2026-07-05 13:18:10', NULL),
(113, 4, 11, 'ملخص تحليل نظم', 'كامل', NULL, NULL, NULL, 'lectures/1783264739_topup_receipt_REF1780388901687.pdf', 'pdf', NULL, NULL, '2026-07-05 13:18:59', '2026-07-05 13:18:59', NULL),
(114, 2, 7, 'حصة 2026-07-05 19:13', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-07-05 17:13:33', '2026-07-05 17:13:33', NULL),
(115, 6, 5, 'حصة 2026-07-05 19:36', NULL, NULL, NULL, NULL, NULL, 'session', NULL, NULL, '2026-07-05 17:36:40', '2026-07-05 17:36:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `group_id` bigint(20) UNSIGNED DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply_to_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
(4, '2026_03_26_144508_create_teachers_table', 1),
(5, '2026_03_26_144509_create_courses_table', 1),
(6, '2026_03_26_144511_create_courses_teachers_table', 1),
(7, '2026_03_26_144512_create_schedules_table', 1),
(8, '2026_03_26_144516_create_departments_tables', 1),
(9, '2026_03_26_144517_create_administrative_tables', 1),
(10, '2026_03_26_144519_create__student_table', 1),
(11, '2026_03_26_144520_create__enrollments_table', 1),
(12, '2026_03_26_160506_create__exams_table', 1),
(13, '2026_03_26_161020_create__grades_table', 1),
(14, '2026_03_26_162513_create_lessons_table', 1),
(15, '2026_03_26_162514_create_attendance_table', 1),
(16, '2026_03_26_162515_create_resources_table', 1),
(17, '2026_03_26_162837_create_announcements_table', 1),
(18, '2026_03_26_162849_create_assignments_table', 1),
(19, '2026_03_26_162902_create_assignment_submissions_table', 1),
(20, '2026_03_26_163452_create_reports_and_requests_tables', 1),
(21, '2026_03_26_163528_create_communication_tables', 1),
(22, '2026_03_26_170239_create_course_departments_pivot_table', 1),
(23, '2026_03_27_195456_create_personal_access_tokens_table', 1),
(24, '2026_03_27_203038_create_session_table', 1),
(25, '2026_03_28_105303_create_subjects_table', 1),
(26, '2026_03_28_195248_add_username_to_users_table', 1),
(27, '2026_04_03_225032_create_otps_table', 1),
(28, '2026_04_06_072056_create_notifications_table', 1),
(29, '2026_04_13_073654_create_messages_table', 1),
(30, '2026_04_16_074853_create_leave_requests_table', 1),
(31, '2026_04_16_083430_create_attendance_sessions_table', 1),
(32, '2026_04_18_105343_add_report_type_to_performance_reports_table', 1),
(33, '2026_04_23_072049_create_semesters_table', 1),
(34, '2026_04_23_072100_create_parent_students_table', 1),
(35, '2026_04_23_072109_create_roles_table', 1),
(36, '2026_04_23_072119_add_role_id_to_users_table', 1),
(37, '2026_04_23_072131_add_semester_id_to_courses_table', 1),
(38, '2026_04_23_072132_add_semester_id_to_enrollments_table', 1),
(39, '2026_04_23_072132_add_semester_id_to_exams_table', 1),
(40, '2026_04_23_072133_add_semester_id_to_assignments_table', 1),
(41, '2026_04_23_072135_add_semester_id_to_attendance_table', 1),
(42, '2026_04_26_182048_create_programs_table', 1),
(43, '2026_04_26_182053_create_course_program_table', 1),
(44, '2026_04_26_182059_update_courses_table_for_programs', 1),
(45, '2026_04_28_172404_cleanup_database_architecture', 1),
(46, '2026_05_01_151740_add_targeting_columns_to_announcements_table', 1),
(47, '2026_05_01_160007_add_avatar_to_users_table', 1),
(48, '2026_05_06_192227_add_category_and_image_to_announcements_table', 1),
(49, '2026_05_06_194210_add_report_type_to_performance_reports_table', 1),
(50, '2026_05_06_194251_2026_05_02_171000_update_hod_tables_for_linking', 1),
(51, '2026_05_06_194327_add_columns_to_schedules_and_exams', 1),
(52, '2026_05_07_000001_add_missing_columns_to_lessons_table', 1),
(53, '2026_05_07_102059_add_category_and_sender_to_notifications_table', 1),
(54, '2026_05_08_000001_add_attachment_to_assignments_table', 1),
(55, '2026_05_08_000002_create_otp_codes_table', 1),
(56, '2026_05_08_000003_add_missing_columns_to_users_table', 1),
(57, '2026_04_16_080032_add_excuse_fields_to_attendance_table', 2),
(58, '2026_05_02_171000_update_hod_tables_for_linking', 3),
(59, '2026_05_04_230000_add_columns_to_schedules_and_exams', 4),
(60, '2026_05_09_131236_update_announcements_table_add_audience', 4),
(61, '2026_05_09_150627_add_image_path_to_announcements_table', 4),
(62, '2026_05_16_000001_add_report_request_id_to_performance_reports', 5),
(63, '2026_05_16_000002_add_teacher_id_and_type_to_assignments_table', 5),
(64, '2026_05_16_000003_fix_parent_students_foreign_keys', 5),
(65, '2026_05_19_035741_create_head_schedule_entries_table', 6),
(66, '2026_05_19_073023_add_device_token_to_users_table', 7),
(67, '2026_05_21_000001_add_year_to_courses_table', 8),
(68, '2026_05_23_add_course_year_to_report_requests', 9),
(69, '2026_05_19_035837_create_head_schedule_entries_table', 9),
(70, '2026_05_21_105015_add_notes_to_assignment_submissions_table', 9),
(71, '2026_05_23_add_related_id_to_notifications_table', 10),
(72, '2026_05_24_211054_add_link_url_to_announcements_table', 11),
(73, '2026_04_21_053852_create_messages_table', 12),
(74, '2026_04_22_000001_add_teacher_and_department_to_lessons_table', 13),
(75, '2026_05_20_070311_add_file_path_to_assignments_table', 13),
(76, '2026_05_20_080838_add_file_fields_to_lessons_table', 13),
(77, '2026_05_27_022103_add_telegram_chat_id_to_users_table', 13),
(78, '2026_05_25_075706_create_calendar_events_table', 14),
(79, '2026_05_27_000000_add_hours_to_courses_table', 14),
(80, '2026_05_29_134459_create_university_ids_table', 15),
(81, '2026_05_29_134756_add_university_id_to_users_table', 16),
(82, '2026_05_30_000001_add_device_lock_to_students_table', 17),
(83, '2026_05_30_000002_add_location_to_attendance_sessions_table', 17),
(84, '2026_05_30_000003_add_verification_fields_to_attendance_table', 17),
(85, '2026_05_30_200000_add_device_fields_to_students_table', 18),
(86, '2026_05_29_194317_add_reply_to_to_messages_table', 19),
(87, '2026_05_29_194532_create_groups_table', 19),
(88, '2026_05_29_194533_create_group_user_table', 19),
(89, '2026_05_29_194536_add_group_id_to_messages_table', 19),
(90, '2026_05_30_100000_add_device_fields_to_students_table', 19),
(91, '2026_05_31_004752_add_program_id_to_students_table', 19),
(92, '2026_06_03_161516_add_first_last_name_to_users_table', 19),
(93, '2026_06_09_082812_add_advisor_fields_to_teachers_table', 20),
(94, '2026_06_09_230000_add_closed_at_to_attendance_sessions_table', 21),
(95, '2026_06_08_130329_add_report_request_id_to_performance_reports_table', 22),
(96, '2026_06_11_165538_add_sent_to_parent_to_report_requests_table', 22),
(97, '2026_06_09_074015_add_telegram_chat_id_to_users_table', 12),
(98, '2026_06_09_095308_add_telegram_chat_id_to_university_ids_table', 12),
(99, '2026_06_17_000001_add_face_fields_for_attendance', 23),
(100, '2026_06_17_000002_fix_reject_reason_enum', 24),
(101, '2026_06_17_000003_fix_face_image_column_type', 25),
(102, '2026_06_19_000001_create_grade_events_table', 25),
(103, '2026_06_19_000002_create_grade_report_requests_table', 26),
(104, '2026_06_21_192810_add_oral_to_grade_events', 27),
(105, '2026_06_28_100000_add_photo_to_university_ids_table', 28),
(106, '2026_06_29_020000_add_extra_fields_to_university_ids_table', 29),
(107, '2026_06_29_023000_add_reference_photo_to_students_table', 30),
(108, '2026_07_03_000001_create_photo_change_requests_table', 31),
(109, '2026_06_28_072234_add_offline_sync_policy_to_departments_table', 32),
(110, '2026_07_04_000001_add_indexes_to_messages_table', 33),
(111, '2026_07_04_100000_create_quizzes_table', 34),
(112, '2026_07_05_152247_add_time_and_duration_to_grade_events_table', 35);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category` enum('academic','administrative') NOT NULL DEFAULT 'administrative',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `sender_id`, `title`, `message`, `type`, `related_id`, `category`, `is_read`, `created_at`, `updated_at`) VALUES
(259, 28, NULL, 'طلب تسجيل جديد', 'قدّم محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 03:55:46', '2026-06-09 08:10:14'),
(260, 46, 28, 'تم تفعيل حسابك ✓', 'مرحباً محمود! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 1, '2026-05-31 03:56:00', '2026-05-31 04:05:25'),
(261, 28, NULL, 'طلب تسجيل جديد', 'قدّم محمد غنام طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 03:58:18', '2026-06-09 08:10:14'),
(262, 47, 28, 'تم تفعيل حسابك ✓', 'مرحباً محمد غنام! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-05-31 03:58:27', '2026-05-31 03:58:27'),
(264, 46, NULL, 'تمت الموافقة على طلب الإجازة', 'وافق رئيس القسم على طلب إجازتك بتاريخ 2026-05-31', 'leave_request', 25, 'administrative', 1, '2026-05-31 04:01:10', '2026-05-31 04:05:23'),
(265, 47, NULL, 'طلب إجازة يحتاج موافقتك', 'قدّم محمود طلب إجازة بتاريخ 2026-05-31، يرجى مراجعة الطلب والرد عليه', 'leave_request', 26, 'administrative', 0, '2026-05-31 04:05:08', '2026-05-31 04:05:08'),
(266, 28, NULL, 'طلب تسجيل جديد', 'قدّم هبة طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 04:28:28', '2026-06-09 08:10:14'),
(267, 48, 28, 'تم تفعيل حسابك ✓', 'مرحباً هبة! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 1, '2026-05-31 04:28:32', '2026-05-31 04:28:55'),
(268, 28, NULL, 'طلب تسجيل جديد', 'قدّم ناصر طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 04:31:01', '2026-06-09 08:10:14'),
(269, 49, 28, 'تم تفعيل حسابك ✓', 'مرحباً ناصر! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-05-31 04:31:13', '2026-05-31 04:31:13'),
(270, 28, NULL, 'طلب تسجيل جديد', 'قدّم ناصر طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 04:33:34', '2026-06-09 08:10:14'),
(271, 50, 28, 'تم تفعيل حسابك ✓', 'مرحباً ناصر! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-05-31 04:33:48', '2026-05-31 04:33:48'),
(272, 36, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(273, 37, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(274, 38, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(275, 39, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(276, 40, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(277, 41, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(278, 42, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(279, 43, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(281, 45, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 0, '2026-05-31 04:37:46', '2026-05-31 04:37:46'),
(282, 46, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 1, '2026-05-31 04:37:46', '2026-06-01 05:53:31'),
(283, 48, 1, 'إعلان جديد من الإدارة', 'اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى', 'announcement', 16, 'administrative', 1, '2026-05-31 04:37:46', '2026-05-31 04:39:03'),
(284, 48, 37, 'محاضرة جديدة — C#', 'رفع المعلم خالد اسماعيل محاضرة جديدة: اساسيات', 'lecture', 51, 'academic', 1, '2026-05-31 04:50:00', '2026-05-31 05:02:46'),
(285, 48, 37, 'محاضرة جديدة — C#', 'رفع المعلم خالد اسماعيل محاضرة جديدة: ..', 'lecture', 54, 'academic', 1, '2026-05-31 04:59:34', '2026-05-31 05:02:43'),
(286, 28, NULL, 'طلب تسجيل جديد', 'قدّم ادم محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 06:18:08', '2026-06-09 08:10:14'),
(287, 51, 28, 'تم تفعيل حسابك ✓', 'مرحباً ادم محمود! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-05-31 06:18:37', '2026-05-31 06:18:37'),
(288, 37, 51, 'تسليم واجب جديد', 'سلّم الطالب ادم محمود الواجب: حل المسائل', 'assignment', 14, 'academic', 0, '2026-05-31 06:28:30', '2026-05-31 06:28:30'),
(289, 28, NULL, 'طلب تسجيل جديد', 'قدّم ابو ادم طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 06:34:21', '2026-06-09 08:10:14'),
(294, 28, NULL, 'طلب تسجيل جديد', 'قدّم ابو ادم طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-05-31 06:36:59', '2026-06-09 08:10:14'),
(295, 53, 28, 'تم تفعيل حسابك ✓', 'مرحباً ابو ادم! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-05-31 06:37:05', '2026-05-31 06:37:05'),
(297, 37, NULL, 'طلب تقرير سلوكي', 'طلب ولي أمر الطالب ادم محمود تقريراً سلوكياً، يُرجى المراجعة.', 'report', NULL, 'administrative', 0, '2026-05-31 06:47:12', '2026-05-31 06:47:12'),
(298, 51, NULL, 'تمت الموافقة على طلب الإجازة', 'وافق رئيس القسم على طلب إجازتك بتاريخ 2026-05-31', 'leave_request', 27, 'administrative', 0, '2026-05-31 06:52:02', '2026-05-31 06:52:02'),
(299, 36, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(300, 37, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 1, '2026-06-07 19:56:46', '2026-06-07 20:54:25'),
(301, 38, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(302, 39, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(303, 40, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(304, 41, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(305, 42, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(306, 43, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(308, 45, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(309, 46, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(310, 48, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(311, 51, NULL, 'إعلان جديد من رئيس القسم', 'اعلان جديد', 'announcement', 17, 'administrative', 0, '2026-06-07 19:56:46', '2026-06-07 19:56:46'),
(312, 28, NULL, 'طلب تسجيل جديد', 'قدّم هادي حسن طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 20:57:13', '2026-06-09 08:10:14'),
(313, 54, 28, 'تم تفعيل حسابك ✓', 'مرحباً هادي حسن! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 20:57:38', '2026-06-07 20:57:38'),
(314, 28, NULL, 'طلب تسجيل جديد', 'قدّم زكي حسن طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 20:58:52', '2026-06-09 08:10:14'),
(315, 55, 28, 'تم تفعيل حسابك ✓', 'مرحباً زكي حسن! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 20:59:17', '2026-06-07 20:59:17'),
(316, 28, NULL, 'طلب تسجيل جديد', 'قدّم حسين دياب طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 21:04:46', '2026-06-09 08:10:14'),
(317, 56, 28, 'تم تفعيل حسابك ✓', 'مرحباً حسين دياب! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 21:04:53', '2026-06-07 21:04:53'),
(318, 28, NULL, 'طلب تسجيل جديد', 'قدّم علي دياب طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 21:05:26', '2026-06-09 08:10:14'),
(319, 57, 28, 'تم تفعيل حسابك ✓', 'مرحباً علي دياب! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 21:05:32', '2026-06-07 21:05:32'),
(320, 28, NULL, 'طلب تسجيل جديد', 'قدّم فارس فارس طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 21:35:46', '2026-06-09 08:10:14'),
(321, 28, NULL, 'طلب تسجيل جديد', 'قدّم ابو فارس طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 1, '2026-06-07 21:36:53', '2026-06-09 08:10:14'),
(322, 59, 28, 'تم تفعيل حسابك ✓', 'مرحباً ابو فارس! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 21:37:01', '2026-06-07 21:37:01'),
(323, 58, 28, 'تم تفعيل حسابك ✓', 'مرحباً فارس فارس! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 0, '2026-06-07 21:37:03', '2026-06-07 21:37:03'),
(324, 79, 113, 'تقرير سلوكي جديد', 'تم إرسال تقرير سلوكي عن ابنك/ابنتك محمود غنام', 'report', 9, 'academic', 0, '2026-06-09 18:49:01', '2026-06-09 18:49:01'),
(325, 73, 113, 'تقرير سلوكي جديد', 'تم رفع تقرير عن الطالب محمود غنام بواسطة ابراهيم جبارة', 'report', 9, 'academic', 1, '2026-06-09 18:49:01', '2026-06-10 21:29:24'),
(327, 128, 28, 'تم تفعيل حسابك ✓', 'مرحباً هبة عيسى! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 1, '2026-06-09 20:55:25', '2026-06-11 10:45:28'),
(328, 118, NULL, 'طلب تقرير سلوكي', 'طُلب منك تقرير سلوكي عن الطالب محمود غنام', 'report', NULL, 'administrative', 1, '2026-06-11 14:14:11', '2026-07-05 13:22:20'),
(329, 79, 118, 'تقرير سلوكي جديد', 'تم إرسال تقرير سلوكي عن ابنك/ابنتك محمود غنام', 'report', 10, 'academic', 0, '2026-06-11 14:15:43', '2026-06-11 14:15:43'),
(330, 73, 118, 'تقرير سلوكي جديد', 'تم رفع تقرير عن الطالب محمود غنام بواسطة اسراء دسوقي', 'report', 10, 'academic', 1, '2026-06-11 14:15:43', '2026-06-11 14:16:01'),
(331, 114, NULL, 'طلب تقرير سلوكي', 'طُلب منك تقرير سلوكي عن الطالب هبة عيسى', 'report', NULL, 'administrative', 1, '2026-06-11 14:30:11', '2026-07-05 13:03:43'),
(332, 129, NULL, 'تقرير أداء للطالب هبة عيسى', 'ممتازة', 'report', 11, 'administrative', 0, '2026-06-11 14:36:49', '2026-06-11 14:36:49'),
(333, 73, NULL, 'تقرير العلامات جاهز', 'البيانات موجودة، يمكنك الاطلاع على تقرير العلامات مباشرة.', 'grade_report_ready', 1, 'administrative', 1, '2026-06-19 11:51:32', '2026-06-26 19:39:34'),
(334, 131, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 25 / 25.00', 'grade', 10, 'administrative', 0, '2026-06-24 22:17:40', '2026-06-24 22:17:40'),
(335, 132, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 15 / 25.00', 'grade', 10, 'administrative', 0, '2026-06-24 22:17:41', '2026-06-24 22:17:41'),
(336, 130, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 24 / 25.00', 'grade', 10, 'administrative', 0, '2026-06-24 22:17:41', '2026-06-24 22:17:41'),
(337, 133, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 11 / 25.00', 'grade', 10, 'administrative', 0, '2026-06-24 22:17:41', '2026-06-24 22:17:41'),
(338, 136, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 15 / 25.00', 'grade', 10, 'administrative', 1, '2026-06-24 22:17:41', '2026-07-05 11:56:50'),
(339, 130, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 10 / 25.00', 'grade', 11, 'administrative', 0, '2026-06-26 16:42:17', '2026-06-26 16:42:17'),
(340, 131, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة C#: 40 / 100.00', 'grade', 9, 'administrative', 0, '2026-06-26 16:42:55', '2026-06-26 16:42:55'),
(341, 132, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة C#: 50 / 100.00', 'grade', 9, 'administrative', 0, '2026-06-26 16:42:55', '2026-06-26 16:42:55'),
(342, 130, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة C#: 100 / 100.00', 'grade', 9, 'administrative', 0, '2026-06-26 16:42:55', '2026-06-26 16:42:55'),
(343, 133, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة C#: 85 / 100.00', 'grade', 9, 'administrative', 0, '2026-06-26 16:42:55', '2026-06-26 16:42:55'),
(344, 136, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة C#: 41 / 100.00', 'grade', 9, 'administrative', 1, '2026-06-26 16:42:55', '2026-07-05 11:56:50'),
(345, 130, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة المادة: 25 / 25.00', 'grade', 13, 'administrative', 0, '2026-06-26 18:08:47', '2026-06-26 18:08:47'),
(346, 131, NULL, 'نتيجة شفهي', 'علامتك في شفهي «تقييم تجريبي» - معلوماتية: 12 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(347, 144, NULL, 'نتيجة شفهي', 'علامة سارة علي حسن في شفهي «تقييم تجريبي» - معلوماتية: 12 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(348, 132, NULL, 'نتيجة شفهي', 'علامتك في شفهي «تقييم تجريبي» - معلوماتية: 1 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(349, 140, NULL, 'نتيجة شفهي', 'علامة عمر خالد مصطفى في شفهي «تقييم تجريبي» - معلوماتية: 1 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(350, 130, NULL, 'نتيجة شفهي', 'علامتك في شفهي «تقييم تجريبي» - معلوماتية: 25 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(351, 138, NULL, 'نتيجة شفهي', 'علامة محمد أحمد السيد في شفهي «تقييم تجريبي» - معلوماتية: 25 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(352, 133, NULL, 'نتيجة شفهي', 'علامتك في شفهي «تقييم تجريبي» - معلوماتية: 15 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(353, 141, NULL, 'نتيجة شفهي', 'علامة نور إبراهيم جمال في شفهي «تقييم تجريبي» - معلوماتية: 15 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(354, 136, NULL, 'نتيجة شفهي', 'علامتك في شفهي «تقييم تجريبي» - معلوماتية: 15 / 25.00', 'grade', 14, 'administrative', 1, '2026-06-26 18:19:48', '2026-07-05 11:56:50'),
(355, 137, NULL, 'نتيجة شفهي', 'علامة هنا سعيد رشيد في شفهي «تقييم تجريبي» - معلوماتية: 15 / 25.00', 'grade', 14, 'administrative', 0, '2026-06-26 18:19:48', '2026-06-26 18:19:48'),
(356, 131, NULL, 'نتيجة امتحان', 'علامتك في امتحان «امتحان» - خوارزميات: 20 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:39', '2026-06-26 18:31:39'),
(357, 144, NULL, 'نتيجة امتحان', 'علامة سارة علي حسن في امتحان «امتحان» - خوارزميات: 20 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:39', '2026-06-26 18:31:39'),
(358, 132, NULL, 'نتيجة امتحان', 'علامتك في امتحان «امتحان» - خوارزميات: 50 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:39', '2026-06-26 18:31:39'),
(359, 140, NULL, 'نتيجة امتحان', 'علامة عمر خالد مصطفى في امتحان «امتحان» - خوارزميات: 50 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:39', '2026-06-26 18:31:39'),
(360, 130, NULL, 'نتيجة امتحان', 'علامتك في امتحان «امتحان» - خوارزميات: 25 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:39', '2026-06-26 18:31:39'),
(361, 138, NULL, 'نتيجة امتحان', 'علامة محمد أحمد السيد في امتحان «امتحان» - خوارزميات: 25 / 100.00', 'grade', 15, 'administrative', 0, '2026-06-26 18:31:40', '2026-06-26 18:31:40'),
(362, 85, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(363, 86, NULL, 'نتيجة مذاكرة', 'علامة جودي سلطاني في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(364, 139, NULL, 'نتيجة مذاكرة', 'علامة جودي سلطاني في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(365, 135, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(366, 143, NULL, 'نتيجة مذاكرة', 'علامة كريم يوسف ناصر في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(367, 134, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة «مذاكرة» - تصميم العاب: 10 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(368, 142, NULL, 'نتيجة مذاكرة', 'علامة ليلى محمود عمر في مذاكرة «مذاكرة» - تصميم العاب: 10 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(369, 136, NULL, 'نتيجة مذاكرة', 'علامتك في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 1, '2026-06-26 18:48:59', '2026-06-26 19:38:22'),
(370, 137, NULL, 'نتيجة مذاكرة', 'علامة هنا سعيد رشيد في مذاكرة «مذاكرة» - تصميم العاب: 25 / 25.00', 'grade', 16, 'administrative', 0, '2026-06-26 18:48:59', '2026-06-26 18:48:59'),
(371, 136, NULL, 'نتيجة امتحان', 'علامتك في امتحان «u» - تصميم العاب: 50 / 100.00', 'grade', 17, 'administrative', 1, '2026-06-26 18:59:20', '2026-06-26 19:37:49'),
(372, 137, NULL, 'نتيجة امتحان', 'علامة هنا سعيد رشيد في امتحان «u» - تصميم العاب: 50 / 100.00', 'grade', 17, 'administrative', 0, '2026-06-26 18:59:20', '2026-06-26 18:59:20'),
(373, 145, NULL, 'نتيجة امتحان', 'علامة هنا سعيد رشيد في امتحان «u» - تصميم العاب: 50 / 100.00', 'grade', 17, 'administrative', 1, '2026-06-26 18:59:20', '2026-06-26 18:59:20'),
(374, 85, NULL, 'نتيجة امتحان', 'علامتك في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(375, 86, NULL, 'نتيجة امتحان', 'علامة جودي سلطاني في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(376, 139, NULL, 'نتيجة امتحان', 'علامة جودي سلطاني في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(377, 135, NULL, 'نتيجة امتحان', 'علامتك في امتحان «..» - تصميم العاب: 50 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(378, 143, NULL, 'نتيجة امتحان', 'علامة كريم يوسف ناصر في امتحان «..» - تصميم العاب: 50 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(379, 134, NULL, 'نتيجة امتحان', 'علامتك في امتحان «..» - تصميم العاب: 39 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(380, 142, NULL, 'نتيجة امتحان', 'علامة ليلى محمود عمر في امتحان «..» - تصميم العاب: 39 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(381, 136, NULL, 'نتيجة امتحان', 'علامتك في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 1, '2026-06-28 04:28:34', '2026-07-05 11:56:50'),
(382, 137, NULL, 'نتيجة امتحان', 'علامة هنا سعيد رشيد في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 0, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(383, 145, NULL, 'نتيجة امتحان', 'علامة هنا سعيد رشيد في امتحان «..» - تصميم العاب: 0 / 100.00', 'grade', 19, 'administrative', 1, '2026-06-28 04:28:34', '2026-06-28 04:28:34'),
(384, 28, NULL, 'طلب تسجيل جديد', 'قدّم مجدولين محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 0, '2026-07-03 18:56:09', '2026-07-03 18:56:09'),
(386, 130, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 99, 'academic', 0, '2026-07-03 21:03:03', '2026-07-03 21:03:03'),
(387, 131, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 99, 'academic', 0, '2026-07-03 21:03:03', '2026-07-03 21:03:03'),
(388, 132, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 99, 'academic', 0, '2026-07-03 21:03:03', '2026-07-03 21:03:03'),
(389, 85, 112, 'محاضرة جديدة — تصميم العاب', 'رفع المعلم خالد اسماعيل محاضرة جديدة: تصميم العاب', 'lecture', 100, 'academic', 0, '2026-07-03 21:03:22', '2026-07-03 21:03:22'),
(390, 134, 112, 'محاضرة جديدة — تصميم العاب', 'رفع المعلم خالد اسماعيل محاضرة جديدة: تصميم العاب', 'lecture', 100, 'academic', 0, '2026-07-03 21:03:22', '2026-07-03 21:03:22'),
(391, 135, 112, 'محاضرة جديدة — تصميم العاب', 'رفع المعلم خالد اسماعيل محاضرة جديدة: تصميم العاب', 'lecture', 100, 'academic', 0, '2026-07-03 21:03:22', '2026-07-03 21:03:22'),
(392, 136, 112, 'محاضرة جديدة — تصميم العاب', 'رفع المعلم خالد اسماعيل محاضرة جديدة: تصميم العاب', 'lecture', 100, 'academic', 1, '2026-07-03 21:03:22', '2026-07-05 11:56:50'),
(393, 130, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 101, 'academic', 0, '2026-07-03 21:03:58', '2026-07-03 21:03:58'),
(394, 131, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 101, 'academic', 0, '2026-07-03 21:03:58', '2026-07-03 21:03:58'),
(395, 132, 112, 'محاضرة جديدة — خوارزميات', 'رفع المعلم خالد اسماعيل محاضرة جديدة: خوارزميات', 'lecture', 101, 'academic', 0, '2026-07-03 21:03:58', '2026-07-03 21:03:58'),
(396, 130, 112, 'ت�& تصح�`ح ��اجبْ', 'صح�ح ا��&ع��& ��اجب \"حل المسائل\" � ع�ا�&تْ: 100/100', 'assignment', 14, 'administrative', 0, '2026-07-03 21:04:40', '2026-07-03 21:04:40'),
(397, 130, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: واجب مهم تجريبي', 'assignment', 15, 'academic', 0, '2026-07-03 21:05:32', '2026-07-03 21:05:32'),
(398, 131, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: واجب مهم تجريبي', 'assignment', 15, 'academic', 0, '2026-07-03 21:05:32', '2026-07-03 21:05:32'),
(399, 132, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: واجب مهم تجريبي', 'assignment', 15, 'academic', 0, '2026-07-03 21:05:32', '2026-07-03 21:05:32'),
(400, 64, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(401, 67, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(402, 69, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(403, 71, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(404, 74, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(405, 76, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(406, 78, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(407, 80, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(408, 83, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(409, 85, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(410, 87, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(411, 89, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(412, 91, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(413, 93, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(414, 96, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(415, 98, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(416, 100, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(417, 102, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(418, 104, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(419, 106, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(420, 128, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(421, 130, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(422, 131, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(423, 132, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(424, 133, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(425, 134, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(426, 135, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 0, '2026-07-03 21:54:13', '2026-07-03 21:54:13'),
(427, 136, 73, 'إعلان جديد من رئيس القسم', 'kk', 'announcement', 18, 'administrative', 1, '2026-07-03 21:54:13', '2026-07-05 11:56:50'),
(428, 64, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(429, 67, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(430, 69, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(431, 71, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(432, 74, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(433, 76, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(434, 78, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(435, 80, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(436, 83, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(437, 85, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(438, 87, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(439, 89, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(440, 91, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(441, 93, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(442, 96, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(443, 98, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(444, 100, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(445, 102, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(446, 104, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(447, 106, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(448, 108, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(449, 109, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(450, 110, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(451, 111, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(452, 112, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 11:55:58'),
(453, 113, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(454, 114, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 13:03:43'),
(455, 115, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(456, 116, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(457, 117, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(458, 118, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 13:22:20'),
(459, 119, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(460, 120, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(461, 121, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(462, 122, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(463, 123, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(464, 124, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(465, 125, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(466, 126, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(467, 127, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(468, 128, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(469, 130, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(470, 131, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(471, 132, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(472, 133, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(473, 134, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(474, 135, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(475, 136, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 19, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 11:56:50'),
(476, 64, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(477, 67, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(478, 69, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(479, 71, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(480, 74, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(481, 76, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(482, 78, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(483, 80, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(484, 83, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(485, 85, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(486, 87, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(487, 89, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(488, 91, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(489, 93, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(490, 96, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(491, 98, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(492, 100, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(493, 102, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(494, 104, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(495, 106, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(496, 108, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(497, 109, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(498, 110, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(499, 111, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(500, 112, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 11:56:00'),
(501, 113, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(502, 114, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 13:03:43'),
(503, 115, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(504, 116, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(505, 117, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(506, 118, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 13:22:20'),
(507, 119, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(508, 120, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(509, 121, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(510, 122, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(511, 123, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(512, 124, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(513, 125, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(514, 126, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(515, 127, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(516, 128, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(517, 130, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(518, 131, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(519, 132, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(520, 133, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(521, 134, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(522, 135, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 0, '2026-07-05 05:39:28', '2026-07-05 05:39:28'),
(523, 136, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 20, 'administrative', 1, '2026-07-05 05:39:28', '2026-07-05 11:56:50'),
(524, 64, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(525, 67, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(526, 69, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(527, 71, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(528, 74, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(529, 76, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(530, 78, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(531, 80, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(532, 83, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(533, 85, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(534, 87, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(535, 89, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(536, 91, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(537, 93, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(538, 96, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(539, 98, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(540, 100, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(541, 102, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(542, 104, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(543, 106, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(544, 108, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(545, 109, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(546, 110, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(547, 111, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(548, 112, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 1, '2026-07-05 05:39:30', '2026-07-05 11:55:55'),
(549, 113, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 1, '2026-07-05 05:39:30', '2026-07-05 12:52:41'),
(550, 114, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 1, '2026-07-05 05:39:30', '2026-07-05 13:03:43'),
(551, 115, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(552, 116, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(553, 117, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(554, 118, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 1, '2026-07-05 05:39:30', '2026-07-05 13:22:20'),
(555, 119, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(556, 120, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(557, 121, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(558, 122, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(559, 123, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(560, 124, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(561, 125, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(562, 126, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(563, 127, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(564, 128, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(565, 130, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(566, 131, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(567, 132, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(568, 133, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(569, 134, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(570, 135, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 0, '2026-07-05 05:39:30', '2026-07-05 05:39:30'),
(571, 136, 73, 'إعلان جديد من رئيس القسم', 'عهبافلغاتنمكتن9ىتا7ف5ث32ض', 'announcement', 21, 'administrative', 1, '2026-07-05 05:39:30', '2026-07-05 11:56:50'),
(728, 28, NULL, 'طلب تسجيل جديد', 'قدّم هدى شبلي طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 0, '2026-07-05 12:23:06', '2026-07-05 12:23:06'),
(729, 147, 28, 'تم تفعيل حسابك ✓', 'مرحباً هدى شبلي! تم مراجعة طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 1, '2026-07-05 12:23:21', '2026-07-05 12:23:50'),
(730, 28, NULL, 'طلب تسجيل جديد', 'قدّم ثناء شبلي طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.', 'administrative', NULL, 'administrative', 0, '2026-07-05 12:29:12', '2026-07-05 12:29:12'),
(731, 148, 28, 'تم تفعيل حسابك ✓', 'مرحباً ثناء شبلي! تم مراجعة طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول.', 'administrative', NULL, 'administrative', 1, '2026-07-05 12:29:30', '2026-07-05 12:29:30');
INSERT INTO `notifications` (`id`, `user_id`, `sender_id`, `title`, `message`, `type`, `related_id`, `category`, `is_read`, `created_at`, `updated_at`) VALUES
(732, 112, 147, 'تسليم واجب جديد', 'سلّم الطالب هدى شبلي الواجب: واجب مهم تجريبي', 'assignment', 15, 'academic', 1, '2026-07-05 12:44:52', '2026-07-05 12:45:03'),
(733, 147, 112, 'ت�& تصح�`ح ��اجبْ', 'صح�ح ا��&ع��& ��اجب \"واجب مهم تجريبي\" � ع�ا�&تْ: 50/100', 'assignment', 15, 'administrative', 1, '2026-07-05 12:45:53', '2026-07-05 12:46:06'),
(734, 148, NULL, 'طلب إجازة يحتاج موافقتك', 'قدّم هدى شبلي طلب إجازة بتاريخ 2026-07-05، يرجى مراجعة الطلب والرد عليه', 'leave_request', 29, 'administrative', 1, '2026-07-05 12:48:29', '2026-07-05 12:48:29'),
(735, 60, NULL, 'طلب إجازة بانتظار موافقتك', 'وافق ولي أمر الطالب هدى شبلي على طلب إجازة بتاريخ 2026-07-05، يرجى مراجعته', 'leave_request', 29, 'administrative', 0, '2026-07-05 12:48:38', '2026-07-05 12:48:38'),
(736, 147, NULL, 'تمت الموافقة على طلب الإجازة', 'وافق رئيس القسم على طلب إجازتك بتاريخ 2026-07-05', 'leave_request', 29, 'administrative', 1, '2026-07-05 12:49:26', '2026-07-05 12:49:35'),
(737, 130, 113, 'محاضرة جديدة — شبكات CCNA(IT)', 'رفع المعلم ابراهيم جبارة محاضرة جديدة: اساسيات الشبكات', 'lecture', 105, 'academic', 0, '2026-07-05 12:53:55', '2026-07-05 12:53:55'),
(738, 147, 113, 'محاضرة جديدة — شبكات CCNA(IT)', 'رفع المعلم ابراهيم جبارة محاضرة جديدة: اساسيات الشبكات', 'lecture', 105, 'academic', 1, '2026-07-05 12:53:55', '2026-07-05 17:03:12'),
(739, 130, 113, 'محاضرة جديدة — شبكات CCNA(IT)', 'رفع المعلم ابراهيم جبارة محاضرة جديدة: محاضرة 1', 'lecture', 106, 'academic', 0, '2026-07-05 12:55:10', '2026-07-05 12:55:10'),
(740, 147, 113, 'محاضرة جديدة — شبكات CCNA(IT)', 'رفع المعلم ابراهيم جبارة محاضرة جديدة: محاضرة 1', 'lecture', 106, 'academic', 1, '2026-07-05 12:55:10', '2026-07-05 12:55:34'),
(741, 130, 114, 'محاضرة جديدة — مواقع ويب', 'رفع المعلم احمد نصلة محاضرة جديدة: css', 'lecture', 107, 'academic', 0, '2026-07-05 12:59:31', '2026-07-05 12:59:31'),
(742, 147, 114, 'محاضرة جديدة — مواقع ويب', 'رفع المعلم احمد نصلة محاضرة جديدة: css', 'lecture', 107, 'academic', 1, '2026-07-05 12:59:31', '2026-07-05 13:00:32'),
(743, 130, 114, 'محاضرة جديدة — مواقع ويب', 'رفع المعلم احمد نصلة محاضرة جديدة: html', 'lecture', 108, 'academic', 0, '2026-07-05 13:00:22', '2026-07-05 13:00:22'),
(744, 147, 114, 'محاضرة جديدة — مواقع ويب', 'رفع المعلم احمد نصلة محاضرة جديدة: html', 'lecture', 108, 'academic', 1, '2026-07-05 13:00:22', '2026-07-05 13:10:42'),
(745, 130, 114, 'محاضرة جديدة — Fluteer', 'رفع المعلم احمد نصلة محاضرة جديدة: اساسيات flutter', 'lecture', 109, 'academic', 0, '2026-07-05 13:01:32', '2026-07-05 13:01:32'),
(746, 147, 114, 'محاضرة جديدة — Fluteer', 'رفع المعلم احمد نصلة محاضرة جديدة: اساسيات flutter', 'lecture', 109, 'academic', 1, '2026-07-05 13:01:32', '2026-07-05 13:02:56'),
(747, 130, 114, 'محاضرة جديدة — laravel', 'رفع المعلم احمد نصلة محاضرة جديدة: php', 'lecture', 110, 'academic', 0, '2026-07-05 13:12:20', '2026-07-05 13:12:20'),
(748, 147, 114, 'محاضرة جديدة — laravel', 'رفع المعلم احمد نصلة محاضرة جديدة: php', 'lecture', 110, 'academic', 1, '2026-07-05 13:12:20', '2026-07-05 13:12:30'),
(749, 130, 118, 'محاضرة جديدة — تحليل نظم', 'رفع المعلم اسراء دسوقي محاضرة جديدة: محاضرة1', 'lecture', 111, 'academic', 0, '2026-07-05 13:17:42', '2026-07-05 13:17:42'),
(750, 147, 118, 'محاضرة جديدة — تحليل نظم', 'رفع المعلم اسراء دسوقي محاضرة جديدة: محاضرة1', 'lecture', 111, 'academic', 1, '2026-07-05 13:17:42', '2026-07-05 17:03:12'),
(751, 130, 118, 'محاضرة جديدة — ASP', 'رفع المعلم اسراء دسوقي محاضرة جديدة: محاضرة 2', 'lecture', 112, 'academic', 0, '2026-07-05 13:18:10', '2026-07-05 13:18:10'),
(752, 147, 118, 'محاضرة جديدة — ASP', 'رفع المعلم اسراء دسوقي محاضرة جديدة: محاضرة 2', 'lecture', 112, 'academic', 1, '2026-07-05 13:18:10', '2026-07-05 17:03:12'),
(753, 130, 118, 'محاضرة جديدة — تحليل نظم', 'رفع المعلم اسراء دسوقي محاضرة جديدة: ملخص تحليل نظم', 'lecture', 113, 'academic', 0, '2026-07-05 13:18:59', '2026-07-05 13:18:59'),
(754, 147, 118, 'محاضرة جديدة — تحليل نظم', 'رفع المعلم اسراء دسوقي محاضرة جديدة: ملخص تحليل نظم', 'lecture', 113, 'academic', 1, '2026-07-05 13:18:59', '2026-07-05 13:19:16'),
(755, 130, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(756, 131, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(757, 132, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(758, 133, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(759, 136, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:28:18', '2026-07-05 13:28:18'),
(760, 147, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين منزلي', 'assignment', 16, 'academic', 1, '2026-07-05 13:28:18', '2026-07-05 17:03:12'),
(761, 130, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:29:23', '2026-07-05 13:29:23'),
(762, 131, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:29:23', '2026-07-05 13:29:23'),
(763, 132, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:29:23', '2026-07-05 13:29:23'),
(764, 133, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:29:23', '2026-07-05 13:29:23'),
(765, 136, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:29:23', '2026-07-05 13:29:23'),
(766, 147, 112, 'واجب جديد — ', 'رفع المعلم خالد اسماعيل واجباً جديداً: تمرين مسائل', 'assignment', 17, 'academic', 1, '2026-07-05 13:29:23', '2026-07-05 17:03:12'),
(767, 112, 147, 'تسليم واجب جديد', 'سلّم الطالب هدى شبلي الواجب: تمرين مسائل', 'assignment', 17, 'academic', 0, '2026-07-05 13:30:06', '2026-07-05 13:30:06'),
(768, 112, 147, 'تسليم واجب جديد', 'سلّم الطالب هدى شبلي الواجب: تمرين منزلي', 'assignment', 16, 'academic', 0, '2026-07-05 13:30:22', '2026-07-05 13:30:22'),
(929, 79, NULL, 'تقرير أداء للطالب محمود غنام', 'ممتاز', 'report', 10, 'administrative', 0, '2026-07-05 17:07:14', '2026-07-05 17:07:14'),
(930, 79, NULL, 'تقرير أداء للطالب محمود غنام', 'ممتاز', 'report', 10, 'administrative', 0, '2026-07-05 17:07:16', '2026-07-05 17:07:16'),
(931, 114, NULL, 'طلب إدخال علامات: Fluteer', 'رئيس القسم يطلب إدخال علامات مادة: Fluteer\nالمطلوب: مذاكرة + امتحان + شفهي لكل طالب', 'grade_report_request', 5, 'administrative', 1, '2026-07-05 17:07:56', '2026-07-05 17:08:36'),
(932, 73, NULL, 'تقرير علامات جاهز: Fluteer', 'تقرير علامات مادة: Fluteer\nالناجحون: 0 / 0\n\n', 'grade_report_ready', 2, 'administrative', 0, '2026-07-05 17:08:43', '2026-07-05 17:08:43'),
(933, 130, NULL, 'نتيجة امتحان', 'علامتك في امتحان «exam» - laravel: 50 / 100.00', 'grade', 20, 'administrative', 0, '2026-07-05 17:11:24', '2026-07-05 17:11:24'),
(934, 138, NULL, 'نتيجة امتحان', 'علامة محمد أحمد السيد في امتحان «exam» - laravel: 50 / 100.00', 'grade', 20, 'administrative', 0, '2026-07-05 17:11:24', '2026-07-05 17:11:24'),
(935, 147, NULL, 'نتيجة امتحان', 'علامتك في امتحان «exam» - laravel: 15 / 100.00', 'grade', 20, 'administrative', 1, '2026-07-05 17:11:24', '2026-07-05 17:11:55'),
(936, 148, NULL, 'نتيجة امتحان', 'علامة هدى شبلي في امتحان «exam» - laravel: 15 / 100.00', 'grade', 20, 'administrative', 0, '2026-07-05 17:11:26', '2026-07-05 17:11:26'),
(1017, 108, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1018, 109, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1019, 110, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1020, 111, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1021, 112, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1022, 113, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1023, 114, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1024, 115, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1025, 116, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1026, 117, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1027, 118, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1028, 119, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1029, 120, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1030, 121, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1031, 122, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1032, 123, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1033, 124, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1034, 125, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1035, 126, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1036, 127, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1037, 64, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1038, 67, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1039, 69, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1040, 71, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1041, 74, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1042, 76, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1043, 78, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1044, 80, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1045, 83, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1046, 85, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1047, 87, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1048, 89, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1049, 91, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1050, 93, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1051, 96, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1052, 98, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1053, 100, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1054, 102, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1055, 104, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1056, 106, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1057, 128, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1058, 130, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1059, 131, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1060, 132, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1061, 133, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1062, 134, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1063, 135, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1064, 136, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1065, 147, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1066, 66, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1067, 68, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1068, 70, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1069, 72, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1070, 75, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1071, 77, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1072, 79, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1073, 82, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1074, 84, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1075, 86, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1076, 88, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1077, 90, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1078, 92, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1079, 94, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1080, 97, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1081, 99, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1082, 101, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1083, 103, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1084, 105, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1085, 107, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1086, 129, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1087, 137, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1088, 138, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1089, 139, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1090, 140, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1091, 141, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1092, 142, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1093, 143, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1094, 144, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1095, 145, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1096, 148, 73, 'إعلان جديد من رئيس القسم', 'اعلان مهم', 'announcement', 27, 'administrative', 0, '2026-07-05 18:36:24', '2026-07-05 18:36:24'),
(1097, 108, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1098, 109, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1099, 110, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1100, 111, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1101, 112, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1102, 113, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1103, 114, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1104, 115, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1105, 116, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1106, 117, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1107, 118, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1108, 119, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1109, 120, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1110, 121, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1111, 122, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1112, 123, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1113, 124, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1114, 125, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1115, 126, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1116, 127, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1117, 64, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1118, 67, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1119, 69, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1120, 71, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1121, 74, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1122, 76, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1123, 78, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1124, 80, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1125, 83, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1126, 85, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1127, 87, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1128, 89, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1129, 91, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1130, 93, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1131, 96, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1132, 98, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1133, 100, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1134, 102, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1135, 104, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1136, 106, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1137, 128, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1138, 130, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1139, 131, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1140, 132, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1141, 133, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1142, 134, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1143, 135, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1144, 136, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1145, 147, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1146, 66, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1147, 68, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1148, 70, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1149, 72, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1150, 75, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1151, 77, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1152, 79, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1153, 82, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1154, 84, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1155, 86, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1156, 88, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1157, 90, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1158, 92, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1159, 94, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1160, 97, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1161, 99, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1162, 101, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1163, 103, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1164, 105, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1165, 107, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1166, 129, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1167, 137, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1168, 138, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1169, 139, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1170, 140, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1171, 141, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1172, 142, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1173, 143, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1174, 144, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1175, 145, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06'),
(1176, 148, 73, 'إعلان جديد من رئيس القسم', 'برنامج الامتحانات', 'announcement', 28, 'administrative', 0, '2026-07-05 18:38:06', '2026-07-05 18:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `code`, `expires_at`, `used`, `created_at`, `updated_at`) VALUES
(3, 'majdouleen@gmail.com', '123456', '2026-05-09 12:14:12', 1, '2026-05-09 09:13:44', '2026-05-09 09:14:12'),
(5, 'mhd@gmail.com', '123456', '2026-05-17 10:46:47', 1, '2026-05-17 07:46:34', '2026-05-17 07:46:47'),
(6, 'm@gmail.com', '123456', '2026-05-19 00:19:49', 1, '2026-05-18 21:19:45', '2026-05-18 21:19:49'),
(7, 'a@gmail.com', '123456', '2026-05-21 11:37:37', 1, '2026-05-21 08:37:32', '2026-05-21 08:37:37'),
(8, 'majdouleenmahmoud8@gmail.com', '123456', '2026-05-21 12:04:28', 1, '2026-05-21 09:04:24', '2026-05-21 09:04:28'),
(9, 'hshsh@gmail.com', '123456', '2026-05-24 08:20:03', 0, '2026-05-24 08:05:03', '2026-05-24 08:05:03'),
(10, 'hshhhsh@gmail.com', '123456', '2026-05-24 08:20:38', 0, '2026-05-24 08:05:38', '2026-05-24 08:05:38'),
(11, 'hdhs@gmail.com', '123456', '2026-05-24 11:06:49', 1, '2026-05-24 08:06:44', '2026-05-24 08:06:49'),
(12, 'h@gmail.com', '123456', '2026-05-25 10:56:02', 1, '2026-05-25 07:55:46', '2026-05-25 07:56:02'),
(13, 'testheba88@test.com', '123456', '2026-05-25 19:45:27', 0, '2026-05-25 19:30:27', '2026-05-25 19:30:27'),
(17, 'joudiqusai@gmail.com', '123456', '2026-05-25 20:04:46', 0, '2026-05-25 19:49:46', '2026-05-25 19:49:46'),
(19, 'joudimahmoud426@gmail.com', '123456', '2026-05-25 22:56:37', 1, '2026-05-25 19:55:43', '2026-05-25 19:56:37'),
(20, 'majdoumoud8@gmail.com', '123456', '2026-05-25 23:20:01', 1, '2026-05-25 20:19:53', '2026-05-25 20:20:01'),
(21, 'mahmoud426@gmail.com', '091848', '2026-05-26 00:06:28', 1, '2026-05-25 21:05:34', '2026-05-25 21:06:28'),
(22, 'majdouleeoud8@gmail.com', '967110', '2026-05-26 00:15:34', 1, '2026-05-25 21:15:17', '2026-05-25 21:15:34'),
(23, 'q@gmail.com', '178956', '2026-05-26 16:12:57', 1, '2026-05-26 13:12:28', '2026-05-26 13:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`parent_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 66, '2026-06-09 08:36:58', '2026-06-09 08:36:58'),
(2, 68, '2026-06-09 08:40:51', '2026-06-09 08:40:51'),
(3, 70, '2026-06-09 08:46:01', '2026-06-09 08:46:01'),
(4, 72, '2026-06-09 08:49:55', '2026-06-09 08:49:55'),
(5, 75, '2026-06-09 09:02:31', '2026-06-09 09:02:31'),
(6, 77, '2026-06-09 09:05:33', '2026-06-09 09:05:33'),
(7, 79, '2026-06-09 09:11:28', '2026-06-09 09:11:28'),
(9, 82, '2026-06-09 09:26:40', '2026-06-09 09:26:40'),
(10, 84, '2026-06-09 09:35:12', '2026-06-09 09:35:12'),
(11, 86, '2026-06-09 09:37:53', '2026-06-09 09:37:53'),
(12, 88, '2026-06-09 11:14:09', '2026-06-09 11:14:09'),
(13, 90, '2026-06-09 11:16:58', '2026-06-09 11:16:58'),
(14, 92, '2026-06-09 11:19:21', '2026-06-09 11:19:21'),
(15, 94, '2026-06-09 11:31:50', '2026-06-09 11:31:50'),
(16, 97, '2026-06-09 12:10:16', '2026-06-09 12:10:16'),
(17, 99, '2026-06-09 12:14:52', '2026-06-09 12:14:52'),
(18, 101, '2026-06-09 12:19:45', '2026-06-09 12:19:45'),
(19, 103, '2026-06-09 12:23:07', '2026-06-09 12:23:07'),
(20, 105, '2026-06-09 12:25:38', '2026-06-09 12:25:38'),
(21, 107, '2026-06-09 12:27:16', '2026-06-09 12:27:16'),
(22, 129, '2026-06-11 14:35:49', '2026-06-11 14:35:49'),
(23, 137, '2026-06-21 16:13:23', '2026-06-21 16:13:23'),
(24, 138, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(25, 139, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(26, 140, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(27, 141, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(28, 142, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(29, 143, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(30, 144, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(31, 145, '2026-06-26 18:53:40', '2026-06-26 18:53:40'),
(32, 148, '2026-07-05 12:29:12', '2026-07-05 12:29:12');

-- --------------------------------------------------------

--
-- Table structure for table `parent_students`
--

CREATE TABLE `parent_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `relationship` enum('father','mother','guardian') NOT NULL DEFAULT 'father',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parent_students`
--

INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `relationship`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'guardian', '2026-06-09 08:36:58', '2026-06-09 08:36:58'),
(2, 2, 2, 'guardian', '2026-06-09 08:40:51', '2026-06-09 08:40:51'),
(3, 3, 3, 'guardian', '2026-06-09 08:46:01', '2026-06-09 08:46:01'),
(4, 4, 4, 'guardian', '2026-06-09 08:49:55', '2026-06-09 08:49:55'),
(5, 5, 5, 'guardian', '2026-06-09 09:02:31', '2026-06-09 09:02:31'),
(6, 6, 6, 'guardian', '2026-06-09 09:05:33', '2026-06-09 09:05:33'),
(7, 7, 7, 'guardian', '2026-06-09 09:11:28', '2026-06-09 09:11:28'),
(9, 9, 8, 'guardian', '2026-06-09 09:26:40', '2026-06-09 09:26:40'),
(10, 10, 9, 'guardian', '2026-06-09 09:35:12', '2026-06-09 09:35:12'),
(11, 11, 10, 'guardian', '2026-06-09 09:37:53', '2026-06-09 09:37:53'),
(12, 12, 11, 'guardian', '2026-06-09 11:14:09', '2026-06-09 11:14:09'),
(13, 13, 12, 'guardian', '2026-06-09 11:16:58', '2026-06-09 11:16:58'),
(14, 14, 13, 'guardian', '2026-06-09 11:19:21', '2026-06-09 11:19:21'),
(15, 15, 14, 'guardian', '2026-06-09 11:31:50', '2026-06-09 11:31:50'),
(16, 16, 16, 'guardian', '2026-06-09 12:10:16', '2026-06-09 12:10:16'),
(17, 17, 17, 'guardian', '2026-06-09 12:14:52', '2026-06-09 12:14:52'),
(18, 18, 18, 'guardian', '2026-06-09 12:19:45', '2026-06-09 12:19:45'),
(19, 19, 19, 'guardian', '2026-06-09 12:23:07', '2026-06-09 12:23:07'),
(20, 20, 20, 'guardian', '2026-06-09 12:25:38', '2026-06-09 12:25:38'),
(21, 21, 21, 'guardian', '2026-06-09 12:27:16', '2026-06-09 12:27:16'),
(22, 22, 22, 'guardian', '2026-06-11 14:35:49', '2026-06-11 14:35:49'),
(24, 23, 28, 'mother', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(25, 24, 29, 'father', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(26, 25, 10, 'mother', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(27, 26, 24, 'father', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(28, 27, 25, 'mother', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(29, 28, 26, 'mother', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(30, 29, 27, 'father', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(31, 30, 23, 'mother', '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(32, 31, 28, 'father', '2026-06-26 18:53:40', '2026-06-26 18:53:40'),
(33, 32, 31, '', '2026-07-05 12:29:30', '2026-07-05 12:29:30');

-- --------------------------------------------------------

--
-- Table structure for table `performance_reports`
--

CREATE TABLE `performance_reports` (
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `report_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` enum('academic','behavioral') NOT NULL DEFAULT 'academic',
  `attendance_rate` decimal(5,2) NOT NULL,
  `average_grade` decimal(5,2) NOT NULL,
  `recommendations` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_reports`
--

INSERT INTO `performance_reports` (`report_id`, `report_request_id`, `student_id`, `report_type`, `attendance_rate`, `average_grade`, `recommendations`, `generated_at`, `created_at`, `updated_at`) VALUES
(13, 9, 7, 'behavioral', 0.00, 0.00, 'جيد لا بئس به', '2026-06-09 18:49:01', '2026-06-09 18:49:01', '2026-06-09 18:49:01'),
(14, 10, 7, 'behavioral', 0.00, 0.00, 'ممتاز', '2026-06-11 14:15:43', '2026-06-11 14:15:43', '2026-06-11 14:15:43'),
(15, 11, 22, 'behavioral', 0.00, 0.00, 'ممتازة', '2026-06-11 14:30:53', '2026-06-11 14:30:53', '2026-06-11 14:30:53');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 6, 'auth_token', '8bac2f1f461cdafd7c4845b9fe6fb4fce51d7c83d903eee41a7bfc04aa19bfb8', '[\"*\"]', '2026-05-09 08:56:03', NULL, '2026-05-09 08:55:23', '2026-05-09 08:56:03'),
(2, 'App\\Models\\User', 6, 'auth_token', '197cc8d4b46b18bf79be5453eef5a5ed604a78f70a85a9ef5bc31148c0e28f52', '[\"*\"]', '2026-05-09 09:14:35', NULL, '2026-05-09 09:14:31', '2026-05-09 09:14:35'),
(3, 'App\\Models\\User', 5, 'auth_token', 'cfe3002322f100f58389429a9dea0adf1948bc0ff5c3dbb733db24988f201370', '[\"*\"]', '2026-05-09 09:33:26', NULL, '2026-05-09 09:29:35', '2026-05-09 09:33:26'),
(4, 'App\\Models\\User', 5, 'auth_token', '2e543dc4207518c6259003a5623945d5f3cad38299899eded8683bef846d984c', '[\"*\"]', '2026-05-09 09:41:22', NULL, '2026-05-09 09:41:08', '2026-05-09 09:41:22'),
(5, 'App\\Models\\User', 5, 'auth_token', '81eb0312c5388d5b86571a5b6bd603533007db247146196e6751a49e6f500426', '[\"*\"]', '2026-05-09 09:47:53', NULL, '2026-05-09 09:46:54', '2026-05-09 09:47:53'),
(8, 'App\\Models\\User', 5, 'auth_token', '3a14cbcff8c49842d20a627c7905542bef331827eff20dba9215560bbcf9f285', '[\"*\"]', '2026-05-09 09:59:53', NULL, '2026-05-09 09:59:16', '2026-05-09 09:59:53'),
(9, 'App\\Models\\User', 5, 'auth_token', '7eac989a8f26bd1e304988bd2301c80c1fa868b9fbb455f880aae6d72794bd53', '[\"*\"]', '2026-05-09 10:00:38', NULL, '2026-05-09 10:00:37', '2026-05-09 10:00:38'),
(10, 'App\\Models\\User', 5, 'auth_token', '56858621f63afff104781476a6c74260b277478ae54f7f04638a368f5a53e54b', '[\"*\"]', '2026-05-09 10:05:30', NULL, '2026-05-09 10:03:15', '2026-05-09 10:05:30'),
(11, 'App\\Models\\User', 5, 'auth_token', '45092eb58d468e88e957e86cb5e4633531d28cda47347b8a3239299c86940298', '[\"*\"]', '2026-05-09 10:08:39', NULL, '2026-05-09 10:08:37', '2026-05-09 10:08:39'),
(12, 'App\\Models\\User', 5, 'auth_token', '10505e37cb14948ce1a781e013cdef8006407f5cdb097e568339b842a389c694', '[\"*\"]', '2026-05-09 10:17:19', NULL, '2026-05-09 10:17:17', '2026-05-09 10:17:19'),
(13, 'App\\Models\\User', 5, 'auth_token', '0ee80413b7d85c797a04f355d25657bea6fc8f82b30faa97a0a1313568908363', '[\"*\"]', '2026-05-09 10:17:39', NULL, '2026-05-09 10:17:37', '2026-05-09 10:17:39'),
(14, 'App\\Models\\User', 5, 'auth_token', '50bbab217605235557a76850a166c7589c6cd19e579a847c3e8b355dfac18d1c', '[\"*\"]', '2026-05-09 10:18:05', NULL, '2026-05-09 10:18:04', '2026-05-09 10:18:05'),
(15, 'App\\Models\\User', 5, 'auth_token', '4c232d25c0317b81651e25b3a2cf380aeb0fc57e71d281c2b9986c16a74cde77', '[\"*\"]', '2026-05-09 11:54:17', NULL, '2026-05-09 11:52:45', '2026-05-09 11:54:17'),
(16, 'App\\Models\\User', 6, 'auth_token', '35118e6b708cb914a50d99d46aaf7a22a48ea1d6dd39d510bfe308ab4bcd4145', '[\"*\"]', '2026-05-09 11:55:34', NULL, '2026-05-09 11:55:00', '2026-05-09 11:55:34'),
(17, 'App\\Models\\User', 6, 'auth_token', '0c01e76b00692bc911a7a0f05cc7a59a4b1b2ed029f9519fe102f56c23ad925c', '[\"*\"]', '2026-05-09 12:00:16', NULL, '2026-05-09 11:59:09', '2026-05-09 12:00:16'),
(19, 'App\\Models\\User', 2, 'auth_token', 'f19d4f82092e00d936395d64704d1d51989810a353abb3979245ede7d11a7913', '[\"*\"]', '2026-05-10 00:12:25', NULL, '2026-05-10 00:02:31', '2026-05-10 00:12:25'),
(20, 'App\\Models\\User', 2, 'auth_token', '4f565e056bbf5f72be0a8cfdceb0e8db4ed78fe11dd62796f9b1b71c74b58d3f', '[\"*\"]', '2026-05-10 00:26:12', NULL, '2026-05-10 00:25:16', '2026-05-10 00:26:12'),
(21, 'App\\Models\\User', 2, 'auth_token', '315202d10b3909f4686573a0ab55293bef095127a50e063b00384de21d7bacfb', '[\"*\"]', '2026-05-10 00:35:53', NULL, '2026-05-10 00:35:38', '2026-05-10 00:35:53'),
(22, 'App\\Models\\User', 5, 'auth_token', '7a3a7ebffc1ebf1d1d3ab8204c850367daad5c1bf8b2dd274eac052fb046211b', '[\"*\"]', '2026-05-10 02:23:27', NULL, '2026-05-10 02:23:24', '2026-05-10 02:23:27'),
(23, 'App\\Models\\User', 5, 'auth_token', '069bdbba37a16d4356584ef0a80f980e6533ac80f27efedf674d90cbbd383491', '[\"*\"]', '2026-05-10 03:29:07', NULL, '2026-05-10 03:28:06', '2026-05-10 03:29:07'),
(24, 'App\\Models\\User', 5, 'auth_token', '688efab17d3e5591247a1018ec4ddd5beb2ee54bb9006c92554ea9f186288ef1', '[\"*\"]', '2026-05-13 03:39:12', NULL, '2026-05-13 03:37:48', '2026-05-13 03:39:12'),
(25, 'App\\Models\\User', 2, 'auth_token', '3a2f0ebf4ed76c51fba6499e5931329349cbf97ca294c7aeab5628dcb6a90c79', '[\"*\"]', '2026-05-13 03:40:28', NULL, '2026-05-13 03:39:46', '2026-05-13 03:40:28'),
(26, 'App\\Models\\User', 2, 'auth_token', '332ec1b4e8b07ca49c534c1fba8ca8a2dc7774e31a3fd16a20dd630c372e8a60', '[\"*\"]', NULL, NULL, '2026-05-13 03:53:13', '2026-05-13 03:53:13'),
(27, 'App\\Models\\User', 2, 'auth_token', '38833cce1db6071b75afdf9580a9b84d6bc893204cb52686a9fbdf7d9f1efc8a', '[\"*\"]', '2026-05-16 14:38:29', NULL, '2026-05-16 14:38:15', '2026-05-16 14:38:29'),
(28, 'App\\Models\\User', 5, 'auth_token', '64c1a605f92ad62b4f1386cd261c2cab745067a04ce470a8940d110a11c76057', '[\"*\"]', '2026-05-17 07:12:34', NULL, '2026-05-17 07:11:18', '2026-05-17 07:12:34'),
(30, 'App\\Models\\User', 6, 'auth_token', '29f5fef50db7204747d4ceb6b228b19814e8bee261a4a92b74bb8a11deec552e', '[\"*\"]', '2026-05-17 07:16:42', NULL, '2026-05-17 07:15:59', '2026-05-17 07:16:42'),
(31, 'App\\Models\\User', 5, 'auth_token', '68484ca15a253ad43fdc962d453f3ed79b98404718d4dfccc608d81c6446e63a', '[\"*\"]', '2026-05-17 07:35:54', NULL, '2026-05-17 07:35:29', '2026-05-17 07:35:54'),
(33, 'App\\Models\\User', 2, 'auth_token', '7582c05b08a2d3ae2b7203ff01541fd05f69370fd7b456af0a9b512a416522df', '[\"*\"]', NULL, NULL, '2026-05-17 07:38:00', '2026-05-17 07:38:00'),
(38, 'App\\Models\\User', 5, 'auth_token', 'c24f4420cc1a8b9e0b8eb413c448491f20a3dbd2f3c5924b16ac08752acb91f4', '[\"*\"]', '2026-05-17 08:03:33', NULL, '2026-05-17 08:03:06', '2026-05-17 08:03:33'),
(41, 'App\\Models\\User', 5, 'auth_token', '4d412eead79bd50291b8521274c2ef801c994545ded26371bded453b63bbc039', '[\"*\"]', '2026-05-18 20:06:12', NULL, '2026-05-18 20:05:50', '2026-05-18 20:06:12'),
(43, 'App\\Models\\User', 5, 'auth_token', '12f8781647684d05b99ce57842e970701398d60338ba0f337f972f14ba27edb1', '[\"*\"]', '2026-05-18 20:11:14', NULL, '2026-05-18 20:10:38', '2026-05-18 20:11:14'),
(45, 'App\\Models\\User', 5, 'auth_token', '25b300a0fafa2f05cd94b086da6368f1b108eeba905ff5d974d92bc53ad27dde', '[\"*\"]', '2026-05-18 21:12:18', NULL, '2026-05-18 21:11:26', '2026-05-18 21:12:18'),
(46, 'App\\Models\\User', 5, 'auth_token', '4e73b282c8406c980a9c32b26cdffae721f2fffc545332077d6af60ee22bbf31', '[\"*\"]', '2026-05-18 21:20:09', NULL, '2026-05-18 21:15:11', '2026-05-18 21:20:09'),
(48, 'App\\Models\\User', 5, 'auth_token', '5c79cf7b3aa77730642f6ff2544a435e6c03ff96b35f5b545f730b2d2caa4ee7', '[\"*\"]', '2026-05-18 21:21:25', NULL, '2026-05-18 21:20:52', '2026-05-18 21:21:25'),
(49, 'App\\Models\\User', 5, 'auth_token', 'bdde4978824d119ca704a27d80ed0ce9d071ef262517096e0116eb930b1acffe', '[\"*\"]', '2026-05-18 21:39:37', NULL, '2026-05-18 21:24:42', '2026-05-18 21:39:37'),
(54, 'App\\Models\\User', 5, 'auth_token', '0ab90fc5b5aa27a5eb705569245615df677a44517048c208e2a3fc3fdc2a87af', '[\"*\"]', '2026-05-18 21:47:31', NULL, '2026-05-18 21:47:08', '2026-05-18 21:47:31'),
(56, 'App\\Models\\User', 5, 'auth_token', 'c79e2b08863f271dd1e11f8e264bd42ba19d53d6f73efb49c8ba6c6cded12a40', '[\"*\"]', '2026-05-18 21:55:47', NULL, '2026-05-18 21:55:42', '2026-05-18 21:55:47'),
(58, 'App\\Models\\User', 5, 'auth_token', 'da1b2da2d07ace1966ecfc177148514cc4838d25b27e2c30c016741c359b5395', '[\"*\"]', '2026-05-18 22:02:28', NULL, '2026-05-18 22:02:17', '2026-05-18 22:02:28'),
(59, 'App\\Models\\User', 5, 'auth_token', '3591a7e058a5cd8dd519a785b98e06f7ab01140c0d60f4396154a4b23b2dca81', '[\"*\"]', '2026-05-18 22:32:16', NULL, '2026-05-18 22:31:41', '2026-05-18 22:32:16'),
(60, 'App\\Models\\User', 5, 'auth_token', '444699e49e9997cee5d44d6ff8368f0c9d38fc4b6f84af76cf0b6c1972f53607', '[\"*\"]', '2026-05-18 22:33:26', NULL, '2026-05-18 22:32:49', '2026-05-18 22:33:26'),
(61, 'App\\Models\\User', 5, 'auth_token', '575cec0d5e69c508a9e4524391c3e847e521799746647de5a3f760220e7ae9d9', '[\"*\"]', '2026-05-18 22:37:23', NULL, '2026-05-18 22:34:09', '2026-05-18 22:37:23'),
(64, 'App\\Models\\User', 5, 'auth_token', '03927cfa32c71e269c19816d2c00ad6f277cc9b3a61ae08faf6d3be4aff8da59', '[\"*\"]', '2026-05-18 22:45:26', NULL, '2026-05-18 22:44:37', '2026-05-18 22:45:26'),
(66, 'App\\Models\\User', 5, 'auth_token', 'fc9f36440c5ed7b79118f421c51e0d71369bf34d34cd97ac851133d1924f481b', '[\"*\"]', '2026-05-18 22:47:50', NULL, '2026-05-18 22:47:38', '2026-05-18 22:47:50'),
(71, 'App\\Models\\User', 2, 'auth_token', 'e942c4a333d876935038d174f7978e6ac4bfea20cd91906c568ee4a1635244c6', '[\"*\"]', '2026-05-19 02:29:23', NULL, '2026-05-19 02:27:39', '2026-05-19 02:29:23'),
(72, 'App\\Models\\User', 5, 'auth_token', 'cc2416ebc2aa82e8755b8313d6307dc704d6ea9911742562c27594365f40c11d', '[\"*\"]', '2026-05-19 02:30:03', NULL, '2026-05-19 02:29:44', '2026-05-19 02:30:03'),
(74, 'App\\Models\\User', 5, 'auth_token', 'ef902d129a0d8c62416bece8d4d4863d8f601769de66d03e32bb1640f97e2927', '[\"*\"]', '2026-05-19 02:43:48', NULL, '2026-05-19 02:35:16', '2026-05-19 02:43:48'),
(80, 'App\\Models\\User', 2, 'auth_token', 'b2f939d509e791d2f11d155a9192d00ce1b1e88da479f5a9bf8eb436ce4d47f0', '[\"*\"]', '2026-05-19 04:52:10', NULL, '2026-05-19 04:48:46', '2026-05-19 04:52:10'),
(81, 'App\\Models\\User', 2, 'auth_token', '8771de9672c4996bf47d69abedbad0b64d5301f8db215f70da4d3ae3b0066e33', '[\"*\"]', '2026-05-19 05:00:24', NULL, '2026-05-19 04:58:51', '2026-05-19 05:00:24'),
(85, 'App\\Models\\User', 5, 'auth_token', '0662969e863cdf72e242e9f20699d1b2820ccee7c592bb8804dcbebb095e9d90', '[\"*\"]', '2026-05-19 05:25:48', NULL, '2026-05-19 05:20:03', '2026-05-19 05:25:48'),
(87, 'App\\Models\\User', 5, 'auth_token', '5fe645399692f135cc6a8d51412cd50d75d0bd9f8a9f87aa3eaa72ee616a3dfc', '[\"*\"]', '2026-05-19 05:33:28', NULL, '2026-05-19 05:29:12', '2026-05-19 05:33:28'),
(89, 'App\\Models\\User', 2, 'auth_token', '46cab9e1c5a8eaeb3553de7e220917559cfc6c8a62a1d21701be3c15185c15a3', '[\"*\"]', '2026-05-19 05:43:20', NULL, '2026-05-19 05:38:34', '2026-05-19 05:43:20'),
(90, 'App\\Models\\User', 10, 'auth_token', 'ff3745fb3845e292ea9918a0e577e4147dfb4898516d38a40c6ab9c39e5c70d3', '[\"*\"]', '2026-05-19 05:43:46', NULL, '2026-05-19 05:43:38', '2026-05-19 05:43:46'),
(92, 'App\\Models\\User', 5, 'auth_token', '151e9d86660e25378ec261b4451737974d5645d4baf36f78eeba675de212fe79', '[\"*\"]', '2026-05-21 03:04:21', NULL, '2026-05-21 02:42:18', '2026-05-21 03:04:21'),
(95, 'App\\Models\\User', 5, 'auth_token', 'aeb3f61f34fad75a0121e720c6de5519df6fa38be31f375cf1821ef7d9026416', '[\"*\"]', '2026-05-21 04:25:18', NULL, '2026-05-21 04:25:17', '2026-05-21 04:25:18'),
(96, 'App\\Models\\User', 2, 'auth_token', 'ee2865399a521844fc8350bf9b8bba84ba247db728723e6c00a8e557c08c8902', '[\"*\"]', '2026-05-21 04:28:19', NULL, '2026-05-21 04:28:19', '2026-05-21 04:28:19'),
(97, 'App\\Models\\User', 2, 'auth_token', 'bc6ab256b302ab1646b713e0692b35f334c376a99f4abf738f17a32db3aba694', '[\"*\"]', '2026-05-21 04:32:12', NULL, '2026-05-21 04:32:11', '2026-05-21 04:32:12'),
(98, 'App\\Models\\User', 5, 'auth_token', '6274b21eb6e93c5e285597ceb336d462a23dff78d220cb58f41963e2b4b1fc76', '[\"*\"]', '2026-05-21 04:40:47', NULL, '2026-05-21 04:40:46', '2026-05-21 04:40:47'),
(99, 'App\\Models\\User', 2, 'auth_token', 'e389dc04b7ae18fe3d123be3b73eb63fc0f5ae0ba9e1e6d8fe9cedf8a92308cf', '[\"*\"]', '2026-05-21 04:46:36', NULL, '2026-05-21 04:41:31', '2026-05-21 04:46:36'),
(100, 'App\\Models\\User', 5, 'auth_token', '25be4f9d8b5c733a9d3a1cc3e8bb7bedd6bf21f4b7024d3b0b6b4144e5d3ebe1', '[\"*\"]', '2026-05-21 04:52:17', NULL, '2026-05-21 04:52:16', '2026-05-21 04:52:17'),
(101, 'App\\Models\\User', 5, 'auth_token', 'bc8d26bc8416e72664f0c366fd6ba49e5bcdb9dcb7881eb92e8af987d7b1f6d8', '[\"*\"]', '2026-05-21 04:57:23', NULL, '2026-05-21 04:57:22', '2026-05-21 04:57:23'),
(102, 'App\\Models\\User', 2, 'auth_token', 'cbd7c19338390bf7633134dc78212be0182d27419da3925da8cf9843992b9226', '[\"*\"]', '2026-05-21 04:58:04', NULL, '2026-05-21 04:58:04', '2026-05-21 04:58:04'),
(103, 'App\\Models\\User', 5, 'auth_token', 'ad4aaff6c993aed7909c2071867de315a3146b10a5ab96003971848e2217be75', '[\"*\"]', '2026-05-21 05:02:41', NULL, '2026-05-21 05:02:40', '2026-05-21 05:02:41'),
(104, 'App\\Models\\User', 5, 'auth_token', '96b54209d288f0c1daa6271e45f1eabc97920116e20c23b7d6bd4e69118c880c', '[\"*\"]', '2026-05-21 05:09:08', NULL, '2026-05-21 05:07:34', '2026-05-21 05:09:08'),
(105, 'App\\Models\\User', 5, 'auth_token', 'cdac92a23009c4bfce80fc8b7eea41ad677c727bcc95c0f0931e18a9255e867c', '[\"*\"]', '2026-05-21 05:30:55', NULL, '2026-05-21 05:15:27', '2026-05-21 05:30:55'),
(106, 'App\\Models\\User', 5, 'auth_token', 'e357ecb3f1822d4b620308ea30565cdba487564ab03af91e5e1d5f36327183e1', '[\"*\"]', '2026-05-21 05:42:24', NULL, '2026-05-21 05:33:40', '2026-05-21 05:42:24'),
(107, 'App\\Models\\User', 5, 'auth_token', 'a4ec20586b543c1dd04021e69b8942401636036f4a866843b92d2d2584a2a526', '[\"*\"]', '2026-05-21 06:02:39', NULL, '2026-05-21 05:46:56', '2026-05-21 06:02:39'),
(108, 'App\\Models\\User', 5, 'auth_token', '01a615b90d57be327a498f128f18dfab59f888870866a6ceb91d37d28128f3b9', '[\"*\"]', '2026-05-21 06:17:16', NULL, '2026-05-21 06:11:06', '2026-05-21 06:17:16'),
(109, 'App\\Models\\User', 5, 'auth_token', '583fa2e5d65df28dabc66107722c762369062628a5d0bef243060cb4292e80c7', '[\"*\"]', '2026-05-21 06:16:05', NULL, '2026-05-21 06:15:54', '2026-05-21 06:16:05'),
(110, 'App\\Models\\User', 5, 'auth_token', '6b333a1ab7de9413632716fcd71aaa993d98ca3b507a3b0e885c445d4fbf50d3', '[\"*\"]', '2026-05-21 06:21:12', NULL, '2026-05-21 06:17:57', '2026-05-21 06:21:12'),
(111, 'App\\Models\\User', 10, 'auth_token', '99ca05e8a16e2de013db641abb951ab44eeff44bab924e7555d48e78ca6db2f4', '[\"*\"]', '2026-05-21 06:23:06', NULL, '2026-05-21 06:21:29', '2026-05-21 06:23:06'),
(112, 'App\\Models\\User', 5, 'auth_token', '25b3e60587b4a8ca47e747718f1b07ce37e77bc80da65462d75c782d06879b9e', '[\"*\"]', '2026-05-21 06:23:49', NULL, '2026-05-21 06:23:27', '2026-05-21 06:23:49'),
(114, 'App\\Models\\User', 5, 'auth_token', 'bb1d52b5f88f2bc9922db482199fdd08f2b80155b55bbbae8ab39321ada0e8d4', '[\"*\"]', '2026-05-21 06:34:24', NULL, '2026-05-21 06:34:12', '2026-05-21 06:34:24'),
(115, 'App\\Models\\User', 5, 'auth_token', 'b49b8db18db1f836092bc43f85774c8ad2f2f722ff4b926ccb3f46383f176bb4', '[\"*\"]', '2026-05-21 07:05:29', NULL, '2026-05-21 07:05:23', '2026-05-21 07:05:29'),
(117, 'App\\Models\\User', 5, 'auth_token', '80a70a3b6e881cf942dcb12645406fd99313870a24682a158bb224e33435e8b1', '[\"*\"]', '2026-05-21 07:06:59', NULL, '2026-05-21 07:06:12', '2026-05-21 07:06:59'),
(120, 'App\\Models\\User', 5, 'auth_token', '3370829a067c40ef48003f51178ee8c2be92f9d91c5e1ede83c2b5fb9079af45', '[\"*\"]', '2026-05-21 07:25:31', NULL, '2026-05-21 07:25:13', '2026-05-21 07:25:31'),
(127, 'App\\Models\\User', 5, 'auth_token', '600242a0b674e19d5220510bb36b3ff1a25fc9d441debc584419a0c8192132cf', '[\"*\"]', '2026-05-21 07:55:37', NULL, '2026-05-21 07:54:04', '2026-05-21 07:55:37'),
(133, 'App\\Models\\User', 5, 'auth_token', '22f8f0c67a4ba3b1a737b365de010583e9f890884d8a5083b61ef178d4a4b776', '[\"*\"]', '2026-05-21 08:28:59', NULL, '2026-05-21 08:28:55', '2026-05-21 08:28:59'),
(134, 'App\\Models\\User', 5, 'auth_token', '508fb094d50f8bd53559f7c85a6e654cb86407cbb4d46cecf18693ea9761653c', '[\"*\"]', '2026-05-21 08:33:35', NULL, '2026-05-21 08:31:09', '2026-05-21 08:33:35'),
(137, 'App\\Models\\User', 5, 'auth_token', 'a989a96c3e8db9d5e2f11ee4d07d616f6c97fec71531a26ec8c5d1ed5eea5f9f', '[\"*\"]', '2026-05-21 08:40:36', NULL, '2026-05-21 08:34:56', '2026-05-21 08:40:36'),
(145, 'App\\Models\\User', 5, 'auth_token', 'd9cf0d7cc8733b14685a82bec3893248f1162feeaca5a2956c5503afb8e745a4', '[\"*\"]', '2026-05-21 09:42:00', NULL, '2026-05-21 09:41:22', '2026-05-21 09:42:00'),
(147, 'App\\Models\\User', 5, 'auth_token', 'e891ec5ca36942752333a12929652ca271b323c5b9700ca1f1e4cb2b1482e7fc', '[\"*\"]', '2026-05-21 09:44:04', NULL, '2026-05-21 09:43:38', '2026-05-21 09:44:04'),
(154, 'App\\Models\\User', 2, 'auth_token', '37bd4477d52714ac82e828d7227c6df462255aeddad605b6fdbaed28be1395a2', '[\"*\"]', '2026-05-21 13:57:39', NULL, '2026-05-21 13:57:22', '2026-05-21 13:57:39'),
(156, 'App\\Models\\User', 2, 'auth_token', '1fb2280dd40bb3168c7c89c79c8052adef8162b51cde6a261409401007b199ee', '[\"*\"]', '2026-05-21 14:08:58', NULL, '2026-05-21 14:08:46', '2026-05-21 14:08:58'),
(160, 'App\\Models\\User', 2, 'auth_token', '0545f937a68d75276f2fc1543faf07878921cc19a09f01bea82e3efd9e46bd9f', '[\"*\"]', '2026-05-23 09:15:07', NULL, '2026-05-23 09:14:37', '2026-05-23 09:15:07'),
(163, 'App\\Models\\User', 2, 'auth_token', '2d04ed47ffb45a1bd8aa6b7bb4d5d81b569dd61e39b9caa31f169ac18370b679', '[\"*\"]', '2026-05-23 09:26:53', NULL, '2026-05-23 09:23:59', '2026-05-23 09:26:53'),
(164, 'App\\Models\\User', 2, 'auth_token', 'e055e96b2305cb35a04f047904ca54143bb270c309a67932d6ed019642f15c7f', '[\"*\"]', '2026-05-23 09:30:41', NULL, '2026-05-23 09:30:22', '2026-05-23 09:30:41'),
(166, 'App\\Models\\User', 5, 'auth_token', 'e8ed75fea3af70b73d1de2469375393ce9e9090d9c7a85f75e0961e822d7b7b1', '[\"*\"]', '2026-05-23 09:31:19', NULL, '2026-05-23 09:31:18', '2026-05-23 09:31:19'),
(170, 'App\\Models\\User', 2, 'auth_token', '42ae023807d7f03b601fb3da9863b2525e547d3ba8102eef8cc16b408e8185d5', '[\"*\"]', '2026-05-23 09:55:48', NULL, '2026-05-23 09:43:35', '2026-05-23 09:55:48'),
(172, 'App\\Models\\User', 2, 'auth_token', 'c318163286bf2128c2bfbe90c8b37f5722e940ce76ed962a9e35b66965470f80', '[\"*\"]', '2026-05-23 10:04:45', NULL, '2026-05-23 10:04:06', '2026-05-23 10:04:45'),
(173, 'App\\Models\\User', 10, 'auth_token', '44e5f23e1a2cab038b8f7b9d70d89a40665f7760b272eda5880746adc8ea5f47', '[\"*\"]', '2026-05-23 10:05:24', NULL, '2026-05-23 10:05:12', '2026-05-23 10:05:24'),
(174, 'App\\Models\\User', 2, 'auth_token', 'fd4ddd099f99c78095f38617fbc17e7656f790ae580d714dc3576abbcab128e6', '[\"*\"]', '2026-05-23 10:34:56', NULL, '2026-05-23 10:33:40', '2026-05-23 10:34:56'),
(175, 'App\\Models\\User', 5, 'auth_token', 'd3e533043cb0921463fdeda4fd891cd20ea8bcd9190915a6098ac966cd0b3219', '[\"*\"]', '2026-05-23 10:37:11', NULL, '2026-05-23 10:36:31', '2026-05-23 10:37:11'),
(176, 'App\\Models\\User', 2, 'auth_token', '626800b6e8efab7f987d390e222cb66e606c01f6c687052c2ded232054e1c342', '[\"*\"]', '2026-05-23 10:55:22', NULL, '2026-05-23 10:54:35', '2026-05-23 10:55:22'),
(178, 'App\\Models\\User', 10, 'auth_token', 'aa94498d47858bc68c33343284737856ee02291f6f0126cee21be07dcbd888a5', '[\"*\"]', '2026-05-23 10:56:03', NULL, '2026-05-23 10:56:00', '2026-05-23 10:56:03'),
(179, 'App\\Models\\User', 5, 'auth_token', 'eeff1029fcc72cc66a6ae7f2b951484502cc112f892e2c4b50b1998b271d7287', '[\"*\"]', '2026-05-23 13:57:59', NULL, '2026-05-23 13:57:55', '2026-05-23 13:57:59'),
(180, 'App\\Models\\User', 10, 'auth_token', '5599e49170a3f9f4aaefe230d240c9016b59181111d78c0d74f95439e0c57eab', '[\"*\"]', '2026-05-23 13:58:30', NULL, '2026-05-23 13:58:13', '2026-05-23 13:58:30'),
(181, 'App\\Models\\User', 2, 'auth_token', 'fb6d4d4e973630ec43a18fd558dd9488ad73bd08ad35c6716b5ec8b922f5c54b', '[\"*\"]', '2026-05-23 13:59:27', NULL, '2026-05-23 13:58:52', '2026-05-23 13:59:27'),
(182, 'App\\Models\\User', 5, 'auth_token', '62ccdfafa4b3d03b31708c47618360aecfd63816a4438b955923664bba2e2c77', '[\"*\"]', '2026-05-23 14:06:59', NULL, '2026-05-23 14:01:43', '2026-05-23 14:06:59'),
(183, 'App\\Models\\User', 10, 'auth_token', 'd995746283e2aa70b3a3f93b8783c8910e4e3305dec50d18dc180c719603119d', '[\"*\"]', '2026-05-23 14:21:20', NULL, '2026-05-23 14:21:02', '2026-05-23 14:21:20'),
(184, 'App\\Models\\User', 2, 'auth_token', '0e1211c78cf9c415c74298c6353f441e3ea0a33a05a651243e358a66a1f6586a', '[\"*\"]', '2026-05-23 14:21:58', NULL, '2026-05-23 14:21:50', '2026-05-23 14:21:58'),
(185, 'App\\Models\\User', 2, 'auth_token', '9307421f10aab28548e472cd0a51c84d2a3e1b61198b5df6077ae5abe6938d99', '[\"*\"]', '2026-05-23 14:24:08', NULL, '2026-05-23 14:24:01', '2026-05-23 14:24:08'),
(186, 'App\\Models\\User', 2, 'auth_token', 'e584cb957cc8e295ca1c6cee05295ddc8036dced53774d94a3ed1d19569b51b0', '[\"*\"]', '2026-05-23 14:35:12', NULL, '2026-05-23 14:31:30', '2026-05-23 14:35:12'),
(187, 'App\\Models\\User', 2, 'auth_token', '9969ff285172155abfe4b213d0752405a202e52f3a6af9a2c15da0e0b8097040', '[\"*\"]', '2026-05-23 14:40:03', NULL, '2026-05-23 14:36:38', '2026-05-23 14:40:03'),
(188, 'App\\Models\\User', 5, 'auth_token', 'fe92db90323d94c11ee9ff35bddb6d31144fc452c308d2ce7e09bbc0253db8e0', '[\"*\"]', '2026-05-23 14:40:39', NULL, '2026-05-23 14:40:21', '2026-05-23 14:40:39'),
(189, 'App\\Models\\User', 5, 'auth_token', '2409048cee711264e5338b3b2c57d544b9ef2efe74009839e02573eab26e0274', '[\"*\"]', '2026-05-23 14:45:04', NULL, '2026-05-23 14:41:15', '2026-05-23 14:45:04'),
(193, 'App\\Models\\User', 2, 'auth_token', '5526d768fb6d7bdbc65e790b8a5ba25a2c657d7dc68303448092d0f007fcf466', '[\"*\"]', '2026-05-23 15:09:35', NULL, '2026-05-23 15:08:30', '2026-05-23 15:09:35'),
(195, 'App\\Models\\User', 5, 'auth_token', 'e7e5eda43fb4b7d9c01e1248a653234691249664a963984ea6ddb5278f1b855f', '[\"*\"]', '2026-05-23 15:12:08', NULL, '2026-05-23 15:11:18', '2026-05-23 15:12:08'),
(197, 'App\\Models\\User', 5, 'auth_token', '72d6bf32eac754ad82c00cdfa2fe20d5c925d5197ed9e423ee28493bb1bf3eaf', '[\"*\"]', '2026-05-23 16:00:24', NULL, '2026-05-23 15:58:57', '2026-05-23 16:00:24'),
(198, 'App\\Models\\User', 2, 'auth_token', '0fa8035ecbe7fcce4c1b6fb8c8b0b46e55678cb20a8bd5b5ae6755fad8ceb86a', '[\"*\"]', '2026-05-23 16:04:10', NULL, '2026-05-23 16:00:40', '2026-05-23 16:04:10'),
(202, 'App\\Models\\User', 5, 'auth_token', 'db1349164e003288c831f18cd5fd51e12985eef35f52fafe68507ec9f39cfb7e', '[\"*\"]', '2026-05-23 16:43:28', NULL, '2026-05-23 16:43:02', '2026-05-23 16:43:28'),
(204, 'App\\Models\\User', 2, 'auth_token', '41277a606a1f5cf3df4bcf4fca6fc7ba095dcf0e6ceb5a4bff092f09730e368f', '[\"*\"]', '2026-05-23 16:47:58', NULL, '2026-05-23 16:47:39', '2026-05-23 16:47:58'),
(205, 'App\\Models\\User', 5, 'auth_token', '274fd4c945da9d22d0e66743d095695b150d4a267a67a721771df9626b524a94', '[\"*\"]', '2026-05-23 16:48:20', NULL, '2026-05-23 16:48:13', '2026-05-23 16:48:20'),
(206, 'App\\Models\\User', 5, 'auth_token', '0025ad754e24e698840906d0afb5bb262d3563168e79b664fab376d398b188c3', '[\"*\"]', '2026-05-23 16:48:43', NULL, '2026-05-23 16:48:42', '2026-05-23 16:48:43'),
(207, 'App\\Models\\User', 2, 'auth_token', '86137ad0068a44f00e1016dbffb26ca46aad69eb9851c5273f8a1c5fb44fe5d3', '[\"*\"]', '2026-05-23 16:58:41', NULL, '2026-05-23 16:58:29', '2026-05-23 16:58:41'),
(209, 'App\\Models\\User', 2, 'auth_token', '7e8f8c4b4a40f055689eaeb4fdc0e4e57455f74bb779b4d85656d85d2fc21242', '[\"*\"]', '2026-05-23 17:04:48', NULL, '2026-05-23 17:04:47', '2026-05-23 17:04:48'),
(212, 'App\\Models\\User', 2, 'auth_token', '2fcceb3d022688dc486cfd58cff21f73aab8ac57e7cf640e7126cf6da1f08897', '[\"*\"]', '2026-05-23 17:12:01', NULL, '2026-05-23 17:12:00', '2026-05-23 17:12:01'),
(216, 'App\\Models\\User', 2, 'auth_token', '48f26b9866f5fa73343d5ffa8da7556cdd3b79e13db2d696c77072ea3108789e', '[\"*\"]', '2026-05-24 02:40:06', NULL, '2026-05-24 02:39:18', '2026-05-24 02:40:06'),
(223, 'App\\Models\\User', 2, 'auth_token', '72f4553984d9c6d99f61dd7d471e534dff25f3f2d9c46cc0db4d283145637956', '[\"*\"]', '2026-05-24 03:27:38', NULL, '2026-05-24 03:24:07', '2026-05-24 03:27:38'),
(224, 'App\\Models\\User', 5, 'auth_token', 'bbf673c97beffdc6b645a2d2fa656fb5d68c885f30bcd067caec99e04a640efb', '[\"*\"]', '2026-05-24 03:30:42', NULL, '2026-05-24 03:27:11', '2026-05-24 03:30:42'),
(226, 'App\\Models\\User', 5, 'auth_token', '3982bde5297ccd0240cbfd752cf425bde4048f0db89783e44da3034dd60c215d', '[\"*\"]', '2026-05-24 03:33:25', NULL, '2026-05-24 03:31:24', '2026-05-24 03:33:25'),
(227, 'App\\Models\\User', 2, 'auth_token', '90280f4481fd08de960444558bde9e5db49fb9fb1afa4f05e94e3610e5352bf1', '[\"*\"]', '2026-05-24 03:42:29', NULL, '2026-05-24 03:32:29', '2026-05-24 03:42:29'),
(229, 'App\\Models\\User', 5, 'auth_token', '20788f9b1b59428d8138436019f5d9effaefb1990565c0d0b1a78ae036f30acf', '[\"*\"]', '2026-05-24 03:46:02', NULL, '2026-05-24 03:43:52', '2026-05-24 03:46:02'),
(234, 'App\\Models\\User', 5, 'auth_token', 'f662ec2648c41258580650f0e4fcd3ee82c837c07079247fbb42dddb6e687f43', '[\"*\"]', '2026-05-24 04:01:41', NULL, '2026-05-24 04:01:40', '2026-05-24 04:01:41'),
(235, 'App\\Models\\User', 2, 'auth_token', 'dc6a88fb0cc31b8057d9dd4590d4559b5fec2eaab3e635e089082717e6012378', '[\"*\"]', '2026-05-24 04:02:09', NULL, '2026-05-24 04:01:58', '2026-05-24 04:02:09'),
(238, 'App\\Models\\User', 5, 'auth_token', '0d9269037c907d39cc45c71b3cea17857a765d2894ea209e0bebc0499082600b', '[\"*\"]', '2026-05-24 04:16:48', NULL, '2026-05-24 04:13:48', '2026-05-24 04:16:48'),
(239, 'App\\Models\\User', 2, 'auth_token', 'de941de2c507591fea40a4f60e0fe015a02997a1446ee319c3d7d09f986ec547', '[\"*\"]', '2026-05-24 04:54:58', NULL, '2026-05-24 04:54:48', '2026-05-24 04:54:58'),
(241, 'App\\Models\\User', 5, 'auth_token', 'f1c0ffed76b55c63901362e170c9a270e7bc0de86c02807115cebb6d883b99fe', '[\"*\"]', '2026-05-24 04:56:08', NULL, '2026-05-24 04:55:59', '2026-05-24 04:56:08'),
(242, 'App\\Models\\User', 5, 'auth_token', 'f19f8e7d5afbae670faa8d2a2b0fb08d10eab38bc1a1166049c5c0c157c66c01', '[\"*\"]', '2026-05-24 04:59:33', NULL, '2026-05-24 04:57:26', '2026-05-24 04:59:33'),
(243, 'App\\Models\\User', 5, 'auth_token', '103fe5e3005e6f4ad581da5b83964b26fca8d647d2abf304b9bc670e436bdc89', '[\"*\"]', '2026-05-24 05:04:55', NULL, '2026-05-24 05:04:44', '2026-05-24 05:04:55'),
(246, 'App\\Models\\User', 2, 'auth_token', '2f517dfdbce50ea36471e99ca1791e1af4b1b33bf1565cdedb426180c7fa5045', '[\"*\"]', '2026-05-24 05:43:06', NULL, '2026-05-24 05:39:06', '2026-05-24 05:43:06'),
(247, 'App\\Models\\User', 5, 'auth_token', '57626c0b2830704a2451fb722606518097acf48edceea566359ca03b54620725', '[\"*\"]', '2026-05-24 05:46:28', NULL, '2026-05-24 05:45:49', '2026-05-24 05:46:28'),
(248, 'App\\Models\\User', 5, 'auth_token', '7931fee1f60f586d163e7b2a9e64539e95e343a7eb3a5865275b2d72f60edda2', '[\"*\"]', '2026-05-24 05:51:51', NULL, '2026-05-24 05:48:10', '2026-05-24 05:51:51'),
(250, 'App\\Models\\User', 2, 'auth_token', '5231ecd80f13fc04a256308292502f5239d6d3b8352d067dd6b8705f671e0291', '[\"*\"]', '2026-05-24 05:54:10', NULL, '2026-05-24 05:53:55', '2026-05-24 05:54:10'),
(251, 'App\\Models\\User', 5, 'auth_token', 'cd9e6c72a77bd4e5d6a0f283c8d423a1e6c3b3da573f3f02538f2fe48b4bc621', '[\"*\"]', '2026-05-24 05:54:27', NULL, '2026-05-24 05:54:20', '2026-05-24 05:54:27'),
(256, 'App\\Models\\User', 2, 'auth_token', '45b51d8a0c667739c110a304a9d90cf32bbf7079508659920d4064e2d4a356b9', '[\"*\"]', '2026-05-24 06:08:00', NULL, '2026-05-24 05:58:01', '2026-05-24 06:08:00'),
(257, 'App\\Models\\User', 2, 'auth_token', '1db267f2bac3522e28331d097fbc5eb2f0d56697423850b194b89b5cfa504cca', '[\"*\"]', '2026-05-24 06:09:13', NULL, '2026-05-24 06:08:41', '2026-05-24 06:09:13'),
(258, 'App\\Models\\User', 5, 'auth_token', 'f3f98f2889bd5c3465a2ad2a3d5967b5f19269b4e71634079ca77f1c511078ab', '[\"*\"]', '2026-05-24 06:09:54', NULL, '2026-05-24 06:09:42', '2026-05-24 06:09:54'),
(260, 'App\\Models\\User', 2, 'auth_token', 'adc090ba1e3fac79b0768d9368b0ce5b84d30f6212abbd80aa5e627bce79fd11', '[\"*\"]', '2026-05-24 06:18:24', NULL, '2026-05-24 06:15:53', '2026-05-24 06:18:24'),
(261, 'App\\Models\\User', 5, 'auth_token', '67b53b98ab81cde119dc1f0296ca9ba199e1606f2fef8870bdaf6575a0f173e5', '[\"*\"]', '2026-05-24 07:31:12', NULL, '2026-05-24 07:30:41', '2026-05-24 07:31:12'),
(266, 'App\\Models\\User', 5, 'auth_token', '2c989fbcf09a344bf4d8878c6aa78f76fd731c5b3aeb8ce5aec397e282e9ee7b', '[\"*\"]', '2026-05-24 07:39:12', NULL, '2026-05-24 07:35:11', '2026-05-24 07:39:12'),
(267, 'App\\Models\\User', 5, 'auth_token', '789a0e4a1ec1364b09a2ac4f77c3eb59fd2d8083446218ca99ed3fa62a240943', '[\"*\"]', '2026-05-24 07:43:04', NULL, '2026-05-24 07:40:32', '2026-05-24 07:43:04'),
(270, 'App\\Models\\User', 2, 'auth_token', '8c84ed82661e091d3a9da91971737c7ac2b8d3655a8045330e76f2c11a4ea5d5', '[\"*\"]', '2026-05-24 07:47:57', NULL, '2026-05-24 07:43:22', '2026-05-24 07:47:57'),
(272, 'App\\Models\\User', 5, 'auth_token', '6652326d64bea62243dc5ea372ad19ea52575374fecda6fb8184692cff6a4bfe', '[\"*\"]', '2026-05-24 07:54:16', NULL, '2026-05-24 07:48:19', '2026-05-24 07:54:16'),
(273, 'App\\Models\\User', 2, 'auth_token', '7ea1f40a5115b492e0b077f54b809712cd6fb618f7316afa80a2174c93850cb0', '[\"*\"]', '2026-05-24 07:55:48', NULL, '2026-05-24 07:54:31', '2026-05-24 07:55:48'),
(274, 'App\\Models\\User', 5, 'auth_token', '4f0a19a7268125b59cac44a554e47e63af9f94b01c5d29963a63bfb2ef2539c8', '[\"*\"]', '2026-05-24 07:56:22', NULL, '2026-05-24 07:56:12', '2026-05-24 07:56:22'),
(275, 'App\\Models\\User', 5, 'auth_token', 'a9af34d5613582fb0375b7c3aa5e75e761ac65a104fe5fda09a294b926442770', '[\"*\"]', '2026-05-24 07:57:48', NULL, '2026-05-24 07:57:15', '2026-05-24 07:57:48'),
(277, 'App\\Models\\User', 5, 'auth_token', '938d6c3a5a7e6d01fbbcbd0b7e6e5d838003425f75f284c883895b863530f5e1', '[\"*\"]', '2026-05-24 08:03:44', NULL, '2026-05-24 07:58:40', '2026-05-24 08:03:44'),
(279, 'App\\Models\\User', 3, 'auth_token', 'b8b5e7de771dd05ecb696de5e0f2dd68312f99a48592e012b1842d5a2a182562', '[\"*\"]', '2026-05-24 08:02:54', NULL, '2026-05-24 08:00:23', '2026-05-24 08:02:54'),
(281, 'App\\Models\\User', 5, 'auth_token', 'fae506497fb07104ee2c0b619b7063c1c96f3632a69504823123bb7790dc107c', '[\"*\"]', '2026-05-24 08:09:57', NULL, '2026-05-24 08:03:37', '2026-05-24 08:09:57'),
(283, 'App\\Models\\User', 5, 'auth_token', '989855814985c1af34568ca4069b2f793a351effa484f1dea5f9d1d5eb2b9e96', '[\"*\"]', '2026-05-24 14:53:29', NULL, '2026-05-24 14:53:00', '2026-05-24 14:53:29'),
(284, 'App\\Models\\User', 2, 'auth_token', 'e4f9d8984e653cf0ce0292681ed9be1b8610879d8fe6426c67e9913b48300542', '[\"*\"]', '2026-05-24 16:07:00', NULL, '2026-05-24 16:05:54', '2026-05-24 16:07:00'),
(287, 'App\\Models\\User', 5, 'auth_token', 'aa6e7f07d74ade2bad2e50bf863854ab5ed3e2b422229b698e4341c9fd15deca', '[\"*\"]', '2026-05-24 16:17:25', NULL, '2026-05-24 16:11:24', '2026-05-24 16:17:25'),
(288, 'App\\Models\\User', 5, 'auth_token', '452b5e82939a9311ca0457eba4cd008150d5004e90c41321e279dcca32fb144d', '[\"*\"]', '2026-05-24 16:42:39', NULL, '2026-05-24 16:38:26', '2026-05-24 16:42:39'),
(289, 'App\\Models\\User', 2, 'auth_token', 'dcb9a906882df3de16d5815753831eb96e295fdb44cc157ba2bca2ce15b65787', '[\"*\"]', '2026-05-24 16:48:33', NULL, '2026-05-24 16:43:02', '2026-05-24 16:48:33'),
(291, 'App\\Models\\User', 5, 'auth_token', '2b590e1743b0cca5f77b6315eb5ac89f85b99d95e0fe1674543390dc43167e8e', '[\"*\"]', '2026-05-24 16:52:29', NULL, '2026-05-24 16:50:09', '2026-05-24 16:52:29'),
(293, 'App\\Models\\User', 5, 'auth_token', '5c37510e8fff9b540142f20a229c6c92cecc8690e2736bfd9b90467ebe662bd0', '[\"*\"]', '2026-05-24 16:56:16', NULL, '2026-05-24 16:54:46', '2026-05-24 16:56:16'),
(297, 'App\\Models\\User', 5, 'auth_token', 'ecfcd003ba21b825884cf8118cfbed164053a6ffec482a2597df18b32a486533', '[\"*\"]', '2026-05-24 17:08:30', NULL, '2026-05-24 17:07:24', '2026-05-24 17:08:30'),
(298, 'App\\Models\\User', 5, 'auth_token', '80959e7b90b9a4cf45830cc48fd4cc0c075d42b858ff3c170e8a452d1d59e321', '[\"*\"]', '2026-05-24 17:18:22', NULL, '2026-05-24 17:18:17', '2026-05-24 17:18:22'),
(299, 'App\\Models\\User', 5, 'auth_token', 'a3d8fa4fb2f816b4a3cc6a27e51c40d4f2b49725e2dda260cbc984b58864a760', '[\"*\"]', '2026-05-24 17:19:18', NULL, '2026-05-24 17:18:48', '2026-05-24 17:19:18'),
(300, 'App\\Models\\User', 5, 'auth_token', 'a990321a5cb6f041ff8744dd8ddd9554362ae90e1c7ba2aad46c3f2c9cf2e93a', '[\"*\"]', '2026-05-24 17:29:27', NULL, '2026-05-24 17:28:27', '2026-05-24 17:29:27'),
(301, 'App\\Models\\User', 2, 'auth_token', '33cb22fcb7c055a5e4ddc6ef3f12e900cc8e137c79996900e8cc10c52fe5767f', '[\"*\"]', '2026-05-24 17:48:46', NULL, '2026-05-24 17:43:50', '2026-05-24 17:48:46'),
(302, 'App\\Models\\User', 2, 'auth_token', '63ab0045fb10585be8b530c442f03d021574e1baf05897e6f0828dcbf078cd4e', '[\"*\"]', '2026-05-24 18:03:10', NULL, '2026-05-24 18:01:04', '2026-05-24 18:03:10'),
(303, 'App\\Models\\User', 2, 'auth_token', '66f6d33b79003e71715656b33952228ccaff9c1bd9a1df0611021d8e4503e9fd', '[\"*\"]', '2026-05-24 18:15:19', NULL, '2026-05-24 18:14:50', '2026-05-24 18:15:19'),
(306, 'App\\Models\\User', 5, 'auth_token', '1d5486f4993c767773601334a906f413af8c94c0e1b1f466a29d522392aad18c', '[\"*\"]', '2026-05-25 02:57:47', NULL, '2026-05-25 02:55:36', '2026-05-25 02:57:47'),
(309, 'App\\Models\\User', 5, 'auth_token', '2f6dbeab4a21d16d748234008aacc7478c4c6fc63360e38308468d704ac4bb29', '[\"*\"]', '2026-05-25 03:10:16', NULL, '2026-05-25 03:07:46', '2026-05-25 03:10:16'),
(312, 'App\\Models\\User', 5, 'auth_token', 'c9127a4d38d558d2ced88f3fdb92bb2e63bc2227448da1b4740ea1362a06abc5', '[\"*\"]', '2026-05-25 03:23:37', NULL, '2026-05-25 03:23:07', '2026-05-25 03:23:37'),
(314, 'App\\Models\\User', 2, 'auth_token', 'e9294ef989dce2d36350ff8443260c1e05c8b742fd739d47d3ac0647964ba967', '[\"*\"]', '2026-05-25 03:24:58', NULL, '2026-05-25 03:24:26', '2026-05-25 03:24:58'),
(316, 'App\\Models\\User', 2, 'auth_token', '415ac80f86211bd9809b9f2c55fe2722546c6670ddf7f12f9c8eac7fa71da5a2', '[\"*\"]', '2026-05-25 03:40:20', NULL, '2026-05-25 03:40:06', '2026-05-25 03:40:20'),
(317, 'App\\Models\\User', 2, 'auth_token', '393a214369ead9153330ff19e47b10ae15319034dd41bbc1e5687f9241ad6586', '[\"*\"]', '2026-05-25 03:46:50', NULL, '2026-05-25 03:46:06', '2026-05-25 03:46:50'),
(319, 'App\\Models\\User', 5, 'auth_token', 'a4b5e2f64dfe92474c65e6c5f2aeacab7ef8ec2947d312d389007e5c30536df8', '[\"*\"]', '2026-05-25 03:49:27', NULL, '2026-05-25 03:49:21', '2026-05-25 03:49:27'),
(321, 'App\\Models\\User', 2, 'auth_token', '4fe40fb39a10c48af763ba384ed960ee6a7541908459c7eb224a5f9f3e443c74', '[\"*\"]', '2026-05-25 03:50:45', NULL, '2026-05-25 03:50:28', '2026-05-25 03:50:45'),
(326, 'App\\Models\\User', 14, 'auth_token', 'a3367d0934b248ea276eb18f406615a40cf43d381a2b91b2e2cb2fcf57232db4', '[\"*\"]', '2026-05-25 05:03:37', NULL, '2026-05-25 04:58:49', '2026-05-25 05:03:37'),
(327, 'App\\Models\\User', 5, 'auth_token', '3735133ce16ab2f3dacb04fc3050384630c8e95780b09001cbb0c57a77608150', '[\"*\"]', '2026-05-25 05:09:22', NULL, '2026-05-25 05:09:20', '2026-05-25 05:09:22'),
(330, 'App\\Models\\User', 5, 'auth_token', '04e541fc769015e5b422f8e82ac9956e8309c255357b75109831cc802ed74995', '[\"*\"]', '2026-05-25 06:12:22', NULL, '2026-05-25 05:17:07', '2026-05-25 06:12:22'),
(331, 'App\\Models\\User', 2, 'auth_token', '7763f38cbc53dfd9ee6a0b2058ee53171f45d62c17bd34ce1772a32f312c7c9d', '[\"*\"]', '2026-05-25 05:21:20', NULL, '2026-05-25 05:21:13', '2026-05-25 05:21:20'),
(334, 'App\\Models\\User', 2, 'auth_token', '4e386132123d3328cd224feb1207aecfcde62b45cab0d6c21f3b1e733fb257c2', '[\"*\"]', '2026-05-25 05:31:38', NULL, '2026-05-25 05:30:37', '2026-05-25 05:31:38'),
(336, 'App\\Models\\User', 5, 'auth_token', '26b442ce7147582a67993ff330b411b149fe6ea9271d84f95edd21c38a09b170', '[\"*\"]', '2026-05-25 05:35:05', NULL, '2026-05-25 05:33:34', '2026-05-25 05:35:05'),
(339, 'App\\Models\\User', 2, 'auth_token', 'cb6dc016b6e2f7f5610df5eadd8d055ef18aaa14a43eab7d3d27a563278f069c', '[\"*\"]', '2026-05-25 06:13:36', NULL, '2026-05-25 06:12:52', '2026-05-25 06:13:36'),
(341, 'App\\Models\\User', 5, 'auth_token', 'f376af1c08e9ffe2d91c548f2febc7293f2a50e20d0844ee101162685d182bf2', '[\"*\"]', '2026-05-25 06:51:09', NULL, '2026-05-25 06:16:33', '2026-05-25 06:51:09'),
(343, 'App\\Models\\User', 5, 'auth_token', '517de9820e91d60b3307f4cda39edcac3faef8c1244b3c8ff9d4fda4513b95f2', '[\"*\"]', '2026-05-25 06:21:03', NULL, '2026-05-25 06:21:01', '2026-05-25 06:21:03'),
(348, 'App\\Models\\User', 26, 'auth_token', '3d4242d683d922ccdf06577d249e4e708357eb9dc28104fd78a25aae5cb0f238', '[\"*\"]', '2026-05-26 13:14:33', NULL, '2026-05-26 13:13:09', '2026-05-26 13:14:33'),
(349, 'App\\Models\\User', 27, 'auth_token', '0aeac2f303724b67a02484f440f55b705053287c05e547191b6ab1ec7b7a9ad8', '[\"*\"]', '2026-05-26 23:37:12', NULL, '2026-05-26 23:36:29', '2026-05-26 23:37:12'),
(350, 'App\\Models\\User', 5, 'auth_token', '4d9714add44681d93c622ac0190cbfa8f483340e00de5ead24e2a3e1d8fc7b1f', '[\"*\"]', '2026-05-26 23:51:02', NULL, '2026-05-26 23:50:13', '2026-05-26 23:51:02'),
(354, 'App\\Models\\User', 2, 'auth_token', 'f28b049d21d13e0dba2db17493694d8750eaeac7be80d788573d84867696834f', '[\"*\"]', '2026-05-27 12:11:57', NULL, '2026-05-27 12:10:49', '2026-05-27 12:11:57'),
(356, 'App\\Models\\User', 5, 'auth_token', '910039386fab890e55ace1880b680091742d7bb9a33d86c177757482ac438afc', '[\"*\"]', '2026-05-27 12:19:16', NULL, '2026-05-27 12:14:15', '2026-05-27 12:19:16'),
(359, 'App\\Models\\User', 2, 'auth_token', '02c6a62a4863c38e47102606539b2e79d6ea3aba31654b848ed8b2e45672f85c', '[\"*\"]', '2026-05-27 12:22:53', NULL, '2026-05-27 12:22:34', '2026-05-27 12:22:53'),
(363, 'App\\Models\\User', 2, 'auth_token', 'e5b6adf1faba2f24cca0eee396981460be0c2496c195d58691aa0b226947f5da', '[\"*\"]', '2026-05-27 12:39:12', NULL, '2026-05-27 12:38:48', '2026-05-27 12:39:12'),
(366, 'App\\Models\\User', 2, 'auth_token', '6e0cc6c9765c2876eb70120b143d2e57ecdf4c35109192d623e628e1f69a9531', '[\"*\"]', '2026-05-27 14:26:31', NULL, '2026-05-27 14:23:28', '2026-05-27 14:26:31'),
(367, 'App\\Models\\User', 2, 'auth_token', '9ce185d022f6e7c54654c042faf9f4974246b2ffe83f4702fba2ca2545364e40', '[\"*\"]', '2026-05-27 14:27:12', NULL, '2026-05-27 14:27:04', '2026-05-27 14:27:12'),
(370, 'App\\Models\\User', 2, 'auth_token', '9fa5b98f065086da2eca0a01466ad0e7a64f0bca3d015c8fb466c8fdc6e46f2d', '[\"*\"]', '2026-05-27 14:35:54', NULL, '2026-05-27 14:32:17', '2026-05-27 14:35:54'),
(371, 'App\\Models\\User', 2, 'auth_token', 'f638f8e6cc6ffb5ef39262fefa0f58141123efc170867e49ace28ff802f065a4', '[\"*\"]', '2026-05-27 14:40:24', NULL, '2026-05-27 14:40:01', '2026-05-27 14:40:24'),
(375, 'App\\Models\\User', 2, 'auth_token', 'e96ba817beaee3de845440ef49dcf99dcf6cec3ffe912207ad16350a2cdb5ece', '[\"*\"]', '2026-05-27 14:45:31', NULL, '2026-05-27 14:45:16', '2026-05-27 14:45:31'),
(378, 'App\\Models\\User', 2, 'auth_token', 'fea6f45dcba324b4a1e0a0c342dc1036654cf150b5753fddff8a70360f242a60', '[\"*\"]', '2026-05-27 15:03:33', NULL, '2026-05-27 15:00:03', '2026-05-27 15:03:33'),
(381, 'App\\Models\\User', 2, 'auth_token', 'f35f532556085937de2c2490a5a198882cd7179dbad2bcafd0792e7483acf326', '[\"*\"]', '2026-05-27 15:04:56', NULL, '2026-05-27 15:04:51', '2026-05-27 15:04:56'),
(382, 'App\\Models\\User', 2, 'auth_token', '28ababef52d0e8298950fb758c63a4020ebd7f7beaf3cfc929e713a8256df956', '[\"*\"]', '2026-05-27 15:23:54', NULL, '2026-05-27 15:11:52', '2026-05-27 15:23:54'),
(385, 'App\\Models\\User', 2, 'auth_token', '5d2a19b7be028bb8e492fb627d5f27911762b39c1f122e2f098c050625fb8adb', '[\"*\"]', '2026-05-28 07:28:37', NULL, '2026-05-28 07:28:20', '2026-05-28 07:28:37'),
(387, 'App\\Models\\User', 2, 'auth_token', 'e09941ab791c4f943e1c89d753ad0d37de002fa240582a720c041b82a206b687', '[\"*\"]', '2026-05-28 07:36:31', NULL, '2026-05-28 07:36:06', '2026-05-28 07:36:31'),
(391, 'App\\Models\\User', 2, 'auth_token', '19cd61d36684333522fdc6a14ce854715e9968cd21504e0479f3ffb6086b6dec', '[\"*\"]', '2026-05-28 07:48:36', NULL, '2026-05-28 07:47:54', '2026-05-28 07:48:36'),
(394, 'App\\Models\\User', 2, 'auth_token', '4036b91964d08c2bc21ecdec32dbf834ac571a8bc0ebfda8ab268814b051761f', '[\"*\"]', '2026-05-28 07:50:58', NULL, '2026-05-28 07:50:58', '2026-05-28 07:50:58'),
(395, 'App\\Models\\User', 2, 'auth_token', '98151a1532f5e61a23d2faf63084abd754736602db9634f1282712cb89e488e7', '[\"*\"]', '2026-05-28 08:09:14', NULL, '2026-05-28 07:57:32', '2026-05-28 08:09:14'),
(396, 'App\\Models\\User', 2, 'auth_token', 'f861e263d297f03161a7a4e87a58f4ee770f31fc2876096a5138b05c74cf3aa4', '[\"*\"]', NULL, NULL, '2026-05-28 08:38:59', '2026-05-28 08:38:59'),
(397, 'App\\Models\\User', 2, 'auth_token', 'a4b2a9197d44cde251798de50e450e7df82d78473a1fb77bdfa13808efd3efcd', '[\"*\"]', '2026-05-28 08:41:19', NULL, '2026-05-28 08:41:19', '2026-05-28 08:41:19'),
(398, 'App\\Models\\User', 2, 'auth_token', '80a1e7d717f0863b1a22475d4d429cbb836ba16b14b1a32f9c25cd4e6fd53fed', '[\"*\"]', '2026-05-28 09:42:08', NULL, '2026-05-28 09:41:47', '2026-05-28 09:42:08'),
(400, 'App\\Models\\User', 5, 'auth_token', '5f9e4d73e3c92df3db81168c0baa25789934b084412b7524d7e019cbbcaa8d60', '[\"*\"]', NULL, NULL, '2026-05-28 09:42:50', '2026-05-28 09:42:50'),
(401, 'App\\Models\\User', 2, 'auth_token', '0fc10552797f8dc484ed11526e92dbfaf5e7ae08290082e5db4b0673e678b646', '[\"*\"]', '2026-05-28 09:48:20', NULL, '2026-05-28 09:48:20', '2026-05-28 09:48:20'),
(402, 'App\\Models\\User', 5, 'auth_token', '6d7474f74f152a25ae34d488bce59dc1243429b43259d869d5cd01150314488b', '[\"*\"]', '2026-05-28 09:52:12', NULL, '2026-05-28 09:52:12', '2026-05-28 09:52:12'),
(403, 'App\\Models\\User', 5, 'auth_token', '3fc32a774a34639f9edab1edeb0264940da1b912494340bf5c9ba8b73dd3945f', '[\"*\"]', '2026-05-28 10:07:13', NULL, '2026-05-28 10:07:09', '2026-05-28 10:07:13'),
(405, 'App\\Models\\User', 2, 'auth_token', '4e89dc849b74a4b13b6c44753d4a3ff16689158e64b4e83152d523b91857d824', '[\"*\"]', '2026-05-28 10:12:01', NULL, '2026-05-28 10:07:49', '2026-05-28 10:12:01'),
(408, 'App\\Models\\User', 5, 'auth_token', 'c0e054d9a330751ce898f152153a76ae95b96c8cd77cd37f040354f131bd0813', '[\"*\"]', '2026-05-28 10:31:31', NULL, '2026-05-28 10:31:30', '2026-05-28 10:31:31'),
(410, 'App\\Models\\User', 2, 'auth_token', '1855b28c7640f7913bcacc66915eb63eba3749756899dc8a83131b3fb10b8910', '[\"*\"]', '2026-05-28 10:34:56', NULL, '2026-05-28 10:34:26', '2026-05-28 10:34:56'),
(412, 'App\\Models\\User', 5, 'auth_token', '01f70dee9b8fbaded78ced5a9d625f0fd9435fa2674e3a36d42bacbe27eaca50', '[\"*\"]', '2026-05-28 10:36:13', NULL, '2026-05-28 10:36:13', '2026-05-28 10:36:13'),
(413, 'App\\Models\\User', 4, 'auth_token', '8bdc82ac99f9a5b969c7c915d99644c297e9455c2adee3711363befd5cbcb601', '[\"*\"]', '2026-05-28 10:37:14', NULL, '2026-05-28 10:36:46', '2026-05-28 10:37:14'),
(414, 'App\\Models\\User', 5, 'auth_token', 'de7a8298131a94a7476b172bd974d14a933feb57ffcec7cc5fc9edb33d597596', '[\"*\"]', '2026-05-28 10:51:34', NULL, '2026-05-28 10:51:25', '2026-05-28 10:51:34'),
(415, 'App\\Models\\User', 2, 'auth_token', 'c88fb15e40321e1d008b3f7f77761a17108d69c0f3cd16fbef0801c7323c8c6c', '[\"*\"]', '2026-05-28 10:52:15', NULL, '2026-05-28 10:51:54', '2026-05-28 10:52:15'),
(417, 'App\\Models\\User', 2, 'auth_token', '77710a94e94e322e4fd1413dc8c2d77612b4efdb1ae70c9feb7b8e419e68a7a6', '[\"*\"]', '2026-05-28 11:07:12', NULL, '2026-05-28 11:05:20', '2026-05-28 11:07:12'),
(418, 'App\\Models\\User', 2, 'auth_token', '7a5fac5db789d3516130f71b50e4bff088b6951189da38b48c9900b3e7025379', '[\"*\"]', '2026-05-28 12:27:43', NULL, '2026-05-28 12:22:38', '2026-05-28 12:27:43'),
(419, 'App\\Models\\User', 2, 'auth_token', 'eefaf9a219bd3a9ae45b57ca52c247f055aa2aa20195d74583e597e333c22e73', '[\"*\"]', '2026-05-28 12:50:37', NULL, '2026-05-28 12:46:37', '2026-05-28 12:50:37'),
(420, 'App\\Models\\User', 5, 'auth_token', 'afea15aed422fdb0fcb59ba3b572e1a346d043091d3fb5d9906cbce83aece91d', '[\"*\"]', '2026-05-28 12:53:18', NULL, '2026-05-28 12:51:46', '2026-05-28 12:53:18'),
(422, 'App\\Models\\User', 5, 'auth_token', 'efb47f0b7c1d981956529bf2aba1f126a857a2b35f69eab0b414edd65df744b9', '[\"*\"]', '2026-05-28 14:02:19', NULL, '2026-05-28 12:57:48', '2026-05-28 14:02:19'),
(423, 'App\\Models\\User', 5, 'auth_token', '7ab831ab5cd194da39acdf91a0eb5076897edface2e56fb03690da46af962a0c', '[\"*\"]', '2026-05-28 21:18:38', NULL, '2026-05-28 21:18:24', '2026-05-28 21:18:38'),
(424, 'App\\Models\\User', 5, 'auth_token', 'effb806f6f3a0a1dfabcfdfdfc01fd117fac9fba17073503cc45048cbf1aaabd', '[\"*\"]', '2026-05-28 21:28:41', NULL, '2026-05-28 21:18:54', '2026-05-28 21:28:41'),
(425, 'App\\Models\\User', 5, 'auth_token', '479d347ab821da5cef256f5026d369c36fd7ac2e41f32fb66ca6d2f5665aa864', '[\"*\"]', '2026-05-29 07:29:59', NULL, '2026-05-29 07:28:21', '2026-05-29 07:29:59'),
(427, 'App\\Models\\User', 5, 'auth_token', 'a428229cdc357da20ec56850c56129a6b23d68cbc828d0c4c14e86b5746bb54d', '[\"*\"]', '2026-05-29 07:57:45', NULL, '2026-05-29 07:57:01', '2026-05-29 07:57:45'),
(429, 'App\\Models\\User', 5, 'auth_token', '9a9a288907da6bcd3d93f2882196c495b1ed691e807abfb3d2a27931ae01146a', '[\"*\"]', '2026-05-29 08:01:16', NULL, '2026-05-29 07:59:45', '2026-05-29 08:01:16'),
(431, 'App\\Models\\User', 5, 'auth_token', '34dececf73e9aa8862209bbf7a04391a830ac9983231c49d092addf6a84c8ec5', '[\"*\"]', '2026-05-29 08:47:47', NULL, '2026-05-29 08:47:07', '2026-05-29 08:47:47'),
(433, 'App\\Models\\User', 5, 'auth_token', '6a082a8ac5ea6003f2bf49237d8033a9862d49bb0632216870ba8e5cc6f59fb8', '[\"*\"]', '2026-05-29 08:49:07', NULL, '2026-05-29 08:48:40', '2026-05-29 08:49:07'),
(436, 'App\\Models\\User', 5, 'auth_token', '09cce8de71c41a6da1e8bc56fb6014a71475a667a0b72aec0b2f0db3527bd35e', '[\"*\"]', '2026-05-29 08:56:19', NULL, '2026-05-29 08:55:30', '2026-05-29 08:56:19'),
(437, 'App\\Models\\User', 5, 'auth_token', '00678f77dbf0f1951043fdfc5eabb833d217ed065a71f6ea93e1061c3d694f69', '[\"*\"]', '2026-05-29 08:58:10', NULL, '2026-05-29 08:58:06', '2026-05-29 08:58:10'),
(438, 'App\\Models\\User', 5, 'auth_token', '52fdc65a1cf3b9c79175fcbf1ef350b29209dc32709870bdf8c59938637e546a', '[\"*\"]', '2026-05-29 09:01:04', NULL, '2026-05-29 09:00:33', '2026-05-29 09:01:04'),
(440, 'App\\Models\\User', 5, 'auth_token', '82e4b44965b2d2f9c31ba8213c959fd8d91b79cf78d1919e119d635ee9a01935', '[\"*\"]', '2026-05-29 09:07:15', NULL, '2026-05-29 09:02:16', '2026-05-29 09:07:15'),
(441, 'App\\Models\\User', 5, 'auth_token', 'd65ea1df1126104f59d7870a74160d6b57288bf4c59a46b19e952b436d975877', '[\"*\"]', '2026-05-29 09:12:54', NULL, '2026-05-29 09:12:23', '2026-05-29 09:12:54'),
(443, 'App\\Models\\User', 5, 'auth_token', '1b8a882649301504e7a6e52768c50b4e4c39f7c0a7dfe390fd64f0b7dab32e6a', '[\"*\"]', '2026-05-29 09:14:24', NULL, '2026-05-29 09:14:17', '2026-05-29 09:14:24'),
(444, 'App\\Models\\User', 5, 'auth_token', '6ed0ca6d54b17b5db2854cf4f3bc757aba1b05813ff97021112d69d81a2214ab', '[\"*\"]', '2026-05-29 09:46:13', NULL, '2026-05-29 09:46:04', '2026-05-29 09:46:13'),
(445, 'App\\Models\\User', 5, 'auth_token', '00a74b02e43bf258b91701ccd71cbf43eb50f097233077fa760520348ee4a934', '[\"*\"]', '2026-05-29 09:47:38', NULL, '2026-05-29 09:46:38', '2026-05-29 09:47:38'),
(446, 'App\\Models\\User', 5, 'auth_token', 'ab722b643f5603dedb9d6edffa1d9c4b7d08d4038ae9a4978e7515470d896d3e', '[\"*\"]', '2026-05-29 09:59:47', NULL, '2026-05-29 09:59:38', '2026-05-29 09:59:47'),
(450, 'App\\Models\\User', 5, 'auth_token', '9c2bb9f9c7fef3239503505bd746c79f5a2e3d879f356c7c7ad6a95d9cf63fd3', '[\"*\"]', '2026-05-29 10:21:56', NULL, '2026-05-29 10:13:35', '2026-05-29 10:21:56'),
(452, 'App\\Models\\User', 5, 'auth_token', '6b24f3008538068476a26a430c31b96370120db05306d97d08b67d4b7ff53c33', '[\"*\"]', '2026-05-29 10:28:55', NULL, '2026-05-29 10:28:44', '2026-05-29 10:28:55'),
(453, 'App\\Models\\User', 29, 'auth_token', 'b0108144a9e904c8e6e3a34b34551be6793a9536fd3a4e22dd5462fb4ed77d38', '[\"*\"]', '2026-05-29 11:25:17', NULL, '2026-05-29 11:22:27', '2026-05-29 11:25:17'),
(454, 'App\\Models\\User', 30, 'auth_token', '540783eff630ca5aa5bdb63fa4b1b1a4d090ef517b4c72a723fb47a0e14c2013', '[\"*\"]', '2026-05-29 11:30:28', NULL, '2026-05-29 11:29:42', '2026-05-29 11:30:28'),
(455, 'App\\Models\\User', 32, 'auth_token', 'bcc5eef69b17d3f5bb815ba67a74938e9a722ec1e39427a35d097d59a921ff63', '[\"*\"]', '2026-05-29 12:36:06', NULL, '2026-05-29 11:41:18', '2026-05-29 12:36:06'),
(456, 'App\\Models\\User', 11, 'auth_token', 'beca67782c18b1798b8006330ab1c3d78f635ec852acd9682e9595d6bd605458', '[\"*\"]', '2026-05-29 14:00:59', NULL, '2026-05-29 13:43:03', '2026-05-29 14:00:59'),
(457, 'App\\Models\\User', 5, 'auth_token', '18e3cc0a5740c471ad1840a05dfeeb8307dc5d2ac97999c25b6da81a64d207c2', '[\"*\"]', '2026-05-29 14:32:30', NULL, '2026-05-29 13:51:25', '2026-05-29 14:32:30'),
(458, 'App\\Models\\User', 5, 'auth_token', '28ddf6f8fe463f26345bd008172b7c734df064bc376f0632be26062e695d6f16', '[\"*\"]', '2026-05-29 14:09:58', NULL, '2026-05-29 13:55:26', '2026-05-29 14:09:58'),
(460, 'App\\Models\\User', 5, 'auth_token', 'f63086bbcc1fd5a0c87b719210e34f9dab91fc7b6d8c21352cc31fda920dcdb8', '[\"*\"]', '2026-05-29 14:39:34', NULL, '2026-05-29 14:36:31', '2026-05-29 14:39:34'),
(461, 'App\\Models\\User', 9, 'auth_token', '3711357e6fe29d0675d78fdc3922793b127eb415d8f7574ffdbc7a12bde4554c', '[\"*\"]', '2026-05-29 15:13:37', NULL, '2026-05-29 14:39:55', '2026-05-29 15:13:37'),
(462, 'App\\Models\\User', 8, 'auth_token', 'b7802aee11d0fc8e16f77361229ad2b28770d2938ef58c549d110c9f2a4e3a27', '[\"*\"]', '2026-05-29 18:45:53', NULL, '2026-05-29 18:36:38', '2026-05-29 18:45:53'),
(463, 'App\\Models\\User', 5, 'auth_token', '9327955185b3ec7ed876611da462433f53743b9657f83b40f2cfe8ede9f222c0', '[\"*\"]', '2026-05-29 19:24:15', NULL, '2026-05-29 18:40:15', '2026-05-29 19:24:15'),
(464, 'App\\Models\\User', 11, 'auth_token', '8678d1079eb24f1cb2f950760a9473cf0e51f169eb0a117c63107e57f73a567c', '[\"*\"]', '2026-05-30 08:49:47', NULL, '2026-05-29 18:46:08', '2026-05-30 08:49:47'),
(465, 'App\\Models\\User', 5, 'auth_token', 'f92ba7011b754dbf0def72e11facc197441effc50efe4a2497f7ce95b10231c6', '[\"*\"]', '2026-05-30 09:01:46', NULL, '2026-05-30 09:01:03', '2026-05-30 09:01:46'),
(466, 'App\\Models\\User', 11, 'auth_token', '57118214b99f6cfe8db68ef75586e8b9bfd1ac81077633a54f83b10dbaf6a6b4', '[\"*\"]', '2026-05-30 09:02:20', NULL, '2026-05-30 09:01:59', '2026-05-30 09:02:20'),
(467, 'App\\Models\\User', 4, 'auth_token', '69ff877e3f85340f0ce8a04401d05f02546315793a7e2b937920346545a47be5', '[\"*\"]', '2026-05-30 09:08:34', NULL, '2026-05-30 09:05:44', '2026-05-30 09:08:34'),
(468, 'App\\Models\\User', 2, 'auth_token', 'e313fe7981846b533728894d47d7fbed4caf28304dc4980ed84341e7ac4e7c78', '[\"*\"]', '2026-05-30 09:09:51', NULL, '2026-05-30 09:09:50', '2026-05-30 09:09:51'),
(469, 'App\\Models\\User', 4, 'auth_token', '722ce7b5bae6591d220d29467b02dcf66f2324d02b81d858db1d9dfb2a5f3815', '[\"*\"]', '2026-05-30 09:23:53', NULL, '2026-05-30 09:18:42', '2026-05-30 09:23:53');
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(470, 'App\\Models\\User', 35, 'auth_token', '060f294118f8b9fda1c84449647dbe935f07abeca15b0f9b4f4c09a24b7e7e22', '[\"*\"]', '2026-05-30 09:45:30', NULL, '2026-05-30 09:42:50', '2026-05-30 09:45:30'),
(471, 'App\\Models\\User', 3, 'auth_token', '29c8344965d87873b7dc73ed521981f0f475c84a1cf93df9925fbf469fffa1c9', '[\"*\"]', '2026-05-30 09:46:05', NULL, '2026-05-30 09:46:00', '2026-05-30 09:46:05'),
(472, 'App\\Models\\User', 4, 'auth_token', 'b1e5402694bb0055ee017577d2727897e3f83c1ea6fd42088ee7db23207e62b7', '[\"*\"]', '2026-05-30 09:46:37', NULL, '2026-05-30 09:46:19', '2026-05-30 09:46:37'),
(473, 'App\\Models\\User', 2, 'auth_token', '8d29ebbf32d09582da02cc05d5b12b87687ad853171f4bdbbbe431730d3cdb82', '[\"*\"]', '2026-05-30 09:47:57', NULL, '2026-05-30 09:46:56', '2026-05-30 09:47:57'),
(474, 'App\\Models\\User', 11, 'auth_token', '1804220a271f8e3cd63156b7b93d0d3ebad5663d9d9e2149dfac4e84672e63d9', '[\"*\"]', '2026-05-30 20:02:27', NULL, '2026-05-30 19:56:44', '2026-05-30 20:02:27'),
(475, 'App\\Models\\User', 5, 'auth_token', '49fcfcf5d4a61db3d96ff70ac994875ca13081c0986126c2a399cd21cf388d29', '[\"*\"]', '2026-05-30 20:02:42', NULL, '2026-05-30 20:02:40', '2026-05-30 20:02:42'),
(476, 'App\\Models\\User', 2, 'auth_token', '776baa0162c54cb4e2775698ec9160de040d17c338b25eb5218c23f3ccc71599', '[\"*\"]', '2026-05-30 20:05:21', NULL, '2026-05-30 20:03:27', '2026-05-30 20:05:21'),
(477, 'App\\Models\\User', 5, 'auth_token', '742ecfcd690c2ec7798c59bc6d66565f9c42f99a0e7370d3ecbd2be5de59ade5', '[\"*\"]', '2026-05-30 20:19:54', NULL, '2026-05-30 20:05:31', '2026-05-30 20:19:54'),
(478, 'App\\Models\\User', 5, 'auth_token', '5bcdac5585bb1eb975c5540c702816f268853242a38e3b021c15ba70b27026a5', '[\"*\"]', '2026-05-30 20:29:29', NULL, '2026-05-30 20:20:10', '2026-05-30 20:29:29'),
(479, 'App\\Models\\User', 11, 'auth_token', '00b12a2d287e6afbbd05ab2370154b96e45ee9dac8952427a062c13bcfd780bc', '[\"*\"]', '2026-05-30 20:30:02', NULL, '2026-05-30 20:29:52', '2026-05-30 20:30:02'),
(480, 'App\\Models\\User', 2, 'auth_token', '86b99e3644a3d1416e8ddc1cf888fce35bb907cde64c05ee5cd5768c7dfb9059', '[\"*\"]', '2026-05-30 20:31:20', NULL, '2026-05-30 20:30:40', '2026-05-30 20:31:20'),
(481, 'App\\Models\\User', 11, 'auth_token', 'd1baed5af076e9296edca9eabf98103a87e5a80938466a912c9d3eef70845151', '[\"*\"]', '2026-05-30 20:33:15', NULL, '2026-05-30 20:31:43', '2026-05-30 20:33:15'),
(482, 'App\\Models\\User', 2, 'auth_token', '64b69f2c52378742f3deeda703b996ae8cb6fc3934a0d6a61fc934da765efdcb', '[\"*\"]', '2026-05-30 20:55:42', NULL, '2026-05-30 20:33:57', '2026-05-30 20:55:42'),
(483, 'App\\Models\\User', 9, 'auth_token', '1ff6e0ad44b17d6a0f64181964d570dc88ed20eecea932a733a75ab1eb1ee7bc', '[\"*\"]', '2026-05-30 21:02:22', NULL, '2026-05-30 20:56:14', '2026-05-30 21:02:22'),
(484, 'App\\Models\\User', 2, 'auth_token', 'c8b838f4d2afbba8d600375a786bd6452f2b379e98acc8bc9e5b90d37f0891fc', '[\"*\"]', '2026-05-30 21:05:12', NULL, '2026-05-30 21:02:41', '2026-05-30 21:05:12'),
(485, 'App\\Models\\User', 11, 'auth_token', '36f47b777d06f8300e651319aeea1982c7735d349f71856c5fb65577104216cf', '[\"*\"]', '2026-05-30 21:24:09', NULL, '2026-05-30 21:05:45', '2026-05-30 21:24:09'),
(486, 'App\\Models\\User', 5, 'auth_token', '1f14db5bec280da4aa40414ea5f32a339c2a4db5238c76882b79f87d6729b4bf', '[\"*\"]', '2026-05-30 21:24:41', NULL, '2026-05-30 21:24:40', '2026-05-30 21:24:41'),
(487, 'App\\Models\\User', 4, 'auth_token', '2a609420e9866a2daf12b2c4df20c60b9a26de5a79e4931d0b78f9046dbc2681', '[\"*\"]', '2026-05-30 21:24:56', NULL, '2026-05-30 21:24:53', '2026-05-30 21:24:56'),
(488, 'App\\Models\\User', 4, 'auth_token', '17304d7378190f4b753aa030587b79845f567d3ae0b21d246caabf2551ecf36a', '[\"*\"]', '2026-05-30 21:25:08', NULL, '2026-05-30 21:25:08', '2026-05-30 21:25:08'),
(489, 'App\\Models\\User', 2, 'auth_token', 'f474bac2d81504dbbff41ad710ad9712b7ba4708284e4b0d8d432dca15889120', '[\"*\"]', '2026-05-30 21:25:30', NULL, '2026-05-30 21:25:29', '2026-05-30 21:25:30'),
(490, 'App\\Models\\User', 11, 'auth_token', '50bfe3cff39e9aa38665ccf63cb5bf8e7e2e572ea1575c7abdffaaacae08aafc', '[\"*\"]', '2026-05-30 21:29:59', NULL, '2026-05-30 21:25:46', '2026-05-30 21:29:59'),
(491, 'App\\Models\\User', 11, 'auth_token', 'b9c6bbf407eb40428a53a7d255e03ae74e3ad9365245fbfd2cce68513f3a166d', '[\"*\"]', '2026-05-30 21:30:36', NULL, '2026-05-30 21:30:32', '2026-05-30 21:30:36'),
(492, 'App\\Models\\User', 4, 'auth_token', '4c89a61b6d55ce4e2e4a8e4b4d77f3023720ebb43109f70b62a31e15f07a6952', '[\"*\"]', '2026-05-30 21:36:16', NULL, '2026-05-30 21:30:57', '2026-05-30 21:36:16'),
(493, 'App\\Models\\User', 11, 'auth_token', '08f864c1a66aaeb83d62287b3781d6db195dbc406ec3b7f750d78273d7623204', '[\"*\"]', '2026-05-30 21:36:39', NULL, '2026-05-30 21:36:38', '2026-05-30 21:36:39'),
(494, 'App\\Models\\User', 4, 'auth_token', '934f395ded32b27b3f2a9db75cd0cd68c4e5929e8407cfec3734a8e67ea2d17e', '[\"*\"]', '2026-05-30 21:37:12', NULL, '2026-05-30 21:37:11', '2026-05-30 21:37:12'),
(495, 'App\\Models\\User', 37, 'auth_token', 'c462135bb4e2717a2ae6506dcf62ba0695632b5b90c91da8f80a95e93d88c138', '[\"*\"]', '2026-05-30 22:26:04', NULL, '2026-05-30 22:26:01', '2026-05-30 22:26:04'),
(496, 'App\\Models\\User', 36, 'auth_token', 'd22b3df1e0286c2c61c0df398335e104e8dd4226e20a794b5dcdf044129871d6', '[\"*\"]', '2026-05-30 22:27:49', NULL, '2026-05-30 22:27:04', '2026-05-30 22:27:49'),
(497, 'App\\Models\\User', 37, 'auth_token', 'b81ff9a535a77d3ced6dbc78e8ee0a3e49dda516b339e12b06df65435b9d6cf3', '[\"*\"]', '2026-05-30 22:28:16', NULL, '2026-05-30 22:27:59', '2026-05-30 22:28:16'),
(498, 'App\\Models\\User', 38, 'auth_token', '3b8fdba01a5a73550a52dcc78353fbdc1e58f5fc886c7a8a848734d068ba25ef', '[\"*\"]', '2026-05-30 22:29:13', NULL, '2026-05-30 22:28:33', '2026-05-30 22:29:13'),
(499, 'App\\Models\\User', 39, 'auth_token', 'acca9d0e136beb193fe02873cc99f6883f85c9f674859e6645ef153fb6c91f94', '[\"*\"]', '2026-05-30 22:29:31', NULL, '2026-05-30 22:29:28', '2026-05-30 22:29:31'),
(500, 'App\\Models\\User', 40, 'auth_token', '8a6219dab277b4c703c30a137544d7c2d86798f7bbcbf11416670180942bb7fa', '[\"*\"]', '2026-05-30 22:33:30', NULL, '2026-05-30 22:33:27', '2026-05-30 22:33:30'),
(501, 'App\\Models\\User', 40, 'auth_token', '039f085d0254c2d43ccf281b0ac1f7af01906cef25dd8614dca0840fc5d3720e', '[\"*\"]', '2026-05-30 22:34:19', NULL, '2026-05-30 22:33:48', '2026-05-30 22:34:19'),
(502, 'App\\Models\\User', 41, 'auth_token', 'afdd12468138b19bc9388da831f3166afa9adb86a0291c6e3d42211da742c8fa', '[\"*\"]', '2026-05-30 22:34:59', NULL, '2026-05-30 22:34:57', '2026-05-30 22:34:59'),
(503, 'App\\Models\\User', 42, 'auth_token', '641d9533fb10c5ae5c6598cdf90d2ab258475354fe8ad5e9d85121de77dc0d73', '[\"*\"]', '2026-05-30 22:49:48', NULL, '2026-05-30 22:35:17', '2026-05-30 22:49:48'),
(504, 'App\\Models\\User', 37, 'auth_token', '89db20039340a34d9c060e1d0460d77c629c0b5d2b8f9091ee1828a0a922a591', '[\"*\"]', '2026-05-31 02:57:42', NULL, '2026-05-31 02:57:16', '2026-05-31 02:57:42'),
(505, 'App\\Models\\User', 37, 'auth_token', 'ebc93700e9285fcad62a36dac65de8b36f32ce0c19956880334bec32a45d46ea', '[\"*\"]', '2026-05-31 03:15:51', NULL, '2026-05-31 03:07:40', '2026-05-31 03:15:51'),
(506, 'App\\Models\\User', 37, 'auth_token', '85ee3e0d14f5ad337b241d05543728540d982519070bd756f8bc5f120f16d91f', '[\"*\"]', '2026-05-31 03:21:25', NULL, '2026-05-31 03:17:16', '2026-05-31 03:21:25'),
(507, 'App\\Models\\User', 46, 'auth_token', 'fce58ff291557059efe214d74d5f5eacf1632ba7ddd6669df12dc5740317537b', '[\"*\"]', '2026-05-31 03:57:19', NULL, '2026-05-31 03:56:15', '2026-05-31 03:57:19'),
(508, 'App\\Models\\User', 47, 'auth_token', '3ca3f901d2a0d46b5acfc5886a45a99e001760e31ef667a88c2df4a16f213e48', '[\"*\"]', '2026-05-31 03:59:43', NULL, '2026-05-31 03:58:40', '2026-05-31 03:59:43'),
(509, 'App\\Models\\User', 2, 'auth_token', 'ec04789e8ab12c930777920e655f3be50c89c708f46301a70cda39b2335ed941', '[\"*\"]', '2026-05-31 04:11:26', NULL, '2026-05-31 04:00:55', '2026-05-31 04:11:26'),
(510, 'App\\Models\\User', 46, 'auth_token', '7797fc5bcaf9c407b2fde58c01eaf34e2006538b2ecaad95853150d486a17a9f', '[\"*\"]', '2026-05-31 04:05:26', NULL, '2026-05-31 04:04:35', '2026-05-31 04:05:26'),
(511, 'App\\Models\\User', 37, 'auth_token', '5e9ab63e54b235e5a92aba3db91753683141328572c35b170acdde94bb65b417', '[\"*\"]', '2026-05-31 04:06:21', NULL, '2026-05-31 04:05:47', '2026-05-31 04:06:21'),
(512, 'App\\Models\\User', 38, 'auth_token', 'c2cc23572c5615e741ef3927e73130b188da698f37283f49de08bcf09a7e870c', '[\"*\"]', '2026-05-31 04:11:59', NULL, '2026-05-31 04:08:13', '2026-05-31 04:11:59'),
(513, 'App\\Models\\User', 40, 'auth_token', '9e92b5162db7dfa4cb860be3a198ffb6924c549d22390b91e7c40a327465a7a6', '[\"*\"]', '2026-05-31 04:21:07', NULL, '2026-05-31 04:15:35', '2026-05-31 04:21:07'),
(514, 'App\\Models\\User', 37, 'auth_token', 'cb1bcc27166c1aaf062dbf4ef601bc2e95fbc4e6c99e31dedf542bd112d3b8d4', '[\"*\"]', '2026-05-31 04:23:01', NULL, '2026-05-31 04:18:38', '2026-05-31 04:23:01'),
(515, 'App\\Models\\User', 46, 'auth_token', '2ab13e7fb6e682c63f0296fc39550a7bde1133e3d1d1cc66fc7bcc48d2c433b8', '[\"*\"]', '2026-05-31 04:26:49', NULL, '2026-05-31 04:23:14', '2026-05-31 04:26:49'),
(516, 'App\\Models\\User', 37, 'auth_token', '5ed01dcb42bd54c6f3b30aefe5285762443a2bf5a6c44afabb64992d3ca82b86', '[\"*\"]', '2026-05-31 04:53:43', NULL, '2026-05-31 04:23:42', '2026-05-31 04:53:43'),
(517, 'App\\Models\\User', 48, 'auth_token', '983f3efcf2dad61f92029fad70980598b09c7822f7de3971c724e587d4e712f7', '[\"*\"]', '2026-05-31 04:29:10', NULL, '2026-05-31 04:28:46', '2026-05-31 04:29:10'),
(518, 'App\\Models\\User', 50, 'auth_token', 'f2ad215aae521ec98916f436332c016cd52ce24e538332d2646e9d987333b263', '[\"*\"]', '2026-05-31 04:34:43', NULL, '2026-05-31 04:34:13', '2026-05-31 04:34:43'),
(519, 'App\\Models\\User', 48, 'auth_token', '6b6cc6f75f1a3c83e2e7cd35ceb33f8a0e17890f6bf7f1fa0a26c27296c903c0', '[\"*\"]', '2026-05-31 04:40:12', NULL, '2026-05-31 04:38:48', '2026-05-31 04:40:12'),
(520, 'App\\Models\\User', 48, 'auth_token', '9017d3a9d5e922224d6927ea167e2166834b0eb53cd227bbae9fcde5763056b9', '[\"*\"]', '2026-05-31 04:40:42', NULL, '2026-05-31 04:40:38', '2026-05-31 04:40:42'),
(521, 'App\\Models\\User', 48, 'auth_token', 'ecdd6b4d084b5447fcd1677113ccb8bdc1df8516962b6830bf42efd64b945a60', '[\"*\"]', '2026-05-31 04:44:43', NULL, '2026-05-31 04:41:40', '2026-05-31 04:44:43'),
(522, 'App\\Models\\User', 37, 'auth_token', 'acba2ba8087f908f2b806cb5c3b4553bd47a4a990342521f981297680ebf8ee0', '[\"*\"]', '2026-05-31 04:53:36', NULL, '2026-05-31 04:45:05', '2026-05-31 04:53:36'),
(523, 'App\\Models\\User', 48, 'auth_token', '225e2f2e2428214320b3fdda1155edd6a66674bf9f9b918bd7fe9209764cda70', '[\"*\"]', '2026-05-31 04:57:30', NULL, '2026-05-31 04:53:53', '2026-05-31 04:57:30'),
(524, 'App\\Models\\User', 37, 'auth_token', 'e2fb3dbb18bdd6892de5cb58bf92074a14d07a8aa24a1baff682829dc309a5a8', '[\"*\"]', '2026-05-31 05:52:07', NULL, '2026-05-31 04:56:07', '2026-05-31 05:52:07'),
(525, 'App\\Models\\User', 48, 'auth_token', '9e04087072c1630541b952b4cd45839f9c66be9d72962142a2473cf42f2e31d5', '[\"*\"]', '2026-05-31 05:35:15', NULL, '2026-05-31 04:57:44', '2026-05-31 05:35:15'),
(526, 'App\\Models\\User', 48, 'auth_token', '9b99b9e694f9767d4ea6e984f491d84371bb383202e8c627c8532ff98e96886d', '[\"*\"]', '2026-05-31 05:39:23', NULL, '2026-05-31 05:39:20', '2026-05-31 05:39:23'),
(527, 'App\\Models\\User', 48, 'auth_token', '72d477885e33373cebe3941057d7e0c293d3f4468efd048ef88adae38a98344d', '[\"*\"]', '2026-05-31 06:11:23', NULL, '2026-05-31 06:01:22', '2026-05-31 06:11:23'),
(528, 'App\\Models\\User', 37, 'auth_token', '5ccba5fc54230dfa7fa41bdb4e264f78bfd000580c062b68569b527f5073d38b', '[\"*\"]', '2026-05-31 06:06:00', NULL, '2026-05-31 06:01:49', '2026-05-31 06:06:00'),
(529, 'App\\Models\\User', 48, 'auth_token', '92d9177dcb18a6fb67512077d3053708af876349e162bc5641636da2f609fafc', '[\"*\"]', '2026-05-31 06:10:52', NULL, '2026-05-31 06:06:48', '2026-05-31 06:10:52'),
(530, 'App\\Models\\User', 37, 'auth_token', '0568c8e6a53ca8726f721b4400400acb411ec320436ade7c143b64aed31b5ca7', '[\"*\"]', '2026-05-31 06:55:40', NULL, '2026-05-31 06:11:08', '2026-05-31 06:55:40'),
(531, 'App\\Models\\User', 51, 'auth_token', 'de1b69b82861e9ab5a62d9005bf597c9dc9369a223ae5748c6239f13a1201d94', '[\"*\"]', '2026-05-31 06:31:14', NULL, '2026-05-31 06:20:51', '2026-05-31 06:31:14'),
(532, 'App\\Models\\User', 36, 'auth_token', '5bbd830d056856c6d9620ec7c7e187069ca9a1d2b27b38ffcdc6776c7f772711', '[\"*\"]', '2026-05-31 06:35:55', NULL, '2026-05-31 06:35:51', '2026-05-31 06:35:55'),
(533, 'App\\Models\\User', 53, 'auth_token', 'f641bafc26f9041e34a548e90f2cc47df347daaf44e1a70855153d9afe06039e', '[\"*\"]', '2026-05-31 06:42:10', NULL, '2026-05-31 06:37:18', '2026-05-31 06:42:10'),
(534, 'App\\Models\\User', 53, 'auth_token', '6e1c4c2b5bcca71bf0fed64e0ddb03806ab2a2f537352667d728a1d3ed70e1b6', '[\"*\"]', '2026-05-31 06:47:48', NULL, '2026-05-31 06:42:37', '2026-05-31 06:47:48'),
(535, 'App\\Models\\User', 2, 'auth_token', '8634522322306add7865bfc8248a8f1fdd7343baceaf28469cbaf6ebe636ffd2', '[\"*\"]', '2026-05-31 06:55:34', NULL, '2026-05-31 06:48:13', '2026-05-31 06:55:34'),
(536, 'App\\Models\\User', 37, 'auth_token', '0defa499a271bed296d4a1c19060cba94270f79b13e1e2936a8fc21558dcfe6a', '[\"*\"]', '2026-05-31 07:14:08', NULL, '2026-05-31 06:56:05', '2026-05-31 07:14:08'),
(537, 'App\\Models\\User', 51, 'auth_token', 'f8a7896a80cf351fd61899bbc087a01ea19509fe9a6ea7b30c87a11483cab8a6', '[\"*\"]', '2026-05-31 07:04:55', NULL, '2026-05-31 06:56:24', '2026-05-31 07:04:55'),
(538, 'App\\Models\\User', 48, 'auth_token', 'a452d3cd03276b392946233c8523b7bab28d980ca3507abc484206dd54042281', '[\"*\"]', '2026-06-01 05:52:40', NULL, '2026-06-01 05:49:09', '2026-06-01 05:52:40'),
(539, 'App\\Models\\User', 46, 'auth_token', '11e5bd7790a959aec20373abe8ad5969adf93c0a0c8c6f3fd31d659514a0dfaf', '[\"*\"]', '2026-06-01 06:31:53', NULL, '2026-06-01 05:53:08', '2026-06-01 06:31:53'),
(540, 'App\\Models\\User', 37, 'auth_token', 'cd9db081cea1d4e408bb84b9cc475d35216ea47c7a6e9d474a74139333501c3b', '[\"*\"]', '2026-06-07 20:54:36', NULL, '2026-06-07 20:54:12', '2026-06-07 20:54:36'),
(541, 'App\\Models\\User', 54, 'auth_token', '2d5260b3a0a3290c4a22521cd65044fdaf4b48123f1d773c14fe7ba8b426d3ee', '[\"*\"]', '2026-06-07 20:57:56', NULL, '2026-06-07 20:57:40', '2026-06-07 20:57:56'),
(542, 'App\\Models\\User', 55, 'auth_token', 'ebdb7d352c0f9863a6cb2e4fbbd5bae10e00b982c774b9ae86a680bac27ce0ea', '[\"*\"]', '2026-06-07 21:03:21', NULL, '2026-06-07 20:59:20', '2026-06-07 21:03:21'),
(543, 'App\\Models\\User', 57, 'auth_token', '43e41d87a952055272ab35a3be5c8e95e69b1956272aabad1d96f3f3f4c7ffd8', '[\"*\"]', '2026-06-07 21:34:12', NULL, '2026-06-07 21:05:41', '2026-06-07 21:34:12'),
(544, 'App\\Models\\User', 59, 'auth_token', 'e63a31e52856c71c3077a3c58a022ac84654fad9655bb47f9a1e107eadce965e', '[\"*\"]', '2026-06-09 05:59:54', NULL, '2026-06-07 21:37:15', '2026-06-09 05:59:54'),
(545, 'App\\Models\\User', 54, 'auth_token', '27e1a9585c6d06f4f4bcf3a099048f4231ed9422e7b8e97a8ce36987eb97f11a', '[\"*\"]', '2026-06-09 06:50:50', NULL, '2026-06-09 06:01:45', '2026-06-09 06:50:50'),
(546, 'App\\Models\\User', 78, 'auth_token', '5a318be2457fcd8de3b44d8367d98d7d6f24f9e4d517d17fc6f5c50dc501edfa', '[\"*\"]', '2026-06-09 18:23:48', NULL, '2026-06-09 18:23:47', '2026-06-09 18:23:48'),
(547, 'App\\Models\\User', 79, 'auth_token', '2a64b406d4b1081e4def1c5bcc061157a3496eef58597b1f89548c5b2568924f', '[\"*\"]', '2026-06-09 19:46:44', NULL, '2026-06-09 18:28:35', '2026-06-09 19:46:44'),
(548, 'App\\Models\\User', 78, 'auth_token', 'f049db1146859f8966e5be18f07759e3db6b65d00b71b708bce755771d3f622a', '[\"*\"]', '2026-06-09 19:56:06', NULL, '2026-06-09 19:47:01', '2026-06-09 19:56:06'),
(549, 'App\\Models\\User', 78, 'auth_token', 'eee9c945f812500fac2034eab4d788cb605282989f13f7169217b579219e1950', '[\"*\"]', '2026-06-10 04:08:40', NULL, '2026-06-09 19:56:21', '2026-06-10 04:08:40'),
(550, 'App\\Models\\User', 78, 'test', '052006e5bc01812184b52ee6d1cbb64f689cecfc482545967e78eef5b837b37b', '[\"*\"]', NULL, NULL, '2026-06-09 20:22:28', '2026-06-09 20:22:28'),
(551, 'App\\Models\\User', 128, 'auth_token', 'c18b8bc8f63bbcb71c655cb07a4976d8a6081439dd80b088d51fcc3f85971b75', '[\"*\"]', '2026-06-10 04:18:11', NULL, '2026-06-10 04:09:10', '2026-06-10 04:18:11'),
(552, 'App\\Models\\User', 78, 'auth_token', '5faf3697ad4fae05df69d8fe26fc72efb47f2709b5ec1714d5e29922c3d2fe25', '[\"*\"]', '2026-06-10 04:23:23', NULL, '2026-06-10 04:18:36', '2026-06-10 04:23:23'),
(553, 'App\\Models\\User', 128, 'auth_token', 'fc9b02b37569ae8a7377449ed7ab44c336a095efb4e154460db76eaec0537c04', '[\"*\"]', '2026-06-10 05:20:14', NULL, '2026-06-10 04:23:42', '2026-06-10 05:20:14'),
(554, 'App\\Models\\User', 78, 'auth_token', '543f7d3b1884dc8e4fe8050d28fe7718177a42d5058b42dec7a533835eabe16e', '[\"*\"]', '2026-06-10 21:28:45', NULL, '2026-06-10 05:20:40', '2026-06-10 21:28:45'),
(555, 'App\\Models\\User', 73, 'auth_token', 'bfeb76cfc6e347957d1860321ef33e57ca8a8c374473b644790e5084a7465a4b', '[\"*\"]', '2026-06-10 21:30:29', NULL, '2026-06-10 21:29:11', '2026-06-10 21:30:29'),
(556, 'App\\Models\\User', 113, 'auth_token', '5b174e89c28f3e5fc009c399579bb69745398ccc01dfb64e49dc8d2fe501d6bb', '[\"*\"]', '2026-06-11 08:28:03', NULL, '2026-06-11 08:26:19', '2026-06-11 08:28:03'),
(557, 'App\\Models\\User', 118, 'auth_token', '75ce9770255a74e008525d8376e21c36be3437ef1fdf728f800d3542aef3deca', '[\"*\"]', '2026-06-11 10:26:05', NULL, '2026-06-11 08:28:39', '2026-06-11 10:26:05'),
(558, 'App\\Models\\User', 78, 'auth_token', 'ee012430ea60569aa845fdd3d0fe4c91b58e464199d421feb69353a4f6dbbe70', '[\"*\"]', '2026-06-11 10:44:25', NULL, '2026-06-11 10:26:19', '2026-06-11 10:44:25'),
(559, 'App\\Models\\User', 128, 'auth_token', 'bd45dcb4c9501212e7859243e71b92d35f3d0109d47b46bee69728f99924e68c', '[\"*\"]', '2026-06-11 10:45:37', NULL, '2026-06-11 10:44:51', '2026-06-11 10:45:37'),
(560, 'App\\Models\\User', 118, 'auth_token', '7a681aa0ab88a6c0a08db5fe1bf0f024648d7c2ba5cf38dbf5009f252fe21858', '[\"*\"]', '2026-06-11 10:48:44', NULL, '2026-06-11 10:46:04', '2026-06-11 10:48:44'),
(561, 'App\\Models\\User', 113, 'auth_token', '8294a991b466d10ae516669e604ea07ee97dd043dc6d1a963e5ba8195c9dbac4', '[\"*\"]', '2026-06-11 11:59:40', NULL, '2026-06-11 10:48:55', '2026-06-11 11:59:40'),
(562, 'App\\Models\\User', 73, 'auth_token', 'b109cce77a98b938632f2e94fdaff831e08b0883af4eacbd5d0c58907080dd13', '[\"*\"]', '2026-06-11 12:09:41', NULL, '2026-06-11 12:00:16', '2026-06-11 12:09:41'),
(563, 'App\\Models\\User', 73, 'auth_token', '1015e69b82e8c991568e0848dfc4e096e5870745d43aa8ad39f72898e0567c4a', '[\"*\"]', '2026-06-11 12:18:37', NULL, '2026-06-11 12:12:06', '2026-06-11 12:18:37'),
(564, 'App\\Models\\User', 73, 'auth_token', '30a2cd7bfe34661afbc97d77410c109b8ae6c2f4318c07f183605a62ab1af9ea', '[\"*\"]', '2026-06-11 12:52:31', NULL, '2026-06-11 12:46:00', '2026-06-11 12:52:31'),
(565, 'App\\Models\\User', 73, 'auth_token', '7ef4f578cc3844a9751410f4963882d74040123b367c9166afdc2303973033f9', '[\"*\"]', '2026-06-11 12:56:48', NULL, '2026-06-11 12:53:18', '2026-06-11 12:56:48'),
(566, 'App\\Models\\User', 73, 'auth_token', '7d61c492bfcd08ac8c6bc6570b03b3b261567276159b7e62796b4b8e957126b2', '[\"*\"]', '2026-06-11 14:16:02', NULL, '2026-06-11 12:57:28', '2026-06-11 14:16:02'),
(567, 'App\\Models\\User', 79, 'auth_token', '112abfa133a663544745aa1e66883cc59815ebe20c4404d4673b59a06e94ed9f', '[\"*\"]', '2026-06-11 14:17:31', NULL, '2026-06-11 14:16:57', '2026-06-11 14:17:31'),
(568, 'App\\Models\\User', 73, 'auth_token', 'be73b98431163be450dc881a5863abbeb64fa9e3481f1267ea289a587d49bf40', '[\"*\"]', '2026-06-11 14:33:48', NULL, '2026-06-11 14:18:48', '2026-06-11 14:33:48'),
(569, 'App\\Models\\User', 129, 'auth_token', 'b2b9505c755baa5246a4c51b038d767a54281c56e431c21023991d996597a795', '[\"*\"]', '2026-06-11 14:36:32', NULL, '2026-06-11 14:36:05', '2026-06-11 14:36:32'),
(570, 'App\\Models\\User', 73, 'auth_token', '524cad8c4f02ce9a8211d1a53bf64356619a26c4e695ec3f804b7f893f1fe037', '[\"*\"]', '2026-06-11 14:36:50', NULL, '2026-06-11 14:36:43', '2026-06-11 14:36:50'),
(571, 'App\\Models\\User', 129, 'auth_token', '46c756f36eefdd35a18ef438dc8dd9e7436e2fc70201a14f67039e4b4150e6fa', '[\"*\"]', '2026-06-11 14:39:02', NULL, '2026-06-11 14:37:12', '2026-06-11 14:39:02'),
(572, 'App\\Models\\User', 73, 'auth_token', '7bf1271acd58879ec2a16fa2ba02a5a990fff9cd573d53ba4fbb6841f7f8f7a0', '[\"*\"]', '2026-06-11 15:05:42', NULL, '2026-06-11 14:39:41', '2026-06-11 15:05:42'),
(573, 'App\\Models\\User', 71, 'auth_token', '856553da9b46d9127de3f0c68cae44e13aadbc688a585609ea2a558b0cdfe631', '[\"*\"]', NULL, NULL, '2026-06-17 09:35:38', '2026-06-17 09:35:38'),
(574, 'App\\Models\\User', 71, 'auth_token', '5907e6e1a5862091aa121cb3dcf0c083ecf8937669dadf0b892040b1ecfb680c', '[\"*\"]', '2026-06-17 12:27:16', NULL, '2026-06-17 10:03:54', '2026-06-17 12:27:16'),
(575, 'App\\Models\\User', 71, 'auth_token', '0ff1d924f39a30aeef4ae13326dbf8a773ac84cbd068a84a948baba438728b93', '[\"*\"]', '2026-06-17 10:41:36', NULL, '2026-06-17 10:41:14', '2026-06-17 10:41:36'),
(576, 'App\\Models\\User', 124, 'auth_token', '85a353c676b49660e51a384922b4291fce4f6b6f067ea63580e5dea596f06960', '[\"*\"]', '2026-06-17 11:08:31', NULL, '2026-06-17 10:47:17', '2026-06-17 11:08:31'),
(577, 'App\\Models\\User', 125, 'auth_token', 'dc2dc10ffcf0033c3beed357e028492036408b2ebea63abe0c4f7d2bb3c1b39f', '[\"*\"]', '2026-06-17 11:11:15', NULL, '2026-06-17 11:11:10', '2026-06-17 11:11:15'),
(578, 'App\\Models\\User', 125, 'auth_token', '42e296175e23b7fefa168a45e8f0c117b1841911fec5f0a1d9ee1c50ce79f2c5', '[\"*\"]', '2026-06-17 11:20:03', NULL, '2026-06-17 11:11:12', '2026-06-17 11:20:03'),
(579, 'App\\Models\\User', 125, 'auth_token', '754787370d592aa898820b602061b47706380c98966127740c9f55ac41b4fdb7', '[\"*\"]', '2026-06-17 12:14:48', NULL, '2026-06-17 11:25:17', '2026-06-17 12:14:48'),
(580, 'App\\Models\\User', 125, 'auth_token', '30684c424a66eb998e1aa61ff06f1c76b50c7db854a0abe2486c6a20cdb8e06b', '[\"*\"]', '2026-06-17 12:25:50', NULL, '2026-06-17 12:16:49', '2026-06-17 12:25:50'),
(581, 'App\\Models\\User', 69, 'auth_token', 'a971bd2bb5c65b7ef356708bb0a08053aa6f54b788b180da06839f3eb5a4f26a', '[\"*\"]', '2026-06-17 12:40:36', NULL, '2026-06-17 12:27:40', '2026-06-17 12:40:36'),
(582, 'App\\Models\\User', 125, 'auth_token', '970872e9a3ac47e89ea3ef5c5793bb8d65bc12c6710d8f83473e5ca3dcaaef95', '[\"*\"]', '2026-06-17 12:34:15', NULL, '2026-06-17 12:28:25', '2026-06-17 12:34:15'),
(583, 'App\\Models\\User', 125, 'auth_token', '1f3dfff2544800da581cee3c92ccd19f615aa4857b73e28d13c1a7353dea8a1e', '[\"*\"]', '2026-06-17 12:42:25', NULL, '2026-06-17 12:36:23', '2026-06-17 12:42:25'),
(584, 'App\\Models\\User', 125, 'auth_token', 'b3a4d89d2f1499e3bd4b28bc9ef654d46d9c349ba947c57c5045c963bc0d6526', '[\"*\"]', '2026-06-17 12:45:26', NULL, '2026-06-17 12:40:54', '2026-06-17 12:45:26'),
(585, 'App\\Models\\User', 125, 'auth_token', 'd59d4d81a1815dbe9a8030b3698ac023c9917cb6b9948b3dbf73413a620fa10d', '[\"*\"]', '2026-06-17 12:46:33', NULL, '2026-06-17 12:45:20', '2026-06-17 12:46:33'),
(586, 'App\\Models\\User', 69, 'auth_token', 'dc020dc07c05f13374703ec86f46716c8c33847f1fcf7b95b3c837ce4636ebd2', '[\"*\"]', '2026-06-17 12:47:10', NULL, '2026-06-17 12:45:38', '2026-06-17 12:47:10'),
(587, 'App\\Models\\User', 125, 'auth_token', '9d2fbcf2ca8eb980292f2bd679d81308348e80765e9fefbf3a4641aae78c900a', '[\"*\"]', '2026-06-17 13:03:06', NULL, '2026-06-17 12:47:31', '2026-06-17 13:03:06'),
(588, 'App\\Models\\User', 125, 'auth_token', '8838d93f70aa6dab8d4f82fda0c7470e7e2e5626cc8c11d09f2374700f1681ed', '[\"*\"]', '2026-06-19 09:32:44', NULL, '2026-06-17 13:14:09', '2026-06-19 09:32:44'),
(589, 'App\\Models\\User', 69, 'auth_token', 'fd062f623d3f2c082703410bc3aa659093657c321979e3505cef4092dd88e1ad', '[\"*\"]', '2026-06-19 09:34:54', NULL, '2026-06-19 09:33:51', '2026-06-19 09:34:54'),
(590, 'App\\Models\\User', 125, 'auth_token', '3d0d8ece3b7115b6fe8bd051d2fb10ab34c1ddbf0be217aee7ca4cb90eb43b6e', '[\"*\"]', '2026-06-19 09:41:46', NULL, '2026-06-19 09:36:45', '2026-06-19 09:41:46'),
(591, 'App\\Models\\User', 112, 'auth_token', '8411cafbebd597f64f2df75ab77f22ab549a89a7c1e9d0c0c845db3def979e06', '[\"*\"]', '2026-06-19 09:48:06', NULL, '2026-06-19 09:42:40', '2026-06-19 09:48:06'),
(592, 'App\\Models\\User', 112, 'auth_token', '933d02ace8f302473727fc22666ec487ddc227ddafd8c294ddedefb2f36a5fe9', '[\"*\"]', '2026-06-19 10:01:55', NULL, '2026-06-19 09:48:57', '2026-06-19 10:01:55'),
(593, 'App\\Models\\User', 69, 'auth_token', '3e098061d0f439b5abbc8511efe7bdccf92d52a4219ab59ffdec3643547d8366', '[\"*\"]', '2026-06-19 10:05:57', NULL, '2026-06-19 10:02:17', '2026-06-19 10:05:57'),
(594, 'App\\Models\\User', 125, 'auth_token', '8bd08e69b3446639faf1b0dab044b6f9c0f5c2f73a0300eaa62112561ccd12c1', '[\"*\"]', '2026-06-19 10:14:41', NULL, '2026-06-19 10:02:39', '2026-06-19 10:14:41'),
(595, 'App\\Models\\User', 112, 'auth_token', 'ebe8b6cc9f4d31c855dcb19a37583cf9c9ae5492879cbb54c1544bf604e213f4', '[\"*\"]', '2026-06-19 11:06:26', NULL, '2026-06-19 10:06:29', '2026-06-19 11:06:26'),
(596, 'App\\Models\\User', 73, 'auth_token', 'ddc798e9b23bf0358a7516cc72a9c246786124a9ed0f582c6435fd39dabc9f71', '[\"*\"]', '2026-06-19 11:44:36', NULL, '2026-06-19 11:07:20', '2026-06-19 11:44:36'),
(597, 'App\\Models\\User', 112, 'auth_token', '4f524da7c9c2a6ac10b9b9242e54a13238461ed82dec1fd2447d32f10917a4c3', '[\"*\"]', '2026-06-19 11:50:05', NULL, '2026-06-19 11:45:08', '2026-06-19 11:50:05'),
(598, 'App\\Models\\User', 73, 'auth_token', '00f16c8ea23c09f0ad5ea11bfec175e95308bdb3c801febe8bf53b140ccdc37a', '[\"*\"]', '2026-06-19 11:51:33', NULL, '2026-06-19 11:51:16', '2026-06-19 11:51:33'),
(599, 'App\\Models\\User', 112, 'auth_token', '3e137b9fa7f95c15db72c1ea3ac5e77f86d08af3bd13fe78e1588c5c0ff33eaf', '[\"*\"]', '2026-06-28 04:29:47', NULL, '2026-06-19 11:51:50', '2026-06-28 04:29:47'),
(600, 'App\\Models\\User', 85, 'auth_token', 'b584e2a647ff25ad2e7e5e21451aac598cd66a5a2c357c80f00bc05679931383', '[\"*\"]', '2026-06-24 22:14:20', NULL, '2026-06-24 22:08:34', '2026-06-24 22:14:20'),
(601, 'App\\Models\\User', 112, 'auth_token', '0a269f2a5c04ebb184035cbdd121454b77f1deddad0c9bc55c8e82bf6c8bafcb', '[\"*\"]', '2026-06-24 22:19:21', NULL, '2026-06-24 22:15:01', '2026-06-24 22:19:21'),
(602, 'App\\Models\\User', 66, 'auth_token', 'c5035e6865089754eccb178366e99dd63014114406890c35323e683b0269ad50', '[\"*\"]', '2026-06-24 23:23:30', NULL, '2026-06-24 22:36:27', '2026-06-24 23:23:30'),
(603, 'App\\Models\\User', 85, 'auth_token', '88f0f75cc0320b80b1a2c278a498d8143a2cf8c640ea21004965514dc5758239', '[\"*\"]', '2026-06-26 16:27:28', NULL, '2026-06-26 16:26:45', '2026-06-26 16:27:28'),
(604, 'App\\Models\\User', 113, 'auth_token', '86e293342babc458e28c26b5c526a54dca93b3c4605690347fba5ad718f9a857', '[\"*\"]', '2026-06-26 16:30:50', NULL, '2026-06-26 16:29:17', '2026-06-26 16:30:50'),
(605, 'App\\Models\\User', 71, 'auth_token', '2f0ea9d61f5a9168cd7cb477f8479b481ed53532084bdd85ce0c5b775e3c7bab', '[\"*\"]', '2026-06-26 16:40:20', NULL, '2026-06-26 16:40:04', '2026-06-26 16:40:20'),
(606, 'App\\Models\\User', 112, 'auth_token', '0bd5ce58fca1a06aa3b788e7e783c6e4609bd96a43ed8d894e2227ab3368b52f', '[\"*\"]', '2026-06-26 16:51:55', NULL, '2026-06-26 16:40:52', '2026-06-26 16:51:55'),
(607, 'App\\Models\\User', 71, 'auth_token', 'e06c0faf25be068c7ec77b51205056183980b67d470e74458e1d50022d3e097b', '[\"*\"]', '2026-06-26 17:03:59', NULL, '2026-06-26 17:03:28', '2026-06-26 17:03:59'),
(608, 'App\\Models\\User', 112, 'auth_token', '75ded64bb2a65a2c57bd6198bd8dc4cfddddd92d303c13f06fcc6668be0ba99b', '[\"*\"]', '2026-06-26 17:18:12', NULL, '2026-06-26 17:05:11', '2026-06-26 17:18:12'),
(609, 'App\\Models\\User', 112, 'auth_token', '1989b7ab79ecf6a05aa06f32fa9567a565f3a82305af6cd8208e250f881772f9', '[\"*\"]', '2026-06-26 18:03:52', NULL, '2026-06-26 17:47:20', '2026-06-26 18:03:52'),
(610, 'App\\Models\\User', 112, 'auth_token', 'f617b5f7f0c7ae40abfc1fcabfb104e31db4d99b6bf6ce1a6db19c8641959a15', '[\"*\"]', '2026-06-26 18:08:53', NULL, '2026-06-26 18:05:49', '2026-06-26 18:08:53'),
(611, 'App\\Models\\User', 112, 'auth_token', '52312fae209bdf3ab9fc7f813b256b7de7f75ee768e00adce29d4b8c6a24f94a', '[\"*\"]', '2026-06-26 18:23:57', NULL, '2026-06-26 18:11:20', '2026-06-26 18:23:57'),
(612, 'App\\Models\\User', 112, 'auth_token', '0ca7812d1a9ddce1b0260063e387cf286c5a355624c53179f993ea6b7974be8d', '[\"*\"]', '2026-06-26 18:35:07', NULL, '2026-06-26 18:29:05', '2026-06-26 18:35:07'),
(613, 'App\\Models\\User', 112, 'auth_token', '0d2f4a67d56a45d4285053d736b6c3a6bccaff3075a0c0abb05b7602dd69835a', '[\"*\"]', '2026-06-26 18:49:45', NULL, '2026-06-26 18:46:12', '2026-06-26 18:49:45'),
(614, 'App\\Models\\User', 145, 'auth_token', '9c177438f0094c552da00dcd4cb836ffa11191c916a49e2ba548f104e4ab971f', '[\"*\"]', '2026-06-26 18:57:34', NULL, '2026-06-26 18:57:03', '2026-06-26 18:57:34'),
(615, 'App\\Models\\User', 112, 'auth_token', '1e099881d8d4521ee9a59b1d91d5e3d3ef00fa83ac3e2c24bf2fefed57aaf9fd', '[\"*\"]', '2026-06-26 19:06:48', NULL, '2026-06-26 18:58:16', '2026-06-26 19:06:48'),
(616, 'App\\Models\\User', 112, 'auth_token', '6e320d4def7262b51432688282814a681880e47a9fc65dd745840243a75ccf55', '[\"*\"]', '2026-06-26 19:09:28', NULL, '2026-06-26 19:09:05', '2026-06-26 19:09:28'),
(617, 'App\\Models\\User', 145, 'auth_token', '44f485eeffadb14d21cb587f78b66c590f9449c0b079eade44590cfefc142000', '[\"*\"]', '2026-06-26 19:14:30', NULL, '2026-06-26 19:09:46', '2026-06-26 19:14:30'),
(618, 'App\\Models\\User', 145, 'auth_token', '52f1add9a1879768ee13e7cc03cc716279d47594572495a41a1aacb2e664d2ec', '[\"*\"]', '2026-06-26 19:24:40', NULL, '2026-06-26 19:16:43', '2026-06-26 19:24:40'),
(619, 'App\\Models\\User', 136, 'auth_token', '2633e4b2510c4dc999eef322852a19cf3527516fd240ebf063e629cd419394ef', '[\"*\"]', '2026-06-26 19:38:23', NULL, '2026-06-26 19:37:31', '2026-06-26 19:38:23'),
(620, 'App\\Models\\User', 73, 'auth_token', '402eada4320798d5d4d477565bf6592009726b759146107d4b04d1812249fc46', '[\"*\"]', '2026-06-26 20:00:27', NULL, '2026-06-26 19:39:20', '2026-06-26 20:00:27'),
(621, 'App\\Models\\User', 112, 'auth_token', '0b584754bf8a94587983007e4a8d921a9961b97ae966c37199c044ce17c42d39', '[\"*\"]', '2026-06-26 20:01:01', NULL, '2026-06-26 19:52:38', '2026-06-26 20:01:01'),
(622, 'App\\Models\\User', 73, 'auth_token', '021f32197cf7340637bfad7de73fe67c9b50e16f6477030616759f56e4c74681', '[\"*\"]', '2026-06-27 19:29:06', NULL, '2026-06-27 19:14:33', '2026-06-27 19:29:06'),
(623, 'App\\Models\\User', 73, 'auth_token', '364c47f0a95ff1a5d7746d37682d0a71f762f7e984accef33bb5ab9dab5cca84', '[\"*\"]', '2026-06-27 19:30:46', NULL, '2026-06-27 19:23:19', '2026-06-27 19:30:46'),
(624, 'App\\Models\\User', 73, 'auth_token', '4ba800139f8cfe6c1deed5150da2bef0a77786de03537e5fe29c9880e41d10c0', '[\"*\"]', '2026-06-27 19:30:45', NULL, '2026-06-27 19:30:13', '2026-06-27 19:30:45'),
(625, 'App\\Models\\User', 73, 'auth_token', '324d3bb150a66ad8d400c86b94f45387250a8b13c750f437781d56dc64a53525', '[\"*\"]', '2026-06-27 19:36:45', NULL, '2026-06-27 19:33:14', '2026-06-27 19:36:45'),
(626, 'App\\Models\\User', 136, 'auth_token', '54c4f1c02a270bfbb60611610ca9ab9935b0a7cdbce4f2063e1a55a6dabf137a', '[\"*\"]', '2026-06-28 04:31:48', NULL, '2026-06-28 04:30:51', '2026-06-28 04:31:48'),
(627, 'App\\Models\\User', 73, 'auth_token', '648fa935331b12803e7b170c243ee8c6c2fbaea67b7addb6eb90b7766563606e', '[\"*\"]', '2026-06-28 04:35:26', NULL, '2026-06-28 04:33:07', '2026-06-28 04:35:26'),
(628, 'App\\Models\\User', 145, 'auth_token', '5fbfedad28f3d46a575161317a3e75be7350207529bf4b59a3ac542dada2d01a', '[\"*\"]', '2026-06-28 04:38:46', NULL, '2026-06-28 04:38:09', '2026-06-28 04:38:46'),
(629, 'App\\Models\\User', 112, 'auth_token', '0afb1d55b93be1cb4f592182715108b784522b2da2b057f1b9d7089ac9e55547', '[\"*\"]', '2026-06-28 05:29:14', NULL, '2026-06-28 04:39:42', '2026-06-28 05:29:14'),
(630, 'App\\Models\\User', 71, 'auth_token', '202df5460fe55e311823d6bb9673bd64143dc54ded87aefb19ce494fabda9640', '[\"*\"]', '2026-06-28 04:40:42', NULL, '2026-06-28 04:40:02', '2026-06-28 04:40:42'),
(631, 'App\\Models\\User', 136, 'auth_token', '098da1be3b4f2eaffb70503a7efdf3c121f34347f91da11d0c8997f9651b1ae1', '[\"*\"]', '2026-06-28 05:50:10', NULL, '2026-06-28 04:41:18', '2026-06-28 05:50:10'),
(634, 'App\\Models\\User', 112, 'auth_token', 'ceb84219a4d3eee7cf3b4c5ea223d3255e7c9817235c8556f7aa22e09ebd02f3', '[\"*\"]', NULL, NULL, '2026-07-02 10:24:00', '2026-07-02 10:24:00'),
(635, 'App\\Models\\User', 85, 'auth_token', 'c5cb7349b5ab88234468772f0678c565927bdb84235d1640db9090531d9a1c2f', '[\"*\"]', '2026-07-02 10:24:18', NULL, '2026-07-02 10:24:13', '2026-07-02 10:24:18'),
(637, 'App\\Models\\User', 145, 'auth_token', '654a99d33cda251dc3e90f36f1d88f2395ca8074ea9a8aee7547f65e6bb96484', '[\"*\"]', '2026-07-03 13:40:11', NULL, '2026-07-03 12:56:41', '2026-07-03 13:40:11'),
(639, 'App\\Models\\User', 73, 'auth_token', '1b7916be24ca8bc6d93cbfac0e5a79f03a365d04e4315d4044b1258e20287c22', '[\"*\"]', '2026-07-03 14:43:01', NULL, '2026-07-03 14:42:04', '2026-07-03 14:43:01'),
(643, 'App\\Models\\User', 112, 'auth_token', '530cf6bbdae032c5d657214aee903df5bbc7f01adbf23b330e31b8a0712844e1', '[\"*\"]', '2026-07-03 19:59:47', NULL, '2026-07-03 19:01:16', '2026-07-03 19:59:47'),
(647, 'App\\Models\\User', 112, 'auth_token', '2af87514e636fafb1abc8bf7bfbcb823a0cea3e7820640108e3c9e30508511ca', '[\"*\"]', '2026-07-03 20:30:55', NULL, '2026-07-03 20:09:49', '2026-07-03 20:30:55'),
(648, 'App\\Models\\User', 112, 'auth_token', 'dd8e7868b46ad2995be9994d47c21c2475fa9ad38fa8dd26ab4bbea017ecf66c', '[\"*\"]', '2026-07-03 20:27:55', NULL, '2026-07-03 20:24:54', '2026-07-03 20:27:55'),
(649, 'App\\Models\\User', 112, 'auth_token', '34dcd12a540fb5e91ec59170c9706b3832eeaad14498b693b1a1b2602f46062e', '[\"*\"]', '2026-07-03 21:45:23', NULL, '2026-07-03 20:32:22', '2026-07-03 21:45:23'),
(654, 'App\\Models\\User', 112, 'auth_token', 'b993676771f98d82a8535aa2a060d5cdeab4a613ac418fe00b83b79bfd804f87', '[\"*\"]', '2026-07-03 21:22:14', NULL, '2026-07-03 21:22:13', '2026-07-03 21:22:14'),
(679, 'App\\Models\\User', 73, 'auth_token', 'ac5779b4e30a14c39d87bd6dbcf2493f8816ad7d6cfcea3928a603766eca9ce6', '[\"*\"]', '2026-07-05 17:24:58', NULL, '2026-07-05 14:17:25', '2026-07-05 17:24:58'),
(687, 'App\\Models\\User', 85, 'auth_token', '7cf6fe210415a2066e762cc4d60acc02af52658d1a8e5ff1addabfffe4e5ec83', '[\"*\"]', '2026-07-05 18:54:41', NULL, '2026-07-05 17:20:02', '2026-07-05 18:54:41'),
(689, 'App\\Models\\User', 73, 'auth_token', '54697cede499c5f2d4bf027d32edf09ba33ebeeb6b8ea17c9c31871a48c9a32a', '[\"*\"]', '2026-07-05 18:50:11', NULL, '2026-07-05 18:50:10', '2026-07-05 18:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `photo_change_requests`
--

CREATE TABLE `photo_change_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `old_photo` varchar(255) DEFAULT NULL,
  `new_photo` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'معلوماتية', 1, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(2, 'اتصالات', 1, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(3, 'صيدلة', 3, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(4, 'مخابر', 3, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(5, 'ادارة اعمال', 2, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(6, 'محاسبة', 2, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(7, 'هندسة عمارة', 4, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(8, 'ديكور', 4, '2026-05-30 21:41:22', '2026-05-30 21:41:22'),
(9, 'ذكاء اصطناعي', 1, '2026-06-09 09:29:21', '2026-06-09 09:29:21'),
(10, 'الكترون', 1, '2026-06-09 09:29:21', '2026-06-09 09:29:21');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `total_marks` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quiz_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `type` enum('mcq','text') NOT NULL DEFAULT 'mcq',
  `marks` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `order_num` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_requests`
--

CREATE TABLE `report_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `head_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `year` tinyint(4) DEFAULT NULL,
  `report_type` enum('academic','behavioral') NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `sent_to_parent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_requests`
--

INSERT INTO `report_requests` (`id`, `head_id`, `teacher_id`, `student_id`, `course_id`, `year`, `report_type`, `notes`, `status`, `sent_to_parent`, `created_at`, `updated_at`) VALUES
(9, 79, 6, 7, NULL, NULL, 'behavioral', 'طلب تقرير سلوكي من ولي الأمر', 'completed', 0, '2026-06-09 18:42:58', '2026-06-09 18:49:01'),
(10, 73, 11, 7, NULL, NULL, 'behavioral', '', 'completed', 1, '2026-06-11 14:14:11', '2026-07-05 17:07:16'),
(11, 73, 7, 22, NULL, NULL, 'behavioral', '', 'completed', 1, '2026-06-11 14:30:11', '2026-06-11 14:36:49');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin', '2026-05-09 08:25:26', '2026-05-09 08:25:26'),
(2, 'teacher', '2026-05-09 08:25:26', '2026-05-09 08:25:26'),
(3, 'student', '2026-05-09 08:25:26', '2026-05-09 08:25:26'),
(4, 'parent', '2026-05-09 08:25:26', '2026-05-09 08:25:26'),
(5, 'head', '2026-05-09 08:25:26', '2026-05-09 08:25:26'),
(6, 'affairs', '2026-05-27 14:03:58', '2026-05-27 14:03:58');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `class_group` varchar(255) DEFAULT NULL,
  `day` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `course_id`, `teacher_id`, `class_group`, `day`, `start_time`, `end_time`, `room`, `created_at`, `updated_at`) VALUES
(6, 1, NULL, NULL, 'الأحد', '08:00:00', '09:30:00', 'A101-A', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(7, 2, NULL, NULL, 'الأحد', '10:00:00', '11:30:00', 'A101-B', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(8, 3, NULL, NULL, 'الاثنين', '08:00:00', '09:30:00', 'B202-A', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(9, 4, NULL, NULL, 'الاثنين', '10:00:00', '11:30:00', 'B202-B', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(10, 5, NULL, NULL, 'الثلاثاء', '08:00:00', '09:30:00', 'C303-A', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(11, 6, NULL, NULL, 'الثلاثاء', '10:00:00', '11:30:00', 'C303-B', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(12, 7, NULL, NULL, 'الأربعاء', '08:00:00', '09:30:00', 'D404-A', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(13, 8, NULL, NULL, 'الأربعاء', '10:00:00', '11:30:00', 'D404-B', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(14, 9, NULL, NULL, 'الخميس', '08:00:00', '09:30:00', 'E505-A', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(15, 10, NULL, NULL, 'الخميس', '10:00:00', '11:30:00', 'E505-B', '2026-05-23 15:04:57', '2026-05-23 15:04:57'),
(16, 12, NULL, NULL, 'الأحد', '08:00:00', '09:30:00', 'D101', '2026-05-25 03:18:27', '2026-05-25 03:18:27'),
(17, 12, NULL, NULL, 'الثلاثاء', '10:00:00', '11:30:00', 'D101', '2026-05-25 03:18:27', '2026-05-25 03:18:27'),
(18, 12, NULL, NULL, 'الخميس', '08:00:00', '09:30:00', 'D101', '2026-05-25 03:18:27', '2026-05-25 03:18:27'),
(22, 2, 7, 'معلوماتية - سنة ثانية', 'Sunday', '09:30:00', '11:00:00', 'A1', '2026-06-09 19:43:29', '2026-06-09 19:43:29'),
(24, 5, 7, 'معلوماتية - سنة ثانية', 'Sunday', '11:00:00', '12:30:00', 'A1', '2026-06-09 19:44:26', '2026-06-09 19:44:26'),
(25, 4, 7, 'معلوماتية - سنة ثانية', 'Sunday', '12:30:00', '14:00:00', 'A1', '2026-06-09 19:44:57', '2026-06-09 19:44:57'),
(26, 7, 7, 'معلوماتية - سنة ثانية', 'Sunday', '14:00:00', '15:30:00', 'A1', '2026-06-09 19:45:38', '2026-06-09 19:45:38'),
(27, 7, 7, 'معلوماتية - سنة ثانية', 'Sunday', '08:00:00', '09:30:00', 'A1', '2026-06-09 19:46:11', '2026-06-09 19:46:11'),
(28, 7, 11, 'معلوماتية - سنة ثانية', 'Wednesday', '08:00:00', '09:30:00', 'A1', '2026-06-09 20:48:30', '2026-06-09 20:48:30'),
(29, 2, 7, 'معلوماتية - سنة ثانية', 'Wednesday', '09:30:00', '11:00:00', 'A1', '2026-06-09 20:49:12', '2026-06-09 20:49:12'),
(30, 5, 7, 'معلوماتية - سنة ثانية', 'Wednesday', '11:00:00', '12:30:00', 'A1', '2026-06-09 20:49:46', '2026-06-09 20:49:46'),
(31, 4, 11, 'معلوماتية - سنة ثانية', 'Wednesday', '12:30:00', '14:00:00', 'A1', '2026-06-09 20:50:10', '2026-06-09 20:50:10'),
(32, 7, 11, 'معلوماتية - سنة ثانية', 'Wednesday', '14:00:00', '15:30:00', 'A1', '2026-06-09 20:50:42', '2026-06-09 20:50:42'),
(33, 7, 11, 'معلوماتية - سنة ثانية', 'Monday', '08:00:00', '09:30:00', 'A1', '2026-06-10 03:55:52', '2026-06-10 03:55:52'),
(34, 2, 7, 'معلوماتية - سنة ثانية', 'Monday', '09:30:00', '11:00:00', 'A1', '2026-06-10 03:57:01', '2026-06-10 03:57:01'),
(35, 5, 7, 'معلوماتية - سنة ثانية', 'Monday', '11:00:00', '12:30:00', 'A1', '2026-06-10 03:57:28', '2026-06-10 03:57:28'),
(36, 4, 11, 'معلوماتية - سنة ثانية', 'Monday', '12:30:00', '14:00:00', 'A1', '2026-06-10 03:58:22', '2026-06-10 03:58:22'),
(37, 7, 11, 'معلوماتية - سنة ثانية', 'Monday', '14:00:00', '15:30:00', 'A1', '2026-06-10 03:59:09', '2026-06-10 03:59:09'),
(38, 7, 11, 'معلوماتية - سنة ثانية', 'Tuesday', '08:00:00', '09:30:00', 'A1', '2026-06-10 03:59:39', '2026-06-10 03:59:39'),
(39, 2, 7, 'معلوماتية - سنة ثانية', 'Tuesday', '09:30:00', '11:00:00', 'A1', '2026-06-10 04:00:34', '2026-06-10 04:00:34'),
(40, 5, 7, 'معلوماتية - سنة ثانية', 'Tuesday', '11:00:00', '12:30:00', 'A1', '2026-06-10 04:01:02', '2026-06-10 04:01:02'),
(41, 4, 11, 'معلوماتية - سنة ثانية', 'Tuesday', '12:30:00', '14:00:00', 'A1', '2026-06-10 04:02:27', '2026-06-10 04:02:27'),
(42, 7, 11, 'معلوماتية - سنة ثانية', 'Tuesday', '14:00:00', '15:30:00', 'A1', '2026-06-10 04:02:54', '2026-06-10 04:02:54'),
(43, 7, 11, 'معلوماتية - سنة ثانية', 'Thursday', '08:00:00', '09:30:00', 'A1', '2026-06-10 04:03:22', '2026-06-10 04:03:22'),
(44, 2, 7, 'معلوماتية - سنة ثانية', 'Thursday', '09:30:00', '11:00:00', 'A1', '2026-06-10 04:04:17', '2026-06-10 04:04:17'),
(45, 5, 7, 'معلوماتية - سنة ثانية', 'Thursday', '11:00:00', '12:30:00', 'A1', '2026-06-10 04:05:04', '2026-06-10 04:05:04'),
(46, 4, 11, 'معلوماتية - سنة ثانية', 'Thursday', '12:30:00', '14:00:00', 'A1', '2026-06-10 04:05:26', '2026-06-10 04:05:26'),
(47, 7, 11, 'معلوماتية - سنة ثانية', 'Thursday', '14:00:00', '15:30:00', 'A1', '2026-06-10 04:05:49', '2026-06-10 04:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'الفصل الأول 2026', '2026-09-01', '2027-01-15', 0, '2026-05-09 08:25:31', '2026-05-09 08:25:31'),
(2, 'الفصل الثاني 2026', '2027-02-01', '2027-06-30', 0, '2026-05-30 12:59:47', '2026-05-30 12:59:47');

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `student_code` varchar(255) NOT NULL,
  `device_id` varchar(255) DEFAULT NULL COMMENT 'معرّف الجهاز الوحيد المسموح له بتسجيل الحضور',
  `is_device_locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true = الجهاز مقفّل ولا يمكن تغييره إلا من الأدمن',
  `level` varchar(255) DEFAULT NULL,
  `face_embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`face_embedding`)),
  `reference_photo` varchar(255) DEFAULT NULL,
  `requires_face_reset` tinyint(1) NOT NULL DEFAULT 0,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `program_id`, `user_id`, `student_code`, `device_id`, `is_device_locked`, `level`, `face_embedding`, `reference_photo`, `requires_face_reset`, `birth_date`, `created_at`, `updated_at`) VALUES
(1, 8, 64, '202607', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 08:19:18', '2026-06-17 11:17:45'),
(2, 7, 67, '202608', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 08:39:16', '2026-06-17 11:17:45'),
(3, 5, 69, '202609', NULL, 0, 'السنة الثانية', '[1.3013287286343358,1.3054659816874699,1.3205590304522985,1.3125637124700364,1.3328939105150384,1.3447358214327694,1.352528830387688,1.3495683526582554,1.3387009080368826,1.3589171481448632,1.2819473900389926,1.227228434308196,1.2910426042482939,1.3607892016409169,1.283235401883771,1.2547359050918063,1.5005729979872635,1.3053661586034875,1.447261454870155,1.6370288359464145,1.5131817404552501,1.6201379499228148,1.6963382136190506,1.746968467130732,1.7610870495992028,1.7912731471288608,1.7973964116150694,1.840119698234811,1.8613360114858484,1.9122569251151955,1.8937104294759246,1.9065268066900132,1.2611613016893406,1.2782403618872444,1.3005194925249624,1.3205590304522985,1.3344753038934265,1.3297311237582623,1.3565892043441599,1.3189749742145314,0.9153119719155609,0.9890447855839375,0.5618605873336754,0.40547650652482975,-0.6696097129421831,-0.8547839506606587,-0.4933114655801171,-0.060155039621026515,0.455462695557557,-0.37721421248517634,-0.7470070916466253,0.47386956785654233,1.1873206763588438,1.562498316718889,1.6900126401054985,1.6948964435809475,1.7713475671385455,1.8065685049210332,1.8140708537582868,1.8321243802525484,1.8722918071975436,1.9165964871956729,1.9070197758350222,1.9019849355821925,1.2858310618148199,1.273293872724737,1.3054659816874699,1.3330962195423819,1.3591450640189058,1.3361821272024954,1.115670330905132,0.7279325574676836,0.4556596203095768,-1.171889011499448,-1.2456076317582205,-1.4471798449118518,-1.5340425813427379,-1.5168751700545116,-1.3424773630077076,-1.043665275087447,-0.628567642784812,-1.0769169991492789,-1.2150054441802158,-1.3012612535290715,-1.3520682092213971,-1.387984425176237,-1.0738283700732205,1.2900424725486717,1.6193940623598788,1.7708289911468373,1.8220661717405489,1.8526568873248936,1.8922173871878587,1.8750499758996328,1.8960639801233272,1.8788965688351011,1.2705357040226475,1.2736984907794235,1.3275798821382032,1.3319194442186806,1.3296542446615998,1.1155051007181476,0.5469927330269447,-0.8432751629762759,-1.4232309698054237,-1.5526659560786702,-1.5446706380964081,-1.498935660053836,-1.148305188417663,-0.42418078007349946,0.401310046096345,0.6459960290713649,0.5448494799850235,0.09164464840775097,-0.5035721587891545,-0.9764402385017201,-1.5141682736023854,-1.5666961874568195,-1.5635334007000434,-1.448393699075912,-0.185176189019948,1.5994057966825055,1.8136662357036002,1.831896464378506,1.8643848403056034,1.8661685427113348,1.8609313934311618,1.8435616731155924,1.2721170974010354,1.3305403598676357,1.3444566333087635,1.2954077731754703,1.1165422527015634,0.35411377888730744,-0.9744169725585917,-1.4574659692978937,-1.5438984808273937,-1.529007741089908,-1.008874679319475,0.05965190448504176,0.9978520025349529,1.1134334010541298,1.055389149795517,0.9924354882147562,0.8330935925358571,0.626346974345676,0.4630445277942871,0.4299863036255186,0.29329107779866515,-0.6454701179152014,-1.5781449523135234,-1.6150612414114203,-1.5382681854861937,-1.1371697934396847,0.9908338137084276,1.7862383068760312,1.8202824693348176,1.7782429888937688,1.8207639664861666,1.8253058375939877,1.3399147622009429,1.337840399677546,1.3640915531814133,1.3465309958321605,0.9401328273749853,-1.1242333705702385,-1.4687124251272543,-1.5220124962507025,-1.4728612087306128,-0.5795762011763832,0.44076539715656937,0.583737645662956,0.7268751243563272,0.9905378278720035,0.9410958216776835,0.7210654639751036,0.5917957078888414,0.4545421058139767,0.3505490369352239,0.21130942342728443,0.086669889539165,0.040783816162647854,-0.005355838491175801,-1.3253241451290854,-1.602321743293994,-1.5189495325779088,-1.1653901021076436,1.1374590967006548,1.6818406199425915,1.7727266514895903,1.7768639045427241,1.7686662775331186,1.3470124929835094,1.3501752797402855,1.3684311152618907,1.2952027427321826,0.2741499476271667,-1.4876635388212116,-1.580410151870604,-1.620665929905921,-0.5268486411539852,0.26343271922703154,0.3550025569527226,0.3668188610237542,0.4123117883392445,0.3517372842525851,0.21723037888614982,0.14687685441149667,0.11243954589168342,0.0896418392622576,0.07721860810919579,0.013569668356082436,-0.09220710292840173,-0.13237452987339687,-0.14974425018896623,-0.18553503621312495,-1.197256771213224,-1.6021194342666507,-1.4801611899839582,-0.7632122120751659,1.6030871594309895,1.7188452601308108,1.7141010799956469,1.7202243444818557,1.3764264332441527,1.394682268765758,1.3978450555225341,1.3032891332207115,-1.4033822689117705,-1.565519412133118,-1.6306357873275983,-1.256204755946433,0.11741164190089005,0.18370479241907145,0.17976984839328086,0.1822373569777047,0.08483491488347047,0.0001604989130028333,-0.07493720569681445,-0.09249776304606726,-0.15218615192669088,-0.1986791523852382,-0.20212112726601988,-0.2357606716701195,-0.27601644970543676,-0.36247456808163586,-0.35102580322493193,-0.30160940387731083,-0.33666783288875834,-1.5156613158904515,-1.6130752299783453,-1.4747332036701017,1.3749846046494851,1.5875894926114738,1.6629778573389564,1.6755150464290394,1.3903427066852807,1.3935054934420568,1.3882683441618835,1.0385131606245346,-1.4897264293509485,-1.5833706296000363,-1.59818449024086,-0.5863974066943235,0.1009280367913565,0.17541881431914358,0.19039790514695137,0.18379314350939358,-4.181011434055068e-5,-0.09319304121841952,-0.1081977388929264,-0.19700940791652802,-0.2236651794750822,-0.26853970771488245,-0.319865239398916,-0.38616986191075736,-0.37239315325335,-0.46456991806107106,-0.541146530105915,-0.35940013241518187,-0.28563023990644615,-0.8843658425242308,-1.6144543143293901,-1.5912634616389374,0.2984594536734437,1.5837172928293062,1.62773131270977,1.6547033512326883,1.4066125307738109,1.4141148796110645,1.4250706753227596,0.9474451011781331,-1.6016264651216416,-1.660252036615585,-1.5297030192622603,0.11996750157563599,0.14557464915711424,0.14287928325521282,-0.02915350115052885,-0.059684193907194885,-0.21982385370180751,-0.2749818434682703,-0.3039258171277896,-0.29343738371440437,-0.3348699370734229,-0.3440676372260856,-0.44882133282362807,-0.5096891598873323,-0.5087915726876368,-0.640742618379326,-0.6222844738303775,-0.42249330022309844,-0.34046043215831956,-0.27880022525422526,-1.5146868495940935,-1.5481611638112087,-1.0631754099158637,1.3634987609524225,1.6056544910993953,1.6101963622072162,1.3960613531168025,1.420731113242282,1.4144055397287298,0.4954056314874666,-1.5802078428432602,-1.623740365572375,-1.4070468339478364,-0.018816163071088764,-0.16143778718869606,-0.8424261851671642,-1.270758947591614,-1.3180241053191755,-1.4403984396502145,-1.2719728017556742,-0.8789378562103741,-0.410942107979598,-0.3776815747834861,-0.5289910150831989,-0.953770507549225,-1.3673123533201714,-1.3502332931222674,-1.5125356665305993,-1.4623100310736046,-1.151102981706361,-0.4051775735734365,-0.2908046449429961,-1.441082187272342,-1.509740360431586,-1.2050733690419104,1.471640554877969,1.5474335376601385,1.5535568021463473,1.3944799597384145,1.398819521818892,1.3723660592876812,0.8199961848945261,-1.62008460967059,-1.6280799276528524,-0.815537113459244,-0.26489542380675735,-0.3246235543871194,-0.48301664917292453,-0.5438562065305503,-0.7590027022011158,-0.8166423354050415,-0.8175399226047373,-0.7760817623990196,-0.3752909452957245,-0.27659782849733267,-0.6679143616267738,-1.159451645493347,-1.0135074472638674,-0.9951376538052412,-0.882553811855856,-0.8520399753681734,-0.833088861674216,-0.8454608205773149,-0.6226696313132665,-0.746938259684666,-1.5871518154325026,-0.9806752381093398,1.4374824930387264,1.5031288576620092,1.495133539679747,1.3805636862972868,1.3864846417561523,1.3803613772699435,1.2399079095979435,0.29449161766582865,-1.5632427405823779,-0.09782326341656239,-0.3055584827561408,-0.6145515462597023,-0.3078210194538418,-0.1877974557976963,-0.8413235089676159,-0.9639912248322645,-0.957374991201047,-0.7440121393796482,-0.21441626562902125,0.2210424972840737,-0.7043377401362267,-1.1164262267622806,-1.0020586824071638,-0.8942702342863407,-0.8880956975501688,-0.9344747400716948,-0.9539957020073233,-0.8660702067463223,-0.4570048249801946,-0.2444283238374143,-1.5053609980948053,0.009708237124561958,1.224777899105885,1.4721335240229776,1.4659219084464468,1.3807659953246303,1.3835241640267195,1.3575380637938184,1.2283309933946145,0.6504591787792866,-1.473051987207732,0.03501389748116272,-0.5531934714669333,-0.924595955156284,-0.7819939087236044,-1.3833427309844344,-1.5187357515569055,-1.2444423284281791,-1.2235450035574502,-0.9681029295952642,0.2277956913961973,0.8956170292152323,-0.5774815575250458,-1.1373378035991784,-1.1391868545513473,-1.1341236860358743,-1.2787940072736146,-1.266573085147896,-1.0621543522018535,-1.0522357670301392,-0.8360493394036488,-0.19981612745263622,-1.4237239389504326,-1.0324275112792374,0.4720594966045341,1.4114079831589383,1.438291670591535,1.3696078905855917,1.3590567129285835,1.3434450881720466,1.2466637080128817,0.9558645563434449,-1.2209263996390813,0.12252336125038193,0.09008619901668272,-0.5418560602444361,-1.156491226320479,-1.1473959535546128,-1.4621448008866202,-1.2182168403275755,-0.9460517733881538,-0.5998065761374032,0.3958793969231092,0.5515027924566003,-0.3604488149488227,-1.0871351121295036,-1.0241611694208022,-1.1873354646255654,-1.4953797272360332,-1.4544401430220237,-1.3894006469242055,-1.1413469053056868,-0.7841142177782058,-0.26246499406269264,-1.302170371278992,-0.8907891625922686,1.3420033181339437,1.419605610168544,1.423400930854049,1.3698101996129353,1.3547171508481062,1.3018102257856845,1.2617682287713707,0.7074769294920005,-0.7795264001391806,0.14934436299592044,0.45809803863177967,0.4795166609101608,-0.10084648538961809,-0.6595630349804086,-0.791046718373788,-0.6950746328805616,-0.3583231801754626,0.27437704294506676,0.5535029972992791,0.45969628827915116,-0.1469321463775344,-0.6359931710819681,-0.9818910516897668,-0.9221399771220848,-0.9338166578528313,-0.9900886786993726,-0.9228979409814954,-0.7804071896264576,-0.557783893408773,-0.17031383610167058,-1.0195026189600158,-0.357896263019904,1.2797591281351388,1.2623894663761346,1.4376078644128423,1.3005565713653213,1.2800240642929757,1.3264031068145017,1.0270751057619127,0.5991247794044076,-1.045884528113323,0.10383730082739094,0.1924466608236492,0.2778163549664691,0.20342806338204333,-0.13700208921216042,-0.30307417645929835,-0.20941850421655062,-0.0530512211201235,0.2537421084859249,0.8079141241095151,0.4307814634384178,0.01270921855331069,-0.650034815897212,-0.7683514976877236,-0.6543629645405945,-0.6692537042780801,-0.6198258329367992,-0.6211165661975216,-0.5695374532361817,-0.5638188068046597,-0.18552084280352088,-1.0650723668156035,1.0606918818483873,1.2235754583789202,1.337435781622859,1.3664451038288157,1.3100078527952905,1.27395207205673,1.1741960793150932,0.8509819473996992,0.6765672255273468,-1.535131005576117,0.027397590706888034,0.02054196920796797,0.25557702458505455,0.358911894131814,0.26271183420798017,0.09733502513319453,-0.11826741939858566,0.2366090546221137,0.06859956533248474,0.05179969325858655,-0.15067010709474032,-0.4050096219705078,-0.48452376775746475,-0.9284769640727322,-0.4785233288991227,-0.478637286836144,-0.5307349757891919,-0.6009117980832008,-0.6227350384162688,-0.5304699225182256,-0.2791394362059096,-0.8177395687727012,1.09591276107431,1.2731172291006574,1.3255055196148062,1.3392194840285907,1.2992543661109386,1.2017635729263825,1.2320380215463624,0.9314705009264491,0.29697326110329175,-1.5277426146758846,-0.36576544762847235,-0.08477891177186632,0.09860015154721795,0.15012799225859486,-0.0015348524024064586,-0.09132098772236596,-0.1732487069844042,-0.22640655046475303,-0.9526052627757486,-0.44172366059762636,-0.5504865164582422,-0.7360795656410087,-0.732007719690877,-0.9321725202308205,-0.3682302933535168,-0.38536062580138375,-0.48397692205967824,-0.5342025575166728,-0.591044426604885,-0.5671468237484202,-0.17093495659330488,-0.4860000123331121,1.155663835641992,1.1769684999833516,1.2851101767957687,1.2939916099840665,1.293333410652073,1.2775194768681928,1.2636032034270652,1.1158355025355513,-0.46443023616422086,-1.3183403137269751,-0.37444190893004775,-0.19626285749421263,-0.15943491948663782,-0.2333187699323948,-0.26804673856987354,-0.33199781043431226,0.021262854227019366,0.17729086781519718,-0.5372999957270114,-0.6758071936656747,-0.8715724092974617,-0.862918891983206,-1.00127239028511,-0.7292724949761076,-0.4214305413929833,-0.5511932666242545,-0.5514724547482601,-0.6239003417463103,-0.5812654062168913,-0.4763862221321027,-0.39897819199014306,0.33771504145257414,1.2558101944721463,1.1918962600046314,1.260870641571675,1.2809985305893337,1.2042310815108062,1.24261480605007,1.2179450459245906,0.6851863854171876,-0.603752230157276,-1.243305353360781,-1.3849497312095216,-0.5177303072876693,-0.3768210664241495,-0.35974200622624564,-0.47903043289717073,-0.3650560346030809,-0.1113861324964016,-0.010137038910134052,-0.36412131000646136,-0.7510073842188534,-0.7017049428082536,-0.7920609263698842,-0.8236631870909461,-0.8014724075435502,-0.6960631754733938,-0.7358259843637022,-0.6856886999970297,-0.6145259394130032,-0.5684375570091427,-0.45752345952846746,0.8725515478988959,1.0305912383234124,1.2408311036443385,1.2306845440420169,1.2516729414190124,1.2380729349422492,1.25158459032869,1.1399752146756628,-0.4585667577867847,0.7522037287002477,-0.6895696503569131,-0.5492726037376172,-0.2197753028677886,-0.7900889912332837,-0.3911048790796049,-0.384893263503074,-0.41686306343846347,-0.5381145575551437,-0.10117074093212075,-0.7321587564682575,-0.7733633939532335,-0.8284471674824132,-0.8921844583258489,-0.9339332786492319,-0.9207122833804565,-0.8471076210587052,-0.8634914030842566,-0.784547105538971,-0.7390771222108006,-0.6745305952579914,-0.6088956440718033,-0.2597722324636056,1.0872705986405846,1.1558404207095063,1.199486901375642,1.2040287724834626,1.2275217572852408,1.2229798861774202,1.2186403240969428,1.1137328117495109,0.6897369485461593,0.6681073252192838,0.4795935400068231,-0.04432082470920567,0.47840262983008247,-0.11172186003256414,-0.4994744717660643,-0.42664208382645724,-0.5337979394619861,-0.6357537246577009,-0.8955954420846078,-0.8479256663023592,-0.8712048700831341,-1.1680397557046003,-1.3276979183478637,-1.1947724063598164,-1.0606701190480683,-1.0983700374086391,-1.0308515607327378,-0.9220259606284988,-0.7605840955795038,-0.6823236042129104,-0.5783049284874586,0.964705427275862,1.160419370657686,1.1334729389814664,1.1629752303324317,1.1661380170892077,1.1675171014402526,1.1492612659186476,0.4907489818864821,0.18420314583940392,0.34660794663453215,1.1453748727268758,0.8712379292074184,0.6034353097792289,0.9023330273738671,1.0130077955434045,0.8756631209622735,-0.4002257001356054,-0.5218676774539331,-0.6388025534774556,-0.549409564218523,-0.5555328287047319,-0.7859491338773353,-0.8410073005598161,-0.9067306028363264,-0.9277446070600209,-0.9717700989341442,-0.8409189494694941,-0.8722818223228533,-0.8543422537656129,-0.7766630826343506,-0.6626886843402606,-0.3180477658986528,0.9277522350073009,1.1202776091159545,1.1508426592970358,1.1122566257304287,1.1492612659186476,1.1373310039105946,1.1617984550087308,1.0829054297134082,1.1639496966287897,1.2500146689439622,1.0461287053992314,1.110433123068394,0.6468538160147573,0.6558519285560207,1.0690518419593384,1.0391395482783625,0.45060171895629764,-0.48931655728321266,-0.5419955664715916,-0.5771281531637573,-0.6611072909618726,-0.6928862538635785,-0.7928560276262185,-0.8905606018317782,-0.8465351099576547,-0.8348840360736074,-0.8322027464681804,-0.8921190512228463,-0.7492351538067819,-0.7837608134169172,-0.6223445552146214,-0.28716639019664253,-0.07329637582063063,0.905498711216282,0.8775522063970056,0.9510428522251706,1.0331613499643275,1.121833337091079,1.1407844507850364,1.1835334028080413,1.190605468187344,1.183393779467756,1.2088613037089493,1.2082287697802199,1.1635052783178,0.82250533603849,0.9036494260378536,-0.4327821460250846,-1.0221697151558389,-0.5170351462284468,-0.4628630947520018,-0.5319629648062916,-0.5328234731656282,-0.5944095223890045,-0.5827584485049571,-0.6370444579184233,-0.65875374031447,-0.800358376463472,-0.8567956274969973,-0.7843677404989473,-0.760091126434495,-0.8482189892794041,-0.7936537917419323,-0.5372371929268235,-0.6433700314319755,-1.059037453419717,-0.5493218580146488,0.5915476279999882,0.9508149363511281,1.0322125490712335,1.0601333884872464,1.2013589548716959,1.2148706102581366,1.2196147903933006,1.1972217018185618,1.2027380392227405,1.0784148308555503,0.2567510784928116,-0.37462127397007133,-0.8705210639044416,-0.5039395808903526,-0.3868792749361488,-0.5432862997323143,-0.5244491439753781,-0.5580886883794778,-0.5601515789092149,-0.6408910508538918,-0.6927864307795965,-0.7082840975991123,-0.7839887292909598,-0.7757911022813541,-0.826307397856014,-0.8579980096673978,-0.8190073580461039,-0.7349398691576665,-0.6446722366863579,-0.7861514429046788,-1.1915070751031143,-0.7077142493574412,-0.5646907286010914,-0.25631606417321984,0.3133731959548139,0.9396311662088258,1.1805472596753446,1.1940589150617857,1.2000682216109733,0.8780051996160164,0.5436956486486421,0.20196062794067654,0.02844893609990817,-0.11289603105345095,-0.3172581074742069,-0.22434898565377456,-0.3579583038205142,-0.4946420576537078,-0.5860095863520554,-0.6281630247301253,-0.6193584706384894,-0.6609049819345292,-0.7127120107699118,-0.8199818243424619,-0.841400446620843,-0.860149251287457,-0.8203864423971486,-0.8391352470637627,-0.7902003448674906,-0.7842793894086253,-0.7666304809690503,-0.8743932636866092,-1.2508933318724125,-0.6834120284462895,-0.6285932496315112,-0.6688604996604883,-0.4307880874573069,-0.0652181495799346,-0.052068766245627576,0.982491237639778,0.7141072979762573,0.545240021743236,0.45246774329057976,0.3819119097885832,0.28263741419829547,0.09297860678373372,-0.3142720228980751,-0.18467452785378832,-0.29600471538281015,-0.4054257705754194,-0.4816118994186158,-0.5710304955242477,-0.631123502459558,-0.63684214889108,-0.6994910155011363,-0.7044375046636437,-0.8004352555601343,-0.8385283199817325,-0.8265980579736796,-0.800751522524499,-0.7575352667597489,-0.8379584717400613,-0.8425003428478821,-0.9180910166027082,-1.2380398758179647,-0.7166840336360614,-0.6303398731968834,-0.8274841731797152,-0.4658977301621525,-0.2815556725403703,0.04837723750616764,0.5269982625181052,0.6458051920376815,0.7144633651969252,0.7441679655752342,0.646765523481,0.4576165414804306,0.2595092471949009,-0.8036439302915499,-0.170960563440004,-0.30054658649063093,-0.3123884974083617,-0.45901650181653336,-0.5514839267419199,-0.6030886465499591,-0.6277584066754384,-0.6460142421970437,-0.7334468268696005,-0.7254515088873382,-0.7548654491479817,-0.7902003448674906,-0.765328275714668,-0.8027375339575736,-0.8223724538302234,-0.8349979940106287,-0.9016957625834968,-1.1402982227720462,-0.7319793914282339,-0.6632841394286308,-0.7233771463639411,-0.47653725890948295,-0.3912673878506451,-0.48896385636493694,0.8952494900009045,0.7874867072833456,0.7435866453399033,0.8454284725985968,0.6975097349297026,0.517646804172118,0.40474669525836804,-0.3093254751790027,-0.17412335019678005,-0.182397856303048,-0.3962536772694558,-0.48153502032195356,-0.5301792624005601,-0.6128676669379529,-0.6526304758282613,-0.6664583981790668,-0.7171770027810703,-0.7045514626006651,-0.739974709410496,-0.7749049870753182,-0.7964119604440215,-0.7790422401284522,-0.7592050112284592,-0.8042049693989405,-0.8816562246561602,-1.0958310340028772,-0.7211490256472199,-0.6719747355832455,-0.6069352394854276,-0.657309248860423,-0.5679162596014905,-0.8862985637344111,-0.7607500877660658,0.8234143952318455,0.8430122362641361,0.9077866790909876,0.755528379341616,0.5505027193135431,0.6401863686901414,0.13952287949224418,-0.1373324910295643,-0.07492573370315453,-0.297460678830517,-0.4132443863770374,-0.5172374552557903,-0.5671839025887792,-0.6567677288813953,-0.6879653715477702,-0.6893444558988149,-0.6929118607102775,-0.6678374825301115,-0.6964421866813815,-0.7375840799227344,-0.7364073045990333,-0.7142934041482998,-0.7334468268696005,-0.7702491580304762,-0.9882820323063211,-0.7303211189531835,-0.60763051765778,-0.5895769911635182,-0.5314443888145834,-0.3926093933613307]', NULL, 0, '2006-01-01', '2026-06-09 08:44:20', '2026-06-17 12:36:57'),
(4, 6, 71, '202610', NULL, 0, 'السنة الثانية', '[2.4467102519274606,2.561790325509703,2.4467102519274606,2.4467102519274606,2.423694237211012,2.4467102519274606,2.4697262666439093,2.377662207778115,2.4697262666439093,2.4006782224945633,2.4006782224945633,2.3546461930616664,2.285598148912321,2.239566119479424,2.1705180753300786,1.0887653836569982,0.7665411776267189,0.9276532806418585,0.90463726592541,1.2498774866721378,1.5490856779859685,1.7792458251504535,2.1014700311807326,1.9633739428820418,1.9633739428820418,1.9633739428820418,1.8713098840162476,1.8482938692997992,1.8482938692997992,1.8022618398669021,1.710197781001108,1.6641657515682111,1.3879735749708288,2.5387743107932548,2.423694237211012,2.4467102519274606,2.4697262666439093,2.423694237211012,2.4697262666439093,2.4006782224945633,2.3546461930616664,2.4006782224945633,2.377662207778115,1.9173419134491447,-0.10806738159832467,-0.7294997789424346,-0.4763236170615009,-0.4533076023450524,-0.6834677495095376,-0.8906118819575742,-0.7294997789424346,-0.01600332273253061,0.6284450893280278,1.0197173395076526,1.4570216191201744,1.710197781001108,1.9403579281655932,1.9403579281655932,1.9633739428820418,1.8252778545833506,1.8252778545833506,1.7792458251504535,1.756229810434005,1.7332137957175566,-0.6834677495095376,-0.06203535216542764,0.5593970451786823,2.3546461930616664,2.423694237211012,2.4927422813603575,2.4467102519274606,2.3086141636287696,0.5593970451786823,-0.7985478230917802,-0.9596599261069197,-0.5914036906437435,-0.4533076023450524,-0.4533076023450524,-0.43029158762860387,-0.20013144046411874,-0.2921954993299128,-0.31521151404636133,-0.7294997789424346,-0.9136278966740227,-0.8675958672411257,-0.4763236170615009,0.2601888538648516,0.858605236492513,1.4109895896872773,1.6411497368517625,1.8713098840162476,1.8713098840162476,1.8482938692997992,1.8252778545833506,1.756229810434005,1.7332137957175566,0.16812479499905753,-0.24616346989701576,-0.8445798525246772,-0.31521151404636133,0.3062208832977486,2.0784540164642844,1.5030536485530714,-0.20013144046411874,-1.0747399996891622,-0.8906118819575742,-0.8445798525246772,-0.7294997789424346,-0.6374357200766405,-0.6834677495095376,-0.4533076023450524,-0.660451734793089,-0.7294997789424346,-0.7294997789424346,-0.5914036906437435,-0.49933963177794943,-0.6834677495095376,-0.706483764225986,-0.8675958672411257,-0.24616346989701576,0.37526892744709417,0.7665411776267189,1.2728935013885863,1.6641657515682111,1.8022618398669021,1.8482938692997992,1.8022618398669021,1.7792458251504535,-0.3382275287628098,-0.6834677495095376,-0.24616346989701576,-0.1540994110312217,-0.7525157936588831,-1.0056919555398167,-0.8215638378082286,-1.0517239849727138,-0.7985478230917802,-0.7294997789424346,-0.6834677495095376,-0.4763236170615009,-0.568387675927295,-0.6834677495095376,-1.0056919555398167,-0.8906118819575742,-0.8906118819575742,-0.9366439113904712,-0.6834677495095376,-0.8445798525246772,-0.7985478230917802,-0.7985478230917802,-0.8215638378082286,-0.8906118819575742,-0.9366439113904712,-0.3382275287628098,0.35225291273064563,1.1117813983734466,1.5260696632695199,1.7792458251504535,1.8022618398669021,1.7792458251504535,-0.43029158762860387,0.2832048685813001,-0.31521151404636133,-0.8215638378082286,-0.7294997789424346,-0.7294997789424346,-0.8675958672411257,-0.8215638378082286,-0.706483764225986,-0.614419705360192,-0.8445798525246772,-0.9826759408233683,-0.8445798525246772,-0.8675958672411257,-0.9366439113904712,-0.6374357200766405,-0.7755318083753316,-0.6374357200766405,-0.8906118819575742,-1.0056919555398167,-1.0517239849727138,-1.143788043838508,-1.143788043838508,-1.0517239849727138,-1.235852102704302,-1.235852102704302,-0.5914036906437435,0.2832048685813001,0.950669295358307,1.3189255308214833,1.756229810434005,1.8022618398669021,0.2601888538648516,-0.24616346989701576,-0.5453716612108465,-0.4763236170615009,-1.0287079702562654,-1.0977560144056109,-1.0517239849727138,-1.0056919555398167,-0.9366439113904712,-0.8906118819575742,-0.8675958672411257,-0.8215638378082286,-0.660451734793089,-0.7985478230917802,-0.7755318083753316,-0.8675958672411257,-0.9136278966740227,-1.0287079702562654,-1.1207720291220593,-1.2128360879878535,-1.1207720291220593,-1.0977560144056109,-1.1668040585549564,-1.3049001468536474,-1.5350602940181326,-1.4429962351523387,-1.41998022043589,-0.8215638378082286,0.32923689801419714,0.950669295358307,1.4570216191201744,1.7332137957175566,0.5363810304622337,0.5363810304622337,0.35225291273064563,-0.4072755729121554,-1.0056919555398167,-1.143788043838508,-1.0287079702562654,-0.9136278966740227,-0.7525157936588831,-0.8215638378082286,-0.7294997789424346,-0.568387675927295,-0.568387675927295,-0.614419705360192,-0.5453716612108465,-0.6834677495095376,-0.8906118819575742,-1.0056919555398167,-1.0056919555398167,-1.1668040585549564,-1.0747399996891622,-0.9366439113904712,-1.0287079702562654,-1.0747399996891622,-1.3509321762865445,-1.5580763087345813,-1.5350602940181326,-1.466012249868787,-0.5914036906437435,0.16812479499905753,1.0657493689405497,1.5030536485530714,0.5593970451786823,0.6284450893280278,-0.1540994110312217,-0.614419705360192,-1.0056919555398167,-1.143788043838508,-1.0517239849727138,-0.8215638378082286,-0.706483764225986,-0.31521151404636133,0.21415682443195455,0.4443169715964397,0.030028706700366423,-0.1310833963147732,-0.568387675927295,-0.7525157936588831,-1.0287079702562654,-1.0517239849727138,-1.2128360879878535,-1.0977560144056109,-0.8445798525246772,-0.9596599261069197,-1.143788043838508,-1.0747399996891622,-1.2128360879878535,-1.2128360879878535,-1.327916161570096,-1.373948191002993,-1.281884132137199,-0.706483764225986,0.39828494216354265,1.2038454572392407,0.12209276556616049,0.07606073613326346,-0.20013144046411874,-0.5453716612108465,-0.36124354347925836,0.3062208832977486,1.2959095161050347,1.5030536485530714,1.6411497368517625,1.5951177074188654,1.5490856779859685,1.5490856779859685,1.1117813983734466,0.5363810304622337,-0.01600332273253061,-0.568387675927295,-0.9136278966740227,-0.9596599261069197,-1.0287079702562654,-0.9366439113904712,-1.0517239849727138,-1.143788043838508,-1.0517239849727138,-1.0056919555398167,-1.0977560144056109,-1.235852102704302,-1.3049001468536474,-1.281884132137199,-1.2588681174207503,-1.143788043838508,-0.4072755729121554,0.7205091481938218,0.2601888538648516,0.09907675084971197,-0.08505136688187616,0.35225291273064563,1.2959095161050347,1.756229810434005,1.5030536485530714,0.90463726592541,0.21415682443195455,0.007012691983917907,-0.31521151404636133,0.09907675084971197,0.39828494216354265,0.37526892744709417,0.19114080971550604,-0.10806738159832467,-0.5453716612108465,-0.7985478230917802,-1.1207720291220593,-1.3049001468536474,-1.235852102704302,-1.1898200732714048,-1.1207720291220593,-1.3049001468536474,-1.373948191002993,-1.41998022043589,-1.3509321762865445,-1.5120442793016842,-1.4429962351523387,-1.3049001468536474,-0.568387675927295,0.5133650157457852,1.3419415455379318,1.1117813983734466,1.3419415455379318,1.7332137957175566,1.8713098840162476,1.756229810434005,1.710197781001108,1.3649575602543804,0.7895571923431675,0.4443169715964397,-0.4072755729121554,-0.9136278966740227,-0.8445798525246772,-0.5914036906437435,-0.36124354347925836,-0.17711542574767022,-0.3382275287628098,-0.38425955819570684,-0.614419705360192,-0.9136278966740227,-1.0977560144056109,-1.1898200732714048,-1.4429962351523387,-1.4429962351523387,-1.5120442793016842,-1.5810923234510297,-1.4890282645852355,-1.41998022043589,-1.3509321762865445,-1.2128360879878535,-0.38425955819570684,0.7435251629102704,1.3189255308214833,1.2498774866721378,1.434005604403726,1.618133722135314,1.7792458251504535,1.756229810434005,1.4109895896872773,0.7435251629102704,0.09907675084971197,-0.24616346989701576,-0.5914036906437435,-0.614419705360192,-0.9366439113904712,-0.9596599261069197,-0.7755318083753316,-0.43029158762860387,-0.06203535216542764,-0.17711542574767022,-0.24616346989701576,-0.49933963177794943,-0.568387675927295,-0.8675958672411257,-1.0977560144056109,-1.373948191002993,-1.4890282645852355,-1.41998022043589,-1.466012249868787,-1.5580763087345813,-1.4429962351523387,-1.327916161570096,-0.17711542574767022,1.042733354224101,1.3649575602543804,1.2959095161050347,1.4109895896872773,1.5260696632695199,1.3879735749708288,1.4570216191201744,1.1578134278063437,0.6054290746115794,-0.20013144046411874,-0.5223556464943979,-0.6834677495095376,-0.8906118819575742,-1.0287079702562654,-1.0056919555398167,-0.9136278966740227,-0.5453716612108465,-0.2691794846134643,-0.17711542574767022,-0.1310833963147732,-0.17711542574767022,-0.3382275287628098,-0.4072755729121554,-0.706483764225986,-1.0977560144056109,-1.3049001468536474,-1.41998022043589,-1.4890282645852355,-1.5350602940181326,-1.5120442793016842,-1.3049001468536474,-0.22314745518056725,0.9967013247912041,1.2728935013885863,1.2959095161050347,1.3879735749708288,1.4570216191201744,1.4109895896872773,1.4109895896872773,1.3419415455379318,0.90463726592541,0.3062208832977486,0.05304472141681494,-0.5453716612108465,-0.6834677495095376,-0.7525157936588831,-0.6834677495095376,-0.38425955819570684,0.030028706700366423,0.16812479499905753,-0.01600332273253061,-0.22314745518056725,-0.22314745518056725,-0.17711542574767022,-0.1310833963147732,-0.38425955819570684,-0.568387675927295,-1.0977560144056109,-1.281884132137199,-1.5350602940181326,-1.604108338167478,-1.5350602940181326,-1.3049001468536474,0.3062208832977486,1.3189255308214833,1.3189255308214833,1.3879735749708288,1.434005604403726,1.5030536485530714,1.5260696632695199,1.5490856779859685,1.572101692702417,1.3879735749708288,1.1578134278063437,0.9736853100747556,0.7435251629102704,0.7665411776267189,1.1578134278063437,1.434005604403726,1.4570216191201744,0.9276532806418585,-0.06203535216542764,-0.614419705360192,-1.0056919555398167,-0.8675958672411257,-0.43029158762860387,-0.24616346989701576,-0.17711542574767022,-0.38425955819570684,-0.7294997789424346,-0.9826759408233683,-1.373948191002993,-1.3969642057194416,-1.466012249868787,-1.41998022043589,-1.0287079702562654,1.4109895896872773,1.1578134278063437,1.1578134278063437,1.2959095161050347,1.3649575602543804,1.3649575602543804,1.3189255308214833,1.3189255308214833,1.1578134278063437,1.2268614719556892,1.2038454572392407,1.6411497368517625,2.1244860458971813,2.1014700311807326,2.0554380017478358,1.5951177074188654,0.5824130598951308,-0.3382275287628098,-0.9596599261069197,-1.2588681174207503,-1.2588681174207503,-1.235852102704302,-0.8906118819575742,-0.4533076023450524,-0.3382275287628098,-0.49933963177794943,-0.7525157936588831,-1.2588681174207503,-1.4890282645852355,-1.3049001468536474,-1.0287079702562654,-0.7985478230917802,-1.1207720291220593,0.950669295358307,0.950669295358307,1.0197173395076526,1.180829442522792,1.180829442522792,1.1578134278063437,1.0197173395076526,0.9967013247912041,1.5030536485530714,1.5260696632695199,1.756229810434005,2.14750206061363,2.1705180753300786,1.9863899575984902,1.2498774866721378,0.32923689801419714,-0.706483764225986,-0.7985478230917802,-0.8675958672411257,-0.9596599261069197,-1.0747399996891622,-1.2588681174207503,-0.8215638378082286,-0.5914036906437435,-0.7294997789424346,-1.0517239849727138,-1.4429962351523387,-1.327916161570096,-1.2128360879878535,-1.1207720291220593,-1.1207720291220593,-0.8675958672411257,0.6744771187609249,0.5593970451786823,0.37526892744709417,0.23717283914840306,0.23717283914840306,0.6974931334773734,1.2728935013885863,1.434005604403726,0.90463726592541,0.5593970451786823,0.4903490010293367,0.950669295358307,1.042733354224101,0.9736853100747556,0.5133650157457852,-0.20013144046411874,-0.614419705360192,-0.7294997789424346,-0.7294997789424346,-0.568387675927295,-0.5223556464943979,-0.6834677495095376,-0.8675958672411257,-1.0977560144056109,-1.1898200732714048,-1.327916161570096,-1.3509321762865445,-0.9596599261069197,-1.1207720291220593,-1.1207720291220593,-1.0517239849727138,-1.0287079702562654,0.4443169715964397,0.32923689801419714,0.07606073613326346,-0.24616346989701576,-0.08505136688187616,0.09907675084971197,0.4673329863128882,0.6514611040444763,0.4213009568799912,0.007012691983917907,-0.31521151404636133,-0.06203535216542764,-0.039019337448979126,-0.06203535216542764,0.007012691983917907,-0.06203535216542764,-0.22314745518056725,-0.4533076023450524,-0.7525157936588831,-0.5914036906437435,-0.6374357200766405,-0.49933963177794943,-0.7525157936588831,-1.0287079702562654,-1.3509321762865445,-1.327916161570096,-1.3049001468536474,-1.3969642057194416,-1.2588681174207503,-1.0517239849727138,-0.9826759408233683,-1.143788043838508,0.2832048685813001,0.3062208832977486,0.12209276556616049,0.09907675084971197,-0.1540994110312217,-0.20013144046411874,-0.2921954993299128,-0.3382275287628098,-0.4533076023450524,-0.31521151404636133,-0.2691794846134643,-0.4533076023450524,-0.4072755729121554,-0.24616346989701576,0.05304472141681494,0.05304472141681494,0.09907675084971197,0.19114080971550604,0.007012691983917907,0.030028706700366423,-0.22314745518056725,-0.4533076023450524,-1.0056919555398167,-1.373948191002993,-1.5350602940181326,-1.4429962351523387,-1.5120442793016842,-1.327916161570096,-1.2588681174207503,-1.373948191002993,-1.281884132137199,-1.0747399996891622,0.19114080971550604,0.12209276556616049,0.16812479499905753,-0.01600332273253061,-0.1310833963147732,-0.24616346989701576,-0.5453716612108465,-0.614419705360192,-0.5914036906437435,-0.49933963177794943,-0.43029158762860387,-0.1540994110312217,-0.08505136688187616,-0.01600332273253061,0.07606073613326346,0.21415682443195455,0.2601888538648516,0.2832048685813001,0.145108780282609,0.030028706700366423,-0.31521151404636133,-0.36124354347925836,-0.2691794846134643,-0.49933963177794943,-0.8215638378082286,-1.1207720291220593,-1.5350602940181326,-1.466012249868787,-1.466012249868787,-1.373948191002993,-1.0517239849727138,-1.5350602940181326,0.09907675084971197,0.12209276556616049,0.145108780282609,0.145108780282609,-0.17711542574767022,-0.22314745518056725,-0.38425955819570684,-0.5914036906437435,-0.6374357200766405,-0.614419705360192,-0.568387675927295,-0.49933963177794943,-0.3382275287628098,-0.1540994110312217,0.12209276556616049,0.23717283914840306,0.21415682443195455,0.16812479499905753,0.007012691983917907,-0.3382275287628098,-0.36124354347925836,-0.4072755729121554,-0.49933963177794943,-0.7985478230917802,-1.1207720291220593,-1.2588681174207503,-1.327916161570096,-1.41998022043589,-1.6731563823168236,-1.6501403676003752,-1.466012249868787,-1.3969642057194416,-0.10806738159832467,-0.22314745518056725,-0.17711542574767022,-0.039019337448979126,-0.08505136688187616,-0.1540994110312217,-0.20013144046411874,-0.24616346989701576,-0.22314745518056725,-0.3382275287628098,-0.4763236170615009,-0.5223556464943979,-0.38425955819570684,-0.24616346989701576,-0.1540994110312217,-0.01600332273253061,-0.17711542574767022,-0.22314745518056725,-0.24616346989701576,-0.10806738159832467,-0.1540994110312217,-0.1540994110312217,-0.2921954993299128,-0.22314745518056725,-1.0747399996891622,-1.327916161570096,-1.3049001468536474,-1.281884132137199,-1.2588681174207503,-1.3969642057194416,-1.604108338167478,-1.5580763087345813,-0.2691794846134643,-0.38425955819570684,-0.38425955819570684,-0.3382275287628098,-0.2921954993299128,-0.1540994110312217,-0.06203535216542764,-0.08505136688187616,-0.039019337448979126,-0.06203535216542764,-0.10806738159832467,-0.31521151404636133,-0.2921954993299128,-0.2921954993299128,-0.3382275287628098,-0.24616346989701576,0.007012691983917907,-0.01600332273253061,0.05304472141681494,-0.5453716612108465,-0.10806738159832467,0.007012691983917907,0.145108780282609,-0.01600332273253061,0.07606073613326346,0.21415682443195455,-0.8445798525246772,-1.281884132137199,-1.2588681174207503,-1.235852102704302,-1.327916161570096,-1.327916161570096,-0.3382275287628098,-0.31521151404636133,-0.36124354347925836,-0.4072755729121554,-0.43029158762860387,-0.4072755729121554,-0.43029158762860387,-0.4763236170615009,-0.4072755729121554,-0.4763236170615009,-0.4072755729121554,-0.43029158762860387,-0.2921954993299128,-0.08505136688187616,0.3062208832977486,0.32923689801419714,0.32923689801419714,0.3062208832977486,0.145108780282609,0.12209276556616049,-0.5914036906437435,-0.43029158762860387,-0.10806738159832467,0.23717283914840306,0.2832048685813001,0.05304472141681494,0.7205091481938218,0.9276532806418585,0.09907675084971197,-1.2128360879878535,-1.1668040585549564,-1.0977560144056109,-0.2691794846134643,-0.38425955819570684,-0.3382275287628098,-0.24616346989701576,-0.2921954993299128,-0.3382275287628098,-0.2921954993299128,-0.24616346989701576,-0.10806738159832467,-0.24616346989701576,-0.20013144046411874,0.05304472141681494,0.32923689801419714,0.35225291273064563,0.39828494216354265,0.32923689801419714,0.39828494216354265,0.39828494216354265,0.4443169715964397,0.4213009568799912,0.3062208832977486,0.145108780282609,-0.20013144046411874,-0.4072755729121554,0.07606073613326346,0.7205091481938218,1.042733354224101,0.90463726592541,0.9276532806418585,0.950669295358307,0.8355892217760644,-0.9366439113904712,-0.2921954993299128,-0.24616346989701576,-0.2691794846134643,-0.2691794846134643,-0.1310833963147732,-0.08505136688187616,-0.10806738159832467,-0.08505136688187616,-0.06203535216542764,-0.039019337448979126,0.2832048685813001,0.32923689801419714,0.39828494216354265,0.4443169715964397,0.39828494216354265,0.4903490010293367,0.4673329863128882,0.4443169715964397,0.5133650157457852,0.5133650157457852,0.5824130598951308,0.5133650157457852,0.6284450893280278,0.7205091481938218,0.7435251629102704,0.90463726592541,0.950669295358307,0.950669295358307,0.90463726592541,0.950669295358307,0.9967013247912041,1.0197173395076526,-0.2921954993299128,-0.22314745518056725,-0.17711542574767022,-0.10806738159832467,-0.06203535216542764,-0.1310833963147732,0.007012691983917907,-0.08505136688187616,0.007012691983917907,0.09907675084971197,0.32923689801419714,0.39828494216354265,0.32923689801419714,0.4443169715964397,0.3062208832977486,0.37526892744709417,0.5133650157457852,0.4903490010293367,0.5593970451786823,0.5363810304622337,0.6514611040444763,0.6974931334773734,0.7205091481938218,0.7895571923431675,0.7205091481938218,0.7665411776267189,0.8355892217760644,0.9276532806418585,0.9967013247912041,0.950669295358307,1.0197173395076526,1.0887653836569982,-0.2691794846134643,-0.38425955819570684,-0.4072755729121554,-0.4072755729121554,-0.3382275287628098,-0.24616346989701576,-0.36124354347925836,-0.5914036906437435,-0.36124354347925836,-0.1540994110312217,0.16812479499905753,0.2832048685813001,0.4213009568799912,0.32923689801419714,0.35225291273064563,0.4213009568799912,0.4443169715964397,0.37526892744709417,0.5363810304622337,0.5824130598951308,0.6054290746115794,0.6284450893280278,0.6744771187609249,0.7435251629102704,0.7435251629102704,0.7665411776267189,0.858605236492513,0.90463726592541,0.9276532806418585,1.0197173395076526,1.0197173395076526,1.0657493689405497,0.32923689801419714,0.05304472141681494,-0.08505136688187616,-0.24616346989701576,-0.3382275287628098,-0.31521151404636133,-0.4072755729121554,-0.49933963177794943,-0.5223556464943979,-0.43029158762860387,-0.06203535216542764,0.23717283914840306,0.2601888538648516,0.35225291273064563,0.4443169715964397,0.4443169715964397,0.5133650157457852,0.4443169715964397,0.5133650157457852,0.5824130598951308,0.6514611040444763,0.6974931334773734,0.6744771187609249,0.7205091481938218,0.6744771187609249,0.6974931334773734,0.8355892217760644,0.90463726592541,0.9276532806418585,0.9967013247912041,1.0197173395076526,1.0887653836569982,0.9276532806418585,0.6054290746115794,0.21415682443195455,0.07606073613326346,-0.06203535216542764,-0.039019337448979126,-0.08505136688187616,-0.039019337448979126,-0.20013144046411874,-0.1310833963147732,-0.1310833963147732,0.145108780282609,0.2601888538648516,0.2601888538648516,0.37526892744709417,0.35225291273064563,0.4673329863128882,0.4673329863128882,0.5133650157457852,0.6284450893280278,0.6514611040444763,0.6514611040444763,0.6514611040444763,0.7435251629102704,0.7435251629102704,0.7205091481938218,0.812573207059616,0.8816212512089615,0.950669295358307,1.0197173395076526,1.042733354224101,1.0887653836569982]', NULL, 0, '2006-01-01', '2026-06-09 08:47:43', '2026-06-17 11:42:54'),
(5, 4, 74, '202605', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:00:51', '2026-06-17 11:17:45'),
(6, 3, 76, '202606', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:04:28', '2026-06-17 11:17:45'),
(7, 1, 78, '202601', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:09:38', '2026-06-17 11:17:45'),
(8, 2, 80, '202602', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:13:02', '2026-06-17 11:17:45'),
(9, 10, 83, '202603', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:34:07', '2026-06-17 11:17:45'),
(10, 9, 85, '202604', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-01-01', '2026-06-09 09:36:45', '2026-06-17 11:17:45'),
(11, 1, 87, '202611', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 11:10:15', '2026-06-17 11:17:45'),
(12, 2, 89, '202612', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 11:15:46', '2026-06-17 11:17:45'),
(13, 10, 91, '202613', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 11:18:02', '2026-06-17 11:17:45'),
(14, 9, 93, '202614', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2006-01-01', '2026-06-09 11:30:45', '2026-06-17 11:17:45'),
(16, 8, 96, '202615', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:09:24', '2026-06-17 11:17:45'),
(17, 7, 98, '202616', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:13:16', '2026-06-17 11:17:45'),
(18, 6, 100, '202617', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:18:08', '2026-06-17 11:17:45'),
(19, 5, 102, '202618', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:21:42', '2026-06-17 11:17:45'),
(20, 4, 104, '202619', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:24:34', '2026-06-17 11:17:45'),
(21, 3, 106, '202620', NULL, 0, 'السنة الأولى', NULL, NULL, 0, '2007-01-01', '2026-06-09 12:26:35', '2026-06-17 11:17:45'),
(22, 1, 128, '202621', NULL, 0, 'السنة الثانية', NULL, NULL, 0, '2006-11-01', '2026-06-09 20:14:49', '2026-06-17 11:17:45'),
(23, 1, 131, 'IT2024002', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:23:59', '2026-06-19 10:23:59'),
(24, 1, 132, 'IT2024003', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(25, 1, 133, 'IT2024004', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(26, 9, 134, 'IT2024005', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(27, 9, 135, 'IT2024006', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15');
INSERT INTO `students` (`student_id`, `program_id`, `user_id`, `student_code`, `device_id`, `is_device_locked`, `level`, `face_embedding`, `reference_photo`, `requires_face_reset`, `birth_date`, `created_at`, `updated_at`) VALUES
(28, 9, 136, 'IT2024007', NULL, 0, NULL, '[0.24243727112277558,0.34888202002607593,0.40210439447772606,0.4819379561552013,0.4819379561552013,0.5351603306068515,0.5617715178326765,0.6149938922843268,0.6149938922843268,0.6416050795101518,0.721438641187627,0.694827453961802,0.721438641187627,0.721438641187627,0.45532676892937624,-0.10350816281295049,0.002936586090349838,0.4819379561552013,0.8012722028651023,0.8544945773167525,0.8811057645425775,0.9077169517684026,0.9077169517684026,0.8811057645425775,0.8811057645425775,0.8811057645425775,0.8811057645425775,0.6682162667359769,0.694827453961802,0.6682162667359769,0.721438641187627,0.721438641187627,0.29565964557442576,0.375493207251901,0.40210439447772606,0.5085491433810264,0.5085491433810264,0.5617715178326765,0.6149938922843268,0.6416050795101518,0.6682162667359769,0.6682162667359769,0.721438641187627,0.7480498284134521,0.7746610156392773,0.7746610156392773,0.8278833900909274,0.7746610156392773,0.8278833900909274,0.8811057645425775,0.8012722028651023,0.8278833900909274,0.9077169517684026,0.9343281389942277,0.9609393262200528,0.9609393262200528,0.9343281389942277,0.9343281389942277,0.9609393262200528,0.6416050795101518,0.694827453961802,0.721438641187627,0.7480498284134521,0.7746610156392773,0.24243727112277558,0.4819379561552013,0.5085491433810264,0.5351603306068515,0.5085491433810264,0.5617715178326765,0.5883827050585017,0.694827453961802,0.721438641187627,0.7480498284134521,0.7480498284134521,0.721438641187627,0.8278833900909274,0.9875505134458779,1.2004400112524785,1.2802735729299537,1.360107134607429,1.306884760155779,1.2004400112524785,1.0939952623491782,1.014161700671703,1.014161700671703,0.9609393262200528,0.9077169517684026,0.9077169517684026,0.9077169517684026,1.2270511984783037,0.721438641187627,0.7480498284134521,0.694827453961802,0.7480498284134521,0.8012722028651023,0.29565964557442576,0.4287155817035512,0.5617715178326765,0.5883827050585017,0.5617715178326765,0.6149938922843268,0.6416050795101518,0.6682162667359769,0.721438641187627,0.8012722028651023,1.014161700671703,1.1738288240266535,1.3867183218332542,1.4399406962849042,1.4399406962849042,1.4399406962849042,1.4399406962849042,1.3867183218332542,1.3867183218332542,1.306884760155779,1.1738288240266535,1.0939952623491782,0.9875505134458779,0.6416050795101518,0.8544945773167525,0.8811057645425775,1.4665518835107294,0.8544945773167525,0.8012722028651023,0.7746610156392773,0.8811057645425775,0.8544945773167525,0.10938133499365017,0.45532676892937624,0.5351603306068515,0.5351603306068515,0.5883827050585017,0.6149938922843268,0.6416050795101518,0.721438641187627,1.014161700671703,1.1206064495750032,1.333495947381604,1.4133295090590792,1.360107134607429,1.3867183218332542,1.3867183218332542,1.3867183218332542,1.360107134607429,1.3867183218332542,1.360107134607429,1.333495947381604,1.306884760155779,1.2270511984783037,1.1206064495750032,0.9077169517684026,0.694827453961802,0.5883827050585017,1.652830194091505,0.9343281389942277,0.8544945773167525,0.8811057645425775,0.8544945773167525,0.8544945773167525,-0.023674601135475246,0.34888202002607593,0.5351603306068515,0.5617715178326765,0.5883827050585017,0.6682162667359769,0.8278833900909274,0.9343281389942277,1.1472176368008284,1.2802735729299537,1.3867183218332542,1.333495947381604,1.333495947381604,1.333495947381604,1.360107134607429,1.4133295090590792,1.4665518835107294,1.360107134607429,1.306884760155779,1.306884760155779,1.1738288240266535,1.2004400112524785,1.0673840751233532,0.9609393262200528,0.7746610156392773,0.5351603306068515,0.5351603306068515,0.9875505134458779,0.8544945773167525,0.9343281389942277,0.8544945773167525,0.694827453961802,-0.36962003507120134,0.2158260838969505,0.5617715178326765,0.5883827050585017,0.6149938922843268,0.9609393262200528,0.8811057645425775,1.1472176368008284,1.2802735729299537,1.333495947381604,1.306884760155779,1.333495947381604,1.333495947381604,1.306884760155779,1.333495947381604,0.721438641187627,0.26904845834860064,0.3222708328002508,0.34888202002607593,0.2158260838969505,0.10938133499365017,-0.05028578836130033,-0.10350816281295049,-0.42284240952285146,0.5617715178326765,0.5617715178326765,0.29565964557442576,0.40210439447772606,0.8811057645425775,0.9609393262200528,0.8811057645425775,0.721438641187627,-1.194566839071779,0.056158960542,0.375493207251901,0.5617715178326765,0.8278833900909274,0.9343281389942277,1.2536623857041287,1.2802735729299537,1.333495947381604,1.2802735729299537,1.2004400112524785,1.2004400112524785,1.1738288240266535,0.4819379561552013,0.02954777331617492,0.24243727112277558,0.34888202002607593,0.40210439447772606,0.34888202002607593,0.375493207251901,0.26904845834860064,0.056158960542,0.002936586090349838,-0.20995291171625083,-0.28978647339372604,-0.36962003507120134,0.34888202002607593,0.13599252221947525,0.7746610156392773,0.9077169517684026,0.9343281389942277,0.8278833900909274,-2.0993472047498316,-0.10350816281295049,0.29565964557442576,0.6682162667359769,0.9609393262200528,1.1738288240266535,1.2270511984783037,1.2802735729299537,1.2270511984783037,1.0939952623491782,1.2004400112524785,0.5883827050585017,-0.023674601135475246,0.13599252221947525,0.26904845834860064,0.29565964557442576,0.375493207251901,0.34888202002607593,0.40210439447772606,0.3222708328002508,0.16260370944530034,0.02954777331617492,-0.023674601135475246,-0.10350816281295049,-0.263175286167901,-0.47606478397450164,-0.5558983456519769,-0.10350816281295049,0.6149938922843268,0.6682162667359769,0.9343281389942277,0.7480498284134521,-2.604959762040508,-0.6623430945552772,0.13599252221947525,0.8278833900909274,0.9875505134458779,1.360107134607429,1.2004400112524785,1.2536623857041287,1.1206064495750032,0.9609393262200528,0.34888202002607593,0.002936586090349838,-0.2365640989420759,-0.47606478397450164,-0.5292871584261518,-1.247789213523429,-0.18334172449042574,0.375493207251901,0.4819379561552013,0.40210439447772606,0.18921489667112543,0.02954777331617492,-0.05028578836130033,-0.13011935003877556,-0.31639766061955116,-0.5292871584261518,-0.7421766562327524,-0.6091207201036271,0.4819379561552013,0.8278833900909274,0.8278833900909274,0.8278833900909274,-1.9929024558465314,-1.56712346023333,0.002936586090349838,0.8278833900909274,1.0673840751233532,1.360107134607429,1.1472176368008284,1.1472176368008284,0.8278833900909274,0.18921489667112543,-0.13011935003877556,-0.18334172449042574,-0.13011935003877556,-0.15673053726460065,-0.18334172449042574,-0.263175286167901,-0.7155654690069274,-1.8598465197174059,-2.0727360175240066,-0.05028578836130033,0.34888202002607593,0.08277014776782508,-0.023674601135475246,-0.13011935003877556,-0.263175286167901,-0.5292871584261518,-0.7953990306844027,-0.8220102179102277,0.40210439447772606,0.6149938922843268,0.694827453961802,0.7746610156392773,-0.7687878434585775,-2.3388478897822575,-0.05028578836130033,1.014161700671703,1.1472176368008284,1.2004400112524785,1.1206064495750032,0.9077169517684026,0.08277014776782508,-0.05028578836130033,-0.10350816281295049,-0.263175286167901,-0.47606478397450164,-0.6623430945552772,-0.5825095328778019,-0.4494535967486766,-0.5558983456519769,-0.6091207201036271,-0.5825095328778019,-0.7421766562327524,-0.18334172449042574,0.02954777331617492,-0.13011935003877556,-0.31639766061955116,-0.36962003507120134,-0.5026759712003267,-0.6357319073294522,-0.8486214051360528,0.29565964557442576,0.5617715178326765,0.6149938922843268,0.6416050795101518,0.10938133499365017,-2.4452926386855576,-0.023674601135475246,0.9875505134458779,1.2004400112524785,1.1206064495750032,0.8544945773167525,0.02954777331617492,-0.023674601135475246,0.056158960542,-0.10350816281295049,-0.42284240952285146,-1.56712346023333,-2.3920702642339076,-2.9775163832020595,-2.844460447072934,-1.56712346023333,-1.221178026297604,-0.6357319073294522,-0.07689697558712541,0.26904845834860064,-0.07689697558712541,-0.5825095328778019,-1.56712346023333,-2.285625515330607,-2.285625515330607,-2.259014328104782,-1.886457706943231,0.24243727112277558,0.5617715178326765,0.4819379561552013,0.5351603306068515,0.34888202002607593,-1.3276227752009042,0.24243727112277558,1.0939952623491782,1.1472176368008284,1.040772887897528,0.34888202002607593,-0.15673053726460065,-0.023674601135475246,0.02954777331617492,0.002936586090349838,-0.023674601135475246,-0.3962312222970264,-1.1413444646201287,-1.4074563368783795,-1.7001793963624554,-1.194566839071779,-1.0082885284910033,-0.28978647339372604,-0.13011935003877556,0.10938133499365017,-0.28978647339372604,-1.0881220901684785,-1.51390108578168,-1.1413444646201287,-1.1147332773943037,-1.3010115879750792,-1.6203458346849802,0.10938133499365017,0.45532676892937624,0.40210439447772606,0.5085491433810264,0.2158260838969505,-0.15673053726460065,0.8278833900909274,1.2004400112524785,1.2536623857041287,0.9077169517684026,0.056158960542,-0.15673053726460065,-0.07689697558712541,0.02954777331617492,0.056158960542,-0.023674601135475246,-0.15673053726460065,-0.263175286167901,-0.28978647339372604,-0.5558983456519769,-0.6091207201036271,-0.36962003507120134,-0.28978647339372604,-0.13011935003877556,-0.05028578836130033,-0.5825095328778019,-1.51390108578168,-2.3920702642339076,-2.179180766427307,-1.4872898985558547,-1.6469570219108052,-1.56712346023333,0.3222708328002508,0.45532676892937624,0.40210439447772606,0.375493207251901,-0.36962003507120134,0.16260370944530034,1.040772887897528,1.1206064495750032,1.1738288240266535,0.694827453961802,-0.18334172449042574,-0.20995291171625083,-0.07689697558712541,0.08277014776782508,0.18921489667112543,0.2158260838969505,0.13599252221947525,-0.10350816281295049,-0.13011935003877556,0.002936586090349838,0.002936586090349838,-0.05028578836130033,-0.15673053726460065,-0.2365640989420759,-0.20995291171625083,-0.6091207201036271,-1.4872898985558547,-1.5937346474591552,-2.1259583919756566,-2.0993472047498316,-2.3654590770080826,-1.0348997157168283,0.40210439447772606,0.4287155817035512,0.375493207251901,0.34888202002607593,-0.20995291171625083,0.4287155817035512,1.014161700671703,1.0939952623491782,1.1206064495750032,0.5351603306068515,-0.3430088478453762,-0.2365640989420759,-0.07689697558712541,0.02954777331617492,0.16260370944530034,0.24243727112277558,0.2158260838969505,0.18921489667112543,0.16260370944530034,0.13599252221947525,0.10938133499365017,-0.15673053726460065,-0.36962003507120134,-0.28978647339372604,-0.2365640989420759,-0.5292871584261518,-1.221178026297604,-1.221178026297604,-1.4606787113300297,-1.7267905835882804,-1.4340675241042047,-0.31639766061955116,0.4287155817035512,0.40210439447772606,0.375493207251901,0.40210439447772606,-0.023674601135475246,0.6416050795101518,1.1472176368008284,1.2536623857041287,1.014161700671703,0.45532676892937624,-0.42284240952285146,-0.263175286167901,-0.07689697558712541,0.002936586090349838,0.13599252221947525,0.2158260838969505,0.24243727112277558,0.16260370944530034,0.056158960542,-0.07689697558712541,-0.263175286167901,-0.3430088478453762,-0.2365640989420759,-0.18334172449042574,0.2158260838969505,-0.42284240952285146,-0.901843779587703,-0.7953990306844027,-0.9816773412651782,-0.928454966813528,-0.8752325923618779,0.24243727112277558,0.375493207251901,0.375493207251901,0.40210439447772606,0.34888202002607593,0.08277014776782508,0.9343281389942277,1.0939952623491782,1.360107134607429,1.2004400112524785,0.375493207251901,-0.36962003507120134,-0.263175286167901,-0.15673053726460065,-0.10350816281295049,0.02954777331617492,0.08277014776782508,0.002936586090349838,-0.10350816281295049,-0.18334172449042574,0.056158960542,-0.31639766061955116,-0.6623430945552772,-1.7001793963624554,-1.3808451496525544,-0.9816773412651782,-1.51390108578168,-1.0082885284910033,-0.7155654690069274,-0.7687878434585775,-0.8752325923618779,-0.6357319073294522,0.8278833900909274,0.29565964557442576,0.29565964557442576,0.34888202002607593,0.34888202002607593,0.056158960542,1.0673840751233532,1.2004400112524785,1.2270511984783037,1.2270511984783037,0.3222708328002508,-0.36962003507120134,-0.28978647339372604,-0.2365640989420759,-0.18334172449042574,-0.10350816281295049,-0.15673053726460065,-0.18334172449042574,-0.07689697558712541,-0.023674601135475246,-0.05028578836130033,0.002936586090349838,-0.05028578836130033,-0.13011935003877556,-1.0082885284910033,-1.7267905835882804,-1.4340675241042047,-0.901843779587703,-0.7155654690069274,-0.7421766562327524,-0.8486214051360528,-0.47606478397450164,0.5617715178326765,0.3222708328002508,0.26904845834860064,0.26904845834860064,0.26904845834860064,-0.023674601135475246,1.2270511984783037,1.2802735729299537,0.9609393262200528,1.2536623857041287,0.5085491433810264,-0.36962003507120134,-0.3430088478453762,-0.263175286167901,-0.263175286167901,-0.2365640989420759,-0.20995291171625083,-0.2365640989420759,-0.2365640989420759,-0.15673053726460065,-0.13011935003877556,-0.13011935003877556,-0.28978647339372604,-0.47606478397450164,-0.6091207201036271,-1.0082885284910033,-0.928454966813528,-0.8220102179102277,-0.7953990306844027,-0.8486214051360528,-0.928454966813528,0.08277014776782508,0.3222708328002508,0.2158260838969505,0.24243727112277558,0.18921489667112543,0.18921489667112543,0.29565964557442576,1.2802735729299537,1.3867183218332542,1.0939952623491782,1.1738288240266535,0.6416050795101518,-0.47606478397450164,-0.3962312222970264,-0.3962312222970264,-0.3430088478453762,-0.31639766061955116,-0.3962312222970264,-0.5026759712003267,-1.0082885284910033,-1.7534017708141056,-1.8598465197174059,-1.6735682091366304,-1.4606787113300297,-1.4606787113300297,-1.3808451496525544,-1.0348997157168283,-0.9550661540393531,-0.9816773412651782,-1.1147332773943037,-1.221178026297604,-0.3430088478453762,0.10938133499365017,-0.5292871584261518,0.18921489667112543,0.13599252221947525,0.16260370944530034,0.2158260838969505,0.375493207251901,1.2802735729299537,1.4665518835107294,1.2270511984783037,1.2270511984783037,0.9875505134458779,-0.10350816281295049,-0.36962003507120134,-0.4494535967486766,-0.5026759712003267,-0.5292871584261518,-0.5558983456519769,-0.5558983456519769,-0.5026759712003267,-0.8220102179102277,-0.901843779587703,-0.7953990306844027,-1.3542339624267294,-1.8598465197174059,-2.498515013137208,-2.179180766427307,-1.3010115879750792,-1.2744004007492542,-1.3276227752009042,-1.0615109029426535,0.2158260838969505,0.2158260838969505,-0.5026759712003267,-1.8332353324915809,-1.8066241452657557,-1.7001793963624554,-1.4872898985558547,0.8811057645425775,1.306884760155779,1.4665518835107294,1.2802735729299537,1.2536623857041287,0.9343281389942277,0.5617715178326765,-0.5558983456519769,-0.5292871584261518,-0.5558983456519769,-0.4494535967486766,-0.47606478397450164,-0.47606478397450164,-0.6091207201036271,-0.6091207201036271,-1.0348997157168283,-1.3542339624267294,-1.1147332773943037,-1.3808451496525544,-1.7267905835882804,-1.4340675241042047,-1.3276227752009042,-1.4074563368783795,-1.4074563368783795,-0.13011935003877556,0.08277014776782508,0.16260370944530034,-0.928454966813528,0.13599252221947525,0.26904845834860064,0.29565964557442576,0.3222708328002508,1.2802735729299537,1.1472176368008284,1.5197742579623794,1.360107134607429,1.2004400112524785,1.2536623857041287,0.6416050795101518,0.13599252221947525,-0.6623430945552772,-0.5292871584261518,-0.3962312222970264,-0.31639766061955116,-0.3430088478453762,-0.36962003507120134,-0.4494535967486766,-0.928454966813528,-1.1413444646201287,-1.194566839071779,-1.51390108578168,-1.6203458346849802,-1.56712346023333,-1.6469570219108052,-1.9130688941690561,-2.924294008750409,-3.217017068234485,-3.110572319331185,-3.0839611321053595,-2.7380156981696335,-0.31639766061955116,-0.05028578836130033,-0.7421766562327524,0.40210439447772606,1.360107134607429,1.1738288240266535,1.4931630707365544,1.4399406962849042,1.306884760155779,1.2270511984783037,1.0673840751233532,0.375493207251901,-0.5825095328778019,-0.8220102179102277,-0.5825095328778019,-0.3962312222970264,-0.3430088478453762,-0.3430088478453762,-0.2365640989420759,-0.36962003507120134,-0.6091207201036271,-0.901843779587703,-1.3808451496525544,-1.540512273007505,-1.6469570219108052,-1.7534017708141056,-2.7380156981696335,-3.24362825546031,-3.2702394426861354,-2.551737387588858,-0.6623430945552772,0.02954777331617492,0.002936586090349838,0.02954777331617492,0.08277014776782508,0.056158960542,1.4133295090590792,1.1738288240266535,1.2802735729299537,1.4931630707365544,1.360107134607429,1.1206064495750032,1.1738288240266535,1.0673840751233532,0.24243727112277558,-0.3430088478453762,-1.1413444646201287,-0.8486214051360528,-0.7155654690069274,-0.6091207201036271,-0.6889542817811023,-0.7687878434585775,-0.9816773412651782,-1.247789213523429,-1.540512273007505,-1.8066241452657557,-1.4074563368783795,-0.6889542817811023,-1.3276227752009042,-2.0993472047498316,-2.7114045109438085,-1.9929024558465314,-1.7267905835882804,-2.3654590770080826,-0.5825095328778019,0.002936586090349838,0.10938133499365017,0.10938133499365017,1.2802735729299537,0.9343281389942277,1.1472176368008284,1.2802735729299537,1.333495947381604,1.2270511984783037,0.8811057645425775,1.040772887897528,1.040772887897528,0.4287155817035512,-0.05028578836130033,-1.2744004007492542,-1.7001793963624554,-1.6469570219108052,-1.6469570219108052,-1.7267905835882804,-1.7534017708141056,-1.9662912686207061,-1.7267905835882804,-0.8486214051360528,-0.5292871584261518,-0.4494535967486766,-1.540512273007505,-2.3654590770080826,-2.259014328104782,-2.0461248302981816,-1.9929024558465314,-1.5937346474591552,-1.247789213523429,-0.20995291171625083,-0.023674601135475246,0.02954777331617492,1.333495947381604,1.014161700671703,0.8278833900909274,0.9609393262200528,1.1206064495750032,1.1738288240266535,1.014161700671703,0.8811057645425775,0.9343281389942277,0.9343281389942277,0.4287155817035512,0.002936586090349838,-0.28978647339372604,-0.9550661540393531,-1.0881220901684785,-1.1147332773943037,-0.8752325923618779,-0.5558983456519769,-0.6091207201036271,-0.5825095328778019,-0.42284240952285146,-0.6623430945552772,-1.8598465197174059,-2.179180766427307,-1.9396800813948811,-1.8598465197174059,-1.9662912686207061,-1.6735682091366304,-0.9816773412651782,-0.07689697558712541,-0.023674601135475246,0.056158960542,1.333495947381604,1.014161700671703,0.7480498284134521,0.8012722028651023,0.9875505134458779,0.9609393262200528,1.014161700671703,0.8278833900909274,0.6416050795101518,0.5085491433810264,0.7480498284134521,0.4287155817035512,-0.07689697558712541,0.3222708328002508,0.24243727112277558,0.10938133499365017,-0.10350816281295049,-0.13011935003877556,-0.3430088478453762,-0.4494535967486766,-0.8486214051360528,-1.3010115879750792,-2.179180766427307,-2.0727360175240066,-1.8598465197174059,-1.7267905835882804,-1.886457706943231,-1.886457706943231,-0.8752325923618779,-0.13011935003877556,-0.07689697558712541,-0.023674601135475246,1.2004400112524785,0.9609393262200528,0.8278833900909274,0.8278833900909274,0.9077169517684026,0.8012722028651023,0.8278833900909274,0.9077169517684026,0.6416050795101518,0.4819379561552013,0.18921489667112543,0.6682162667359769,0.6682162667359769,-0.36962003507120134,0.002936586090349838,0.056158960542,0.16260370944530034,-0.20995291171625083,-0.6091207201036271,-0.3962312222970264,-1.6735682091366304,-2.551737387588858,-2.1525695792014816,-1.9662912686207061,-1.9130688941690561,-1.886457706943231,-1.8332353324915809,-1.7534017708141056,-1.51390108578168,-0.263175286167901,-0.15673053726460065,-0.13011935003877556,1.014161700671703,0.9609393262200528,0.5617715178326765,0.7480498284134521,0.8278833900909274,0.6416050795101518,0.6682162667359769,0.6682162667359769,0.5351603306068515,0.40210439447772606,0.26904845834860064,0.4819379561552013,-0.3430088478453762,0.34888202002607593,-0.10350816281295049,-0.8752325923618779,-0.7421766562327524,-0.9816773412651782,-1.2744004007492542,-1.194566839071779,-2.205791953653132,-2.0195136430723566,-1.9929024558465314,-1.9929024558465314,-2.0461248302981816,-1.9396800813948811,-1.8066241452657557,-1.8066241452657557,-1.8598465197174059,-1.6469570219108052,-0.18334172449042574,-0.15673053726460065]', NULL, 0, NULL, '2026-06-19 10:24:15', '2026-06-28 04:41:50'),
(29, 1, 130, 'IT2024001', NULL, 0, NULL, NULL, NULL, 0, NULL, '2026-06-19 10:24:39', '2026-06-19 10:24:39');
INSERT INTO `students` (`student_id`, `program_id`, `user_id`, `student_code`, `device_id`, `is_device_locked`, `level`, `face_embedding`, `reference_photo`, `requires_face_reset`, `birth_date`, `created_at`, `updated_at`) VALUES
(31, 1, 147, '2026100', NULL, 0, 'السنة الأولى', '[-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-2.316762099860302,-0.7861524555639919,-0.8989342188279306,-0.8183758164965459,-0.6572590118337763,-0.6411473313674995,-0.6572590118337763,-0.7217057336988841,-0.7217057336988841,-0.7217057336988841,-0.7539290946314381,-0.7055940532326073,-0.7217057336988841,-0.6894823727663303,-0.6250356509012225,-0.6894823727663303,-0.6089239704349455,-0.7217057336988841,-0.6089239704349455,-0.6572590118337763,-0.6572590118337763,-0.7539290946314381,-0.6894823727663303,-0.7700407750977151,-0.7700407750977151,-0.7700407750977151,-0.7861524555639919,-0.6250356509012225,-0.36724876344079127,-0.5122538876372839,-0.5767006095023917,-0.8505991774290997,-1.140609425822085,-1.108386064889531,-1.1728327867546386,-1.0439393430244233,-1.140609425822085,-1.0600510234907001,-1.2372795086197466,-1.140609425822085,-1.1728327867546386,-1.092274384423254,-0.44780716577217605,-0.0772385150478062,0.1161016505475172,0.32555349660911753,0.47055862080561006,0.5994520645358257,0.3416651770753945,-0.9633809406930384,-0.9794926211593153,-0.9472692602267615,-0.9633809406930384,-0.9633809406930384,-0.9150458992942075,-1.0278276625581462,-1.0439393430244233,-1.0278276625581462,-0.9956043016255923,-0.9956043016255923,-1.0278276625581462,-0.9794926211593153,-0.9956043016255923,-2.316762099860302,-2.316762099860302,1.6789346557763813,1.6628229753101043,1.6467112948438274,1.6305996143775505,1.6305996143775505,1.6467112948438274,1.6467112948438274,1.6305996143775505,1.6305996143775505,1.6305996143775505,1.6144879339112734,1.6144879339112734,1.6144879339112734,1.5983762534449966,1.5822645729787195,1.5661528925124426,1.5983762534449966,1.5822645729787195,1.5500412120461657,-0.5605889290361147,-0.6089239704349455,-0.41558380483962215,-0.28669036110940654,-0.30280204157568347,-0.28669036110940654,-0.27057868064312957,-0.22224363924429874,-0.1739085978454679,-0.141685236912914,-0.31891372204196045,-0.9472692602267615,-0.9633809406930384,-0.6572590118337763,-0.9633809406930384,-1.108386064889531,-1.0278276625581462,-1.0278276625581462,-1.2050561476871926,-1.2211678281534697,-1.2695028695523005,-1.140609425822085,-0.8989342188279306,-0.2061319587780218,-0.012791793182698399,0.2127717333451789,0.40611189894050226,0.502781981738164,0.3094418161428406,-1.0278276625581462,-0.9311575797604845,-0.9633809406930384,-0.9956043016255923,-0.9956043016255923,-0.9633809406930384,-0.9472692602267615,-0.9633809406930384,-1.0117159820918693,-1.092274384423254,-0.9633809406930384,-0.9956043016255923,-0.9633809406930384,-1.0439393430244233,-2.316762099860302,-2.316762099860302,1.6305996143775505,1.6467112948438274,1.6467112948438274,1.6305996143775505,1.6305996143775505,1.6305996143775505,1.6305996143775505,1.6305996143775505,1.5983762534449966,1.6144879339112734,1.6144879339112734,1.5983762534449966,1.5983762534449966,1.5983762534449966,1.5822645729787195,1.5500412120461657,1.5339295315798886,1.5178178511136118,1.501706170647335,-0.6733706923000533,-1.0117159820918693,-0.9472692602267615,0.0194315677498555,0.9861323957264725,0.9700207152601955,0.6800104668672105,0.43833525987305616,0.6477871059346565,0.2772184552102867,0.2127717333451789,0.180548372412625,0.16443669194634805,0.16443669194634805,0.32555349660911753,0.32555349660911753,0.502781981738164,-0.41558380483962215,-1.2856145500185774,-1.2533911890860234,-1.3178379109511313,-1.3178379109511313,-1.2211678281534697,-0.35113708297451435,-0.1094618759803601,0.1161016505475172,0.35777685754167143,0.502781981738164,0.19666005287890195,-1.0439393430244233,-0.9150458992942075,-0.9633809406930384,-1.0117159820918693,-1.0278276625581462,-1.0117159820918693,-1.0117159820918693,-0.9633809406930384,-1.0600510234907001,-1.0117159820918693,-0.9311575797604845,-1.0117159820918693,-1.0278276625581462,-0.9794926211593153,-2.316762099860302,-2.316762099860302,1.6789346557763813,1.6467112948438274,1.6628229753101043,1.4855944901810578,1.5500412120461657,1.6305996143775505,1.6305996143775505,1.6305996143775505,1.6144879339112734,1.5983762534449966,1.6305996143775505,1.6144879339112734,1.5983762534449966,1.5983762534449966,1.5822645729787195,1.5822645729787195,1.5661528925124426,1.5500412120461657,1.5500412120461657,1.3083660050520114,1.2116959222543497,1.163360880855519,0.39000021847422534,1.131137519922965,0.40611189894050226,0.5511170231369948,0.2772184552102867,0.19666005287890195,0.09998997008124025,0.09998997008124025,0.1161016505475172,0.1161016505475172,0.03554324821613245,0.16443669194634805,0.0838782896149633,0.22888341381145585,0.2127717333451789,0.22888341381145585,0.45444694033933314,-1.2372795086197466,-1.3983963132825161,-1.3983963132825161,-0.44780716577217605,-0.2061319587780218,0.06776660914868635,0.3094418161428406,0.47055862080561006,0.24499509427773278,-1.0278276625581462,-1.0117159820918693,-1.0117159820918693,-0.9956043016255923,-1.0439393430244233,-1.0439393430244233,-1.0278276625581462,-1.0600510234907001,-1.076162703956977,-1.0117159820918693,-0.9956043016255923,-0.9956043016255923,-1.0600510234907001,-0.9956043016255923,-2.316762099860302,-2.316762099860302,1.2439192831869037,1.2278076027206266,1.2922543245857345,1.4211477683159501,1.501706170647335,1.469482809714781,1.437259448782227,1.453371129248504,1.405036087849673,1.405036087849673,1.3889244073833962,1.3889244073833962,1.437259448782227,1.437259448782227,1.4211477683159501,1.3889244073833962,1.405036087849673,1.3083660050520114,1.2439192831869037,1.1794725613217958,1.1955842417880729,1.0344674371253033,0.9861323957264725,0.5833403840695487,0.48667030127188704,0.180548372412625,0.3094418161428406,0.03554324821613245,0.09998997008124025,0.0838782896149633,0.06776660914868635,0.09998997008124025,0.0194315677498555,0.051654928682409396,0.051654928682409396,0.0838782896149633,0.1161016505475172,0.09998997008124025,0.16443669194634805,0.180548372412625,0.35777685754167143,0.6800104668672105,-0.5444772485698377,-0.30280204157568347,0.0194315677498555,0.26110677474400973,0.43833525987305616,0.1483250114800711,-1.0278276625581462,-1.0278276625581462,-1.0117159820918693,-0.9956043016255923,-1.0117159820918693,-1.0278276625581462,-1.0117159820918693,-1.0439393430244233,-1.0600510234907001,-0.9956043016255923,-1.0600510234907001,-0.9956043016255923,-1.0600510234907001,-0.9794926211593153,-2.316762099860302,-2.316762099860302,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6628229753101043,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6305996143775505,1.6467112948438274,1.6144879339112734,1.6305996143775505,1.6467112948438274,1.6467112948438274,1.6144879339112734,1.3728127269171193,1.3244776855182885,1.2278076027206266,1.2116959222543497,1.1955842417880729,0.9377973543276417,1.163360880855519,0.8894623129288107,0.5188936622044409,0.40611189894050226,0.3416651770753945,0.2127717333451789,0.1161016505475172,0.13221333101379415,0.051654928682409396,0.13221333101379415,0.1483250114800711,0.0194315677498555,0.0194315677498555,0.0194315677498555,-0.028903473648975348,0.09998997008124025,0.24499509427773278,0.180548372412625,0.180548372412625,0.24499509427773278,0.3094418161428406,0.42222357940677924,-0.27057868064312957,0.003319887283578551,0.24499509427773278,0.35777685754167143,0.1161016505475172,-0.9956043016255923,-0.9794926211593153,-1.0117159820918693,-1.0278276625581462,-0.9956043016255923,-0.9956043016255923,-1.0278276625581462,-1.0278276625581462,-1.0278276625581462,-1.0600510234907001,-1.0117159820918693,-0.9794926211593153,-1.108386064889531,-0.9794926211593153,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6789346557763813,1.5339295315798886,1.3728127269171193,1.2922543245857345,1.3567010464508422,1.2600309636531806,1.0505791175915802,1.2439192831869037,0.9055739933950877,0.48667030127188704,0.6638987864009335,0.3738885380079484,0.7283455082660413,0.29333013567656363,0.8250155910637029,0.03554324821613245,0.003319887283578551,-0.012791793182698399,0.16443669194634805,0.06776660914868635,0.16443669194634805,0.13221333101379415,0.13221333101379415,-0.012791793182698399,-0.12557355644663704,0.39000021847422534,-0.1094618759803601,0.2127717333451789,0.2127717333451789,0.24499509427773278,0.2772184552102867,0.39000021847422534,0.48667030127188704,0.26110677474400973,0.3094418161428406,0.2127717333451789,-1.0278276625581462,-1.076162703956977,-1.0439393430244233,-1.0439393430244233,-0.9794926211593153,-0.9956043016255923,-0.9794926211593153,-0.9794926211593153,-1.0439393430244233,-1.092274384423254,-1.108386064889531,-0.9633809406930384,-0.9794926211593153,-1.0117159820918693,-2.316762099860302,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6144879339112734,1.437259448782227,1.405036087849673,1.2600309636531806,1.3889244073833962,1.3244776855182885,1.3405893659845654,1.2439192831869037,1.0183557566590264,0.9861323957264725,1.2278076027206266,1.163360880855519,0.43833525987305616,0.2772184552102867,0.1161016505475172,-1.0117159820918693,-0.6411473313674995,-0.463918846238453,-0.38336044390706825,-0.35113708297451435,-0.38336044390706825,-0.31891372204196045,-0.36724876344079127,-0.27057868064312957,-0.38336044390706825,-0.463918846238453,-0.33502540250823737,0.24499509427773278,-0.045015154115252295,0.180548372412625,0.13221333101379415,0.26110677474400973,0.3094418161428406,0.32555349660911753,0.502781981738164,0.42222357940677924,0.1483250114800711,-1.0439393430244233,-0.9794926211593153,-0.9956043016255923,-1.076162703956977,-1.0439393430244233,-0.9633809406930384,-0.8667108578953767,-0.9633809406930384,-1.0600510234907001,-1.0600510234907001,-0.9956043016255923,-1.0278276625581462,-0.8989342188279306,-1.0439393430244233,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.469482809714781,1.437259448782227,1.3889244073833962,1.3083660050520114,1.437259448782227,1.1794725613217958,1.098914158990411,1.3244776855182885,1.0666907980578573,1.163360880855519,0.502781981738164,0.26110677474400973,-0.44780716577217605,-0.27057868064312957,-0.22224363924429874,-0.27057868064312957,-0.31891372204196045,-0.31891372204196045,-0.31891372204196045,-0.35113708297451435,-0.33502540250823737,-0.33502540250823737,-0.35113708297451435,-0.35113708297451435,-0.30280204157568347,-0.30280204157568347,-0.30280204157568347,-0.27057868064312957,-0.22224363924429874,-0.7217057336988841,0.40611189894050226,-0.012791793182698399,0.43833525987305616,0.2772184552102867,0.39000021847422534,0.5511170231369948,0.0838782896149633,-0.9794926211593153,-1.0600510234907001,-1.108386064889531,-1.0600510234907001,-1.0117159820918693,-1.0600510234907001,-0.8505991774290997,-0.9794926211593153,-1.0439393430244233,-0.9956043016255923,-1.0278276625581462,-0.9311575797604845,-0.8344874969628228,-0.8828225383616537,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.501706170647335,1.437259448782227,1.405036087849673,1.3405893659845654,1.2600309636531806,1.3728127269171193,1.3567010464508422,1.3083660050520114,1.3889244073833962,1.0022440761927494,-0.44780716577217605,-0.141685236912914,-0.061126834581529246,-0.12557355644663704,-0.25446700017685264,-0.28669036110940654,-0.30280204157568347,-0.33502540250823737,-0.35113708297451435,-0.31891372204196045,-0.38336044390706825,-0.36724876344079127,-0.38336044390706825,-0.41558380483962215,-0.39947212437334517,-0.31891372204196045,-0.30280204157568347,-0.27057868064312957,-0.25446700017685264,-0.22224363924429874,-0.28669036110940654,-0.22224363924429874,-0.38336044390706825,0.42222357940677924,0.3094418161428406,0.6477871059346565,0.5350053426707179,0.5994520645358257,-0.7700407750977151,-1.0439393430244233,-1.0117159820918693,-1.0439393430244233,-1.076162703956977,-0.9956043016255923,-0.9794926211593153,-0.9794926211593153,-0.9633809406930384,-0.9956043016255923,-1.0278276625581462,-1.0117159820918693,0.5833403840695487,1.0183557566590264,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.711158016708935,1.4855944901810578,1.3567010464508422,1.5661528925124426,1.3405893659845654,1.3728127269171193,1.3567010464508422,1.2600309636531806,1.3728127269171193,1.2600309636531806,0.22888341381145585,-0.31891372204196045,-0.12557355644663704,0.180548372412625,0.0838782896149633,-0.09335019551408315,-0.141685236912914,-0.25446700017685264,-0.27057868064312957,-0.36724876344079127,-0.28669036110940654,-0.35113708297451435,-0.35113708297451435,-0.33502540250823737,-0.36724876344079127,-0.41558380483962215,-0.35113708297451435,-0.33502540250823737,-0.30280204157568347,-0.28669036110940654,-0.27057868064312957,-0.2061319587780218,-0.2383553197105757,-0.1739085978454679,-0.15779691737919094,-0.30280204157568347,0.19666005287890195,0.2772184552102867,0.5188936622044409,0.24499509427773278,0.5833403840695487,-1.076162703956977,-1.0278276625581462,-1.076162703956977,-1.0278276625581462,-1.0117159820918693,-1.076162703956977,-0.9472692602267615,-0.9472692602267615,-1.0439393430244233,-1.0439393430244233,-1.0117159820918693,1.7594930581077661,1.4211477683159501,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6467112948438274,1.6628229753101043,1.6628229753101043,1.6789346557763813,1.3405893659845654,1.5339295315798886,1.4211477683159501,1.2922543245857345,1.3244776855182885,1.3405893659845654,1.453371129248504,1.437259448782227,1.405036087849673,0.003319887283578551,-0.09335019551408315,0.3416651770753945,0.39000021847422534,0.29333013567656363,0.0194315677498555,-0.09335019551408315,-0.2061319587780218,-0.2383553197105757,-0.30280204157568347,-0.39947212437334517,-0.33502540250823737,-0.36724876344079127,-0.33502540250823737,-0.39947212437334517,-0.31891372204196045,-0.36724876344079127,-0.41558380483962215,-0.38336044390706825,-0.36724876344079127,-0.33502540250823737,-0.28669036110940654,-0.2383553197105757,-0.22224363924429874,-0.2061319587780218,-0.2061319587780218,-0.0772385150478062,-0.061126834581529246,-0.6572590118337763,0.13221333101379415,0.5188936622044409,0.5188936622044409,0.42222357940677924,-0.9794926211593153,-1.0600510234907001,-1.0117159820918693,-0.9633809406930384,-1.0439393430244233,-1.0278276625581462,-0.9956043016255923,-1.0278276625581462,-0.9794926211593153,-0.9956043016255923,1.7594930581077661,1.453371129248504,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6467112948438274,1.6628229753101043,1.6467112948438274,1.3567010464508422,1.3889244073833962,1.5822645729787195,1.4211477683159501,1.501706170647335,1.405036087849673,1.3567010464508422,1.405036087849673,1.4855944901810578,0.7605688691985951,-0.15779691737919094,0.6316754254683796,0.6961221473334873,0.40611189894050226,0.1483250114800711,-0.045015154115252295,-0.12557355644663704,-0.25446700017685264,-0.27057868064312957,-0.28669036110940654,-0.33502540250823737,-0.35113708297451435,-0.35113708297451435,-0.30280204157568347,-0.31891372204196045,-0.33502540250823737,-0.28669036110940654,-0.35113708297451435,-0.35113708297451435,-0.31891372204196045,-0.27057868064312957,-0.27057868064312957,-0.1739085978454679,-0.19002027831174484,-0.09335019551408315,-0.09335019551408315,0.003319887283578551,0.003319887283578551,0.0194315677498555,-0.7539290946314381,0.19666005287890195,0.35777685754167143,0.7122338277997643,0.6155637450021026,-1.092274384423254,-1.076162703956977,-1.0278276625581462,-1.140609425822085,-1.0117159820918693,-1.0600510234907001,-0.8989342188279306,-0.9472692602267615,-1.0278276625581462,1.711158016708935,1.4211477683159501,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6467112948438274,1.6628229753101043,1.5500412120461657,1.0666907980578573,1.5661528925124426,1.437259448782227,1.469482809714781,1.3889244073833962,1.2922543245857345,1.469482809714781,1.501706170647335,1.163360880855519,-0.15779691737919094,0.6638987864009335,0.9377973543276417,0.6961221473334873,0.35777685754167143,0.22888341381145585,0.051654928682409396,-0.1739085978454679,-0.19002027831174484,-0.2383553197105757,-0.30280204157568347,-0.30280204157568347,-0.30280204157568347,-0.33502540250823737,-0.33502540250823737,-0.33502540250823737,-0.31891372204196045,-0.28669036110940654,-0.33502540250823737,-0.38336044390706825,-0.31891372204196045,-0.31891372204196045,-0.30280204157568347,-0.27057868064312957,-0.2383553197105757,-0.15779691737919094,-0.12557355644663704,-0.045015154115252295,0.051654928682409396,0.180548372412625,-0.045015154115252295,-0.7055940532326073,0.42222357940677924,0.2772184552102867,0.5833403840695487,0.2772184552102867,-1.0117159820918693,-1.0439393430244233,-1.140609425822085,-1.0117159820918693,-1.0117159820918693,-0.9794926211593153,-0.9956043016255923,-0.9311575797604845,1.6789346557763813,1.405036087849673,-2.316762099860302,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6789346557763813,1.3405893659845654,1.3405893659845654,1.5661528925124426,1.5500412120461657,1.453371129248504,1.3567010464508422,1.5178178511136118,1.405036087849673,1.6144879339112734,-0.045015154115252295,0.42222357940677924,0.9700207152601955,1.0828024785241341,0.9377973543276417,0.42222357940677924,0.1161016505475172,-0.0772385150478062,-0.22224363924429874,-0.22224363924429874,-0.15779691737919094,-0.22224363924429874,-0.31891372204196045,-0.30280204157568347,-0.28669036110940654,-0.30280204157568347,-0.31891372204196045,-0.27057868064312957,-0.30280204157568347,-0.31891372204196045,-0.31891372204196045,-0.31891372204196045,-0.28669036110940654,-0.28669036110940654,-0.28669036110940654,-0.30280204157568347,-0.141685236912914,-0.09335019551408315,-0.061126834581529246,0.0838782896149633,0.19666005287890195,0.09998997008124025,-0.22224363924429874,-0.09335019551408315,0.7605688691985951,0.5994520645358257,0.6155637450021026,-0.9633809406930384,-0.9794926211593153,-0.9956043016255923,-1.108386064889531,-0.9956043016255923,-0.9311575797604845,-0.9633809406930384,-0.8828225383616537,1.6467112948438274,1.4855944901810578,-2.316762099860302,-2.316762099860302,1.6950463362426582,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6789346557763813,1.5983762534449966,1.163360880855519,1.5661528925124426,1.5178178511136118,1.437259448782227,1.3728127269171193,1.5500412120461657,1.5822645729787195,1.3083660050520114,0.9539090347939185,0.051654928682409396,1.0183557566590264,0.8411272715299799,0.9377973543276417,0.7444571887323183,0.6961221473334873,0.06776660914868635,-0.15779691737919094,-0.25446700017685264,-0.28669036110940654,-0.30280204157568347,-0.31891372204196045,-0.31891372204196045,-0.25446700017685264,-0.28669036110940654,-0.28669036110940654,-0.27057868064312957,-0.30280204157568347,-0.22224363924429874,-0.31891372204196045,-0.28669036110940654,-0.31891372204196045,-0.33502540250823737,-0.33502540250823737,-0.31891372204196045,-0.33502540250823737,-0.19002027831174484,-0.1094618759803601,-0.028903473648975348,0.03554324821613245,0.09998997008124025,0.1161016505475172,0.06776660914868635,-0.463918846238453,0.45444694033933314,0.7605688691985951,0.7122338277997643,0.3094418161428406,-1.0439393430244233,-1.076162703956977,-1.0439393430244233,-1.0600510234907001,-1.0117159820918693,-0.9794926211593153,-0.9150458992942075,1.6789346557763813,1.453371129248504,-2.316762099860302,-2.316762099860302,1.6950463362426582,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.3567010464508422,1.1794725613217958,1.5983762534449966,1.5983762534449966,1.5178178511136118,1.5178178511136118,1.3405893659845654,1.6144879339112734,1.453371129248504,0.09998997008124025,0.8572389519962569,0.9861323957264725,0.8733506324625339,0.6316754254683796,0.3416651770753945,0.26110677474400973,-0.012791793182698399,-0.30280204157568347,-0.30280204157568347,-0.38336044390706825,-0.39947212437334517,-0.36724876344079127,-0.30280204157568347,-0.28669036110940654,-0.2383553197105757,-0.25446700017685264,-0.28669036110940654,-0.28669036110940654,-0.28669036110940654,-0.25446700017685264,-0.30280204157568347,-0.38336044390706825,-0.35113708297451435,-0.4316954853058991,-0.38336044390706825,-0.36724876344079127,-0.27057868064312957,-0.25446700017685264,-0.1739085978454679,-0.12557355644663704,-0.028903473648975348,0.0838782896149633,0.1161016505475172,0.03554324821613245,0.16443669194634805,0.6477871059346565,0.7122338277997643,0.19666005287890195,-0.9633809406930384,-0.9956043016255923,-1.0600510234907001,-1.092274384423254,-0.9956043016255923,-1.0117159820918693,-0.9794926211593153,1.3889244073833962,1.437259448782227,-2.316762099860302,-2.316762099860302,1.6950463362426582,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.2761426441194574,1.0022440761927494,1.3405893659845654,1.5178178511136118,1.5983762534449966,1.5339295315798886,1.6144879339112734,1.5822645729787195,0.8250155910637029,0.5672287036032718,1.0666907980578573,1.2600309636531806,0.48667030127188704,0.5994520645358257,0.3094418161428406,0.0194315677498555,-0.012791793182698399,-0.28669036110940654,-0.6089239704349455,-0.44780716577217605,-0.5444772485698377,-0.48003052670472995,-0.44780716577217605,-0.36724876344079127,-0.28669036110940654,-0.28669036110940654,-0.28669036110940654,-0.27057868064312957,-0.2383553197105757,-0.28669036110940654,-0.31891372204196045,-0.5122538876372839,-0.5767006095023917,-0.8667108578953767,-0.7700407750977151,-1.0439393430244233,-0.6572590118337763,-0.48003052670472995,-0.38336044390706825,-0.5444772485698377,-0.19002027831174484,-0.27057868064312957,0.1161016505475172,0.24499509427773278,-0.25446700017685264,0.3094418161428406,0.6800104668672105,0.6638987864009335,0.3094418161428406,-0.9956043016255923,-0.9150458992942075,-0.9794926211593153,-0.9956043016255923,-0.9956043016255923,-1.0117159820918693,0.2772184552102867,1.469482809714781,1.2439192831869037,-2.316762099860302,1.6950463362426582,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.711158016708935,1.2761426441194574,1.5983762534449966,1.437259448782227,1.5983762534449966,1.6144879339112734,1.4855944901810578,1.3405893659845654,1.437259448782227,0.051654928682409396,1.098914158990411,1.115025839456688,0.47055862080561006,-0.045015154115252295,0.1161016505475172,-1.0117159820918693,-0.9956043016255923,-1.3500612718836853,-1.6078481593441163,-0.9311575797604845,-0.8828225383616537,-1.3017262304848543,-0.8667108578953767,-0.7217057336988841,-0.5767006095023917,-0.4316954853058991,-0.31891372204196045,-0.2383553197105757,-0.27057868064312957,-0.22224363924429874,-0.31891372204196045,-0.463918846238453,-0.9150458992942075,-0.5767006095023917,-1.2533911890860234,-1.4628430351476238,-1.1728327867546386,-1.4950663960801778,-1.2695028695523005,-1.2856145500185774,-0.8667108578953767,-1.076162703956977,-0.7055940532326073,-0.5444772485698377,0.16443669194634805,0.16443669194634805,0.3738885380079484,0.35777685754167143,0.9539090347939185,0.2127717333451789,-0.9150458992942075,-1.0600510234907001,-0.9794926211593153,-0.9794926211593153,-1.0117159820918693,-1.0439393430244233,0.180548372412625,1.4211477683159501,1.2761426441194574,-2.316762099860302,1.6950463362426582,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.7272696971752122,1.163360880855519,1.4855944901810578,1.3083660050520114,1.6467112948438274,1.6305996143775505,1.5983762534449966,1.5822645729787195,0.8894623129288107,1.098914158990411,1.098914158990411,0.8733506324625339,-0.36724876344079127,-0.44780716577217605,-0.35113708297451435,-0.30280204157568347,-1.140609425822085,-1.3339495914174082,-1.3661729523499622,-1.0278276625581462,-1.0117159820918693,-0.7378174141651611,-0.9472692602267615,-1.108386064889531,-0.9311575797604845,-0.44780716577217605,-0.25446700017685264,-0.28669036110940654,-0.33502540250823737,-0.28669036110940654,-0.35113708297451435,-0.5283655681035607,-0.8505991774290997,-1.2211678281534697,-1.3661729523499622,-1.3339495914174082,-1.2372795086197466,-1.1728327867546386,-1.414507993748793,-1.382284632816239,-1.1728327867546386,-0.6733706923000533,-0.8989342188279306,-0.6250356509012225,-0.7700407750977151,0.39000021847422534,0.22888341381145585,0.26110677474400973,0.6155637450021026,0.180548372412625,-0.9311575797604845,-0.9794926211593153,-0.9956043016255923,-1.0278276625581462,-1.0439393430244233,-1.1244977453558078,0.3416651770753945,1.4211477683159501,1.0828024785241341,-2.316762099860302,1.6789346557763813,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6950463362426582,1.2116959222543497,0.9700207152601955,1.2761426441194574,1.6305996143775505,1.711158016708935,1.5822645729787195,1.1955842417880729,0.48667030127188704,1.3567010464508422,-0.09335019551408315,0.45444694033933314,-0.028903473648975348,-0.463918846238453,-0.19002027831174484,-0.41558380483962215,-0.6572590118337763,-0.7861524555639919,-0.6089239704349455,-0.6572590118337763,-0.463918846238453,-0.44780716577217605,-0.8022641360302689,-0.6572590118337763,-0.7378174141651611,-0.28669036110940654,-0.19002027831174484,-0.28669036110940654,-0.25446700017685264,-0.25446700017685264,-0.2061319587780218,-0.36724876344079127,-0.8344874969628228,-0.8667108578953767,-0.9150458992942075,-0.5767006095023917,-0.41558380483962215,-0.4316954853058991,-0.35113708297451435,-0.28669036110940654,-0.38336044390706825,-0.2383553197105757,-0.22224363924429874,0.03554324821613245,-0.09335019551408315,0.180548372412625,-0.045015154115252295,0.6316754254683796,0.8089039105974261,0.003319887283578551,-0.9794926211593153,-0.9794926211593153,-1.0600510234907001,-1.0439393430244233,-1.0117159820918693,-1.0439393430244233,0.13221333101379415,1.4855944901810578,0.39000021847422534,-2.316762099860302,1.6789346557763813,1.6628229753101043,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.5339295315798886,0.5188936622044409,1.6789346557763813,1.5822645729787195,1.5983762534449966,1.6628229753101043,1.0828024785241341,1.2600309636531806,1.2116959222543497,1.437259448782227,0.6638987864009335,0.26110677474400973,0.0838782896149633,-0.19002027831174484,-0.2061319587780218,-0.36724876344079127,-0.41558380483962215,-0.41558380483962215,-0.41558380483962215,-0.31891372204196045,-0.22224363924429874,-0.12557355644663704,-0.141685236912914,-0.12557355644663704,-0.141685236912914,-0.1739085978454679,-0.28669036110940654,-0.2383553197105757,-0.19002027831174484,-0.22224363924429874,-0.19002027831174484,-0.33502540250823737,-0.36724876344079127,-0.38336044390706825,-0.5283655681035607,-0.5928122899686685,-0.5605889290361147,-0.463918846238453,-0.41558380483962215,-0.38336044390706825,-0.30280204157568347,-0.12557355644663704,0.003319887283578551,0.26110677474400973,0.1161016505475172,-0.19002027831174484,0.13221333101379415,0.7766805496648721,-0.2061319587780218,-0.8667108578953767,-1.0278276625581462,-1.0278276625581462,-1.0600510234907001,-1.076162703956977,-1.0278276625581462,-0.1739085978454679,1.453371129248504,0.7927922301311491,-2.316762099860302,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6950463362426582,1.6789346557763813,0.3094418161428406,1.5178178511136118,1.5500412120461657,1.6144879339112734,1.4211477683159501,0.9861323957264725,1.4211477683159501,1.501706170647335,1.2439192831869037,0.9861323957264725,0.16443669194634805,-0.2383553197105757,-0.33502540250823737,-0.36724876344079127,-0.4961422071710069,-0.5605889290361147,-0.5283655681035607,-0.6250356509012225,-0.6250356509012225,-0.44780716577217605,-0.27057868064312957,-0.1739085978454679,-0.0772385150478062,-0.028903473648975348,-0.12557355644663704,-0.2061319587780218,-0.31891372204196045,-0.19002027831174484,-0.15779691737919094,-0.2061319587780218,-0.35113708297451435,-0.5605889290361147,-0.6572590118337763,-0.7378174141651611,-0.7217057336988841,-0.8344874969628228,-0.7378174141651611,-0.6572590118337763,-0.48003052670472995,-0.25446700017685264,-0.2061319587780218,-0.028903473648975348,0.19666005287890195,0.39000021847422534,0.19666005287890195,0.24499509427773278,0.9055739933950877,-0.12557355644663704,-0.061126834581529246,-0.9794926211593153,-1.0600510234907001,-1.076162703956977,-1.1567211062883618,-1.108386064889531,-0.09335019551408315,0.502781981738164,0.42222357940677924,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6950463362426582,1.1794725613217958,0.22888341381145585,0.9377973543276417,1.6305996143775505,1.3405893659845654,1.2761426441194574,0.8411272715299799,1.5500412120461657,1.501706170647335,1.2439192831869037,0.6638987864009335,0.42222357940677924,-0.141685236912914,-0.061126834581529246,-0.44780716577217605,-0.5605889290361147,-0.6411473313674995,-0.6250356509012225,-0.7700407750977151,-0.5767006095023917,-0.5122538876372839,-0.5283655681035607,-0.36724876344079127,-0.27057868064312957,0.003319887283578551,0.06776660914868635,-0.22224363924429874,-0.36724876344079127,-0.22224363924429874,-0.061126834581529246,-0.2061319587780218,-0.44780716577217605,-0.7378174141651611,-0.8667108578953767,-0.9150458992942075,-0.9311575797604845,-0.9150458992942075,-0.7217057336988841,-0.6250356509012225,-0.5444772485698377,-0.4961422071710069,-0.028903473648975348,-0.12557355644663704,0.0838782896149633,0.26110677474400973,0.26110677474400973,0.03554324821613245,0.47055862080561006,-0.12557355644663704,0.29333013567656363,-0.9472692602267615,-1.0600510234907001,-1.0439393430244233,-1.076162703956977,-1.140609425822085,1.775604738574043,1.3083660050520114,1.131137519922965,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.6950463362426582,1.2278076027206266,0.16443669194634805,0.24499509427773278,1.5983762534449966,1.5983762534449966,1.3405893659845654,0.8411272715299799,1.5822645729787195,1.3244776855182885,1.0505791175915802,1.0344674371253033,0.22888341381145585,0.9861323957264725,-0.09335019551408315,-0.4316954853058991,-0.6411473313674995,-0.6572590118337763,-0.7055940532326073,-0.7700407750977151,-0.8667108578953767,-0.6894823727663303,-0.5928122899686685,-0.5444772485698377,-0.4316954853058991,-0.12557355644663704,-0.061126834581529246,-0.1094618759803601,-0.38336044390706825,-0.2061319587780218,-0.141685236912914,-0.2383553197105757,-0.7055940532326073,-0.9150458992942075,-1.0439393430244233,-1.0117159820918693,-1.414507993748793,-1.8011883249394398,-1.736741603074332,-1.7045182421417782,-1.5272897570127317,-0.7378174141651611,-0.09335019551408315,-0.30280204157568347,0.0838782896149633,0.24499509427773278,0.2772184552102867,-0.0772385150478062,0.5672287036032718,-0.045015154115252295,0.7283455082660413,-0.9956043016255923,-0.9794926211593153,-0.9956043016255923,-1.0278276625581462,-1.0117159820918693,-0.4316954853058991,0.42222357940677924,0.39000021847422534,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.7272696971752122,1.131137519922965,0.180548372412625,0.502781981738164,1.6144879339112734,1.6467112948438274,1.163360880855519,1.2761426441194574,1.469482809714781,1.1794725613217958,0.9700207152601955,1.0022440761927494,1.6467112948438274,1.5661528925124426,-1.5434014374790086,-1.5756247984115626,-1.736741603074332,-1.8334116858719938,-1.2372795086197466,-1.2856145500185774,-0.7055940532326073,-0.7378174141651611,-0.8505991774290997,-0.7378174141651611,-0.7217057336988841,-0.33502540250823737,0.03554324821613245,0.180548372412625,-0.30280204157568347,-0.25446700017685264,-0.15779691737919094,-0.31891372204196045,-0.6572590118337763,-0.7861524555639919,-1.3178379109511313,-0.9794926211593153,-1.6078481593441163,-1.720629922608055,-1.3017262304848543,-1.1567211062883618,0.06776660914868635,-0.31891372204196045,-1.752853283540609,-0.1739085978454679,-1.092274384423254,0.19666005287890195,0.3416651770753945,-0.2061319587780218,0.3738885380079484,-0.25446700017685264,0.22888341381145585,-0.9150458992942075,-0.9311575797604845,-1.0117159820918693,-0.9956043016255923,-1.076162703956977,-1.076162703956977,1.5822645729787195,1.2116959222543497,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6789346557763813,1.711158016708935,1.2922543245857345,0.13221333101379415,0.24499509427773278,1.5983762534449966,1.5500412120461657,0.8250155910637029,1.5500412120461657,1.3567010464508422,1.131137519922965,0.2772184552102867,-0.44780716577217605,-1.3661729523499622,-0.0772385150478062,1.6950463362426582,-1.3500612718836853,-1.8011883249394398,-1.2533911890860234,-0.31891372204196045,-0.31891372204196045,-0.7055940532326073,-1.0117159820918693,-1.076162703956977,-0.6572590118337763,-0.6089239704349455,-0.09335019551408315,0.502781981738164,0.26110677474400973,-0.15779691737919094,-0.27057868064312957,-0.2061319587780218,-0.30280204157568347,-0.39947212437334517,-0.9311575797604845,-1.3339495914174082,-1.3500612718836853,-1.752853283540609,-1.5595131179452855,-1.5434014374790086,-0.4961422071710069,0.0194315677498555,-0.39947212437334517,-1.4628430351476238,-0.463918846238453,-0.045015154115252295,0.09998997008124025,0.29333013567656363,0.03554324821613245,0.502781981738164,-0.2383553197105757,0.3416651770753945,-0.8989342188279306,-0.9311575797604845,-0.9472692602267615,-0.9794926211593153,-1.1567211062883618,-0.8989342188279306,1.6144879339112734,1.2278076027206266,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6950463362426582,1.743381377641489,1.405036087849673,0.13221333101379415,-0.19002027831174484,1.3244776855182885,1.5339295315798886,0.8733506324625339,1.405036087849673,1.2116959222543497,0.6155637450021026,0.3416651770753945,-0.028903473648975348,0.3094418161428406,-0.8828225383616537,-1.2695028695523005,-1.3178379109511313,-1.5756247984115626,-1.7850766444731627,-1.140609425822085,-0.6733706923000533,-0.7055940532326073,-0.7861524555639919,-0.8022641360302689,-0.7861524555639919,-0.33502540250823737,0.42222357940677924,0.7766805496648721,0.5833403840695487,-0.1739085978454679,-0.27057868064312957,-0.12557355644663704,-0.15779691737919094,-0.33502540250823737,-0.7217057336988841,-0.8505991774290997,-1.0600510234907001,-0.8989342188279306,-0.8828225383616537,-1.108386064889531,-0.8667108578953767,-0.48003052670472995,-0.6250356509012225,-0.141685236912914,-0.09335019551408315,-0.012791793182698399,0.1161016505475172,0.2772184552102867,0.2772184552102867,0.45444694033933314,-0.2383553197105757,-0.7217057336988841,-0.8989342188279306,-0.9311575797604845,-0.9794926211593153,-0.9956043016255923,-1.076162703956977,-0.9633809406930384,1.5661528925124426,1.2439192831869037,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6628229753101043,1.6628229753101043,1.7272696971752122,1.501706170647335,0.8089039105974261,0.051654928682409396,1.4855944901810578,1.098914158990411,1.5822645729787195,1.3567010464508422,0.8894623129288107,0.8894623129288107,0.3094418161428406,0.09998997008124025,-0.045015154115252295,0.16443669194634805,0.0194315677498555,-0.1094618759803601,-0.33502540250823737,-0.44780716577217605,-0.6572590118337763,-0.6894823727663303,-0.6733706923000533,-0.6733706923000533,-0.7217057336988841,-0.1739085978454679,0.22888341381145585,1.0666907980578573,1.0183557566590264,0.5511170231369948,-0.22224363924429874,-0.30280204157568347,-0.15779691737919094,-0.0772385150478062,-0.15779691737919094,-0.25446700017685264,-0.6733706923000533,-0.7539290946314381,-0.7217057336988841,-0.8022641360302689,-0.6894823727663303,-0.6250356509012225,-0.38336044390706825,-0.30280204157568347,-0.0772385150478062,-0.09335019551408315,-0.09335019551408315,0.03554324821613245,0.22888341381145585,0.3738885380079484,0.16443669194634805,-0.141685236912914,-0.8989342188279306,-0.9472692602267615,-0.9472692602267615,-0.9472692602267615,-0.9150458992942075,-0.9956043016255923,-1.0117159820918693,1.2922543245857345,1.3244776855182885,-2.316762099860302,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.6789346557763813,1.7272696971752122,1.6950463362426582,1.0666907980578573,-0.2383553197105757,1.4855944901810578,1.2278076027206266,1.6950463362426582,1.3244776855182885,0.9700207152601955,0.6638987864009335,0.3738885380079484,0.09998997008124025,0.051654928682409396,-0.09335019551408315,-0.36724876344079127,-0.5122538876372839,-0.5605889290361147,-0.5605889290361147,-0.5767006095023917,-0.5444772485698377,-0.6411473313674995,-0.5928122899686685,-0.15779691737919094,0.40611189894050226,0.8250155910637029,1.1955842417880729,0.8250155910637029,0.42222357940677924,-0.141685236912914,-0.30280204157568347,-0.25446700017685264,-0.1739085978454679,-0.1094618759803601,-0.045015154115252295,-0.19002027831174484,-0.33502540250823737,-0.41558380483962215,-0.5283655681035607,-0.48003052670472995,-0.39947212437334517,-0.31891372204196045,-0.30280204157568347,-0.22224363924429874,-0.141685236912914,-0.045015154115252295,0.1161016505475172,0.32555349660911753,0.40611189894050226,0.03554324821613245,-0.0772385150478062,-1.0278276625581462,-0.9956043016255923,-0.9794926211593153,-0.9956043016255923,-0.9794926211593153,-0.9956043016255923,-1.108386064889531,1.2116959222543497,1.3083660050520114,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.743381377641489,1.6950463362426582,1.3083660050520114,0.3738885380079484,1.501706170647335,1.0344674371253033,1.7272696971752122,1.5661528925124426,1.0505791175915802,0.8572389519962569,0.48667030127188704,0.0838782896149633,-0.061126834581529246,-0.30280204157568347,-0.41558380483962215,-0.463918846238453,-0.463918846238453,-0.5122538876372839,-0.5767006095023917,-0.5767006095023917,-0.463918846238453,-0.045015154115252295,0.2772184552102867,1.115025839456688,1.2116959222543497,1.0183557566590264,0.6638987864009335,0.1161016505475172,-0.22224363924429874,-0.31891372204196045,-0.28669036110940654,-0.141685236912914,-0.09335019551408315,0.0194315677498555,0.03554324821613245,-0.25446700017685264,-0.27057868064312957,-0.30280204157568347,-0.38336044390706825,-0.39947212437334517,-0.41558380483962215,-0.38336044390706825,-0.25446700017685264,-0.09335019551408315,0.003319887283578551,0.24499509427773278,0.26110677474400973,0.40611189894050226,-0.012791793182698399,0.1161016505475172,-0.9311575797604845,-1.0117159820918693,-0.9472692602267615,-1.0117159820918693,-0.9794926211593153,-1.0117159820918693,-1.0439393430244233,1.2761426441194574,1.405036087849673,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6467112948438274,1.6467112948438274,1.743381377641489,1.6467112948438274,1.5500412120461657,0.7605688691985951,1.469482809714781,1.405036087849673,1.7272696971752122,1.711158016708935,1.3405893659845654,0.9700207152601955,0.7122338277997643,0.19666005287890195,-0.028903473648975348,-0.15779691737919094,-0.35113708297451435,-0.39947212437334517,-0.5122538876372839,-0.5444772485698377,-0.41558380483962215,-0.36724876344079127,-0.28669036110940654,-0.1094618759803601,0.13221333101379415,1.131137519922965,1.2439192831869037,0.8894623129288107,0.6961221473334873,0.1161016505475172,-0.27057868064312957,-0.38336044390706825,-0.28669036110940654,-0.2383553197105757,-0.061126834581529246,0.003319887283578551,0.051654928682409396,-0.19002027831174484,-0.38336044390706825,-0.33502540250823737,-0.38336044390706825,-0.31891372204196045,-0.33502540250823737,-0.30280204157568347,-0.2061319587780218,-0.061126834581529246,0.09998997008124025,0.2127717333451789,0.32555349660911753,0.45444694033933314,0.1483250114800711,0.26110677474400973,-1.0117159820918693,-0.9472692602267615,-1.0117159820918693,-1.076162703956977,-0.9956043016255923,-1.092274384423254,-1.076162703956977,1.2278076027206266,1.3728127269171193,-2.316762099860302,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6467112948438274,1.6950463362426582,1.6144879339112734,1.6628229753101043,0.9861323957264725,-0.1739085978454679,1.3889244073833962,1.6950463362426582,1.5983762534449966,1.6467112948438274,1.131137519922965,0.8894623129288107,0.39000021847422534,0.29333013567656363,-0.09335019551408315,-0.30280204157568347,-0.33502540250823737,-0.38336044390706825,-0.39947212437334517,-0.33502540250823737,-0.30280204157568347,-0.27057868064312957,-0.2061319587780218,0.09998997008124025,0.8411272715299799,1.163360880855519,1.437259448782227,0.8250155910637029,0.0838782896149633,-0.1739085978454679,-0.28669036110940654,-0.25446700017685264,-0.141685236912914,0.1161016505475172,0.3094418161428406,0.003319887283578551,-0.2061319587780218,-0.38336044390706825,-0.39947212437334517,-0.4316954853058991,-0.33502540250823737,-0.33502540250823737,-0.22224363924429874,-0.12557355644663704,0.003319887283578551,0.0838782896149633,0.26110677474400973,0.3416651770753945,0.43833525987305616,0.39000021847422534,0.24499509427773278,-0.8989342188279306,-0.9472692602267615,-1.0600510234907001,-0.9956043016255923,-1.0117159820918693,-0.9956043016255923,-1.1244977453558078,1.2278076027206266,1.7594930581077661,-2.316762099860302,1.6628229753101043,1.6628229753101043,1.6628229753101043,1.6950463362426582,1.743381377641489,1.6789346557763813,1.5822645729787195,1.2278076027206266,0.6638987864009335,1.1955842417880729,1.5983762534449966,1.6467112948438274,1.6144879339112734,1.115025839456688,1.2116959222543497,0.5188936622044409,0.3738885380079484,-0.09335019551408315,-0.1739085978454679,-0.27057868064312957,-0.2061319587780218,-0.30280204157568347,-0.39947212437334517,-0.35113708297451435,-0.33502540250823737,-0.30280204157568347,-0.1739085978454679,1.501706170647335,1.3889244073833962,1.115025839456688,1.0828024785241341,0.6316754254683796,-0.012791793182698399,-0.12557355644663704,-0.09335019551408315,0.0194315677498555,0.06776660914868635,0.09998997008124025,0.24499509427773278,-0.39947212437334517,-0.4316954853058991,-0.39947212437334517,-0.36724876344079127,-0.33502540250823737,-0.28669036110940654,-0.1739085978454679,-0.09335019551408315,0.03554324821613245,0.13221333101379415,0.32555349660911753,0.42222357940677924,0.40611189894050226,0.003319887283578551,0.39000021847422534,-0.9311575797604845,-0.8989342188279306,-1.0600510234907001,-1.0278276625581462,-1.0600510234907001,-0.9956043016255923,-0.9956043016255923,1.3083660050520114,1.4211477683159501,-2.316762099860302,1.775604738574043,1.775604738574043,1.7917164190403199,1.7917164190403199,1.775604738574043,1.6628229753101043,1.5339295315798886,1.6144879339112734,0.9539090347939185,1.163360880855519,1.469482809714781,1.5661528925124426,1.453371129248504,1.3405893659845654,0.8250155910637029,0.7444571887323183,0.39000021847422534,0.29333013567656363,0.0194315677498555,-0.0772385150478062,-0.19002027831174484,-0.2383553197105757,-0.36724876344079127,-0.35113708297451435,-0.33502540250823737,-0.31891372204196045,-0.35113708297451435,1.163360880855519,1.0344674371253033,0.6800104668672105,0.5994520645358257,0.9539090347939185,0.6316754254683796,0.1483250114800711,-0.061126834581529246,0.051654928682409396,0.0194315677498555,-0.5928122899686685,0.03554324821613245,-0.463918846238453,-0.463918846238453,-0.39947212437334517,-0.39947212437334517,-0.33502540250823737,-0.27057868064312957,-0.2061319587780218,-0.012791793182698399,0.051654928682409396,0.16443669194634805,0.3094418161428406,0.42222357940677924,0.35777685754167143,0.051654928682409396,0.0838782896149633,-0.9311575797604845,-0.9472692602267615,-0.9956043016255923,-1.0439393430244233,-1.0117159820918693,-1.0117159820918693,-0.9794926211593153,0.3738885380079484,1.5339295315798886,-2.316762099860302,1.7917164190403199,1.7917164190403199,1.7917164190403199,1.7917164190403199,1.7917164190403199,1.6305996143775505,1.5500412120461657,1.437259448782227,1.0666907980578573,0.03554324821613245,1.405036087849673,1.5339295315798886,1.501706170647335,1.453371129248504,1.3889244073833962,1.0828024785241341,0.8250155910637029,0.3416651770753945,0.13221333101379415,0.03554324821613245,-0.061126834581529246,-0.15779691737919094,-0.25446700017685264,-0.33502540250823737,-0.35113708297451435,-0.31891372204196045,-0.35113708297451435,-0.4961422071710069,0.48667030127188704,-0.33502540250823737,0.35777685754167143,0.39000021847422534,1.0828024785241341,0.32555349660911753,-0.012791793182698399,-0.2061319587780218,-0.5444772485698377,-0.39947212437334517,-0.44780716577217605,-0.36724876344079127,-0.35113708297451435,-0.35113708297451435,-0.35113708297451435,-0.28669036110940654,-0.2061319587780218,-0.1094618759803601,-0.045015154115252295,0.03554324821613245,0.24499509427773278,0.40611189894050226,0.3738885380079484,0.3416651770753945,0.0838782896149633,-1.0600510234907001,-0.9472692602267615,-0.9956043016255923,-1.0439393430244233,-1.0117159820918693,-1.0117159820918693,-0.9472692602267615,-0.9150458992942075,0.9377973543276417,1.5661528925124426,-2.316762099860302,1.775604738574043,1.775604738574043,1.775604738574043,1.775604738574043,1.775604738574043,1.7917164190403199,1.5822645729787195,1.4855944901810578,1.5339295315798886,0.8089039105974261,0.40611189894050226,1.6144879339112734,1.469482809714781,1.437259448782227,1.3889244073833962,0.7927922301311491,0.9539090347939185,0.7605688691985951,0.43833525987305616,0.13221333101379415,-0.045015154115252295,-0.25446700017685264,-0.30280204157568347,-0.33502540250823737,-0.30280204157568347,-0.2383553197105757,-0.19002027831174484,-0.12557355644663704,-0.15779691737919094,0.32555349660911753,0.3738885380079484,-0.12557355644663704,-0.25446700017685264,-0.33502540250823737,-0.25446700017685264,-0.2061319587780218,-0.1094618759803601,-0.2061319587780218,-0.30280204157568347,-0.30280204157568347,-0.2061319587780218,-0.28669036110940654,-0.33502540250823737,-0.30280204157568347,-0.15779691737919094,-0.141685236912914,-0.045015154115252295,0.0838782896149633,0.19666005287890195,0.39000021847422534,0.40611189894050226,0.24499509427773278,0.2127717333451789,-1.0117159820918693,-0.9956043016255923,-0.9633809406930384,-1.0117159820918693,-0.9472692602267615,-0.9633809406930384,-0.9956043016255923,-0.8828225383616537,0.7605688691985951,1.469482809714781,-2.316762099860302,1.6950463362426582,1.711158016708935,1.7594930581077661,1.711158016708935,1.0022440761927494,0.5994520645358257,1.3567010464508422,1.501706170647335,1.6467112948438274,0.9539090347939185,0.2127717333451789,1.6144879339112734,1.4211477683159501,1.453371129248504,1.2600309636531806,1.1794725613217958,1.0344674371253033,1.0344674371253033,0.5511170231369948,0.3094418161428406,0.09998997008124025,-0.15779691737919094,-0.22224363924429874,-0.28669036110940654,-0.25446700017685264,0.03554324821613245,0.003319887283578551,0.0194315677498555,0.16443669194634805,0.6316754254683796,0.0838782896149633,-0.141685236912914,-0.30280204157568347,-0.35113708297451435,-0.35113708297451435,-0.22224363924429874,-0.141685236912914,-0.12557355644663704,-0.1739085978454679,-0.1739085978454679,-0.141685236912914,-0.2061319587780218,-0.27057868064312957,-0.2383553197105757,-0.12557355644663704,-0.045015154115252295,0.0194315677498555,0.06776660914868635,0.2127717333451789,0.3738885380079484,0.42222357940677924,0.0194315677498555,0.47055862080561006,-0.9956043016255923,-1.0117159820918693,-0.9633809406930384,-0.9472692602267615,-0.8989342188279306,-0.9311575797604845,-0.9472692602267615,-0.9472692602267615,0.9861323957264725,1.4211477683159501,-2.316762099860302,0.8089039105974261,0.9377973543276417,0.6800104668672105,0.3094418161428406,0.1161016505475172,0.09998997008124025,1.5661528925124426,1.405036087849673,1.2922543245857345,1.115025839456688,0.7927922301311491,1.5339295315798886,1.5339295315798886,1.3083660050520114,1.2922543245857345,1.2278076027206266,1.098914158990411,1.0666907980578573,0.9377973543276417,0.43833525987305616,0.03554324821613245,-0.028903473648975348,-0.19002027831174484,-0.25446700017685264,-0.012791793182698399,0.16443669194634805,0.39000021847422534,0.2127717333451789,0.180548372412625,0.1161016505475172,-0.0772385150478062,-0.31891372204196045,-0.36724876344079127,-0.48003052670472995,-0.48003052670472995,-0.36724876344079127,-0.28669036110940654,-0.2383553197105757,-0.15779691737919094,-0.1094618759803601,-0.1094618759803601,-0.1094618759803601,-0.15779691737919094,-0.19002027831174484,-0.15779691737919094,-0.028903473648975348,0.06776660914868635,0.0838782896149633,0.180548372412625,0.32555349660911753,0.35777685754167143,0.1483250114800711,0.42222357940677924,-0.9956043016255923,-0.9956043016255923,-0.9794926211593153,-0.9794926211593153,-0.9311575797604845,-0.9150458992942075,-0.9794926211593153,-0.9311575797604845,0.9055739933950877,1.3889244073833962,-2.316762099860302,1.0022440761927494,0.1483250114800711,0.2772184552102867,0.40611189894050226,-0.27057868064312957,-0.19002027831174484,0.5188936622044409,1.2761426441194574,1.3728127269171193,1.405036087849673,0.8089039105974261,1.5178178511136118,1.5661528925124426,1.4211477683159501,1.3083660050520114,1.2278076027206266,1.1794725613217958,1.115025839456688,1.0183557566590264,0.7927922301311491,0.19666005287890195,0.0838782896149633,-0.0772385150478062,-0.0772385150478062,0.06776660914868635,0.8250155910637029,0.7444571887323183,0.5994520645358257,0.42222357940677924,0.0838782896149633,0.003319887283578551,-0.141685236912914,-0.30280204157568347,-0.33502540250823737,-0.33502540250823737,-0.22224363924429874,-0.30280204157568347,-0.28669036110940654,-0.19002027831174484,-0.061126834581529246,-0.028903473648975348,0.0194315677498555,-0.061126834581529246,-0.09335019551408315,-0.061126834581529246,0.003319887283578551,0.0194315677498555,0.1161016505475172,0.19666005287890195,0.35777685754167143,0.3094418161428406,0.3094418161428406,-0.7539290946314381,-0.9633809406930384,-0.9633809406930384,-0.9311575797604845,-1.0439393430244233,-1.0439393430244233,-1.0117159820918693,-0.9956043016255923,-0.9472692602267615,0.5511170231369948,1.3083660050520114,-2.316762099860302,-2.316762099860302,-0.5122538876372839,-0.141685236912914,-0.48003052670472995,-0.4316954853058991,-0.5605889290361147,-0.4316954853058991,1.453371129248504,1.131137519922965,1.131137519922965,1.163360880855519,1.0344674371253033,1.5500412120461657,1.3889244073833962,1.3083660050520114,1.2600309636531806,1.2761426441194574,1.131137519922965,1.0183557566590264,1.0344674371253033,0.5511170231369948,0.2127717333451789,-0.061126834581529246,0.0194315677498555,0.7444571887323183,1.2439192831869037,0.7122338277997643,0.0194315677498555,-0.25446700017685264,-0.141685236912914,-0.36724876344079127,-0.463918846238453,-0.48003052670472995,-0.41558380483962215,-0.4961422071710069,-0.463918846238453,-0.41558380483962215,-0.33502540250823737,-0.35113708297451435,-0.36724876344079127,-0.39947212437334517,0.003319887283578551,-0.141685236912914,-0.1739085978454679,-0.141685236912914,-0.061126834581529246,0.06776660914868635,0.06776660914868635,0.180548372412625,0.24499509427773278,0.35777685754167143,0.003319887283578551,-0.8667108578953767,-0.8989342188279306,-0.9472692602267615,-0.9150458992942075,1.5661528925124426,1.3244776855182885,0.3738885380079484,0.26110677474400973,-0.7055940532326073,-0.48003052670472995,1.2922543245857345,-2.316762099860302,-2.316762099860302,-0.045015154115252295,-0.6089239704349455,-0.7217057336988841,-0.5605889290361147,-0.4961422071710069,-0.6894823727663303,1.2922543245857345,1.3083660050520114,1.453371129248504,1.5983762534449966,1.098914158990411,1.5822645729787195,1.405036087849673,1.2116959222543497,1.2439192831869037,1.147249200389242,1.0828024785241341,1.0666907980578573,0.9377973543276417,0.40611189894050226,0.13221333101379415,-0.141685236912914,-0.0772385150478062,0.9539090347939185,-0.0772385150478062,0.1161016505475172,0.2127717333451789,-0.045015154115252295,-0.09335019551408315,-0.09335019551408315,-0.25446700017685264,-0.36724876344079127,-0.4316954853058991,-0.463918846238453,-0.463918846238453,-0.6411473313674995,-0.7055940532326073,-0.5767006095023917,-0.5122538876372839,-0.6250356509012225,-0.8344874969628228,-1.1889444672209157,-0.39947212437334517,-0.2061319587780218,-0.0772385150478062,0.0838782896149633,0.13221333101379415,0.180548372412625,0.26110677474400973,0.2772184552102867,0.19666005287890195,-0.8183758164965459,-0.9633809406930384,-0.9311575797604845,-0.9633809406930384,-0.6411473313674995,1.6305996143775505,1.2600309636531806,0.43833525987305616,0.32555349660911753,0.051654928682409396,-0.061126834581529246,-2.316762099860302,-2.316762099860302,-0.7055940532326073,-0.7378174141651611,-0.8989342188279306,-0.6250356509012225,-0.463918846238453,-0.5605889290361147,-0.41558380483962215,1.6467112948438274,0.9861323957264725,1.131137519922965,1.405036087849673,1.4855944901810578,1.453371129248504,1.2116959222543497,1.2600309636531806,1.115025839456688,1.115025839456688,1.0344674371253033,0.8572389519962569,0.19666005287890195,-0.2061319587780218,-0.5444772485698377,-0.7378174141651611,-0.8667108578953767,-0.5928122899686685,0.1483250114800711,0.19666005287890195,0.0194315677498555,-0.38336044390706825,-0.5767006095023917,-0.5767006095023917,-0.6250356509012225,-0.5605889290361147,-0.4961422071710069,-0.5122538876372839,-0.4316954853058991,-0.4316954853058991,-0.31891372204196045,-0.33502540250823737,-0.44780716577217605,-0.2383553197105757,-0.39947212437334517,-0.41558380483962215,-0.30280204157568347,-0.141685236912914,0.051654928682409396,0.13221333101379415,0.13221333101379415,0.3094418161428406,0.1161016505475172,0.3738885380079484,-0.8828225383616537,-0.9150458992942075,-0.9472692602267615,-0.8828225383616537,-0.9794926211593153,-1.1244977453558078,1.4211477683159501,1.1794725613217958,0.9055739933950877,0.5350053426707179,0.09998997008124025,-2.316762099860302,-2.316762099860302,-0.9150458992942075,-0.9633809406930384,-0.5605889290361147,-0.6411473313674995,-0.5444772485698377,-0.38336044390706825,-0.31891372204196045,1.775604738574043,1.3728127269171193,0.8089039105974261,1.6628229753101043,1.0022440761927494,1.4855944901810578,1.2600309636531806,1.2278076027206266,1.131137519922965,1.131137519922965,1.0666907980578573,0.6316754254683796,0.0194315677498555,-0.2061319587780218,-0.38336044390706825,-0.27057868064312957,0.42222357940677924,0.6800104668672105,0.6316754254683796,0.3094418161428406,0.39000021847422534,-0.045015154115252295,-0.41558380483962215,-0.5283655681035607,-0.5283655681035607,-0.5605889290361147,-0.5444772485698377,-0.4961422071710069,-0.48003052670472995,-0.4316954853058991,-0.38336044390706825,-0.15779691737919094,0.0194315677498555,-0.045015154115252295,-0.2061319587780218,-0.22224363924429874,-0.19002027831174484,-0.15779691737919094,0.003319887283578551,0.06776660914868635,0.1483250114800711,0.2127717333451789,0.180548372412625,0.47055862080561006,-0.8344874969628228,-0.9633809406930384,-0.9633809406930384,-1.0117159820918693,-0.9472692602267615,-1.0439393430244233,-0.7217057336988841,0.9055739933950877,0.6961221473334873,0.7444571887323183,0.6477871059346565,-2.316762099860302,-2.316762099860302,-0.4316954853058991,0.0194315677498555,1.0183557566590264,1.0022440761927494,1.098914158990411,1.3083660050520114,1.3405893659845654,1.098914158990411,1.6467112948438274,0.9055739933950877,0.6477871059346565,1.453371129248504,1.469482809714781,1.3405893659845654,1.1955842417880729,1.1794725613217958,1.115025839456688,0.9861323957264725,0.6638987864009335,0.16443669194634805,0.03554324821613245,-0.0772385150478062,0.180548372412625,0.5833403840695487,1.0344674371253033,1.0183557566590264,0.8572389519962569,0.3738885380079484,-0.012791793182698399,-0.27057868064312957,-0.36724876344079127,-0.38336044390706825,-0.28669036110940654,-0.30280204157568347,-0.2383553197105757,-0.25446700017685264,-0.2383553197105757,-0.1094618759803601,-0.028903473648975348,-0.09335019551408315,-0.09335019551408315,-0.0772385150478062,-0.09335019551408315,-0.141685236912914,-0.09335019551408315,-0.012791793182698399,0.0838782896149633,0.1161016505475172,0.06776660914868635,0.24499509427773278,0.5188936622044409,-0.9311575797604845,-0.9472692602267615,-0.9311575797604845,-0.9472692602267615,-0.9472692602267615,-0.9956043016255923,-0.9633809406930384,0.24499509427773278,0.8733506324625339,0.8572389519962569,0.9861323957264725,-2.316762099860302,-2.316762099860302,0.8572389519962569,-0.22224363924429874,-1.0600510234907001,-0.30280204157568347,0.3416651770753945,0.5672287036032718,-0.25446700017685264,-0.6894823727663303,1.6144879339112734,1.4855944901810578,0.43833525987305616,0.6155637450021026,1.2600309636531806,1.3405893659845654,1.2278076027206266,1.147249200389242,1.098914158990411,0.9377973543276417,0.8572389519962569,0.5188936622044409,0.32555349660911753,0.45444694033933314,0.5833403840695487,0.8089039105974261,0.7927922301311491,0.6638987864009335,0.6155637450021026,0.3738885380079484,0.24499509427773278,0.1161016505475172,-0.0772385150478062,-0.22224363924429874,-0.22224363924429874,-0.30280204157568347,-0.27057868064312957,-0.2383553197105757,-0.27057868064312957,-0.19002027831174484,-0.12557355644663704,-0.1094618759803601,-0.09335019551408315,-0.09335019551408315,-0.028903473648975348,-0.0772385150478062,-0.028903473648975348,-0.12557355644663704,0.0194315677498555,0.09998997008124025,0.35777685754167143,0.48667030127188704,0.5350053426707179,-0.6250356509012225,-0.8989342188279306,-0.9633809406930384,-0.9633809406930384,-0.9633809406930384,-0.9633809406930384,-0.9311575797604845,-0.8989342188279306,-0.8667108578953767,-0.7217057336988841,1.1794725613217958,-2.316762099860302,-2.316762099860302,1.0505791175915802,-0.2061319587780218,1.3083660050520114,-0.15779691737919094,-0.1739085978454679,-0.4316954853058991,-0.31891372204196045,-0.31891372204196045,0.47055862080561006,1.4855944901810578,1.3889244073833962,0.48667030127188704,0.7444571887323183,1.098914158990411,1.2116959222543497,1.1794725613217958,1.163360880855519,1.0344674371253033,0.9216856738613647,0.8411272715299799,0.7766805496648721,0.7766805496648721,0.6961221473334873,0.9216856738613647,0.7122338277997643,0.5994520645358257,0.13221333101379415,0.03554324821613245,-0.09335019551408315,-0.22224363924429874,-0.30280204157568347,-0.41558380483962215,-0.35113708297451435,-0.36724876344079127,-0.30280204157568347,-0.30280204157568347,-0.25446700017685264,-0.2061319587780218,-0.1739085978454679,-0.15779691737919094,-0.0772385150478062,-0.045015154115252295,0.03554324821613245,0.03554324821613245,-0.045015154115252295,-0.045015154115252295,0.051654928682409396,0.19666005287890195,0.40611189894050226,0.5188936622044409,0.5188936622044409,-0.7055940532326073,-0.8505991774290997,-0.9794926211593153,-0.9794926211593153,-0.9472692602267615,-0.8667108578953767,-0.8828225383616537,-0.8828225383616537,-0.8183758164965459,-0.9150458992942075,1.3567010464508422,-2.316762099860302,-2.316762099860302,0.0194315677498555,0.003319887283578551,0.29333013567656363,-0.6250356509012225,-0.6572590118337763,-0.8989342188279306,-0.8022641360302689,-0.38336044390706825,0.22888341381145585,1.743381377641489,1.5983762534449966,1.2439192831869037,0.3416651770753945,0.26110677474400973,1.115025839456688,1.2439192831869037,1.163360880855519,1.0505791175915802,1.0828024785241341,1.0666907980578573,1.0828024785241341,1.098914158990411,0.9861323957264725,0.7605688691985951,0.8894623129288107,0.39000021847422534,0.19666005287890195,-0.012791793182698399,-0.1739085978454679,-0.19002027831174484,-0.30280204157568347,-0.38336044390706825,-0.35113708297451435,-0.33502540250823737,-0.28669036110940654,-0.25446700017685264,-0.2383553197105757,-0.1739085978454679,-0.141685236912914,-0.028903473648975348,0.0194315677498555,0.06776660914868635,0.051654928682409396,-0.028903473648975348,-0.0772385150478062,-0.44780716577217605,-0.0772385150478062,0.051654928682409396,-1.1889444672209157,-0.09335019551408315,-0.19002027831174484,0.2772184552102867,-0.8505991774290997,-0.8989342188279306,-0.9311575797604845,-0.9956043016255923,-0.9794926211593153,-0.9150458992942075,-0.9472692602267615,-0.9472692602267615,-0.8828225383616537,1.469482809714781,-2.316762099860302,-2.316762099860302,0.16443669194634805,-0.19002027831174484,0.1161016505475172,-0.8022641360302689,-0.9794926211593153,-1.2695028695523005,0.3094418161428406,0.6961221473334873,0.9861323957264725,0.22888341381145585,1.6467112948438274,1.4855944901810578,0.9055739933950877,0.40611189894050226,0.48667030127188704,1.2278076027206266,0.6316754254683796,1.115025839456688,1.0828024785241341,1.0666907980578573,1.163360880855519,1.2116959222543497,1.163360880855519,1.0828024785241341,1.0022440761927494,0.5511170231369948,0.19666005287890195,0.180548372412625,-0.012791793182698399,-0.15779691737919094,-0.25446700017685264,-0.27057868064312957,-0.27057868064312957,-0.25446700017685264,-0.27057868064312957,-0.22224363924429874,-0.15779691737919094,-0.1094618759803601,-0.1094618759803601,0.1161016505475172,0.03554324821613245,0.1161016505475172,-0.028903473648975348,-0.141685236912914,-0.2061319587780218,-0.012791793182698399,0.180548372412625,0.180548372412625,-1.2211678281534697,0.03554324821613245,-0.5767006095023917,0.16443669194634805,0.1483250114800711,0.45444694033933314,-0.9956043016255923,-0.9794926211593153,-0.9633809406930384,-0.9150458992942075,-1.0117159820918693,-0.9794926211593153,-0.9956043016255923,1.6950463362426582,-2.316762099860302,-2.316762099860302,-0.045015154115252295,-0.36724876344079127,-0.9794926211593153,-0.15779691737919094,-0.15779691737919094,0.03554324821613245,0.0838782896149633,0.0194315677498555,0.03554324821613245,0.09998997008124025,1.6144879339112734,1.3405893659845654,1.4211477683159501,0.40611189894050226,0.051654928682409396,0.7766805496648721,-0.0772385150478062,1.115025839456688,0.5994520645358257,1.0666907980578573,1.115025839456688,1.131137519922965,1.2116959222543497,1.131137519922965,1.0183557566590264,0.8089039105974261,0.42222357940677924,0.43833525987305616,0.03554324821613245,0.003319887283578551,-0.19002027831174484,-0.2383553197105757,-0.2061319587780218,-0.2383553197105757,-0.22224363924429874,-0.15779691737919094,-0.0772385150478062,-0.061126834581529246,-0.0772385150478062,0.003319887283578551,0.0194315677498555,-0.15779691737919094,-0.35113708297451435,-0.012791793182698399,0.1483250114800711,-0.045015154115252295,0.003319887283578551,-0.9794926211593153,-0.4316954853058991,-0.5928122899686685,0.06776660914868635,-0.012791793182698399,-0.045015154115252295,0.32555349660911753,0.2772184552102867,-0.9472692602267615,-1.0278276625581462,-1.0117159820918693,-1.076162703956977,-1.0600510234907001,-1.0600510234907001,1.147249200389242,-2.316762099860302,-2.316762099860302,-0.5444772485698377,-0.39947212437334517,-0.4316954853058991,-1.4789547156139007,-0.12557355644663704,-0.12557355644663704,-0.09335019551408315,-0.1739085978454679,1.6305996143775505,1.5500412120461657,1.5339295315798886,1.2278076027206266,1.131137519922965,1.3405893659845654,0.03554324821613245,0.0838782896149633,0.19666005287890195,-0.2383553197105757,-0.6894823727663303,0.9377973543276417,0.7766805496648721,1.115025839456688,1.115025839456688,1.0505791175915802,1.098914158990411,0.8894623129288107,0.5994520645358257,0.5511170231369948,0.3094418161428406,0.09998997008124025,-0.1094618759803601,-0.2383553197105757,-0.28669036110940654,-0.1094618759803601,-0.12557355644663704,-0.2061319587780218,-0.12557355644663704,-0.1739085978454679,-0.12557355644663704,-0.141685236912914,-0.5283655681035607,-0.25446700017685264,-0.30280204157568347,0.1483250114800711,-0.2383553197105757,-0.48003052670472995,-0.7539290946314381,-0.6894823727663303,-0.41558380483962215,-0.5122538876372839,-0.045015154115252295,-0.09335019551408315,-0.1094618759803601,0.26110677474400973,0.19666005287890195,0.29333013567656363,-1.1244977453558078,-1.0600510234907001,-1.0439393430244233,-1.0600510234907001,-1.0439393430244233,1.115025839456688,-2.316762099860302,-2.316762099860302,-0.31891372204196045,-1.1728327867546386,-1.736741603074332,-1.4628430351476238,-1.5434014374790086,-1.4306196742150699,-1.2856145500185774,-1.2050561476871926,1.6628229753101043,1.5983762534449966,1.5500412120461657,1.5500412120461657,1.4855944901810578,1.2600309636531806,1.2761426441194574,1.405036087849673,1.3567010464508422,1.1794725613217958,1.2600309636531806,1.2600309636531806,0.502781981738164,0.1483250114800711,-0.0772385150478062,-0.38336044390706825,0.8894623129288107,0.7766805496648721,0.7605688691985951,0.39000021847422534,0.2772184552102867,0.0838782896149633,-0.061126834581529246,-0.19002027831174484,-0.22224363924429874,-0.1739085978454679,-0.1094618759803601,-0.19002027831174484,-0.27057868064312957,-0.22224363924429874,-0.4316954853058991,-0.5928122899686685,0.09998997008124025,-0.19002027831174484,-0.30280204157568347,-0.33502540250823737,-0.33502540250823737,-0.8505991774290997,-0.463918846238453,-0.31891372204196045,-0.9794926211593153,-0.4961422071710069,-0.028903473648975348,0.003319887283578551,0.051654928682409396,0.1483250114800711,0.1483250114800711,0.32555349660911753,-0.9311575797604845,-1.0439393430244233,-1.076162703956977,-1.0278276625581462,-0.9633809406930384,1.7594930581077661,-2.316762099860302,-2.316762099860302,0.003319887283578551,-0.8183758164965459,-1.5111780765464546,-1.736741603074332,-1.5434014374790086,-1.3983963132825161,-1.3661729523499622,1.3244776855182885,1.5822645729787195,1.5500412120461657,1.469482809714781,1.405036087849673,1.405036087849673,1.6467112948438274,1.163360880855519,1.2761426441194574,1.2761426441194574,1.163360880855519,0.9861323957264725,1.0344674371253033,0.5672287036032718,0.47055862080561006,0.45444694033933314,0.32555349660911753,-0.028903473648975348,-0.30280204157568347,-0.41558380483962215,-0.36724876344079127,-0.4961422071710069,-0.4961422071710069,-0.6733706923000533,-0.5928122899686685,-0.7055940532326073,-0.5767006095023917,-0.7700407750977151,-0.5444772485698377,-0.4316954853058991,-0.1739085978454679,-0.36724876344079127,-0.35113708297451435,-0.28669036110940654,-0.28669036110940654,-0.12557355644663704,-0.9794926211593153,-0.27057868064312957,-0.6411473313674995,-0.27057868064312957,-0.9311575797604845,-0.4961422071710069,-0.141685236912914,0.2772184552102867,-0.2383553197105757,-0.028903473648975348,0.3094418161428406,0.0838782896149633,0.24499509427773278,-1.0117159820918693,-0.9794926211593153,-1.0278276625581462,-1.108386064889531,-1.0117159820918693,1.7272696971752122,-2.316762099860302,-2.316762099860302,-1.1889444672209157,-1.1889444672209157,-1.1728327867546386,-1.1567211062883618,-1.108386064889531,-1.092274384423254,-0.9311575797604845,0.22888341381145585,1.3728127269171193,1.5822645729787195,1.5983762534449966,1.3405893659845654,1.3567010464508422,1.1955842417880729,1.131137519922965,1.1955842417880729,1.0828024785241341,1.0344674371253033,0.5511170231369948,0.7283455082660413,0.35777685754167143,0.3416651770753945,0.22888341381145585,0.5350053426707179,-0.27057868064312957,-0.12557355644663704,-0.09335019551408315,-0.31891372204196045,-0.6089239704349455,-0.6572590118337763,-0.7217057336988841,-0.6250356509012225,-0.36724876344079127,-0.41558380483962215,-0.463918846238453,-0.6733706923000533,-0.36724876344079127,-0.48003052670472995,-0.25446700017685264,-0.48003052670472995,-0.6572590118337763,-0.4316954853058991,-0.7217057336988841,-0.28669036110940654,-0.5605889290361147,-0.19002027831174484,-0.8505991774290997,-0.4316954853058991,-0.44780716577217605,-0.061126834581529246,-0.045015154115252295,-0.09335019551408315,-0.1739085978454679,-0.0772385150478062,0.16443669194634805,0.26110677474400973,0.32555349660911753,-0.7700407750977151,-1.0117159820918693,-0.9956043016255923,-0.9311575797604845,1.5500412120461657,-2.316762099860302,-2.316762099860302,-0.7539290946314381,-0.7861524555639919,-0.7861524555639919,-0.8505991774290997,-0.7378174141651611,-0.8505991774290997,-0.7055940532326073,1.5178178511136118,1.3567010464508422,1.2116959222543497,0.7927922301311491,0.9861323957264725,1.5339295315798886,1.147249200389242,1.2761426441194574,1.3567010464508422,1.0828024785241341,0.8894623129288107,0.8250155910637029,0.7927922301311491,0.5833403840695487,0.13221333101379415,0.1483250114800711,0.0838782896149633,0.06776660914868635,-0.061126834581529246,-0.1739085978454679,-0.48003052670472995,-0.5283655681035607,-0.48003052670472995,-0.5928122899686685,-0.15779691737919094,-0.19002027831174484,-0.22224363924429874,-0.22224363924429874,-0.5605889290361147,-0.5767006095023917,-0.4961422071710069,-0.22224363924429874,-0.6250356509012225,-0.9472692602267615,-0.36724876344079127,-0.35113708297451435,-0.15779691737919094,-0.5283655681035607,-1.2856145500185774,-0.1739085978454679,-0.141685236912914,-0.39947212437334517,0.0838782896149633,-0.12557355644663704,-0.15779691737919094,-0.012791793182698399,0.051654928682409396,0.2772184552102867,0.1483250114800711,0.3738885380079484,0.3094418161428406,-0.9794926211593153,-1.0439393430244233,-0.9956043016255923,0.35777685754167143,-2.316762099860302,-2.316762099860302,-0.6733706923000533,-0.6894823727663303,-0.6411473313674995,-0.6411473313674995,-0.6894823727663303,-0.6733706923000533,-0.5767006095023917,1.3405893659845654,1.6144879339112734,1.3889244073833962,1.2761426441194574,1.0666907980578573,1.0022440761927494,0.19666005287890195,1.3083660050520114,1.0828024785241341,1.147249200389242,0.6638987864009335,0.16443669194634805,0.13221333101379415,0.1483250114800711,0.5994520645358257,-0.012791793182698399,-0.22224363924429874,-0.09335019551408315,0.5511170231369948,-0.09335019551408315,-0.19002027831174484,-0.30280204157568347,-0.15779691737919094,-0.28669036110940654,-0.39947212437334517,-0.35113708297451435,-0.44780716577217605,-0.5928122899686685,-0.38336044390706825,-0.2061319587780218,-0.25446700017685264,-0.19002027831174484,-0.7539290946314381,-0.15779691737919094,-0.44780716577217605,-0.35113708297451435,-0.9472692602267615,-1.414507993748793,-0.44780716577217605,-0.41558380483962215,0.003319887283578551,0.003319887283578551,-0.12557355644663704,-0.2383553197105757,-0.028903473648975348,-0.2061319587780218,0.09998997008124025,0.06776660914868635,-0.09335019551408315,0.16443669194634805,-0.028903473648975348,-1.3017262304848543,-1.076162703956977,-0.9633809406930384,-0.7861524555639919,-2.316762099860302,-2.316762099860302,-0.6411473313674995,-0.6894823727663303,-0.6572590118337763,-0.8183758164965459,-0.7861524555639919,-0.6411473313674995,-0.35113708297451435,1.5983762534449966,1.5500412120461657,1.5178178511136118,1.2439192831869037,1.1794725613217958,1.2278076027206266,1.0505791175915802,1.1794725613217958,1.0828024785241341,0.9539090347939185,1.0183557566590264,1.0183557566590264,0.6155637450021026,0.45444694033933314,0.42222357940677924,0.3094418161428406,0.2127717333451789,0.1161016505475172,-0.1739085978454679,0.06776660914868635,-0.1739085978454679,-0.44780716577217605,-0.5928122899686685,-0.5767006095023917,-0.5122538876372839,-0.6733706923000533,-0.2383553197105757,-0.1739085978454679,-0.22224363924429874,-0.27057868064312957,-0.44780716577217605,-0.31891372204196045,-0.6411473313674995,-0.5605889290361147,-0.7217057336988841,-0.7217057336988841,-0.0772385150478062,-0.25446700017685264,-0.27057868064312957,-0.27057868064312957,-0.1739085978454679,-0.0772385150478062,-0.25446700017685264,-0.15779691737919094,0.06776660914868635,-0.1094618759803601,-0.061126834581529246,-0.028903473648975348,0.09998997008124025,0.13221333101379415,-1.4628430351476238,-1.5111780765464546,-1.4950663960801778,-0.9472692602267615,-1.1728327867546386,-2.316762099860302,-2.316762099860302,-1.1244977453558078,-1.140609425822085,-1.1728327867546386,-1.0600510234907001,-1.076162703956977,-0.061126834581529246,0.1483250114800711,1.5983762534449966,1.5822645729787195,1.3889244073833962,1.5339295315798886,1.4855944901810578,1.3405893659845654,1.3244776855182885,1.2761426441194574,0.9700207152601955,0.8894623129288107,0.6961221473334873,0.7283455082660413,0.47055862080561006,0.1483250114800711,0.09998997008124025,-0.012791793182698399,-0.2061319587780218,-0.36724876344079127,-0.25446700017685264,-0.39947212437334517,-0.463918846238453,-0.6411473313674995,-0.33502540250823737,-0.38336044390706825,-0.15779691737919094,-0.09335019551408315,-0.31891372204196045,-0.35113708297451435,-0.38336044390706825,-0.27057868064312957,-0.4316954853058991,-0.39947212437334517,-0.8344874969628228,-0.045015154115252295,-0.061126834581529246,0.003319887283578551,-0.15779691737919094,-0.22224363924429874,-0.22224363924429874,-0.15779691737919094,0.051654928682409396,-0.12557355644663704,-0.141685236912914,0.0194315677498555,-0.15779691737919094,-0.19002027831174484,-0.1094618759803601,0.0838782896149633,-0.012791793182698399,-1.3661729523499622,-1.4950663960801778,-1.5917364788778394,-1.3017262304848543,-1.414507993748793,-1.5111780765464546,-2.316762099860302,-2.316762099860302,-1.7850766444731627,-1.736741603074332,-0.28669036110940654,0.0194315677498555,0.13221333101379415,0.16443669194634805,1.3889244073833962,1.5500412120461657,1.5822645729787195,1.3244776855182885,1.1955842417880729,1.3244776855182885,1.2600309636531806,1.437259448782227,0.8089039105974261,0.9055739933950877,1.2278076027206266,1.163360880855519,0.8733506324625339,0.5188936622044409,0.48667030127188704,0.7605688691985951,0.6800104668672105,0.22888341381145585,0.3094418161428406,0.3416651770753945,0.13221333101379415,0.051654928682409396,-0.141685236912914,-0.1094618759803601,-0.1739085978454679,-0.41558380483962215,-0.061126834581529246,-0.27057868064312957,-0.4316954853058991,-0.5283655681035607,-0.41558380483962215,-0.6250356509012225,-1.076162703956977,-0.1739085978454679,-0.09335019551408315,-0.045015154115252295,-0.19002027831174484,-0.2383553197105757,-0.1739085978454679,0.1161016505475172,-0.028903473648975348,-0.2061319587780218,-0.27057868064312957,-0.1739085978454679,-0.25446700017685264,-0.045015154115252295,-0.15779691737919094,-0.30280204157568347,-0.19002027831174484,-1.6400715202766702,-1.6561832007429473,-1.736741603074332,-1.6561832007429473,-1.5756247984115626,-1.4950663960801778,-1.4628430351476238,-2.316762099860302,-2.316762099860302,-0.6572590118337763,0.1161016505475172,-0.1094618759803601,-0.2383553197105757,0.0194315677498555,-0.09335019551408315,1.098914158990411,1.2600309636531806,1.3244776855182885,1.3728127269171193,0.9700207152601955,1.0022440761927494,1.3244776855182885,1.1794725613217958,1.1955842417880729,1.2600309636531806,0.9216856738613647,0.8894623129288107,0.6316754254683796,0.9216856738613647,0.3416651770753945,0.40611189894050226,0.6638987864009335,0.47055862080561006,0.051654928682409396,-0.045015154115252295,0.09998997008124025,-0.12557355644663704,-0.2061319587780218,-0.8505991774290997,-0.7700407750977151,-0.5928122899686685,-0.22224363924429874,-0.5122538876372839,-0.15779691737919094,-0.5122538876372839,-0.5605889290361147,-0.48003052670472995,-0.30280204157568347,-0.12557355644663704,-0.28669036110940654,-0.141685236912914,-0.27057868064312957,-0.25446700017685264,-0.463918846238453,-0.39947212437334517,-0.4316954853058991,-0.41558380483962215,-0.31891372204196045,-0.36724876344079127,-0.31891372204196045,-0.35113708297451435,-0.22224363924429874,-0.5605889290361147,-1.8656350468045475,-1.8173000054057167,-1.7850766444731627,-1.720629922608055,-1.6400715202766702,-1.6400715202766702,-1.6722948812092242,-1.688406561675501,-2.316762099860302,-2.316762099860302,-0.2061319587780218,-0.27057868064312957,-0.5605889290361147,-0.0772385150478062,-0.5928122899686685,-0.36724876344079127,1.0505791175915802,0.9861323957264725,0.6477871059346565,1.3728127269171193,1.5178178511136118,1.5500412120461657,1.1955842417880729,1.3405893659845654,1.5178178511136118,1.5339295315798886,1.0344674371253033,1.3889244073833962,0.5672287036032718,0.7766805496648721,0.3738885380079484,0.24499509427773278,0.29333013567656363,0.26110677474400973,-0.1739085978454679,-0.27057868064312957,-0.41558380483962215,-0.28669036110940654,-0.045015154115252295,-0.31891372204196045,-0.30280204157568347,-0.27057868064312957,-0.39947212437334517,-0.5122538876372839,-0.27057868064312957,-0.35113708297451435,-0.35113708297451435,-0.33502540250823737,-0.35113708297451435,-0.30280204157568347,-0.31891372204196045,-0.31891372204196045,-0.5283655681035607,-0.44780716577217605,-0.4961422071710069,-0.4961422071710069,-0.5283655681035607,-0.4961422071710069,-0.4961422071710069,-0.463918846238453,-0.9311575797604845,-0.0772385150478062,-0.39947212437334517,-1.8334116858719938,-1.8656350468045475,-1.8173000054057167,-1.752853283540609,-1.7689649640068859,-1.6239598398103934,-1.720629922608055,-1.720629922608055,-1.688406561675501,-2.316762099860302,-2.316762099860302,-0.6411473313674995,-0.33502540250823737,-0.7539290946314381,-0.33502540250823737,-0.33502540250823737,-0.30280204157568347,0.8894623129288107,1.3889244073833962,0.180548372412625,-0.19002027831174484,0.19666005287890195,0.8572389519962569,1.0666907980578573,1.0344674371253033,0.9216856738613647,0.6316754254683796,0.8089039105974261,0.8894623129288107,-0.028903473648975348,0.24499509427773278,0.29333013567656363,0.3094418161428406,0.2772184552102867,0.13221333101379415,0.03554324821613245,0.180548372412625,-0.5283655681035607,-0.5122538876372839,-0.463918846238453,-0.6733706923000533,-0.6894823727663303,-0.7861524555639919,-1.414507993748793,-0.8183758164965459,-0.36724876344079127,-0.41558380483962215,-0.41558380483962215,-0.44780716577217605,-0.5444772485698377,-0.5283655681035607,-0.8344874969628228,-0.9956043016255923,-0.9794926211593153,-0.9794926211593153,-0.5767006095023917,-0.5444772485698377,-0.41558380483962215,-0.33502540250823737,-0.6411473313674995,-0.38336044390706825,-0.6572590118337763,-1.9300817686696554,-1.9784168100684862,-1.8656350468045475,-1.7045182421417782,-1.7850766444731627,-1.720629922608055,-1.688406561675501,-1.736741603074332,-1.688406561675501,-1.6239598398103934,-1.6239598398103934,-2.316762099860302,-2.316762099860302,-0.27057868064312957,-0.25446700017685264,-0.6089239704349455,-0.5767006095023917,-0.7055940532326073,-0.7378174141651611,0.9539090347939185,0.6638987864009335,0.48667030127188704,-0.25446700017685264,-0.25446700017685264,-0.39947212437334517,0.03554324821613245,0.3094418161428406,0.16443669194634805,0.1483250114800711,-0.5122538876372839,-0.5283655681035607,-0.5122538876372839,-0.061126834581529246,-0.35113708297451435,-0.7217057336988841,-1.5434014374790086,-0.8344874969628228,-0.7378174141651611,-0.31891372204196045,-0.2383553197105757,-0.045015154115252295,-0.27057868064312957,-0.2383553197105757,-0.5767006095023917,-0.48003052670472995,-0.35113708297451435,-0.7539290946314381,-0.5444772485698377,-0.5928122899686685,-0.5444772485698377,-0.6411473313674995,-0.7378174141651611,-0.6733706923000533,-0.39947212437334517,-0.44780716577217605,-0.6089239704349455,-0.6733706923000533,-0.7378174141651611,-0.7539290946314381,-0.7217057336988841,-0.7700407750977151,-0.6733706923000533,-0.6250356509012225,-1.736741603074332,-1.720629922608055,-1.8656350468045475,-1.994528490534763,-1.9784168100684862,-1.8495233663382706,-1.7045182421417782,-1.752853283540609,-1.752853283540609,-1.7689649640068859,-1.688406561675501,-1.6722948812092242]', 'student_photos/MFxJo6I7vns6pRnV9YF6AKglaH9xEWQEjRoGOuDa.jpg', 0, '2006-07-01', '2026-07-05 12:23:06', '2026-07-05 12:39:21');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `specialization` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `advisor_branch` varchar(255) DEFAULT NULL,
  `advisor_year` varchar(255) DEFAULT NULL,
  `advisor_section` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `specialization`, `created_at`, `updated_at`, `advisor_branch`, `advisor_year`, `advisor_section`) VALUES
(1, 108, 'صيدلة', '2026-06-09 13:18:25', '2026-06-09 13:18:25', NULL, NULL, NULL),
(2, 109, 'صيدلة', '2026-06-09 13:19:19', '2026-06-09 13:19:19', NULL, NULL, NULL),
(3, 110, 'مخابر', '2026-06-09 14:16:09', '2026-06-09 14:16:09', NULL, NULL, NULL),
(4, 111, 'مخابر', '2026-06-09 14:16:49', '2026-06-09 14:16:49', NULL, NULL, NULL),
(5, 112, 'ذكاء اصطناعي - معلوماتية', '2026-06-09 15:05:17', '2026-06-11 14:59:04', 'ذكاء اصطناعي', 'السنة الثانية', NULL),
(6, 113, 'ذكاء اصطناعي - معلوماتية', '2026-06-09 15:19:21', '2026-06-09 18:26:10', 'معلوماتية', 'السنة الثانية', NULL),
(7, 114, 'ذكاء اصطناعي - معلوماتية', '2026-06-09 15:21:43', '2026-06-11 14:59:16', 'معلوماتية', 'السنة الأولى', NULL),
(8, 115, 'اتصالات - الكترون', '2026-06-09 15:29:51', '2026-06-11 14:59:27', 'اتصالات', 'السنة الثانية', NULL),
(9, 116, 'الكترون - ذكاء اصطناعي', '2026-06-09 15:32:53', '2026-06-11 14:59:44', 'اتصالات', 'السنة الأولى', NULL),
(10, 117, 'الكترون - معلوماتية', '2026-06-09 15:34:16', '2026-06-11 14:59:57', 'الكترون', 'السنة الأولى', NULL),
(11, 118, 'ذكاء اصطناعي - معلوماتية', '2026-06-09 15:35:13', '2026-06-11 15:01:13', 'ذكاء اصطناعي', 'السنة الأولى', NULL),
(12, 119, 'اتصالات - الكترون - ذكاء اصطناعي - معلوماتية', '2026-06-09 15:35:46', '2026-06-11 15:01:02', 'الكترون', 'السنة الثانية', NULL),
(13, 120, 'ديكور', '2026-06-09 15:56:38', '2026-06-09 15:56:38', NULL, NULL, NULL),
(14, 121, 'ديكور', '2026-06-09 15:57:21', '2026-06-09 15:57:21', NULL, NULL, NULL),
(15, 122, 'هندسة عمارة', '2026-06-09 15:57:57', '2026-06-09 15:57:57', NULL, NULL, NULL),
(16, 123, 'هندسة عمارة', '2026-06-09 15:58:32', '2026-06-09 15:58:32', NULL, NULL, NULL),
(17, 124, 'ادارة اعمال', '2026-06-09 16:03:23', '2026-06-09 16:03:23', NULL, NULL, NULL),
(18, 125, 'ادارة اعمال', '2026-06-09 16:04:10', '2026-06-09 16:04:10', NULL, NULL, NULL),
(19, 126, 'محاسبة', '2026-06-09 16:05:04', '2026-06-09 16:05:04', NULL, NULL, NULL),
(20, 127, 'محاسبة', '2026-06-09 16:05:43', '2026-06-09 16:05:43', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university_ids`
--

CREATE TABLE `university_ids` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `university_id` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('student','parent') NOT NULL DEFAULT 'student',
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `university_ids`
--

INSERT INTO `university_ids` (`id`, `university_id`, `full_name`, `first_name`, `last_name`, `date_of_birth`, `phone`, `photo`, `role`, `telegram_chat_id`, `is_used`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '202601', 'محمود غنام', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:05:47', '2026-06-09 08:05:47'),
(2, '202602', 'محمد ربيعي', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:06:07', '2026-06-09 08:06:07'),
(3, '202603', 'محمد عبد القادر', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:06:44', '2026-06-09 08:06:44'),
(4, '202604', 'جودي سلطاني', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:07:07', '2026-06-09 08:07:07'),
(5, '202605', 'هادي حسن', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:07:26', '2026-06-09 08:07:26'),
(6, '202606', 'زهرية سعدوني', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:08:13', '2026-06-09 08:08:13'),
(7, '202607', 'روى ابو سمرة', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:08:37', '2026-06-09 08:08:37'),
(8, '202608', 'ملاك دسوقي', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:08:50', '2026-06-09 08:08:50'),
(9, '202609', 'علي الحسن', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:09:19', '2026-06-09 08:09:19'),
(10, '202610', 'امين امين', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 08:09:45', '2026-06-09 08:09:45'),
(11, '202611', 'محمود عثمان', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:00:54', '2026-06-09 11:00:54'),
(12, '202612', 'خليل خليل', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:01:10', '2026-06-09 11:01:10'),
(13, '202613', 'فيصل فيصل', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:01:26', '2026-06-09 11:01:26'),
(14, '202614', 'حسن علي', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:01:35', '2026-06-09 11:29:25'),
(16, '202615', 'جواد نوفل', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:02:11', '2026-06-09 11:40:05'),
(17, '202616', 'خالد خالد', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:02:25', '2026-06-09 11:02:25'),
(18, '202617', 'ندى طه', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:02:33', '2026-06-09 12:18:42'),
(19, '202618', 'اية زيد', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:02:49', '2026-06-09 12:20:47'),
(20, '202619', 'عدنان عزام', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:03:02', '2026-06-09 11:03:02'),
(21, '202620', 'سارة احمد', NULL, NULL, NULL, NULL, NULL, 'student', NULL, 0, 28, '2026-06-09 11:03:11', '2026-06-09 11:03:11'),
(32, '2026100', 'هدى شبلي', 'هدى', 'شبلي', '2006-01-01', '0986368755', 'student_photos/MFxJo6I7vns6pRnV9YF6AKglaH9xEWQEjRoGOuDa.jpg', 'student', '7821980919', 1, 28, '2026-07-05 12:21:16', '2026-07-05 12:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `university_id` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `children_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`children_ids`)),
  `gender` enum('ذكر','أنثى') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `device_token` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `full_name`, `first_name`, `last_name`, `username`, `email`, `avatar`, `password`, `phone`, `telegram_chat_id`, `university_id`, `department`, `branch`, `children_ids`, `gender`, `birth_date`, `academic_year`, `status`, `device_token`, `last_login`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 'إدارة المعهد التقني', NULL, NULL, 'admin_main', 'admin@edu-bridge.com', NULL, '$2y$12$E7Iq3rpX567aurumZtCCzuRKsm9.mMFSxMz3BAWRDO.xlJ8/wyT/e', '0986354774', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-09 08:25:30', '2026-05-30 13:31:21'),
(28, 6, 'موظف الشؤون', NULL, NULL, 'affairs_user', 'affairs@edu-bridge.com', NULL, '$2y$12$5o1cXjBiW5d7Dg8SXnLIcOU.RO9VRxE6u8BxSCDLayNBhWuz4Rzd6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'dWKBi1-ZQWq6pIx8PQ2WbO:APA91bF2VoETwMMj5Lk3G8hGPTUbiLxcYWDiij73Tg2HsJ7wmDJ3OGKkvtZE0hb-L2I2nPH4Lwa89GyPeVDQhSj65Q-wTMSeutTQN_4hSptdH7FNwaAgtnc', '2026-07-05 12:03:43', NULL, '2026-05-27 14:04:06', '2026-07-05 12:03:44'),
(60, 5, 'احمد علي', NULL, NULL, 'ahmad_ali', 'ahmad_ali@edu.com', NULL, '$2y$12$uMapkMrUPcrdzIkff4L1Teh1s7fpqSkFXczONHH26jBH6IpEDmvwm', '0932323232', NULL, NULL, 'طبي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 07:07:06', '2026-06-09 16:09:20'),
(62, 5, 'احمد وليد', NULL, NULL, 'walid', 'walid@edu.com', NULL, '$2y$12$fVyTmFhm3eTCNYGn59jvxO0IlcIseBFll3owcGLw90xT9PcAA2.xS', '0911111111', NULL, NULL, 'تجاري', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 07:37:36', '2026-06-09 16:09:08'),
(63, 5, 'احمد عيسى', NULL, NULL, 'issa', 'issa@edu.com', NULL, '$2y$12$N1yXwc/BMmEzmqoVOQMg/u8z073WMgBo82gBdVbERI5sKa2OdRM.K', '0922222222', NULL, NULL, 'هندسي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 07:39:13', '2026-06-09 16:09:00'),
(64, 3, 'روى ابو سمرة', NULL, NULL, '202607', 'rwa@gmail.com', NULL, '$2y$12$ORIuI4jki7GLUl9JWXI6U.II.3Cdo7S1vykDD42iBnme9A0W4jIHu', '0933333333', NULL, '202607', 'هندسي', NULL, NULL, 'أنثى', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 08:19:18', '2026-06-09 10:44:56'),
(66, 4, 'احمد ابو سمرة', NULL, NULL, 'abo_samra', 'abosamra@gmail.cm', NULL, '$2y$12$iesatscbAxL/ZKqKeDgbNuxQSuIc5L9o90V6zX8WLltvCf7Z0W4u.', '0933333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-06-24 22:36:27', NULL, '2026-06-09 08:36:58', '2026-06-24 22:36:27'),
(67, 3, 'ملاك دسوقي', NULL, NULL, '202608', 'malak@gmail.com', NULL, '$2y$12$eYLiZl7XqSjQlc/TslW1...Z3dMLm.8XBNSTXKPwWmoX3Q2nkUJnG', '09444444444', NULL, '202608', 'هندسي', NULL, NULL, 'أنثى', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 08:39:16', '2026-06-09 10:45:09'),
(68, 4, 'احمد دسوقي', NULL, NULL, 'ahmad_dasoqe', 'dasoqe@gmail.com', NULL, '$2y$12$GFJofAZ8UkL6wOBhmhP61OkWbwjkEULtz.nG/5CfAd6TJJ4giOL5m', '09444444444', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 08:40:51', '2026-06-09 08:40:51'),
(69, 3, 'علي الحسن', NULL, NULL, '202609', 'ali@gmail.com', NULL, '$2y$12$xh.mvLFNHYBQJhoihhHjxuQTXYHodub62Ju6Y423TzWucJOij.fd2', '0911111111', NULL, '202609', 'تجاري', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', 'cunTNpuPQMa0XJzXe_tkOr:APA91bGXhJJaSz3k-ANpp7aHNA5bd3ncR5CmhJYTPkFPIWqRlpaP_wXUvMgqEACFhPDUWhNqPJifL6AzEEZHug3F-985AVzy6N9gGlYUZNUaE3sz59HEXXI', '2026-06-19 10:02:16', NULL, '2026-06-09 08:44:20', '2026-06-19 10:02:16'),
(70, 4, 'احمد الحسن', NULL, NULL, 'ahmad_hasan', 'ahmad_hasan@gmail.com', NULL, '$2y$12$Cs9NUwWiKFAyTS17H4S6s.7f3cbsa1A0PIJsZEQfceKheajc/MFka', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 08:46:01', '2026-06-09 08:46:01'),
(71, 3, 'امين امين', NULL, NULL, '202610', 'amin@gmail.com', NULL, '$2y$12$qs3D96pbKmo33Uu2MeaiVu9kON0SYKU55b7TfxZD31zfJds6ysI0.', '0922222222', NULL, '202610', 'تجاري', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', 'c9ddhudKQEWZuBjGL-W1wx:APA91bGUx0QT9K7m4ve9G6YzPKWgLPK5PhqwkstW0X_yZsLmnX3CctMe61mptBbzRTRFDrC3GNteT2H8gF8g0TQwJXolJOjRs9HoEDzEEtu8F8cF1j-tMGc', '2026-07-05 11:51:15', NULL, '2026-06-09 08:47:43', '2026-07-05 11:51:15'),
(72, 4, 'احمد امين', NULL, NULL, 'ahmad_amin', 'ahmad_amin@gmail.com', NULL, '$2y$12$ReRPks/Xz0WENjqB2q547eE9JMJOrsRoSP447SiXkRQpUEbWDU2Aq', '0922222222', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 08:49:55', '2026-06-09 08:49:55'),
(73, 5, 'احمد ديب', NULL, NULL, 'ahmad_deeb', 'ahmad_deeb@edu.com', NULL, '$2y$12$ZmntZ7UXVpapBeEj44AMteZRIufqPx19LiYde6Rr2rIHVSQ/iM0Bu', '0999999999', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', 'dV1tKxHjRrK-769PYSJru-:APA91bEUt_ff69wg0PDOd2p9fs9sIVRJFeK1CCka-UhGNY_SEDWXMkndSLBrHxSBkku_MvgB9euIaZaKV5dfbu0Z5kqg8h6h66WiOrrGOb9ot0oTVRr0nw8', '2026-07-05 18:50:10', NULL, '2026-06-09 08:58:34', '2026-07-05 18:50:11'),
(74, 3, 'هادي حسن', NULL, NULL, '202605', 'hadi@gmail.com', NULL, '$2y$12$m469t3BINJiT1qg9fwru2e1UBIRqGECfmIZ99yhN7YBm8aN8U6f3a', '0966666666', NULL, '202605', 'طبي', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 09:00:51', '2026-06-09 10:48:17'),
(75, 4, 'زكي حسن', NULL, NULL, 'zki_hasan', 'zki_hasan@gmail.com', NULL, '$2y$12$VPqPLKdEqhnX/GuA3lRIJeoGDMdolxKWhsXRa2D53tvFnIQEEVTG.', '0966666666', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 09:02:31', '2026-06-09 09:02:31'),
(76, 3, 'زهرية سعدوني', NULL, NULL, '202606', 'zhria@gmail.com', NULL, '$2y$12$wDPi/jnZFUBkKxGD2q8bSegdVltSAP/GYyFHSWdTvKN4GlSecwSru', '0955555555', NULL, '202606', 'طبي', NULL, NULL, 'أنثى', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 09:04:28', '2026-06-09 10:48:29'),
(77, 4, 'احمد سعدوني', NULL, NULL, 'ahmad_zadoni', 'ahmad_zadoni@gmail.com', NULL, '$2y$12$ZZmoD5HNERX0giaDXCLK9ODhcTlDKD5n42qqxBlucIN2vgV9Nt9WO', '0955555555', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 09:05:33', '2026-06-09 09:05:33'),
(78, 3, 'محمود غنام', NULL, NULL, '202601', 'mahmoud@gmail.com', NULL, '$2y$12$VERWEQJmaGB4tcuDvUPMjuwk4EPYLyyalbl1qyARIVIUibYiDFfD.', '09976859206', NULL, '202601', 'نظم معلومات', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', 'd8jVr_quRIOcfXvWbbNtE-:APA91bGKkTCNMNIjiHVHAiKjMfT9IITxtc-mvnD2dkY3mD9dOKFkHw60wnejPbfGf8fsv1c2eVDQpTvCNFu_4VfHYp3x6CogrVam_otWnHM3GkG3Y7txVF4', '2026-07-05 09:43:03', NULL, '2026-06-09 09:09:38', '2026-07-05 09:43:04'),
(79, 4, 'محمد غنام', NULL, NULL, 'mhmd_ghannam', 'ghannam@gmail.com', 'avatars/o72ASsPO0PDNXnlNjHzKregX5Nvq57zygxgDzbiE.jpg', '$2y$12$A/6QhiJE3Ra7QvtommTvEOjt/1qUQCby985Lb1yEec2PfyWWuCJbC', '0997685906', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'cQWxvZxeQT2b7Uq2pq7zuI:APA91bGw8cNQvmXJq2Z0KaPtI682HeBKGCt5H1yJPsXENLMdZQENmp9CqIboGjGNg5JTFvUPQmefqG4pGfiJUt-eXtvGq8hYnqsjZ5Q7B5nN24_qHHXf_Ew', '2026-06-11 14:16:57', NULL, '2026-06-09 09:11:28', '2026-06-11 14:16:57'),
(80, 3, 'محمد ربيعي', NULL, NULL, '202602', 'rbee@gmail.com', NULL, '$2y$12$xR5GrxMjSZouJfULLk39ROlg6rd77XbZrVxgnT903/RyzMtsFanL.', '0999999999', NULL, '202602', 'نظم معلومات', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 09:13:02', '2026-06-09 10:42:12'),
(82, 4, 'احمد ربيعي', NULL, NULL, 'ahmad_rbee', 'ahmad_rbee@gmail.com', NULL, '$2y$12$UNZJM7HeJhbO0W6b7N.3BOpB/o59qeoOVHLHKieV8tG0Qn9wp43C6', '0999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 09:26:40', '2026-06-09 09:26:40'),
(83, 3, 'محمد عبد القادر', NULL, NULL, '202603', 'qader@gmail.com', NULL, '$2y$12$zfaSNuG2tMA/jpcOlaFi3ufYLl7qvaQcalTYFASGkvaq5JJmj5dKe', '0911111111', NULL, '202603', 'نظم معلومات', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الثانية', 'active', NULL, NULL, NULL, '2026-06-09 09:34:07', '2026-06-09 10:42:30'),
(84, 4, 'ابو عبد القادر', NULL, NULL, 'abo_qader', 'abo_qader@gmail.com', NULL, '$2y$12$GgcOjPm/H2WcKBntiWD0pOPKL5APVB40MYzLKbA1aaZEe0NVOyodq', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 09:35:12', '2026-06-09 09:35:12'),
(85, 3, 'جودي سلطاني', NULL, NULL, '202604', 'jodi@gmail.com', 'avatars/DzoiyNen1yaIkPuVq3I9bcAKeNumDqaTBEyZQm8D.png', '$2y$12$dTKQnHTIW.A1wTXVtCvLq.Ht5OG6rZeJTtq2Q7f1UkU60YU.JNGc6', '0988888888', NULL, '202604', 'نظم معلومات', NULL, NULL, 'أنثى', '2006-01-01', 'السنة الثانية', 'active', 'c9ddhudKQEWZuBjGL-W1wx:APA91bGUx0QT9K7m4ve9G6YzPKWgLPK5PhqwkstW0X_yZsLmnX3CctMe61mptBbzRTRFDrC3GNteT2H8gF8g0TQwJXolJOjRs9HoEDzEEtu8F8cF1j-tMGc', '2026-07-05 17:20:02', NULL, '2026-06-09 09:36:45', '2026-07-05 17:20:05'),
(86, 4, 'احمد سلطاني', NULL, NULL, 'ahmad_soltane', 'ahmad_soltane@gmail.com', NULL, '$2y$12$.uerad11IIZ7Mk6HAWjumO9LLPUpfloaR5u3DMNne/tG7xgXiXDfG', '0988888888', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 09:37:53', '2026-06-09 09:37:53'),
(87, 3, 'محمود عثمان', NULL, NULL, '202611', 'mahmoud_oth@gmail.com', NULL, '$2y$12$ND6migC//rIHssUOqCNm5ePWV78ixwH2PhexhssRkyr7r4fF.ZpDa', '0999999999', NULL, '202611', 'نظم معلومات', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 11:10:15', '2026-06-09 20:55:55'),
(88, 4, 'محمد عثمان', NULL, NULL, 'mhmd_othman', 'mhmd_othman@gmail.com', NULL, '$2y$12$kyinwdIVKP8Tr5HKDRniYeQjcSsFvcnsi.YnC.hwVx.qKqPoPGK2G', '0933333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 11:14:09', '2026-06-09 11:14:09'),
(89, 3, 'خليل خليل', NULL, NULL, '202612', 'khalil@gmail.com', NULL, '$2y$12$nFXkh.98A2VHyuR9up1hJOJlK45Lbop/5co7NZCreNwuaIvaoUWvO', '0911111111', NULL, '202612', 'نظم معلومات', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 11:15:46', '2026-06-09 11:15:46'),
(90, 4, 'محمد خليل', NULL, NULL, 'mhmd_khalil', 'mhmd_khalil@gmail.com', NULL, '$2y$12$rm.G6swrQgIthSk/z3q4J.mQHQVNMdz2KEGCVt7/1HdQD0bJrqJGO', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 11:16:58', '2026-06-09 11:16:58'),
(91, 3, 'فيصل فيصل', NULL, NULL, '202613', 'fisal@gmail.com', NULL, '$2y$12$m2FbWwURcOmr8P7gjfivJuZu8DA8t/KX3YCjyL6HYiQ9fpejHW6PC', '0999999999', NULL, '202613', 'نظم معلومات', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 11:18:02', '2026-06-09 11:18:02'),
(92, 4, 'محمد فيصل', NULL, NULL, 'mhmd_fisal', 'mhmd_fisal@gmail.com', NULL, '$2y$12$/ZFtHx48LBUCqn/Kf.8UfesyJZqYMveii.TXE7sjztWkeDCs6gzRS', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 11:19:21', '2026-06-09 11:19:21'),
(93, 3, 'حسن علي', NULL, NULL, '202614', 'hasan@gmail.com', NULL, '$2y$12$VUf6TV.P6Wkz5hI1PqaJieFA7BF6P/bx1KjG9xvDvOlZJ31nZHwBy', '0911111111', NULL, '202614', 'نظم معلومات', NULL, NULL, 'ذكر', '2006-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 11:30:45', '2026-06-09 11:30:45'),
(94, 4, 'محمد علي', NULL, NULL, 'mhmd_ali', 'mhmd_ali@gmail.com', NULL, '$2y$12$/QjD9yMurXv2NzUE4ali4.NCkrsBYGxm2FpkhuUWejNyZOFq3xJky', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 11:31:50', '2026-06-09 11:31:50'),
(96, 3, 'جواد نوفل', NULL, NULL, '202615', 'jwad@gmail.com', NULL, '$2y$12$JWHkn4RbaoBjPLRbFZ8R/u.4RVV3LzNJePdL7YZ1un1QIRUQxAsrK', '0911111111', NULL, '202615', 'هندسي', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:09:24', '2026-06-09 12:09:24'),
(97, 4, 'محمد نوفل', NULL, NULL, 'mhmd_nofal', 'mhmd_nofal@gmail.com', NULL, '$2y$12$giVg6pmKn45V5pn7ElbthO2MgdAlSaeTEw/RfkbTf0qHppMzgl8Tm', '0933333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:10:16', '2026-06-09 12:10:16'),
(98, 3, 'خالد خالد', NULL, NULL, '202616', 'khalid@gmail.com', NULL, '$2y$12$JvZf9PRwv04GxuMNO..U/eEkOCaJ5Nz793lju8H7eB/lwl0oZDUa6', '0933333333', NULL, '202616', 'هندسي', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:13:16', '2026-06-09 12:13:16'),
(99, 4, 'محمد خالد', NULL, NULL, 'mhmd_khalid', 'mhmd_khalid@gmail.com', NULL, '$2y$12$RG1Oo.Oup8OxhnReNyViWeRo4GtDPyk7HwCR4jlYUSSX4/bGOb2aW', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:14:52', '2026-06-09 12:14:52'),
(100, 3, 'ندى طه', NULL, NULL, '202617', 'nada@gmail.com', NULL, '$2y$12$mGKrLgI0YIHazOoRw3cPregD4FOVigkdAM37OjGYNTEr11tjl0KqK', '0933333333', NULL, '202617', 'تجاري', NULL, NULL, 'أنثى', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:18:08', '2026-06-09 12:18:53'),
(101, 4, 'محمد طه', NULL, NULL, 'mhmd_taha', 'mhmd_taha@gmail.com', NULL, '$2y$12$q5PcEV3R9Bk4YwgnexnL.OMQzvCIvAAP1MogL.zqi.IbYaJ0Z/ds2', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:19:45', '2026-06-09 12:19:45'),
(102, 3, 'اية زيد', NULL, NULL, '202618', 'aya@gmail.com', NULL, '$2y$12$2KaksW/bZ2eem7m12HGMDuAHFczXt9mBWUPefmozJpvZH24j2HGlG', '0911111111', NULL, '202618', 'تجاري', NULL, NULL, 'أنثى', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:21:42', '2026-06-09 12:21:42'),
(103, 4, 'محمد زيد', NULL, NULL, 'mhmd_zid', 'mhmd_zid@gmail.com', NULL, '$2y$12$9p3jjNHVwY91iUhxiINp3O62BgZh/g8b0p2aEBxfWVaA9DPsuiZFe', '0911111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:23:07', '2026-06-09 12:23:07'),
(104, 3, 'عدنان عزام', NULL, NULL, '202619', 'adnan@gmail.com', NULL, '$2y$12$FZK0.H6JSJ3mo5aItSkRcOu0ZHNDv6y09Rc6b2romESp5lh0xXhWe', '0933333333', NULL, '202619', 'طبي', NULL, NULL, 'ذكر', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:24:34', '2026-06-09 12:24:34'),
(105, 4, 'محمد عزام', NULL, NULL, 'mhmd_azam', 'mhmd_azam@gmail.com', NULL, '$2y$12$ykAErtOQS3owqohCiToNmuAsZibI1xI9Xk3vsO.NVtlubx5zELWVe', '0933333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:25:38', '2026-06-09 12:25:38'),
(106, 3, 'سارة احمد', NULL, NULL, '202620', 'sara@gmail.com', NULL, '$2y$12$pakrZb79qWJ.YCrG4oKPxevnfQPMc59qvwzC6MQ1bKN38shWJJWrW', '0911111111', NULL, '202620', 'طبي', NULL, NULL, 'أنثى', '2007-01-01', 'السنة الأولى', 'active', NULL, NULL, NULL, '2026-06-09 12:26:35', '2026-06-09 12:26:35'),
(107, 4, 'محمد احمد', NULL, NULL, 'mhmd_ahmad', 'mhmd_ahmad@gmail.com', NULL, '$2y$12$bct7g2PNDKzQdya/hREQ9een1d8Y7/ITRxv/2.a18CHL9qwla.3E6', '0933333333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 12:27:16', '2026-06-09 12:27:16'),
(108, 2, 'احمد سالم', NULL, NULL, 'ahmed', 'ahmed@edu.com', NULL, '$2y$12$3EABQUHaGt7xVtHl3bEIau58O5DKokguh4363XHqQ2R/GHoEZw2bK', '0911111111', NULL, NULL, 'طبي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 13:18:25', '2026-06-09 13:18:25'),
(109, 2, 'سامر ياسين', NULL, NULL, 'samer', 'samer@edu.com', NULL, '$2y$12$/ITrhmzPWXxI42KwsmJuruiaMLGh5h26tPC70U8dPA6XvDi7d1JZ2', '0933333333', NULL, NULL, 'طبي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 13:19:19', '2026-06-09 13:19:19'),
(110, 2, 'منى خليل', NULL, NULL, 'mona', 'mona@edu.com', NULL, '$2y$12$93hMMCapdWW.j.0LsjfRVe6AP/rUNjqUu5HkQFIp0HGSRj3/lr0k.', '0933333333', NULL, NULL, 'طبي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 14:16:09', '2026-06-09 14:16:09'),
(111, 2, 'ريم عبد الله', NULL, NULL, 'reem', 'reem@edu.com', NULL, '$2y$12$SEpih.IDrWs47bcsCwo8LOFP9Asdw5O3hDJLkI/hAFx2ZYtf7x33G', '0911111111', NULL, NULL, 'طبي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 14:16:49', '2026-06-09 14:16:49'),
(112, 2, 'خالد اسماعيل', NULL, NULL, '+9631234567890', 'kh_ismail@edu.com', NULL, '$2y$12$LNNAyprxsxX1FmYf90hkYO2p3dZmh2ZRBmE8wOFD9V2t6k3R12iPe', '+9631234567890', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', 'dV1tKxHjRrK-769PYSJru-:APA91bEUt_ff69wg0PDOd2p9fs9sIVRJFeK1CCka-UhGNY_SEDWXMkndSLBrHxSBkku_MvgB9euIaZaKV5dfbu0Z5kqg8h6h66WiOrrGOb9ot0oTVRr0nw8', '2026-07-05 17:36:31', 'Nj5BxMRm5xTWQDvYgXigahtOGGNMcSstdnqev9AMkVbFGYOFTKgOy4mAwhS5', '2026-06-09 15:05:17', '2026-07-05 17:36:33'),
(113, 2, 'ابراهيم جبارة', NULL, NULL, 'ibr_jbara', 'ibr_jbara@edu.com', NULL, '$2y$12$X3r8ersFj0Oa2Z58EmA9BepKZSe6qrtDcy6tNNAE/tboj4yia4Zfm', '0911111111', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', 'dWKBi1-ZQWq6pIx8PQ2WbO:APA91bF2VoETwMMj5Lk3G8hGPTUbiLxcYWDiij73Tg2HsJ7wmDJ3OGKkvtZE0hb-L2I2nPH4Lwa89GyPeVDQhSj65Q-wTMSeutTQN_4hSptdH7FNwaAgtnc', '2026-07-05 12:52:33', NULL, '2026-06-09 15:19:21', '2026-07-05 12:52:33'),
(114, 2, 'احمد نصلة', NULL, NULL, 'ahmd_nasle', 'ahmd_nasle@edu.com', NULL, '$2y$12$i8sRtr7obqICMsIa8Muuzet8BwnziCbhl2pTvJIMryz5MvTBDfJgO', '0911111111', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', 'c9ddhudKQEWZuBjGL-W1wx:APA91bGUx0QT9K7m4ve9G6YzPKWgLPK5PhqwkstW0X_yZsLmnX3CctMe61mptBbzRTRFDrC3GNteT2H8gF8g0TQwJXolJOjRs9HoEDzEEtu8F8cF1j-tMGc', '2026-07-05 17:13:16', NULL, '2026-06-09 15:21:43', '2026-07-05 17:13:16'),
(115, 2, 'حذيفة محمد', NULL, NULL, 'hthifa', 'hthifa@edu.com', NULL, '$2y$12$YH3bN3HHde2wB6bPijPBZuZDZPsS/vxq3DX2G6RckBh8QpG.1d11K', '0999999999', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:29:51', '2026-06-09 15:29:51'),
(116, 2, 'عبد الله محمد', NULL, NULL, 'abdalla', 'abdalla@edu.com', NULL, '$2y$12$GYE.rgCiqCs8zIKKwsUiA.Gaon4DZn8J5fxixlAM/RZG74x8g9r8S', '0911111111', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:32:53', '2026-06-09 15:32:53'),
(117, 2, 'محمد صبح', NULL, NULL, 'mhmd_sobeh', 'mhmd_sobeh@edu.com', NULL, '$2y$12$.6GEUKaf9fMVD/5tEvLEP.guMwL8Pw2lRIgIMLOx0FN6grRdY3VDa', '0911111111', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:34:16', '2026-06-09 15:34:16'),
(118, 2, 'اسراء دسوقي', NULL, NULL, 'israa_dasoqe', 'israa_dasoqe@edu.com', NULL, '$2y$12$bnki5/T0fqNXLUL/UMQaLOEK1c7iPuCOQ/yf9DykR31OYjlIfXFd.', '0933333333', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', 'dWKBi1-ZQWq6pIx8PQ2WbO:APA91bF2VoETwMMj5Lk3G8hGPTUbiLxcYWDiij73Tg2HsJ7wmDJ3OGKkvtZE0hb-L2I2nPH4Lwa89GyPeVDQhSj65Q-wTMSeutTQN_4hSptdH7FNwaAgtnc', '2026-07-05 13:14:36', NULL, '2026-06-09 15:35:13', '2026-07-05 13:14:40'),
(119, 2, 'جمال عمري', NULL, NULL, 'jamal_omry', 'jamal_omry@edu.com', NULL, '$2y$12$PlEVUbYkb7FNOpIkvkOWLOL9DUl.MBJ3q67/SrcNpI4aV.hegC6r.', '0966666666', NULL, NULL, 'نظم معلومات', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:35:46', '2026-06-09 15:35:46'),
(120, 2, 'طارق زيدان', NULL, NULL, 'tareq', 'tareq@edu.com', NULL, '$2y$12$/ATMO/gZmPrS4YePjUJSF.3kGl05jhe64hQRUvbHibMyj.m3KG4.W', '0933333333', NULL, NULL, 'هندسي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:56:38', '2026-06-09 15:56:38'),
(121, 2, 'ليلى عثمان', NULL, NULL, 'layla', 'layla@edu.com', NULL, '$2y$12$32Ee6.7Wm2gKMQOaV00cLeR5DF1P1bL/l16CQWoMCb1305Gz1e0qm', '09444444444', NULL, NULL, 'هندسي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:57:21', '2026-06-09 15:57:21'),
(122, 2, 'يوسف النجار', NULL, NULL, 'yousef', 'yousef@edu.com', NULL, '$2y$12$5b58kUJNoUdhoQnPiB3GMetScbTsv0O6/sgofSvG0myLCNmBosQ56', '0966666666', NULL, NULL, 'هندسي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:57:57', '2026-06-09 15:57:57'),
(123, 2, 'ناديا محمود', NULL, NULL, 'nadia', 'nadia@edu.com', NULL, '$2y$12$L5pu3VNqkz6n9rmiqr.7Le/J//VHheq5/6wxFcn9uISq9PBu8XM6W', '0922222222', NULL, NULL, 'هندسي', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 15:58:32', '2026-06-09 15:58:32'),
(124, 2, 'عمر الخالد', NULL, NULL, 'omar', 'omar@edu.com', NULL, '$2y$12$b74YGC4ngNwWYvBomiEA2udcHT0uEFF.SXQXmr7CvJ/vKWokt/Yrq', '0911111111', NULL, NULL, 'تجاري', NULL, NULL, NULL, NULL, NULL, 'active', NULL, '2026-06-17 10:47:17', NULL, '2026-06-09 16:03:23', '2026-06-17 10:47:17'),
(125, 2, 'كمال منصور', NULL, NULL, '+963986387992', 'kamal@edu.com', 'avatars/Xlhgyh9qLDfNZt2MNvInM5xcWPZJ4BzgqODleVQu.jpg', '$2y$12$TvSzPb0FgI959mFOemQj6OLa9QtNN3q6b1G6NODMXpKaPAVMVwhFu', '+963986387992', NULL, NULL, 'تجاري', NULL, NULL, NULL, NULL, NULL, 'active', 'cunTNpuPQMa0XJzXe_tkOr:APA91bGXhJJaSz3k-ANpp7aHNA5bd3ncR5CmhJYTPkFPIWqRlpaP_wXUvMgqEACFhPDUWhNqPJifL6AzEEZHug3F-985AVzy6N9gGlYUZNUaE3sz59HEXXI', '2026-06-19 10:02:39', NULL, '2026-06-09 16:04:10', '2026-06-19 10:02:39'),
(126, 2, 'هبة الجندي', NULL, NULL, 'heba', 'heba@edu.com', NULL, '$2y$12$fRKkKI9PvnoNRYvaq.YBNeBvQC39GRTz8VdUHLWerad3FSgJ4G9KC', '0999999999', NULL, NULL, 'تجاري', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 16:05:04', '2026-06-09 16:05:04'),
(127, 2, 'زينب ابراهيم', NULL, NULL, 'zeinab', 'zeinab@edu.com', NULL, '$2y$12$LSr/1Vve405bUsXGYXx6BOuYb2WrN4LRFqYr/Un5p/YAEzXoJzo1W', '0999999999', NULL, NULL, 'تجاري', NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-09 16:05:42', '2026-06-09 16:26:25'),
(128, 3, 'هبة عيسى', NULL, NULL, '202621', 'hiba_isaa@gmail.com', NULL, '$2y$12$nACon2y.w71.8W3TcnP5Cu.94husEIT.Mnx89z91uKBNU68ySgu2u', '0999999999', NULL, '202621', 'نظم معلومات', NULL, NULL, 'أنثى', '2006-11-01', 'السنة الثانية', 'active', 'cQWxvZxeQT2b7Uq2pq7zuI:APA91bGw8cNQvmXJq2Z0KaPtI682HeBKGCt5H1yJPsXENLMdZQENmp9CqIboGjGNg5JTFvUPQmefqG4pGfiJUt-eXtvGq8hYnqsjZ5Q7B5nN24_qHHXf_Ew', '2026-06-11 10:44:51', NULL, '2026-06-09 20:14:49', '2026-06-11 10:44:51'),
(129, 4, 'ناصر عيسى', NULL, NULL, 'naser_issa', 'naser_issa@gmail.com', NULL, '$2y$12$nEgXKBS7dsH7uOizJlrwreR4z9UTyWm3C2ePqjVMyscXMweabliWi', '0999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'cQWxvZxeQT2b7Uq2pq7zuI:APA91bGw8cNQvmXJq2Z0KaPtI682HeBKGCt5H1yJPsXENLMdZQENmp9CqIboGjGNg5JTFvUPQmefqG4pGfiJUt-eXtvGq8hYnqsjZ5Q7B5nN24_qHHXf_Ew', '2026-06-11 14:37:12', NULL, '2026-06-11 14:35:49', '2026-06-11 14:37:12'),
(130, 3, 'محمد أحمد السيد', 'محمد', 'السيد', 'st_test1', 'st_test1@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024001', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:23:42', '2026-06-19 10:23:42'),
(131, 3, 'سارة علي حسن', 'سارة', 'حسن', 'st_test2', 'st_test2@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024002', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:23:59', '2026-06-19 10:23:59'),
(132, 3, 'عمر خالد مصطفى', 'عمر', 'مصطفى', 'st_test3', 'st_test3@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024003', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(133, 3, 'نور إبراهيم جمال', 'نور', 'جمال', 'st_test4', 'st_test4@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024004', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(134, 3, 'ليلى محمود عمر', 'ليلى', 'عمر', 'st_test5', 'st_test5@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024005', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(135, 3, 'كريم يوسف ناصر', 'كريم', 'ناصر', 'st_test6', 'st_test6@edu.com', NULL, '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024006', NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-19 10:24:15', '2026-06-19 10:24:15'),
(136, 3, 'هنا سعيد رشيد', 'هنا', 'رشيد', 'st_test7', 'st_test7@edu.com', 'avatars/yYD5SSU4OB6NVMUOlEZ1fb8UsQFGhCmusJ4uplU3.jpg', '$2y$12$/jsE3G9UPyqPnQS.fRO/ReJNvEPw/q1ffd0wtG3DMwOL4wikVnJJK', NULL, NULL, 'IT2024007', NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'coW9qngKSy6rsJ_fEsJGXa:APA91bG2SaDwW-xUuKqvh-p5PghjYPqkEAI3OJMOoMKyAKJsBTi2B4iwFU-Z1we-MLtDRE0t2PtgfCApThuXUlnBYlF9pS7JIGddYqUFcAtQY-qBpEHtP9A', '2026-07-05 11:54:44', NULL, '2026-06-19 10:24:15', '2026-07-05 11:54:46'),
(137, 4, 'والدة هنا سعيد رشيد', NULL, NULL, 'parent_st_test7_edu_com', 'parent_st_test7@edu.com', NULL, '$2y$12$wRDLHHjN74OXUyXanANOPuRWt8QSeiBywwdTLOpybZvycqhArwCHy', '+96391644944', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:13:23', '2026-06-21 16:13:23'),
(138, 4, 'والد محمد أحمد السيد', NULL, NULL, 'parent_st_test1_edu_com', 'parent_st_test1@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391939521', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(139, 4, 'والدة جودي سلطاني', NULL, NULL, 'parent_jodi_gmail_com', 'parent_jodi@gmail.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391122593', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(140, 4, 'والد عمر خالد مصطفى', NULL, NULL, 'parent_st_test3_edu_com', 'parent_st_test3@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391218271', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(141, 4, 'والدة نور إبراهيم جمال', NULL, NULL, 'parent_st_test4_edu_com', 'parent_st_test4@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391313984', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(142, 4, 'والدة ليلى محمود عمر', NULL, NULL, 'parent_st_test5_edu_com', 'parent_st_test5@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391210244', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(143, 4, 'والد كريم يوسف ناصر', NULL, NULL, 'parent_st_test6_edu_com', 'parent_st_test6@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391239264', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(144, 4, 'والدة سارة علي حسن', NULL, NULL, 'parent_st_test2_edu_com', 'parent_st_test2@edu.com', NULL, '$2y$12$GKISd5/s9/vka.6Bu/8xCeuEC2a4tZp/BtL9RRKnkxpcWzGhzGxbu', '+96391384737', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, NULL, '2026-06-21 16:14:55', '2026-06-21 16:14:55'),
(145, 4, 'أم هناء رشيد', 'أم هناء', 'رشيد', 'parent_hana_rashid', 'parent.hana@edubridge.com', NULL, '$2y$12$l1n8HtnV5guzcmZozyO67uA6rD5z9a0yUGx7Y6o2oUiHh1L9/HXga', '0991234570', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'dV1tKxHjRrK-769PYSJru-:APA91bEUt_ff69wg0PDOd2p9fs9sIVRJFeK1CCka-UhGNY_SEDWXMkndSLBrHxSBkku_MvgB9euIaZaKV5dfbu0Z5kqg8h6h66WiOrrGOb9ot0oTVRr0nw8', '2026-07-05 11:54:14', NULL, '2026-06-26 18:53:40', '2026-07-05 11:54:15'),
(147, 3, 'هدى شبلي', 'هدى', 'شبلي', 'hudashbli8', 'hudashbli8@gmail.com', NULL, '$2y$12$gsjHmCJstR8A/o85Rhq.peqItCYiK93pl/RdaPvSjdhZCJYzyQuWm', '0986387552', NULL, '2026100', 'نظم معلومات', 'معلوماتية', NULL, 'أنثى', '2006-07-01', 'السنة الأولى', 'active', 'c9ddhudKQEWZuBjGL-W1wx:APA91bGUx0QT9K7m4ve9G6YzPKWgLPK5PhqwkstW0X_yZsLmnX3CctMe61mptBbzRTRFDrC3GNteT2H8gF8g0TQwJXolJOjRs9HoEDzEEtu8F8cF1j-tMGc', '2026-07-05 17:11:48', NULL, '2026-07-05 12:23:06', '2026-07-05 17:11:49'),
(148, 4, 'ثناء شبلي', 'ثناء', 'شبلي', 'thanaashbli', 'thanaashbli@gmail.com', NULL, '$2y$12$7Mhux93wGr5aiEG8FMRRE.AdaCoy4zDjacdZV18rmQ2uXB5hzaZHi', '0987654321', NULL, NULL, NULL, NULL, '[\"2026100\"]', NULL, NULL, NULL, 'active', 'c9ddhudKQEWZuBjGL-W1wx:APA91bGUx0QT9K7m4ve9G6YzPKWgLPK5PhqwkstW0X_yZsLmnX3CctMe61mptBbzRTRFDrC3GNteT2H8gF8g0TQwJXolJOjRs9HoEDzEEtu8F8cF1j-tMGc', '2026-07-05 17:12:30', NULL, '2026-07-05 12:29:12', '2026-07-05 17:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `activity_type` varchar(255) NOT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absence_requests`
--
ALTER TABLE `absence_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `absence_requests_student_id_foreign` (`student_id`),
  ADD KEY `absence_requests_reviewed_by_foreign` (`reviewed_by`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `admins_user_id_foreign` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `announcements_user_id_foreign` (`user_id`),
  ADD KEY `announcements_course_id_foreign` (`course_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `assignments_course_id_foreign` (`course_id`),
  ADD KEY `assignments_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_submissions_assignment_id_foreign` (`assignment_id`),
  ADD KEY `assignment_submissions_student_id_foreign` (`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `attendance_student_id_foreign` (`student_id`),
  ADD KEY `attendance_lesson_id_foreign` (`lesson_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_sessions_qr_token_unique` (`qr_token`),
  ADD KEY `attendance_sessions_lesson_id_foreign` (`lesson_id`);

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
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `calendar_events_user_id_foreign` (`user_id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `chats_sender_id_foreign` (`sender_id`),
  ADD KEY `chats_receiver_id_foreign` (`receiver_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `courses_semester_id_foreign` (`semester_id`);

--
-- Indexes for table `course_program`
--
ALTER TABLE `course_program`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_program_course_id_foreign` (`course_id`),
  ADD KEY `course_program_program_id_foreign` (`program_id`);

--
-- Indexes for table `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD PRIMARY KEY (`course_teacher_id`),
  ADD KEY `course_teachers_course_id_foreign` (`course_id`),
  ADD KEY `course_teachers_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `enrollments_student_id_foreign` (`student_id`),
  ADD KEY `enrollments_course_id_foreign` (`course_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `exams_course_id_foreign` (`course_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `grades_student_id_foreign` (`student_id`),
  ADD KEY `grades_exam_id_foreign` (`exam_id`);

--
-- Indexes for table `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_entries_grade_event_id_student_id_unique` (`grade_event_id`,`student_id`),
  ADD KEY `grade_entries_student_id_foreign` (`student_id`);

--
-- Indexes for table `grade_events`
--
ALTER TABLE `grade_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_events_teacher_id_foreign` (`teacher_id`),
  ADD KEY `grade_events_course_id_foreign` (`course_id`);

--
-- Indexes for table `grade_report_requests`
--
ALTER TABLE `grade_report_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_report_requests_course_id_foreign` (`course_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_user`
--
ALTER TABLE `group_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_user_group_id_foreign` (`group_id`),
  ADD KEY `group_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `heads`
--
ALTER TABLE `heads`
  ADD PRIMARY KEY (`head_id`),
  ADD KEY `heads_user_id_foreign` (`user_id`),
  ADD KEY `heads_department_id_foreign` (`department_id`);

--
-- Indexes for table `head_schedule_entries`
--
ALTER TABLE `head_schedule_entries`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_requests_student_id_foreign` (`student_id`),
  ADD KEY `leave_requests_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `lessons_course_id_foreign` (`course_id`),
  ADD KEY `lessons_department_id_foreign` (`department_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`),
  ADD KEY `messages_receiver_id_foreign` (`receiver_id`),
  ADD KEY `idx_messages_conversation` (`sender_id`,`receiver_id`),
  ADD KEY `idx_messages_unread` (`receiver_id`,`sender_id`,`is_read`),
  ADD KEY `idx_messages_latest` (`sender_id`,`receiver_id`,`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`),
  ADD KEY `notifications_sender_id_foreign` (`sender_id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otps_email_index` (`email`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otp_codes_email_index` (`email`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD KEY `parents_user_id_foreign` (`user_id`);

--
-- Indexes for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parent_students_parent_id_student_id_unique` (`parent_id`,`student_id`),
  ADD KEY `parent_students_student_id_foreign` (`student_id`);

--
-- Indexes for table `performance_reports`
--
ALTER TABLE `performance_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `performance_reports_student_id_foreign` (`student_id`),
  ADD KEY `performance_reports_report_request_id_foreign` (`report_request_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `photo_change_requests`
--
ALTER TABLE `photo_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `photo_change_requests_user_id_foreign` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programs_department_id_foreign` (`department_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quizzes_teacher_id_foreign` (`teacher_id`),
  ADD KEY `quizzes_course_id_foreign` (`course_id`);

--
-- Indexes for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_options_question_id_foreign` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_questions_quiz_id_foreign` (`quiz_id`);

--
-- Indexes for table `report_requests`
--
ALTER TABLE `report_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_requests_head_id_foreign` (`head_id`),
  ADD KEY `report_requests_teacher_id_foreign` (`teacher_id`),
  ADD KEY `report_requests_student_id_foreign` (`student_id`),
  ADD KEY `report_requests_course_id_foreign` (`course_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `resources_course_id_foreign` (`course_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `schedules_course_id_foreign` (`course_id`),
  ADD KEY `schedules_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `students_student_code_unique` (`student_code`),
  ADD KEY `students_user_id_foreign` (`user_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD KEY `teachers_user_id_foreign` (`user_id`);

--
-- Indexes for table `university_ids`
--
ALTER TABLE `university_ids`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `university_ids_university_id_unique` (`university_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_university_id_unique` (`university_id`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_activity_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absence_requests`
--
ALTER TABLE `absence_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `submission_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `chat_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `course_program`
--
ALTER TABLE `course_program`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `course_teachers`
--
ALTER TABLE `course_teachers`
  MODIFY `course_teacher_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `grade_entries`
--
ALTER TABLE `grade_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `grade_events`
--
ALTER TABLE `grade_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `grade_report_requests`
--
ALTER TABLE `grade_report_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_user`
--
ALTER TABLE `group_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `heads`
--
ALTER TABLE `heads`
  MODIFY `head_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `head_schedule_entries`
--
ALTER TABLE `head_schedule_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1177;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `parent_students`
--
ALTER TABLE `parent_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `performance_reports`
--
ALTER TABLE `performance_reports`
  MODIFY `report_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=690;

--
-- AUTO_INCREMENT for table `photo_change_requests`
--
ALTER TABLE `photo_change_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_requests`
--
ALTER TABLE `report_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `university_ids`
--
ALTER TABLE `university_ids`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `activity_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absence_requests`
--
ALTER TABLE `absence_requests`
  ADD CONSTRAINT `absence_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `absence_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `chats_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE SET NULL;

--
-- Constraints for table `course_program`
--
ALTER TABLE `course_program`
  ADD CONSTRAINT `course_program_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_program_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_teachers`
--
ALTER TABLE `course_teachers`
  ADD CONSTRAINT `course_teachers_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_teachers_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD CONSTRAINT `grade_entries_grade_event_id_foreign` FOREIGN KEY (`grade_event_id`) REFERENCES `grade_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_entries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_events`
--
ALTER TABLE `grade_events`
  ADD CONSTRAINT `grade_events_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grade_events_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_report_requests`
--
ALTER TABLE `grade_report_requests`
  ADD CONSTRAINT `grade_report_requests_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_user`
--
ALTER TABLE `group_user`
  ADD CONSTRAINT `group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `heads`
--
ALTER TABLE `heads`
  ADD CONSTRAINT `heads_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `heads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lessons_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD CONSTRAINT `parent_students_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_reports`
--
ALTER TABLE `performance_reports`
  ADD CONSTRAINT `performance_reports_report_request_id_foreign` FOREIGN KEY (`report_request_id`) REFERENCES `report_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `performance_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `photo_change_requests`
--
ALTER TABLE `photo_change_requests`
  ADD CONSTRAINT `photo_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `quiz_options_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_requests`
--
ALTER TABLE `report_requests`
  ADD CONSTRAINT `report_requests_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `report_requests_head_id_foreign` FOREIGN KEY (`head_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
