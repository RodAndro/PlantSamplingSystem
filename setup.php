<?php
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=localhost", "root", "");
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS plant_sampling_system");
        $pdo->exec("USE plant_sampling_system");
        
        // Disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Drop existing tables to start fresh
        $pdo->exec("DROP TABLE IF EXISTS deletion_requests");
        $pdo->exec("DROP TABLE IF EXISTS plant_samples");
        $pdo->exec("DROP TABLE IF EXISTS users");
        
        // Create users table
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE plant_samples (
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
        )");

        $pdo->exec("CREATE TABLE deletion_requests (
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
        )");

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $user_password = password_hash('user123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([1, 'System Admin', 'admin@plantsampling.com', $admin_password, 'admin']);
        $stmt->execute([2, 'Researcher', 'researcher@plantsampling.com', $user_password, 'user']);

        $message = "✅ Database setup completed successfully!";
        
    } catch(PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Plant Sampling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-database me-2"></i>Plant Sampling System - Database Setup</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="login.php" class="btn btn-success w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="index.php" class="btn btn-primary w-100">
                                            <i class="fas fa-leaf me-2"></i>View Samples
                                        </a>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6>Demo Accounts Created:</h6>
                                <ul class="mb-3">
                                    <li><strong>Admin:</strong> admin@plantsampling.com / admin123</li>
                                    <li><strong>User:</strong> researcher@plantsampling.com / user123</li>
                                </ul>
                                
                                <h6>Features Available:</h6>
                                <ul class="mb-0">
                                    <li>✅ Admin can approve/reject plant samples</li>
                                    <li>✅ Admin can delete any sample directly</li>
                                    <li>✅ Users can submit plant samples</li>
                                    <li>✅ Users can request deletion of pending/approved samples</li>
                                    <li>✅ Admin approval required for user deletion requests</li>
                                    <li>✅ Query samples by Sample ID</li>
                                    <li>✅ Update sample information</li>
                                    <li>✅ JSON data storage for complex fields</li>
                                    <li>✅ Admin dashboard with deletion requests</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$message): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This will completely reset your database and remove all existing data.
                            </div>
                            <p>Click the button below to set up your database with:</p>
                            <ul>
                                <li>Admin and user accounts</li>
                                <li>Plant sample approval system</li>
                                <li>Role-based deletion system</li>
                                <li>JSON columns for complex data</li>
                                <li>CRUD operations support</li>
                            </ul>
                            <form method="POST">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Setup Plant Sampling Database
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
