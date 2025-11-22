-- FILE: /database.sql
-- SplashRecruit - Multi-tenant ATS Database Schema
-- MySQL 5.7+ / MariaDB 10.2+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Database: splashrecruit
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `splashrecruit` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `splashrecruit`;

-- --------------------------------------------------------
-- Table: tenants
-- --------------------------------------------------------

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `primary_contact_name` varchar(255) DEFAULT NULL,
  `primary_contact_email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `default_timezone` varchar(50) DEFAULT 'UTC',
  `default_locale` varchar(10) DEFAULT 'en_US',
  `career_site_subdomain` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `career_site_subdomain` (`career_site_subdomain`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: plans
-- --------------------------------------------------------

CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','quarterly','yearly') DEFAULT 'monthly',
  `max_jobs` int(11) DEFAULT NULL,
  `max_active_jobs` int(11) DEFAULT NULL,
  `max_users` int(11) DEFAULT NULL,
  `max_candidates_per_month` int(11) DEFAULT NULL,
  `max_storage_size` bigint(20) DEFAULT NULL COMMENT 'in bytes',
  `max_emails_per_month` int(11) DEFAULT NULL,
  `features` text DEFAULT NULL COMMENT 'JSON array of feature flags',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tenant_subscriptions
-- --------------------------------------------------------

CREATE TABLE `tenant_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('trialing','active','past_due','canceled','expired') DEFAULT 'trialing',
  `trial_ends_at` datetime DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_subscription_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscription_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('platform_admin','tenant_admin','recruiter','hiring_manager','interviewer','hr_assistant','viewer') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `tenant_id` (`tenant_id`),
  KEY `role` (`role`),
  KEY `status` (`status`),
  CONSTRAINT `fk_user_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pipelines
-- --------------------------------------------------------

CREATE TABLE `pipelines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `is_default` (`is_default`),
  CONSTRAINT `fk_pipeline_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pipeline_stages
-- --------------------------------------------------------

CREATE TABLE `pipeline_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_final_stage` tinyint(1) DEFAULT 0,
  `is_rejection_stage` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pipeline_id` (`pipeline_id`),
  KEY `position` (`position`),
  CONSTRAINT `fk_stage_pipeline` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: jobs
-- --------------------------------------------------------

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location_type` enum('on_site','hybrid','remote') DEFAULT 'on_site',
  `location_city` varchar(100) DEFAULT NULL,
  `location_country` varchar(100) DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','internship','temporary') DEFAULT 'full_time',
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `salary_currency` varchar(10) DEFAULT 'USD',
  `salary_visible` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `status` enum('draft','published','closed','archived') DEFAULT 'draft',
  `hiring_manager_user_id` int(11) DEFAULT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `maximum_applications` int(11) DEFAULT NULL,
  `external_job_board_visibility` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `slug` (`slug`),
  KEY `status` (`status`),
  KEY `hiring_manager_user_id` (`hiring_manager_user_id`),
  KEY `pipeline_id` (`pipeline_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_job_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_hiring_manager` FOREIGN KEY (`hiring_manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_job_pipeline` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: candidates
-- --------------------------------------------------------

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `current_company` varchar(255) DEFAULT NULL,
  `current_title` varchar(255) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `linkedin_url` varchar(500) DEFAULT NULL,
  `github_url` varchar(500) DEFAULT NULL,
  `portfolio_url` varchar(500) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL COMMENT 'career_page, referral, job_board, manual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `email` (`email`),
  KEY `full_name` (`full_name`),
  KEY `source` (`source`),
  CONSTRAINT `fk_candidate_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------

CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `current_stage_id` int(11) DEFAULT NULL,
  `status` enum('applied','in_review','interview_scheduled','offered','hired','rejected','withdrawn') DEFAULT 'applied',
  `source` varchar(100) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_status_change_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rejection_reason` text DEFAULT NULL,
  `offer_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `job_id` (`job_id`),
  KEY `current_stage_id` (`current_stage_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_application_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_stage` FOREIGN KEY (`current_stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: application_stage_history
-- --------------------------------------------------------

CREATE TABLE `application_stage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `from_stage_id` int(11) DEFAULT NULL,
  `to_stage_id` int(11) NOT NULL,
  `changed_by_user_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `from_stage_id` (`from_stage_id`),
  KEY `to_stage_id` (`to_stage_id`),
  KEY `changed_by_user_id` (`changed_by_user_id`),
  CONSTRAINT `fk_history_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_from_stage` FOREIGN KEY (`from_stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_history_to_stage` FOREIGN KEY (`to_stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: interviews
-- --------------------------------------------------------

CREATE TABLE `interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `interview_type` enum('phone','video','onsite','group','technical','hr') DEFAULT 'phone',
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime NOT NULL,
  `location` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','completed','canceled','no_show','rescheduled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `application_id` (`application_id`),
  KEY `status` (`status`),
  KEY `scheduled_start` (`scheduled_start`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_interview_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interview_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interview_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: interview_interviewers
-- --------------------------------------------------------

CREATE TABLE `interview_interviewers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interview_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `interview_id` (`interview_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_ii_interview` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ii_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: evaluations
-- --------------------------------------------------------

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `interview_id` int(11) DEFAULT NULL,
  `application_id` int(11) NOT NULL,
  `evaluator_user_id` int(11) NOT NULL,
  `overall_rating` int(11) DEFAULT NULL COMMENT '1-5 rating',
  `recommendation` enum('strong_hire','hire','no_hire','hold') DEFAULT NULL,
  `scores` text DEFAULT NULL COMMENT 'JSON for custom criteria scores',
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `interview_id` (`interview_id`),
  KEY `application_id` (`application_id`),
  KEY `evaluator_user_id` (`evaluator_user_id`),
  CONSTRAINT `fk_evaluation_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluation_interview` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluation_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluation_evaluator` FOREIGN KEY (`evaluator_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: candidate_files
-- --------------------------------------------------------

CREATE TABLE `candidate_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size_bytes` bigint(20) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `application_id` (`application_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_file_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tags
-- --------------------------------------------------------

CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#3B82F6',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `fk_tag_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: candidate_tags
-- --------------------------------------------------------

CREATE TABLE `candidate_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `candidate_tag` (`candidate_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `fk_ct_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ct_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: email_templates
-- --------------------------------------------------------

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `type` enum('application_received','interview_invitation','rejection','offer','generic','status_update') DEFAULT 'generic',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `code` (`code`),
  CONSTRAINT `fk_template_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `type` enum('system','candidate_email','user_alert') DEFAULT 'system',
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  CONSTRAINT `fk_notification_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  KEY `entity_type` (`entity_type`,`entity_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_log_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: invoices
-- --------------------------------------------------------

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('pending','paid','overdue','canceled') DEFAULT 'pending',
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `tenant_id` (`tenant_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_invoice_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `tenant_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_method` varchar(50) DEFAULT 'credit_card',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_payment_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tenant_api_keys
-- --------------------------------------------------------

CREATE TABLE `tenant_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `tenant_id` (`tenant_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `fk_apikey_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SEED DATA
-- --------------------------------------------------------

-- Insert Plans
INSERT INTO `plans` (`name`, `code`, `description`, `price`, `billing_cycle`, `max_jobs`, `max_active_jobs`, `max_users`, `max_candidates_per_month`, `max_storage_size`, `max_emails_per_month`, `features`) VALUES
('Starter', 'starter', 'Perfect for small teams getting started', 49.00, 'monthly', 5, 3, 3, 50, 1073741824, 100, '{"advanced_analytics":false,"custom_pipeline":false,"career_page_branding":false}'),
('Professional', 'professional', 'For growing teams with advanced needs', 149.00, 'monthly', 20, 10, 10, 200, 5368709120, 500, '{"advanced_analytics":true,"custom_pipeline":true,"career_page_branding":false}'),
('Enterprise', 'enterprise', 'Unlimited recruiting power', 399.00, 'monthly', NULL, NULL, NULL, NULL, NULL, NULL, '{"advanced_analytics":true,"custom_pipeline":true,"career_page_branding":true}');

-- Insert Platform Admin User (password: admin123)
INSERT INTO `users` (`tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(NULL, 'Platform Admin', 'admin@splashrecruit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'platform_admin', 'active');

-- Insert Demo Tenant
INSERT INTO `tenants` (`name`, `code`, `slug`, `primary_contact_name`, `primary_contact_email`, `phone`, `career_site_subdomain`, `status`) VALUES
('TechCorp Solutions', 'TECHCORP', 'techcorp-solutions', 'Jane Smith', 'jane.smith@techcorp.com', '+1-555-0100', 'techcorp', 'active'),
('InnovateLabs Inc', 'INNOVATE', 'innovatelabs-inc', 'John Doe', 'john@innovatelabs.com', '+1-555-0200', 'innovatelabs', 'active');

-- Insert Tenant Subscriptions
INSERT INTO `tenant_subscriptions` (`tenant_id`, `plan_id`, `status`, `start_date`, `renewal_date`) VALUES
(1, 2, 'active', '2025-01-01', '2025-02-01'),
(2, 1, 'active', '2025-01-15', '2025-02-15');

-- Insert Demo Users for TechCorp (tenant_id = 1)
-- Password for all demo users: password123
INSERT INTO `users` (`tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'Sarah Johnson', 'sarah@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'active'),
(1, 'Mike Chen', 'mike@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 'active'),
(1, 'Emma Davis', 'emma@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hiring_manager', 'active'),
(1, 'Alex Turner', 'alex@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'interviewer', 'active');

-- Insert Demo Users for InnovateLabs (tenant_id = 2)
INSERT INTO `users` (`tenant_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(2, 'Robert Wilson', 'robert@innovatelabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 'active'),
(2, 'Lisa Martinez', 'lisa@innovatelabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 'active');

-- Insert Default Pipelines
INSERT INTO `pipelines` (`tenant_id`, `name`, `description`, `is_default`) VALUES
(1, 'Standard Hiring Pipeline', 'Default recruitment pipeline for TechCorp', 1),
(2, 'Standard Hiring Pipeline', 'Default recruitment pipeline for InnovateLabs', 1);

-- Insert Pipeline Stages for TechCorp
INSERT INTO `pipeline_stages` (`pipeline_id`, `name`, `position`, `is_final_stage`, `is_rejection_stage`) VALUES
(1, 'Applied', 1, 0, 0),
(1, 'Screening', 2, 0, 0),
(1, 'Phone Interview', 3, 0, 0),
(1, 'Technical Interview', 4, 0, 0),
(1, 'HR Interview', 5, 0, 0),
(1, 'Offer', 6, 0, 0),
(1, 'Hired', 7, 1, 0),
(1, 'Rejected', 8, 1, 1);

-- Insert Pipeline Stages for InnovateLabs
INSERT INTO `pipeline_stages` (`pipeline_id`, `name`, `position`, `is_final_stage`, `is_rejection_stage`) VALUES
(2, 'Applied', 1, 0, 0),
(2, 'Initial Review', 2, 0, 0),
(2, 'Interview', 3, 0, 0),
(2, 'Final Round', 4, 0, 0),
(2, 'Offer', 5, 0, 0),
(2, 'Hired', 6, 1, 0),
(2, 'Rejected', 7, 1, 1);

-- Insert Demo Jobs for TechCorp
INSERT INTO `jobs` (`tenant_id`, `title`, `slug`, `reference_code`, `department`, `location_type`, `location_city`, `location_country`, `employment_type`, `salary_range_min`, `salary_range_max`, `salary_currency`, `salary_visible`, `description`, `requirements`, `status`, `hiring_manager_user_id`, `pipeline_id`, `created_by`, `published_at`) VALUES
(1, 'Senior Software Engineer', 'senior-software-engineer', 'TC-SSE-001', 'Engineering', 'hybrid', 'San Francisco', 'USA', 'full_time', 120000.00, 180000.00, 'USD', 1, '<p>We are seeking an experienced Senior Software Engineer to join our engineering team.</p>', '<ul><li>5+ years of software development experience</li><li>Strong knowledge of Python and JavaScript</li><li>Experience with cloud platforms (AWS, GCP, Azure)</li></ul>', 'published', 4, 1, 3, '2025-01-15 10:00:00'),
(1, 'Product Manager', 'product-manager', 'TC-PM-001', 'Product', 'remote', NULL, 'USA', 'full_time', 100000.00, 150000.00, 'USD', 1, '<p>Join our product team to lead innovative product development.</p>', '<ul><li>3+ years of product management experience</li><li>Strong analytical and communication skills</li><li>Experience with Agile methodologies</li></ul>', 'published', 4, 1, 3, '2025-01-18 14:00:00'),
(1, 'UX Designer', 'ux-designer', 'TC-UXD-001', 'Design', 'hybrid', 'San Francisco', 'USA', 'full_time', 80000.00, 120000.00, 'USD', 0, '<p>Create exceptional user experiences for our products.</p>', '<ul><li>3+ years of UX design experience</li><li>Proficiency in Figma, Sketch, or Adobe XD</li><li>Strong portfolio demonstrating UX work</li></ul>', 'published', 4, 1, 3, '2025-01-20 09:00:00');

-- Insert Demo Jobs for InnovateLabs
INSERT INTO `jobs` (`tenant_id`, `title`, `slug`, `reference_code`, `department`, `location_type`, `location_city`, `location_country`, `employment_type`, `salary_range_min`, `salary_range_max`, `salary_currency`, `salary_visible`, `description`, `requirements`, `status`, `hiring_manager_user_id`, `pipeline_id`, `created_by`, `published_at`) VALUES
(2, 'Data Scientist', 'data-scientist', 'IL-DS-001', 'Data Science', 'remote', NULL, 'USA', 'full_time', 110000.00, 160000.00, 'USD', 1, '<p>Apply machine learning and data analysis to solve complex problems.</p>', '<ul><li>MS or PhD in Computer Science, Statistics, or related field</li><li>Experience with Python, R, and SQL</li><li>Knowledge of ML frameworks (TensorFlow, PyTorch)</li></ul>', 'published', NULL, 2, 7, '2025-01-10 11:00:00');

-- Insert Demo Candidates
INSERT INTO `candidates` (`tenant_id`, `first_name`, `last_name`, `email`, `phone`, `country`, `city`, `current_company`, `current_title`, `years_of_experience`, `linkedin_url`, `source`) VALUES
(1, 'David', 'Anderson', 'david.anderson@email.com', '+1-555-1001', 'USA', 'New York', 'BigTech Inc', 'Software Engineer', 6, 'https://linkedin.com/in/david-anderson', 'career_page'),
(1, 'Maria', 'Garcia', 'maria.garcia@email.com', '+1-555-1002', 'USA', 'Austin', 'StartupX', 'Senior Developer', 8, 'https://linkedin.com/in/maria-garcia', 'referral'),
(1, 'James', 'Brown', 'james.brown@email.com', '+1-555-1003', 'USA', 'Seattle', 'CloudCorp', 'Product Lead', 5, 'https://linkedin.com/in/james-brown', 'job_board'),
(1, 'Jennifer', 'Lee', 'jennifer.lee@email.com', '+1-555-1004', 'USA', 'San Francisco', 'DesignStudio', 'UX Designer', 4, 'https://linkedin.com/in/jennifer-lee', 'career_page'),
(2, 'Michael', 'Taylor', 'michael.taylor@email.com', '+1-555-2001', 'USA', 'Boston', 'Analytics Pro', 'Data Analyst', 7, 'https://linkedin.com/in/michael-taylor', 'career_page');

-- Insert Demo Applications
INSERT INTO `applications` (`tenant_id`, `candidate_id`, `job_id`, `current_stage_id`, `status`, `source`, `applied_at`) VALUES
(1, 1, 1, 1, 'in_review', 'career_page', '2025-01-16 10:30:00'),
(1, 2, 1, 3, 'interview_scheduled', 'referral', '2025-01-17 14:20:00'),
(1, 3, 2, 2, 'in_review', 'job_board', '2025-01-19 09:45:00'),
(1, 4, 3, 1, 'applied', 'career_page', '2025-01-21 11:15:00'),
(2, 5, 4, 9, 'in_review', 'career_page', '2025-01-11 13:00:00');

-- Insert Demo Tags
INSERT INTO `tags` (`tenant_id`, `name`, `color`) VALUES
(1, 'Senior Level', '#10B981'),
(1, 'Top Candidate', '#F59E0B'),
(1, 'Immediate Start', '#EF4444'),
(1, 'Remote Preferred', '#3B82F6'),
(2, 'PhD Candidate', '#8B5CF6');

-- Insert Demo Email Templates
INSERT INTO `email_templates` (`tenant_id`, `name`, `code`, `subject`, `body`, `type`, `is_default`) VALUES
(NULL, 'Application Received', 'application_received', 'Thank you for your application to {{job_title}}', '<p>Dear {{candidate_name}},</p><p>Thank you for applying to the {{job_title}} position at {{company_name}}. We have received your application and our team will review it shortly.</p><p>We will contact you if your qualifications match our requirements.</p><p>Best regards,<br>{{company_name}} Recruitment Team</p>', 'application_received', 1),
(NULL, 'Interview Invitation', 'interview_invitation', 'Interview Invitation for {{job_title}}', '<p>Dear {{candidate_name}},</p><p>We are pleased to invite you for an interview for the {{job_title}} position.</p><p><strong>Interview Details:</strong><br>Date: {{interview_date}}<br>Time: {{interview_time}}<br>Location: {{interview_location}}</p><p>Please confirm your attendance by replying to this email.</p><p>Best regards,<br>{{company_name}}</p>', 'interview_invitation', 1),
(NULL, 'Application Rejected', 'rejection', 'Update on your application to {{job_title}}', '<p>Dear {{candidate_name}},</p><p>Thank you for your interest in the {{job_title}} position at {{company_name}}. After careful consideration, we have decided to move forward with other candidates whose qualifications better match our current needs.</p><p>We appreciate the time you invested in the application process and wish you the best in your job search.</p><p>Best regards,<br>{{company_name}}</p>', 'rejection', 1),
(NULL, 'Job Offer', 'offer', 'Job Offer - {{job_title}} at {{company_name}}', '<p>Dear {{candidate_name}},</p><p>We are delighted to offer you the position of {{job_title}} at {{company_name}}!</p><p>{{offer_details}}</p><p>Please review the attached offer letter and let us know your decision by {{offer_deadline}}.</p><p>We look forward to welcoming you to our team!</p><p>Best regards,<br>{{company_name}}</p>', 'offer', 1);

-- Insert API Keys for demo tenants
INSERT INTO `tenant_api_keys` (`tenant_id`, `api_key`, `name`, `is_active`) VALUES
(1, 'tc_live_abc123def456ghi789jkl012mno345pqr678stu901', 'TechCorp Production API Key', 1),
(2, 'il_live_xyz987wvu654tsr321qpo098nml765kji432hgf210', 'InnovateLabs Production API Key', 1);

-- End of database.sql
