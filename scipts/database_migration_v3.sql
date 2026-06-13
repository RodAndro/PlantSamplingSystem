-- Complete database setup with JSON columns for plant samples
-- This migration handles all table creation and column updates

-- Create plants table if it doesn't exist
CREATE TABLE IF NOT EXISTS plants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  scientific_name VARCHAR(255),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create plant_samples table if it doesn't exist
CREATE TABLE IF NOT EXISTS plant_samples (
  id INT PRIMARY KEY AUTO_INCREMENT,
  plant_id INT NOT NULL,
  sample_date DATE NOT NULL,
  plant_sample_detail JSON NULL,
  sampling_location JSON NULL,
  environmental_conditions JSON NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);

-- Modify plant_sample_detail column to ensure it's JSON type
ALTER TABLE plant_samples MODIFY COLUMN plant_sample_detail JSON NULL;

-- Modify sampling_location column to ensure it's JSON type
ALTER TABLE plant_samples MODIFY COLUMN sampling_location JSON NULL;

-- Modify environmental_conditions column to ensure it's JSON type
ALTER TABLE plant_samples MODIFY COLUMN environmental_conditions JSON NULL;

-- Create index on sample_date for better query performance
CREATE INDEX IF NOT EXISTS idx_sample_date ON plant_samples(sample_date);

-- Create index on plant_id for foreign key queries
CREATE INDEX IF NOT EXISTS idx_plant_id ON plant_samples(plant_id);
