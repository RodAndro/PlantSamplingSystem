-- Add deletion_requests table to existing plant_sampling_system database
USE plant_sampling_system;

-- Create deletion_requests table if it doesn't exist
CREATE TABLE IF NOT EXISTS deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sample_id INT NOT NULL,
    requested_by INT NOT NULL,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sample_id) REFERENCES plant_samples(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_pending_request (sample_id, requested_by, status)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_sample_id ON deletion_requests(sample_id);
CREATE INDEX IF NOT EXISTS idx_requested_by ON deletion_requests(requested_by);
CREATE INDEX IF NOT EXISTS idx_status ON deletion_requests(status);
