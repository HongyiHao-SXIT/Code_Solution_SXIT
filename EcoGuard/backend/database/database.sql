CREATE DATABASE IF NOT EXISTS `trashdet` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `trashdet`;
CREATE TABLE IF NOT EXISTS `user` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `security_code` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `detect_task` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `source_type` VARCHAR(20),
    `source_path` VARCHAR(255),
    `result_path` VARCHAR(255),
    `device_id` VARCHAR(50),
    `location` VARCHAR(100),
    `status` VARCHAR(20) DEFAULT 'PENDING',
    `error_msg` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `latitude` FLOAT,
    `longitude` FLOAT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `detect_item` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT,
    `label` VARCHAR(50),
    `confidence` FLOAT,
    `x1` INT,
    `y1` INT,
    `x2` INT,
    `y2` INT,
    `area` INT,
    `handle_state` VARCHAR(20) DEFAULT 'NEW',
    `frame_index` INT,
    `snapshot_path` VARCHAR(255),
    `captured_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_detect_item_task`
        FOREIGN KEY (`task_id`) REFERENCES `detect_task`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ops_log` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `robot` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100),
    `status` VARCHAR(20) DEFAULT 'OFFLINE',
    `ip_address` VARCHAR(50),
    `current_lat` FLOAT,
    `current_lng` FLOAT,
    `target_lat` FLOAT,
    `target_lng` FLOAT,
    `last_heartbeat` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `next_command` VARCHAR(100) DEFAULT 'IDLE',
    `battery` INT DEFAULT 100,
    `config` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance indexes for hotspot/statistics queries
CREATE INDEX `idx_detect_task_time_geo`
    ON `detect_task` (`created_at`, `latitude`, `longitude`);

CREATE INDEX `idx_detect_item_task_label`
    ON `detect_item` (`task_id`, `label`);

CREATE INDEX `idx_detect_item_label`
    ON `detect_item` (`label`);