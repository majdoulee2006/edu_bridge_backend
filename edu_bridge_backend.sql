-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: edu_bridge_backend
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_generated_reports`
--

LOCK TABLES `admin_generated_reports` WRITE;
/*!40000 ALTER TABLE `admin_generated_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_generated_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `admin_profile_stats_view`
--

DROP TABLE IF EXISTS `admin_profile_stats_view`;
/*!50001 DROP VIEW IF EXISTS `admin_profile_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `admin_profile_stats_view` AS SELECT 
 1 AS `total_users`,
 1 AS `total_courses`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
-- Temporary view structure for view `affairs_dashboard_stats_view`
--

DROP TABLE IF EXISTS `affairs_dashboard_stats_view`;
/*!50001 DROP VIEW IF EXISTS `affairs_dashboard_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `affairs_dashboard_stats_view` AS SELECT 
 1 AS `total_students`,
 1 AS `total_teachers`,
 1 AS `total_staff`,
 1 AS `pending_leaves`,
 1 AS `total_users`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `announcement_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT 'general',
  `image` varchar(255) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,1,'تم إصدار جدول الامتحانات النهائية للفصل الدراسي الأول','يرجى من جميع الطلاب مراجعة الجدول الدراسي والتأكد من توقيت الامتحانات والقاعات المخصصة.',NULL,'general',NULL,NULL,'general','all',NULL,NULL,NULL,NULL,'2026-09-06 17:30:55','2026-09-06 17:30:55',NULL,NULL,NULL),(2,1,'ورشة عمل حول مهارات البحث العلمي','ندعو جميع الطلاب للحضور والمشاركة في ورشة العمل التي ستقام في مبنى الأنشطة.',NULL,'general',NULL,NULL,'general','all',NULL,NULL,NULL,NULL,'2026-09-05 17:30:55','2026-09-05 17:30:55',NULL,NULL,NULL);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sessions`
--

LOCK TABLES `attendance_sessions` WRITE;
/*!40000 ALTER TABLE `attendance_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
INSERT INTO `cache` VALUES ('laravel-cache-system_setting_primary_color','s:7:\"#f2f20d\";',1788694514);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_program`
--

LOCK TABLES `course_program` WRITE;
/*!40000 ALTER TABLE `course_program` DISABLE KEYS */;
INSERT INTO `course_program` VALUES (1,1,1,NULL,NULL),(2,1,2,NULL,NULL),(3,2,1,NULL,NULL),(4,3,3,NULL,NULL),(5,4,3,NULL,NULL),(6,5,3,NULL,NULL),(7,6,3,NULL,NULL);
/*!40000 ALTER TABLE `course_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_teachers`
--

DROP TABLE IF EXISTS `course_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_teachers`
--

LOCK TABLES `course_teachers` WRITE;
/*!40000 ALTER TABLE `course_teachers` DISABLE KEYS */;
INSERT INTO `course_teachers` VALUES (1,1,1,'primary','2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,2,1,'primary','2026-09-06 17:30:56','2026-09-06 17:30:56');
/*!40000 ALTER TABLE `course_teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `course_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'أساسيات البرمجة','مقدمة في البرمجة','مبتدئ',0,1,1,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,'قواعد البيانات','تصميم قواعد البيانات','متوسط',0,1,1,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(3,'دورة نظم معلومات 1','دورة رقم 1 تابعة لقسم نظم المعلومات الحاسوبية','مبتدئ',3,1,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(4,'دورة نظم معلومات 2','دورة رقم 2 تابعة لقسم نظم المعلومات الحاسوبية','مبتدئ',3,1,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(5,'دورة نظم معلومات 3','دورة رقم 3 تابعة لقسم نظم المعلومات الحاسوبية','مبتدئ',3,1,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(6,'دورة نظم معلومات 4','دورة رقم 4 تابعة لقسم نظم المعلومات الحاسوبية','مبتدئ',3,1,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `department_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `offline_sync_policy` varchar(255) NOT NULL DEFAULT 'anytime',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'قسم هندسة البرمجيات','قسم يهتم بتطوير البرمجيات والتطبيقات','anytime','2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,'نظم المعلومات الحاسوبية','قسم نظم المعلومات الحاسوبية (CIS)','anytime','2026-09-06 17:31:00','2026-09-06 17:31:00');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (1,1,1,'2026-09-06','active','2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,2,3,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(3,3,3,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(4,4,3,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(5,5,3,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(6,6,3,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(7,7,4,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(8,8,4,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(9,9,4,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(10,10,4,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(11,11,4,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(12,12,5,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(13,13,5,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(14,14,5,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(15,15,5,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(16,16,5,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(17,17,6,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(18,18,6,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(19,19,6,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(20,20,6,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00'),(21,21,6,'2026-09-06','active','2026-09-06 17:31:00','2026-09-06 17:31:00');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_entries`
--

LOCK TABLES `grade_entries` WRITE;
/*!40000 ALTER TABLE `grade_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_events`
--

DROP TABLE IF EXISTS `grade_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_events`
--

LOCK TABLES `grade_events` WRITE;
/*!40000 ALTER TABLE `grade_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_report_requests`
--

DROP TABLE IF EXISTS `grade_report_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `heads`
--

LOCK TABLES `heads` WRITE;
/*!40000 ALTER TABLE `heads` DISABLE KEYS */;
/*!40000 ALTER TABLE `heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessons`
--

LOCK TABLES `lessons` WRITE;
/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES (1,1,'المحاضرة الأولى: المتغيرات','شرح أساسي للمتغيرات وأنواع البيانات',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56',1,NULL),(2,1,'المحاضرة الثانية: الشروط','If Statement والحلقات التكرارية',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56',1,NULL),(3,2,'مقدمة في الجداول','SQL Basics',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56',NULL,NULL);
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_26_144508_create_teachers_table',1),(5,'2026_03_26_144509_create_courses_table',1),(6,'2026_03_26_144511_create_courses_teachers_table',1),(7,'2026_03_26_144512_create_schedules_table',1),(8,'2026_03_26_144516_create_departments_tables',1),(9,'2026_03_26_144517_create_administrative_tables',1),(10,'2026_03_26_144519_create__student_table',1),(11,'2026_03_26_144520_create__enrollments_table',1),(12,'2026_03_26_160506_create__exams_table',1),(13,'2026_03_26_161020_create__grades_table',1),(14,'2026_03_26_162513_create_lessons_table',1),(15,'2026_03_26_162514_create_attendance_table',1),(16,'2026_03_26_162515_create_resources_table',1),(17,'2026_03_26_162837_create_announcements_table',1),(18,'2026_03_26_162849_create_assignments_table',1),(19,'2026_03_26_162902_create_assignment_submissions_table',1),(20,'2026_03_26_163452_create_reports_and_requests_tables',1),(21,'2026_03_26_163528_create_communication_tables',1),(22,'2026_03_26_170239_create_course_departments_pivot_table',1),(23,'2026_03_27_195456_create_personal_access_tokens_table',1),(24,'2026_03_27_203038_create_session_table',1),(25,'2026_03_28_105303_create_subjects_table',1),(26,'2026_03_28_195248_add_username_to_users_table',1),(27,'2026_04_03_225032_create_otps_table',1),(28,'2026_04_06_072056_create_notifications_table',1),(29,'2026_04_13_073654_create_messages_table',1),(30,'2026_04_16_074853_create_leave_requests_table',1),(31,'2026_04_16_080032_add_excuse_fields_to_attendance_table',1),(32,'2026_04_16_083430_create_attendance_sessions_table',1),(33,'2026_04_18_105343_add_report_type_to_performance_reports_table',1),(34,'2026_04_21_053852_create_messages_table',1),(35,'2026_04_22_000001_add_teacher_and_department_to_lessons_table',1),(36,'2026_04_23_072049_create_semesters_table',1),(37,'2026_04_23_072100_create_parent_students_table',1),(38,'2026_04_23_072109_create_roles_table',1),(39,'2026_04_23_072119_add_role_id_to_users_table',1),(40,'2026_04_23_072131_add_semester_id_to_courses_table',1),(41,'2026_04_23_072132_add_semester_id_to_enrollments_table',1),(42,'2026_04_23_072132_add_semester_id_to_exams_table',1),(43,'2026_04_23_072133_add_semester_id_to_assignments_table',1),(44,'2026_04_23_072135_add_semester_id_to_attendance_table',1),(45,'2026_04_26_182048_create_programs_table',1),(46,'2026_04_26_182053_create_course_program_table',1),(47,'2026_04_26_182059_update_courses_table_for_programs',1),(48,'2026_04_28_172404_cleanup_database_architecture',1),(49,'2026_05_01_151740_add_targeting_columns_to_announcements_table',1),(50,'2026_05_01_160007_add_avatar_to_users_table',1),(51,'2026_05_02_171000_update_hod_tables_for_linking',1),(52,'2026_05_04_230000_add_columns_to_schedules_and_exams',1),(53,'2026_05_06_192227_add_category_and_image_to_announcements_table',1),(54,'2026_05_06_194210_add_report_type_to_performance_reports_table',1),(55,'2026_05_06_194251_2026_05_02_171000_update_hod_tables_for_linking',1),(56,'2026_05_06_194327_add_columns_to_schedules_and_exams',1),(57,'2026_05_07_000001_add_missing_columns_to_lessons_table',1),(58,'2026_05_07_102059_add_category_and_sender_to_notifications_table',1),(59,'2026_05_08_000001_add_attachment_to_assignments_table',1),(60,'2026_05_08_000002_create_otp_codes_table',1),(61,'2026_05_08_000003_add_missing_columns_to_users_table',1),(62,'2026_05_09_131236_update_announcements_table_add_audience',1),(63,'2026_05_09_150627_add_image_path_to_announcements_table',1),(64,'2026_05_19_035741_create_head_schedule_entries_table',1),(65,'2026_05_19_035837_create_head_schedule_entries_table',1),(66,'2026_05_19_073023_add_device_token_to_users_table',1),(67,'2026_05_20_070311_add_file_path_to_assignments_table',1),(68,'2026_05_20_080838_add_file_fields_to_lessons_table',1),(69,'2026_05_21_000001_add_year_to_courses_table',1),(70,'2026_05_21_105015_add_notes_to_assignment_submissions_table',1),(71,'2026_05_23_add_course_year_to_report_requests',1),(72,'2026_05_23_add_related_id_to_notifications_table',1),(73,'2026_05_24_211054_add_link_url_to_announcements_table',1),(74,'2026_05_25_075706_create_calendar_events_table',1),(75,'2026_05_27_000000_add_hours_to_courses_table',1),(76,'2026_05_27_022103_add_telegram_chat_id_to_users_table',1),(77,'2026_05_29_134459_create_university_ids_table',1),(78,'2026_05_29_134756_add_university_id_to_users_table',1),(79,'2026_05_29_194317_add_reply_to_to_messages_table',1),(80,'2026_05_29_194532_create_groups_table',1),(81,'2026_05_29_194533_create_group_user_table',1),(82,'2026_05_29_194536_add_group_id_to_messages_table',1),(83,'2026_05_30_000001_add_device_lock_to_students_table',1),(84,'2026_05_30_000002_add_location_to_attendance_sessions_table',1),(85,'2026_05_30_000003_add_verification_fields_to_attendance_table',1),(86,'2026_05_30_100000_add_device_fields_to_students_table',1),(87,'2026_05_30_200000_add_device_fields_to_students_table',1),(88,'2026_05_31_004752_add_program_id_to_students_table',1),(89,'2026_06_03_161516_add_first_last_name_to_users_table',1),(90,'2026_06_08_130329_add_report_request_id_to_performance_reports_table',1),(91,'2026_06_09_074015_add_telegram_chat_id_to_users_table',1),(92,'2026_06_09_082812_add_advisor_fields_to_teachers_table',1),(93,'2026_06_09_095308_add_telegram_chat_id_to_university_ids_table',1),(94,'2026_06_09_230000_add_closed_at_to_attendance_sessions_table',1),(95,'2026_06_11_165538_add_sent_to_parent_to_report_requests_table',1),(96,'2026_06_17_000001_add_face_fields_for_attendance',1),(97,'2026_06_17_000002_fix_reject_reason_enum',1),(98,'2026_06_17_000003_fix_face_image_column_type',1),(99,'2026_06_19_000001_create_grade_events_table',1),(100,'2026_06_19_000002_create_grade_report_requests_table',1),(101,'2026_06_21_192810_add_oral_to_grade_events',1),(102,'2026_06_28_072234_add_offline_sync_policy_to_departments_table',1),(103,'2026_06_28_100000_add_photo_to_university_ids_table',1),(104,'2026_06_29_020000_add_extra_fields_to_university_ids_table',1),(105,'2026_06_29_023000_add_reference_photo_to_students_table',1),(106,'2026_07_03_000001_create_photo_change_requests_table',1),(107,'2026_07_04_000001_add_indexes_to_messages_table',1),(108,'2026_07_04_100000_create_quizzes_table',1),(109,'2026_07_05_152247_add_time_and_duration_to_grade_events_table',1),(110,'2026_07_15_083820_create_student_requests_table',1),(111,'2026_07_21_000001_add_telegram_id_to_users_and_parents_table',1),(112,'2026_07_21_132439_create_admin_generated_reports_table',1),(113,'2026_07_23_182956_add_is_delivered_to_messages_table',1),(114,'2026_08_02_000001_add_solution_text_to_assignment_submissions_table',1),(115,'2026_08_02_000002_add_notifications_muted_to_users_table',1),(116,'2026_08_02_000003_create_parent_meetings_and_summons_table',1),(117,'2026_08_02_080123_add_event_details_to_announcements_table',1),(118,'2026_08_03_195110_modify_category_column_in_announcements_table',1),(119,'2026_08_04_102340_add_notes_to_assignments_table',1),(120,'2026_08_05_000000_make_department_id_nullable_in_programs_table',1),(121,'2026_08_05_085500_make_teacher_id_nullable_in_report_requests_table',1),(122,'2026_08_05_230000_add_target_role_to_parent_meeting_requests_table',1),(123,'2026_08_12_074541_create_affairs_dashboard_stats_view',1),(124,'2026_08_12_101800_create_teacher_dashboard_stats_view',1),(125,'2026_08_12_221000_add_performance_indexes_to_tables',1),(126,'2026_08_12_222000_create_user_activities_table',1),(127,'2026_08_14_000001_add_deletion_flags_to_messages_table',1),(128,'2026_08_14_000002_add_ephemeral_and_forwarding_to_messages_table',1),(129,'2026_08_14_190000_update_status_column_in_absence_requests',1),(130,'2026_08_18_121500_create_admin_profile_stats_view',1),(131,'2026_08_18_123000_create_system_settings_table',1),(132,'2026_08_19_052012_add_department_id_to_calendar_events_table',1),(133,'2026_08_25_100000_add_missing_indexes_for_production',1),(134,'2026_09_01_000001_create_student_warnings_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `category` enum('academic','administrative') NOT NULL DEFAULT 'administrative',
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,4,NULL,'تنبيه غياب','نحيطكم علماً بغياب ابنكم عمر عن محاضرة البرمجة اليوم.','attendance',NULL,'administrative',0,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,2,NULL,'طلب إجازة جديد','هناك طلب إجازة معلق مقدم من المدرس سامر المحمد بانتظار موافقتك.','leave_request',NULL,'administrative',0,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(3,5,NULL,'تسليم واجب جديد','قام الطالب عمر الخالد بتسليم واجب \"تمارين المتغيرات\" في مادة أساسيات البرمجة.','academic',NULL,'administrative',0,'2026-09-06 17:00:56','2026-09-06 17:00:56'),(4,5,NULL,'جلسة حضور انتهت','انتهت جلسة تسجيل الحضور لمادة أساسيات البرمجة. حضر 18 من أصل 22 طالباً.','attendance',NULL,'administrative',0,'2026-09-06 15:30:56','2026-09-06 15:30:56'),(5,5,NULL,'تذكير: موعد تسليم الدرجات','تذكير من الإدارة: آخر موعد لرفع درجات الفصل الأول هو نهاية هذا الأسبوع.','administrative',NULL,'administrative',0,'2026-09-06 12:30:56','2026-09-06 12:30:56'),(6,5,NULL,'تحديث الجدول الدراسي','تم تعديل جدول محاضرات مادة قواعد البيانات ليوم الثلاثاء إلى قاعة 401.','administrative',NULL,'administrative',1,'2026-09-05 17:30:56','2026-09-05 17:30:56'),(7,5,NULL,'طلب تصحيح واجب','هناك 5 واجبات بانتظار التصحيح في مادة أساسيات البرمجة.','academic',NULL,'administrative',1,'2026-09-04 17:30:56','2026-09-04 17:30:56'),(8,3,NULL,'تم رفع وظيفة جديدة في مادة الرياضيات ','قام الأستاذ أحمد برفع وظيفة جديدة في مادة الرياضيات المتقدمة. يرجى التسليم قبل...','academic',NULL,'administrative',0,'2026-09-06 15:30:56','2026-09-06 17:30:56'),(9,3,NULL,'تعيين البرنامج الامتحاني','تم اعتماد جدول الامتحانات النصفية للفصل الدراسي الحالي، اضغط للتفاصيل.','academic',NULL,'administrative',1,'2026-09-06 12:30:56','2026-09-06 17:30:56'),(10,3,NULL,'تحديد عطلة رسمية','بمناسبة عيد المعلم، تعلن إدارة المعهد عن تعطيل الدوام الرسمي يوم الخميس القادم.','administrative',NULL,'administrative',0,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(11,3,NULL,'تنبيه اشتراك','يرجى المبادرة بتسديد القسط الجامعي الثاني قبل نهاية الشهر الحالي لتجنب الغرامات...','administrative',NULL,'administrative',1,'2026-09-02 17:30:56','2026-09-06 17:30:56');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_students`
--

LOCK TABLES `parent_students` WRITE;
/*!40000 ALTER TABLE `parent_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `parent_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_summons`
--

DROP TABLE IF EXISTS `parent_summons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parents` (
  `parent_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `telegram_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`parent_id`),
  KEY `parents_user_id_foreign` (`user_id`),
  CONSTRAINT `parents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parents`
--

LOCK TABLES `parents` WRITE;
/*!40000 ALTER TABLE `parents` DISABLE KEYS */;
INSERT INTO `parents` VALUES (1,4,'2026-09-06 17:30:56','2026-09-06 17:30:56',NULL);
/*!40000 ALTER TABLE `parents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_reports`
--

DROP TABLE IF EXISTS `performance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reports`
--

LOCK TABLES `performance_reports` WRITE;
/*!40000 ALTER TABLE `performance_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `photo_change_requests`
--

DROP TABLE IF EXISTS `photo_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'دبلوم تطوير الويب',NULL,NULL,NULL,1,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,'دبلوم الذكاء الاصطناعي',NULL,NULL,NULL,1,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(3,'برنامج نظم المعلومات',NULL,NULL,NULL,2,'2026-09-06 17:31:00','2026-09-06 17:31:00');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_options`
--

DROP TABLE IF EXISTS `quiz_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_requests`
--

LOCK TABLES `report_requests` WRITE;
/*!40000 ALTER TABLE `report_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,1,1,NULL,'الاثنين','08:00:00','09:30:00','قاعة 101','2026-09-06 17:30:56','2026-09-06 17:30:56'),(2,1,1,NULL,'الأربعاء','10:00:00','11:30:00','قاعة 101','2026-09-06 17:30:56','2026-09-06 17:30:56'),(3,1,1,NULL,'الخميس','14:00:00','15:30:00','قاعة 203','2026-09-06 17:30:56','2026-09-06 17:30:56'),(4,2,1,NULL,'الثلاثاء','09:00:00','10:30:00','قاعة 305','2026-09-06 17:30:56','2026-09-06 17:30:56'),(5,2,1,NULL,'الجمعة','08:00:00','09:30:00','قاعة 305','2026-09-06 17:30:56','2026-09-06 17:30:56');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semesters` (
  `semester_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`semester_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
INSERT INTO `semesters` VALUES (1,'الفصل الأول 2026','2026-09-01','2027-01-15',0,'2026-09-06 17:30:56','2026-09-06 17:30:56');
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,3,'2026100',NULL,0,'السنة الخامسة',NULL,NULL,0,'2002-05-20','2026-09-06 17:30:55','2026-09-06 17:30:55',NULL),(2,7,'2026200',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(3,8,'2026201',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(4,9,'2026202',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(5,10,'2026203',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(6,11,'2026204',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(7,12,'2026205',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(8,13,'2026206',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(9,14,'2026207',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(10,15,'2026208',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(11,16,'2026209',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(12,17,'2026210',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(13,18,'2026211',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(14,19,'2026212',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(15,20,'2026213',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(16,21,'2026214',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(17,22,'2026215',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(18,23,'2026216',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(19,24,'2026217',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(20,25,'2026218',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3),(21,26,'2026219',NULL,0,'السنة الأولى',NULL,NULL,0,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00',3);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
-- Temporary view structure for view `teacher_dashboard_stats_view`
--

DROP TABLE IF EXISTS `teacher_dashboard_stats_view`;
/*!50001 DROP VIEW IF EXISTS `teacher_dashboard_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `teacher_dashboard_stats_view` AS SELECT 
 1 AS `teacher_id`,
 1 AS `courses_count`,
 1 AS `active_assignments_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,5,'علوم الحاسوب','2026-09-06 17:30:56','2026-09-06 17:30:56',NULL,NULL,NULL);
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `university_ids`
--

DROP TABLE IF EXISTS `university_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activities`
--

LOCK TABLES `user_activities` WRITE;
/*!40000 ALTER TABLE `user_activities` DISABLE KEYS */;
INSERT INTO `user_activities` VALUES (1,7,'محمد المحمد','طالب','تسجيل دخول','تسجيل دخول ناجح','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-06 17:35:12','2026-09-06 17:35:12');
/*!40000 ALTER TABLE `user_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'إدارة المعهد التقني',NULL,NULL,'admin_main','admin@edu-bridge.com',NULL,'$2y$12$JVZJUdLy6g2eFqlnVS.xreNAPIEHfvjSlHAE9lgYaQA4q7QQX8yh.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:30:54','2026-09-06 17:30:54'),(2,5,'أحمد ديب',NULL,NULL,'ahmad_head','head@test.com',NULL,'$2y$12$9JRwdYyIxzEAKdVY.6RREeriBz86Jz6PgI2w/0ysLgcwJNmkk.Bfy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:30:55','2026-09-06 17:30:55'),(3,3,'عمر الخالد',NULL,NULL,'2026100','student@test.com',NULL,'$2y$12$r.V/H/VhLSoVX7FtVH6utuWF82/4o.0C1XAAxoBhkUNMGjWCb1sB2','0930000000',NULL,NULL,'2026100','هندسة حواسب وشبكات',NULL,NULL,'ذكر','2002-05-20','السنة الخامسة','active',NULL,0,NULL,NULL,'2026-09-06 17:30:55','2026-09-06 17:30:55'),(4,4,'أبو عمر الخالد',NULL,NULL,'098638799','parent@test.com',NULL,'$2y$12$z95Wu6VehkV.yFaTI26ZROUy3.nrcIV9hmowa1MSlJnW9nIfX5D3C','0986387993',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:30:55','2026-09-06 17:30:55'),(5,2,'د. سامر المحمد',NULL,NULL,'0986387992','teacher@test.com',NULL,'$2y$12$d6wNggwgvYxk29T.gXsdvu6agtDl1ujAgR2elVIsOidOTDRtaDjTa','0986387992',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(6,6,'محمد المحمد',NULL,NULL,'affairs_user','affairs@edu-bridge.com',NULL,'$2y$12$d8Cx8JmlNFal.tjBWirBrOVEZsyxTHNLm0TuBMKNYJ6xLneksYN0y',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:30:56','2026-09-06 17:30:56'),(7,3,'محمد المحمد','محمد','المحمد','student_2026200','student2026200@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026200','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(8,3,'أحمد الخالد','أحمد','الخالد','student_2026201','student2026201@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026201','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(9,3,'عمر العلي','عمر','العلي','student_2026202','student2026202@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026202','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(10,3,'خالد الحسن','خالد','الحسن','student_2026203','student2026203@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026203','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(11,3,'عبدالله العبدالله','عبدالله','العبدالله','student_2026204','student2026204@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026204','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(12,3,'فاطمة السالم','فاطمة','السالم','student_2026205','student2026205@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026205','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(13,3,'عائشة النجار','عائشة','النجار','student_2026206','student2026206@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026206','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(14,3,'مريم الحداد','مريم','الحداد','student_2026207','student2026207@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026207','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(15,3,'سارة العبيد','سارة','العبيد','student_2026208','student2026208@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026208','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(16,3,'نورة المحمود','نورة','المحمود','student_2026209','student2026209@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026209','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(17,3,'يوسف اليوسف','يوسف','اليوسف','student_2026210','student2026210@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026210','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(18,3,'طارق الطارق','طارق','الطارق','student_2026211','student2026211@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026211','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(19,3,'حسن الحسين','حسن','الحسين','student_2026212','student2026212@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026212','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(20,3,'حسين العمر','حسين','العمر','student_2026213','student2026213@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026213','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(21,3,'منى الصالح','منى','الصالح','student_2026214','student2026214@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026214','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(22,3,'ريم الأحمد','ريم','الأحمد','student_2026215','student2026215@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026215','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(23,3,'ندى الناصر','ندى','الناصر','student_2026216','student2026216@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026216','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(24,3,'محمود الدوسري','محمود','الدوسري','student_2026217','student2026217@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026217','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(25,3,'علي القحطاني','علي','القحطاني','student_2026218','student2026218@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026218','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00'),(26,3,'ليلى الزهراني','ليلى','الزهراني','student_2026219','student2026219@cis.edu',NULL,'$2y$12$ds0n.Fh/BiJxmlao4RmH1O3ZT.YD10n0ZqvJOlj18EsXeyEMVWTxC',NULL,NULL,NULL,'2026219','نظم المعلومات الحاسوبية',NULL,NULL,NULL,NULL,NULL,'active',NULL,0,NULL,NULL,'2026-09-06 17:31:00','2026-09-06 17:31:00');
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

-- Dump completed on 2026-09-06  3:45:06
