-- Vistiq MySQL schema generated from the migrations applied to Laragon MySQL 8.4.3.
-- Database: vistiq (64 physical tables: 55 Vistiq, 8 Laravel runtime, 1 migration ledger).
-- This file creates the database if needed and declares its tables, indexes, and foreign keys.
-- Database contents are in the sibling data.sql file.

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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `vistiq` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `vistiq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_threshold` decimal(10,2) DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_definitions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answer_grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attempt_answer_id` bigint unsigned NOT NULL,
  `grading_criterion_id` bigint unsigned NOT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  `awarded_score` decimal(7,2) NOT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `answer_grades_attempt_answer_id_grading_criterion_id_unique` (`attempt_answer_id`,`grading_criterion_id`),
  KEY `answer_grades_grading_criterion_id_foreign` (`grading_criterion_id`),
  KEY `answer_grades_graded_by_foreign` (`graded_by`),
  CONSTRAINT `answer_grades_attempt_answer_id_foreign` FOREIGN KEY (`attempt_answer_id`) REFERENCES `attempt_answers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answer_grades_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `answer_grades_grading_criterion_id_foreign` FOREIGN KEY (`grading_criterion_id`) REFERENCES `grading_criteria` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_answer_options` (
  `attempt_answer_id` bigint unsigned NOT NULL,
  `question_option_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`attempt_answer_id`,`question_option_id`),
  KEY `attempt_answer_options_question_option_id_index` (`question_option_id`),
  CONSTRAINT `attempt_answer_options_attempt_answer_id_foreign` FOREIGN KEY (`attempt_answer_id`) REFERENCES `attempt_answers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempt_answer_options_question_option_id_foreign` FOREIGN KEY (`question_option_id`) REFERENCES `question_options` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_attempt_id` bigint unsigned NOT NULL,
  `exam_section_question_id` bigint unsigned NOT NULL,
  `response_text` longtext COLLATE utf8mb4_unicode_ci,
  `response_asset_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `awarded_points` decimal(7,2) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attempt_answers_exam_attempt_id_exam_section_question_id_unique` (`exam_attempt_id`,`exam_section_question_id`),
  KEY `attempt_answers_exam_section_question_id_foreign` (`exam_section_question_id`),
  CONSTRAINT `attempt_answers_exam_attempt_id_foreign` FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempt_answers_exam_section_question_id_foreign` FOREIGN KEY (`exam_section_question_id`) REFERENCES `exam_section_questions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_event_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attempt_event_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_attempt_id` bigint unsigned NOT NULL,
  `attempt_event_type_id` bigint unsigned NOT NULL,
  `occurred_at` timestamp NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attempt_events_attempt_event_type_id_foreign` (`attempt_event_type_id`),
  KEY `attempt_events_exam_attempt_id_occurred_at_index` (`exam_attempt_id`,`occurred_at`),
  CONSTRAINT `attempt_events_attempt_event_type_id_foreign` FOREIGN KEY (`attempt_event_type_id`) REFERENCES `attempt_event_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attempt_events_exam_attempt_id_foreign` FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_report_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `attempt_report_reasons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_attempt_id` bigint unsigned NOT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `attempt_report_reason_id` bigint unsigned NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attempt_reports_reported_by_foreign` (`reported_by`),
  KEY `attempt_reports_attempt_report_reason_id_foreign` (`attempt_report_reason_id`),
  KEY `attempt_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `attempt_reports_exam_attempt_id_status_index` (`exam_attempt_id`,`status`),
  KEY `attempt_reports_status_index` (`status`),
  CONSTRAINT `attempt_reports_attempt_report_reason_id_foreign` FOREIGN KEY (`attempt_report_reason_id`) REFERENCES `attempt_report_reasons` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attempt_reports_exam_attempt_id_foreign` FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempt_reports_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempt_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attempt_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_attempt_id` bigint unsigned NOT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  `raw_score` decimal(9,2) NOT NULL,
  `calculated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attempt_results_exam_attempt_id_unique` (`exam_attempt_id`),
  KEY `attempt_results_graded_by_foreign` (`graded_by`),
  CONSTRAINT `attempt_results_exam_attempt_id_foreign` FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attempt_results_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collocation_dictionary_entries` (
  `collocation_id` bigint unsigned NOT NULL,
  `dictionary_entry_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`collocation_id`,`dictionary_entry_id`),
  KEY `collocation_dictionary_entries_dictionary_entry_id_index` (`dictionary_entry_id`),
  CONSTRAINT `collocation_dictionary_entries_collocation_id_foreign` FOREIGN KEY (`collocation_id`) REFERENCES `collocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collocation_dictionary_entries_dictionary_entry_id_foreign` FOREIGN KEY (`dictionary_entry_id`) REFERENCES `dictionary_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `grammatical_pattern` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collocations_learning_item_id_unique` (`learning_item_id`),
  CONSTRAINT `collocations_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dictionary_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `phonetic` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dictionary_entries_learning_item_id_unique` (`learning_item_id`),
  CONSTRAINT `dictionary_entries_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `exam_template_id` bigint unsigned NOT NULL,
  `attempt_number` smallint unsigned NOT NULL DEFAULT '1',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `leaderboard_status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `started_at` timestamp NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_attempts_user_id_exam_template_id_attempt_number_unique` (`user_id`,`exam_template_id`,`attempt_number`),
  KEY `exam_attempts_exam_template_id_status_leaderboard_status_index` (`exam_template_id`,`status`,`leaderboard_status`),
  KEY `exam_attempts_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `exam_attempts_status_index` (`status`),
  KEY `exam_attempts_leaderboard_status_index` (`leaderboard_status`),
  CONSTRAINT `exam_attempts_exam_template_id_foreign` FOREIGN KEY (`exam_template_id`) REFERENCES `exam_templates` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exam_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_type_id` bigint unsigned NOT NULL,
  `skill_id` bigint unsigned DEFAULT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_parts_exam_type_id_code_unique` (`exam_type_id`,`code`),
  KEY `exam_parts_skill_id_foreign` (`skill_id`),
  KEY `exam_parts_exam_type_id_skill_id_sort_order_index` (`exam_type_id`,`skill_id`,`sort_order`),
  CONSTRAINT `exam_parts_exam_type_id_foreign` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_parts_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_template_id` bigint unsigned NOT NULL,
  `time_limit_minutes` smallint unsigned DEFAULT NULL,
  `is_timed` tinyint(1) NOT NULL DEFAULT '1',
  `disable_copy_paste` tinyint(1) NOT NULL DEFAULT '1',
  `track_focus_changes` tinyint(1) NOT NULL DEFAULT '1',
  `require_fullscreen` tinyint(1) NOT NULL DEFAULT '0',
  `auto_submit_on_expiry` tinyint(1) NOT NULL DEFAULT '1',
  `max_focus_loss_count` smallint unsigned DEFAULT NULL,
  `answers_visible_after_submit` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_policies_exam_template_id_unique` (`exam_template_id`),
  CONSTRAINT `exam_policies_exam_template_id_foreign` FOREIGN KEY (`exam_template_id`) REFERENCES `exam_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_section_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_section_id` bigint unsigned NOT NULL,
  `question_bank_item_id` bigint unsigned NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `points` decimal(7,2) NOT NULL DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `esq_section_question_unique` (`exam_section_id`,`question_bank_item_id`),
  UNIQUE KEY `exam_section_questions_exam_section_id_sort_order_unique` (`exam_section_id`,`sort_order`),
  KEY `exam_section_questions_question_bank_item_id_foreign` (`question_bank_item_id`),
  CONSTRAINT `exam_section_questions_exam_section_id_foreign` FOREIGN KEY (`exam_section_id`) REFERENCES `exam_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_section_questions_question_bank_item_id_foreign` FOREIGN KEY (`question_bank_item_id`) REFERENCES `question_bank_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_template_id` bigint unsigned NOT NULL,
  `exam_part_id` bigint unsigned NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `time_limit_minutes` smallint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_sections_exam_template_id_sort_order_unique` (`exam_template_id`,`sort_order`),
  KEY `exam_sections_exam_part_id_foreign` (`exam_part_id`),
  KEY `exam_sections_exam_template_id_exam_part_id_index` (`exam_template_id`,`exam_part_id`),
  CONSTRAINT `exam_sections_exam_part_id_foreign` FOREIGN KEY (`exam_part_id`) REFERENCES `exam_parts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exam_sections_exam_template_id_foreign` FOREIGN KEY (`exam_template_id`) REFERENCES `exam_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_type_id` bigint unsigned NOT NULL,
  `practice_mode_id` bigint unsigned NOT NULL,
  `score_scale_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_templates_slug_unique` (`slug`),
  KEY `exam_templates_practice_mode_id_foreign` (`practice_mode_id`),
  KEY `exam_templates_score_scale_id_foreign` (`score_scale_id`),
  KEY `exam_templates_created_by_foreign` (`created_by`),
  KEY `exam_templates_exam_type_id_practice_mode_id_status_index` (`exam_type_id`,`practice_mode_id`,`status`),
  KEY `exam_templates_status_index` (`status`),
  CONSTRAINT `exam_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_templates_exam_type_id_foreign` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exam_templates_practice_mode_id_foreign` FOREIGN KEY (`practice_mode_id`) REFERENCES `practice_modes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `exam_templates_score_scale_id_foreign` FOREIGN KEY (`score_scale_id`) REFERENCES `score_scales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_criteria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grading_rubric_id` bigint unsigned NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maximum_score` decimal(7,2) NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grading_criteria_grading_rubric_id_code_unique` (`grading_rubric_id`,`code`),
  KEY `grading_criteria_grading_rubric_id_sort_order_index` (`grading_rubric_id`,`sort_order`),
  CONSTRAINT `grading_criteria_grading_rubric_id_foreign` FOREIGN KEY (`grading_rubric_id`) REFERENCES `grading_rubrics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_rubrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_type_id` bigint unsigned DEFAULT NULL,
  `skill_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_rubrics_skill_id_foreign` (`skill_id`),
  KEY `grading_rubrics_created_by_foreign` (`created_by`),
  KEY `grading_rubrics_exam_type_id_skill_id_is_active_index` (`exam_type_id`,`skill_id`,`is_active`),
  CONSTRAINT `grading_rubrics_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grading_rubrics_exam_type_id_foreign` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grading_rubrics_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grammar_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grammar_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grammar_examples` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grammar_rule_id` bigint unsigned NOT NULL,
  `english` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `vietnamese` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grammar_examples_grammar_rule_id_foreign` (`grammar_rule_id`),
  CONSTRAINT `grammar_examples_grammar_rule_id_foreign` FOREIGN KEY (`grammar_rule_id`) REFERENCES `grammar_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grammar_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grammar_topic_id` bigint unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci,
  `explanation` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grammar_rules_grammar_topic_id_sort_order_index` (`grammar_topic_id`,`sort_order`),
  CONSTRAINT `grammar_rules_grammar_topic_id_foreign` FOREIGN KEY (`grammar_topic_id`) REFERENCES `grammar_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grammar_topics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `grammar_category_id` bigint unsigned NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grammar_topics_learning_item_id_unique` (`learning_item_id`),
  KEY `grammar_topics_grammar_category_id_index` (`grammar_category_id`),
  CONSTRAINT `grammar_topics_grammar_category_id_foreign` FOREIGN KEY (`grammar_category_id`) REFERENCES `grammar_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `grammar_topics_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `idioms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `register` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usage_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idioms_learning_item_id_unique` (`learning_item_id`),
  CONSTRAINT `idioms_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_item_meaning_examples` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_meaning_id` bigint unsigned NOT NULL,
  `english` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `vietnamese` text COLLATE utf8mb4_unicode_ci,
  `source_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_item_meaning_examples_learning_item_meaning_id_foreign` (`learning_item_meaning_id`),
  CONSTRAINT `learning_item_meaning_examples_learning_item_meaning_id_foreign` FOREIGN KEY (`learning_item_meaning_id`) REFERENCES `learning_item_meanings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_item_meanings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `part_of_speech_id` bigint unsigned DEFAULT NULL,
  `sense_number` smallint unsigned NOT NULL,
  `translation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `definition` text COLLATE utf8mb4_unicode_ci,
  `usage_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_item_meanings_learning_item_id_sense_number_unique` (`learning_item_id`,`sense_number`),
  KEY `learning_item_meanings_part_of_speech_id_foreign` (`part_of_speech_id`),
  KEY `learning_item_meanings_learning_item_id_part_of_speech_id_index` (`learning_item_id`,`part_of_speech_id`),
  CONSTRAINT `learning_item_meanings_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_item_meanings_part_of_speech_id_foreign` FOREIGN KEY (`part_of_speech_id`) REFERENCES `parts_of_speech` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_item_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_learning_item_id` bigint unsigned NOT NULL,
  `target_learning_item_id` bigint unsigned NOT NULL,
  `relation_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_item_relation_unique` (`source_learning_item_id`,`target_learning_item_id`,`relation_type`),
  KEY `lir_target_type_idx` (`target_learning_item_id`,`relation_type`),
  CONSTRAINT `learning_item_relations_source_learning_item_id_foreign` FOREIGN KEY (`source_learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_item_relations_target_learning_item_id_foreign` FOREIGN KEY (`target_learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_item_tags` (
  `learning_item_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`learning_item_id`,`tag_id`),
  KEY `learning_item_tags_tag_id_index` (`tag_id`),
  CONSTRAINT `learning_item_tags_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_item_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_item_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_item_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_type_id` bigint unsigned NOT NULL,
  `proficiency_level_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `canonical_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_items_learning_item_type_id_canonical_text_unique` (`learning_item_type_id`,`canonical_text`),
  UNIQUE KEY `learning_items_slug_unique` (`slug`),
  KEY `learning_items_proficiency_level_id_foreign` (`proficiency_level_id`),
  KEY `learning_items_created_by_foreign` (`created_by`),
  KEY `learning_items_learning_item_type_id_status_index` (`learning_item_type_id`,`status`),
  KEY `learning_items_status_index` (`status`),
  CONSTRAINT `learning_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `learning_items_learning_item_type_id_foreign` FOREIGN KEY (`learning_item_type_id`) REFERENCES `learning_item_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `learning_items_proficiency_level_id_foreign` FOREIGN KEY (`proficiency_level_id`) REFERENCES `proficiency_levels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_stimuli` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stimulus_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `asset_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transcript` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_stimuli_created_by_foreign` (`created_by`),
  KEY `learning_stimuli_stimulus_type_index` (`stimulus_type`),
  CONSTRAINT `learning_stimuli_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parts_of_speech` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `parts_of_speech_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phrasal_verbs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `base_dictionary_entry_id` bigint unsigned NOT NULL,
  `particle` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phrasal_verbs_learning_item_id_unique` (`learning_item_id`),
  KEY `phrasal_verbs_base_dictionary_entry_id_particle_index` (`base_dictionary_entry_id`,`particle`),
  CONSTRAINT `phrasal_verbs_base_dictionary_entry_id_foreign` FOREIGN KEY (`base_dictionary_entry_id`) REFERENCES `dictionary_entries` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `phrasal_verbs_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `practice_modes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `practice_modes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proficiency_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proficiency_levels_code_unique` (`code`),
  UNIQUE KEY `proficiency_levels_sort_order_unique` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_accepted_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_bank_item_id` bigint unsigned NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_answer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_answer_value_unique` (`question_bank_item_id`,`normalized_answer`),
  CONSTRAINT `question_accepted_answers_question_bank_item_id_foreign` FOREIGN KEY (`question_bank_item_id`) REFERENCES `question_bank_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_bank_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `learning_item_id` bigint unsigned NOT NULL,
  `exam_part_id` bigint unsigned DEFAULT NULL,
  `question_type_id` bigint unsigned NOT NULL,
  `question_difficulty_id` bigint unsigned NOT NULL,
  `learning_stimulus_id` bigint unsigned DEFAULT NULL,
  `prompt` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_bank_items_learning_item_id_unique` (`learning_item_id`),
  KEY `question_bank_items_question_difficulty_id_foreign` (`question_difficulty_id`),
  KEY `question_bank_items_learning_stimulus_id_foreign` (`learning_stimulus_id`),
  KEY `question_bank_items_exam_part_id_question_difficulty_id_index` (`exam_part_id`,`question_difficulty_id`),
  KEY `qbi_type_difficulty_idx` (`question_type_id`,`question_difficulty_id`),
  CONSTRAINT `question_bank_items_exam_part_id_foreign` FOREIGN KEY (`exam_part_id`) REFERENCES `exam_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `question_bank_items_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_bank_items_learning_stimulus_id_foreign` FOREIGN KEY (`learning_stimulus_id`) REFERENCES `learning_stimuli` (`id`) ON DELETE SET NULL,
  CONSTRAINT `question_bank_items_question_difficulty_id_foreign` FOREIGN KEY (`question_difficulty_id`) REFERENCES `question_difficulties` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `question_bank_items_question_type_id_foreign` FOREIGN KEY (`question_type_id`) REFERENCES `question_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_difficulties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_difficulties_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_bank_item_id` bigint unsigned NOT NULL,
  `option_key` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_options_question_bank_item_id_option_key_unique` (`question_bank_item_id`,`option_key`),
  KEY `question_options_question_bank_item_id_sort_order_index` (`question_bank_item_id`,`sort_order`),
  CONSTRAINT `question_options_question_bank_item_id_foreign` FOREIGN KEY (`question_bank_item_id`) REFERENCES `question_bank_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_rubrics` (
  `question_bank_item_id` bigint unsigned NOT NULL,
  `grading_rubric_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`question_bank_item_id`,`grading_rubric_id`),
  KEY `question_rubrics_grading_rubric_id_index` (`grading_rubric_id`),
  CONSTRAINT `question_rubrics_grading_rubric_id_foreign` FOREIGN KEY (`grading_rubric_id`) REFERENCES `grading_rubrics` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `question_rubrics_question_bank_item_id_foreign` FOREIGN KEY (`question_bank_item_id`) REFERENCES `question_bank_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supports_options` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `question_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `role_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  KEY `role_user_user_id_index` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_item_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_saved_item_id` bigint unsigned NOT NULL,
  `reviewed_at` timestamp NOT NULL,
  `outcome` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_item_reviews_user_saved_item_id_reviewed_at_index` (`user_saved_item_id`,`reviewed_at`),
  CONSTRAINT `saved_item_reviews_user_saved_item_id_foreign` FOREIGN KEY (`user_saved_item_id`) REFERENCES `user_saved_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `score_scale_ranges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `score_scale_id` bigint unsigned NOT NULL,
  `minimum_raw_score` smallint unsigned NOT NULL,
  `maximum_raw_score` smallint unsigned NOT NULL,
  `minimum_scaled_score` decimal(8,2) DEFAULT NULL,
  `maximum_scaled_score` decimal(8,2) DEFAULT NULL,
  `band_label` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `score_scale_ranges_score_scale_id_minimum_raw_score_unique` (`score_scale_id`,`minimum_raw_score`),
  KEY `score_scale_ranges_score_scale_id_maximum_raw_score_index` (`score_scale_id`,`maximum_raw_score`),
  CONSTRAINT `score_scale_ranges_score_scale_id_foreign` FOREIGN KEY (`score_scale_id`) REFERENCES `score_scales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `score_scales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_achievements` (
  `user_id` bigint unsigned NOT NULL,
  `achievement_definition_id` bigint unsigned NOT NULL,
  `awarded_at` timestamp NOT NULL,
  PRIMARY KEY (`user_id`,`achievement_definition_id`),
  KEY `user_achievements_achievement_definition_id_foreign` (`achievement_definition_id`),
  KEY `user_achievements_awarded_at_index` (`awarded_at`),
  CONSTRAINT `user_achievements_achievement_definition_id_foreign` FOREIGN KEY (`achievement_definition_id`) REFERENCES `achievement_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_achievements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_notifications_user_id_read_at_created_at_index` (`user_id`,`read_at`,`created_at`),
  CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vi',
  `theme` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preferences_user_id_unique` (`user_id`),
  CONSTRAINT `user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_zone` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_saved_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `learning_item_id` bigint unsigned NOT NULL,
  `personal_note` text COLLATE utf8mb4_unicode_ci,
  `is_starred` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_saved_items_user_id_learning_item_id_unique` (`user_id`,`learning_item_id`),
  KEY `user_saved_items_learning_item_id_foreign` (`learning_item_id`),
  KEY `user_saved_items_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `user_saved_items_learning_item_id_foreign` FOREIGN KEY (`learning_item_id`) REFERENCES `learning_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_saved_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
