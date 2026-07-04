/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_order` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_levels_school_name_type_unique` (`school_id`,`name`,`type`),
  CONSTRAINT `academic_levels_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `education_level` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `min_units` decimal(5,2) DEFAULT NULL,
  `max_units` decimal(5,2) DEFAULT NULL,
  `max_subjects` int(11) DEFAULT NULL,
  `overload_threshold_units` decimal(5,2) DEFAULT NULL,
  `max_section_capacity_override` int(11) DEFAULT NULL,
  `requires_payment_to_enrol` tinyint(1) NOT NULL DEFAULT '0',
  `min_payment_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_policies_program_id_foreign` (`program_id`),
  KEY `academic_policies_term_id_foreign` (`term_id`),
  KEY `academic_policies_created_by_foreign` (`created_by`),
  KEY `academic_policies_updated_by_foreign` (`updated_by`),
  KEY `idx_policy_resolution` (`school_id`,`education_level`,`program_id`,`term_id`),
  CONSTRAINT `academic_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_policies_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_policies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_policies_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_policies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_schedulers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_schedulers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `config` json DEFAULT NULL,
  `sessions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_schedulers_school_term_unique` (`school_id`,`term_id`),
  KEY `academic_schedulers_school_id_index` (`school_id`),
  KEY `academic_schedulers_term_id_index` (`term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `education_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'higher_ed',
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_years_school_id_education_level_index` (`school_id`,`education_level`),
  KEY `academic_years_education_node_id_foreign` (`education_node_id`),
  CONSTRAINT `academic_years_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_years_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `person_id` bigint(20) unsigned NOT NULL,
  `office_id` bigint(20) unsigned DEFAULT NULL,
  `role_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_access_user_id_foreign` (`user_id`),
  KEY `account_access_assigned_by_foreign` (`assigned_by`),
  KEY `account_access_role_id_foreign` (`role_id`),
  CONSTRAINT `account_access_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  CONSTRAINT `account_access_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `account_access_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admission_exam_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_exam_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `require_for_new_student` tinyint(1) NOT NULL DEFAULT '0',
  `require_for_transferee` tinyint(1) NOT NULL DEFAULT '0',
  `require_for_returnee` tinyint(1) NOT NULL DEFAULT '0',
  `require_for_shiftee` tinyint(1) NOT NULL DEFAULT '0',
  `exam_purpose` enum('diagnostic_only','admission_requirement','scholarship_grants') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diagnostic_only',
  `grants_scholarship` tinyint(1) NOT NULL DEFAULT '0',
  `max_score` smallint(5) unsigned NOT NULL DEFAULT '100',
  `passing_score` smallint(5) unsigned DEFAULT NULL,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `retake_cooldown_days` smallint(5) unsigned DEFAULT NULL,
  `result_validity_months` smallint(5) unsigned DEFAULT NULL,
  `allow_program_head_waiver` tinyint(1) NOT NULL DEFAULT '1',
  `notify_applicant_on_schedule` tinyint(1) NOT NULL DEFAULT '1',
  `auto_assess_after_pass` tinyint(1) NOT NULL DEFAULT '0',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `scholarship_bands` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_exam_settings_school_id_unique` (`school_id`),
  CONSTRAINT `admission_exam_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcement_acknowledgements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_acknowledgements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_acknowledgements_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `announcement_acknowledgements_user_id_foreign` (`user_id`),
  CONSTRAINT `announcement_acknowledgements_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_acknowledgements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcement_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `assignable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignable_id` bigint(20) unsigned NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcement_assignments_announcement_id_foreign` (`announcement_id`),
  KEY `announcement_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `announcement_assignable_index` (`assignable_type`,`assignable_id`),
  CONSTRAINT `announcement_assignments_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `announcement_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `priority_level` enum('normal','super') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `super_priority_expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_published_at_index` (`published_at`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applications_school_id_foreign` (`school_id`),
  KEY `applications_student_id_foreign` (`student_id`),
  KEY `applications_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banks_school_id_foreign` (`school_id`),
  CONSTRAINT `banks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campuses_school_id_code_unique` (`school_id`,`code`),
  CONSTRAINT `campuses_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_event_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_event_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `recipient_template_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `award_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recognition_reason` text COLLATE utf8mb4_unicode_ci,
  `organization_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signatory_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `custom_fields` json DEFAULT NULL,
  `generated_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_event_recipients_event_id_status_index` (`event_id`,`status`),
  KEY `certificate_event_recipients_recipient_template_id_foreign` (`recipient_template_id`),
  KEY `cer_event_template_idx` (`event_id`,`recipient_template_id`),
  CONSTRAINT `certificate_event_recipients_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `certificate_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificate_event_recipients_recipient_template_id_foreign` FOREIGN KEY (`recipient_template_id`) REFERENCES `certificate_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `certificate_title_default` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_issued_default` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificate_events_school_id_foreign` (`school_id`),
  KEY `certificate_events_template_id_foreign` (`template_id`),
  CONSTRAINT `certificate_events_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificate_events_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `certificate_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_type_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_type` enum('internal','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'training',
  `background_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elements` json DEFAULT NULL,
  `layout_json` json DEFAULT NULL,
  `orientation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'landscape',
  `paper_size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a4',
  `is_default` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `layout_html` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `certificate_templates_training_type_id_foreign` (`training_type_id`),
  CONSTRAINT `certificate_templates_training_type_id_foreign` FOREIGN KEY (`training_type_id`) REFERENCES `training_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `chat_thread_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` bigint(20) unsigned DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_by_user` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `flag_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_chat_thread_id_foreign` (`chat_thread_id`),
  KEY `chat_messages_user_id_foreign` (`user_id`),
  CONSTRAINT `chat_messages_chat_thread_id_foreign` FOREIGN KEY (`chat_thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_thread_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_thread_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chat_thread_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_thread_user_chat_thread_id_user_id_unique` (`chat_thread_id`,`user_id`),
  KEY `chat_thread_user_user_id_foreign` (`user_id`),
  CONSTRAINT `chat_thread_user_chat_thread_id_foreign` FOREIGN KEY (`chat_thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_thread_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('private','group','department','class') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` enum('active','pending_deletion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chats_student_id_foreign` (`student_id`),
  KEY `chats_approved_by_foreign` (`approved_by`),
  CONSTRAINT `chats_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chats_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `choices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) unsigned NOT NULL,
  `choice_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `choices_question_id_foreign` (`question_id`),
  CONSTRAINT `choices_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_grade_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_grade_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_grade_id` bigint(20) unsigned NOT NULL,
  `grading_component_id` bigint(20) unsigned NOT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cgc_grade_component_unique` (`class_grade_id`,`grading_component_id`),
  KEY `class_grade_components_grading_component_id_foreign` (`grading_component_id`),
  CONSTRAINT `class_grade_components_class_grade_id_foreign` FOREIGN KEY (`class_grade_id`) REFERENCES `class_grades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_grade_components_grading_component_id_foreign` FOREIGN KEY (`grading_component_id`) REFERENCES `grading_components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_grades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `final_grade` decimal(8,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_grades_class_id_student_id_unique` (`class_id`,`student_id`),
  KEY `class_grades_student_id_foreign` (`student_id`),
  CONSTRAINT `class_grades_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_grades_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_schedules_class_id_foreign` (`class_id`),
  CONSTRAINT `class_schedules_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `room` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_sessions_subject_id_foreign` (`subject_id`),
  KEY `class_sessions_class_id_meeting_date_index` (`class_id`,`meeting_date`),
  KEY `idx_session_time` (`meeting_date`,`start_time`,`end_time`),
  KEY `idx_session_teacher_date` (`teacher_id`,`meeting_date`),
  CONSTRAINT `class_sessions_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_sessions_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_sessions_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_student` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_model_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_student_class_model_id_student_id_unique` (`class_model_id`,`student_id`),
  KEY `class_student_student_id_foreign` (`student_id`),
  CONSTRAINT `class_student_class_model_id_foreign` FOREIGN KEY (`class_model_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_student_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `class_teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_teacher` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `role` enum('primary','assistant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_teacher_class_id_teacher_id_unique` (`class_id`,`teacher_id`),
  KEY `class_teacher_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `class_teacher_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_teacher_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teacher_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int(10) unsigned DEFAULT NULL,
  `max_students` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classes_subject_id_semester_id_section_id_unique` (`subject_id`,`term_id`,`section_id`),
  KEY `classes_school_id_foreign` (`school_id`),
  KEY `classes_teacher_id_foreign` (`teacher_id`),
  KEY `classes_section_id_foreign` (`section_id`),
  KEY `classes_term_id_foreign` (`term_id`),
  CONSTRAINT `classes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classes_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `colleges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `colleges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `dean_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colleges_code_unique` (`code`),
  KEY `colleges_school_id_foreign` (`school_id`),
  KEY `colleges_dean_id_foreign` (`dean_id`),
  CONSTRAINT `colleges_dean_id_foreign` FOREIGN KEY (`dean_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `colleges_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `topic_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `bloom_level` enum('remember','understand','apply','analyze','evaluate','create') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mastery_threshold` int(11) NOT NULL DEFAULT '75',
  `sequence` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `competencies_lesson_id_name_unique` (`lesson_id`,`name`),
  KEY `competencies_school_id_foreign` (`school_id`),
  KEY `competencies_subject_id_foreign` (`subject_id`),
  KEY `competencies_topic_id_foreign` (`topic_id`),
  KEY `competencies_created_by_foreign` (`created_by`),
  CONSTRAINT `competencies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `competencies_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `competencies_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `competencies_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `competencies_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `order_index` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `configurations_owner_type_owner_id_category_index` (`owner_type`,`owner_id`,`category`),
  KEY `configurations_owner_type_owner_id_category_is_active_index` (`owner_type`,`owner_id`,`category`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cross_school_subject_equivalencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cross_school_subject_equivalencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `home_school_id` bigint(20) unsigned NOT NULL,
  `host_school_id` bigint(20) unsigned NOT NULL,
  `home_subject_id` bigint(20) unsigned NOT NULL,
  `host_subject_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cross_subj_unique` (`home_school_id`,`host_school_id`,`home_subject_id`,`host_subject_id`),
  KEY `cross_school_subject_equivalencies_host_school_id_foreign` (`host_school_id`),
  KEY `cross_school_subject_equivalencies_home_subject_id_foreign` (`home_subject_id`),
  KEY `cross_school_subject_equivalencies_host_subject_id_foreign` (`host_subject_id`),
  CONSTRAINT `cross_school_subject_equivalencies_home_school_id_foreign` FOREIGN KEY (`home_school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cross_school_subject_equivalencies_home_subject_id_foreign` FOREIGN KEY (`home_subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cross_school_subject_equivalencies_host_school_id_foreign` FOREIGN KEY (`host_school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cross_school_subject_equivalencies_host_subject_id_foreign` FOREIGN KEY (`host_subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curriculum_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` bigint(20) unsigned NOT NULL,
  `enrollment_mode` enum('cohort','credit','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cohort',
  `enforce_prerequisites` tinyint(1) NOT NULL DEFAULT '1',
  `allow_core_override` tinyint(1) NOT NULL DEFAULT '0',
  `allow_cross_year` tinyint(1) NOT NULL DEFAULT '1',
  `max_units` int(10) unsigned DEFAULT NULL,
  `min_units` int(10) unsigned DEFAULT NULL,
  `auto_assign_core` tinyint(1) NOT NULL DEFAULT '1',
  `strict_year_level` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curriculum_settings_curriculum_id_unique` (`curriculum_id`),
  CONSTRAINT `curriculum_settings_curriculum_id_foreign` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curriculum_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `year_level` int(10) unsigned DEFAULT NULL,
  `semester` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT '1',
  `is_elective` tinyint(1) NOT NULL DEFAULT '0',
  `units` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curriculum_subjects_curriculum_id_subject_id_unique` (`curriculum_id`,`subject_id`),
  KEY `curriculum_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `curriculum_subjects_curriculum_id_foreign` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE,
  CONSTRAINT `curriculum_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curriculums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculums` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `terms_per_year` tinyint(3) unsigned NOT NULL DEFAULT '2',
  `has_summer_term` tinyint(1) NOT NULL DEFAULT '0',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curriculums_program_id_version_unique` (`program_id`,`version`),
  KEY `curriculums_school_id_foreign` (`school_id`),
  CONSTRAINT `curriculums_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `curriculums_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deadline_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deadline_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `deadline_id` bigint(20) unsigned NOT NULL,
  `assignable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignable_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deadline_assignments_unique` (`deadline_id`,`assignable_type`,`assignable_id`),
  KEY `deadline_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `deadline_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `deadline_assignments_school_id_deadline_id_index` (`school_id`,`deadline_id`),
  KEY `deadline_assignments_deadline_id_visible_index` (`deadline_id`,`visible`),
  CONSTRAINT `deadline_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_assignments_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_assignments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deadline_user_completions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deadline_user_completions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deadline_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deadline_user_unique` (`deadline_id`,`user_id`),
  KEY `idx_user_deadline` (`user_id`,`deadline_id`),
  KEY `idx_deadline_status` (`deadline_id`,`status`),
  CONSTRAINT `deadline_user_completions_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_user_completions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deadlines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deadlines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assignment',
  `due_date` datetime NOT NULL,
  `requires_submission` tinyint(1) NOT NULL DEFAULT '1',
  `allow_late_submission` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deadlines_created_by_foreign` (`created_by`),
  KEY `idx_school_active` (`school_id`,`active`),
  KEY `idx_school_due_date` (`school_id`,`due_date`),
  KEY `idx_school_creator` (`school_id`,`created_by`),
  KEY `idx_school_deleted` (`school_id`,`deleted_at`),
  CONSTRAINT `deadlines_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadlines_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_user_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_school_id_foreign` (`school_id`),
  KEY `departments_head_user_id_foreign` (`head_user_id`),
  CONSTRAINT `departments_head_user_id_foreign` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_signatories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_signatories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `signatory_id` bigint(20) unsigned NOT NULL,
  `sign_order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_signatories_document_id_foreign` (`document_id`),
  KEY `document_signatories_signatory_id_foreign` (`signatory_id`),
  CONSTRAINT `document_signatories_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_signatories_signatory_id_foreign` FOREIGN KEY (`signatory_id`) REFERENCES `signatories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_signatories` int(11) NOT NULL DEFAULT '1',
  `request_count` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documents_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_file_edits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drive_file_edits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drive_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` enum('edit','replace','rename') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'edit',
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drive_file_edits_user_id_foreign` (`user_id`),
  KEY `drive_file_edits_drive_file_id_created_at_index` (`drive_file_id`,`created_at`),
  CONSTRAINT `drive_file_edits_drive_file_id_foreign` FOREIGN KEY (`drive_file_id`) REFERENCES `drive_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drive_file_edits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_file_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drive_file_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drive_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission` enum('view','edit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'view',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drive_file_shares_drive_file_id_user_id_unique` (`drive_file_id`,`user_id`),
  KEY `drive_file_shares_user_id_index` (`user_id`),
  CONSTRAINT `drive_file_shares_drive_file_id_foreign` FOREIGN KEY (`drive_file_id`) REFERENCES `drive_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drive_file_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drive_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drive_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('folder','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drive_files_school_id_foreign` (`school_id`),
  KEY `drive_files_owner_id_parent_id_index` (`owner_id`,`parent_id`),
  KEY `drive_files_parent_id_type_index` (`parent_id`,`type`),
  CONSTRAINT `drive_files_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drive_files_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `drive_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `drive_files_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `education_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `education_nodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `node_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT '0',
  `is_offered` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `education_nodes_parent_id_index` (`parent_id`),
  KEY `education_nodes_node_type_index` (`node_type`),
  CONSTRAINT `education_nodes_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `education_nodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enrollment_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_documents_school_id_foreign` (`school_id`),
  KEY `enrollment_documents_enrollment_id_foreign` (`enrollment_id`),
  CONSTRAINT `enrollment_documents_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_documents_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enrollment_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_drafts_student_id_foreign` (`student_id`),
  KEY `enrollment_drafts_term_id_foreign` (`term_id`),
  CONSTRAINT `enrollment_drafts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_drafts_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enrollment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `old_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_logs_school_id_foreign` (`school_id`),
  KEY `enrollment_logs_changed_by_foreign` (`changed_by`),
  KEY `enrollment_logs_enrollment_id_foreign` (`enrollment_id`),
  CONSTRAINT `enrollment_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `enrollment_logs_enrollment_id_foreign` FOREIGN KEY (`enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enrollment_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollment_type_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(12,2) DEFAULT NULL,
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructor_title` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_details` text COLLATE utf8mb4_unicode_ci,
  `is_open` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_settings_academic_year_id_term_id_unique` (`academic_year_id`,`term_id`),
  KEY `enrollment_settings_term_id_foreign` (`term_id`),
  KEY `enrollment_settings_created_by_foreign` (`created_by`),
  KEY `enrollment_settings_updated_by_foreign` (`updated_by`),
  KEY `enrollment_settings_enrollment_type_id_foreign` (`enrollment_type_id`),
  CONSTRAINT `enrollment_settings_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enrollment_settings_enrollment_type_id_foreign` FOREIGN KEY (`enrollment_type_id`) REFERENCES `enrollment_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enrollment_settings_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enrollment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_activities_school_id_name_unique` (`school_id`,`name`),
  KEY `event_activities_school_id_index` (`school_id`),
  CONSTRAINT `event_activities_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `recipient_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `time_in_at` time DEFAULT NULL,
  `time_out_at` time DEFAULT NULL,
  `capture_source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_attendance_unique` (`event_id`,`recipient_id`,`attendance_date`),
  KEY `event_attendances_recipient_id_foreign` (`recipient_id`),
  KEY `event_attendances_event_id_attendance_date_index` (`event_id`,`attendance_date`),
  CONSTRAINT `event_attendances_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `certificate_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_attendances_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `certificate_event_recipients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_roles_event_id_name_unique` (`event_id`,`name`),
  KEY `event_roles_event_id_index` (`event_id`),
  CONSTRAINT `event_roles_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `certificate_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_types_event_id_name_unique` (`event_id`,`name`),
  KEY `event_types_event_id_index` (`event_id`),
  CONSTRAINT `event_types_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `certificate_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `finance_discount_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_discount_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_kind` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `applies_to` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'total',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_discount_types_school_code_unique` (`school_id`,`code`),
  CONSTRAINT `finance_discount_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `finance_fee_setups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_fee_setups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `payment_plan_id` bigint(20) unsigned DEFAULT NULL,
  `year_level` tinyint(3) unsigned DEFAULT NULL,
  `fee_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tuition',
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_basis` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_fee_setups_school_code_unique` (`school_id`,`code`),
  KEY `finance_fee_setups_academic_year_id_foreign` (`academic_year_id`),
  KEY `finance_fee_setups_term_id_foreign` (`term_id`),
  KEY `finance_fee_setups_education_node_id_foreign` (`education_node_id`),
  KEY `finance_fee_setups_program_id_foreign` (`program_id`),
  KEY `finance_fee_setups_scope_idx` (`school_id`,`academic_year_id`,`term_id`),
  KEY `finance_fee_setups_payment_plan_id_foreign` (`payment_plan_id`),
  CONSTRAINT `finance_fee_setups_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_fee_setups_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_fee_setups_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_fee_setups_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_fee_setups_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `finance_fee_setups_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `finance_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `soa_frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_term',
  `soa_generation_day` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `auto_generate_soa` tinyint(1) NOT NULL DEFAULT '1',
  `auto_invoice_on_billing` tinyint(1) NOT NULL DEFAULT '1',
  `invoice_due_days` smallint(5) unsigned NOT NULL DEFAULT '7',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PHP',
  `soa_footer_note` text COLLATE utf8mb4_unicode_ci,
  `last_soa_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_settings_school_id_unique` (`school_id`),
  CONSTRAINT `finance_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grade_level_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_level_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `education_node_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_level_subject_unique` (`education_node_id`,`subject_id`),
  KEY `grade_level_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `grade_level_subjects_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grade_level_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grading_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grading_system_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grading_components_grading_system_id_foreign` (`grading_system_id`),
  CONSTRAINT `grading_components_grading_system_id_foreign` FOREIGN KEY (`grading_system_id`) REFERENCES `grading_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grading_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','gpa','letter','competency','pass_fail') COLLATE utf8mb4_unicode_ci NOT NULL,
  `passing_mark` decimal(5,2) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_members_group_id_foreign` (`group_id`),
  KEY `group_members_profile_id_foreign` (`profile_id`),
  CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_profile_id_foreign` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'parent',
  `relationship` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `landline_number` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_emergency_contact` tinyint(1) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_student_id_type_index` (`student_id`,`type`),
  CONSTRAINT `guardians_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `finance_fee_setup_id` bigint(20) unsigned DEFAULT NULL,
  `finance_discount_type_id` bigint(20) unsigned DEFAULT NULL,
  `fee_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_basis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `quantity` decimal(8,2) NOT NULL DEFAULT '1.00',
  `unit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_school_id_foreign` (`school_id`),
  KEY `invoice_items_finance_fee_setup_id_foreign` (`finance_fee_setup_id`),
  KEY `invoice_items_finance_discount_type_id_foreign` (`finance_discount_type_id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `invoice_items_finance_discount_type_id_foreign` FOREIGN KEY (`finance_discount_type_id`) REFERENCES `finance_discount_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_finance_fee_setup_id_foreign` FOREIGN KEY (`finance_fee_setup_id`) REFERENCES `finance_fee_setups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `student_enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `subtotal_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL,
  `status` enum('paid','partial','unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `billing_date` date DEFAULT NULL,
  `issued_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_school_number_unique` (`school_id`,`invoice_number`),
  KEY `invoices_student_id_foreign` (`student_id`),
  KEY `invoices_student_enrollment_id_foreign` (`student_enrollment_id`),
  KEY `invoices_school_student_index` (`school_id`,`student_id`),
  KEY `invoices_school_billing_date_index` (`school_id`,`billing_date`),
  CONSTRAINT `invoices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_student_enrollment_id_foreign` FOREIGN KEY (`student_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `student_enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'charge',
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance_after` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledger_entries_student_id_foreign` (`student_id`),
  KEY `ledger_entries_student_enrollment_id_foreign` (`student_enrollment_id`),
  KEY `ledger_entries_invoice_id_foreign` (`invoice_id`),
  KEY `ledger_entries_payment_id_foreign` (`payment_id`),
  KEY `ledger_school_student_date_index` (`school_id`,`student_id`,`entry_date`),
  KEY `ledger_school_student_id_index` (`school_id`,`student_id`,`id`),
  CONSTRAINT `ledger_entries_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledger_entries_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledger_entries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ledger_entries_student_enrollment_id_foreign` FOREIGN KEY (`student_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ledger_entries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lesson_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lesson_resources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `topic_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `competency_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_resources_school_id_foreign` (`school_id`),
  KEY `lesson_resources_subject_id_foreign` (`subject_id`),
  KEY `lesson_resources_topic_id_foreign` (`topic_id`),
  KEY `lesson_resources_competency_id_foreign` (`competency_id`),
  KEY `lesson_resources_created_by_foreign` (`created_by`),
  KEY `lesson_resources_program_id_subject_id_index` (`program_id`,`subject_id`),
  KEY `lesson_resources_lesson_id_index` (`lesson_id`),
  CONSTRAINT `lesson_resources_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_resources_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_resources_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_resources_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_resources_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_resources_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lesson_resources_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lessons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `topic_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `sequence` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lessons_topic_id_name_unique` (`topic_id`,`name`),
  KEY `lessons_school_id_foreign` (`school_id`),
  KEY `lessons_subject_id_foreign` (`subject_id`),
  KEY `lessons_created_by_foreign` (`created_by`),
  KEY `lessons_sort_order_index` (`sort_order`),
  CONSTRAINT `lessons_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lessons_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lessons_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lessons_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modalities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modalities_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `module_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_dependencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint(20) unsigned NOT NULL,
  `depends_on_module_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_dependencies_module_id_depends_on_module_id_unique` (`module_id`,`depends_on_module_id`),
  KEY `module_dependencies_depends_on_module_id_foreign` (`depends_on_module_id`),
  CONSTRAINT `module_dependencies_depends_on_module_id_foreign` FOREIGN KEY (`depends_on_module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `module_dependencies_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `office_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `office_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `office_type_id` bigint(20) unsigned DEFAULT NULL,
  `head_role_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offices_office_type_id_foreign` (`office_type_id`),
  KEY `offices_head_role_id_foreign` (`head_role_id`),
  CONSTRAINT `offices_head_role_id_foreign` FOREIGN KEY (`head_role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offices_office_type_id_foreign` FOREIGN KEY (`office_type_id`) REFERENCES `office_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencies` json DEFAULT NULL,
  `class_start_date` date DEFAULT NULL,
  `class_end_date` date DEFAULT NULL,
  `billing_end_date` date DEFAULT NULL,
  `billing_day` tinyint(3) unsigned DEFAULT NULL,
  `due_days` tinyint(3) unsigned DEFAULT NULL,
  `cash_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `cash_discount_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash_discount_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `installments` smallint(5) unsigned NOT NULL DEFAULT '1',
  `down_payment_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `down_payment_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `dp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `interest_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_plans_school_id_code_unique` (`school_id`,`code`),
  CONSTRAINT `payment_plans_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `system_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_submissions_student_id_foreign` (`student_id`),
  KEY `payment_submissions_reviewed_by_foreign` (`reviewed_by`),
  KEY `payment_submissions_payment_id_foreign` (`payment_id`),
  KEY `payment_submissions_school_id_status_index` (`school_id`,`status`),
  KEY `payment_submissions_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `payment_submissions_status_index` (`status`),
  CONSTRAINT `payment_submissions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_submissions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_submissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `training_enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'miscellaneous',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_school_id_foreign` (`school_id`),
  KEY `payments_student_id_foreign` (`student_id`),
  KEY `payments_training_enrollment_id_foreign` (`training_enrollment_id`),
  KEY `payments_payment_type_index` (`payment_type`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_training_enrollment_id_foreign` FOREIGN KEY (`training_enrollment_id`) REFERENCES `training_enrollments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `penalty_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `penalty_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `basis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grace_days` smallint(5) unsigned NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penalty_rules_school_id_code_unique` (`school_id`,`code`),
  CONSTRAINT `penalty_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pricings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `teacher_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `staff_price` int(11) NOT NULL DEFAULT '80',
  `parent_price` int(11) NOT NULL DEFAULT '30',
  `alumni_price` int(11) NOT NULL DEFAULT '20',
  `setup_fee` int(11) NOT NULL DEFAULT '20000',
  `addon_price` int(11) NOT NULL DEFAULT '20',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pricings_plan_name_unique` (`plan_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `profile_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `program_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `enforce_capacity` tinyint(1) NOT NULL DEFAULT '1',
  `waitlist_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `default_delivery_mode` enum('onsite','online','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'onsite',
  `allow_cross_program_enrollment` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_settings_program_id_unique` (`program_id`),
  CONSTRAINT `program_settings_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `program_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `year_level` int(11) NOT NULL,
  `semester_number` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_subject_unique` (`program_id`,`subject_id`,`year_level`,`semester_number`),
  KEY `program_subjects_subject_id_foreign` (`subject_id`),
  KEY `program_subjects_room_id_index` (`room_id`),
  CONSTRAINT `program_subjects_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `program_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `program_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_types_school_id_name_unique` (`school_id`,`name`),
  CONSTRAINT `program_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `college_id` bigint(20) unsigned NOT NULL,
  `program_head_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `requires_admission_test` tinyint(1) NOT NULL DEFAULT '0',
  `admission_passing_score` int(11) DEFAULT NULL,
  `admission_max_attempts` int(11) NOT NULL DEFAULT '1',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_school_id_code_unique` (`school_id`,`code`),
  KEY `programs_campus_id_foreign` (`campus_id`),
  KEY `programs_college_id_foreign` (`college_id`),
  KEY `programs_program_head_id_foreign` (`program_head_id`),
  KEY `programs_education_node_id_index` (`education_node_id`),
  CONSTRAINT `programs_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `programs_college_id_foreign` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `programs_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `programs_program_head_id_foreign` FOREIGN KEY (`program_head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `programs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) unsigned NOT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `academic_level_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned DEFAULT NULL,
  `competency_id` bigint(20) unsigned DEFAULT NULL,
  `question_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `difficulty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyword` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `questions_topic_id_foreign` (`topic_id`),
  KEY `questions_teacher_id_foreign` (`teacher_id`),
  KEY `questions_academic_level_id_foreign` (`academic_level_id`),
  CONSTRAINT `questions_academic_level_id_foreign` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_topic_id_foreign` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `display_duration` int(11) NOT NULL DEFAULT '1',
  `activated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `request_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int(11) NOT NULL DEFAULT '1',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_approvals_request_id_foreign` (`request_id`),
  KEY `request_approvals_approver_id_foreign` (`approver_id`),
  CONSTRAINT `request_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `request_approvals_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL,
  `status` enum('pending','in_review','approved','rejected','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requests_school_id_foreign` (`school_id`),
  KEY `requests_requested_by_foreign` (`requested_by`),
  CONSTRAINT `requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requests_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_head_role` tinyint(1) NOT NULL DEFAULT '0',
  `badge_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_text_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int(10) unsigned NOT NULL DEFAULT '40',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rooms_school_id_index` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedule_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedule_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `day_of_week` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `conflict_reasons` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_sessions_schedule_id_index` (`schedule_id`),
  KEY `schedule_sessions_section_id_day_of_week_index` (`section_id`,`day_of_week`),
  KEY `schedule_sessions_teacher_id_day_of_week_index` (`teacher_id`,`day_of_week`),
  KEY `schedule_sessions_room_id_day_of_week_index` (`room_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scheduler_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheduler_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `min_session_hours` decimal(4,2) NOT NULL DEFAULT '1.00',
  `max_session_hours` decimal(4,2) NOT NULL DEFAULT '3.00',
  `max_subjects_per_day` int(10) unsigned NOT NULL DEFAULT '6',
  `max_hours_per_day` decimal(4,2) NOT NULL DEFAULT '8.00',
  `max_hours_per_week` decimal(5,2) NOT NULL DEFAULT '40.00',
  `max_allowed_gap` int(10) unsigned NOT NULL DEFAULT '60',
  `min_days_per_week` int(10) unsigned NOT NULL DEFAULT '1',
  `max_days_per_week` int(10) unsigned NOT NULL DEFAULT '3',
  `teacher_max_hours_per_week` int(10) unsigned NOT NULL DEFAULT '24',
  `teacher_max_hours_per_day` int(10) unsigned NOT NULL DEFAULT '5',
  `teacher_work_days_per_week` int(10) unsigned NOT NULL DEFAULT '5',
  `teacher_min_hours_per_day` decimal(4,2) NOT NULL DEFAULT '1.00',
  `prioritize_full_time` tinyint(1) NOT NULL DEFAULT '1',
  `part_time_min_hours_per_day` decimal(4,2) NOT NULL DEFAULT '1.00',
  `allow_gaps` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scheduler_settings_school_id_unique` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `score` decimal(8,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_school_id_term_id_is_active_index` (`school_id`,`term_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scholarships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `coverage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tuition',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scholarships_school_id_code_unique` (`school_id`,`code`),
  CONSTRAINT `scholarships_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `school_modalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_modalities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `modality_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_modalities_school_id_foreign` (`school_id`),
  KEY `school_modalities_modality_id_foreign` (`modality_id`),
  CONSTRAINT `school_modalities_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_modalities_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `school_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `module_id` bigint(20) unsigned DEFAULT NULL,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_modules_school_id_module_name_unique` (`school_id`,`module_name`),
  CONSTRAINT `school_modules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `school_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_seal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_hero` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_header` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_footer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_background` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header_space` decimal(4,2) NOT NULL DEFAULT '1.00',
  `footer_space` decimal(4,2) NOT NULL DEFAULT '0.50',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vision` text COLLATE utf8mb4_unicode_ci,
  `mission` text COLLATE utf8mb4_unicode_ci,
  `head_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `head_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrar_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrar_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `business_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sss_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_permit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `building` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phase` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barangay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_admission_test` tinyint(1) NOT NULL DEFAULT '0',
  `admission_test_mode` enum('required','optional','diagnostic_only') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'school',
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic',
  `plan_expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `approval_status` enum('approved','pending','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `dashboard_identity` json DEFAULT NULL,
  `sidebar_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#1e293b',
  PRIMARY KEY (`id`),
  UNIQUE KEY `schools_slug_unique` (`slug`),
  UNIQUE KEY `schools_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `section_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `section_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `units` decimal(4,2) NOT NULL DEFAULT '3.00',
  `hours_per_week` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_subjects_section_id_subject_id_unique` (`section_id`,`subject_id`),
  KEY `section_subjects_section_id_index` (`section_id`),
  KEY `section_subjects_subject_id_index` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_level` int(11) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT '0',
  `adviser_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sections_program_id_semester_id_name_unique` (`program_id`,`term_id`,`name`),
  KEY `sections_school_id_foreign` (`school_id`),
  KEY `sections_adviser_id_foreign` (`adviser_id`),
  KEY `sections_term_id_foreign` (`term_id`),
  CONSTRAINT `sections_adviser_id_foreign` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sections_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sections_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `signatories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signatories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `office_id` bigint(20) unsigned DEFAULT NULL,
  `employee_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `tin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sss` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `philhealth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagibig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documents` json DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statement_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statement_of_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `soa_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `student_enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_term',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `period_label` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_charges` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_credits` decimal(12,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `line_items` json DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `soa_school_number_unique` (`school_id`,`soa_number`),
  KEY `statement_of_accounts_student_id_foreign` (`student_id`),
  KEY `statement_of_accounts_student_enrollment_id_foreign` (`student_enrollment_id`),
  KEY `soa_school_student_period_index` (`school_id`,`student_id`,`period_end`),
  CONSTRAINT `statement_of_accounts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `statement_of_accounts_student_enrollment_id_foreign` FOREIGN KEY (`student_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `statement_of_accounts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_academic_backgrounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_academic_backgrounds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `education_level` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `school_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_type` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_grade_level` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_started` year(4) DEFAULT NULL,
  `year_ended` year(4) DEFAULT NULL,
  `gpa` decimal(5,2) DEFAULT NULL,
  `honors` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_academic_backgrounds_student_id_education_level_index` (`student_id`,`education_level`),
  KEY `student_academic_backgrounds_education_node_id_index` (`education_node_id`),
  CONSTRAINT `student_academic_backgrounds_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_enrollment_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_enrollment_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_enrollment_id` bigint(20) unsigned NOT NULL,
  `class_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `modality` enum('modular','online','face_to_face') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'modular',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `status` enum('enrolled','dropped','completed','passed','failed','credit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `progress_percentage` int(11) NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_class_enrollment` (`student_enrollment_id`,`class_id`),
  KEY `student_enrollment_subjects_class_id_foreign` (`class_id`),
  KEY `student_enrollment_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `student_enrollment_subjects_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollment_subjects_student_enrollment_id_foreign` FOREIGN KEY (`student_enrollment_id`) REFERENCES `student_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollment_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `selected_subject_count` int(11) NOT NULL DEFAULT '0',
  `enrollment_setting_id` bigint(20) unsigned DEFAULT NULL,
  `section_id` bigint(20) unsigned DEFAULT NULL,
  `total_units` decimal(5,2) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_level` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `payment_plan_id` bigint(20) unsigned DEFAULT NULL,
  `payment_option` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_frequency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scholarship_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scholarship_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `scholarship_percent` decimal(5,2) DEFAULT NULL,
  `scholarship_apply_to` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreed_to_penalty` tinyint(1) NOT NULL DEFAULT '0',
  `certified_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_accuracy` tinyint(1) NOT NULL DEFAULT '0',
  `data_privacy_consent` tinyint(1) NOT NULL DEFAULT '0',
  `certified_at` timestamp NULL DEFAULT NULL,
  `modality_id` bigint(20) unsigned DEFAULT NULL,
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `year_level` tinyint(3) unsigned DEFAULT NULL,
  `student_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'new|transferee|returnee|regular|irregular',
  `education_level` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `enrollee_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','submitted','exam_passed','exam_failed','assessed','provisional','rejected','sent_billing','billed','partially_paid','enrolled','provisionally_enrolled','dropped','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `billing_cleared_as` enum('assessed','provisional') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_due_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_semester` (`student_id`,`academic_year_id`,`term_id`),
  KEY `student_enrollments_academic_year_id_foreign` (`academic_year_id`),
  KEY `student_enrollments_program_id_foreign` (`program_id`),
  KEY `student_enrollments_term_id_foreign` (`term_id`),
  KEY `student_enrollments_school_id_foreign` (`school_id`),
  KEY `student_enrollments_campus_id_foreign` (`campus_id`),
  KEY `student_enrollments_enrollment_setting_id_foreign` (`enrollment_setting_id`),
  KEY `student_enrollments_section_id_foreign` (`section_id`),
  KEY `student_enrollments_approved_by_foreign` (`approved_by`),
  KEY `student_enrollments_modality_id_foreign` (`modality_id`),
  KEY `student_enrollments_education_node_id_foreign` (`education_node_id`),
  KEY `student_enrollments_payment_plan_id_foreign` (`payment_plan_id`),
  CONSTRAINT `student_enrollments_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_enrollment_setting_id_foreign` FOREIGN KEY (`enrollment_setting_id`) REFERENCES `enrollment_settings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_modality_id_foreign` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_health_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_health_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `has_medical_condition` tinyint(1) NOT NULL DEFAULT '0',
  `medical_conditions` json DEFAULT NULL,
  `other_medical_condition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `takes_maintenance_medication` tinyint(1) NOT NULL DEFAULT '0',
  `medication_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medication_dosage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medication_schedule` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','unknown') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_medical_instructions` text COLLATE utf8mb4_unicode_ci,
  `is_pwd` tinyint(1) NOT NULL DEFAULT '0',
  `pwd_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwd_id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_contact` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_mobile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_alt` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_health_records_student_id_unique` (`student_id`),
  CONSTRAINT `student_health_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_id_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_id_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `orientation` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'portrait',
  `barcode_source` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lrn',
  `show_back` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_settings_school_id_unique` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `status` enum('active','completed','shifted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `completion_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_programs_student_id_program_id_unique` (`student_id`,`program_id`),
  KEY `student_programs_program_id_foreign` (`program_id`),
  CONSTRAINT `student_programs_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_programs_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `home_school_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `student_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lrn` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','graduated','transferred','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_type` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexual_orientation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `civil_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `government_id_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `government_id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `landline_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unit_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `building_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdivision` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barangay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_municipality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_number_unique` (`student_number`),
  UNIQUE KEY `students_user_id_unique` (`user_id`),
  KEY `students_school_id_foreign` (`school_id`),
  KEY `students_home_school_id_foreign` (`home_school_id`),
  CONSTRAINT `students_home_school_id_foreign` FOREIGN KEY (`home_school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subject_credit_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_credit_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `source_subject_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_subject_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_school` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_grade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_units` decimal(4,1) DEFAULT NULL,
  `credited_units` decimal(4,1) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'transferee',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `evaluated_by` bigint(20) unsigned DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_credit_evaluations_subject_id_foreign` (`subject_id`),
  KEY `subject_credit_evaluations_evaluated_by_foreign` (`evaluated_by`),
  KEY `subject_credit_evaluations_student_id_status_index` (`student_id`,`status`),
  KEY `subject_credit_evaluations_school_id_status_index` (`school_id`,`status`),
  CONSTRAINT `subject_credit_evaluations_evaluated_by_foreign` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subject_credit_evaluations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_credit_evaluations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_credit_evaluations_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subject_offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_offerings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `year_level` tinyint(3) unsigned DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `is_for_irregular` tinyint(1) NOT NULL DEFAULT '1',
  `offering_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_mode` enum('pure_online','online_teacher','face_to_face') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pure_online',
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `max_slots` int(11) DEFAULT NULL,
  `enrolled_count` int(11) NOT NULL DEFAULT '0',
  `status` enum('draft','open','ongoing','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_offerings_offering_code_unique` (`offering_code`),
  UNIQUE KEY `subj_off_unique` (`term_id`,`subject_id`,`program_id`,`year_level`),
  KEY `subject_offerings_subject_id_foreign` (`subject_id`),
  KEY `subject_offerings_teacher_id_foreign` (`teacher_id`),
  KEY `subject_offerings_program_id_foreign` (`program_id`),
  CONSTRAINT `subject_offerings_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subject_offerings_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_offerings_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `subject_offerings_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subject_prerequisites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_prerequisites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` bigint(20) unsigned NOT NULL,
  `prerequisite_subject_id` bigint(20) unsigned NOT NULL,
  `minimum_grade` decimal(5,2) DEFAULT NULL,
  `is_strict` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_pre_unique` (`subject_id`,`prerequisite_subject_id`),
  KEY `subject_prerequisites_prerequisite_subject_id_foreign` (`prerequisite_subject_id`),
  CONSTRAINT `subject_prerequisites_prerequisite_subject_id_foreign` FOREIGN KEY (`prerequisite_subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_prerequisites_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `units` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_basic_ed` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `scope` enum('academic','training') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'academic',
  `category` enum('gen_ed','prof_ed','major','pe','nstp','internship') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'major',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_school_id_academic_level_id_code_unique` (`school_id`,`code`),
  KEY `subjects_created_by_foreign` (`created_by`),
  KEY `subjects_scope_index` (`scope`),
  KEY `subjects_category_index` (`category`),
  KEY `subjects_is_basic_ed_index` (`is_basic_ed`),
  CONSTRAINT `subjects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `session_timeout` int(11) NOT NULL DEFAULT '15',
  `pagination` int(11) NOT NULL DEFAULT '10',
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Manila',
  `date_format` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y-m-d',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `upload_limit` int(11) NOT NULL DEFAULT '10',
  `allowed_file_types` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backup_schedule` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `school_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_password` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_encryption` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_from_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_api` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_provider` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_api_key` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_api_secret` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_sender_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_from_number` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_school_id_unique` (`school_id`),
  CONSTRAINT `system_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_availabilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `day_of_week` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_availabilities_teacher_id_day_of_week_index` (`teacher_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `preferred_block` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_hours_per_day` decimal(4,2) DEFAULT NULL,
  `max_hours_per_week` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_preferences_teacher_id_unique` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `employee_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` enum('active','inactive','on_leave','resigned','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `employment_start` date DEFAULT NULL,
  `employment_end` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `teacher_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `qualification_level` enum('primary','secondary','assistant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_subjects_teacher_id_subject_id_unique` (`teacher_id`,`subject_id`),
  KEY `teacher_subjects_subject_id_foreign` (`subject_id`),
  CONSTRAINT `teacher_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teacher_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `employee_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `highest_degree` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `degree_school` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prc_license` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prc_expiry` date DEFAULT NULL,
  `tin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sss` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `philhealth` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagibig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaching_load_limit` int(11) DEFAULT NULL,
  `adviser_flag` tinyint(1) NOT NULL DEFAULT '0',
  `thesis_flag` tinyint(1) NOT NULL DEFAULT '0',
  `research_flag` tinyint(1) NOT NULL DEFAULT '0',
  `documents` json DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `education_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'higher_ed',
  `education_node_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_unique_name_per_year` (`school_id`,`academic_year_id`,`name`,`education_node_id`),
  KEY `semesters_school_academic_year_idx` (`school_id`,`academic_year_id`),
  KEY `terms_school_id_education_level_index` (`school_id`,`education_level`),
  KEY `terms_education_node_id_foreign` (`education_node_id`),
  CONSTRAINT `semesters_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `terms_education_node_id_foreign` FOREIGN KEY (`education_node_id`) REFERENCES `education_nodes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_availabilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `show_score_immediately` tinyint(1) NOT NULL DEFAULT '0',
  `show_correct_answers` tinyint(1) NOT NULL DEFAULT '0',
  `allow_retakes` tinyint(1) NOT NULL DEFAULT '0',
  `allow_practice` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_availabilities_test_id_foreign` (`test_id`),
  CONSTRAINT `test_availabilities_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_question_type_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_question_type_points` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `question_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_question_type_points_test_id_foreign` (`test_id`),
  CONSTRAINT `test_question_type_points_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  `points` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `timer_minutes` int(11) DEFAULT NULL,
  `attempts_allowed` int(11) NOT NULL DEFAULT '1',
  `passing_score` int(11) NOT NULL DEFAULT '75',
  `show_results` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'after_exam',
  `show_correct_answers` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'after_exam',
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT '0',
  `shuffle_mcq_choices` tinyint(1) NOT NULL DEFAULT '0',
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `availability_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'duration',
  `duration_minutes` int(11) DEFAULT NULL,
  `mode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assessment_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_settings_test_id_foreign` (`test_id`),
  CONSTRAINT `test_settings_test_id_foreign` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `mcq_count` int(11) NOT NULL DEFAULT '0',
  `tf_count` int(11) NOT NULL DEFAULT '0',
  `mtf_count` int(11) NOT NULL DEFAULT '0',
  `identification_count` int(11) NOT NULL DEFAULT '0',
  `matching_count` int(11) NOT NULL DEFAULT '0',
  `fib_count` int(11) NOT NULL DEFAULT '0',
  `enumeration_count` int(11) NOT NULL DEFAULT '0',
  `essay_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `class_id` bigint(20) unsigned DEFAULT NULL,
  `difficulty` json DEFAULT NULL,
  `academic_levels` json DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tests_class_id_foreign` (`class_id`),
  CONSTRAINT `tests_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sequence` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topics_school_id_foreign` (`school_id`),
  KEY `topics_created_by_foreign` (`created_by`),
  KEY `topics_subject_id_name_index` (`subject_id`,`name`),
  KEY `topics_sort_order_index` (`sort_order`),
  CONSTRAINT `topics_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `topics_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `topics_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trainees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `trainee_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trainees_trainee_number_unique` (`trainee_number`),
  KEY `trainees_profile_id_foreign` (`profile_id`),
  CONSTRAINT `trainees_profile_id_foreign` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_assessment_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_assessment_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_assessment_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_session_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_items` int(11) DEFAULT NULL,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_enrollment_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_attendance_training_enrollment_id_foreign` (`training_enrollment_id`),
  CONSTRAINT `training_attendance_training_enrollment_id_foreign` FOREIGN KEY (`training_enrollment_id`) REFERENCES `training_enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_enrollment_id` bigint(20) unsigned NOT NULL,
  `training_type_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_certificates_training_enrollment_id_foreign` (`training_enrollment_id`),
  CONSTRAINT `training_certificates_training_enrollment_id_foreign` FOREIGN KEY (`training_enrollment_id`) REFERENCES `training_enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `training_type_id` bigint(20) unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `fee` decimal(10,2) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `delivery_mode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `training_provider_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_courses_training_type_id_foreign` (`training_type_id`),
  KEY `training_courses_training_provider_id_foreign` (`training_provider_id`),
  CONSTRAINT `training_courses_training_provider_id_foreign` FOREIGN KEY (`training_provider_id`) REFERENCES `training_providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `training_courses_training_type_id_foreign` FOREIGN KEY (`training_type_id`) REFERENCES `training_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trainee_id` bigint(20) unsigned NOT NULL,
  `training_course_id` bigint(20) unsigned DEFAULT NULL,
  `training_session_id` bigint(20) unsigned NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `expires_at` timestamp NULL DEFAULT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('internal','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  PRIMARY KEY (`id`),
  KEY `training_enrollments_trainee_id_foreign` (`trainee_id`),
  KEY `training_enrollments_training_session_id_foreign` (`training_session_id`),
  CONSTRAINT `training_enrollments_trainee_id_foreign` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_training_session_id_foreign` FOREIGN KEY (`training_session_id`) REFERENCES `training_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_course_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_materials_training_course_id_foreign` (`training_course_id`),
  CONSTRAINT `training_materials_training_course_id_foreign` FOREIGN KEY (`training_course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_providers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_course_id` bigint(20) unsigned NOT NULL,
  `session_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_slots` int(11) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `qr_code_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_sessions_training_course_id_foreign` (`training_course_id`),
  CONSTRAINT `training_sessions_training_course_id_foreign` FOREIGN KEY (`training_course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `training_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transcript_edit_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transcript_edit_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `enrollment_subject_id` bigint(20) unsigned DEFAULT NULL,
  `old_grade` decimal(5,2) DEFAULT NULL,
  `new_grade` decimal(5,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned NOT NULL,
  `program_head_id` bigint(20) unsigned DEFAULT NULL,
  `program_head_approved_at` timestamp NULL DEFAULT NULL,
  `program_head_note` text COLLATE utf8mb4_unicode_ci,
  `dean_id` bigint(20) unsigned DEFAULT NULL,
  `dean_approved_at` timestamp NULL DEFAULT NULL,
  `dean_note` text COLLATE utf8mb4_unicode_ci,
  `applied_at` timestamp NULL DEFAULT NULL,
  `applied_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transcript_edit_requests_subject_id_foreign` (`subject_id`),
  KEY `transcript_edit_requests_enrollment_subject_id_foreign` (`enrollment_subject_id`),
  KEY `transcript_edit_requests_requested_by_foreign` (`requested_by`),
  KEY `transcript_edit_requests_program_head_id_foreign` (`program_head_id`),
  KEY `transcript_edit_requests_dean_id_foreign` (`dean_id`),
  KEY `transcript_edit_requests_applied_by_foreign` (`applied_by`),
  KEY `transcript_edit_requests_student_id_subject_id_index` (`student_id`,`subject_id`),
  KEY `transcript_edit_requests_status_index` (`status`),
  CONSTRAINT `transcript_edit_requests_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transcript_edit_requests_dean_id_foreign` FOREIGN KEY (`dean_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transcript_edit_requests_enrollment_subject_id_foreign` FOREIGN KEY (`enrollment_subject_id`) REFERENCES `student_enrollment_subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transcript_edit_requests_program_head_id_foreign` FOREIGN KEY (`program_head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transcript_edit_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transcript_edit_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transcript_edit_requests_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_colleges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_colleges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `college_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_colleges_user_id_college_id_unique` (`user_id`,`college_id`),
  KEY `user_colleges_college_id_foreign` (`college_id`),
  CONSTRAINT `user_colleges_college_id_foreign` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_colleges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_programs_user_id_program_id_unique` (`user_id`,`program_id`),
  KEY `user_programs_program_id_foreign` (`program_id`),
  CONSTRAINT `user_programs_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_programs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `assignment_type` enum('permanent','temporary','acting','concurrent','part_time','leave','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent',
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_table_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_table_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `table_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_settings` json DEFAULT NULL,
  `rows_per_page` int(11) NOT NULL DEFAULT '10',
  `sort_column` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_direction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_table_preferences_user_id_table_key_index` (`user_id`,`table_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `google2fa_secret` text COLLATE utf8mb4_unicode_ci,
  `recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dashboard_identity` json DEFAULT NULL,
  `sidebar_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Manila',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_department_id_foreign` (`department_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_conference_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_conference_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `context_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `allow_repeated_room_creation` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `granted_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_conference_permissions_teacher_id_foreign` (`teacher_id`),
  KEY `video_conference_permissions_student_id_foreign` (`student_id`),
  KEY `video_conference_permissions_group_id_foreign` (`group_id`),
  KEY `video_conference_permissions_granted_by_user_id_foreign` (`granted_by_user_id`),
  KEY `vcp_school_student_active_idx` (`school_id`,`student_id`,`is_active`),
  CONSTRAINT `video_conference_permissions_granted_by_user_id_foreign` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_permissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_permissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_permissions_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_conference_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_conference_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `context_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `permission_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `owner_user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `auto_end_minutes` smallint(5) unsigned NOT NULL DEFAULT '180',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_conference_rooms_group_id_foreign` (`group_id`),
  KEY `video_conference_rooms_permission_id_foreign` (`permission_id`),
  KEY `video_conference_rooms_created_by_foreign` (`created_by`),
  KEY `video_conference_rooms_owner_user_id_foreign` (`owner_user_id`),
  KEY `vcr_school_status_idx` (`school_id`,`status`),
  CONSTRAINT `video_conference_rooms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_rooms_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_rooms_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_rooms_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `video_conference_permissions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_rooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_conference_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_conference_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `room_id` bigint(20) unsigned NOT NULL,
  `started_by` bigint(20) unsigned NOT NULL,
  `host_user_id` bigint(20) unsigned NOT NULL,
  `reopened_from_session_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `auto_end_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `ended_by` bigint(20) unsigned DEFAULT NULL,
  `ended_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_conference_sessions_room_id_foreign` (`room_id`),
  KEY `video_conference_sessions_started_by_foreign` (`started_by`),
  KEY `video_conference_sessions_host_user_id_foreign` (`host_user_id`),
  KEY `video_conference_sessions_reopened_from_session_id_foreign` (`reopened_from_session_id`),
  KEY `video_conference_sessions_ended_by_foreign` (`ended_by`),
  KEY `vcs_school_status_idx` (`school_id`,`status`),
  CONSTRAINT `video_conference_sessions_ended_by_foreign` FOREIGN KEY (`ended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_sessions_host_user_id_foreign` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_sessions_reopened_from_session_id_foreign` FOREIGN KEY (`reopened_from_session_id`) REFERENCES `video_conference_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `video_conference_sessions_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `video_conference_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_sessions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_conference_sessions_started_by_foreign` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_01_01_000003_create_academic_levels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_01_01_000004_create_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_01_01_000005_create_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_01_14_000006_create_topics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_01_14_000008_create_teacher_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_01_14_000009_create_semesters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_01_15_082700_create_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_01_15_082808_create_test_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_01_15_084944_create_test_availabilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_01_15_094123_create_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_15_094416_create_choices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_15_155428_add_topic_id_to_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_02_05_130549_create_test_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_02_06_151503_create_configurations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_02_09_150847_create_school_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_02_10_142423_add_2fa_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_02_10_142649_add_recovery_codes_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_02_10_144812_add_id_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_02_10_145213_make_school_id_nullable_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_02_10_150154_add_type_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_02_10_181008_add_slug_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_02_11_005627_add_is_active_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_02_11_074232_create_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_02_11_082356_add_module_id_to_school_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_02_11_082400_add_expires_at_to_school_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_02_11_095632_create_module_dependencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_02_11_110057_create_pricings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_02_11_114618_add_plan_name_to_pricings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_02_11_120359_add_subscription_fields_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_02_11_144622_add_profile_fields_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_02_13_141007_create_campuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_02_13_141008_create_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_02_13_141009_create_academic_terms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_02_13_141010_create_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_02_13_141011_create_enrollment_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_02_13_141012_create_enrollment_documents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_02_14_061955_create_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_02_14_061956_create_applications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_02_14_070518_add_slug_to_modules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_02_14_111354_add_primary_color_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_02_14_131130_add_profile_photo_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_02_14_141035_create_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_02_14_145558_add_admission_test_settings_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_02_14_153544_add_admission_rules_to_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_02_14_164235_add_school_id_to_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_02_14_172437_create_program_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_02_15_140526_create_enrollment_drafts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_02_15_143756_upgrade_students_table_for_enrollment',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_02_15_144959_upgrade_students_table_full_profile',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_02_15_172422_add_sexual_orientation_to_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_02_15_172820_add_demographics_and_id_to_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_02_15_175528_remove_place_of_birth_from_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_02_16_080412_add_approval_status_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_02_16_092729_add_sidebar_color_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_02_16_141003_add_header_theme_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_02_16_163939_add_sidebar_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_02_17_074620_create_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_02_17_075608_update_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_02_17_080309_create_chat_threads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_02_17_080311_create_chat_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_02_17_080403_create_chat_thread_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_02_17_084108_update_chat_threads_structure',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_02_17_092121_remove_academic_columns_from_chat_threads',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_02_17_092440_add_governance_to_chat_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_02_17_114535_create_chats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_02_17_131202_add_flagged_to_chats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_02_17_131549_add_is_flagged_to_chat_messages',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_02_17_132331_add_flag_level_to_chat_messages',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_02_17_133638_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_02_17_150423_add_title_to_chat_threads',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_02_17_161301_add_timezone_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_02_17_163112_add_last_read_at_to_chat_thread_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_02_18_041828_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_02_18_042050_add_department_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_02_18_083404_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_02_18_083541_add_role_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_02_18_145643_add_school_id_to_all_chat_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_02_19_015951_create_quotes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_02_19_020804_add_columns_to_quotes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_02_19_021014_add_logic_columns_to_quotes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_02_19_114359_add_super_priority_to_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_02_19_114717_create_announcement_acknowledgements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_02_19_222643_remove_target_columns_from_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_02_20_023507_update_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_02_20_025100_create_academic_years_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_02_20_031540_create_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_02_20_031550_create_classes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_02_20_031750_create_class_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_02_20_031850_create_class_teacher_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_02_20_102458_create_curriculums_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_02_20_104314_create_curriculum_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_02_20_112436_create_grading_systems_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_02_20_112529_create_grading_components_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_02_20_112647_create_program_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_02_21_004830_create_class_grades_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_02_21_004929_create_class_grade_components_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_02_21_111525_add_dashboard_identity_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_02_21_113608_add_dashboard_identity_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_02_21_130143_add_status_to_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_02_21_130842_add_student_number_to_students_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_02_21_132230_create_student_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_02_21_132417_create_student_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_02_21_165803_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_02_21_165804_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_02_21_185919_add_paid_at_to_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_02_21_203227_add_code_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_02_21_203300_add_domain_to_schools_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_02_21_212013_add_school_id_to_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_02_21_225311_remove_theme_columns_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_02_22_011848_drop_name_column_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_02_22_012357_add_name_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_02_23_190456_add_active_to_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_02_23_192822_remove_status_from_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_02_23_200800_create_colleges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_02_23_200844_add_college_id_to_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_02_23_232431_add_school_id_to_colleges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_02_24_000614_add_dean_id_to_colleges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_02_24_000845_add_program_head_id_to_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_02_25_120724_create_announcement_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_02_26_012842_create_deadlines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_02_26_013220_create_deadline_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_02_26_014542_create_deadline_user_completions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_02_27_203659_add_teacher_id_to_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_02_27_220000_create_curriculum_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_02_27_220929_create_lessons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_02_27_221517_create_competencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_02_27_223000_create_program_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_02_27_230259_create_subject_prerequisites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_02_27_233000_create_teacher_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_02_27_233121_rename_is_active_to_active_on_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_02_28_000100_create_student_enrollment_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_02_28_001453_change_type_to_string_on_academic_levels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_02_28_002946_add_name_to_topics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_02_28_003816_drop_title_from_topics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_02_28_005706_add_columns_to_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_02_28_010942_drop_question_column_from_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_02_28_115717_drop_academic_level_id_from_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_02_28_115815_add_academic_level_id_to_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_03_01_130045_add_difficulty_and_academic_levels_to_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_03_01_141043_update_test_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_03_01_141143_create_test_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_03_01_142504_add_assessment_type_to_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_03_01_143420_remove_assessment_type_from_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_03_01_143849_add_availability_fields_to_test_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_03_01_164012_drop_topic_id_fk_from_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_03_01_214721_modify_term_enum_in_semesters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_03_01_220854_add_class_id_to_tests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_03_09_165936_add_duration_minutes_to_test_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_03_09_224233_fix_topics_unique_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_03_12_134039_create_test_question_type_points_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_03_12_165618_add_meta_to_choices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_03_23_112217_add_sidebar_color_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_03_23_195504_add_academic_year_id_to_semesters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_03_23_223656_change_term_column_to_varchar_in_semesters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_03_23_231946_rename_semesters_to_terms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_03_23_232101_rename_semester_id_to_term_id_in_related_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_03_24_102305_add_status_to_terms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_03_24_132644_create_user_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_03_24_132710_create_user_colleges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_03_24_132737_create_user_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_03_24_142128_add_assignment_and_validity_to_user_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_03_24_142445_update_user_roles_add_dates_and_assignment_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_03_24_142940_add_assigned_by_to_user_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_03_24_153338_create_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_03_24_153403_create_request_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_03_24_171710_create_account_access_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_03_24_181827_add_user_id_to_programs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_03_24_181850_add_user_id_to_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_03_24_202203_add_department_program_to_account_access_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_03_24_205220_create_teachers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_03_24_205401_create_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_03_24_234134_create_enrollment_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_03_24_234355_create_enrollment_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_03_24_234601_update_enrollment_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_03_25_000020_update_enrollment_settings_add_session_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_03_25_124657_add_enrollment_type_to_terms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_03_26_134354_create_system_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_03_26_145920_create_documents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_03_26_164509_create_signatories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_03_26_164553_create_document_signatories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_03_26_230839_create_offices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_03_26_231007_update_account_access_add_office_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_03_27_075836_add_office_head_id_to_offices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_03_27_195715_add_school_id_to_system_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_03_27_195812_add_unique_school_id_to_system_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_03_27_201740_drop_authority_from_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_03_28_121910_create_system_settings_history_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_03_28_150211_create_school_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_03_28_193728_create_modalities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_03_28_194300_create_school_modalities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_03_28_201951_add_address_fields_to_school_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_03_28_214342_create_banks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_03_31_122531_add_profile_fields_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_03_31_122638_create_trainees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_03_31_123025_create_training_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_03_31_123050_create_training_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_03_31_123109_create_training_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_03_31_123130_create_training_attendance_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_03_31_123203_create_training_materials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_03_31_123221_create_training_certificates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_03_31_132401_drop_role_id_from_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_03_31_134409_create_profile_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_03_31_183647_create_office_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_03_31_183715_add_office_type_id_to_offices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_03_31_185242_add_fields_to_announcements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_03_31_193459_create_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_03_31_193522_create_group_members_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_03_31_195028_add_school_id_to_offices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2026_03_31_204925_create_training_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2026_03_31_204926_create_training_assessments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2026_03_31_205634_create_training_assessment_scores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2026_03_31_223714_create_user_table_preferences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2026_03_31_230834_create_positions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2026_04_02_085055_create_office_heads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2026_04_02_085210_update_office_heads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2026_04_10_193339_add_badge_columns_to_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2026_04_10_220341_update_training_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2026_04_10_220618_add_qr_code_to_training_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2026_04_10_224317_add_title_to_terms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2026_04_10_232023_update_terms_unique_constraint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2026_04_17_173230_add_training_type_id_to_training_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2026_04_17_173449_drop_course_type_from_training_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2026_04_17_175530_create_certificate_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2026_04_17_180705_add_certificate_type_to_certificate_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2026_04_17_182957_create_training_providers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2026_04_17_183143_add_training_provider_id_to_training_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2026_04_17_215322_add_layout_html_to_certificate_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2026_04_18_171800_add_layout_json_to_certificate_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2026_04_18_173500_add_page_settings_to_certificate_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2026_04_18_183000_add_category_to_certificate_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2026_04_18_190000_create_certificate_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2026_04_18_190100_create_certificate_event_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2026_04_18_200000_create_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2026_04_18_200100_create_event_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2026_04_18_210000_create_event_activities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2026_04_20_000001_add_schedule_fields_to_certificate_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2026_04_20_000002_create_event_attendances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2026_04_20_000003_add_recipient_template_id_to_certificate_event_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2026_04_20_000004_add_time_tracking_to_event_attendances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2026_04_20_000005_add_training_details_to_training_certificates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2026_04_20_230000_add_enrollment_payment_and_expiry_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2026_04_21_010000_add_payment_type_to_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2026_04_22_120000_create_video_conference_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2026_04_22_120100_create_video_conference_rooms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2026_04_22_120200_create_video_conference_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2026_04_22_130000_add_attachments_to_chat_messages',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2026_04_22_140000_create_drive_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2026_04_22_160000_create_drive_file_edits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2026_04_22_184810_add_school_hero_to_school_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2026_04_22_185300_add_business_fields_to_school_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2026_04_22_191605_make_bank_columns_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2026_04_22_210000_add_course_fields_to_enrollment_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2026_04_23_100000_add_scope_to_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2026_04_23_110000_add_school_id_to_academic_levels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2026_04_23_120000_remap_questions_academic_level_id_to_school',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2026_04_23_170000_add_account_type_to_schools_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2026_04_23_180000_rename_account_type_to_country_on_schools_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2026_04_23_211700_add_role_id_to_account_access_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2026_04_23_212000_drop_role_id_from_account_access_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2026_04_23_213000_restore_role_id_on_account_access_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2026_04_24_100000_office_heads_refactor_and_head_role_flag',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2026_04_25_100000_extend_student_enrollments_for_acad_enrolment',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2026_04_25_100001_create_academic_policies_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2026_04_25_100002_create_class_sessions_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2026_04_25_100003_add_capacity_home_school_and_cross_enrollee_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2026_04_25_100004_drop_legacy_enrollments_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2026_04_25_120000_create_guardians_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2026_04_25_120001_create_student_academic_backgrounds_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2026_04_26_120000_add_terms_per_year_to_curriculums_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2026_04_27_000001_add_has_summer_term_to_curriculums_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2026_04_27_120000_add_units_to_subjects_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2026_04_27_000001_add_is_active_to_subjects_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2026_04_27_213733_update_student_enrollment_subjects_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2026_04_27_221459_add_selected_subject_count_to_student_enrollments',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2026_04_27_220000_create_scheduler_supporting_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2026_04_27_220100_create_scheduler_schedules_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2026_04_27_223000_add_room_id_to_program_subjects_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2026_04_27_224000_create_scheduler_optional_tables',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2026_04_28_110000_add_status_to_academic_years_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2026_04_28_114500_drop_legacy_academic_terms_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2026_04_28_115000_rename_enrollment_drafts_term_id',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2026_04_28_120000_add_status_to_sections_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2026_04_28_121000_create_subject_credit_evaluations_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2026_04_28_120000_add_days_per_week_to_scheduler_settings',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2026_04_28_140000_add_teacher_constraints_to_scheduler_settings',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2026_04_28_184500_add_part_time_min_hours_to_scheduler_settings',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2026_04_28_191500_add_work_hours_per_day_to_scheduler_settings',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2026_04_28_193000_replace_work_hours_with_min_hours_in_scheduler_settings',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2026_04_28_214317_create_academic_schedulers_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2026_04_29_000000_drop_active_from_subjects_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2026_04_29_000100_add_category_to_subjects_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2026_04_29_000200_refactor_subject_codes',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2026_04_29_165651_create_subject_offerings_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2026_04_29_170000_create_lesson_resources_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2026_04_29_180000_make_program_id_nullable_on_lesson_resources',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2026_04_29_210000_make_file_columns_nullable_on_lesson_resources',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2026_04_29_220000_drop_subcompetency_title_from_lesson_resources',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2026_04_29_220557_add_sort_order_to_topics_and_lessons_tables',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2026_04_30_120000_add_residential_address_fields_to_students',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2026_04_30_120000_add_scheduling_columns_to_classes_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2026_04_30_140000_add_unique_index_to_students_user_id',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2026_04_30_130000_create_class_student_pivot_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2026_05_01_120000_create_education_nodes_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2026_05_01_220000_add_education_node_id_to_programs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2026_05_02_151411_add_program_offering_fields_to_subject_offerings_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2026_05_02_171748_add_pathway_fields_to_student_enrollments',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2026_05_12_130000_add_education_node_id_to_student_academic_backgrounds',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2026_05_12_192500_make_program_id_nullable_on_student_enrollments',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2026_05_12_200000_extend_system_settings_smtp_sms',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2026_05_12_220000_create_student_health_records_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2026_05_13_184135_create_admission_exam_settings_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2026_05_13_200000_extend_student_enrollments_status_for_exam',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2026_05_14_090000_add_provisional_billing_statuses_to_student_enrollments',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2026_05_14_090500_add_billing_cleared_as_to_student_enrollments',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2026_05_14_100000_add_payment_due_at_to_student_enrollments',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2026_05_14_025143_create_transcript_edit_requests_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2026_05_14_040000_expand_status_enum_student_enrollment_subjects',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2026_05_14_000003_create_grade_level_subjects_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2026_05_14_135004_add_is_basic_ed_to_subjects_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2026_05_14_151304_add_education_level_to_ay_and_terms',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2026_06_28_000001_create_finance_fee_setups_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2026_06_28_000002_create_finance_discount_types_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2026_06_28_000010_add_billing_links_to_invoices_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2026_06_28_000011_add_invoice_id_to_payments_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2026_06_28_000012_create_invoice_items_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2026_06_28_000013_create_ledger_entries_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2026_06_28_000014_create_statement_of_accounts_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2026_06_28_000015_create_finance_settings_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2026_06_29_100000_add_lrn_to_students_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2026_06_29_110000_create_student_id_settings_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2026_06_29_120000_make_sections_program_year_nullable',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2026_06_29_130000_add_place_of_birth_blood_type_to_students',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2026_06_29_130000_create_payment_plans_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2026_06_29_130100_create_scholarships_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2026_06_29_130200_create_penalty_rules_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2026_06_29_130300_add_payment_plan_id_to_finance_fee_setups_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2026_06_29_140000_add_down_payment_to_payment_plans_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2026_06_29_141000_add_down_payment_type_to_payment_plans_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2026_06_29_142000_add_plan_options_to_payment_plans_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2026_06_29_150000_add_payment_plan_id_to_student_enrollments_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2026_06_30_090000_add_payment_selection_to_student_enrollments_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2026_06_30_100000_add_education_node_id_to_ay_and_terms',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2026_06_30_110000_widen_terms_unique_name_to_include_level',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2026_06_30_120000_add_billing_schedule_to_payment_plans_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2026_06_30_130000_add_consent_to_student_enrollments_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2026_06_30_140000_add_letterhead_images_to_school_profiles_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2026_06_30_150000_add_letterhead_spacing_to_school_profiles_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2026_07_01_090000_add_scholarship_bands_to_admission_exam_settings',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2026_07_01_100000_add_grants_scholarship_to_admission_exam_settings',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2026_07_01_110000_add_scholarship_to_student_enrollments_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2026_07_03_100000_add_due_days_to_payment_plans_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2026_07_03_100100_add_billing_date_to_invoices_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2026_07_04_100000_create_platform_settings_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2026_07_04_120000_create_payment_submissions_table',79);
