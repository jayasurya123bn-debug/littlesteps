-- phpMyAdmin SQL Dump
-- Little Steps Childcare Platform Database Schema & Seed Data
-- Version 2.0 (Complete Specification)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `littlesteps_db`
--
-- [Removed CREATE DATABASE for ProFreeHost compatibility]
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table 1: users (Parents & Admins)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('parent','admin') NOT NULL DEFAULT 'parent',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 1,
  `verification_token` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 2: providers (Daycare Organizations & Centers)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `providers`;
CREATE TABLE `providers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `pincode` varchar(15) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `established_year` int(4) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `capacity_children` int(11) DEFAULT 0,
  `age_groups` json DEFAULT NULL,
  `operating_hours` varchar(255) DEFAULT NULL,
  `is_24x7` tinyint(1) NOT NULL DEFAULT 0,
  `pricing_hourly` decimal(10,2) DEFAULT NULL,
  `pricing_daily` decimal(10,2) DEFAULT NULL,
  `pricing_monthly` decimal(10,2) DEFAULT NULL,
  `facilities` json DEFAULT NULL,
  `safety_measures` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `admin_notes` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 3: caregivers (Staff & Babysitters)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `caregivers`;
CREATE TABLE `caregivers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `specialization` varchar(255) DEFAULT NULL,
  `background_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` date DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `joined_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  KEY `background_verified` (`background_verified`),
  CONSTRAINT `fk_caregivers_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 4: daycare_centers (Individual Facilities/Branches)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `daycare_centers`;
CREATE TABLE `daycare_centers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('daycare','creche','preschool','babysitting','after_school') NOT NULL DEFAULT 'daycare',
  `address` text NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `pincode` varchar(15) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `images` json DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amenities` json DEFAULT NULL,
  `curriculum` varchar(100) DEFAULT NULL,
  `min_age_months` int(11) DEFAULT 0,
  `max_age_years` int(11) DEFAULT 12,
  `capacity` int(11) DEFAULT 30,
  `current_occupancy` int(11) DEFAULT 0,
  `operating_hours_start` time DEFAULT '07:00:00',
  `operating_hours_end` time DEFAULT '20:00:00',
  `is_24x7` tinyint(1) NOT NULL DEFAULT 0,
  `meals_included` tinyint(1) NOT NULL DEFAULT 1,
  `transport_available` tinyint(1) NOT NULL DEFAULT 1,
  `cctv_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `pricing_hourly` decimal(10,2) DEFAULT 150.00,
  `pricing_daily` decimal(10,2) DEFAULT 800.00,
  `pricing_monthly` decimal(10,2) DEFAULT 9500.00,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `rating` decimal(3,2) DEFAULT 4.80,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  KEY `city` (`city`),
  KEY `is_24x7` (`is_24x7`),
  KEY `status` (`status`),
  CONSTRAINT `fk_centers_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 5: availability (Slot & Calendar Management)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `availability`;
CREATE TABLE `availability` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `center_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 15,
  `booked_count` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 200.00,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `center_id` (`center_id`),
  KEY `date` (`date`),
  CONSTRAINT `fk_availability_center` FOREIGN KEY (`center_id`) REFERENCES `daycare_centers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 6: bookings (Full Lifecycle Bookings)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `center_id` bigint(20) UNSIGNED NOT NULL,
  `child_name` varchar(100) NOT NULL,
  `child_age_months` int(11) DEFAULT NULL,
  `child_gender` varchar(20) DEFAULT NULL,
  `booking_type` enum('hourly','daily','monthly','emergency') NOT NULL DEFAULT 'daily',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `hours_count` decimal(8,2) DEFAULT NULL,
  `days_count` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'Online UPI/Card',
  `payment_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` enum('parent','provider','admin') DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `pickup_person` varchar(100) DEFAULT NULL,
  `pickup_phone` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `provider_notes` text DEFAULT NULL,
  `parent_rating` int(1) DEFAULT NULL,
  `parent_review` text DEFAULT NULL,
  `provider_rating` int(1) DEFAULT NULL,
  `provider_review` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `user_id` (`user_id`),
  KEY `center_id` (`center_id`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`),
  KEY `start_datetime` (`start_datetime`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_center` FOREIGN KEY (`center_id`) REFERENCES `daycare_centers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 7: subscriptions (Recurring Care Plans)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_code` varchar(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `center_id` bigint(20) UNSIGNED NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_type` enum('weekly','monthly','quarterly','yearly','custom') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `billing_cycle_day` int(2) DEFAULT 1,
  `monthly_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','paused','expired','cancelled') NOT NULL DEFAULT 'active',
  `child_name` varchar(100) NOT NULL,
  `included_hours_per_day` int(11) DEFAULT 8,
  `included_days_per_week` int(11) DEFAULT 5,
  `notes` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_code` (`subscription_code`),
  KEY `user_id` (`user_id`),
  KEY `center_id` (`center_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_subs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subs_center` FOREIGN KEY (`center_id`) REFERENCES `daycare_centers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 8: notifications (Cross-Role Alerts)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `provider_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','booking','system') NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 9: reviews (Parent Reviews & Ratings)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `center_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `title` varchar(255) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `staff_rating` int(1) DEFAULT 5,
  `cleanliness_rating` int(1) DEFAULT 5,
  `safety_rating` int(1) DEFAULT 5,
  `value_rating` int(1) DEFAULT 5,
  `is_verified` tinyint(1) NOT NULL DEFAULT 1,
  `admin_response` text DEFAULT NULL,
  `admin_response_at` datetime DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `center_id` (`center_id`),
  KEY `booking_id` (`booking_id`),
  KEY `rating` (`rating`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_center` FOREIGN KEY (`center_id`) REFERENCES `daycare_centers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 10: documents (Compliance & License Uploads)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('business_license','identity_proof','address_proof','tax_registration','safety_certificate','insurance','other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  KEY `verified_by` (`verified_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_docs_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_docs_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 11: settings (Platform Configuration)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 12: audit_log (Optional Table for Security Audits)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type` enum('admin','provider','parent','system') NOT NULL DEFAULT 'system',
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 13: messages (Internal Messaging)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_type` enum('parent','provider','admin') NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_type` enum('parent','provider','admin') NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DATA
-- Test Accounts:
--   Admin:    admin@littlesteps.com    / admin123
--   Provider: provider@littlesteps.com / provider123
--   Parent:   parent@littlesteps.com   / parent123
-- ========================================================

-- 1. Users (Admin + Sample Parents)
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `city`, `state`, `pincode`, `role`, `is_active`, `email_verified`) VALUES
(1, 'admin@littlesteps.com', '$2y$10$oDe5fDfDJ60sVpWaqbcA/eIlvDj6tiGmEbB1Nh0hAvdFHhKQGW7Ci', 'System', 'Admin', '+91 9876543210', 'Headquarters, Suite 400', 'Bengaluru', 'Karnataka', '560001', 'admin', 1, 1),
(2, 'parent@littlesteps.com', '$2y$10$PvUchmtlgfY/7nacu7wZE.7wm8ZBxDiz61.7Imai2JnsUbxw79da6', 'Sneha', 'Sharma', '+91 9876543222', '42 Palm Grove Avenue, Indiranagar', 'Bengaluru', 'Karnataka', '560038', 'parent', 1, 1),
(3, 'ananya.iyer@gmail.com', '$2y$10$PvUchmtlgfY/7nacu7wZE.7wm8ZBxDiz61.7Imai2JnsUbxw79da6', 'Ananya', 'Iyer', '+91 9811223344', '12 Coral Heights, Koramangala', 'Bengaluru', 'Karnataka', '560034', 'parent', 1, 1),
(4, 'rahul.verma@outlook.com', '$2y$10$PvUchmtlgfY/7nacu7wZE.7wm8ZBxDiz61.7Imai2JnsUbxw79da6', 'Rahul', 'Verma', '+91 9988776655', '77 Green Valley, Whitefield', 'Bengaluru', 'Karnataka', '560066', 'parent', 1, 1);

-- 2. Providers
INSERT INTO `providers` (`id`, `business_name`, `owner_name`, `email`, `password`, `phone`, `alternate_phone`, `address`, `city`, `state`, `pincode`, `latitude`, `longitude`, `description`, `established_year`, `license_number`, `capacity_children`, `is_24x7`, `pricing_hourly`, `pricing_daily`, `pricing_monthly`, `rating`, `total_reviews`, `status`, `is_active`) VALUES
(1, 'Bloom & Blossom Daycare Network', 'Dr. Priya Nambiar', 'provider@littlesteps.com', '$2y$10$QeD/gGjIREtjZVvthB6dceB.oIpj8HKnq9Ty4vrZFooi.xf7suhJG', '+91 9845012345', '+91 9845012346', '104 Sunrise Boulevard, Koramangala 4th Block', 'Bengaluru', 'Karnataka', '560034', 12.9352, 77.6245, 'Premium early childhood learning, Montessori nursery, and round-the-clock childcare for working professionals.', 2018, 'KA-CH-2018-8849', 60, 1, 180.00, 950.00, 11500.00, 4.90, 48, 'approved', 1),
(2, 'Little Wonders 24x7 Creche', 'Rajesh K. Mehta', 'rajesh@littlewonders.in', '$2y$10$QeD/gGjIREtjZVvthB6dceB.oIpj8HKnq9Ty4vrZFooi.xf7suhJG', '+91 9900112233', '+91 9900112234', '55 Orchid Road, Indiranagar', 'Bengaluru', 'Karnataka', '560038', 12.9784, 77.6408, 'Safe, nurturing overnight and day childcare with certified paediatric nurses and continuous live CCTV access.', 2020, 'KA-CH-2020-1092', 45, 1, 200.00, 1100.00, 13000.00, 4.85, 36, 'approved', 1),
(3, 'Tiny Toes Infant Care', 'Kavita Menon', 'kavita@tinytoes.org', '$2y$10$QeD/gGjIREtjZVvthB6dceB.oIpj8HKnq9Ty4vrZFooi.xf7suhJG', '+91 9741234567', NULL, '18 Jubilee Hills, Road No 36', 'Hyderabad', 'Telangana', '500033', 17.4319, 78.4073, 'Holistic care tailored specifically for infants aged 2 months to 2 years with infant sensory stimulation.', 2021, 'TS-CH-2021-3321', 30, 0, 220.00, 1200.00, 14000.00, 4.75, 19, 'approved', 1),
(4, 'StarKidz Montessori Academy', 'Sunita Rao', 'sunita@starkidz.com', '$2y$10$QeD/gGjIREtjZVvthB6dceB.oIpj8HKnq9Ty4vrZFooi.xf7suhJG', '+91 9880123456', NULL, '92 Residency Road', 'Bengaluru', 'Karnataka', '560025', 12.9698, 77.6045, 'Montessori-driven after school and weekend activity care center.', 2023, 'KA-CH-2023-4567', 40, 0, 160.00, 850.00, 9800.00, 4.60, 12, 'pending', 1);

-- 3. Daycare Centers
INSERT INTO `daycare_centers` (`id`, `provider_id`, `name`, `type`, `address`, `area`, `city`, `state`, `pincode`, `contact_phone`, `contact_email`, `description`, `curriculum`, `min_age_months`, `max_age_years`, `capacity`, `current_occupancy`, `is_24x7`, `meals_included`, `transport_available`, `cctv_enabled`, `pricing_hourly`, `pricing_daily`, `pricing_monthly`, `status`, `rating`, `total_reviews`) VALUES
(1, 1, 'Bloom & Blossom - Koramangala Flagship', 'daycare', '104 Sunrise Boulevard, Koramangala 4th Block', 'Koramangala', 'Bengaluru', 'Karnataka', '560034', '+91 9845012345', 'koramangala@bloomblossom.in', 'Our flagship center features air-conditioned baby nurseries, sensory play arenas, organic chef-cooked meals, and 24x7 doctor-on-call.', 'Montessori & Playway', 3, 8, 35, 18, 1, 1, 1, 1, 180.00, 950.00, 11500.00, 'active', 4.90, 48),
(2, 1, 'Bloom & Blossom - HSR Layout Branch', 'creche', '21st Main, Sector 1, HSR Layout', 'HSR Layout', 'Bengaluru', 'Karnataka', '560102', '+91 9845012349', 'hsr@bloomblossom.in', 'Spacious open-air playground, safe foam soft-play, and dedicated infant nap zones.', 'Playway', 6, 6, 25, 12, 0, 1, 1, 1, 160.00, 850.00, 10500.00, 'active', 4.80, 22),
(3, 2, 'Little Wonders 24x7 Creche - Indiranagar', 'daycare', '55 Orchid Road, Near Metro Station, Indiranagar', 'Indiranagar', 'Bengaluru', 'Karnataka', '560038', '+91 9900112233', 'care@littlewonders.in', '24x7 dedicated emergency and night-shift childcare for doctors, IT consultants, and airline crew.', 'Reggio Emilia', 2, 10, 45, 24, 1, 1, 1, 1, 200.00, 1100.00, 13000.00, 'active', 4.85, 36),
(4, 3, 'Tiny Toes Infant Care - Jubilee Hills', 'creche', '18 Jubilee Hills, Road No 36', 'Jubilee Hills', 'Hyderabad', 'Telangana', '500033', '+91 9741234567', 'info@tinytoes.org', 'Specialised infant sanctuary providing 1:1 infant-to-nurse ratio with sterile infant feeding setups.', 'Infant Milestones', 2, 2, 30, 14, 0, 1, 0, 1, 220.00, 1200.00, 14000.00, 'active', 4.75, 19);

-- 4. Caregivers
INSERT INTO `caregivers` (`id`, `provider_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `qualification`, `experience_years`, `specialization`, `background_verified`, `verification_date`, `bio`, `status`) VALUES
(1, 1, 'Mary', 'Fernandes', 'mary.f@bloomblossom.in', '+91 9820011221', 'Female', 'M.Ed in Early Childhood Education', 8, 'Infant Milestones & Toddler Psychology', 1, '2023-01-15', 'Certified early childhood educator with 8 years of experience guiding infants through vital sensory milestones.', 'active'),
(2, 1, 'Lakshmi', 'Narayanan', 'lakshmi.n@bloomblossom.in', '+91 9820011222', 'Female', 'B.Sc Nursing, Pediatric CPR Certified', 6, 'Pediatric First Aid & Infant Nutrition', 1, '2023-03-20', 'Registered pediatric nurse specialized in infant sleep schedules, feeding, and medical safety.', 'active'),
(3, 2, 'Rupal', 'Deshmukh', 'rupal@littlewonders.in', '+91 9830022331', 'Female', 'Diploma in Montessori Teaching', 5, 'Overnight Care & Storytelling', 1, '2023-05-10', 'Loving and gentle caregiver experienced with overnight sleep routines and toddlers.', 'active'),
(4, 3, 'Anu', 'George', 'anu@tinytoes.org', '+91 9840033441', 'Female', 'Certified Infant Care Specialist', 4, 'Newborn & Infant Development', 1, '2023-06-18', 'Trained infant care specialist with focus on colic relief and gentle sleeping rhythms.', 'active');

-- 5. Availability Slots
INSERT INTO `availability` (`id`, `center_id`, `date`, `start_time`, `end_time`, `max_capacity`, `booked_count`, `price`, `is_available`, `notes`) VALUES
(1, 1, CURDATE(), '08:00:00', '13:00:00', 15, 6, 950.00, 1, 'Morning Shift & Activity Learning'),
(2, 1, CURDATE(), '13:00:00', '18:00:00', 15, 4, 950.00, 1, 'Afternoon Nap & Play'),
(3, 1, CURDATE(), '18:00:00', '23:00:00', 10, 2, 1100.00, 1, 'Evening & Dinner Session'),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '18:00:00', 20, 5, 1400.00, 1, 'Full Day Care with Meals'),
(5, 3, CURDATE(), '20:00:00', '08:00:00', 12, 3, 1600.00, 1, '24x7 Night Shift Overnight Care');

-- 6. Bookings
INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `center_id`, `child_name`, `child_age_months`, `child_gender`, `booking_type`, `start_datetime`, `end_datetime`, `hours_count`, `days_count`, `unit_price`, `total_amount`, `final_amount`, `payment_status`, `status`, `special_requirements`, `pickup_person`, `pickup_phone`, `emergency_contact`) VALUES
(1, 'LS-BKG-1001', 2, 1, 'Aarav Sharma', 24, 'Male', 'daily', CONCAT(CURDATE(), ' 08:00:00'), CONCAT(CURDATE(), ' 17:00:00'), 9.00, 1, 950.00, 950.00, 950.00, 'paid', 'confirmed', 'Allergic to peanuts. Requires afternoon fruit snack.', 'Sneha Sharma (Mother)', '+91 9876543222', '+91 9876543223 (Vikram Father)'),
(2, 'LS-BKG-1002', 3, 1, 'Meera Iyer', 36, 'Female', 'hourly', DATE_ADD(CONCAT(CURDATE(), ' 14:00:00'), INTERVAL 1 DAY), DATE_ADD(CONCAT(CURDATE(), ' 18:00:00'), INTERVAL 1 DAY), 4.00, 1, 180.00, 720.00, 720.00, 'paid', 'confirmed', 'Loves coloring and story books.', 'Ananya Iyer', '+91 9811223344', '+91 9811223340'),
(3, 'LS-BKG-1003', 4, 3, 'Kabir Verma', 18, 'Male', 'emergency', CONCAT(CURDATE(), ' 20:00:00'), DATE_ADD(CONCAT(CURDATE(), ' 08:00:00'), INTERVAL 1 DAY), 12.00, 1, 200.00, 2400.00, 2400.00, 'paid', 'in_progress', 'Emergency doctor shift booking. Warm milk at 9 PM.', 'Rahul Verma', '+91 9988776655', '+91 9988776650'),
(4, 'LS-BKG-1004', 2, 2, 'Aarav Sharma', 24, 'Male', 'daily', DATE_SUB(CONCAT(CURDATE(), ' 08:00:00'), INTERVAL 5 DAY), DATE_SUB(CONCAT(CURDATE(), ' 16:00:00'), INTERVAL 5 DAY), 8.00, 1, 850.00, 850.00, 850.00, 'paid', 'completed', 'None', 'Sneha Sharma', '+91 9876543222', '+91 9876543223');

-- 7. Subscriptions
INSERT INTO `subscriptions` (`id`, `subscription_code`, `user_id`, `center_id`, `plan_name`, `plan_type`, `start_date`, `end_date`, `monthly_amount`, `total_amount`, `amount_paid`, `status`, `child_name`, `included_hours_per_day`, `included_days_per_week`) VALUES
(1, 'LS-SUB-2001', 2, 1, 'Full-Time Executive Care', 'monthly', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 11500.00, 11500.00, 11500.00, 'active', 'Aarav Sharma', 9, 5),
(2, 'LS-SUB-2002', 3, 3, 'Night-Owl 24x7 Essential', 'monthly', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 13000.00, 13000.00, 13000.00, 'active', 'Meera Iyer', 10, 4);

-- 8. Notifications
INSERT INTO `notifications` (`id`, `user_id`, `provider_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 2, NULL, 'Booking Confirmed! ðŸŒŸ', 'Your booking for Aarav Sharma at Bloom & Blossom - Koramangala has been confirmed for today.', 'booking', 'bookings.php', 0, NOW()),
(2, NULL, 1, 'New Booking Received ðŸ‘¶', 'Parent Sneha Sharma booked a daily session for Aarav Sharma (Booking #LS-BKG-1001).', 'booking', 'bookings.php', 0, NOW()),
(3, 1, NULL, 'New Provider Registration', 'StarKidz Montessori Academy applied for platform verification.', 'system', 'providers.php', 0, NOW());

-- 9. Reviews
INSERT INTO `reviews` (`id`, `user_id`, `center_id`, `booking_id`, `rating`, `title`, `review_text`, `pros`, `cons`, `staff_rating`, `cleanliness_rating`, `safety_rating`, `value_rating`, `is_verified`, `is_approved`) VALUES
(1, 2, 1, 4, 5, 'Peace of mind while working!', 'Bloom & Blossom has been a life saver for us. The caregivers treat Aarav like their own child. Live CCTV feed and detailed meal logs gave us complete reassurance.', 'Spotless hygiene, caring nurses, organic food', 'Slightly busy parking during evening pickup', 5, 5, 5, 5, 1, 1),
(2, 3, 3, NULL, 5, 'Unmatched 24x7 Night Care Support', 'As an emergency room physician, finding overnight childcare was impossible until Little Steps. Little Wonders staff are incredibly professional and compassionate.', '24x7 flexibility, experienced pediatric staff', 'Book in advance for weekend nights', 5, 5, 5, 5, 1, 1);

-- 10. Documents
INSERT INTO `documents` (`id`, `provider_id`, `document_type`, `document_name`, `file_path`, `file_size`, `file_type`, `expiry_date`, `status`, `verified_by`, `verified_at`) VALUES
(1, 1, 'business_license', 'KMC_Childcare_Trade_License.pdf', 'uploads/documents/doc_license_bloom.pdf', 1048576, 'application/pdf', '2027-12-31', 'approved', 1, NOW()),
(2, 1, 'safety_certificate', 'Fire_Safety_NOC_2024.pdf', 'uploads/documents/doc_fire_safety.pdf', 524288, 'application/pdf', '2026-06-30', 'approved', 1, NOW()),
(3, 1, 'insurance', 'Commercial_Child_Liability_Policy.pdf', 'uploads/documents/doc_insurance.pdf', 842100, 'application/pdf', '2026-09-30', 'approved', 1, NOW()),
(4, 4, 'business_license', 'StarKidz_Registration_Certificate.pdf', 'uploads/documents/doc_starkidz_reg.pdf', 942100, 'application/pdf', '2026-10-15', 'pending', NULL, NULL);

-- 11. Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_public`) VALUES
('site_name', 'Little Steps', 'string', 'Platform Brand Name', 'general', 1),
('site_tagline', 'Trusted 24x7 Childcare Platform', 'string', 'Platform Tagline', 'general', 1),
('contact_email', 'support@littlesteps.com', 'string', 'Official Support Email', 'general', 1),
('contact_phone', '+91 80 4912 3456', 'string', 'Official Helpline Phone', 'general', 1),
('cancellation_cutoff_hours', '4', 'integer', 'Hours before start time for full refund', 'booking', 0),
('platform_fee_percent', '5.0', 'string', 'Platform service fee percentage', 'finance', 0),
('enable_sms_alerts', '1', 'boolean', 'Enable automated SMS updates to parents', 'notification', 0),
('require_id_verification', '1', 'boolean', 'Require KYC proof before provider activation', 'compliance', 0);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

