-- Clean SQL Dump for `u630342125_teluguupdates`
-- Fixed collation issue (utf8mb4_unicode_ci)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `u630342125_teluguupdates`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `u630342125_teluguupdates`;

-- ==============================
-- Table: admins
-- ==============================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin','editor') DEFAULT 'admin',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin@teluguschemes.com', '$2y$10$JouFekyxzfwGdHZtQmGI9eijy8MUK9S/WynMA80G.FdJpspfObKS2', 'Super Admin', 'admin', '2025-11-05 15:01:38');

-- ==============================
-- Table: categories
-- ==============================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL DEFAULT '',
  `post_count` INT(11) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `post_count`, `created_at`) VALUES
(1, 'Jobs', 'jobs', 0, '2025-11-05 15:01:38'),
(2, 'Schemes', 'schemes', 2, '2025-11-05 15:01:38'),
(3, 'Exams', 'exams', 0, '2025-11-05 15:01:38'),
(4, 'Scholarships', 'scholarships', 1, '2025-11-05 15:01:38');

-- ==============================
-- Table: posts
-- ==============================
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL DEFAULT '',
  `category_id` INT(11) DEFAULT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `content_html` LONGTEXT DEFAULT NULL,
  `thumb` VARCHAR(1024) DEFAULT NULL,
  `published` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `labels` VARCHAR(255) DEFAULT '',
  `location` VARCHAR(255) DEFAULT '',
  `meta_description` TEXT DEFAULT NULL,
  `meta_keywords` TEXT DEFAULT NULL,
  `views` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` (`id`, `title`, `slug`, `category_id`, `excerpt`, `content_html`, `thumb`, `published`, `created_at`, `labels`, `location`, `meta_description`, `meta_keywords`, `views`) VALUES
(3, 'first post', 'first post', 2, NULL, '<h2>this is the heading :</h2>\r\n<p>and the <strong>heading</strong> is working fine</p>\r\n<p>is it true</p>\r\n<p>really <strong>heading</strong> functionality works properly</p>\r\n<p>i can\'t believe it</p>\r\n<p><img src=\"uploads/1762570467_download_2.png\" alt=\"alt text for image\" width=\"450\" height=\"300\"></p>', 'uploads/1762570467_download_2.png', 1, '2025-11-08 02:54:49', '', '', '', '', 6);

-- ==============================
-- Table: settings
-- ==============================
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(191) NOT NULL,
  `value` TEXT DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
('site_tagline', 'తెలుగువారికి ప్రభుత్వ పథకాల, ఉద్యోగాల & విద్యా సమాచారం ఒకే చోట'),
('site_title', 'Telugu Updates');

COMMIT;
