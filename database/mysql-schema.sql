-- CommServe MySQL Database Schema
-- For InfinityFree hosting

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `municipality_id` int(11) DEFAULT NULL,
  `barangay_id` int(11) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('user','official','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements table
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `author_id` bigint(20) UNSIGNED NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcements_status` (`status`),
  KEY `idx_announcements_author` (`author_id`),
  CONSTRAINT `fk_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency Hotlines table
CREATE TABLE IF NOT EXISTS `emergency_hotlines` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `category` enum('police','fire','medical','disaster','utility','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hotlines_category` (`category`),
  KEY `idx_hotlines_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Document Requests table
CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('barangay_clearance','cedula','indigency','residency','business_permit','other') NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','processing','ready','completed','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requests_user` (`user_id`),
  KEY `idx_requests_status` (`status`),
  CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_requests_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_recipient` (`recipient_id`),
  KEY `idx_messages_read` (`is_read`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Regions table
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code` varchar(10) NOT NULL,
  `region_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_code` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Provinces table
CREATE TABLE IF NOT EXISTS `provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province_code` varchar(10) NOT NULL,
  `province_name` varchar(100) NOT NULL,
  `region_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `province_code` (`province_code`),
  KEY `idx_provinces_region` (`region_id`),
  CONSTRAINT `fk_provinces_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Municipalities table
CREATE TABLE IF NOT EXISTS `municipalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `municipality_code` varchar(10) NOT NULL,
  `municipality_name` varchar(100) NOT NULL,
  `province_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `municipality_code` (`municipality_code`),
  KEY `idx_municipalities_province` (`province_id`),
  CONSTRAINT `fk_municipalities_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Barangays table
CREATE TABLE IF NOT EXISTS `barangays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barangay_code` varchar(10) NOT NULL,
  `barangay_name` varchar(100) NOT NULL,
  `municipality_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barangay_code` (`barangay_code`),
  KEY `idx_barangays_municipality` (`municipality_id`),
  CONSTRAINT `fk_barangays_municipality` FOREIGN KEY (`municipality_id`) REFERENCES `municipalities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_password_resets_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Notification Preferences table
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `announcements` tinyint(1) NOT NULL DEFAULT 1,
  `document_updates` tinyint(1) NOT NULL DEFAULT 1,
  `messages` tinyint(1) NOT NULL DEFAULT 1,
  `emergency_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notification_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Preferences table
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `theme` enum('light','dark','auto') NOT NULL DEFAULT 'light',
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample Philippine regions
INSERT INTO `regions` (`region_code`, `region_name`) VALUES
('NCR', 'National Capital Region'),
('CAR', 'Cordillera Administrative Region'),
('01', 'Ilocos Region'),
('02', 'Cagayan Valley'),
('03', 'Central Luzon'),
('04A', 'CALABARZON'),
('04B', 'MIMAROPA'),
('05', 'Bicol Region'),
('06', 'Western Visayas'),
('07', 'Central Visayas'),
('08', 'Eastern Visayas'),
('09', 'Zamboanga Peninsula'),
('10', 'Northern Mindanao'),
('11', 'Davao Region'),
('12', 'SOCCSKSARGEN'),
('13', 'Caraga'),
('BARMM', 'Bangsamoro Autonomous Region in Muslim Mindanao');
