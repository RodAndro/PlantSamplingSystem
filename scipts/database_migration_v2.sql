-- Plant Sampling System - Database Migration v2
-- Add JSON columns for plant sample detail, sampling location, and environmental conditions

ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS plant_sample_detail JSON;
ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS sampling_location JSON;
ALTER TABLE plant_samples ADD COLUMN IF NOT EXISTS environmental_conditions JSON;

-- Example JSON structures:
-- plant_sample_detail: {
--   "date_of_sampling": "2025-01-15",
--   "plant_species_scientific": "Scientific Name",
--   "plant_common_name": "Common Name",
--   "sample_description": "Description here"
-- }
--
-- sampling_location: {
--   "latitude": 40.7128,
--   "longitude": -74.0060,
--   "location_description": "Forest",
--   "region_country": "USA"
-- }
--
-- environmental_conditions: {
--   "soil_composition": {
--     "ph": 6.8,
--     "nutrients": "High nitrogen",
--     "type": "Loamy"
--   },
--   "temperature_celsius": 22.5,
--   "humidity_percent": 65,
--   "altitude_meters": 150
-- }
