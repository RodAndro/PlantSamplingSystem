-- Add JSON columns to plant_samples table if they don't exist
ALTER TABLE plant_samples ADD COLUMN plant_sample_detail JSON NULL;
ALTER TABLE plant_samples ADD COLUMN sampling_location JSON NULL;
ALTER TABLE plant_samples ADD COLUMN environmental_conditions JSON NULL;
