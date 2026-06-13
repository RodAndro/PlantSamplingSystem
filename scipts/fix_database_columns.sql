-- Fix Plant Sampling System - Ensure JSON columns exist
-- Run this script to add missing JSON columns to plant_samples table

ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS plant_sample_detail JSON NULL;
ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS sampling_location JSON NULL;
ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS environmental_conditions JSON NULL;

-- Verify columns exist
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'plant_samples' 
  AND COLUMN_NAME IN ('plant_sample_detail', 'sampling_location', 'environmental_conditions');
