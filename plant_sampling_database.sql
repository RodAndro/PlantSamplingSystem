-- Plant Sampling System Database
-- Import this SQL file directly into phpMyAdmin

-- Create database
CREATE DATABASE IF NOT EXISTS plant_sampling_system;
USE plant_sampling_system;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to start fresh
DROP TABLE IF EXISTS plant_samples;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create plant_samples table with all required columns including JSON columns
CREATE TABLE plant_samples (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id VARCHAR(100) UNIQUE NOT NULL,
    plant_name VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    location_sampled VARCHAR(500) NOT NULL,
    sample_date DATE NOT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    collected_by INT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    plant_sample_detail JSON NULL,
    sampling_location JSON NULL,
    environmental_conditions JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sample_date (sample_date),
    INDEX idx_status (status),
    INDEX idx_plant_name (plant_name)
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert demo accounts
INSERT INTO users (id, name, email, password, role) VALUES 
(1, 'System Admin', 'admin@plantsampling.com', '$2y$10$zN3Rq5fK8zL9m2pQ6sT7uOw.Xz5Yy8a1b2c3d4e5f6g7h8i9j0k1', 'admin'),
(2, 'Researcher', 'researcher@plantsampling.com', '$2y$10$zN3Rq5fK8zL9m2pQ6sT7uOw.Xz5Yy8a1b2c3d4e5f6g7h8i9j0k1', 'user');
