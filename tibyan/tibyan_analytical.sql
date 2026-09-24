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
-- Database: `tibyan_analytical`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_page`
--

DROP TABLE IF EXISTS `about_page`;
CREATE TABLE IF NOT EXISTS `about_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `text3` text,
  `text4` text,
  `text5` text,
  `text6` text,
  `text7` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

DROP TABLE IF EXISTS `about_us`;
CREATE TABLE IF NOT EXISTS `about_us` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image1` varchar(255) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `text3` text,
  `text4` text,
  `text5` text,
  `text6` text,
  `text7` text,
  `text8` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `analytical_tables`
--

DROP TABLE IF EXISTS `analytical_tables`;
CREATE TABLE IF NOT EXISTS `analytical_tables` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `lbxglu` decimal(10,2) NOT NULL,
  `lbxin` decimal(10,2) NOT NULL,
  `bmxwaist` decimal(10,2) NOT NULL,
  `bmxbmi` decimal(10,2) NOT NULL,
  `lbxcp` decimal(10,2) DEFAULT NULL,
  `lbdhdd` decimal(10,2) DEFAULT NULL,
  `lbdldl` decimal(10,2) DEFAULT NULL,
  `lbxtr` decimal(10,2) DEFAULT NULL,
  `lbxsal` decimal(10,2) DEFAULT NULL,
  `lbxsc3si` decimal(10,2) DEFAULT NULL,
  `lbxsatsi` decimal(10,2) DEFAULT NULL,
  `urxuma` decimal(10,2) DEFAULT NULL,
  `lbdsbusi` decimal(10,2) DEFAULT NULL,
  `vnavebpxsy` decimal(10,2) DEFAULT NULL,
  `vnlbavebpxdi` decimal(10,2) DEFAULT NULL,
  `bmpwhr` decimal(10,2) DEFAULT NULL,
  `bmxsad1` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `analytical_tables`
--

INSERT INTO `analytical_tables` (`id`, `user_id`, `lbxglu`, `lbxin`, `bmxwaist`, `bmxbmi`, `lbxcp`, `lbdhdd`, `lbdldl`, `lbxtr`, `lbxsal`, `lbxsc3si`, `lbxsatsi`, `urxuma`, `lbdsbusi`, `vnavebpxsy`, `vnlbavebpxdi`, `bmpwhr`, `bmxsad1`, `created_at`, `updated_at`) VALUES
(2, 5, 110.50, 10.20, 92.00, 27.50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-29 17:44:00', '2026-04-29 17:44:00'),
(3, 5, 110.50, 10.20, 92.00, 27.50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-29 18:16:12', '2026-04-29 18:16:12'),
(4, 8, 120.50, 10.80, 94.00, 26.50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-30 11:53:16', '2026-04-30 11:53:16');

-- --------------------------------------------------------

--
-- Table structure for table `carousel`
--

DROP TABLE IF EXISTS `carousel`;
CREATE TABLE IF NOT EXISTS `carousel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `contact_page`
--

DROP TABLE IF EXISTS `contact_page`;
CREATE TABLE IF NOT EXISTS `contact_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(100) NOT NULL,
  `text` varchar(255) NOT NULL,
  `text1` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

DROP TABLE IF EXISTS `features`;
CREATE TABLE IF NOT EXISTS `features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  `text1` varchar(255) NOT NULL,
  `text2` text NOT NULL,
  `icon` varchar(100) NOT NULL,
  `text3` varchar(255) NOT NULL,
  `text4` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `feature_importance`
--

DROP TABLE IF EXISTS `feature_importance`;
CREATE TABLE IF NOT EXISTS `feature_importance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_version_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `importance_score` decimal(10,6) NOT NULL,
  `calculation_date` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_model_id` (`model_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feature_importance`
--

INSERT INTO `feature_importance` (`id`, `model_version_id`, `feature_name`, `importance_score`, `calculation_date`, `created_at`) VALUES
(1, '1', 'systolic_bp', 0.452300, '2026-02-27', '2026-02-27 03:29:21'),
(2, 'Tibyan-RF-v1', 'lbxglu', 0.650000, '2026-04-30', '2026-04-30 15:22:30');

-- --------------------------------------------------------

--
-- Table structure for table `health_logs`
--

DROP TABLE IF EXISTS `health_logs`;
CREATE TABLE IF NOT EXISTS `health_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `upid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cholesterol_hdl` int(11) NOT NULL,
  `systolic_bp` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_health_upid` (`upid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_logs`
--

INSERT INTO `health_logs` (`id`, `upid`, `cholesterol_hdl`, `systolic_bp`, `created_at`, `updated_at`) VALUES
(1, 'A005', 48, 128, '2026-04-30 08:54:13', '2026-04-30 08:54:13');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_performance_logs`
--

DROP TABLE IF EXISTS `model_performance_logs`;
CREATE TABLE IF NOT EXISTS `model_performance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_version_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `performance_date` date NOT NULL,
  `accuracy` decimal(5,4) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_performance_version` (`model_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_performance_logs`
--

INSERT INTO `model_performance_logs` (`id`, `model_version_id`, `performance_date`, `accuracy`, `created_at`, `updated_at`) VALUES
(2, '1', '2026-02-27', 0.9650, '2026-02-27 00:55:14', '2026-02-27 00:55:14'),
(3, 'Tibyan-RF-v1', '2026-04-30', 0.9350, '2026-04-30 12:25:29', '2026-04-30 12:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `model_versions`
--

DROP TABLE IF EXISTS `model_versions`;
CREATE TABLE IF NOT EXISTS `model_versions` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `algorithm_used` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `training_data_source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `training_end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_versions`
--

INSERT INTO `model_versions` (`id`, `model_type`, `algorithm_used`, `training_data_source`, `training_end_date`, `created_at`, `updated_at`) VALUES
('1', 'Diabetes Risk Prediction', 'XGBoost', 'clinical_dataset_2025_Q4', '2026-01-15', '2026-02-26 22:10:01', '2026-02-26 22:10:01'),
('Tibyan-RF-v1', 'RandomForest', 'Scikit-Learn Random Forest Classifier', 'NHANES Dataset 2025', '2026-04-20', '2026-04-29 14:34:49', '2026-04-29 14:34:49'),
('Tibyan-RF-v2', 'RandomForest', 'Scikit-Learn Random Forest Classifier', 'NHANES Dataset 2025', '2026-04-20', '2026-04-30 08:52:30', '2026-04-30 08:52:30');

-- --------------------------------------------------------

--
-- Table structure for table `prediction_records`
--

DROP TABLE IF EXISTS `prediction_records`;
CREATE TABLE IF NOT EXISTS `prediction_records` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `upid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_version_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `risk_level_dm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `safe_fail_triggered` tinyint(1) NOT NULL DEFAULT '0',
  `feature_input_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prediction_records_model_version_id_foreign` (`model_version_id`),
  KEY `prediction_records_upid_index` (`upid`),
  KEY `model_version_id` (`model_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prediction_records`
--

INSERT INTO `prediction_records` (`id`, `upid`, `model_version_id`, `risk_level_dm`, `safe_fail_triggered`, `feature_input_type`, `confidence_score`, `created_at`, `updated_at`) VALUES
(4, 'A005', 'Tibyan-RF-v1', 'Low', 0, 'standard_lab_v1', 0.94, '2026-04-30 10:17:29', '2026-04-30 10:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(100) NOT NULL,
  `text` varchar(255) NOT NULL,
  `text1` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `upid_links`
--

DROP TABLE IF EXISTS `upid_links`;
CREATE TABLE IF NOT EXISTS `upid_links` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `upid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_date` timestamp NULL DEFAULT NULL,
  `deletion_flag_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upid_links_upid_unique` (`upid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `upid_links`
--

INSERT INTO `upid_links` (`id`, `user_id`, `upid`, `creation_date`, `last_activity_date`, `deletion_flag_date`, `created_at`, `updated_at`) VALUES
(9, 0, 'A001', '2026-04-28 21:00:00', '2026-04-29 12:39:26', NULL, '2026-04-29 12:39:26', '2026-04-29 12:39:26'),
(10, 8, 'A005', '2026-04-30 11:10:03', NULL, NULL, '2026-04-30 08:10:03', NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytical_tables`
--
ALTER TABLE `analytical_tables`
  ADD CONSTRAINT `analytical_tables_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tibyan_pii`.`user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feature_importance`
--
ALTER TABLE `feature_importance`
  ADD CONSTRAINT `fk_model_id` FOREIGN KEY (`model_version_id`) REFERENCES `model_versions` (`id`);

--
-- Constraints for table `health_logs`
--
ALTER TABLE `health_logs`
  ADD CONSTRAINT `fk_health_upid` FOREIGN KEY (`upid`) REFERENCES `upid_links` (`upid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `model_performance_logs`
--
ALTER TABLE `model_performance_logs`
  ADD CONSTRAINT `fk_performance_version` FOREIGN KEY (`model_version_id`) REFERENCES `model_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prediction_records`
--
ALTER TABLE `prediction_records`
  ADD CONSTRAINT `fk_modelv_id` FOREIGN KEY (`model_version_id`) REFERENCES `model_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prediction_upid` FOREIGN KEY (`upid`) REFERENCES `upid_links` (`upid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `upid_links`
--
ALTER TABLE `upid_links`
  ADD CONSTRAINT `fk_analytical_upid_pii` FOREIGN KEY (`upid`) REFERENCES `tibyan_pii`.`user` (`upid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
