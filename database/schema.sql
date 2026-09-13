-- College Complaint & Maintenance System Database Schema
-- Database: college_complaint_db

CREATE DATABASE IF NOT EXISTS `college_complaint_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `college_complaint_db`;

-- Drop existing tables if re-initialization is needed
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `complaint_updates`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
  `department` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-wrench',
  `description` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complaints Table
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_code` VARCHAR(20) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
  `status` ENUM('Pending', 'Assigned', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `assigned_staff_id` INT NULL,
  `image_path` VARCHAR(255) NULL,
  `resolution_image` VARCHAR(255) NULL,
  `resolution_notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaint_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaint_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaint_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complaint Updates / Activity Log Table
CREATE TABLE `complaint_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status_from` VARCHAR(50) NULL,
  `status_to` VARCHAR(50) NULL,
  `comment` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_update_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_update_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedbacks Table
CREATE TABLE `feedbacks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL UNIQUE,
  `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `comments` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_feedback_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Initial Categories
INSERT INTO `categories` (`id`, `name`, `icon`, `description`) VALUES
(1, 'Electrical & Lighting', 'bi-lightning-charge-fill', 'Issues related to power outlets, lights, fans, wiring, and circuit breakers.'),
(2, 'IT & Computers', 'bi-laptop-fill', 'Lab computers, Wi-Fi connectivity, projectors, smart boards, and network ports.'),
(3, 'Plumbing & Sanitation', 'bi-droplet-fill', 'Restroom maintenance, water leakage, pipe bursts, and tap repairs.'),
(4, 'Furniture & Carpentry', 'bi-archive-fill', 'Broken desks, chairs, doors, window locks, podiums, and whiteboards.'),
(5, 'HVAC & Air Conditioning', 'bi-snow', 'AC cooling issues, remote controllers, ventilation, and heating systems.'),
(6, 'Hostel & Mess', 'bi-building-fill', 'Hostel room repairs, mess hygiene, water coolers, and common area maintenance.');

-- Seed Initial Users (Password: Password123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `phone`) VALUES
(1, 'System Administrator', 'admin@college.edu', '$2y$10$AlKGmlMqg/SHUOCX3GlyfuNwXE/gFU/IltrfkPgNA5lxNLSh6WNGu', 'admin', 'Administration', '9876543210');
-- Seed Sample Updates
INSERT INTO `complaint_updates` (`complaint_id`, `user_id`, `status_from`, `status_to`, `comment`, `created_at`) VALUES
(1, 2, NULL, 'Pending', 'Complaint submitted by student.', NOW() - INTERVAL 2 DAY),
(1, 1, 'Pending', 'Assigned', 'Assigned ticket to Robert Miller (Electrical Dept).', NOW() - INTERVAL 1 DAY),
(2, 2, NULL, 'Pending', 'Anonymous complaint submitted.', NOW() - INTERVAL 1 DAY),
(2, 4, 'Pending', 'In Progress', 'Technician inspected projector, ordering replacement HDMI splitter.', NOW() - INTERVAL 12 HOUR);
