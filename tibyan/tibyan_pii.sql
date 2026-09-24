-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: May 28, 2026 at 07:07 PM
-- Server version: 5.7.31
-- PHP Version: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tibyan_pii`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(14, 4, 'login_success', 'User logged in successfully', '::1', '2026-04-27 19:40:12'),
(15, 4, 'login_success', 'User logged in successfully', '::1', '2026-04-27 19:49:46'),
(18, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-28 19:01:27'),
(19, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-28 19:11:59'),
(20, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-28 19:16:26'),
(21, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-28 19:18:13'),
(22, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-28 19:31:04'),
(23, 5, 'unauthorized_access_attempt', 'User ID 5 (patient) attempted admin privileges.', '::1', '2026-04-28 16:43:20'),
(24, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-29 14:59:33'),
(25, 5, 'unauthorized_access_attempt', 'User ID 5 (patient) attempted admin privileges.', '::1', '2026-04-29 12:09:46'),
(26, 3, 'login_success', 'User logged in successfully', '::1', '2026-04-29 15:13:52'),
(27, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-29 15:23:09'),
(28, 5, 'unauthorized_access_attempt', 'User ID 5 (patient) attempted admin privileges.', '::1', '2026-04-29 12:24:08'),
(29, 3, 'login_success', 'User logged in successfully', '::1', '2026-04-29 15:24:58'),
(30, 3, 'unauthorized_data_submission', '{\"target_user_id\":1}', '::1', '2026-04-29 17:41:27'),
(31, 3, 'unauthorized_data_submission', '{\"target_user_id\":2}', '::1', '2026-04-29 17:41:40'),
(32, 3, 'unauthorized_data_submission', '{\"target_user_id\":1}', '::1', '2026-04-29 17:41:50'),
(33, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-29 17:43:46'),
(34, 5, 'added_medical_metrics', '{\"fields_recorded\":5}', '::1', '2026-04-29 17:44:00'),
(35, 5, 'login_success', 'User logged in successfully', '::1', '2026-04-29 18:13:02'),
(36, 5, 'added_medical_metrics', '{\"fields_recorded\":5}', '::1', '2026-04-29 18:16:12'),
(37, 8, 'login_success', 'User logged in successfully', '::1', '2026-04-30 11:10:57'),
(38, 8, 'unauthorized_access_attempt', 'User ID 8 (patient) attempted admin privileges.', '::1', '2026-04-30 08:17:24'),
(39, 8, 'unauthorized_access_attempt', 'User ID 8 (patient) attempted admin privileges.', '::1', '2026-04-30 08:18:07'),
(40, 3, 'login_success', 'User logged in successfully', '::1', '2026-04-30 11:47:11'),
(41, 5, 'unauthorized_data_submission', '{\"target_user_id\":8}', '::1', '2026-04-30 11:53:02'),
(42, 8, 'added_medical_metrics', '{\"fields_recorded\":5}', '::1', '2026-04-30 11:53:16'),
(43, 5, 'signed_consent', '{\"policy_version\":\"v1.2-2026\",\"research_opt_in\":1}', '::1', '2026-04-30 12:49:28'),
(44, 5, 'signed_consent', '{\"policy_version\":\"v1.2-2026\",\"research_opt_in\":2}', '::1', '2026-04-30 12:50:00'),
(45, 8, 'signed_consent', '{\"policy_version\":\"v1.2-2026\",\"research_opt_in\":2}', '::1', '2026-04-30 12:50:58'),
(46, 5, 'unauthorized_access_attempt', 'User ID 5 (patient) attempted admin privileges.', '::1', '2026-05-28 15:44:15'),
(47, 3, 'login_success', 'User logged in successfully', '::1', '2026-05-28 18:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consent_policy`
--

DROP TABLE IF EXISTS `consent_policy`;
CREATE TABLE IF NOT EXISTS `consent_policy` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `policy_agreed_version` int(11) NOT NULL,
  `agreed_at` date NOT NULL,
  `research_opt_in` tinyint(1) NOT NULL DEFAULT '0',
  `withdrawn_requested_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `consent_policy_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consent_policy`
--

INSERT INTO `consent_policy` (`id`, `user_id`, `policy_agreed_version`, `agreed_at`, `research_opt_in`, `withdrawn_requested_at`, `created_at`, `updated_at`) VALUES
(3, 5, 0, '2026-04-30', 2, NULL, '2026-04-30 09:50:00', '2026-04-30 09:50:00'),
(4, 8, 0, '2026-04-30', 2, NULL, '2026-04-30 09:50:58', '2026-04-30 09:50:58');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
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

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_02_15_203126_create_pii_tables', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('tibyan111@gmail.com', 'bc6acd6713983f340d76a031843c24223ed6d4fe8e4445c8f1f0bf11fd35b7f8', '2026-04-30 08:17:57'),
('tibyan12@gmail.com', 'f9cfa601cb55cfbee078367efeaf24f872eda8926d2d6b9efcc7d539096d41c1', '2026-04-29 12:00:21');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0163a4d9c6c48b33fd6ca5bf7334eb93', 3, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1779993892),
('23d9ad945ed2a6909b16e243e9002326', 3, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777475698),
('29d0e7d2a8a6649e2e7024c2216fdf33', 3, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777476320),
('373ee1e9ed8efd57ad9e80663d01f72d', 5, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777486494),
('37524b5432b7335b646f0cb1d1754b32', 3, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777549638),
('3be98e0e07a99b7b50f12d79bddce945', 5, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777405901),
('4b3a6b37caf7a099fdd7f28785e4b746', 5, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777404831),
('68f44bcc37ec64f3114b9bd83d6ed9a5', 5, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777476196),
('8b48421f3e21eada4d6f3c2367fe69b3', 8, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777547801),
('965fb78732eb93a3b9e97d9e042a5999', 5, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777474781),
('d34d61658e11a5cc081e22542a0983fe', 8, '::1', 'PostmanRuntime/7.53.0', 'web_login', 1777547785);

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

DROP TABLE IF EXISTS `subscription`;
CREATE TABLE IF NOT EXISTS `subscription` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `renewal_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription`
--

INSERT INTO `subscription` (`id`, `user_id`, `status`, `renewal_date`, `created_at`, `updated_at`) VALUES
(1, 5, 'active', '2027-04-28', '2026-04-29 10:40:35', '2026-04-29 10:40:35'),
(2, 8, 'active', '2027-04-28', '2026-04-30 08:17:41', '2026-04-30 08:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `upid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hashed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'patient',
  `date_of_birth` date NOT NULL,
  `contact_info` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_upid_unique` (`upid`),
  UNIQUE KEY `user_email_unique` (`email`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `upid`, `email`, `password_hashed`, `full_name`, `role`, `date_of_birth`, `contact_info`, `mfa_enabled`, `last_login_at`, `created_at`, `updated_at`) VALUES
(2, 'P101', 'test@me.com', '$2y$10$3JDtSAQfShhCqGUi1SynMu0kFZYooCssXpbOEh4NQmDO3usfftxCa', 'Test User', 'patient', '1990-01-01', '0000', 0, NULL, '2026-03-31 16:41:18', '2026-04-23 16:34:03'),
(3, 'A001', 'admin@tibyan.com', '$2y$10$KdKKZW/xLuHx25eNn1UOGegm8FRDjr8oMD59VEdRjbkdp.frMsj7y', 'Senior Admin', 'admin', '2000-01-01', '71000001', 0, '2026-05-28 15:44:52', '2026-04-27 16:22:08', NULL),
(4, 'A002', 'salma@gmail.com', '$2y$10$4Sgc/Na1GWg.5WhIKQq7uemQUavuDSzKsMzWwQUe7tYYtq6YTOBYe', 'salman sweidan', 'patient', '2000-01-01', '71000201', 0, NULL, '2026-04-27 16:24:08', NULL),
(5, 'A003', 'tibyan12@gmail.com', '$2y$10$iASDXx8ksS0LmafXxAttnusCFB8Pto7XSW9jsimkgi2rqJMOvJcz6', 'mariam meslmani', 'patient', '2000-01-01', '71022001', 0, '2026-04-29 15:14:54', '2026-04-28 15:53:33', NULL),
(8, 'A005', 'tibyan111@gmail.com', '$2y$10$PIsJv8VrX50Awc8MBDrXpe1tJLodU4EwfMl384ukBGXxH3AAwfm1y', 'ranim berjawi', 'patient', '2000-01-01', '71021101', 0, '2026-04-30 08:16:41', '2026-04-30 08:10:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john@example.com', '2026-02-25 08:00:00', '$2y$10$nJj9HQOZcAAjHZkI/bDE9evcWRWsSC18yh.EZ1uqxt36nj7INe2US', 'random_string_token_123', '2026-02-24 22:14:13', '2026-02-24 22:14:13');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `consent_policy`
--
ALTER TABLE `consent_policy`
  ADD CONSTRAINT `consent_policy_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_consent_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user_email` FOREIGN KEY (`email`) REFERENCES `user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `fk_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
