-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: edu_bridge_backend
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `absence_requests`
--

DROP TABLE IF EXISTS `absence_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absence_requests` (
  `request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `document` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending_parent',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `absence_requests_student_id_foreign` (`student_id`),
  KEY `absence_requests_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `absence_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `absence_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absence_requests`
--

LOCK TABLES `absence_requests` WRITE;
/*!40000 ALTER TABLE `absence_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `absence_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_generated_reports`
--

DROP TABLE IF EXISTS `admin_generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_generated_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `report_type` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `program_name` varchar(255) DEFAULT NULL,
  `semester_id` bigint(20) unsigned DEFAULT NULL,
  `semester_name` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_generated_reports`
--

LOCK TABLES `admin_generated_reports` WRITE;
/*!40000 ALTER TABLE `admin_generated_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_generated_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `admin_profile_stats_view`
--

DROP TABLE IF EXISTS `admin_profile_stats_view`;
/*!50001 DROP VIEW IF EXISTS `admin_profile_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `admin_profile_stats_view` AS SELECT
 1 AS `total_users`,
  1 AS `total_courses` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  KEY `admins_user_id_foreign` (`user_id`),
  CONSTRAINT `admins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `affairs_dashboard_stats_view`
--

DROP TABLE IF EXISTS `affairs_dashboard_stats_view`;
/*!50001 DROP VIEW IF EXISTS `affairs_dashboard_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `affairs_dashboard_stats_view` AS SELECT
 1 AS `total_students`,
  1 AS `total_teachers`,
  1 AS `total_staff`,
  1 AS `pending_leaves`,
  1 AS `total_users` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `announcement_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT 'general',
  `image` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `link_url` varchar(255) DEFAULT NULL,
  `type` enum('general','course_specific') NOT NULL DEFAULT 'general',
  `target_audience` varchar(255) NOT NULL DEFAULT 'all',
  `target_role` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`announcement_id`),
  KEY `announcements_user_id_foreign` (`user_id`),
  KEY `announcements_department_id_index` (`department_id`),
  KEY `announcements_course_id_index` (`course_id`),
  KEY `announcements_target_audience_index` (`target_audience`),
  KEY `announcements_created_at_index` (`created_at`),
  CONSTRAINT `announcements_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `announcements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,1,'تم إصدار جدول الامتحانات النهائية للفصل الدراسي الأول','يرجى من جميع الطلاب مراجعة الجدول الدراسي والتأكد من توقيت الامتحانات والقاعات المخصصة.',NULL,'general',NULL,NULL,NULL,'general','all',NULL,NULL,NULL,NULL,'2026-09-06 17:30:55','2026-09-06 17:30:55',NULL,NULL,NULL),(2,1,'ورشة عمل حول مهارات البحث العلمي','ندعو جميع الطلاب للحضور والمشاركة في ورشة العمل التي ستقام في مبنى الأنشطة.',NULL,'general',NULL,NULL,NULL,'general','all',NULL,NULL,NULL,NULL,'2026-09-05 17:30:55','2026-09-05 17:30:55',NULL,NULL,NULL);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_submissions` (
  `submission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `solution_text` text DEFAULT NULL,
  `student_notes` text DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  KEY `assignment_submissions_assignment_id_index` (`assignment_id`),
  KEY `assignment_submissions_student_id_index` (`student_id`),
  CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
INSERT INTO `assignment_submissions` VALUES (8,17,31,'assignments/17/1783265406_31_electricity_receipt_REF1779294728570.pdf',NULL,NULL,NULL,NULL,'2026-07-05 15:30:06','2026-07-05 20:30:06','2026-07-05 20:30:06'),(9,16,31,'assignments/16/1783265422_31_JPEG_20260705_183018_6088037005948865680.jpg',NULL,NULL,NULL,NULL,'2026-07-05 15:30:22','2026-07-05 20:30:22','2026-07-05 20:30:22');
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `assignment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `max_points` int(11) NOT NULL DEFAULT 100,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`assignment_id`),
  KEY `assignments_teacher_id_foreign` (`teacher_id`),
  KEY `assignments_course_id_index` (`course_id`),
  CONSTRAINT `assignments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
INSERT INTO `assignments` VALUES (1,1,NULL,'واجب 1: تمارين المتغيرات','حل التمارين من الصفحة 10 إلى 15 وتطبيق أنواع البيانات المختلفة',NULL,NULL,NULL,NULL,'2026-09-13 10:30:56',100,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,1,NULL,'واجب 2: برنامج الحاسبة','كتابة برنامج حاسبة بسيط يدعم العمليات الأربع الأساسية',NULL,NULL,NULL,NULL,'2026-09-20 10:30:56',100,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56');
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `attendance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  KEY `attendance_student_id_foreign` (`student_id`),
  KEY `attendance_lesson_id_foreign` (`lesson_id`),
  CONSTRAINT `attendance_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_sessions`
--

DROP TABLE IF EXISTS `attendance_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `qr_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `closed_at` timestamp NULL DEFAULT NULL COMMENT 'وقت إغلاق الجلسة من المعلم',
  `latitude` decimal(10,7) DEFAULT NULL COMMENT 'خط عرض موقع المعلم عند فتح الجلسة',
  `longitude` decimal(10,7) DEFAULT NULL COMMENT 'خط طول موقع المعلم عند فتح الجلسة',
  `radius_meters` smallint(5) unsigned NOT NULL DEFAULT 50 COMMENT 'الحد الأقصى للمسافة المسموح بها بالمتر (افتراضي 50م)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_sessions_qr_token_unique` (`qr_token`),
  KEY `attendance_sessions_lesson_id_foreign` (`lesson_id`),
  CONSTRAINT `attendance_sessions_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sessions`
--

LOCK TABLES `attendance_sessions` WRITE;
/*!40000 ALTER TABLE `attendance_sessions` DISABLE KEYS */;
INSERT INTO `attendance_sessions` VALUES (99,156,'fuO9TkQA6pd8VIFqI2hHjqXD18q17Jkv','2026-09-12 00:03:56','2026-09-12 00:12:55',1,NULL,NULL,NULL,50,'2026-09-12 00:02:55','2026-09-12 00:03:26');
/*!40000 ALTER TABLE `attendance_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-a75f3f172bfb296f2e10cbfc6dfc1883','i:1;',1789247885),('laravel-cache-a75f3f172bfb296f2e10cbfc6dfc1883:timer','i:1789247885;',1789247885),('laravel-cache-affairs_dashboard_stats','O:8:\"stdClass\":5:{s:14:\"total_students\";i:16;s:14:\"total_teachers\";i:8;s:11:\"total_staff\";i:14;s:14:\"pending_leaves\";i:0;s:11:\"total_users\";i:46;}',1789252779),('laravel-cache-ca1ba00ec28ad76354d8d29b647a510d','i:6;',1789279953),('laravel-cache-ca1ba00ec28ad76354d8d29b647a510d:timer','i:1789279953;',1789279953),('laravel-cache-d189a3df25facd3522214377f5876bcb','i:1;',1789246544),('laravel-cache-d189a3df25facd3522214377f5876bcb:timer','i:1789246544;',1789246544),('laravel-cache-f1f70ec40aaa556905d4a030501c0ba4','i:8;',1789222610),('laravel-cache-f1f70ec40aaa556905d4a030501c0ba4:timer','i:1789222610;',1789222610),('laravel-cache-f332535f113ba93a8a05bdf678fcd444','i:1;',1789280233),('laravel-cache-f332535f113ba93a8a05bdf678fcd444:timer','i:1789280233;',1789280233),('laravel-cache-system_setting_primary_color','s:7:\"#f2f20d\";',1789256319),('laravel-cache-system_theme_settings','a:3:{s:13:\"primary_color\";s:7:\"#f2f20d\";s:11:\"accent_name\";s:4:\"gold\";s:10:\"theme_mode\";s:4:\"dark\";}',1789250025),('laravel-cache-telegram_schedule_student_31_Saturday','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1789335607);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_events_user_id_foreign` (`user_id`),
  KEY `calendar_events_department_id_foreign` (`department_id`),
  CONSTRAINT `calendar_events_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
INSERT INTO `calendar_events` VALUES (18,28,'as','2026-08-02','00:00:00','as',NULL,'2026-08-12 13:26:58','2026-08-12 13:26:58'),(19,28,'qqq3','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:21','2026-08-12 13:32:11'),(20,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:22','2026-08-12 13:27:22'),(21,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:23','2026-08-12 13:27:23'),(22,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:23','2026-08-12 13:27:23'),(23,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:24','2026-08-12 13:27:24'),(24,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:25','2026-08-12 13:27:25'),(25,28,'qqq','2026-08-02',NULL,'qqqq',NULL,'2026-08-12 13:27:25','2026-08-12 13:27:25'),(26,28,'حفل تخرج','2026-08-02','18:00:00',NULL,NULL,'2026-08-12 13:29:08','2026-08-12 13:29:08'),(27,28,'تجربة','2025-01-12','00:00:00','تجربة',NULL,'2026-08-12 13:38:50','2026-08-12 13:38:50'),(28,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:02','2026-08-13 13:01:02'),(29,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:03','2026-08-13 13:01:03'),(30,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:04','2026-08-13 13:01:04'),(31,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:04','2026-08-13 13:01:04'),(32,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:04','2026-08-13 13:01:04'),(33,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:04','2026-08-13 13:01:04'),(34,28,'ن','2026-08-13',NULL,NULL,NULL,'2026-08-13 13:01:05','2026-08-13 13:01:05'),(37,28,'تجربة','2026-08-14','00:00:00','ِA1',NULL,'2026-08-13 14:16:12','2026-08-13 14:16:12');
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chats` (
  `chat_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`chat_id`),
  KEY `chats_sender_id_foreign` (`sender_id`),
  KEY `chats_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `chats_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `chats_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chats`
--

LOCK TABLES `chats` WRITE;
/*!40000 ALTER TABLE `chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_program`
--

DROP TABLE IF EXISTS `course_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_program` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_program_course_id_foreign` (`course_id`),
  KEY `course_program_program_id_foreign` (`program_id`),
  CONSTRAINT `course_program_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `course_program_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_program`
--

LOCK TABLES `course_program` WRITE;
/*!40000 ALTER TABLE `course_program` DISABLE KEYS */;
INSERT INTO `course_program` VALUES (1,2,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(2,3,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(3,4,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(4,5,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(5,6,2,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(6,7,2,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(7,8,2,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(8,9,2,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(9,6,3,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(10,7,3,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(11,10,3,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(12,11,3,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(13,12,4,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(14,7,4,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(15,13,4,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(16,14,4,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(18,17,1,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(19,18,1,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(20,19,1,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(21,20,1,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(22,21,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(23,22,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(24,23,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(25,24,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(26,25,3,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(27,26,3,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(28,27,3,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(29,28,3,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(30,29,4,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(31,30,4,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(32,31,4,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(33,32,4,'2026-09-18 20:30:27','2026-09-18 20:30:27');
/*!40000 ALTER TABLE `course_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_teachers`
--

DROP TABLE IF EXISTS `course_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_teachers` (
  `course_teacher_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`course_teacher_id`),
  KEY `course_teachers_teacher_id_index` (`teacher_id`),
  KEY `course_teachers_course_id_index` (`course_id`),
  CONSTRAINT `course_teachers_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `course_teachers_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_teachers`
--

LOCK TABLES `course_teachers` WRITE;
/*!40000 ALTER TABLE `course_teachers` DISABLE KEYS */;
INSERT INTO `course_teachers` VALUES (3,2,24,NULL,'2026-09-10 18:54:13','2026-09-10 18:54:13'),(4,14,24,NULL,'2026-09-10 18:54:13','2026-09-10 18:54:13'),(9,4,27,NULL,'2026-09-11 01:54:11','2026-09-11 01:54:11'),(10,5,27,NULL,'2026-09-11 01:54:11','2026-09-11 01:54:11'),(11,7,28,NULL,'2026-09-11 01:57:30','2026-09-11 01:57:30'),(12,12,25,NULL,'2026-09-11 01:58:20','2026-09-11 01:58:20'),(13,13,25,NULL,'2026-09-11 01:58:20','2026-09-11 01:58:20'),(14,8,29,NULL,'2026-09-11 02:00:02','2026-09-11 02:00:02'),(15,9,29,NULL,'2026-09-11 02:00:02','2026-09-11 02:00:02'),(16,3,26,NULL,'2026-09-11 02:01:01','2026-09-11 02:01:01'),(17,6,26,NULL,'2026-09-11 02:01:01','2026-09-11 02:01:01'),(18,6,30,NULL,'2026-09-11 02:02:36','2026-09-11 02:02:36'),(19,7,30,NULL,'2026-09-11 02:02:36','2026-09-11 02:02:36'),(20,10,31,NULL,'2026-09-11 02:04:22','2026-09-11 02:04:22'),(21,11,31,NULL,'2026-09-11 02:04:22','2026-09-11 02:04:22'),(22,17,27,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(23,18,26,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(24,19,28,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(25,20,24,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(26,21,29,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(27,22,30,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(28,23,29,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(29,24,30,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(30,25,31,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(31,26,28,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(32,27,31,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(33,28,28,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(34,29,25,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(35,30,26,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(36,31,25,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(37,32,24,NULL,'2026-09-18 20:30:27','2026-09-18 20:30:27');
/*!40000 ALTER TABLE `course_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `course_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1 COMMENT 'تثقيل المادة (عدد الساعات)',
  `description` text DEFAULT NULL,
  `level` varchar(255) NOT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `year` tinyint(4) DEFAULT 1,
  `semester_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  KEY `courses_semester_id_foreign` (`semester_id`),
  CONSTRAINT `courses_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (2,'خوارزميات',3,'مقرر خوارزميات','beginner',3,1,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(3,'رياضيات حاسوبية',1,'مقرر رياضيات حاسوبية','beginner',3,1,1,'2026-09-09 18:20:56','2026-09-11 17:27:00'),(4,'laravel',1,'مقرر laravel','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:27:16'),(5,'Flutter',1,'مقرر Flutter','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:27:09'),(6,'c++',3,'مقرر c++','beginner',3,1,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(7,'شبكات',3,'مقرر شبكات','beginner',3,1,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(8,'اتصالات خليوية',2,'مقرر اتصالات خليوية','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:26:42'),(9,'مايكروية',1,'مقرر مايكروية','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:26:50'),(10,'طاقة شمسية',2,'مقرر طاقة شمسية','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:27:29'),(11,'معالجات',1,'مقرر معالجات','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 17:28:59'),(12,'c#',3,'مقرر c#','beginner',3,1,1,'2026-09-09 18:20:56','2026-09-09 18:20:56'),(13,'تصميم العاب',2,'مقرر تصميم العاب','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 18:00:00'),(14,'رؤية حاسوبية',1,'مقرر رؤية حاسوبية','beginner',3,2,1,'2026-09-09 18:20:56','2026-09-11 18:00:09'),(17,'قواعد بيانات 1',3,'مقرر قواعد البيانات ونظم إدارة قواعد البيانات العلائقية SQL','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(18,'هياكل بيانات',2,'مقرر تراكيب وهياكل البيانات وتطبيقاتها البرمجية','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(19,'أمن الشبكات والمعلومات',2,'مقرر أمن الشبكات وحماية النظم السيبرانية','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(20,'مشروع التخرج البرمجي',3,'مشروع التخرج العملي التطبيقي لبرمجيات الويب والموبايل','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(21,'إشارات ونظم',3,'مقرر تحليل الإشارات ومعالجة النظم التماثلية والرقمية','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(22,'دارات كهربائية وإلكترونية',2,'مقرر الدارات الكهربائية والإلكترونية ونظريات التيار','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(23,'شبكات الألياف الضوئية',2,'مقرر اتصالات الألياف البصرية ونظم التراسل الضوئي','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(24,'مشروع التخرج في الاتصالات',3,'مشروع التخرج العملي في هندسة الاتصالات والشبكات','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(25,'إلكترونيات صناعية',3,'مقرر الدوائر الإلكترونية الصناعية وإلكترونيات القوى','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(26,'أجهزة وقياسات إلكترونية',2,'مقرر القياسات الكهربائية وأجهزة الفحص المخبرية','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(27,'أنظمة التحكم والمتحكمات',2,'مقرر أنظمة التحكم الآلي وبرمجة المتحكمات الدقيقة','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(28,'مشروع التخرج الإلكتروني',3,'مشروع التخرج التطبيقي في الأنظمة الإلكترونية والأتمتة','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(29,'برمجة بايثون للذكاء الاصطناعي',3,'مقرر البرمجة المتقدمة بلغة Python ومعالجة البيانات','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(30,'جبر خطي وإحصاء احتمالي',2,'مقرر الجبر الخطي ونظرية الاحتمالات لنظم الذكاء الاصطناعي','intermediate',3,1,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(31,'تعلم الآلة والشبكات العصبونية',2,'مقرر خوارزميات تعلم الآلة ونماذج الشبكات العصبية الاصطناعية','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27'),(32,'مشروع التخرج في الذكاء الاصطناعي',3,'مشروع التخرج العملي في حلول وتطبيقات الذكاء الاصطناعي','intermediate',3,2,2,'2026-09-18 20:30:27','2026-09-18 20:30:27');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `department_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `offline_sync_policy` varchar(255) NOT NULL DEFAULT 'anytime',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'هندسي','يهدف إلى إعداد مهندسي المستقبل عبر تقديم برامج تعليمية متميزة تجمع بين المعرفة النظرية والتطبيق العملي. نلتزم بابتكار حلول هندسية لمواكبة التطور التكنولوجي، ودعم البحث العلمي، وتطوير مهارات الطلاب بما يتماشى مع متطلبات سوق العمل المحلي والعالمي.','anytime','2026-09-09 15:41:15','2026-09-09 15:41:15'),(2,'نظم المعلومات الحاسوبية','يهدف القسم إلى إعداد كفاءات مؤهلة تجمع بين مهارات تطوير البرمجيات، تحليل البيانات، وإدارة الأنظمة الذكية، لتمكين المؤسسات من اتخاذ قرارات استراتيجية مبنية على المعرفة والابتكار الرقمي.','anytime','2026-09-09 15:42:32','2026-09-09 15:42:32'),(3,'طبي','يسعى القسم الطبي إلى تقديم تعليم أكاديمي متميز يجمع بين المعرفة النظرية والتدريب السريري المتقدم. نهدف إلى إعداد جيل من الأطباء والممارسين الصحيين القادرين على تلبية احتياجات المجتمع، والمساهمة الفعالة في البحث العلمي وتطوير منظومة الرعاية الصحية','anytime','2026-09-09 15:47:09','2026-09-09 15:47:09'),(4,'تجاري','نهدف في القسم التجاري إلى إعداد قادة المستقبل وتزويدهم بالمعارف والمهارات الإدارية، المحاسبية، والتسويقية اللازمة للمنافسة في سوق العمل العالمي.\"','anytime','2026-09-09 15:51:08','2026-09-09 15:51:08');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `enrollment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`enrollment_id`),
  KEY `enrollments_student_id_index` (`student_id`),
  KEY `enrollments_course_id_index` (`course_id`),
  CONSTRAINT `enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=321 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (29,31,1,'2026-06-07','active','2026-06-08 04:04:46','2026-06-08 04:04:46'),(34,31,6,'2026-06-07','active','2026-06-08 04:04:46','2026-06-08 04:04:46'),(35,31,7,'2026-06-07','active','2026-06-08 04:04:46','2026-06-08 04:04:46'),(189,31,73,'2026-08-18','active','2026-08-18 14:43:30','2026-08-18 14:43:30'),(200,30,2,'2026-09-11','active','2026-09-10 02:52:39','2026-09-12 00:00:43'),(201,30,3,'2026-09-11','active','2026-09-10 02:52:39','2026-09-12 00:00:43'),(204,32,6,'2026-09-09','active','2026-09-10 03:23:56','2026-09-10 03:43:57'),(205,32,7,'2026-09-09','active','2026-09-10 03:23:56','2026-09-10 03:43:57'),(208,33,12,'2026-09-09','active','2026-09-10 04:16:26','2026-09-10 04:16:26'),(209,33,7,'2026-09-09','active','2026-09-10 04:16:26','2026-09-10 04:16:26'),(210,34,4,'2026-09-13','active','2026-09-10 04:37:32','2026-09-13 04:17:07'),(211,34,5,'2026-09-13','active','2026-09-10 04:37:32','2026-09-13 04:17:07'),(212,35,8,'2026-09-09','active','2026-09-10 04:40:03','2026-09-10 04:40:03'),(213,35,9,'2026-09-09','active','2026-09-10 04:40:03','2026-09-10 04:40:03'),(214,36,10,'2026-09-09','active','2026-09-10 04:42:38','2026-09-10 04:42:38'),(215,36,11,'2026-09-09','active','2026-09-10 04:42:38','2026-09-10 04:42:38'),(216,37,13,'2026-09-09','active','2026-09-10 04:52:04','2026-09-10 04:52:04'),(217,37,14,'2026-09-09','active','2026-09-10 04:52:04','2026-09-10 04:52:04'),(227,42,2,'2026-09-10','active','2026-09-11 02:06:26','2026-09-11 02:06:26'),(228,42,3,'2026-09-10','active','2026-09-11 02:06:26','2026-09-11 02:06:26'),(229,43,4,'2026-09-10','active','2026-09-11 02:07:42','2026-09-11 02:07:42'),(230,43,5,'2026-09-10','active','2026-09-11 02:07:42','2026-09-11 02:07:42'),(231,44,6,'2026-09-10','active','2026-09-11 02:15:28','2026-09-11 02:15:28'),(232,44,7,'2026-09-10','active','2026-09-11 02:15:28','2026-09-11 02:15:28'),(233,45,8,'2026-09-10','active','2026-09-11 02:16:39','2026-09-11 02:16:39'),(234,45,9,'2026-09-10','active','2026-09-11 02:16:39','2026-09-11 02:16:39'),(235,46,6,'2026-09-10','active','2026-09-11 02:28:37','2026-09-11 02:28:37'),(236,46,7,'2026-09-10','active','2026-09-11 02:28:37','2026-09-11 02:28:37'),(237,47,10,'2026-09-10','active','2026-09-11 02:30:26','2026-09-11 02:30:26'),(238,47,11,'2026-09-10','active','2026-09-11 02:30:26','2026-09-11 02:30:26'),(239,48,12,'2026-09-10','active','2026-09-11 02:33:23','2026-09-11 02:33:23'),(240,48,7,'2026-09-10','active','2026-09-11 02:33:23','2026-09-11 02:33:23'),(241,49,13,'2026-09-10','active','2026-09-11 02:37:28','2026-09-11 02:37:28'),(242,49,14,'2026-09-10','active','2026-09-11 02:37:28','2026-09-11 02:37:28'),(257,34,2,'2026-09-13','active',NULL,'2026-09-13 04:17:07'),(258,34,3,'2026-09-13','active',NULL,'2026-09-13 04:17:07'),(259,30,17,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(260,30,18,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(261,31,21,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(262,31,22,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(263,32,25,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(264,32,26,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(265,33,29,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(266,33,30,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(267,34,17,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(268,34,18,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(269,34,19,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(270,34,20,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(271,35,6,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(272,35,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(273,35,21,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(274,35,22,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(275,35,23,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(276,35,24,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(277,36,6,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(278,36,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(279,36,25,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(280,36,26,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(281,36,27,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(282,36,28,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(283,37,12,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(284,37,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(285,37,29,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(286,37,30,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(287,37,31,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(288,37,32,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(289,42,17,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(290,42,18,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(291,43,2,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(292,43,3,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(293,43,17,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(294,43,18,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(295,43,19,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(296,43,20,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(297,44,21,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(298,44,22,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(299,45,6,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(300,45,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(301,45,21,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(302,45,22,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(303,45,23,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(304,45,24,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(305,46,25,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(306,46,26,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(307,47,6,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(308,47,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(309,47,25,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(310,47,26,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(311,47,27,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(312,47,28,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(313,48,29,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(314,48,30,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(315,49,12,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(316,49,7,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(317,49,29,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(318,49,30,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(319,49,31,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27'),(320,49,32,'2026-09-01','active','2026-09-18 20:30:27','2026-09-18 20:30:27');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `exam_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `exam_date` datetime NOT NULL,
  `room` varchar(255) DEFAULT NULL,
  `class_group` varchar(255) DEFAULT NULL,
  `max_score` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`exam_id`),
  KEY `exams_course_id_foreign` (`course_id`),
  CONSTRAINT `exams_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (10,2,'الامتحان النهائي - خوارزميات','2026-12-20 09:00:00','قاعة معلوماتية - سنة أولى','معلوماتية - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(11,3,'الامتحان النهائي - رياضيات حاسوبية','2026-12-20 11:30:00','قاعة معلوماتية - سنة أولى','معلوماتية - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(12,4,'الامتحان النهائي - laravel','2026-12-20 14:00:00','قاعة معلوماتية - سنة ثانية','معلوماتية - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(13,5,'الامتحان النهائي - Flutter','2026-12-21 09:00:00','قاعة معلوماتية - سنة ثانية','معلوماتية - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(14,6,'الامتحان النهائي - c++','2026-12-21 11:30:00','قاعة اتصالات - سنة أولى','اتصالات - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(15,7,'الامتحان النهائي - شبكات','2026-12-21 14:00:00','قاعة اتصالات - سنة أولى','اتصالات - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(16,8,'الامتحان النهائي - اتصالات خليوية','2026-12-22 09:00:00','قاعة اتصالات - سنة ثانية','اتصالات - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(17,9,'الامتحان النهائي - مايكروية','2026-12-22 11:30:00','قاعة اتصالات - سنة ثانية','اتصالات - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(18,6,'الامتحان النهائي - c++','2026-12-22 14:00:00','قاعة الكترون - سنة أولى','الكترون - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(19,7,'الامتحان النهائي - شبكات','2026-12-23 09:00:00','قاعة الكترون - سنة أولى','الكترون - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(20,10,'الامتحان النهائي - طاقة شمسية','2026-12-23 11:30:00','قاعة الكترون - سنة ثانية','الكترون - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(21,11,'الامتحان النهائي - معالجات','2026-12-23 14:00:00','قاعة الكترون - سنة ثانية','الكترون - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(22,7,'الامتحان النهائي - شبكات','2026-12-24 09:00:00','قاعة ذكاء اصطناعي - سنة أولى','ذكاء اصطناعي - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(23,12,'الامتحان النهائي - c#','2026-12-24 11:30:00','قاعة ذكاء اصطناعي - سنة أولى','ذكاء اصطناعي - سنة أولى',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(24,13,'الامتحان النهائي - تصميم العاب','2026-12-24 14:00:00','قاعة ذكاء اصطناعي - سنة ثانية','ذكاء اصطناعي - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57'),(25,14,'الامتحان النهائي - رؤية حاسوبية','2026-12-27 09:00:00','قاعة ذكاء اصطناعي - سنة ثانية','ذكاء اصطناعي - سنة ثانية',100,'2026-09-11 17:54:57','2026-09-11 17:54:57');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_entries`
--

DROP TABLE IF EXISTS `grade_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grade_event_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_entries_grade_event_id_student_id_unique` (`grade_event_id`,`student_id`),
  KEY `grade_entries_grade_event_id_index` (`grade_event_id`),
  KEY `grade_entries_student_id_index` (`student_id`),
  CONSTRAINT `grade_entries_grade_event_id_foreign` FOREIGN KEY (`grade_event_id`) REFERENCES `grade_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grade_entries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_entries`
--

LOCK TABLES `grade_entries` WRITE;
/*!40000 ALTER TABLE `grade_entries` DISABLE KEYS */;
INSERT INTO `grade_entries` VALUES (65,33,30,21.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(66,34,30,21.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(67,35,30,46.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(68,33,42,19.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(69,34,42,24.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(70,35,42,26.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(71,36,30,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(72,37,30,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(73,38,30,34.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(74,36,42,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(75,37,42,11.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(76,38,42,7.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(77,39,34,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(78,40,34,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(79,41,34,24.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(80,39,43,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(81,40,43,16.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(82,41,43,46.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(83,42,34,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(84,43,34,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(85,44,34,46.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(86,42,43,13.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(87,43,43,14.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(88,44,43,15.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(89,45,31,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(90,46,31,21.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(91,47,31,30.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(92,45,44,22.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(93,46,44,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(94,47,44,33.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(95,48,31,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(96,49,31,21.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(97,50,31,39.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(98,48,44,15.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(99,49,44,9.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(100,50,44,7.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(101,51,35,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(102,52,35,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(103,53,35,49.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(104,51,45,22.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(105,52,45,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(106,53,45,39.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(107,54,35,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(108,55,35,16.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(109,56,35,32.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(110,54,45,17.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(111,55,45,14.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(112,56,45,9.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(113,57,32,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(114,58,32,15.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(115,59,32,47.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(116,57,46,21.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(117,58,46,16.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(118,59,46,43.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(119,60,32,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(120,61,32,24.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(121,62,32,32.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(122,60,46,15.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(123,61,46,9.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(124,62,46,11.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(125,63,36,22.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(126,64,36,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(127,65,36,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(128,63,47,24.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(129,64,47,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(130,65,47,48.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(131,66,36,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(132,67,36,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(133,68,36,42.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(134,66,47,10.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(135,67,47,14.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(136,68,47,7.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(137,69,33,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(138,70,33,16.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(139,71,33,39.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(140,69,48,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(141,70,48,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(142,71,48,32.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(143,72,33,22.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(144,73,33,20.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(145,74,33,33.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(146,72,48,12.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(147,73,48,13.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(148,74,48,14.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(149,75,37,22.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(150,76,37,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(151,77,37,45.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(152,75,49,25.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(153,76,49,18.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(154,77,49,38.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(155,78,37,19.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(156,79,37,23.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(157,80,37,49.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(158,78,49,17.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(159,79,49,8.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(160,80,49,10.00,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59');
/*!40000 ALTER TABLE `grade_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_events`
--

DROP TABLE IF EXISTS `grade_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT NULL,
  `type` enum('exam','quiz','oral') NOT NULL,
  `title` varchar(255) NOT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `notes` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` varchar(255) DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grade_events_teacher_id_index` (`teacher_id`),
  KEY `grade_events_course_id_index` (`course_id`),
  KEY `grade_events_type_index` (`type`),
  CONSTRAINT `grade_events_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `grade_events_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_events`
--

LOCK TABLES `grade_events` WRITE;
/*!40000 ALTER TABLE `grade_events` DISABLE KEYS */;
INSERT INTO `grade_events` VALUES (33,24,2,1,1,'quiz','مذاكرة الفصل - خوارزميات',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(34,24,2,1,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - خوارزميات',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(35,24,2,1,1,'exam','الامتحان النهائي - خوارزميات',50.00,NULL,'2026-12-20',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(36,26,3,1,1,'quiz','مذاكرة الفصل - رياضيات حاسوبية',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(37,26,3,1,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - رياضيات حاسوبية',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(38,26,3,1,1,'exam','الامتحان النهائي - رياضيات حاسوبية',50.00,NULL,'2026-12-20',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(39,27,4,1,2,'quiz','مذاكرة الفصل - laravel',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(40,27,4,1,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - laravel',25.00,NULL,'2026-11-29',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(41,27,4,1,2,'exam','الامتحان النهائي - laravel',50.00,NULL,'2026-12-20',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(42,27,5,1,2,'quiz','مذاكرة الفصل - Flutter',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(43,27,5,1,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - Flutter',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(44,27,5,1,2,'exam','الامتحان النهائي - Flutter',50.00,NULL,'2026-12-21',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(45,26,6,2,1,'quiz','مذاكرة الفصل - c++',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(46,26,6,2,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - c++',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(47,26,6,2,1,'exam','الامتحان النهائي - c++',50.00,NULL,'2026-12-21',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(48,28,7,2,1,'quiz','مذاكرة الفصل - شبكات',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(49,28,7,2,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - شبكات',25.00,NULL,'2026-11-30',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(50,28,7,2,1,'exam','الامتحان النهائي - شبكات',50.00,NULL,'2026-12-21',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(51,29,8,2,2,'quiz','مذاكرة الفصل - اتصالات خليوية',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(52,29,8,2,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - اتصالات خليوية',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(53,29,8,2,2,'exam','الامتحان النهائي - اتصالات خليوية',50.00,NULL,'2026-12-22',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(54,29,9,2,2,'quiz','مذاكرة الفصل - مايكروية',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(55,29,9,2,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - مايكروية',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(56,29,9,2,2,'exam','الامتحان النهائي - مايكروية',50.00,NULL,'2026-12-22',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(57,30,6,3,1,'quiz','مذاكرة الفصل - c++',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(58,30,6,3,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - c++',25.00,NULL,'2026-12-01',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(59,30,6,3,1,'exam','الامتحان النهائي - c++',50.00,NULL,'2026-12-22',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(60,30,7,3,1,'quiz','مذاكرة الفصل - شبكات',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(61,30,7,3,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - شبكات',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(62,30,7,3,1,'exam','الامتحان النهائي - شبكات',50.00,NULL,'2026-12-23',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(63,31,10,3,2,'quiz','مذاكرة الفصل - طاقة شمسية',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(64,31,10,3,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - طاقة شمسية',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(65,31,10,3,2,'exam','الامتحان النهائي - طاقة شمسية',50.00,NULL,'2026-12-23',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(66,31,11,3,2,'quiz','مذاكرة الفصل - معالجات',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(67,31,11,3,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - معالجات',25.00,NULL,'2026-12-02',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(68,31,11,3,2,'exam','الامتحان النهائي - معالجات',50.00,NULL,'2026-12-23',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(69,28,7,4,1,'quiz','مذاكرة الفصل - شبكات',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(70,28,7,4,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - شبكات',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(71,28,7,4,1,'exam','الامتحان النهائي - شبكات',50.00,NULL,'2026-12-24',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(72,25,12,4,1,'quiz','مذاكرة الفصل - c#',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(73,25,12,4,1,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - c#',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(74,25,12,4,1,'exam','الامتحان النهائي - c#',50.00,NULL,'2026-12-24',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(75,25,13,4,2,'quiz','مذاكرة الفصل - تصميم العاب',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(76,25,13,4,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - تصميم العاب',25.00,NULL,'2026-12-03',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(77,25,13,4,2,'exam','الامتحان النهائي - تصميم العاب',50.00,NULL,'2026-12-24',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(78,24,14,4,2,'quiz','مذاكرة الفصل - رؤية حاسوبية',25.00,NULL,'2026-12-06',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(79,24,14,4,2,'oral','الشفهي (وظائف، تسميع، تقييم، سبر) - رؤية حاسوبية',25.00,NULL,'2026-12-06',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59'),(80,24,14,4,2,'exam','الامتحان النهائي - رؤية حاسوبية',50.00,NULL,'2026-12-27',NULL,NULL,'2026-09-11 18:49:59','2026-09-11 18:49:59');
/*!40000 ALTER TABLE `grade_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_report_requests`
--

DROP TABLE IF EXISTS `grade_report_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_report_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `boss_user_id` bigint(20) unsigned NOT NULL,
  `teacher_user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grade_report_requests_course_id_foreign` (`course_id`),
  CONSTRAINT `grade_report_requests_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_report_requests`
--

LOCK TABLES `grade_report_requests` WRITE;
/*!40000 ALTER TABLE `grade_report_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_report_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `grade_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `exam_id` bigint(20) unsigned NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`grade_id`),
  KEY `grades_student_id_foreign` (`student_id`),
  KEY `grades_exam_id_foreign` (`exam_id`),
  CONSTRAINT `grades_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  CONSTRAINT `grades_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_user`
--

DROP TABLE IF EXISTS `group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_user_group_id_foreign` (`group_id`),
  KEY `group_user_user_id_foreign` (`user_id`),
  CONSTRAINT `group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_user`
--

LOCK TABLES `group_user` WRITE;
/*!40000 ALTER TABLE `group_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `head_schedule_entries`
--

DROP TABLE IF EXISTS `head_schedule_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `head_schedule_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `head_schedule_entries`
--

LOCK TABLES `head_schedule_entries` WRITE;
/*!40000 ALTER TABLE `head_schedule_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `head_schedule_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `heads`
--

DROP TABLE IF EXISTS `heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heads` (
  `head_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`head_id`),
  KEY `heads_user_id_foreign` (`user_id`),
  KEY `heads_department_id_foreign` (`department_id`),
  CONSTRAINT `heads_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  CONSTRAINT `heads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `heads`
--

LOCK TABLES `heads` WRITE;
/*!40000 ALTER TABLE `heads` DISABLE KEYS */;
INSERT INTO `heads` VALUES (3,60,4,'2026-06-09 14:07:06','2026-09-10 05:51:27'),(5,63,1,'2026-06-09 14:39:13','2026-09-10 05:48:57'),(7,2,2,NULL,'2026-09-10 05:07:09'),(8,62,3,NULL,'2026-09-10 05:49:58');
/*!40000 ALTER TABLE `heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('full_day','hourly') NOT NULL,
  `leave_category` enum('hourly','daily') NOT NULL DEFAULT 'daily',
  `date` date NOT NULL,
  `reason` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending_parent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_student_id_foreign` (`student_id`),
  KEY `leave_requests_teacher_id_foreign` (`teacher_id`),
  KEY `leave_requests_status_index` (`status`),
  CONSTRAINT `leave_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lessons` (
  `lesson_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
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
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`lesson_id`),
  KEY `lessons_course_id_foreign` (`course_id`),
  KEY `lessons_teacher_id_foreign` (`teacher_id`),
  KEY `lessons_department_id_foreign` (`department_id`),
  CONSTRAINT `lessons_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `lessons_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  CONSTRAINT `lessons_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessons`
--

LOCK TABLES `lessons` WRITE;
/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES (3,2,'مقدمة في الجداول','SQL Basics',NULL,NULL,NULL,NULL,'pdf',NULL,NULL,'2026-09-06 17:30:56','2026-09-07 16:17:08',24,NULL),(119,74,'مقدمة في الجداول','SQL Basics',NULL,NULL,NULL,NULL,'pdf',NULL,NULL,'2026-08-18 14:43:30','2026-09-07 16:17:08',NULL,NULL),(143,2,'محاضرة خوارزميات','محاضرة نظامية في مادة خوارزميات','lectures/lecture_2.pdf','lecture_2.pdf','pdf',NULL,'lecture','587',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',24,NULL),(144,3,'محاضرة رياضيات حاسوبية','محاضرة نظامية في مادة رياضيات حاسوبية','lectures/lecture_3.pdf','lecture_3.pdf','pdf',NULL,'lecture','598',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',26,NULL),(145,4,'محاضرة laravel','محاضرة نظامية في مادة laravel','lectures/lecture_4.pdf','lecture_4.pdf','pdf',NULL,'lecture','576',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',27,NULL),(146,5,'محاضرة Flutter','محاضرة نظامية في مادة Flutter','lectures/lecture_5.pdf','lecture_5.pdf','pdf',NULL,'lecture','576',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',27,NULL),(147,6,'محاضرة c++','محاضرة نظامية في مادة c++','lectures/lecture_6.pdf','lecture_6.pdf','pdf',NULL,'lecture','572',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',26,NULL),(148,7,'محاضرة شبكات','محاضرة نظامية في مادة شبكات','lectures/lecture_7.pdf','lecture_7.pdf','pdf',NULL,'lecture','579',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',28,NULL),(149,8,'محاضرة اتصالات خليوية','محاضرة نظامية في مادة اتصالات خليوية','lectures/lecture_8.pdf','lecture_8.pdf','pdf',NULL,'lecture','596',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',29,NULL),(150,9,'محاضرة مايكروية','محاضرة نظامية في مادة مايكروية','lectures/lecture_9.pdf','lecture_9.pdf','pdf',NULL,'lecture','585',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',29,NULL),(151,10,'محاضرة طاقة شمسية','محاضرة نظامية في مادة طاقة شمسية','lectures/lecture_10.pdf','lecture_10.pdf','pdf',NULL,'lecture','588',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',31,NULL),(152,11,'محاضرة معالجات','محاضرة نظامية في مادة معالجات','lectures/lecture_11.pdf','lecture_11.pdf','pdf',NULL,'lecture','583',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',31,NULL),(153,12,'محاضرة c#','محاضرة نظامية في مادة c#','lectures/lecture_12.pdf','lecture_12.pdf','pdf',NULL,'lecture','571',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',25,NULL),(154,13,'محاضرة تصميم العاب','محاضرة نظامية في مادة تصميم العاب','lectures/lecture_13.pdf','lecture_13.pdf','pdf',NULL,'lecture','590',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',25,NULL),(155,14,'محاضرة رؤية حاسوبية','محاضرة نظامية في مادة رؤية حاسوبية','lectures/lecture_14.pdf','lecture_14.pdf','pdf',NULL,'lecture','592',NULL,'2026-09-11 06:34:16','2026-09-11 06:34:16',24,NULL),(156,3,'جلسة حضور - 2026-09-11 17:02',NULL,NULL,NULL,NULL,NULL,'session',NULL,NULL,'2026-09-12 00:02:55','2026-09-12 00:02:55',26,NULL);
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply_to_message_id` bigint(20) unsigned DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_delivered` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_for_sender` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_for_receiver` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_for_everyone` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `disappears_after` int(11) DEFAULT NULL,
  `is_forwarded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_conversation` (`sender_id`,`receiver_id`),
  KEY `idx_messages_unread` (`receiver_id`,`sender_id`,`is_read`),
  KEY `idx_messages_latest` (`sender_id`,`receiver_id`,`id`),
  KEY `messages_sender_id_index` (`sender_id`),
  KEY `messages_receiver_id_index` (`receiver_id`),
  KEY `messages_created_at_index` (`created_at`),
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (2,28,1,NULL,NULL,'السلام عليكم',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-08-13 22:52:03','2026-08-13 22:52:24'),(3,1,28,NULL,NULL,'وعليكم السلام',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-08-13 22:52:34','2026-08-13 22:53:05'),(4,1,28,NULL,NULL,'[Voice Note]',NULL,'http://127.0.0.1:8000/storage/chat_voice_notes/E7Ey7mDLqv37Jr7WVynL8fqXzXoMovtkv0oEhS6Y.webm',1,0,0,0,0,NULL,NULL,0,'2026-08-13 22:52:43','2026-08-13 22:53:05'),(5,28,60,NULL,NULL,'..',NULL,NULL,0,0,0,0,0,NULL,NULL,0,'2026-08-13 22:53:16','2026-08-13 22:53:16'),(6,28,1,NULL,NULL,'[Attachment]',NULL,'http://127.0.0.1:8000/storage/chat_attachments/dS6OpzPCzyKNXKRpPfle4DuzHYbJqqjhipB5fQRO.jpg',1,0,0,0,0,NULL,NULL,0,'2026-08-14 12:04:09','2026-08-14 12:06:34'),(7,1,28,NULL,NULL,'[Attachment]',NULL,'http://127.0.0.1:8000/storage/chat_attachments/oEJT2FZCWf6MBy3c7TcOfzTu2H9MzFlf20BeIBvj.pdf',1,0,0,0,0,NULL,NULL,0,'2026-08-14 12:06:53','2026-08-14 12:07:18'),(8,1,28,NULL,NULL,'مرحبا',NULL,NULL,0,0,0,0,0,NULL,NULL,0,'2026-08-18 15:02:16','2026-08-18 15:02:16'),(11,153,1,NULL,NULL,'مرحبا',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 11:10:00','2026-09-12 11:14:11'),(12,153,1,NULL,NULL,'[Voice Note|00:02]',NULL,'http://192.168.1.101:8001/storage/chat_attachments/chat_6aa54f44746f48.44565799.m4a',1,0,0,1,1,NULL,NULL,0,'2026-09-12 11:10:28','2026-09-12 11:16:09'),(13,153,1,NULL,NULL,'[Attachment]',NULL,'http://192.168.1.101:8001/storage/chat_attachments/chat_6aa54f4c9c37f4.65779249.pdf',1,0,0,0,0,NULL,NULL,0,'2026-09-12 11:10:36','2026-09-12 11:14:11'),(14,153,1,NULL,NULL,'استاذ احمد قلي ابعتلك',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 11:47:18','2026-09-12 11:47:55'),(15,1,153,NULL,NULL,'اهلا بنتي',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 11:48:12','2026-09-12 12:19:28'),(16,1,153,NULL,NULL,'شو الاخبار',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 12:19:07','2026-09-12 12:19:28'),(17,153,1,NULL,NULL,'ممكن سؤال',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 12:19:39','2026-09-12 12:20:42'),(18,1,153,NULL,NULL,'تفضلي ؟',NULL,NULL,1,0,0,0,0,NULL,NULL,0,'2026-09-12 12:20:51','2026-09-12 12:21:01'),(19,153,1,NULL,NULL,'[Attachment]',NULL,'http://127.0.0.1:8001/storage/chat_attachments/chat_6aa55fe9d1aa29.79184724.pdf',1,0,0,0,0,NULL,NULL,0,'2026-09-12 12:21:29','2026-09-12 12:21:46');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_26_144508_create_teachers_table',1),(5,'2026_03_26_144509_create_courses_table',1),(6,'2026_03_26_144511_create_courses_teachers_table',1),(7,'2026_03_26_144512_create_schedules_table',1),(8,'2026_03_26_144516_create_departments_tables',1),(9,'2026_03_26_144517_create_administrative_tables',1),(10,'2026_03_26_144519_create__student_table',1),(11,'2026_03_26_144520_create__enrollments_table',1),(12,'2026_03_26_160506_create__exams_table',1),(13,'2026_03_26_161020_create__grades_table',1),(14,'2026_03_26_162513_create_lessons_table',1),(15,'2026_03_26_162514_create_attendance_table',1),(16,'2026_03_26_162515_create_resources_table',1),(17,'2026_03_26_162837_create_announcements_table',1),(18,'2026_03_26_162849_create_assignments_table',1),(19,'2026_03_26_162902_create_assignment_submissions_table',1),(20,'2026_03_26_163452_create_reports_and_requests_tables',1),(21,'2026_03_26_163528_create_communication_tables',1),(22,'2026_03_26_170239_create_course_departments_pivot_table',1),(23,'2026_03_27_195456_create_personal_access_tokens_table',1),(24,'2026_03_27_203038_create_session_table',1),(25,'2026_03_28_105303_create_subjects_table',1),(26,'2026_03_28_195248_add_username_to_users_table',1),(27,'2026_04_03_225032_create_otps_table',1),(28,'2026_04_06_072056_create_notifications_table',1),(29,'2026_04_13_073654_create_messages_table',1),(30,'2026_04_16_074853_create_leave_requests_table',1),(31,'2026_04_16_080032_add_excuse_fields_to_attendance_table',1),(32,'2026_04_16_083430_create_attendance_sessions_table',1),(33,'2026_04_18_105343_add_report_type_to_performance_reports_table',1),(34,'2026_04_21_053852_create_messages_table',1),(35,'2026_04_22_000001_add_teacher_and_department_to_lessons_table',1),(36,'2026_04_23_072049_create_semesters_table',1),(37,'2026_04_23_072100_create_parent_students_table',1),(38,'2026_04_23_072109_create_roles_table',1),(39,'2026_04_23_072119_add_role_id_to_users_table',1),(40,'2026_04_23_072131_add_semester_id_to_courses_table',1),(41,'2026_04_23_072132_add_semester_id_to_enrollments_table',1),(42,'2026_04_23_072132_add_semester_id_to_exams_table',1),(43,'2026_04_23_072133_add_semester_id_to_assignments_table',1),(44,'2026_04_23_072135_add_semester_id_to_attendance_table',1),(45,'2026_04_26_182048_create_programs_table',1),(46,'2026_04_26_182053_create_course_program_table',1),(47,'2026_04_26_182059_update_courses_table_for_programs',1),(48,'2026_04_28_172404_cleanup_database_architecture',1),(49,'2026_05_01_151740_add_targeting_columns_to_announcements_table',1),(50,'2026_05_01_160007_add_avatar_to_users_table',1),(51,'2026_05_02_171000_update_hod_tables_for_linking',1),(52,'2026_05_04_230000_add_columns_to_schedules_and_exams',1),(53,'2026_05_06_192227_add_category_and_image_to_announcements_table',1),(54,'2026_05_06_194210_add_report_type_to_performance_reports_table',1),(55,'2026_05_06_194251_2026_05_02_171000_update_hod_tables_for_linking',1),(56,'2026_05_06_194327_add_columns_to_schedules_and_exams',1),(57,'2026_05_07_000001_add_missing_columns_to_lessons_table',1),(58,'2026_05_07_102059_add_category_and_sender_to_notifications_table',1),(59,'2026_05_08_000001_add_attachment_to_assignments_table',1),(60,'2026_05_08_000002_create_otp_codes_table',1),(61,'2026_05_08_000003_add_missing_columns_to_users_table',1),(62,'2026_05_09_131236_update_announcements_table_add_audience',1),(63,'2026_05_09_150627_add_image_path_to_announcements_table',1),(64,'2026_05_19_035741_create_head_schedule_entries_table',1),(65,'2026_05_19_035837_create_head_schedule_entries_table',1),(66,'2026_05_19_073023_add_device_token_to_users_table',1),(67,'2026_05_20_070311_add_file_path_to_assignments_table',1),(68,'2026_05_20_080838_add_file_fields_to_lessons_table',1),(69,'2026_05_21_000001_add_year_to_courses_table',1),(70,'2026_05_21_105015_add_notes_to_assignment_submissions_table',1),(71,'2026_05_23_add_course_year_to_report_requests',1),(72,'2026_05_23_add_related_id_to_notifications_table',1),(73,'2026_05_24_211054_add_link_url_to_announcements_table',1),(74,'2026_05_25_075706_create_calendar_events_table',1),(75,'2026_05_27_000000_add_hours_to_courses_table',1),(76,'2026_05_27_022103_add_telegram_chat_id_to_users_table',1),(77,'2026_05_29_134459_create_university_ids_table',1),(78,'2026_05_29_134756_add_university_id_to_users_table',1),(79,'2026_05_29_194317_add_reply_to_to_messages_table',1),(80,'2026_05_29_194532_create_groups_table',1),(81,'2026_05_29_194533_create_group_user_table',1),(82,'2026_05_29_194536_add_group_id_to_messages_table',1),(83,'2026_05_30_000001_add_device_lock_to_students_table',1),(84,'2026_05_30_000002_add_location_to_attendance_sessions_table',1),(85,'2026_05_30_000003_add_verification_fields_to_attendance_table',1),(86,'2026_05_30_100000_add_device_fields_to_students_table',1),(87,'2026_05_30_200000_add_device_fields_to_students_table',1),(88,'2026_05_31_004752_add_program_id_to_students_table',1),(89,'2026_06_03_161516_add_first_last_name_to_users_table',1),(90,'2026_06_08_130329_add_report_request_id_to_performance_reports_table',1),(91,'2026_06_09_074015_add_telegram_chat_id_to_users_table',1),(92,'2026_06_09_082812_add_advisor_fields_to_teachers_table',1),(93,'2026_06_09_095308_add_telegram_chat_id_to_university_ids_table',1),(94,'2026_06_09_230000_add_closed_at_to_attendance_sessions_table',1),(95,'2026_06_11_165538_add_sent_to_parent_to_report_requests_table',1),(96,'2026_06_17_000001_add_face_fields_for_attendance',1),(97,'2026_06_17_000002_fix_reject_reason_enum',1),(98,'2026_06_17_000003_fix_face_image_column_type',1),(99,'2026_06_19_000001_create_grade_events_table',1),(100,'2026_06_19_000002_create_grade_report_requests_table',1),(101,'2026_06_21_192810_add_oral_to_grade_events',1),(102,'2026_06_28_072234_add_offline_sync_policy_to_departments_table',1),(103,'2026_06_28_100000_add_photo_to_university_ids_table',1),(104,'2026_06_29_020000_add_extra_fields_to_university_ids_table',1),(105,'2026_06_29_023000_add_reference_photo_to_students_table',1),(106,'2026_07_03_000001_create_photo_change_requests_table',1),(107,'2026_07_04_000001_add_indexes_to_messages_table',1),(108,'2026_07_04_100000_create_quizzes_table',1),(109,'2026_07_05_152247_add_time_and_duration_to_grade_events_table',1),(110,'2026_07_15_083820_create_student_requests_table',1),(111,'2026_07_21_000001_add_telegram_id_to_users_and_parents_table',1),(112,'2026_07_21_132439_create_admin_generated_reports_table',1),(113,'2026_07_23_182956_add_is_delivered_to_messages_table',1),(114,'2026_08_02_000001_add_solution_text_to_assignment_submissions_table',1),(115,'2026_08_02_000002_add_notifications_muted_to_users_table',1),(116,'2026_08_02_000003_create_parent_meetings_and_summons_table',1),(117,'2026_08_02_080123_add_event_details_to_announcements_table',1),(118,'2026_08_03_195110_modify_category_column_in_announcements_table',1),(119,'2026_08_04_102340_add_notes_to_assignments_table',1),(120,'2026_08_05_000000_make_department_id_nullable_in_programs_table',1),(121,'2026_08_05_085500_make_teacher_id_nullable_in_report_requests_table',1),(122,'2026_08_05_230000_add_target_role_to_parent_meeting_requests_table',1),(123,'2026_08_12_074541_create_affairs_dashboard_stats_view',1),(124,'2026_08_12_101800_create_teacher_dashboard_stats_view',1),(125,'2026_08_12_221000_add_performance_indexes_to_tables',1),(126,'2026_08_12_222000_create_user_activities_table',1),(127,'2026_08_14_000001_add_deletion_flags_to_messages_table',1),(128,'2026_08_14_000002_add_ephemeral_and_forwarding_to_messages_table',1),(129,'2026_08_14_190000_update_status_column_in_absence_requests',1),(130,'2026_08_18_121500_create_admin_profile_stats_view',1),(131,'2026_08_18_123000_create_system_settings_table',1),(132,'2026_08_19_052012_add_department_id_to_calendar_events_table',1),(133,'2026_08_25_100000_add_missing_indexes_for_production',1),(134,'2026_09_01_000001_create_student_warnings_table',1),(135,'2026_09_07_200000_add_weight_to_courses_table',2),(136,'2026_09_08_030000_assign_university_id_to_huda_shbli',3),(137,'2026_09_08_031000_reset_huda_shbli_password',4),(138,'2026_09_01_111345_add_images_json_to_announcements_table',5),(139,'2026_09_09_220000_add_chat_category_to_notifications_table',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `category` enum('academic','administrative','chat') DEFAULT 'administrative',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_sender_id_foreign` (`sender_id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_is_read_index` (`is_read`),
  KEY `notifications_type_index` (`type`),
  CONSTRAINT `notifications_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=788 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,2,NULL,'طلب إجازة جديد','هناك طلب إجازة معلق مقدم من المدرس سامر المحمد بانتظار موافقتك.','leave_request',NULL,'administrative',1,'2026-09-06 17:30:56','2026-09-17 06:32:18'),(259,28,NULL,'طلب تسجيل جديد','قدّم محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 10:55:46','2026-08-13 14:19:04'),(260,28,28,'تم تفعيل حسابك ✓','مرحباً محمود! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',1,'2026-05-31 10:56:00','2026-08-13 14:19:04'),(261,28,NULL,'طلب تسجيل جديد','قدّم محمد غنام طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 10:58:18','2026-08-13 14:19:04'),(262,47,28,'تم تفعيل حسابك ✓','مرحباً محمد غنام! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-05-31 10:58:27','2026-05-31 10:58:27'),(264,28,NULL,'تمت الموافقة على طلب الإجازة','وافق رئيس القسم على طلب إجازتك بتاريخ 2026-05-31','leave_request',25,'administrative',1,'2026-05-31 11:01:10','2026-08-13 14:19:04'),(265,47,NULL,'طلب إجازة يحتاج موافقتك','قدّم محمود طلب إجازة بتاريخ 2026-05-31، يرجى مراجعة الطلب والرد عليه','leave_request',26,'administrative',0,'2026-05-31 11:05:08','2026-05-31 11:05:08'),(266,28,NULL,'طلب تسجيل جديد','قدّم هبة طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 11:28:28','2026-08-13 14:19:04'),(267,48,28,'تم تفعيل حسابك ✓','مرحباً هبة! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',1,'2026-05-31 11:28:32','2026-05-31 11:28:55'),(268,28,NULL,'طلب تسجيل جديد','قدّم ناصر طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 11:31:01','2026-08-13 14:19:04'),(269,49,28,'تم تفعيل حسابك ✓','مرحباً ناصر! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-05-31 11:31:13','2026-05-31 11:31:13'),(270,28,NULL,'طلب تسجيل جديد','قدّم ناصر طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 11:33:34','2026-08-13 14:19:04'),(271,50,28,'تم تفعيل حسابك ✓','مرحباً ناصر! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-05-31 11:33:48','2026-05-31 11:33:48'),(272,36,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(273,37,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(274,38,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(275,39,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(276,40,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(277,41,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(278,42,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(279,43,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(281,45,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',0,'2026-05-31 11:37:46','2026-05-31 11:37:46'),(282,28,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',1,'2026-05-31 11:37:46','2026-08-13 14:19:04'),(283,48,1,'إعلان جديد من الإدارة','اعلنت الانروا عن عطلة رسمية بمناسبة عيد الاضحى','announcement',16,'administrative',1,'2026-05-31 11:37:46','2026-05-31 11:39:03'),(284,48,NULL,'محاضرة جديدة — C#','رفع المعلم خالد اسماعيل محاضرة جديدة: اساسيات','lecture',51,'academic',1,'2026-05-31 11:50:00','2026-05-31 12:02:46'),(285,48,NULL,'محاضرة جديدة — C#','رفع المعلم خالد اسماعيل محاضرة جديدة: ..','lecture',54,'academic',1,'2026-05-31 11:59:34','2026-05-31 12:02:43'),(286,28,NULL,'طلب تسجيل جديد','قدّم ادم محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 13:18:08','2026-08-13 14:19:04'),(287,28,28,'تم تفعيل حسابك ✓','مرحباً ادم محمود! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',1,'2026-05-31 13:18:37','2026-08-13 14:19:04'),(288,37,NULL,'تسليم واجب جديد','سلّم الطالب ادم محمود الواجب: حل المسائل','assignment',14,'academic',0,'2026-05-31 13:28:30','2026-05-31 13:28:30'),(289,28,NULL,'طلب تسجيل جديد','قدّم ابو ادم طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 13:34:21','2026-08-13 14:19:04'),(294,28,NULL,'طلب تسجيل جديد','قدّم ابو ادم طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-05-31 13:36:59','2026-08-13 14:19:04'),(295,53,28,'تم تفعيل حسابك ✓','مرحباً ابو ادم! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-05-31 13:37:05','2026-05-31 13:37:05'),(297,37,NULL,'طلب تقرير سلوكي','طلب ولي أمر الطالب ادم محمود تقريراً سلوكياً، يُرجى المراجعة.','report',NULL,'administrative',0,'2026-05-31 13:47:12','2026-05-31 13:47:12'),(298,28,NULL,'تمت الموافقة على طلب الإجازة','وافق رئيس القسم على طلب إجازتك بتاريخ 2026-05-31','leave_request',27,'administrative',1,'2026-05-31 13:52:02','2026-08-13 14:19:04'),(299,36,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(300,37,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',1,'2026-06-08 02:56:46','2026-06-08 03:54:25'),(301,38,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(302,39,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(303,40,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(304,41,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(305,42,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(306,43,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(308,45,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(309,28,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',1,'2026-06-08 02:56:46','2026-08-13 14:19:04'),(310,48,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',0,'2026-06-08 02:56:46','2026-06-08 02:56:46'),(311,28,NULL,'إعلان جديد من رئيس القسم','اعلان جديد','announcement',17,'administrative',1,'2026-06-08 02:56:46','2026-08-13 14:19:04'),(312,28,NULL,'طلب تسجيل جديد','قدّم هادي حسن طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 03:57:13','2026-08-13 14:19:04'),(313,54,28,'تم تفعيل حسابك ✓','مرحباً هادي حسن! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 03:57:38','2026-06-08 03:57:38'),(314,28,NULL,'طلب تسجيل جديد','قدّم زكي حسن طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 03:58:52','2026-08-13 14:19:04'),(315,55,28,'تم تفعيل حسابك ✓','مرحباً زكي حسن! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 03:59:17','2026-06-08 03:59:17'),(316,28,NULL,'طلب تسجيل جديد','قدّم حسين دياب طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 04:04:46','2026-08-13 14:19:04'),(317,56,28,'تم تفعيل حسابك ✓','مرحباً حسين دياب! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 04:04:53','2026-06-08 04:04:53'),(318,28,NULL,'طلب تسجيل جديد','قدّم علي دياب طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 04:05:26','2026-08-13 14:19:04'),(319,57,28,'تم تفعيل حسابك ✓','مرحباً علي دياب! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 04:05:32','2026-06-08 04:05:32'),(320,28,NULL,'طلب تسجيل جديد','قدّم فارس فارس طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 04:35:46','2026-08-13 14:19:04'),(321,28,NULL,'طلب تسجيل جديد','قدّم ابو فارس طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-06-08 04:36:53','2026-08-13 14:19:04'),(322,59,28,'تم تفعيل حسابك ✓','مرحباً ابو فارس! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 04:37:01','2026-06-08 04:37:01'),(323,58,28,'تم تفعيل حسابك ✓','مرحباً فارس فارس! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',0,'2026-06-08 04:37:03','2026-06-08 04:37:03'),(384,28,NULL,'طلب تسجيل جديد','قدّم مجدولين محمود طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-07-04 01:56:09','2026-08-13 14:19:04'),(728,28,NULL,'طلب تسجيل جديد','قدّم هدى شبلي طلب انضمام كـطالب. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-07-05 19:23:06','2026-08-13 14:19:04'),(729,147,28,'تم تفعيل حسابك ✓','مرحباً هدى شبلي! تم مراجعة طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول.','administrative',NULL,'administrative',1,'2026-07-05 19:23:21','2026-07-05 19:23:50'),(730,28,NULL,'طلب تسجيل جديد','قدّم ثناء شبلي طلب انضمام كـولي أمر. يرجى مراجعة الطلب والموافقة أو الرفض.','administrative',NULL,'administrative',1,'2026-07-05 19:29:12','2026-08-13 14:19:04'),(770,1,153,'جودي محمد','مرحبا','message',NULL,'chat',0,'2026-09-12 11:10:02','2026-09-12 11:10:02'),(771,1,153,'جودي محمد','[Voice Note|00:02]','message',NULL,'chat',0,'2026-09-12 11:10:30','2026-09-12 11:10:30'),(772,1,153,'جودي محمد','[Attachment]','message',NULL,'chat',0,'2026-09-12 11:10:38','2026-09-12 11:10:38'),(773,1,153,'جودي محمد','استاذ احمد قلي ابعتلك','message',NULL,'chat',0,'2026-09-12 11:47:19','2026-09-12 11:47:19'),(774,153,1,'إدارة المعهد التقني','اهلا بنتي','message',NULL,'chat',1,'2026-09-12 11:48:17','2026-09-12 12:16:47'),(775,153,NULL,'رسالة جديدة','لقد تلقيت رسالة جديدة من إدارة المعهد التقني','message',NULL,'administrative',1,'2026-09-12 12:19:09','2026-09-12 12:19:22'),(776,1,153,'جودي محمد','ممكن سؤال','message',NULL,'chat',1,'2026-09-12 12:19:40','2026-09-12 12:19:40'),(777,153,NULL,'رسالة جديدة','لقد تلقيت رسالة جديدة من إدارة المعهد التقني','message',NULL,'administrative',1,'2026-09-12 12:20:53','2026-09-12 12:24:59'),(778,1,153,'جودي محمد','[Attachment]','message',NULL,'chat',0,'2026-09-12 12:21:31','2026-09-12 12:21:31'),(779,151,NULL,'واجب جديد: مشروع التخرج 📚','تمت إضافة واجب جديد لمادة مشروع التخرج، يرجى تسليمه قبل نهاية الأسبوع.','assignment',NULL,'academic',1,'2026-09-17 06:05:08','2026-09-17 06:08:58'),(780,151,NULL,'إعلان: بدء فترة التسجيل والتثبيت 🏢','تعلن إدارة شؤون الطلاب عن بدء فترة تثبيت التسجيل للفصل الدراسي الجديد.','announcement',NULL,'administrative',1,'2026-09-17 04:05:08','2026-09-17 06:09:00'),(781,185,NULL,'طلب تقرير سلوكي','طُلب منك تقرير سلوكي عن الطالب يوسف الاحمد','report',NULL,'administrative',1,'2026-09-17 06:32:50','2026-09-17 06:34:24'),(782,2,NULL,'تم إرسال تقييم الطالب','قدّم المعلم تقييماً للطالب يوسف الاحمد','report',NULL,'academic',1,'2026-09-17 06:34:56','2026-09-17 06:35:32'),(783,154,NULL,'تقرير الطالب جاهز','أرسل المعلم التقرير السلوكي للطالب يوسف الاحمد، يرجى الاطلاع عليه.','report',NULL,'administrative',1,'2026-09-17 06:34:56','2026-09-17 06:34:56'),(784,154,NULL,'تقرير أداء للطالب يوسف الاحمد','الطالب لا بأس به ملتزم وجيد ومتعاون','report',1,'administrative',1,'2026-09-17 06:35:51','2026-09-17 06:35:51'),(785,151,NULL,'مبروك! تم الترفيع إلى الفصل الثاني 📚','قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الثاني بعد استيفاء مقررات الفصل الأول. ملاحظة: اختبار','academic',NULL,'academic',0,'2026-09-19 17:55:01','2026-09-19 17:55:01'),(786,160,NULL,'مبروك! تم الترفيع إلى الفصل الرابع 📚✨','قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الرابع (فصل التخرج النهائي) بعد استيفاء مقررات الفصل الثالث. ملاحظة: اختبار','academic',NULL,'academic',0,'2026-09-19 17:55:29','2026-09-19 17:55:29'),(787,151,NULL,'مبروك! تم الترفيع إلى الفصل الثاني 📚','قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الثاني بعد استيفاء مقررات الفصل الأول.','academic',NULL,'academic',0,'2026-09-20 03:33:51','2026-09-20 03:33:51');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otp_codes_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otps_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otps`
--

LOCK TABLES `otps` WRITE;
/*!40000 ALTER TABLE `otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_meeting_requests`
--

DROP TABLE IF EXISTS `parent_meeting_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_meeting_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_user_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `target_role` varchar(255) DEFAULT 'affairs',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `target_user_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_meeting_requests_parent_user_id_foreign` (`parent_user_id`),
  KEY `parent_meeting_requests_student_id_foreign` (`student_id`),
  CONSTRAINT `parent_meeting_requests_parent_user_id_foreign` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `parent_meeting_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_meeting_requests`
--

LOCK TABLES `parent_meeting_requests` WRITE;
/*!40000 ALTER TABLE `parent_meeting_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_meeting_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_students`
--

DROP TABLE IF EXISTS `parent_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `relationship` enum('father','mother','guardian') NOT NULL DEFAULT 'father',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_students_parent_id_student_id_unique` (`parent_id`,`student_id`),
  KEY `parent_students_student_id_foreign` (`student_id`),
  CONSTRAINT `parent_students_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `parent_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_students`
--

LOCK TABLES `parent_students` WRITE;
/*!40000 ALTER TABLE `parent_students` DISABLE KEYS */;
INSERT INTO `parent_students` VALUES (37,154,151,'guardian','2026-09-10 03:12:28','2026-09-10 03:12:28'),(38,155,153,'guardian','2026-09-10 03:20:04','2026-09-10 03:20:04'),(39,157,156,'guardian','2026-09-10 03:58:41','2026-09-10 03:58:41'),(40,159,158,'guardian','2026-09-10 04:19:10','2026-09-10 04:19:10'),(41,161,160,'guardian','2026-09-10 04:38:45','2026-09-10 04:38:45'),(42,163,162,'guardian','2026-09-10 04:41:17','2026-09-10 04:41:17'),(43,165,164,'guardian','2026-09-10 04:45:20','2026-09-10 04:45:20'),(44,167,166,'guardian','2026-09-10 04:53:19','2026-09-10 04:53:19'),(48,194,190,'guardian','2026-09-11 02:23:19','2026-09-11 02:23:19'),(49,195,191,'guardian','2026-09-11 02:24:32','2026-09-11 02:24:32'),(50,196,192,'guardian','2026-09-11 02:25:51','2026-09-11 02:25:51'),(51,197,193,'guardian','2026-09-11 02:27:06','2026-09-11 02:27:06'),(52,200,198,'guardian','2026-09-11 02:31:34','2026-09-11 02:31:34'),(53,200,199,'guardian','2026-09-11 02:31:34','2026-09-11 02:31:34'),(54,202,201,'guardian','2026-09-11 02:34:12','2026-09-11 02:34:12'),(56,203,204,'father','2026-09-11 23:42:10','2026-09-11 23:42:10'),(57,154,153,'father','2026-09-17 06:38:53','2026-09-17 06:38:53');
/*!40000 ALTER TABLE `parent_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_summons`
--

DROP TABLE IF EXISTS `parent_summons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_summons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_user_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `parent_user_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `reason_title` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `summon_date` date DEFAULT NULL,
  `status` enum('sent','acknowledged','attended','cancelled') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_summons_student_id_index` (`student_id`),
  KEY `parent_summons_parent_user_id_index` (`parent_user_id`),
  KEY `parent_summons_sender_user_id_index` (`sender_user_id`),
  KEY `parent_summons_status_index` (`status`),
  CONSTRAINT `parent_summons_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `parent_summons_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_summons`
--

LOCK TABLES `parent_summons` WRITE;
/*!40000 ALTER TABLE `parent_summons` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_summons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parents`
--

DROP TABLE IF EXISTS `parents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parents` (
  `parent_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `telegram_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`parent_id`),
  KEY `parents_user_id_foreign` (`user_id`),
  CONSTRAINT `parents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parents`
--

LOCK TABLES `parents` WRITE;
/*!40000 ALTER TABLE `parents` DISABLE KEYS */;
INSERT INTO `parents` VALUES (35,154,'2026-09-10 03:12:28','2026-09-10 03:12:28',NULL),(36,155,'2026-09-10 03:20:03','2026-09-10 03:20:03',NULL),(37,157,'2026-09-10 03:58:41','2026-09-10 03:58:41',NULL),(38,159,'2026-09-10 04:19:10','2026-09-10 04:19:10',NULL),(39,161,'2026-09-10 04:38:45','2026-09-10 04:38:45',NULL),(40,163,'2026-09-10 04:41:17','2026-09-10 04:41:17',NULL),(41,165,'2026-09-10 04:45:20','2026-09-10 04:45:20',NULL),(42,167,'2026-09-10 04:53:19','2026-09-10 04:53:19',NULL),(46,194,'2026-09-11 02:23:19','2026-09-11 02:23:19',NULL),(47,195,'2026-09-11 02:24:32','2026-09-11 02:24:32',NULL),(48,196,'2026-09-11 02:25:51','2026-09-11 02:25:51',NULL),(49,197,'2026-09-11 02:27:06','2026-09-11 02:27:06',NULL),(50,200,'2026-09-11 02:31:34','2026-09-11 02:31:34',NULL),(51,202,'2026-09-11 02:34:12','2026-09-11 02:34:12',NULL),(52,203,'2026-09-11 02:36:13','2026-09-11 23:42:19',NULL);
/*!40000 ALTER TABLE `parents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_reports`
--

DROP TABLE IF EXISTS `performance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_reports` (
  `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_request_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `report_type` enum('academic','behavioral') NOT NULL DEFAULT 'academic',
  `attendance_rate` decimal(5,2) NOT NULL,
  `average_grade` decimal(5,2) NOT NULL,
  `recommendations` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `performance_reports_student_id_foreign` (`student_id`),
  CONSTRAINT `performance_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reports`
--

LOCK TABLES `performance_reports` WRITE;
/*!40000 ALTER TABLE `performance_reports` DISABLE KEYS */;
INSERT INTO `performance_reports` VALUES (1,NULL,30,'behavioral',0.00,0.00,'الطالب لا بأس به ملتزم وجيد ومتعاون','2026-09-17 06:34:56','2026-09-17 06:34:56','2026-09-17 06:34:56');
/*!40000 ALTER TABLE `performance_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (2,'App\\Models\\User',151,'auth_token','3c13017fb68083e54c2c1556f7bd376dddb4687577a7ce7bd3db0a263e9b73b8','[\"*\"]','2026-09-12 01:05:35',NULL,'2026-09-12 00:10:20','2026-09-12 01:05:35'),(3,'App\\Models\\User',151,'test','4fb77625afe13c007ac816fe741fd0ceb23e93d2b92e024d290d177b3b7fc1d4','[\"*\"]','2026-09-12 00:16:51',NULL,'2026-09-12 00:16:39','2026-09-12 00:16:51'),(4,'App\\Models\\User',151,'test2','bbd63ced7f2bb893b919cdf51ba21898b4f5abbe03186ba69c30bc29e74bccb9','[\"*\"]','2026-09-12 00:49:50',NULL,'2026-09-12 00:49:49','2026-09-12 00:49:50'),(5,'App\\Models\\User',151,'verify','9403dac714eb6b5bd251ef5342d8a4ff4a896d83672787bcb896a860b031e8d9','[\"*\"]','2026-09-12 00:58:57',NULL,'2026-09-12 00:58:57','2026-09-12 00:58:57'),(6,'App\\Models\\User',153,'auth_token','062340e8c1988354a8469f3fb844470ca3a467ed7db3eeb312e5dae62db416b4','[\"*\"]',NULL,NULL,'2026-09-12 10:15:06','2026-09-12 10:15:06'),(11,'App\\Models\\User',153,'auth_token','2cbf20654d176003a9da60fcac34a233e3eb859ca0c31750c86d1a77a77f7fcf','[\"*\"]','2026-09-12 11:57:21',NULL,'2026-09-12 11:56:58','2026-09-12 11:57:21'),(12,'App\\Models\\User',153,'auth_token','e115f1a9d0dcf304e8d05f1dc536bd1fbba8a3cd9f4e894f0186341625ba2d70','[\"*\"]','2026-09-13 04:11:41',NULL,'2026-09-12 12:16:40','2026-09-13 04:11:41'),(21,'App\\Models\\User',168,'auth_token','125701dfc876d89d08977849e77976eefc6c60f04793637b20d399a392eda39e','[\"*\"]','2026-09-20 07:57:44',NULL,'2026-09-20 06:47:49','2026-09-20 07:57:44'),(22,'App\\Models\\User',151,'auth_token','a0a27a941c60c139608f242fe2aa3dec07664ada47aad586e03b5cff190b14d7','[\"*\"]','2026-09-20 08:05:07',NULL,'2026-09-20 08:05:06','2026-09-20 08:05:07');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `photo_change_requests`
--

DROP TABLE IF EXISTS `photo_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photo_change_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `old_photo` varchar(255) DEFAULT NULL,
  `new_photo` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `photo_change_requests_user_id_foreign` (`user_id`),
  CONSTRAINT `photo_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `photo_change_requests`
--

LOCK TABLES `photo_change_requests` WRITE;
/*!40000 ALTER TABLE `photo_change_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `photo_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `semester` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `programs_department_id_foreign` (`department_id`),
  CONSTRAINT `programs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'معلوماتية',NULL,NULL,NULL,2,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(2,'اتصالات',NULL,NULL,NULL,2,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(3,'الكترون',NULL,NULL,NULL,2,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(4,'ذكاء اصطناعي',NULL,NULL,NULL,2,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(5,'مخبري',NULL,NULL,NULL,3,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(6,'صيدلة',NULL,NULL,NULL,3,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(7,'هندسة مدني',NULL,NULL,NULL,1,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(8,'هندسة عمارة',NULL,NULL,NULL,1,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(9,'هندسة ديكور وتصميم داخلي',NULL,NULL,NULL,1,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(10,'إدارة اعمال',NULL,NULL,NULL,4,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(11,'محاسبة',NULL,NULL,NULL,4,'2026-09-09 18:03:48','2026-09-09 18:03:48'),(12,'مصارف و تأمين',NULL,NULL,NULL,4,'2026-09-09 18:03:48','2026-09-09 18:03:48');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_options`
--

DROP TABLE IF EXISTS `quiz_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) unsigned NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_options_question_id_foreign` (`question_id`),
  CONSTRAINT `quiz_options_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_options`
--

LOCK TABLES `quiz_options` WRITE;
/*!40000 ALTER TABLE `quiz_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `type` enum('mcq','text') NOT NULL DEFAULT 'mcq',
  `marks` smallint(5) unsigned NOT NULL DEFAULT 1,
  `order_num` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_questions_quiz_id_foreign` (`quiz_id`),
  CONSTRAINT `quiz_questions_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` smallint(5) unsigned NOT NULL DEFAULT 60,
  `total_marks` smallint(5) unsigned NOT NULL DEFAULT 100,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quizzes_teacher_id_foreign` (`teacher_id`),
  KEY `quizzes_course_id_foreign` (`course_id`),
  CONSTRAINT `quizzes_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `quizzes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_requests`
--

DROP TABLE IF EXISTS `report_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `head_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `year` tinyint(4) DEFAULT NULL,
  `report_type` enum('academic','behavioral') NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `sent_to_parent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_requests_head_id_foreign` (`head_id`),
  KEY `report_requests_teacher_id_foreign` (`teacher_id`),
  KEY `report_requests_student_id_foreign` (`student_id`),
  KEY `report_requests_course_id_foreign` (`course_id`),
  CONSTRAINT `report_requests_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL,
  CONSTRAINT `report_requests_head_id_foreign` FOREIGN KEY (`head_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `report_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `report_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_requests`
--

LOCK TABLES `report_requests` WRITE;
/*!40000 ALTER TABLE `report_requests` DISABLE KEYS */;
INSERT INTO `report_requests` VALUES (1,2,27,30,NULL,NULL,'behavioral','الطالب لا بأس به ملتزم وجيد ومتعاون','completed',1,'2026-09-17 06:32:50','2026-09-17 06:35:51');
/*!40000 ALTER TABLE `report_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resources` (
  `resource_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `resources_course_id_foreign` (`course_id`),
  CONSTRAINT `resources_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','2026-09-06 17:30:50','2026-09-06 17:30:50'),(2,'teacher','2026-09-06 17:30:50','2026-09-06 17:30:50'),(3,'student','2026-09-06 17:30:50','2026-09-06 17:30:50'),(4,'parent','2026-09-06 17:30:50','2026-09-06 17:30:50'),(5,'head','2026-09-06 17:30:50','2026-09-06 17:30:50'),(6,'affairs','2026-09-06 17:30:50','2026-09-06 17:30:50');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `schedule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `class_group` varchar(255) DEFAULT NULL,
  `day` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `schedules_teacher_id_foreign` (`teacher_id`),
  KEY `schedules_course_id_index` (`course_id`),
  KEY `schedules_day_index` (`day`),
  CONSTRAINT `schedules_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (53,2,182,'معلوماتية - سنة أولى','Sunday','08:00:00','09:30:00','قاعة معلوماتية - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(54,2,182,'معلوماتية - سنة أولى','Monday','08:00:00','09:30:00','قاعة معلوماتية - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(55,3,184,'معلوماتية - سنة أولى','Sunday','09:30:00','11:00:00','قاعة معلوماتية - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(56,3,184,'معلوماتية - سنة أولى','Monday','09:30:00','11:00:00','قاعة معلوماتية - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(57,4,185,'معلوماتية - سنة ثانية','Sunday','08:00:00','09:30:00','قاعة معلوماتية - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(58,4,185,'معلوماتية - سنة ثانية','Monday','08:00:00','09:30:00','قاعة معلوماتية - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(59,5,185,'معلوماتية - سنة ثانية','Sunday','09:30:00','11:00:00','قاعة معلوماتية - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(60,5,185,'معلوماتية - سنة ثانية','Monday','09:30:00','11:00:00','قاعة معلوماتية - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(61,6,184,'اتصالات - سنة أولى','Sunday','08:00:00','09:30:00','قاعة اتصالات - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(62,6,184,'اتصالات - سنة أولى','Monday','08:00:00','09:30:00','قاعة اتصالات - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(63,7,186,'اتصالات - سنة أولى','Sunday','09:30:00','11:00:00','قاعة اتصالات - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(64,7,186,'اتصالات - سنة أولى','Monday','09:30:00','11:00:00','قاعة اتصالات - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(65,8,187,'اتصالات - سنة ثانية','Sunday','08:00:00','09:30:00','قاعة اتصالات - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(66,8,187,'اتصالات - سنة ثانية','Monday','08:00:00','09:30:00','قاعة اتصالات - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(67,9,187,'اتصالات - سنة ثانية','Sunday','09:30:00','11:00:00','قاعة اتصالات - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(68,9,187,'اتصالات - سنة ثانية','Monday','09:30:00','11:00:00','قاعة اتصالات - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(69,6,188,'الكترون - سنة أولى','Sunday','08:00:00','09:30:00','قاعة الكترون - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(70,6,188,'الكترون - سنة أولى','Monday','08:00:00','09:30:00','قاعة الكترون - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(71,7,188,'الكترون - سنة أولى','Sunday','09:30:00','11:00:00','قاعة الكترون - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(72,7,188,'الكترون - سنة أولى','Monday','09:30:00','11:00:00','قاعة الكترون - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(73,10,189,'الكترون - سنة ثانية','Sunday','08:00:00','09:30:00','قاعة الكترون - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(74,10,189,'الكترون - سنة ثانية','Monday','08:00:00','09:30:00','قاعة الكترون - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(75,11,189,'الكترون - سنة ثانية','Sunday','09:30:00','11:00:00','قاعة الكترون - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(76,11,189,'الكترون - سنة ثانية','Monday','09:30:00','11:00:00','قاعة الكترون - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(77,7,186,'ذكاء اصطناعي - سنة أولى','Sunday','08:00:00','09:30:00','قاعة ذكاء اصطناعي - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(78,7,186,'ذكاء اصطناعي - سنة أولى','Monday','08:00:00','09:30:00','قاعة ذكاء اصطناعي - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(79,12,183,'ذكاء اصطناعي - سنة أولى','Sunday','09:30:00','11:00:00','قاعة ذكاء اصطناعي - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(80,12,183,'ذكاء اصطناعي - سنة أولى','Monday','09:30:00','11:00:00','قاعة ذكاء اصطناعي - سنة أولى','2026-09-11 17:54:57','2026-09-11 17:54:57'),(81,13,183,'ذكاء اصطناعي - سنة ثانية','Sunday','08:00:00','09:30:00','قاعة ذكاء اصطناعي - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(82,13,183,'ذكاء اصطناعي - سنة ثانية','Monday','08:00:00','09:30:00','قاعة ذكاء اصطناعي - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(83,14,182,'ذكاء اصطناعي - سنة ثانية','Sunday','09:30:00','11:00:00','قاعة ذكاء اصطناعي - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57'),(84,14,182,'ذكاء اصطناعي - سنة ثانية','Monday','09:30:00','11:00:00','قاعة ذكاء اصطناعي - سنة ثانية','2026-09-11 17:54:57','2026-09-11 17:54:57');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semesters` (
  `semester_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`semester_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
INSERT INTO `semesters` VALUES (1,'الفصل الدراسي الأول','2026-09-09','2026-09-19',1,'2026-09-09 18:20:56','2026-09-20 03:33:19'),(2,'الفصل الدراسي الثاني','2027-02-01','2027-06-30',0,'2026-09-18 20:30:27','2026-09-18 20:30:27');
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_requests`
--

DROP TABLE IF EXISTS `student_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_affairs',
  `affairs_decision` enum('approved','rejected') DEFAULT NULL,
  `hod_decision` enum('approved','rejected') DEFAULT NULL,
  `admin_decision` enum('approved','rejected') DEFAULT NULL,
  `affairs_notes` text DEFAULT NULL,
  `hod_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_requests_student_id_foreign` (`student_id`),
  CONSTRAINT `student_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_requests`
--

LOCK TABLES `student_requests` WRITE;
/*!40000 ALTER TABLE `student_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_warnings`
--

DROP TABLE IF EXISTS `student_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_warnings` (
  `warning_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `warning_level` enum('first','second','final') NOT NULL,
  `absence_days` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `action_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`warning_id`),
  KEY `student_warnings_student_id_foreign` (`student_id`),
  CONSTRAINT `student_warnings_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_warnings`
--

LOCK TABLES `student_warnings` WRITE;
/*!40000 ALTER TABLE `student_warnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `student_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `student_code` varchar(255) NOT NULL,
  `device_id` varchar(255) DEFAULT NULL COMMENT 'معرّف الجهاز الوحيد المسموح له بتسجيل الحضور',
  `is_device_locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true = الجهاز مقفّل ولا يمكن تغييره إلا من الأدمن',
  `level` varchar(255) DEFAULT NULL,
  `face_embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`face_embedding`)),
  `reference_photo` varchar(255) DEFAULT NULL,
  `requires_face_reset` tinyint(1) NOT NULL DEFAULT 0,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `students_student_code_unique` (`student_code`),
  KEY `students_user_id_index` (`user_id`),
  KEY `students_program_id_index` (`program_id`),
  CONSTRAINT `students_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (30,151,'2026001','bb13d570ddc38d1beae55c12befabdd3310a33f0211dc2a2020f8d3303807df8',1,'السنة الأولى',NULL,NULL,0,'2008-04-12','2026-09-10 02:52:39','2026-09-19 17:56:00',1),(31,153,'2026002','902a36ac671e56215922a5053aaf7623fe8328d6fefa760ca5059d1effbd0256',1,'السنة الأولى',NULL,NULL,0,'2008-06-06','2026-09-10 03:08:18','2026-09-12 11:09:38',2),(32,156,'2026003',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-08-07','2026-09-10 03:23:56','2026-09-10 03:23:56',3),(33,158,'2026004',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-05-06','2026-09-10 04:16:26','2026-09-10 04:16:26',4),(34,160,'2026005',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-04-06','2026-09-10 04:37:32','2026-09-19 17:56:00',1),(35,162,'2026006',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-02-03','2026-09-10 04:40:03','2026-09-10 04:40:03',2),(36,164,'2026007',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-02-05','2026-09-10 04:42:38','2026-09-10 04:53:55',3),(37,166,'2026008',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-01-03','2026-09-10 04:52:04','2026-09-10 04:52:04',4),(42,190,'2026009',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-03-04','2026-09-11 02:06:26','2026-09-11 02:06:26',1),(43,191,'2026010',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-05-06','2026-09-11 02:07:42','2026-09-11 02:07:42',1),(44,192,'2026011',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-03-05','2026-09-11 02:15:28','2026-09-11 02:15:28',2),(45,193,'2026012',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-05-06','2026-09-11 02:16:39','2026-09-11 02:16:39',2),(46,198,'2026013',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-09-06','2026-09-11 02:28:37','2026-09-11 02:28:37',3),(47,199,'2026014',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-09-09','2026-09-11 02:30:26','2026-09-11 02:30:26',3),(48,201,'2026015',NULL,0,'السنة الأولى',NULL,NULL,0,'2008-07-31','2026-09-11 02:33:23','2026-09-11 02:33:23',4),(49,204,'2026016',NULL,0,'السنة الثانية',NULL,NULL,0,'2007-04-04','2026-09-11 02:37:28','2026-09-11 22:28:53',4);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'primary_color','#f2f20d','2026-09-06 17:30:54','2026-09-06 17:30:54'),(2,'accent_name','gold','2026-09-06 17:30:54','2026-09-06 17:30:54'),(3,'theme_mode','dark','2026-09-06 17:30:54','2026-09-06 17:30:54');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `teacher_dashboard_stats_view`
--

DROP TABLE IF EXISTS `teacher_dashboard_stats_view`;
/*!50001 DROP VIEW IF EXISTS `teacher_dashboard_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `teacher_dashboard_stats_view` AS SELECT
 1 AS `teacher_id`,
  1 AS `courses_count`,
  1 AS `active_assignments_count` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `teacher_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `specialization` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `advisor_branch` varchar(255) DEFAULT NULL,
  `advisor_year` varchar(255) DEFAULT NULL,
  `advisor_section` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`teacher_id`),
  KEY `teachers_user_id_index` (`user_id`),
  CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (21,155,'علوم الحاسوب','2026-08-18 14:43:29','2026-08-18 14:43:29',NULL,NULL,NULL),(24,182,'ذكاء اصطناعي','2026-09-10 18:54:13','2026-09-10 18:54:13','ذكاء اصطناعي','السنة الثانية',NULL),(25,183,'ذكاء اصطناعي','2026-09-11 01:48:21','2026-09-11 01:58:20','ذكاء اصطناعي','السنة الأولى',NULL),(26,184,'معلوماتية','2026-09-11 01:50:15','2026-09-11 02:01:01','معلوماتية','السنة الأولى',NULL),(27,185,'معلوماتية','2026-09-11 01:54:11','2026-09-11 01:54:11','معلوماتية','السنة الثانية',NULL),(28,186,'اتصالات','2026-09-11 01:57:30','2026-09-11 01:57:30','اتصالات','السنة الأولى',NULL),(29,187,'اتصالات','2026-09-11 02:00:02','2026-09-11 02:00:02','اتصالات','السنة الثانية',NULL),(30,188,'الكترون','2026-09-11 02:02:36','2026-09-11 02:02:36','الكترون','السنة الأولى',NULL),(31,189,'الكترون','2026-09-11 02:04:22','2026-09-11 02:04:22','الكترون','السنة الثانية',NULL);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `university_ids`
--

DROP TABLE IF EXISTS `university_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `university_ids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `university_ids_university_id_unique` (`university_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `university_ids`
--

LOCK TABLES `university_ids` WRITE;
/*!40000 ALTER TABLE `university_ids` DISABLE KEYS */;
/*!40000 ALTER TABLE `university_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activities_user_id_index` (`user_id`),
  KEY `user_activities_role_name_index` (`role_name`),
  KEY `user_activities_action_index` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activities`
--

LOCK TABLES `user_activities` WRITE;
/*!40000 ALTER TABLE `user_activities` DISABLE KEYS */;
INSERT INTO `user_activities` VALUES (1,7,'محمد المحمد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-06 17:35:12','2026-09-06 17:35:12'),(2,7,'محمد المحمد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 13:54:53','2026-09-07 13:54:53'),(3,7,'محمد المحمد','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 14:13:14','2026-09-07 14:13:14'),(4,3,'عمر الخالد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 14:13:46','2026-09-07 14:13:46'),(5,3,'عمر الخالد','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 14:13:55','2026-09-07 14:13:55'),(6,28,'محمد المحمد','شؤون طلاب','تغيير السنة الدراسية لطالب','تم تغيير سنة الطالب سارة علي حسن من السنة الأولى إلى السنة الثانية','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:41:25','2026-08-13 12:41:25'),(7,28,'محمد المحمد','شؤون طلاب','تغيير السنة الدراسية لطالب','تم تغيير سنة الطالب سارة احمد من السنة الثانية إلى السنة الأولى','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:41:44','2026-08-13 12:41:44'),(8,76,'زهرية سعدوني','طالب','تسجيل دخول','تسجيل دخول ناجح عبر موقع الطالب الإلكتروني','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:54:25','2026-08-13 12:54:25'),(9,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:57:24','2026-08-13 12:57:24'),(10,28,'محمد المحمد','شؤون طلاب','تغيير السنة الدراسية لطالب','تم تغيير سنة الطالب زهرية سعدوني من السنة الثانية إلى السنة الأولى','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:57:36','2026-08-13 12:57:36'),(11,76,'زهرية سعدوني','طالب','تسجيل دخول','تسجيل دخول ناجح عبر موقع الطالب الإلكتروني','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:57:53','2026-08-13 12:57:53'),(12,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:58:17','2026-08-13 12:58:17'),(13,28,'محمد المحمد','شؤون طلاب','تغيير السنة الدراسية لطالب','تم تغيير سنة الطالب زهرية سعدوني من السنة الأولى إلى السنة الثانية','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 12:58:24','2026-08-13 12:58:24'),(14,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:11:46','2026-08-13 13:11:46'),(15,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:24:45','2026-08-13 13:24:45'),(16,79,'محمد غنام','ولي أمر','تسجيل دخول','تسجيل دخول ناجح عبر موقع ولي الأمر الإلكتروني','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:27:23','2026-08-13 13:27:23'),(17,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:39:48','2026-08-13 13:39:48'),(18,79,'محمد غنام','ولي أمر','محاولة دخول مرفوضة','هذا الحساب ليس حساب طالب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:40:36','2026-08-13 13:40:36'),(19,79,'محمد غنام','ولي أمر','تسجيل دخول','تسجيل دخول ناجح عبر موقع ولي الأمر الإلكتروني','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 13:41:08','2026-08-13 13:41:08'),(20,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 14:01:46','2026-08-13 14:01:46'),(21,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح إلى لوحة الإدارة العامة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 22:52:15','2026-08-13 22:52:15'),(22,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 22:52:52','2026-08-13 22:52:52'),(23,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح إلى لوحة الإدارة العامة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 22:54:49','2026-08-13 22:54:49'),(24,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-13 22:55:16','2026-08-13 22:55:16'),(25,28,'محمد المحمد','شؤون طلاب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','10.63.70.210','Dart/3.11 (dart:io)','2026-08-13 22:56:49','2026-08-13 22:56:49'),(26,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح إلى لوحة الإدارة العامة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 12:06:15','2026-08-14 12:06:15'),(27,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 12:07:07','2026-08-14 12:07:07'),(28,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 12:37:24','2026-08-14 12:37:24'),(29,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح إلى لوحة شؤون الطلاب','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 13:45:37','2026-08-14 13:45:37'),(30,28,'محمد المحمد','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 13:58:32','2026-08-14 13:58:32'),(31,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح عبر البوابة الموحدة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:01:04','2026-08-14 14:01:04'),(32,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:20:07','2026-08-14 14:20:07'),(33,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:35:11','2026-08-14 14:35:11'),(34,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:35:21','2026-08-14 14:35:21'),(35,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:35:29','2026-08-14 14:35:29'),(36,28,'محمد المحمد','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:38:14','2026-08-14 14:38:14'),(37,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:49:22','2026-08-14 14:49:22'),(38,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 14:50:22','2026-08-14 14:50:22'),(39,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 17:37:24','2026-08-14 17:37:24'),(40,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 17:41:01','2026-08-14 17:41:01'),(41,112,'خالد اسماعيل','معلم','تسجيل خروج','قام المعلم بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 17:59:11','2026-08-14 17:59:11'),(42,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 17:59:33','2026-08-14 17:59:33'),(43,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:09:02','2026-08-14 18:09:02'),(44,112,'خالد اسماعيل','معلم','تسجيل خروج','قام المعلم بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:17:24','2026-08-14 18:17:24'),(45,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:17:46','2026-08-14 18:17:46'),(46,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:28:47','2026-08-14 18:28:47'),(47,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:32:35','2026-08-14 18:32:35'),(48,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:33:58','2026-08-14 18:33:58'),(49,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:39:32','2026-08-14 18:39:32'),(50,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:41:35','2026-08-14 18:41:35'),(51,108,'احمد سالم','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:42:50','2026-08-14 18:42:50'),(52,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:45:24','2026-08-14 18:45:24'),(53,108,'احمد سالم','معلم','تسجيل خروج','قام المعلم بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:47:22','2026-08-14 18:47:22'),(54,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:49:25','2026-08-14 18:49:25'),(55,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:51:11','2026-08-14 18:51:11'),(56,64,'روى ابو سمرة','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 18:52:48','2026-08-14 18:52:48'),(57,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 21:44:49','2026-08-14 21:44:49'),(58,64,'روى ابو سمرة','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 22:11:51','2026-08-14 22:11:51'),(59,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 22:17:37','2026-08-14 22:17:37'),(60,28,'محمد المحمد','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 22:42:13','2026-08-14 22:42:13'),(61,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 23:31:17','2026-08-14 23:31:17'),(62,79,'محمد غنام','ولي أمر','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 23:31:25','2026-08-14 23:31:25'),(63,79,'محمد غنام','ولي أمر','تسجيل خروج','قام ولي الأمر بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 23:35:59','2026-08-14 23:35:59'),(64,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-14 23:36:06','2026-08-14 23:36:06'),(65,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-15 00:01:08','2026-08-15 00:01:08'),(66,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-15 00:26:12','2026-08-15 00:26:12'),(67,28,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-18 14:44:07','2026-08-18 14:44:07'),(68,28,'محمد المحمد','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-18 14:52:06','2026-08-18 14:52:06'),(69,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-18 14:52:13','2026-08-18 14:52:13'),(70,1,'إدارة المعهد التقني','إدارة','تسجيل خروج','قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-18 15:55:48','2026-08-18 15:55:48'),(71,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-18 15:59:11','2026-08-18 15:59:11'),(72,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','192.168.1.105','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-28 17:16:50','2026-08-28 17:16:50'),(73,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-28 17:17:16','2026-08-28 17:17:16'),(74,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-28 17:19:32','2026-08-28 17:19:32'),(75,112,'خالد اسماعيل','معلم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-28 18:07:50','2026-08-28 18:07:50'),(76,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','192.168.1.105','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-08-28 18:13:18','2026-08-28 18:13:18'),(77,147,'هدى شبلي','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-08-31 22:28:57','2026-08-31 22:28:57'),(78,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 22:52:06','2026-08-31 22:52:06'),(79,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 13:29:53','2026-09-01 13:29:53'),(80,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','10.245.0.235','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-09-01 15:30:21','2026-09-01 15:30:21'),(81,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','10.245.0.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 15:30:50','2026-09-01 15:30:50'),(82,112,'خالد اسماعيل','معلم','تسجيل خروج','قام المعلم بتسجيل الخروج يدوياً','10.245.0.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 15:31:29','2026-09-01 15:31:29'),(83,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','10.245.0.82','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 15:32:06','2026-09-01 15:32:06'),(84,147,'هدى شبلي','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 16:18:19','2026-09-01 16:18:19'),(85,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 16:18:44','2026-09-01 16:18:44'),(86,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 16:20:38','2026-09-01 16:20:38'),(87,147,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','192.168.1.105','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36','2026-09-01 21:46:10','2026-09-01 21:46:10'),(88,112,'خالد اسماعيل','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 21:46:27','2026-09-01 21:46:27'),(89,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 12:31:46','2026-09-02 12:31:46'),(90,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 13:15:15','2026-09-02 13:15:15'),(91,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 13:15:21','2026-09-02 13:15:21'),(92,85,'جودي سلطاني','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 14:30:39','2026-09-07 14:30:39'),(93,11,'عبدالله العبدالله','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-07 14:32:38','2026-09-07 14:32:38'),(94,3,'عمر الخالد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 14:34:35','2026-09-07 14:34:35'),(95,11,'عبدالله العبدالله','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-07 14:35:14','2026-09-07 14:35:14'),(96,85,'جودي سلطاني','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-07 14:35:24','2026-09-07 14:35:24'),(97,3,'عمر الخالد','طالب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 15:12:53','2026-09-07 15:12:53'),(98,64,'روى ابو سمرة','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 15:13:43','2026-09-07 15:13:43'),(99,64,'روى ابو سمرة','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 15:14:25','2026-09-07 15:14:25'),(100,64,'روى ابو سمرة','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-07 15:19:00','2026-09-07 15:19:00'),(101,73,'احمد ديب','رئيس قسم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 11:24:22','2026-09-08 11:24:22'),(102,64,'روى ابو سمرة','طالب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-08 13:49:03','2026-09-08 13:49:03'),(103,6,'محمد المحمد','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-09-08 14:05:40','2026-09-08 14:05:40'),(104,73,'احمد ديب','رئيس قسم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 15:46:44','2026-09-08 15:46:44'),(105,3,'عمر الخالد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-08 15:50:04','2026-09-08 15:50:04'),(106,3,'عمر الخالد','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-08 16:53:50','2026-09-08 16:53:50'),(107,3,'عمر الخالد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-08 16:54:07','2026-09-08 16:54:07'),(108,3,'عمر الخالد','طالب','تسجيل خروج','قام الطالب بتسجيل الخروج يدوياً من الموقع','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-08 16:54:12','2026-09-08 16:54:12'),(109,150,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-09 01:57:59','2026-09-09 01:57:59'),(110,150,'هدى شبلي','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-09 12:20:55','2026-09-09 12:20:55'),(111,73,'احمد ديب','رئيس قسم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-09 12:22:22','2026-09-09 12:22:22'),(112,73,'احمد ديب','رئيس قسم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-09 14:42:26','2026-09-09 14:42:26'),(113,6,'محمد المحمد','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-09-09 14:43:41','2026-09-09 14:43:41'),(114,6,'محمد المحمد','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-09 15:31:30','2026-09-09 15:31:30'),(115,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-09 15:33:39','2026-09-09 15:33:39'),(116,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:12:56','2026-09-10 02:12:56'),(117,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: نور إبراهيم جمال (st_test4)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:40:36','2026-09-10 02:40:36'),(118,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: ليلى محمود عمر (st_test5)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:40:43','2026-09-10 02:40:43'),(119,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: محمد المحمد (student_2026200)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:40:48','2026-09-10 02:40:48'),(120,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: هدى شبلي (2026100)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:40:54','2026-09-10 02:40:54'),(121,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: أحمد الخالد (student_2026201)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:40:59','2026-09-10 02:40:59'),(122,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: عمر العلي (student_2026202)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:10','2026-09-10 02:41:10'),(123,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: خالد الحسن (student_2026203)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:16','2026-09-10 02:41:16'),(124,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: عبدالله العبدالله (student_2026204)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:21','2026-09-10 02:41:21'),(125,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: فاطمة السالم (student_2026205)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:26','2026-09-10 02:41:26'),(126,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: عائشة النجار (student_2026206)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:31','2026-09-10 02:41:31'),(127,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: مريم الحداد (student_2026207)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:41:37','2026-09-10 02:41:37'),(128,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: سارة العبيد (student_2026208)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:02','2026-09-10 02:43:02'),(129,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: نورة المحمود (student_2026209)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:07','2026-09-10 02:43:07'),(130,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: يوسف اليوسف (student_2026210)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:15','2026-09-10 02:43:15'),(131,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: طارق الطارق (student_2026211)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:18','2026-09-10 02:43:18'),(132,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: منى الصالح (student_2026214)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:23','2026-09-10 02:43:23'),(133,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: حسن الحسين (student_2026212)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:38','2026-09-10 02:43:38'),(134,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: حسين العمر (student_2026213)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:43','2026-09-10 02:43:43'),(135,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: ريم الأحمد (student_2026215)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:43:49','2026-09-10 02:43:49'),(136,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: ندى الناصر (student_2026216)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:01','2026-09-10 02:44:01'),(137,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: ليلى الزهراني (student_2026219)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:06','2026-09-10 02:44:06'),(138,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: محمود الدوسري (student_2026217)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:13','2026-09-10 02:44:13'),(139,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: علي القحطاني (student_2026218)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:18','2026-09-10 02:44:18'),(140,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: عمر الخالد (2026099)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:23','2026-09-10 02:44:23'),(141,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: عمر خالد مصطفى (st_test3)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:28','2026-09-10 02:44:28'),(142,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: كريم يوسف ناصر (st_test6)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:33','2026-09-10 02:44:33'),(143,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: هنا سعيد رشيد (st_test7)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:44:56','2026-09-10 02:44:56'),(144,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: سارة علي حسن (st_test2)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:45:01','2026-09-10 02:45:01'),(145,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: محمد أحمد السيد (st_test1)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:45:06','2026-09-10 02:45:06'),(146,1,'إدارة المعهد التقني','إدارة','حذف جماعي','قامت الإدارة بحذف جميع حسابات الطلاب (21 طالب)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:50:36','2026-09-10 02:50:36'),(147,1,'إدارة المعهد التقني','إدارة','حذف جماعي','قامت الإدارة بحذف جميع حسابات أولياء الأمور (32 ولي أمر)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:50:48','2026-09-10 02:50:48'),(148,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: يوسف الاحمد برقم جامعي (2026001)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 02:52:39','2026-09-10 02:52:39'),(149,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: جودي محمد برقم جامعي (2026002)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 03:08:18','2026-09-10 03:08:18'),(150,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: ابو يوسف الاحمد (admin@edu-bridge.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 03:11:29','2026-09-10 03:11:29'),(151,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: عمر خالد برقم جامعي (2026003)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 03:23:56','2026-09-10 03:23:56'),(152,156,'عمر خالد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-10 03:43:44','2026-09-10 03:43:44'),(153,156,'عمر خالد','طالب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-10 04:05:46','2026-09-10 04:05:46'),(154,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: منال علي برقم جامعي (2026004)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:16:26','2026-09-10 04:16:26'),(155,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:20:37','2026-09-10 04:20:37'),(156,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: كمال يوسف برقم جامعي (2026005)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:37:32','2026-09-10 04:37:32'),(157,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: منة جمعة برقم جامعي (2026006)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:40:03','2026-09-10 04:40:03'),(158,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: هدى الشبلي برقم جامعي (2026007)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:42:38','2026-09-10 04:42:38'),(159,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: كريم الابيض برقم جامعي (2026008)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:52:04','2026-09-10 04:52:04'),(160,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: هدى الشبلي (hudashbli@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:53:55','2026-09-10 04:53:55'),(161,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: محمد المحمد (affairs@edu-bridge.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:57:05','2026-09-10 04:57:05'),(162,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: Majdouleen Mahmood (adm2in)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 04:57:15','2026-09-10 04:57:15'),(163,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: محمد المحمد (mohmmadalmohmmad@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:02:56','2026-09-10 05:02:56'),(164,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: أحمد ديب (ahmaddeep@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:07:09','2026-09-10 05:07:09'),(165,1,'إدارة المعهد التقني','إدارة','حذف حساب','قامت الإدارة بحذف حساب: احمد ديب (ahmad_deeb)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:09:45','2026-09-10 05:09:45'),(166,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: عيسى كردي (issakurdi@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:48:57','2026-09-10 05:48:57'),(167,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: وليد احمد (walidadmad@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:49:58','2026-09-10 05:49:58'),(168,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: علي خليل (alikhalil@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:51:27','2026-09-10 05:51:27'),(169,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: خالد اسماعيل (khaledismail@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 05:56:12','2026-09-10 05:56:12'),(183,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 18:26:47','2026-09-10 18:26:47'),(184,1,'إدارة المعهد التقني','إدارة','حذف جماعي','قامت الإدارة بحذف جميع حسابات المعلمين (21 معلم)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-10 18:27:19','2026-09-10 18:27:19'),(185,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 01:44:53','2026-09-11 01:44:53'),(186,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: عوض حلاوة (awadhalawa@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 01:58:20','2026-09-11 01:58:20'),(187,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: رنا باكير (ranabakir@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:01:01','2026-09-11 02:01:01'),(188,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: احمد خليفة برقم جامعي (2026009)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:06:26','2026-09-11 02:06:26'),(189,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: اكرم السيد برقم جامعي (2026010)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:07:42','2026-09-11 02:07:42'),(190,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: ربا عيسى برقم جامعي (2026011)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:15:28','2026-09-11 02:15:28'),(191,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: لؤي الشيخ برقم جامعي (2026012)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:16:39','2026-09-11 02:16:39'),(192,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: محمد ايوب برقم جامعي (2026013)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:28:37','2026-09-11 02:28:37'),(193,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: خالد ايوب برقم جامعي (2026014)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:30:26','2026-09-11 02:30:26'),(194,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: رنا خليل برقم جامعي (2026015)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:33:23','2026-09-11 02:33:23'),(195,1,'إدارة المعهد التقني','إدارة','إنشاء حساب طالب','قامت الإدارة بإنشاء حساب جديد للطالب: يوسف الاحمد برقم جامعي (2026016)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 02:37:28','2026-09-11 02:37:28'),(196,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 06:24:00','2026-09-11 06:24:00'),(197,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 07:07:40','2026-09-11 07:07:40'),(198,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 07:08:21','2026-09-11 07:08:21'),(199,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 15:42:37','2026-09-11 15:42:37'),(200,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 16:16:18','2026-09-11 16:16:18'),(201,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 16:22:47','2026-09-11 16:22:47'),(202,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 16:50:03','2026-09-11 16:50:03'),(203,2,'أحمد ديب','رئيس قسم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 17:23:00','2026-09-11 17:23:00'),(204,2,'أحمد ديب','رئيس قسم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 17:50:52','2026-09-11 17:50:52'),(205,2,'أحمد ديب','رئيس قسم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 17:59:10','2026-09-11 17:59:10'),(206,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 18:03:15','2026-09-11 18:03:15'),(207,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 18:18:37','2026-09-11 18:18:37'),(208,2,'أحمد ديب','رئيس قسم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0','2026-09-11 18:38:43','2026-09-11 18:38:43'),(209,185,'احمد نصلة','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-09-11 18:40:27','2026-09-11 18:40:27'),(210,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 19:00:09','2026-09-11 19:00:09'),(211,185,'احمد نصلة','معلم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','2026-09-11 19:08:20','2026-09-11 19:08:20'),(212,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:36:26','2026-09-11 21:36:26'),(213,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:37:07','2026-09-11 21:37:07'),(214,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:37:47','2026-09-11 21:37:47'),(215,151,'يوسف الاحمد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 22:05:41','2026-09-11 22:05:41'),(216,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 22:27:08','2026-09-11 22:27:08'),(217,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 22:27:33','2026-09-11 22:27:33'),(218,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: ابراهيم يوسف (ibrahemyousef@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 22:28:53','2026-09-11 22:28:53'),(219,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: ابو ابراهيم يوسف (aboibrahem@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 22:29:43','2026-09-11 22:29:43'),(220,151,'يوسف الاحمد','طالب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:01:14','2026-09-11 23:01:14'),(221,NULL,'زائر','مستخدم','ربط ابن بولي أمر','قامت الإدارة بربط الطالب يوسف الاحمد بحساب ولي الأمر: ابو ابراهيم يوسف','127.0.0.1','Symfony','2026-09-11 23:02:04','2026-09-11 23:02:04'),(222,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-09-11 23:26:50','2026-09-11 23:26:50'),(223,1,'إدارة المعهد التقني','إدارة','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:36:04','2026-09-11 23:36:04'),(224,151,'يوسف الاحمد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:36:38','2026-09-11 23:36:38'),(225,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:40:29','2026-09-11 23:40:29'),(226,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:41:28','2026-09-11 23:41:28'),(227,1,'إدارة المعهد التقني','إدارة','ربط ابن بولي أمر','قامت الإدارة بربط الطالب ابراهيم يوسف بحساب ولي الأمر: ابو ابراهيم يوسف','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:42:10','2026-09-11 23:42:10'),(228,1,'إدارة المعهد التقني','إدارة','تعديل حساب','قامت الإدارة بتعديل بيانات الحساب: ابو ابراهيم يوسف (aboibrahem@gmail.com)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 23:42:19','2026-09-11 23:42:19'),(229,184,'رنا باكير','معلم','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 00:02:17','2026-09-12 00:02:17'),(230,151,'يوسف الاحمد','طالب','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 00:07:51','2026-09-12 00:07:51'),(231,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 00:10:20','2026-09-12 00:10:20'),(232,151,'يوسف الاحمد','طالب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 00:21:20','2026-09-12 00:21:20'),(233,184,'رنا باكير','معلم','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 00:31:38','2026-09-12 00:31:38'),(234,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','192.168.1.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 08:37:14','2026-09-12 08:37:14'),(235,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل خروج','قام موظف الشؤون بتسجيل الخروج يدوياً','192.168.1.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 08:58:08','2026-09-12 08:58:08'),(236,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','192.168.1.101','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 09:17:45','2026-09-12 09:17:45'),(237,153,'جودي محمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.1.101','curl/8.17.0','2026-09-12 10:15:06','2026-09-12 10:15:06'),(238,153,'جودي محمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:09:38','2026-09-12 11:09:38'),(239,153,'جودي محمد','طالب','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:10:56','2026-09-12 11:10:56'),(240,1,'إدارة المعهد التقني','إدارة','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:13:46','2026-09-12 11:13:46'),(241,1,'إدارة المعهد التقني','إدارة','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:15:06','2026-09-12 11:15:06'),(242,153,'جودي محمد','طالب','محاولة دخول فاشلة','كلمة مرور غير صحيحة عبر التطبيق','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:15:28','2026-09-12 11:15:28'),(243,153,'جودي محمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.1.105','Dart/3.11 (dart:io)','2026-09-12 11:15:36','2026-09-12 11:15:36'),(244,153,'جودي محمد','طالب','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 11:47:27','2026-09-12 11:47:27'),(245,1,'إدارة المعهد التقني','إدارة','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 11:47:41','2026-09-12 11:47:41'),(246,153,'جودي محمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','curl/8.17.0','2026-09-12 11:56:58','2026-09-12 11:56:58'),(247,1,'إدارة المعهد التقني','إدارة','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 12:16:25','2026-09-12 12:16:25'),(248,153,'جودي محمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-09-12 12:16:40','2026-09-12 12:16:40'),(249,1,'إدارة المعهد التقني','إدارة','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 12:18:49','2026-09-12 12:18:49'),(250,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 20:38:38','2026-09-12 20:38:38'),(251,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:53:30','2026-09-17 05:53:30'),(252,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 05:55:03','2026-09-17 05:55:03'),(253,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:55:41','2026-09-17 05:55:41'),(254,151,'يوسف الاحمد','طالب','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:57:26','2026-09-17 05:57:26'),(255,185,'احمد نصلة','معلم','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:59:04','2026-09-17 05:59:04'),(256,185,'احمد نصلة','معلم','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:59:23','2026-09-17 05:59:23'),(257,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 05:59:35','2026-09-17 05:59:35'),(258,151,'يوسف الاحمد','طالب','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:31:04','2026-09-17 06:31:04'),(259,2,'أحمد ديب','رئيس قسم','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:31:56','2026-09-17 06:31:56'),(260,2,'أحمد ديب','رئيس قسم','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:32:58','2026-09-17 06:32:58'),(261,185,'احمد نصلة','معلم','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:34:16','2026-09-17 06:34:16'),(262,185,'احمد نصلة','معلم','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:35:08','2026-09-17 06:35:08'),(263,2,'أحمد ديب','رئيس قسم','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:35:21','2026-09-17 06:35:21'),(264,2,'أحمد ديب','رئيس قسم','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:36:18','2026-09-17 06:36:18'),(265,154,'ابو يوسف الاحمد','ولي أمر','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 06:38:53','2026-09-17 06:38:53'),(266,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 07:00:42','2026-09-17 07:00:42'),(267,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 07:14:21','2026-09-17 07:14:21'),(268,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 07:34:35','2026-09-17 07:34:35'),(269,154,'ابو يوسف الاحمد','ولي أمر','تسجيل خروج (تطبيق)','قام المستخدم بتسجيل الخروج من التطبيق','192.168.55.52','Dart/3.11 (dart:io)','2026-09-17 07:40:00','2026-09-17 07:40:00'),(270,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 07:43:26','2026-09-17 07:43:26'),(271,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 08:05:44','2026-09-17 08:05:44'),(272,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 08:16:04','2026-09-17 08:16:04'),(273,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 09:17:26','2026-09-17 09:17:26'),(274,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-17 09:18:54','2026-09-17 09:18:54'),(275,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 18:39:07','2026-09-18 18:39:07'),(276,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 19:41:14','2026-09-18 19:41:14'),(277,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 19:41:46','2026-09-18 19:41:46'),(278,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 21:23:48','2026-09-18 21:23:48'),(279,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 21:32:14','2026-09-18 21:32:14'),(280,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 23:04:32','2026-09-18 23:04:32'),(281,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 23:04:42','2026-09-18 23:04:42'),(282,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 15:41:15','2026-09-19 15:41:15'),(283,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:13:11','2026-09-19 17:13:11'),(284,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:21:09','2026-09-19 17:21:09'),(285,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 18:17:19','2026-09-19 18:17:19'),(286,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 03:22:46','2026-09-20 03:22:46'),(287,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','192.168.55.52','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36','2026-09-20 06:05:53','2026-09-20 06:05:53'),(288,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 06:27:45','2026-09-20 06:27:45'),(289,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','192.168.55.52','Dart/3.11 (dart:io)','2026-09-20 06:47:49','2026-09-20 06:47:49'),(290,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 07:30:35','2026-09-20 07:30:35'),(291,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 07:52:17','2026-09-20 07:52:17'),(292,151,'يوسف الاحمد','طالب','تسجيل دخول (تطبيق)','تسجيل دخول ناجح عبر التطبيق المحمول','127.0.0.1','Dart/3.11 (dart:io)','2026-09-20 08:05:06','2026-09-20 08:05:06'),(293,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 08:27:10','2026-09-20 08:27:10'),(294,168,'أحمد الرنتيسي','شؤون طلاب','خروج تلقائي (خمول)','تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 08:47:57','2026-09-20 08:47:57'),(295,168,'أحمد الرنتيسي','شؤون طلاب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 09:40:56','2026-09-20 09:40:56');
/*!40000 ALTER TABLE `user_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity` (
  `activity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `activity_type` varchar(255) NOT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`activity_id`),
  KEY `user_activity_user_id_foreign` (`user_id`),
  CONSTRAINT `user_activity_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity`
--

LOCK TABLES `user_activity` WRITE;
/*!40000 ALTER TABLE `user_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `telegram_id` varchar(255) DEFAULT NULL,
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
  `notifications_muted` tinyint(1) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_university_id_unique` (`university_id`),
  KEY `users_role_id_index` (`role_id`),
  KEY `users_status_index` (`status`),
  KEY `users_university_id_index` (`university_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'إدارة المعهد التقني',NULL,NULL,'admin_main','admin@edu-bridge.com',NULL,'$2y$12$JVZJUdLy6g2eFqlnVS.xreNAPIEHfvjSlHAE9lgYaQA4q7QQX8yh.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,'2026-09-12 11:47:41','i7UhZhe1HmfSBPchO2G0ePBjsNDM9NqhkC1aaPFxPLOBPfOb00Cb2Rf9NBkg','2026-09-06 17:30:54','2026-09-12 12:16:25'),(2,5,'أحمد ديب','أحمد','ديب','ahmaddeep-head@edu-bridge.com','ahmaddeep@gmail.com',NULL,'$2y$12$aMQ.RAnEUnqIE8IgSD8y9u1SU6c5nUwKPqexfvoeRSsT3pRZPb0S2','0956879878',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,'2026-09-17 06:35:20',NULL,'2026-09-06 17:30:55','2026-09-17 06:36:18'),(6,6,'محمد المحمد','محمد','المحمد','mohmmadalmohmmad-affairs@edu-bridge.com','mohmmadalmohmmad@gmail.com',NULL,'$2y$12$EWvn0eLo17mA2WfIpxpCmOCKoDRPonsxzxESeJbVpn9Gfydpfv.vW','0989768765',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,'SS6oqiaPqlUtwlkjkJAJy5B8LBdvSI5O5y38T9vfK9wqJOLEv7jWuVMVDZdw','2026-09-06 17:30:56','2026-09-10 05:02:56'),(60,5,'علي خليل','علي','خليل','alikhalil-head@edu-bridge.com','alikhalil@gmail.com',NULL,'$2y$12$kVQrlQuM57HtaTmenXjSX.Nmh7Z2coItclRCRuuYf3quqAh49TkKa','0932323232',NULL,NULL,NULL,'تجاري',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-06-09 14:07:06','2026-09-10 05:51:27'),(62,5,'وليد احمد','وليد','احمد','walidadmad-head@edu-bridge.com','walidadmad@gmail.com',NULL,'$2y$12$am9DNOyveoNlM1gwl8xvUu2s9Er00/9tqWitkV8XEufhS6JPYk7qy','0911111111',NULL,NULL,NULL,'طبي',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-06-09 14:37:36','2026-09-10 05:49:58'),(63,5,'عيسى كردي','عيسى','كردي','issakurdi-head@edu-bridge.com','issakurdi@gmail.com',NULL,'$2y$12$tZGP7AXQ8/T.1Qb6g06bsuOO7XoVEBwhvv.vOWpPvIs6khku/PvpS','0922222222',NULL,NULL,NULL,'هندسي',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-06-09 14:39:13','2026-09-10 05:48:56'),(151,3,'يوسف الاحمد','يوسف','الاحمد','2026001','yousefahmad@gmail.com',NULL,'$2y$12$pm1CAPsrCX7Q8gH7Y2Lcoec3lQqIML2IuGgJdYmS./1CuGXQA7j9i','0987467453',NULL,'7650604064','2026001','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2008-04-12','السنة الأولى','active','dKw8tOwdS_mOIl-jtyI-Nu:APA91bHydSXXLNUfHGDq0mCOUue1PXRhCdYJn1tvUAm0zz0G6N_OdRh45bbcvDBGpndYMoNNvt1tr9H_0R7bbjetW-zbSHx3qviHjeW_L0xNVG9anxEwr1w',0,'2026-09-20 08:05:06',NULL,'2026-09-10 02:52:33','2026-09-20 08:05:07'),(153,3,'جودي محمد','جودي','محمد','2026002','joudimohmmad@gmail.com',NULL,'$2y$12$BY9VsXsGAi6te4mqAGnYaei22gE3Xw9jioVyoYLrgVrAIkipLpPJe','0976385632',NULL,NULL,'2026002','نظم المعلومات الحاسوبية',NULL,NULL,'أنثى','2008-06-06','السنة الأولى','active','dWKBi1-ZQWq6pIx8PQ2WbO:APA91bGewg05XXGBAOwc_Yc4TTwljS7Mueo_nD3atlkFeXLs3HzhZhg2DH8W5Q-DTtVmpVCnBppa72xtqil9Y08JoRz_8qeO0EJCVCWonTIAkTVCRl64M9c',0,'2026-09-12 12:16:40',NULL,'2026-09-10 03:08:14','2026-09-12 20:36:57'),(154,4,'ابو يوسف الاحمد','ابو يوسف','الاحمد','aboyousef@edu-bridge.com','aboyousef@gmail.com',NULL,'$2y$12$Wrwkq5P1H6AXEKWJ5nmpn.8QAgV7af2.t7QMVvoqfWbJ0DR5/Jb4K','0976385632',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,'2026-09-17 06:38:53',NULL,'2026-09-10 03:12:28','2026-09-17 07:40:00'),(155,4,'ابو جودي محمد','ابو جودي','محمد','abojoudi@edu-bridge.com','abojoudi@gmail.com',NULL,'$2y$12$P4cMI3HqE.4cmZu/MI496O3LrGDM0n/lOmf9OKnfr/s15PKbfNjzy','0987467453',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 03:20:03','2026-09-10 03:20:03'),(156,3,'عمر خالد','عمر','خالد','2026003','omarkhaled@gmail.com',NULL,'$2y$12$cIpQRCA7mlfe1Uuyi1tJhOZu8KO0UGVrGsjA.01AbGGGU3ZjfxGF.','0978564789',NULL,'7650604064','2026003','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2008-08-07','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-10 03:23:52','2026-09-10 03:43:06'),(157,4,'ابو عمر خالد','ابو عمر','خالد','aboomar@edu-bridge.com','aboomar@gmail.com',NULL,'$2y$12$K3YeUi6NXCR.jPPoDFr7/udNX4t75unnuyMdQqAHOUZ7ylW2sSmOe','0956876798',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 03:58:41','2026-09-10 03:58:41'),(158,3,'منال علي','منال','علي','2026004','manalali@gmail.com',NULL,'$2y$12$tM5VhpMKtbfo42AfkrgeN.5r.xWsO0lzFWNCWw0dhWDcsjtoODWoG','0923567687',NULL,NULL,'2026004','نظم المعلومات الحاسوبية',NULL,NULL,'أنثى','2008-05-06','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-10 04:16:26','2026-09-10 04:16:26'),(159,4,'ابو منال علي','ابو منال','علي','abomanal@edu-bridge.com','abomanal@gmail.com',NULL,'$2y$12$uYhJQp96IVKuYjlv8rxwI.U69TjJHOIrSVHViDPNHXaTHttAKZQFq','0978563432',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 04:19:10','2026-09-10 04:19:10'),(160,3,'كمال يوسف','كمال','يوسف','2026005','kamalyousef@gmail.com',NULL,'$2y$12$uQhptqoqNdL6cm.g9QYkxOWXMCc0py7F8UuiA76zStzVelZfX1W/O','0978678498',NULL,NULL,'2026005','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2007-04-06','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-10 04:37:32','2026-09-13 04:17:25'),(161,4,'ابو كمال يوسف','ابو كمال','يوسف','abokamal@edu-bridge.com','abokamal@gmail.com',NULL,'$2y$12$0befqjvidGw8iWZCRRLMaOpO2O5mhds8lmZVRxlV.RNY7zxBYCP1q','0980487092',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 04:38:45','2026-09-10 04:38:45'),(162,3,'منة جمعة','منة','جمعة','2026006','menajoumaa@gmail.com',NULL,'$2y$12$Fi7I1.hdnDukZi8IfTjLDepLVcbXoSmOlDTjcAE9p9Y7YjXKOdNBi','0967594367',NULL,NULL,'2026006','نظم المعلومات الحاسوبية',NULL,NULL,'أنثى','2007-02-03','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-10 04:40:03','2026-09-10 04:40:03'),(163,4,'ابو منة جمعة','ابو منة','جمعة','abomena@edu-bridge.com','abomena@gmail.com',NULL,'$2y$12$kiYPZMP0COabfj5BjXP12uH3CzX2U07x/TOCq9ZT3Gnpl79tswhd2','09786578',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 04:41:17','2026-09-10 04:41:17'),(164,3,'هدى الشبلي','هدى','الشبلي','2026007','hudashbli@gmail.com',NULL,'$2y$12$/PnapX1rKizu72vceX1Fxek.5/Yv1NuV41/U0qtpQAgPy3wsigkh6','0980868954',NULL,NULL,'2026007','نظم المعلومات الحاسوبية','الكترون',NULL,'أنثى','2007-02-05','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-10 04:42:38','2026-09-10 04:53:55'),(165,4,'ابو هدى الشبلي','ابو هدى','الشبلي','abohuda@edu-bridge','abohuda@gmail.com',NULL,'$2y$12$n.i8vOdstzV3hOGq/.zcIegtdm09ndI8KugNk3LZVK5f4FDhRv/iG','0978675432',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 04:45:20','2026-09-10 04:45:20'),(166,3,'كريم الابيض','كريم','الابيض','2026008','karemalabiad@gmail.com',NULL,'$2y$12$GTqHghXg6gvVi6eXWoMo5esoyESEldCA6.6pV15YCtUgYyZMmdxL6','0967874532',NULL,NULL,'2026008','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2007-01-03','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-10 04:52:04','2026-09-10 04:52:04'),(167,4,'ابو كريم الابيض','ابو كريم','الابيض','abokarem@edu-bride.com','abokarem@gmail.com',NULL,'$2y$12$7uh8SkPVzRBobyAoL3Lmheq9qrCRG2Y8WdZ1fdQoQZiPoEr9RkT7K','0989097656',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 04:53:19','2026-09-10 04:53:19'),(168,6,'أحمد الرنتيسي','أحمد','الرنتيسي','ahmadalrantisi-affairs@edu-bridge.com','ahmadalrantisi@gmail.com',NULL,'$2y$12$TCcLEqVYHZjn/FKpitXmK.vGui5D4A5.PAflann4YnG5Iry2yvkay','098976564321',NULL,'7821980919',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','dKw8tOwdS_mOIl-jtyI-Nu:APA91bHydSXXLNUfHGDq0mCOUue1PXRhCdYJn1tvUAm0zz0G6N_OdRh45bbcvDBGpndYMoNNvt1tr9H_0R7bbjetW-zbSHx3qviHjeW_L0xNVG9anxEwr1w',0,'2026-09-20 06:47:49','DngKioJHRXx3FmK87sbqkHumzo4vXWIcczZsPnMvkbCYeYZVxLfFamHR1S0R','2026-09-10 05:01:13','2026-09-20 06:47:51'),(182,2,'خالد اسماعيل','خالد','اسماعيل','khaledismail-trainer@edu-bridge.com','khaledismail@gmail.com',NULL,'$2y$12$6HEX.i9VU900srutrjTUpOVXxLKiyZZqQadNp9XKIgSXQGgofr4a2','0989768976',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-10 18:54:13','2026-09-10 18:54:13'),(183,2,'عوض حلاوة','عوض','حلاوة','awadhalawa-trainer@edu-bridge.com','awadhalawa@gmail.com',NULL,'$2y$12$efdkEEqqQtA/rFr3iBIkl.JraUO3bGXnAgRmoyUJiMxmVYaRHR/.6','098787654532',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 01:48:21','2026-09-11 01:58:20'),(184,2,'رنا باكير','رنا','باكير','ranabakir-trainer@edu-bridge.com','ranabakir@gmail.com',NULL,'$2y$12$Iet6raM8M5tbEsl2j84zBuY1eyMsP.GoJzbR9OEjr.5PcJIyFEU5.','097865436543',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 01:50:15','2026-09-11 02:01:01'),(185,2,'احمد نصلة','احمد','نصلة','ahmadnasla-trainer@edu-bridge.com','ahmadnasla@gmail.com',NULL,'$2y$12$ZdJwcz1KDCmMKBPQrHtwReG6mljy/l/k.Qr8T7j/XLPcaY4caRH2K','0978654321',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,'2026-09-17 06:34:16',NULL,'2026-09-11 01:54:11','2026-09-17 06:35:08'),(186,2,'حذيفة محمد','حذيفة','محمد','hudhayfatmuhamad-trainer@edu-bridge.com','hudhayfatmuhamad@gmail.com',NULL,'$2y$12$NHPEaZROreVkvQjtgWQxxuJrrvwsyRBuBk0gQ7e12/qdZ.eLf750S','0978657645',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 01:57:30','2026-09-11 01:57:30'),(187,2,'عبدالله يوسف','عبدالله','يوسف','abdallahyousef-trainer@edu-bridge.com','abdallahyousef@gmail.com',NULL,'$2y$12$rTcrmjpbnxgU4q3Ctksv2eQQQ3685qw4Ov4NVgw8eOtpkGUqRQByS','0989876567',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:00:02','2026-09-11 02:00:02'),(188,2,'جمال العمري','جمال','العمري','jamalaleumari-trainer@edu-bridge.com','jamalaleumari@gmail.com',NULL,'$2y$12$Ohg8B.Yk9vY.KEodunOeVeLwP4be/2cWAwvIDFzzKSESLBqUzVnpW','097864434567',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:02:36','2026-09-11 02:02:36'),(189,2,'ماهر خليفة','ماهر','خليفة','mahirkhalifa-trainer@edu-bridge.com','mahirkhalifa@gmail.com',NULL,'$2y$12$dwwCNyBvVLMpF4HN7Wc6tusVeviWF0cVvHhUEvhOahTWCvZ.8clli','098787676',NULL,NULL,NULL,'نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:04:22','2026-09-11 02:04:22'),(190,3,'احمد خليفة','احمد','خليفة','2026009','ahmadkhalifa@gmail.com',NULL,'$2y$12$f8Iv69FjwQOa7FNcDEMC7OC2wasnoSiVZmREC1CEYVidTRYmA1KpO','0987654565',NULL,NULL,'2026009','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2008-03-04','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-11 02:06:26','2026-09-11 02:06:26'),(191,3,'اكرم السيد','اكرم','السيد','2026010','aikramalsayid@gmail.com',NULL,'$2y$12$mP2lsJyO8Kda6bqSGjCKaOV66PKlYAy5P0YP9.wsgQQGZ77IIPSii','0989879876',NULL,NULL,'2026010','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2007-05-06','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-11 02:07:42','2026-09-11 02:07:42'),(192,3,'ربا عيسى','ربا','عيسى','2026011','rubaissa@gmail.com',NULL,'$2y$12$cxa2OXpOCitHo1GMis0kreQNlbDMGBYnNu0fUeF0efTcV2UdqpoGC','098767875643',NULL,NULL,'2026011','نظم المعلومات الحاسوبية',NULL,NULL,'أنثى','2008-03-05','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-11 02:15:28','2026-09-11 02:15:28'),(193,3,'لؤي الشيخ','لؤي','الشيخ','2026012','luayalshaykh@gmail.com',NULL,'$2y$12$beQ7vUaAhFI58MhAoc8jUOD9MgcKzAQk1x/B/QQcavihr2MBUPNIi','098789554',NULL,NULL,'2026012','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2007-05-06','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-11 02:16:39','2026-09-11 02:16:39'),(194,4,'ابو احمد خليفة','ابو احمد','خليفة','aboahamad@edu-bridge.com','aboahamad@gmail.com',NULL,'$2y$12$78CXcYB/45azacD1DU7Jr.e3zxT05Gszo45s2SW1bmQQLK0DeIO4u','0987111111',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:23:19','2026-09-11 02:23:19'),(195,4,'ابو اكرم السيد','ابو اكرم','السيد','aboakram@edu-bridge.com','aboakram@gmail.com',NULL,'$2y$12$iPfo.mIC76O0lajZL6erWegaEiApC.EtYD0RPVW/azXm1Ny2zNXC6','0987655232',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:24:32','2026-09-11 02:24:32'),(196,4,'ابو ربا عيسى','ابو ربا','عيسى','aboruba@edu-bridge.com','aboruba@gmail.com',NULL,'$2y$12$M.2fGQJQ11kj6Sq9mnPu/uLOyaKCE3Q8rCxynVhkCknS.9k6gBEDe','0989675432',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:25:51','2026-09-11 02:25:51'),(197,4,'ابو لؤي الشيخ','ابو لؤي','الشيخ','aboluay@edu-bridge.com','aboluay@gmail.com',NULL,'$2y$12$qoQPytikcskl1WORRXEMkesX0yFSAC12Fd/rupgmrCw1Ka2K2iP9u','0954768976',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:27:06','2026-09-11 02:27:06'),(198,3,'محمد ايوب','محمد','ايوب','2026013','mohmmadayoub@gmail.com',NULL,'$2y$12$PbTqlEZg7Lide0Ls727In.OrkO3uwnpPxdWodCxZ9iKajU/jBmgcq','00943536373',NULL,NULL,'2026013','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2008-09-06','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-11 02:28:37','2026-09-11 02:28:37'),(199,3,'خالد ايوب','خالد','ايوب','2026014','khaledayoub@gmail.com',NULL,'$2y$12$x5w9LEm5I0HPhI2OEh6jGeIE7hJX9d7HfWhFZwAVZ5EQbBD3xY60W','095477865344',NULL,NULL,'2026014','نظم المعلومات الحاسوبية',NULL,NULL,'ذكر','2007-09-09','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-11 02:30:26','2026-09-11 02:30:26'),(200,4,'ابو خالد ايوب','ابو خالد','ايوب','abokhaled@edu-bridge.com','abokhaled@gmail.com',NULL,'$2y$12$7M6b.C82CqzaodQcfahPB.3k68dXiFEesF74gJEqJlwtiiXfr1TQq','0945454545',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:31:34','2026-09-11 02:31:34'),(201,3,'رنا خليل','رنا','خليل','2026015','ranakhlail@gmail.com',NULL,'$2y$12$YZV2FasUbxQVQkL5FJqYGOTwTKRJqa5LUHqXk2EECuns13ipOncSS','0987878787',NULL,NULL,'2026015','نظم المعلومات الحاسوبية',NULL,NULL,'أنثى','2008-07-31','السنة الأولى','active',NULL,0,NULL,NULL,'2026-09-11 02:33:23','2026-09-11 02:33:23'),(202,4,'ابو رنا خليل','ابو رنا','خليل','aborana@edu-bridge.com','aborana@gmail.com',NULL,'$2y$12$TZ7uCPTi21rySTDUE0nyneZW6j3.nKC1PWReEtMfj7tvokKov55J2','09768765443',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:34:12','2026-09-11 02:34:12'),(203,4,'ابو ابراهيم يوسف','ابو ابراهيم','يوسف','aboyousefalahmad@edu-bridge.com','aboibrahem@gmail.com',NULL,'$2y$12$ASq3eLwnn07J4xTDFawV4.j0JdJx7uYXt1N8PdshkhkQlibJ19.8O','0987656565',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-11 02:36:13','2026-09-11 23:42:19'),(204,3,'ابراهيم يوسف','ابراهيم','يوسف','2026016','ibrahemyousef@gmail.com',NULL,'$2y$12$T96ErYqKrEApkDH77OMcxOlLLsUZKeHDRuQsbDJCJ7zjNasBiCaN2','0987654321',NULL,NULL,'2026016','نظم المعلومات الحاسوبية','ذكاء اصطناعي',NULL,'ذكر','2007-04-04','السنة الثانية','active',NULL,0,NULL,NULL,'2026-09-11 02:37:28','2026-09-11 22:28:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `admin_profile_stats_view`
--

/*!50001 DROP VIEW IF EXISTS `admin_profile_stats_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `admin_profile_stats_view` AS select (select count(0) from `users`) AS `total_users`,(select count(0) from `courses`) AS `total_courses` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `affairs_dashboard_stats_view`
--

/*!50001 DROP VIEW IF EXISTS `affairs_dashboard_stats_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `affairs_dashboard_stats_view` AS select (select count(0) from `users` where `users`.`role_id` = 3) AS `total_students`,(select count(0) from `users` where `users`.`role_id` = 2) AS `total_teachers`,(select count(0) from `users` where `users`.`role_id` in (2,5,6)) AS `total_staff`,(select count(0) from `leave_requests` where `leave_requests`.`status` in ('pending','pending_affairs')) AS `pending_leaves`,(select count(0) from `users`) AS `total_users` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `teacher_dashboard_stats_view`
--

/*!50001 DROP VIEW IF EXISTS `teacher_dashboard_stats_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `teacher_dashboard_stats_view` AS select `t`.`teacher_id` AS `teacher_id`,(select count(distinct `ct`.`course_id`) from `course_teachers` `ct` where `ct`.`teacher_id` = `t`.`teacher_id`) AS `courses_count`,(select count(distinct `a`.`assignment_id`) from (`assignments` `a` join `course_teachers` `ct` on(`a`.`course_id` = `ct`.`course_id`)) where `ct`.`teacher_id` = `t`.`teacher_id`) AS `active_assignments_count` from `teachers` `t` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-21 23:23:52
