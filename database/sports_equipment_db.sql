-- ============================================================================
-- University Sports Equipment Booking Management System
-- Database Name: sports_equipment_db
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- Create Database
CREATE DATABASE IF NOT EXISTS `sports_equipment_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sports_equipment_db`;

-- Drop existing tables in reverse dependency order if re-importing
DROP TABLE IF EXISTS `equipment_returns`;
DROP TABLE IF EXISTS `equipment_issues`;
DROP TABLE IF EXISTS `equipment_requests`;
DROP TABLE IF EXISTS `equipment`;
DROP TABLE IF EXISTS `equipment_categories`;
DROP TABLE IF EXISTS `users`;

-- ============================================================================
-- 7.1 Users Table (users)
-- Stores student, staff, and administrator account information.
-- ============================================================================
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(30) NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7.2 Equipment Categories Table (equipment_categories)
-- Stores the categories used to organize equipment.
-- ============================================================================
CREATE TABLE `equipment_categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7.3 Equipment Table (equipment)
-- Stores equipment details and quantities.
-- ============================================================================
CREATE TABLE `equipment` (
  `equipment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `equipment_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `total_quantity` INT NOT NULL DEFAULT 0 CHECK (`total_quantity` >= 0),
  `available_quantity` INT NOT NULL DEFAULT 0 CHECK (`available_quantity` >= 0),
  `condition_status` VARCHAR(50) NOT NULL DEFAULT 'Good',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `fk_equipment_category`
    FOREIGN KEY (`category_id`) REFERENCES `equipment_categories` (`category_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7.4 Equipment Requests Table (equipment_requests)
-- Stores equipment borrowing requests submitted by students and staff.
-- ============================================================================
CREATE TABLE `equipment_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `equipment_id` INT NOT NULL,
  `quantity_requested` INT NOT NULL CHECK (`quantity_requested` > 0),
  `request_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` DATE NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT NULL,
  `review_note` TEXT NULL,
  CONSTRAINT `fk_requests_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_requests_equipment`
    FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_requests_reviewed_by`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7.5 Equipment Issues Table (equipment_issues)
-- Stores records of equipment issued to users.
-- Each request can have at most one issue record (request_id is UNIQUE).
-- ============================================================================
CREATE TABLE `equipment_issues` (
  `issue_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL UNIQUE,
  `issued_by` INT NOT NULL,
  `issue_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` DATE NOT NULL,
  `quantity_issued` INT NOT NULL CHECK (`quantity_issued` > 0),
  CONSTRAINT `fk_issues_request`
    FOREIGN KEY (`request_id`) REFERENCES `equipment_requests` (`request_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_issues_admin`
    FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7.6 Equipment Returns Table (equipment_returns)
-- Stores records of equipment returned by students and staff.
-- issue_id is non-unique because an issue may have multiple partial returns.
-- ============================================================================
CREATE TABLE `equipment_returns` (
  `return_id` INT AUTO_INCREMENT PRIMARY KEY,
  `issue_id` INT NOT NULL,
  `received_by` INT NOT NULL,
  `return_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `quantity_returned` INT NOT NULL CHECK (`quantity_returned` > 0),
  `condition_on_return` VARCHAR(50) NOT NULL DEFAULT 'Good',
  `notes` TEXT NULL,
  CONSTRAINT `fk_returns_issue`
    FOREIGN KEY (`issue_id`) REFERENCES `equipment_issues` (`issue_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_returns_admin`
    FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA (Default Password for all accounts: password123)
-- Hash generated via PHP password_hash('password123', PASSWORD_DEFAULT)
-- ============================================================================

INSERT INTO `users` (`user_id`, `student_id`, `full_name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, NULL, 'Navaratnam (Sports Officer)', 'admin@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'admin', 'active'),
(2, '2023/ICT/001', 'Kamal', 'kamal@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(3, '2023/ICT/002', 'Nimal', 'nimal@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(4, '2023/ICT/003', 'Priya', 'priya@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(5, '2023/ICT/004', 'Kumar', 'kumar@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(6, '2023/ICT/005', 'Fathima', 'fathima@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(7, '2023/ICT/006', 'Anura', 'anura@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'student', 'active'),
(8, NULL, 'Sivakumar (Senior Lecturer)', 'sivakumar@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'staff', 'active'),
(9, NULL, 'Tharshini (Lecturer)', 'tharshini@gmail.com', '$2y$12$GCKkEduYRxjv23k8vi6kxeU3Ckc/UEWdAeTRQ.hs0br.ZWDbVUdQG', 'staff', 'active');

INSERT INTO `equipment_categories` (`category_id`, `category_name`, `description`, `active`) VALUES
(1, 'Cricket', 'Bats, leather balls, pads, gloves, helmets, and stumps for university cricket practices and matches.', 1),
(2, 'Badminton', 'Racquets, feather and nylon shuttlecocks, and court nets for indoor stadium use.', 1),
(3, 'Football & Futsal', 'Size 5 match footballs, futsal balls, training cones, and goalkeeper gloves.', 1),
(4, 'Volleyball', 'Indoor and outdoor volleyballs, nets, and antennae.', 1),
(5, 'Table Tennis', 'Table tennis bats, 3-star balls, and net assemblies.', 1),
(6, 'Athletics & Fitness', 'Stopwatches, relay batons, shot puts, javelins, and yoga mats.', 1);

INSERT INTO `equipment` (`equipment_id`, `category_id`, `equipment_name`, `description`, `total_quantity`, `available_quantity`, `condition_status`, `active`) VALUES
(1, 1, 'English Willow Cricket Bat (SG)', 'Full-size men/women English willow bat for inter-faculty matches.', 10, 10, 'Good', 1),
(2, 1, 'Cricket Batting Pads & Gloves Set', 'Standard protective leg guards and batting gloves pair.', 12, 12, 'Good', 1),
(3, 1, 'Leather Cricket Ball (4-Piece)', 'Red alum-tanned leather ball for practice and tournament play.', 24, 24, 'New', 1),
(4, 2, 'Yonex Astrox Badminton Racquet', 'Lightweight graphite racquet strung at 24 lbs.', 16, 16, 'Good', 1),
(5, 2, 'Mavis 350 Nylon Shuttlecock Tube', 'Tube of 6 medium-speed yellow nylon shuttlecocks.', 20, 20, 'New', 1),
(6, 3, 'FIFA Pro Size 5 Football', 'Thermally bonded match and training football.', 15, 15, 'Good', 1),
(7, 4, 'Mikasa V200W Official Volleyball', '18-panel aerodynamic indoor/outdoor volleyball.', 12, 12, 'Good', 1),
(8, 5, 'Butterfly Timo Boll Table Tennis Bat', 'ITTF-approved rubber table tennis racquet.', 14, 14, 'Good', 1),
(9, 6, 'Aluminum Relay Baton Set (Set of 4)', 'Anodized rounded-edge track relay batons.', 8, 8, 'Good', 1),
(10, 6, 'Digital Track Stopwatch (Casio)', '1/1000-second lap memory stopwatch for athletics trials.', 6, 6, 'Fair', 1);


COMMIT;
