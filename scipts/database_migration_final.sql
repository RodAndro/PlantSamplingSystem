-- Plant Sampling System - Complete Database Setup
-- This file creates the database and tables with all necessary columns

CREATE DATABASE IF NOT EXISTS plant_sampling_system;
USE plant_sampling_system;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Plant Samples table
CREATE TABLE IF NOT EXISTS plant_samples (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    plant_sample_detail JSON NULL,
    sampling_location JSON NULL,
    environmental_conditions JSON NULL,
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_sample_date ON plant_samples(sample_date);
CREATE INDEX IF NOT EXISTS idx_plant_name ON plant_samples(plant_name);
CREATE INDEX IF NOT EXISTS idx_collected_by ON plant_samples(collected_by);
CREATE INDEX IF NOT EXISTS idx_status ON plant_samples(status);

-- Insert demo users if they don't exist
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@plantsampling.com', '$2y$10$JCi8QIpPxFjpQANb4LuZt.A7xhMTLbWl4BvvKJAM6VEZ2vLCyJxQe', 'admin'),
('Researcher', 'researcher@plantsampling.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/UVm', 'user');
