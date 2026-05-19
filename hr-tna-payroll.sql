CREATE DATABASE  IF NOT EXISTS `hr-tna-payroll` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `hr-tna-payroll`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: hr-tna-payroll
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `allowances`
--

DROP TABLE IF EXISTS `allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `allowances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `allowance_type` varchar(80) NOT NULL COMMENT 'e.g. Transportation, Meal, Clothing',
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `frequency` enum('Monthly','Per Payroll','Annually','One-time') NOT NULL DEFAULT 'Monthly',
  `is_taxable` tinyint NOT NULL DEFAULT '0',
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allowances`
--

LOCK TABLES `allowances` WRITE;
/*!40000 ALTER TABLE `allowances` DISABLE KEYS */;
/*!40000 ALTER TABLE `allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=Present, 2=Late, 3=Absent, 4=Excused',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_att_user_date` (`user_id`,`attendance_date`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,4,'2026-05-19','2026-05-19 09:00:00','2026-05-19 18:00:00',1,NULL,'2026-05-19 02:07:27','2026-05-19 02:07:27'),(2,4,'2026-05-19','2026-05-19 07:55:00','2026-05-19 17:05:00',1,'On time','2026-05-19 10:13:43','2026-05-19 10:13:43'),(3,4,'2026-05-20','2026-05-20 08:37:00','2026-05-20 17:00:00',2,'Late arrival','2026-05-19 10:13:43','2026-05-19 10:13:43'),(4,4,'2026-05-21',NULL,NULL,3,'Absent','2026-05-19 10:13:43','2026-05-19 10:13:43'),(5,4,'2026-05-22','2026-05-22 07:58:00','2026-05-22 16:15:00',1,'Undertime example','2026-05-19 10:13:43','2026-05-19 10:13:43'),(6,4,'2026-05-23','2026-05-23 08:00:00','2026-05-23 19:30:00',1,'Overtime example','2026-05-19 10:13:43','2026-05-19 10:13:43'),(7,4,'2026-05-19','2026-05-19 07:55:00','2026-05-19 17:05:00',1,'On time','2026-05-19 10:14:48','2026-05-19 10:14:48'),(8,4,'2026-05-20','2026-05-20 08:37:00','2026-05-20 17:00:00',2,'Late arrival','2026-05-19 10:14:48','2026-05-19 10:14:48'),(9,4,'2026-05-21',NULL,NULL,3,'Absent','2026-05-19 10:14:48','2026-05-19 10:14:48'),(10,4,'2026-05-22','2026-05-22 07:58:00','2026-05-22 16:15:00',1,'Undertime example','2026-05-19 10:14:48','2026-05-19 10:14:48'),(11,4,'2026-05-23','2026-05-23 08:00:00','2026-05-23 19:30:00',1,'Overtime example','2026-05-19 10:14:48','2026-05-19 10:14:48');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefit_dependents`
--

DROP TABLE IF EXISTS `benefit_dependents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `benefit_dependents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_id` int unsigned NOT NULL,
  `full_name` varchar(160) NOT NULL,
  `relationship` varchar(60) NOT NULL COMMENT 'e.g. Spouse, Child, Parent',
  `birth_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit_dependents`
--

LOCK TABLES `benefit_dependents` WRITE;
/*!40000 ALTER TABLE `benefit_dependents` DISABLE KEYS */;
/*!40000 ALTER TABLE `benefit_dependents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefit_eligibility`
--

DROP TABLE IF EXISTS `benefit_eligibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `benefit_eligibility` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int unsigned NOT NULL,
  `employment_type` enum('Full-time','Part-time','Contractual','Intern') DEFAULT NULL COMMENT 'NULL = all types',
  `min_tenure_months` int NOT NULL DEFAULT '0',
  `eligible_departments` text COMMENT 'JSON array of dept IDs, NULL = all',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit_eligibility`
--

LOCK TABLES `benefit_eligibility` WRITE;
/*!40000 ALTER TABLE `benefit_eligibility` DISABLE KEYS */;
/*!40000 ALTER TABLE `benefit_eligibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefit_enrollments`
--

DROP TABLE IF EXISTS `benefit_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `benefit_enrollments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `plan_id` int unsigned NOT NULL,
  `enrollment_date` date NOT NULL,
  `coverage_start` date NOT NULL,
  `coverage_end` date DEFAULT NULL,
  `status` int NOT NULL DEFAULT '3' COMMENT '1=Active, 2=Terminated, 3=Pending',
  `enrolled_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_be_emp_status` (`employee_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit_enrollments`
--

LOCK TABLES `benefit_enrollments` WRITE;
/*!40000 ALTER TABLE `benefit_enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `benefit_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefit_plans`
--

DROP TABLE IF EXISTS `benefit_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `benefit_plans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `benefit_type` varchar(80) NOT NULL COMMENT 'e.g. Health Insurance, Retirement, Allowance, Flexible',
  `provider` varchar(120) DEFAULT NULL,
  `coverage_details` text,
  `employer_cost` decimal(10,2) DEFAULT NULL,
  `employee_cost` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit_plans`
--

LOCK TABLES `benefit_plans` WRITE;
/*!40000 ALTER TABLE `benefit_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `benefit_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `break_logs`
--

DROP TABLE IF EXISTS `break_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `break_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `time_log_id` int unsigned NOT NULL,
  `break_start` datetime NOT NULL,
  `break_end` datetime DEFAULT NULL,
  `break_type` enum('Lunch','Rest','Other') NOT NULL DEFAULT 'Lunch',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `break_logs`
--

LOCK TABLES `break_logs` WRITE;
/*!40000 ALTER TABLE `break_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `break_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
-- Table structure for table `dashboard_widgets`
--

DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int unsigned DEFAULT NULL COMMENT 'NULL = system default',
  `widget_type` varchar(60) NOT NULL COMMENT 'e.g. bar_chart, kpi_card, table',
  `metric_key` varchar(80) NOT NULL COMMENT 'e.g. headcount, attrition_rate, overtime_hours',
  `title` varchar(120) DEFAULT NULL,
  `config_json` json DEFAULT NULL COMMENT 'Filters, date range, display options',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_widgets`
--

LOCK TABLES `dashboard_widgets` WRITE;
/*!40000 ALTER TABLE `dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deduction_rules`
--

DROP TABLE IF EXISTS `deduction_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deduction_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `type` enum('Fixed','Percentage','Prorated') NOT NULL DEFAULT 'Fixed',
  `amount` decimal(10,2) DEFAULT NULL,
  `rate` decimal(5,4) DEFAULT NULL,
  `scope` varchar(120) DEFAULT NULL,
  `description` text,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deduction_rules`
--

LOCK TABLES `deduction_rules` WRITE;
/*!40000 ALTER TABLE `deduction_rules` DISABLE KEYS */;
INSERT INTO `deduction_rules` VALUES (1,'Late Deduction','Prorated',NULL,0.0010,'Attendance linked',NULL,1,0,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(2,'Absent Deduction','Fixed',1000.00,NULL,'Attendance linked',NULL,1,1,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL);
/*!40000 ALTER TABLE `deduction_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `parent_dept_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Human Resources',NULL,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(2,'Operations',NULL,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(3,'Information Technology',NULL,'2026-05-19 04:35:27','2026-05-19 04:35:27');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `full_name` varchar(160) NOT NULL,
  `relationship` varchar(60) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `alt_phone` varchar(30) DEFAULT NULL,
  `address` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_contacts`
--

LOCK TABLES `emergency_contacts` WRITE;
/*!40000 ALTER TABLE `emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_deduction_rule`
--

DROP TABLE IF EXISTS `employee_deduction_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_deduction_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `deduction_rule_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_deduction_rule_employee_id_deduction_rule_id_unique` (`employee_id`,`deduction_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_deduction_rule`
--

LOCK TABLES `employee_deduction_rule` WRITE;
/*!40000 ALTER TABLE `employee_deduction_rule` DISABLE KEYS */;
INSERT INTO `employee_deduction_rule` VALUES (1,1,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(2,1,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(5,3,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(6,3,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(7,4,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(8,4,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(9,5,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(10,5,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(11,6,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(12,6,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(13,7,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(14,7,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(15,8,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(16,8,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(17,9,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(18,9,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(19,10,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(20,10,2,'2026-05-18 20:35:29','2026-05-18 20:35:29');
/*!40000 ALTER TABLE `employee_deduction_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `doc_type` varchar(80) NOT NULL COMMENT 'e.g. Contract, NBI Clearance, Diploma',
  `file_name` varchar(200) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_size_kb` int unsigned DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_by` int unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_documents`
--

LOCK TABLES `employee_documents` WRITE;
/*!40000 ALTER TABLE `employee_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_government_contribution`
--

DROP TABLE IF EXISTS `employee_government_contribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_government_contribution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `government_contribution_rate_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_gov_contrib_unique` (`employee_id`,`government_contribution_rate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_government_contribution`
--

LOCK TABLES `employee_government_contribution` WRITE;
/*!40000 ALTER TABLE `employee_government_contribution` DISABLE KEYS */;
INSERT INTO `employee_government_contribution` VALUES (1,1,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(2,1,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(3,1,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(4,2,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(5,2,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(6,2,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(7,3,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(8,3,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(9,3,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(10,4,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(11,4,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(12,4,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(13,5,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(14,5,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(15,5,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(16,6,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(17,6,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(18,6,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(19,7,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(20,7,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(21,7,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(22,8,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(23,8,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(24,8,3,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(25,9,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(26,9,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(27,9,3,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(28,10,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(29,10,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(30,10,3,'2026-05-18 20:35:29','2026-05-18 20:35:29');
/*!40000 ALTER TABLE `employee_government_contribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_plottings`
--

DROP TABLE IF EXISTS `employee_plottings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_plottings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`employee_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_plottings`
--

LOCK TABLES `employee_plottings` WRITE;
/*!40000 ALTER TABLE `employee_plottings` DISABLE KEYS */;
INSERT INTO `employee_plottings` VALUES (1,5,2,'2026-05-18','Manila Zoo',454.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(2,5,2,'2026-05-19','Manila Zoo',470.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(3,5,2,'2026-05-20','SM',540.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(4,5,2,'2026-05-21','Manila Zoo',537.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(5,5,2,'2026-05-22','Manila Zoo',463.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(6,6,2,'2026-05-18','Manila Zoo',565.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(7,6,3,'2026-05-19','Robinsons Mall',650.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(8,6,2,'2026-05-20','SM',578.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(9,6,2,'2026-05-21','Manila Zoo',589.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(10,6,2,'2026-05-22','Manila Zoo',636.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(11,7,3,'2026-05-18','Robinsons Mall',469.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(12,7,3,'2026-05-19','Robinsons Mall',429.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(13,7,3,'2026-05-20','SM',432.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(14,7,3,'2026-05-21','Robinsons Mall',471.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(15,7,3,'2026-05-22','Robinsons Mall',491.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(16,8,3,'2026-05-18','Robinsons Mall',595.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(17,8,3,'2026-05-19','Robinsons Mall',553.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(18,8,3,'2026-05-20','SM',519.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(19,8,3,'2026-05-21','Robinsons Mall',596.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(20,8,3,'2026-05-22','Robinsons Mall',533.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(21,9,4,'2026-05-18','IT Park',727.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(22,9,4,'2026-05-19','IT Park',692.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(23,9,4,'2026-05-21','IT Park',658.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(24,9,4,'2026-05-22','IT Park',743.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(25,10,4,'2026-05-18','IT Park',677.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(26,10,4,'2026-05-19','IT Park',630.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(27,10,4,'2026-05-20','SM',661.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(28,10,4,'2026-05-21','IT Park',605.00,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(29,10,4,'2026-05-22','IT Park',648.00,'2026-05-18 20:35:29','2026-05-18 20:35:29');
/*!40000 ALTER TABLE `employee_plottings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_tax_bracket`
--

DROP TABLE IF EXISTS `employee_tax_bracket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_tax_bracket` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `tax_bracket_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_tax_bracket_employee_id_tax_bracket_id_unique` (`employee_id`,`tax_bracket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_tax_bracket`
--

LOCK TABLES `employee_tax_bracket` WRITE;
/*!40000 ALTER TABLE `employee_tax_bracket` DISABLE KEYS */;
INSERT INTO `employee_tax_bracket` VALUES (1,1,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(2,1,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(3,1,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(4,1,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(5,1,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(6,1,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(9,2,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(13,3,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(14,3,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(15,3,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(16,3,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(17,3,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(18,3,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(19,4,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(20,4,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(21,4,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(22,4,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(23,4,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(24,4,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(25,5,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(26,5,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(27,5,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(28,5,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(29,5,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(30,5,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(31,6,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(32,6,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(33,6,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(34,6,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(35,6,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(36,6,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(37,7,1,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(38,7,2,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(39,7,3,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(40,7,4,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(41,7,5,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(42,7,6,'2026-05-18 20:35:28','2026-05-18 20:35:28'),(43,8,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(44,8,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(45,8,3,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(46,8,4,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(47,8,5,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(48,8,6,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(49,9,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(50,9,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(51,9,3,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(52,9,4,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(53,9,5,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(54,9,6,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(55,10,1,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(56,10,2,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(57,10,3,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(58,10,4,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(59,10,5,'2026-05-18 20:35:29','2026-05-18 20:35:29'),(60,10,6,'2026-05-18 20:35:29','2026-05-18 20:35:29');
/*!40000 ALTER TABLE `employee_tax_bracket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(30) NOT NULL COMMENT 'e.g. EMP-0001',
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('Male','Female','Non-binary','Prefer not to say') DEFAULT NULL,
  `nationality` varchar(80) DEFAULT NULL,
  `marital_status` enum('Single','Married','Widowed','Divorced','Separated') DEFAULT NULL,
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(80) DEFAULT 'Philippines',
  `status` int NOT NULL DEFAULT '2' COMMENT '1=Active, 2=Probationary, 3=On Leave, 4=Resigned, 5=Terminated',
  `employment_type` int DEFAULT '1',
  `hire_date` date NOT NULL,
  `regularization_date` date DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text,
  `position_id` int unsigned DEFAULT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `manager_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_emp_status` (`status`),
  KEY `idx_emp_dept` (`department_id`),
  KEY `idx_emp_manager` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'JP001','Joefhanie','Perez','Cruz','hr@example.com','+63 917 123 4567','1988-05-12','Female','Filipino','Single','888 Taft Ave',NULL,'Manila','Metro Manila','1000','Philippines',1,1,'2020-01-15',NULL,NULL,NULL,1,1,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(2,'AD002','Andrei','Dilag',NULL,'sv1@example.com','+63 917 222 0001','1985-08-20','Male','Filipino','Married','Supervisor St',NULL,'Makati','Metro Manila','1200','Philippines',1,1,'2021-06-10',NULL,NULL,NULL,2,2,1,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(3,'RV003','Ramon','Valenzuela',NULL,'sv2@example.com','+63 917 222 0002','1985-08-20','Male','Filipino','Married','Supervisor St',NULL,'Makati','Metro Manila','1200','Philippines',1,1,'2021-06-10',NULL,NULL,NULL,3,2,1,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(4,'MS004','Maria Clara','Santos',NULL,'sv3@example.com','+63 917 222 0003','1985-08-20','Female','Filipino','Married','Supervisor St',NULL,'Makati','Metro Manila','1200','Philippines',1,1,'2021-06-10',NULL,NULL,NULL,4,3,1,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(5,'JD005','Juan','dela Cruz',NULL,'emp1@example.com','+63 917 333 0001','1995-10-05','Male','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,5,2,2,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(6,'PM006','Princess','Mendoza',NULL,'emp2@example.com','+63 917 333 0002','1995-10-05','Female','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,5,2,2,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(7,'JM007','Jose Rizal','Macaraeg',NULL,'emp3@example.com','+63 917 333 0003','1995-10-05','Male','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,6,2,3,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(8,'AP008','Arnel','Pineda',NULL,'emp4@example.com','+63 917 333 0004','1995-10-05','Female','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,6,2,3,'2026-05-19 04:35:29','2026-05-19 04:35:29'),(9,'GA009','Gloc Nine','Alimario',NULL,'emp5@example.com','+63 917 333 0005','1995-10-05','Male','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,7,3,4,'2026-05-19 04:35:29','2026-05-19 04:35:29'),(10,'CG010','Catriona','Gray',NULL,'emp6@example.com','+63 917 333 0006','1995-10-05','Female','Filipino','Single','Employee Rd',NULL,'Quezon City','Metro Manila','1100','Philippines',1,1,'2023-01-10',NULL,NULL,NULL,7,3,4,'2026-05-19 04:35:29','2026-05-19 04:35:29');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
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
-- Table structure for table `government_contribution_rates`
--

DROP TABLE IF EXISTS `government_contribution_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `government_contribution_rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `employee_rate` decimal(5,4) NOT NULL,
  `employer_rate` decimal(5,4) NOT NULL,
  `description` text,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `government_contribution_rates`
--

LOCK TABLES `government_contribution_rates` WRITE;
/*!40000 ALTER TABLE `government_contribution_rates` DISABLE KEYS */;
INSERT INTO `government_contribution_rates` VALUES (1,'SSS',0.0450,0.0950,NULL,1,0,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(2,'PhilHealth',0.0250,0.0250,NULL,1,1,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(3,'Pag-IBIG',0.0200,0.0200,NULL,1,2,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL);
/*!40000 ALTER TABLE `government_contribution_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `government_contributions`
--

DROP TABLE IF EXISTS `government_contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `government_contributions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` int unsigned NOT NULL,
  `contribution_type` varchar(60) NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG',
  `employee_share` decimal(10,2) NOT NULL DEFAULT '0.00',
  `employer_share` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `government_contributions`
--

LOCK TABLES `government_contributions` WRITE;
/*!40000 ALTER TABLE `government_contributions` DISABLE KEYS */;
INSERT INTO `government_contributions` VALUES (1,1,'SSS',0.00,0.00),(2,2,'SSS',0.00,0.00),(3,3,'SSS',0.00,0.00),(4,5,'SSS',450.00,750.00),(5,6,'SSS',72.00,120.00),(6,7,'SSS',375.00,625.00),(7,9,'SSS',450.00,750.00),(8,10,'SSS',72.00,120.00),(9,11,'SSS',375.00,625.00),(10,13,'SSS',875.00,1750.00),(11,13,'PhilHealth',437.50,437.50),(12,13,'Pag-IBIG',350.00,350.00),(13,14,'SSS',3600.00,7600.00),(14,14,'PhilHealth',2000.00,2000.00),(15,14,'Pag-IBIG',1600.00,1600.00),(16,15,'SSS',1282.50,2707.50),(17,15,'PhilHealth',712.50,712.50),(18,15,'Pag-IBIG',570.00,570.00),(19,16,'SSS',2828.57,5971.43),(20,16,'PhilHealth',1571.43,1571.43),(21,16,'Pag-IBIG',1257.14,1257.14),(22,17,'SSS',3214.29,6785.71),(23,17,'PhilHealth',1785.71,1785.71),(24,17,'Pag-IBIG',1428.57,1428.57),(25,18,'SSS',1414.29,2985.71),(26,18,'PhilHealth',785.71,785.71),(27,18,'Pag-IBIG',628.57,628.57),(28,19,'SSS',1414.29,2985.71),(29,19,'PhilHealth',785.71,785.71),(30,19,'Pag-IBIG',628.57,628.57),(31,20,'SSS',1414.29,2985.71),(32,20,'PhilHealth',785.71,785.71),(33,20,'Pag-IBIG',628.57,628.57),(34,21,'SSS',1414.29,2985.71),(35,21,'PhilHealth',785.71,785.71),(36,21,'Pag-IBIG',628.57,628.57),(37,22,'SSS',1800.00,3800.00),(38,22,'PhilHealth',1000.00,1000.00),(39,22,'Pag-IBIG',800.00,800.00),(40,23,'SSS',1800.00,3800.00),(41,23,'PhilHealth',1000.00,1000.00),(42,23,'Pag-IBIG',800.00,800.00);
/*!40000 ALTER TABLE `government_contributions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `government_ids`
--

DROP TABLE IF EXISTS `government_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `government_ids` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `id_type` varchar(60) NOT NULL COMMENT 'e.g. SSS, PhilHealth, Pag-IBIG, TIN, Passport',
  `id_number` varchar(80) NOT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `government_ids`
--

LOCK TABLES `government_ids` WRITE;
/*!40000 ALTER TABLE `government_ids` DISABLE KEYS */;
/*!40000 ALTER TABLE `government_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday_calendars`
--

DROP TABLE IF EXISTS `holiday_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holiday_calendars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `year` year NOT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'PH',
  `is_active` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday_calendars`
--

LOCK TABLES `holiday_calendars` WRITE;
/*!40000 ALTER TABLE `holiday_calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday_calendars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `leave_type_id` int unsigned NOT NULL,
  `year` year NOT NULL,
  `entitled_days` decimal(5,1) NOT NULL DEFAULT '0.0',
  `used_days` decimal(5,1) NOT NULL DEFAULT '0.0',
  `accrued_days` decimal(5,1) NOT NULL DEFAULT '0.0',
  `carried_over` decimal(5,1) NOT NULL DEFAULT '0.0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lb_emp_type_year` (`employee_id`,`leave_type_id`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `leave_type_id` int unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` decimal(5,1) NOT NULL,
  `reason` text,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1=Pending, 2=Approved, 3=Rejected, 4=Cancelled',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_note` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lr_emp_status` (`employee_id`,`status`),
  KEY `idx_lr_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `code` varchar(20) NOT NULL COMMENT 'e.g. VL, SL, PL, ML, PaTL',
  `is_paid` tinyint NOT NULL DEFAULT '1',
  `max_days_per_year` decimal(5,1) DEFAULT NULL,
  `is_accrued` tinyint NOT NULL DEFAULT '0',
  `accrual_rate` decimal(5,2) DEFAULT NULL COMMENT 'Days accrued per month',
  `requires_approval` tinyint NOT NULL DEFAULT '1',
  `min_notice_days` int NOT NULL DEFAULT '0',
  `is_active` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_05_19_015331_add_daily_divisor_to_salary_records_table',1),(2,'0001_01_01_000000_create_users_table',2),(3,'0001_01_01_000001_create_cache_table',2),(4,'0001_01_01_000002_create_jobs_table',2),(5,'2026_05_13_000000_create_salary_settings_tables',2),(6,'2026_05_14_000000_add_username_to_users_table',3),(7,'2026_05_14_000001_create_employees_table',3),(8,'2026_05_14_000001_create_sessions_table',3),(9,'2026_05_14_000002_create_emergency_contacts_table',3),(10,'2026_05_14_000002_create_leaves_table',4),(11,'2026_05_14_000003_create_employee_documents_table',4),(12,'2026_05_14_000003_create_payrolls_table',5),(13,'2026_05_14_000004_add_employee_fields_to_users_table',6),(14,'2026_05_14_000004_create_salary_records_table',6),(15,'2026_05_14_000005_create_attendance_table',6),(16,'2026_05_14_000006_convert_leaves_status_to_integer',6),(17,'2026_05_14_000006_create_pay_runs_table',6),(18,'2026_05_14_000007_convert_payrolls_status_to_integer',6),(19,'2026_05_14_000007_create_payslips_table',6),(20,'2026_05_14_000008_convert_attendance_status_to_integer',6),(21,'2026_05_14_000008_create_payslip_line_items_table',6),(22,'2026_05_14_000009_create_government_contributions_table',6),(23,'2026_05_15_000000_add_timestamps_to_pay_runs_table',6),(24,'2026_05_15_000001_add_timestamps_to_payslips_table',6),(25,'2026_05_15_000002_convert_enums_to_int',6),(26,'2026_05_15_000003_add_timestamps_to_salary_records_table',6),(27,'2026_05_15_090633_create_employee_tax_deduction_pivots',6),(28,'2026_05_18_000001_add_role_to_users_table',6),(29,'2026_05_18_000002_drop_users_table',6),(30,'2026_05_18_000003_drop_all_foreign_keys',6),(31,'2026_05_18_100000_create_plotting_tables',7),(32,'2026_05_18_200000_add_departments_and_positions_tables',7),(33,'2026_05_19_000000_seed_default_government_contribution_rates',7),(34,'2026_05_19_000001_seed_default_tax_brackets',8),(35,'2026_05_19_015332_add_attendance_rate_overrides_to_salary_records_table',9),(36,'2026_05_19_020000_create_payroll_settings_table',9);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipient_id` int unsigned NOT NULL,
  `type` varchar(60) NOT NULL COMMENT 'e.g. leave_approved, payslip_ready, task_due',
  `title` varchar(160) NOT NULL,
  `message` text,
  `link` varchar(300) DEFAULT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_unread` (`recipient_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_runs`
--

DROP TABLE IF EXISTS `pay_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pay_runs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL COMMENT 'e.g. June 2026 - 1st Half',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `frequency` int DEFAULT '4',
  `status` int DEFAULT '1',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `finalized_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_runs`
--

LOCK TABLES `pay_runs` WRITE;
/*!40000 ALTER TABLE `pay_runs` DISABLE KEYS */;
INSERT INTO `pay_runs` VALUES (1,'May 01 - May 15, 2026','2026-05-01','2026-05-15','2026-05-15',4,13,2,'2026-05-19 01:46:50','2026-05-19 03:48:33',NULL),(2,'May 16 - May 31, 2026','2026-05-16','2026-05-31','2026-05-31',4,13,2,'2026-05-19 01:47:18','2026-05-19 03:48:38',NULL),(3,'May 16 - May 31, 2026','2026-05-16','2026-05-31','2026-05-31',4,13,2,'2026-05-19 02:27:35','2026-05-19 03:48:41',NULL),(4,'May 16 - May 31, 2026','2026-05-16','2026-05-31','2026-05-31',4,13,2,'2026-05-19 03:48:58','2026-05-19 05:10:27',NULL),(5,'May 16 - May 31, 2026','2026-05-16','2026-05-31','2026-05-31',4,2,1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL);
/*!40000 ALTER TABLE `pay_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_settings`
--

DROP TABLE IF EXISTS `payroll_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_overtime_multiplier` decimal(8,4) NOT NULL DEFAULT '1.2500',
  `attendance_night_differential_multiplier` decimal(8,4) NOT NULL DEFAULT '0.1000',
  `attendance_late_deduction_multiplier` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `attendance_undertime_deduction_multiplier` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `attendance_absence_deduction_multiplier` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_settings`
--

LOCK TABLES `payroll_settings` WRITE;
/*!40000 ALTER TABLE `payroll_settings` DISABLE KEYS */;
INSERT INTO `payroll_settings` VALUES (1,1.2500,0.1000,1.0000,1.0000,1.0000,'2026-05-18 20:35:27','2026-05-18 20:35:27');
/*!40000 ALTER TABLE `payroll_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payrolls`
--

DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payrolls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `payroll_date` date NOT NULL,
  `gross_salary` decimal(12,2) NOT NULL,
  `deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(12,2) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1=processing, 2=completed, 3=failed',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payrolls_user_id` (`user_id`),
  KEY `idx_payrolls_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payrolls`
--

LOCK TABLES `payrolls` WRITE;
/*!40000 ALTER TABLE `payrolls` DISABLE KEYS */;
/*!40000 ALTER TABLE `payrolls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_line_items`
--

DROP TABLE IF EXISTS `payslip_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_line_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payslip_id` int unsigned NOT NULL,
  `component_type` int NOT NULL,
  `description` varchar(120) NOT NULL COMMENT 'e.g. Basic Pay, Overtime, SSS, PhilHealth, Pag-IBIG, Withholding Tax',
  `amount` decimal(14,2) NOT NULL,
  `is_taxable` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_pli_payslip` (`payslip_id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_line_items`
--

LOCK TABLES `payslip_line_items` WRITE;
/*!40000 ALTER TABLE `payslip_line_items` DISABLE KEYS */;
INSERT INTO `payslip_line_items` VALUES (1,1,1,'Base salary',0.00,1),(2,1,1,'SSS',0.00,0),(3,2,1,'Base salary',0.00,1),(4,2,1,'SSS',0.00,0),(5,3,1,'Base salary',0.00,1),(6,3,1,'SSS',0.00,0),(7,4,1,'Base salary',0.00,1),(8,5,1,'Base salary',15000.00,1),(9,5,1,'SSS',450.00,0),(10,6,1,'Base salary',2400.00,1),(11,6,1,'SSS',72.00,0),(12,7,1,'Base salary',12500.00,1),(13,7,1,'SSS',375.00,0),(14,8,1,'Base salary',15000.00,1),(15,1,1,'Basic Pay',15000.00,1),(16,1,1,'Overtime Pay',850.00,1),(17,1,1,'Night Differential / Premium',300.00,1),(18,1,1,'Late Differential Bonus',150.00,1),(19,1,1,'Late Deduction',180.00,0),(20,1,1,'Undertime Deduction',450.00,0),(21,1,1,'Absence Deduction',750.00,0),(22,1,1,'Basic Pay',15000.00,1),(23,1,1,'Overtime Pay',850.00,1),(24,1,1,'Night Differential / Premium',300.00,1),(25,1,1,'Late Differential Bonus',150.00,1),(26,1,1,'Late Deduction',180.00,0),(27,1,1,'Undertime Deduction',450.00,0),(28,1,1,'Absence Deduction',750.00,0),(29,9,1,'Base salary',15000.00,1),(30,9,1,'SSS',450.00,0),(31,10,1,'Base salary',2400.00,1),(32,10,1,'SSS',72.00,0),(33,11,1,'Base salary',12500.00,1),(34,11,1,'Attendance: Overtime Pay',752.80,1),(35,11,1,'Attendance: Night Differential',29.30,1),(36,11,1,'Attendance: Late Deduction',218.11,0),(37,11,1,'Attendance: Undertime Deduction',146.49,0),(38,11,1,'Attendance: Absence Deduction',1562.50,0),(39,11,1,'SSS',375.00,0),(40,12,1,'Base salary',15000.00,1),(41,13,1,'Base salary',17500.00,1),(42,13,1,'Attendance: Overtime Pay',1053.88,1),(43,13,1,'Attendance: Night Differential',41.02,1),(44,13,2,'Attendance: Late Deduction',305.34,0),(45,13,2,'Attendance: Undertime Deduction',205.08,0),(46,13,2,'Attendance: Absence Deduction',2187.50,0),(47,13,4,'SSS',875.00,0),(48,13,4,'PhilHealth',437.50,0),(49,13,4,'Pag-IBIG',350.00,0),(50,14,1,'Base salary',80000.00,1),(51,14,3,'Income Tax',11875.05,0),(52,14,4,'SSS',3600.00,0),(53,14,4,'PhilHealth',2000.00,0),(54,14,4,'Pag-IBIG',1600.00,0),(55,14,2,'Absent Deduction',1000.00,0),(56,15,1,'Base salary',28500.00,1),(57,15,4,'SSS',1282.50,0),(58,15,4,'PhilHealth',712.50,0),(59,15,4,'Pag-IBIG',570.00,0),(60,16,1,'Base salary',62857.14,1),(61,16,3,'Income Tax',7779.83,0),(62,16,4,'SSS',2828.57,0),(63,16,4,'PhilHealth',1571.43,0),(64,16,4,'Pag-IBIG',1257.14,0),(65,16,2,'Absent Deduction',1000.00,0),(66,17,1,'Base salary',71428.57,1),(67,17,1,'Attendance: Overtime Pay',4301.56,1),(68,17,1,'Attendance: Night Differential',167.41,1),(69,17,2,'Attendance: Late Deduction',1246.29,0),(70,17,2,'Attendance: Undertime Deduction',837.06,0),(71,17,2,'Attendance: Absence Deduction',8928.58,0),(72,17,3,'Income Tax',10849.43,0),(73,17,4,'SSS',3214.29,0),(74,17,4,'PhilHealth',1785.71,0),(75,17,4,'Pag-IBIG',1428.57,0),(76,17,2,'Absent Deduction',1000.00,0),(77,18,1,'Base salary',31428.57,1),(78,18,3,'Income Tax',1589.34,0),(79,18,4,'SSS',1414.29,0),(80,18,4,'PhilHealth',785.71,0),(81,18,4,'Pag-IBIG',628.57,0),(82,18,2,'Absent Deduction',1000.00,0),(83,19,1,'Base salary',31428.57,1),(84,19,3,'Income Tax',1589.34,0),(85,19,4,'SSS',1414.29,0),(86,19,4,'PhilHealth',785.71,0),(87,19,4,'Pag-IBIG',628.57,0),(88,19,2,'Absent Deduction',1000.00,0),(89,20,1,'Base salary',31428.57,1),(90,20,3,'Income Tax',1589.34,0),(91,20,4,'SSS',1414.29,0),(92,20,4,'PhilHealth',785.71,0),(93,20,4,'Pag-IBIG',628.57,0),(94,20,2,'Absent Deduction',1000.00,0),(95,21,1,'Base salary',31428.57,1),(96,21,3,'Income Tax',1589.34,0),(97,21,4,'SSS',1414.29,0),(98,21,4,'PhilHealth',785.71,0),(99,21,4,'Pag-IBIG',628.57,0),(100,21,2,'Absent Deduction',1000.00,0),(101,22,1,'Base salary',40000.00,1),(102,22,3,'Income Tax',3208.40,0),(103,22,4,'SSS',1800.00,0),(104,22,4,'PhilHealth',1000.00,0),(105,22,4,'Pag-IBIG',800.00,0),(106,22,2,'Absent Deduction',1000.00,0),(107,23,1,'Base salary',40000.00,1),(108,23,3,'Income Tax',3208.40,0),(109,23,4,'SSS',1800.00,0),(110,23,4,'PhilHealth',1000.00,0),(111,23,4,'Pag-IBIG',800.00,0),(112,23,2,'Absent Deduction',1000.00,0);
/*!40000 ALTER TABLE `payslip_line_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pay_run_id` int unsigned NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `gross_pay` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(14,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `status` int DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payslip` (`pay_run_id`,`employee_id`),
  KEY `idx_ps_run` (`pay_run_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
INSERT INTO `payslips` VALUES (1,1,1,0.00,0.00,0.00,'PHP',1,'2026-05-19 01:46:51','2026-05-19 01:46:51',NULL),(2,1,2,0.00,0.00,0.00,'PHP',1,'2026-05-19 01:46:51','2026-05-19 01:46:51',NULL),(3,1,3,0.00,0.00,0.00,'PHP',1,'2026-05-19 01:46:51','2026-05-19 01:46:51',NULL),(4,1,4,0.00,0.00,0.00,'PHP',1,'2026-05-19 01:46:51','2026-05-19 01:46:51',NULL),(5,2,1,15000.00,450.00,14550.00,'PHP',1,'2026-05-19 01:47:18','2026-05-19 01:47:18',NULL),(6,2,2,2400.00,72.00,2328.00,'PHP',1,'2026-05-19 01:47:18','2026-05-19 01:47:18',NULL),(7,2,3,12500.00,375.00,12125.00,'PHP',1,'2026-05-19 01:47:18','2026-05-19 01:47:18',NULL),(8,2,4,15000.00,0.00,15000.00,'PHP',1,'2026-05-19 01:47:18','2026-05-19 01:47:18',NULL),(9,3,1,15000.00,450.00,14550.00,'PHP',1,'2026-05-19 02:27:35','2026-05-19 02:27:35',NULL),(10,3,2,2400.00,72.00,2328.00,'PHP',1,'2026-05-19 02:27:35','2026-05-19 02:27:35',NULL),(11,3,3,13282.10,2302.10,10980.00,'PHP',1,'2026-05-19 02:27:35','2026-05-19 02:27:35',NULL),(12,3,4,15000.00,0.00,15000.00,'PHP',1,'2026-05-19 02:27:35','2026-05-19 02:27:35',NULL),(13,4,3,18594.90,4360.42,14234.48,'PHP',1,'2026-05-19 03:48:58','2026-05-19 03:48:58',NULL),(14,5,1,80000.00,20075.05,59924.95,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(15,5,2,28500.00,2565.00,25935.00,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(16,5,3,62857.14,14436.97,48420.17,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(17,5,4,75897.54,29289.93,46607.61,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(18,5,5,31428.57,5417.91,26010.66,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(19,5,6,31428.57,5417.91,26010.66,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(20,5,7,31428.57,5417.91,26010.66,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(21,5,8,31428.57,5417.91,26010.66,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(22,5,9,40000.00,7808.40,32191.60,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL),(23,5,10,40000.00,7808.40,32191.60,'PHP',1,'2026-05-19 05:10:37','2026-05-19 05:10:37',NULL);
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `policy_documents`
--

DROP TABLE IF EXISTS `policy_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `policy_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(80) DEFAULT NULL COMMENT 'e.g. Code of Conduct, Benefits, Safety',
  `file_url` varchar(500) NOT NULL,
  `version` varchar(20) DEFAULT NULL,
  `applies_to` enum('All','Full-time','Part-time','Contractual','Intern') NOT NULL DEFAULT 'All',
  `department_id` int unsigned DEFAULT NULL COMMENT 'NULL = company-wide',
  `published_at` date DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `uploaded_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `policy_documents`
--

LOCK TABLES `policy_documents` WRITE;
/*!40000 ALTER TABLE `policy_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `policy_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_activity_logs`
--

DROP TABLE IF EXISTS `portal_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_activity_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `action` varchar(120) NOT NULL COMMENT 'e.g. login, view_payslip, submit_leave',
  `module` varchar(80) DEFAULT NULL,
  `record_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pal_emp_time` (`employee_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_activity_logs`
--

LOCK TABLES `portal_activity_logs` WRITE;
/*!40000 ALTER TABLE `portal_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `level` varchar(60) DEFAULT NULL COMMENT 'e.g. Junior, Senior, Lead, Manager',
  `department_id` int unsigned DEFAULT NULL,
  `min_salary` decimal(14,2) DEFAULT NULL,
  `max_salary` decimal(14,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'HR Manager','Manager',1,50000.00,90000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(2,'Operations Supervisor','Manager',2,40000.00,70000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(3,'Customer Support Supervisor','Manager',2,40000.00,70000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(4,'Technical Lead','Lead',3,45000.00,80000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(5,'Operations Associate','Junior',2,20000.00,35000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(6,'Support Associate','Junior',2,20000.00,35000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27'),(7,'Software Engineer','Junior',3,25000.00,45000.00,'2026-05-19 04:35:27','2026-05-19 04:35:27');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `public_holidays`
--

DROP TABLE IF EXISTS `public_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_holidays` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `calendar_id` int unsigned NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(120) NOT NULL,
  `holiday_type` enum('Regular','Special Non-working','Special Working') NOT NULL DEFAULT 'Regular',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `public_holidays`
--

LOCK TABLES `public_holidays` WRITE;
/*!40000 ALTER TABLE `public_holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `public_holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reimbursement_requests`
--

DROP TABLE IF EXISTS `reimbursement_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reimbursement_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `category` varchar(80) NOT NULL COMMENT 'e.g. Travel, Medical, Training',
  `description` text,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `expense_date` date NOT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=Pending, 2=Approved, 3=Rejected, 4=Paid',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `rejection_note` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursement_requests`
--

LOCK TABLES `reimbursement_requests` WRITE;
/*!40000 ALTER TABLE `reimbursement_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `reimbursement_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_definitions`
--

DROP TABLE IF EXISTS `report_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_definitions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `module` varchar(80) NOT NULL COMMENT 'e.g. Attendance, Payroll, Headcount',
  `description` text,
  `filters_json` json DEFAULT NULL COMMENT 'Saved filter criteria',
  `columns_json` json DEFAULT NULL COMMENT 'Selected output columns',
  `is_shared` tinyint NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_definitions`
--

LOCK TABLES `report_definitions` WRITE;
/*!40000 ALTER TABLE `report_definitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_definitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_schedules`
--

DROP TABLE IF EXISTS `report_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int unsigned NOT NULL,
  `frequency` enum('Daily','Weekly','Monthly') NOT NULL,
  `day_of_week` tinyint DEFAULT NULL COMMENT '0=Sun, 6=Sat — for weekly',
  `day_of_month` tinyint DEFAULT NULL COMMENT '1-31 — for monthly',
  `recipients_json` json NOT NULL COMMENT 'Array of email addresses',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `last_run_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_schedules`
--

LOCK TABLES `report_schedules` WRITE;
/*!40000 ALTER TABLE `report_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_records`
--

DROP TABLE IF EXISTS `salary_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_records` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'PHP',
  `pay_frequency` int NOT NULL DEFAULT '4' COMMENT '1=Hourly, 2=Daily, 3=Semi-monthly, 4=Monthly',
  `daily_divisor` decimal(8,4) NOT NULL DEFAULT '21.8000',
  `attendance_overtime_multiplier` decimal(8,4) DEFAULT NULL,
  `attendance_night_differential_multiplier` decimal(8,4) DEFAULT NULL,
  `attendance_late_deduction_multiplier` decimal(8,4) DEFAULT NULL,
  `attendance_undertime_deduction_multiplier` decimal(8,4) DEFAULT NULL,
  `attendance_absence_deduction_multiplier` decimal(8,4) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL COMMENT 'e.g. Promotion, Annual review',
  `notes` varchar(300) DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_records`
--

LOCK TABLES `salary_records` WRITE;
/*!40000 ALTER TABLE `salary_records` DISABLE KEYS */;
INSERT INTO `salary_records` VALUES (1,1,70000.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2020-01-15',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(2,2,55000.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2021-06-10','2026-05-18','Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 05:09:58'),(3,3,55000.00,'PHP',4,21.8000,1.2500,0.1000,1.0000,1.0000,1.0000,'2026-05-19',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 05:07:41'),(4,4,62500.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2021-06-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(5,5,27500.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(6,6,27500.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(7,7,27500.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:28','2026-05-19 04:35:28'),(8,8,27500.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:29','2026-05-19 04:35:29'),(9,9,35000.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:29','2026-05-19 04:35:29'),(10,10,35000.00,'PHP',4,21.8000,NULL,NULL,NULL,NULL,NULL,'2023-01-10',NULL,'Initial Salary',NULL,NULL,'2026-05-19 04:35:29','2026-05-19 04:35:29'),(11,2,57000.00,'PHP',5,21.8000,1.2500,0.1000,1.0000,1.0000,1.0000,'2026-05-19',NULL,NULL,NULL,1,'2026-05-19 05:09:58','2026-05-19 05:09:58');
/*!40000 ALTER TABLE `salary_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_user_id` (`user_id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('dklv3OjuZp2sfijRSsBBwejEWegWevIlVJG5s42V',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLeFg0UXF0Y1Z6R1JUWjRTQVFJNGRDUXVtT1hhMTBUR1ZnbXlhcTZKIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL3RpbWVrZWVwaW5nIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvdGltZWtlZXBpbmciLCJyb3V0ZSI6InRpbWVrZWVwaW5nLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9',1779168131);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_assignments`
--

DROP TABLE IF EXISTS `shift_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_assignments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `shift_id` int unsigned NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_assignments`
--

LOCK TABLES `shift_assignments` WRITE;
/*!40000 ALTER TABLE `shift_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_minutes` int NOT NULL DEFAULT '60',
  `is_night_shift` tinyint NOT NULL DEFAULT '0',
  `days_of_week` varchar(20) NOT NULL COMMENT 'e.g. Mon-Fri, CSV bitmask',
  `is_active` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supervisor_assignments`
--

DROP TABLE IF EXISTS `supervisor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisor_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supervisor_id` bigint unsigned NOT NULL,
  `location` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sv_date` (`supervisor_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supervisor_assignments`
--

LOCK TABLES `supervisor_assignments` WRITE;
/*!40000 ALTER TABLE `supervisor_assignments` DISABLE KEYS */;
INSERT INTO `supervisor_assignments` VALUES (1,2,'Manila Zoo','2026-05-18','2026-05-18 20:35:29','2026-05-18 20:35:29'),(2,2,'Manila Zoo','2026-05-19','2026-05-18 20:35:29','2026-05-18 20:35:29'),(3,2,'SM','2026-05-20','2026-05-18 20:35:29','2026-05-18 20:35:29'),(4,2,'Manila Zoo','2026-05-21','2026-05-18 20:35:29','2026-05-18 20:35:29'),(5,2,'Manila Zoo','2026-05-22','2026-05-18 20:35:29','2026-05-18 20:35:29'),(6,3,'Robinsons Mall','2026-05-18','2026-05-18 20:35:29','2026-05-18 20:35:29'),(7,3,'Robinsons Mall','2026-05-19','2026-05-18 20:35:29','2026-05-18 20:35:29'),(8,3,'SM','2026-05-20','2026-05-18 20:35:29','2026-05-18 20:35:29'),(9,3,'Robinsons Mall','2026-05-21','2026-05-18 20:35:29','2026-05-18 20:35:29'),(10,3,'Robinsons Mall','2026-05-22','2026-05-18 20:35:29','2026-05-18 20:35:29'),(11,4,'IT Park','2026-05-18','2026-05-18 20:35:29','2026-05-18 20:35:29'),(12,4,'IT Park','2026-05-19','2026-05-18 20:35:29','2026-05-18 20:35:29'),(13,4,'SM','2026-05-20','2026-05-18 20:35:29','2026-05-18 20:35:29'),(14,4,'IT Park','2026-05-21','2026-05-18 20:35:29','2026-05-18 20:35:29'),(15,4,'IT Park','2026-05-22','2026-05-18 20:35:29','2026-05-18 20:35:29');
/*!40000 ALTER TABLE `supervisor_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_brackets`
--

DROP TABLE IF EXISTS `tax_brackets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_brackets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `threshold` decimal(14,2) NOT NULL,
  `rate` decimal(5,4) NOT NULL,
  `label` varchar(120) DEFAULT NULL,
  `notes` text,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_brackets`
--

LOCK TABLES `tax_brackets` WRITE;
/*!40000 ALTER TABLE `tax_brackets` DISABLE KEYS */;
INSERT INTO `tax_brackets` VALUES (1,0.00,0.0000,'Exempt','₱20,833 and below',1,0,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(2,20833.00,0.1500,'Bracket 2','Over ₱20,833 to ₱33,333',1,1,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(3,33333.00,0.2000,'Bracket 3','Over ₱33,333 to ₱66,667',1,2,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(4,66667.00,0.2500,'Bracket 4','Over ₱66,667 to ₱166,667',1,3,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(5,166667.00,0.3000,'Bracket 5','Over ₱166,667 to ₱666,667',1,4,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL),(6,666667.00,0.3500,'Bracket 6','Over ₱666,667',1,5,'2026-05-19 04:35:27','2026-05-19 04:35:27',NULL);
/*!40000 ALTER TABLE `tax_brackets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_logs`
--

DROP TABLE IF EXISTS `time_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `log_date` date NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `source` enum('Biometric','Web','Mobile','Manual') NOT NULL DEFAULT 'Manual',
  `biometric_device_id` varchar(60) DEFAULT NULL,
  `is_remote` tinyint NOT NULL DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL,
  `late_minutes` int NOT NULL DEFAULT '0',
  `undertime_minutes` int NOT NULL DEFAULT '0',
  `total_hours` decimal(5,2) DEFAULT NULL COMMENT 'Computed after clock-out',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tl_emp_date` (`employee_id`,`log_date`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_logs`
--

LOCK TABLES `time_logs` WRITE;
/*!40000 ALTER TABLE `time_logs` DISABLE KEYS */;
INSERT INTO `time_logs` VALUES (1,4,'2026-05-19','2026-05-19 07:55:00','2026-05-19 17:05:00','Manual',NULL,0,NULL,0,0,8.17,'2026-05-19 10:13:43'),(2,4,'2026-05-20','2026-05-20 08:37:00','2026-05-20 17:00:00','Manual',NULL,0,NULL,37,0,7.38,'2026-05-19 10:13:43'),(3,4,'2026-05-21','2026-05-21 00:00:00',NULL,'Manual',NULL,0,NULL,0,0,NULL,'2026-05-19 10:13:43'),(4,4,'2026-05-22','2026-05-22 07:58:00','2026-05-22 16:15:00','Manual',NULL,0,NULL,0,45,7.28,'2026-05-19 10:13:43'),(5,4,'2026-05-23','2026-05-23 08:00:00','2026-05-23 19:30:00','Manual',NULL,0,NULL,0,0,10.50,'2026-05-19 10:13:43'),(6,4,'2026-05-19','2026-05-19 07:55:00','2026-05-19 17:05:00','Manual',NULL,0,NULL,0,0,8.17,'2026-05-19 10:14:40'),(7,4,'2026-05-20','2026-05-20 08:37:00','2026-05-20 17:00:00','Manual',NULL,0,NULL,37,0,7.38,'2026-05-19 10:14:40'),(8,4,'2026-05-21','2026-05-21 00:00:00',NULL,'Manual',NULL,0,NULL,0,0,NULL,'2026-05-19 10:14:40'),(9,4,'2026-05-22','2026-05-22 07:58:00','2026-05-22 16:15:00','Manual',NULL,0,NULL,0,45,7.28,'2026-05-19 10:14:40'),(10,4,'2026-05-23','2026-05-23 08:00:00','2026-05-23 19:30:00','Manual',NULL,0,NULL,0,0,10.50,'2026-05-19 10:14:40');
/*!40000 ALTER TABLE `time_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timesheets`
--

DROP TABLE IF EXISTS `timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `regular_hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `overtime_hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `late_hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `absent_days` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1' COMMENT '1=Draft, 2=Submitted, 3=Approved, 4=Rejected',
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`id`),
  KEY `idx_ts_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timesheets`
--

LOCK TABLES `timesheets` WRITE;
/*!40000 ALTER TABLE `timesheets` DISABLE KEYS */;
/*!40000 ALTER TABLE `timesheets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(160) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `employee_id` int unsigned DEFAULT NULL,
  `role` tinyint NOT NULL DEFAULT '4' COMMENT '1=Employee,2=Supervisor,3=OIC,4=HR',
  `status` varchar(60) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hire_date` date DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Joefhanie Diaz','hr_admin','hr@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,1,4,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(2,'Andrei Dilag','sv_ops','sv1@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,2,2,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(3,'Ramon Valenzuela','sv_support','sv2@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,3,2,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(4,'Maria Clara Santos','sv_tech','sv3@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,4,2,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(5,'Juan dela Cruz','emp_ops_a','emp1@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,5,1,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(6,'Princess Mendoza','emp_ops_b','emp2@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,6,1,'1','2026-05-19 04:35:28','2026-05-19 04:35:28',NULL,NULL,NULL),(7,'Jose Rizal Macaraeg','emp_support_a','emp3@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,7,1,'1','2026-05-19 04:35:29','2026-05-19 04:35:29',NULL,NULL,NULL),(8,'Arnel Pineda','emp_support_b','emp4@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,8,1,'1','2026-05-19 04:35:29','2026-05-19 04:35:29',NULL,NULL,NULL),(9,'Gloc Nine Alimario','emp_tech_a','emp5@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,9,1,'1','2026-05-19 04:35:29','2026-05-19 04:35:29',NULL,NULL,NULL),(10,'Catriona Gray','emp_tech_b','emp6@example.com',NULL,'$2y$12$won3z.cl8IOzpUh5p3XybuDmEc/xXzVkPWLsvBX6NZVL.2mRh8cv6',NULL,10,1,'1','2026-05-19 04:35:29','2026-05-19 04:35:29',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workforce_snapshots`
--

DROP TABLE IF EXISTS `workforce_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workforce_snapshots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL,
  `total_headcount` int NOT NULL DEFAULT '0',
  `active_count` int NOT NULL DEFAULT '0',
  `probationary_count` int NOT NULL DEFAULT '0',
  `new_hires` int NOT NULL DEFAULT '0',
  `terminations` int NOT NULL DEFAULT '0',
  `attrition_rate` decimal(5,2) DEFAULT NULL COMMENT 'Percentage',
  `avg_tenure_months` decimal(6,1) DEFAULT NULL,
  `dept_breakdown_json` json DEFAULT NULL COMMENT 'Headcount per department',
  `gender_breakdown_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `snapshot_date` (`snapshot_date`),
  KEY `idx_ws_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workforce_snapshots`
--

LOCK TABLES `workforce_snapshots` WRITE;
/*!40000 ALTER TABLE `workforce_snapshots` DISABLE KEYS */;
/*!40000 ALTER TABLE `workforce_snapshots` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 13:23:46
