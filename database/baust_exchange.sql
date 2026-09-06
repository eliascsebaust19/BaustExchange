-- BAUST Exchange Database Schema
-- MySQL 8+

CREATE DATABASE IF NOT EXISTS `baust_exchange` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `baust_exchange`;

-- Users table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `google_id` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `profile_image` VARCHAR(500) DEFAULT NULL,
    `role` ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    `student_level` ENUM('senior','junior') DEFAULT NULL,
    `department` VARCHAR(255) DEFAULT NULL,
    `student_id` VARCHAR(50) DEFAULT NULL,
    `level_term` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_student_level` (`student_level`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'fa-tag',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Listings table
CREATE TABLE `listings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `condition` ENUM('new','like_new','good','used','damaged') NOT NULL DEFAULT 'good',
    `transaction_type` ENUM('sell','buy','share','exchange','rent') NOT NULL DEFAULT 'sell',
    `price` DECIMAL(10,2) DEFAULT NULL,
    `rent_price` DECIMAL(10,2) DEFAULT NULL,
    `rent_period` ENUM('hour','day','week','month','semester') DEFAULT NULL,
    `exchange_for` TEXT DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `contact_preference` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','active','sold','exchanged','shared','rented','rejected','removed') NOT NULL DEFAULT 'pending',
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- Listing images table
CREATE TABLE `listing_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `listing_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Exchange requests table
CREATE TABLE `exchange_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `listing_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED NOT NULL,
    `message` TEXT DEFAULT NULL,
    `offered_item` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_receiver` (`receiver_id`)
) ENGINE=InnoDB;

-- Rentals table
CREATE TABLE `rentals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `listing_id` INT UNSIGNED NOT NULL,
    `renter_id` INT UNSIGNED NOT NULL,
    `owner_id` INT UNSIGNED NOT NULL,
    `rent_price` DECIMAL(10,2) NOT NULL,
    `rent_period` ENUM('hour','day','week','month','semester') NOT NULL DEFAULT 'day',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending','active','completed','cancelled','rejected','returned') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`renter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_renter` (`renter_id`),
    INDEX `idx_owner` (`owner_id`),
    INDEX `idx_listing` (`listing_id`)
) ENGINE=InnoDB;

-- Shares table (for sharing items)
CREATE TABLE `shares` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `listing_id` INT UNSIGNED NOT NULL,
    `requester_id` INT UNSIGNED NOT NULL,
    `owner_id` INT UNSIGNED NOT NULL,
    `message` TEXT DEFAULT NULL,
    `duration` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_requester` (`requester_id`),
    INDEX `idx_owner` (`owner_id`),
    INDEX `idx_listing` (`listing_id`)
) ENGINE=InnoDB;

-- Wanted items table
CREATE TABLE `wanted_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `description` TEXT DEFAULT NULL,
    `budget` DECIMAL(10,2) DEFAULT NULL,
    `status` ENUM('active','fulfilled','closed') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_status` (`status`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB;

-- Messages table
CREATE TABLE `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED NOT NULL,
    `listing_id` INT UNSIGNED DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE SET NULL,
    INDEX `idx_sender` (`sender_id`),
    INDEX `idx_receiver` (`receiver_id`),
    INDEX `idx_read` (`is_read`)
) ENGINE=InnoDB;

-- Notifications table
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `reference_id` INT UNSIGNED DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- Reports table
CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `listing_id` INT UNSIGNED DEFAULT NULL,
    `reported_user_id` INT UNSIGNED DEFAULT NULL,
    `reason` ENUM('fake_listing','spam','wrong_info','offensive','suspicious_user','other') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- Activities table
CREATE TABLE `activities` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- Settings table
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default categories
INSERT INTO `categories` (`name`, `slug`, `icon`, `sort_order`) VALUES
('Books', 'books', 'fa-book', 1),
('Furniture', 'furniture', 'fa-chair', 2),
('Electronics', 'electronics', 'fa-laptop', 3),
('Clothing', 'clothing', 'fa-shirt', 4),
('Stationery', 'stationery', 'fa-pen', 5),
('Academic Materials', 'academic-materials', 'fa-graduation-cap', 6),
('Sports', 'sports', 'fa-futbol', 7),
('Accessories', 'accessories', 'fa-gem', 8),
('Others', 'others', 'fa-ellipsis-h', 9);

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'BAUST Exchange'),
('site_description', 'Buy • Sell • Share • Exchange • Rent'),
('items_per_page', '12'),
('maintenance_mode', '0');
