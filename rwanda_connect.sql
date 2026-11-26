-- Rwanda Connect starter database
-- Import this file using phpMyAdmin.
CREATE DATABASE IF NOT EXISTS rwanda_connect_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rwanda_connect_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'admin') NOT NULL DEFAULT 'citizen',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO service_categories (name, description) VALUES
('Agriculture', 'Farming information and market opportunities'),
('Employment', 'Job announcements and career resources'),
('Education', 'Scholarships, learning and training information'),
('Healthcare', 'Health facility and service information'),
('Community', 'Community issue reporting'),
('Environment', 'Environmental and sanitation reporting'),
('Business', 'Entrepreneurship and economic resources');
