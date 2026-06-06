CREATE DATABASE IF NOT EXISTS `visitor_detail`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `visitor_detail`;

CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'admin',
  `token` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uq_accounts_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `locations` (
  `location_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `color` VARCHAR(20) NOT NULL DEFAULT '#3273dc',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_id` VARCHAR(100) DEFAULT NULL,
  `name` VARCHAR(150) DEFAULT NULL,
  `faculty` VARCHAR(150) DEFAULT NULL,
  `semester` VARCHAR(100) DEFAULT NULL,
  `custom_fields` TEXT DEFAULT NULL,
  `username` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `role` VARCHAR(50) DEFAULT NULL,
  `card_token` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_card_token` (`card_token`),
  KEY `idx_users_visitor_id` (`visitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visits` (
  `visit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_id` VARCHAR(100) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `check_in` DATETIME NOT NULL,
  `check_out` DATETIME DEFAULT NULL,
  `location_id` INT UNSIGNED DEFAULT NULL,
  `faculty` VARCHAR(150) DEFAULT NULL,
  `semester` VARCHAR(100) DEFAULT NULL,
  `custom_fields` TEXT DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'checked-in',
  `location_name` VARCHAR(150) DEFAULT NULL,
  `visit_count` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`visit_id`),
  KEY `idx_visits_visitor_id` (`visitor_id`),
  KEY `idx_visits_check_in` (`check_in`),
  KEY `idx_visits_faculty` (`faculty`),
  KEY `idx_visits_semester` (`semester`),
  KEY `idx_visits_location_id` (`location_id`),
  CONSTRAINT `fk_visits_location`
    FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `form_fields` (
  `field_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(150) NOT NULL,
  `field_type` ENUM('text', 'select') NOT NULL DEFAULT 'text',
  `options` TEXT DEFAULT NULL,
  `required` TINYINT(1) NOT NULL DEFAULT 1,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`field_id`),
  UNIQUE KEY `uq_form_fields_field_key` (`field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounts` (`username`, `password`, `role`)
VALUES ('admin', '$2y$10$lyZNOVHx4pqGYAqIYSDj1O.aNcqiDh9bqWfkq5cTG4JgD5p6t547K', 'superadmin')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `role` = VALUES(`role`);

INSERT INTO `locations` (`name`, `description`, `status`, `color`)
SELECT 'Lobby', 'Main visitor registration area', 'active', '#3273dc'
WHERE NOT EXISTS (SELECT 1 FROM `locations` WHERE `name` = 'Lobby');

INSERT INTO `locations` (`name`, `description`, `status`, `color`)
SELECT 'Reading Area', 'Public reading and reference area', 'active', '#23d160'
WHERE NOT EXISTS (SELECT 1 FROM `locations` WHERE `name` = 'Reading Area');

INSERT INTO `locations` (`name`, `description`, `status`, `color`)
SELECT 'Meeting Room', 'Official visit and meeting area', 'active', '#ffdd57'
WHERE NOT EXISTS (SELECT 1 FROM `locations` WHERE `name` = 'Meeting Room');

INSERT INTO `form_fields` (`field_key`, `label`, `field_type`, `options`, `required`, `active`, `is_system`, `sort_order`)
VALUES
  ('name', 'Nama', 'text', NULL, 1, 1, 1, 10),
  ('faculty', 'Faculty', 'text', NULL, 1, 1, 1, 20),
  ('semester', 'Semester', 'select', 'Semester 1\nSemester 2\nSemester 3\nSemester 4\nSemester 5\nSemester 6\nSemester 7\nSemester 8', 1, 1, 1, 30)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `field_type` = VALUES(`field_type`),
  `options` = VALUES(`options`),
  `required` = VALUES(`required`),
  `active` = VALUES(`active`),
  `is_system` = VALUES(`is_system`),
  `sort_order` = VALUES(`sort_order`);
