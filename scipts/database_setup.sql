-- Plant Sampling System Database Setup
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS plant_sampling_system;
USE plant_sampling_system;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to start fresh
DROP TABLE IF EXISTS plant_samples;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Plant Samples table
CREATE TABLE plant_samples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id VARCHAR(100) UNIQUE NOT NULL,
    plant_name VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    location_sampled VARCHAR(500) NOT NULL,
    sample_date DATETIME NOT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    collected_by INT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Updated demo accounts with correct bcrypt hashes for admin123 and user123
INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@plantsampling.com', '$2y$10$JCi8QIpPxFjpQANb4LuZt.A7xhMTLbWl4BvvKJAM6VEZ2vLCyJxQe', 'admin'),
('Researcher', 'researcher@plantsampling.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/UVm', 'user');
