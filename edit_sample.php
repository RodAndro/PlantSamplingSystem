<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=3');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=1');
    exit;
}

$sample_id = intval($_GET['id']);
$error_message = '';
$success_message = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sample) {
        header('Location: index.php?error=2');
        exit;
    }

    if ($sample['collected_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
        header('Location: index.php?error=4');
        exit;
    }

    $plant_sample_detail = $sample['plant_sample_detail'] ? json_decode($sample['plant_sample_detail'], true) : [];
    $sampling_location = $sample['sampling_location'] ? json_decode($sample['sampling_location'], true) : [];
    $environmental_conditions = $sample['environmental_conditions'] ? json_decode($sample['environmental_conditions'], true) : [];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plant_name = $_POST['plant_name'] ?? '';
        $species = $_POST['species'] ?? '';
        $location_sampled = $_POST['location_sampled'] ?? '';
        $sample_date = $_POST['sample_date'] ?? '';
        
        $plant_sample_detail_new = json_encode([
            'date_of_sampling' => $sample_date,
            'plant_species_scientific' => $species,
            'plant_common_name' => $plant_name,
            'sample_description' => $_POST['notes'] ?? ''
        ]);
        
        $sampling_location_new = json_encode([
            'latitude' => floatval($_POST['latitude'] ?? 0),
            'longitude' => floatval($_POST['longitude'] ?? 0),
            'location_description' => $_POST['location_description'] ?? '',
            'region_country' => $_POST['region_country'] ?? ''
        ]);
        
        $environmental_conditions_new = json_encode([
            'soil_composition' => [
                'ph' => floatval($_POST['soil_ph'] ?? 0),
                'nutrients' => $_POST['soil_nutrients'] ?? '',
                'type' => $_POST['soil_type'] ?? ''
            ],
            'temperature_celsius' => floatval($_POST['temperature_celsius'] ?? 0),
            'humidity_percent' => intval($_POST['humidity_percent'] ?? 0),
            'altitude_meters' => intval($_POST['altitude_meters'] ?? 0)
        ]);

        if (empty($plant_name) || empty($species) || empty($location_sampled) || empty($sample_date)) {
            $error_message = "All required fields must be filled!";
        } else {
            $stmt = $pdo->prepare("
                UPDATE plant_samples 
                SET plant_name = ?, species = ?, location_sampled = ?, sample_date = ?, 
                    plant_sample_detail = ?, sampling_location = ?, environmental_conditions = ?,
                    status = 'pending'
                WHERE id = ?
            ");
            
            if ($stmt->execute([$plant_name, $species, $location_sampled, $sample_date, 
                               $plant_sample_detail_new, $sampling_location_new, $environmental_conditions_new, $sample_id])) {
                $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
                $stmt->execute([$sample_id]);
                $updated_sample = $stmt->fetch(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Sample updated successfully! Status reset to pending for admin review.',
                    'data' => $updated_sample
                ]);
                exit;
            } else {
                $error_message = "Error updating sample. Please try again.";
            }
        }
    }

} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

$page_title = 'Edit Sample - Plant Sampling System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf me-2"></i>Plant Sampling System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="index.php">Samples</a>
                <a class="nav-link" href="add_sample.php">Add Sample</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a class="nav-link" href="dashboard.php">Admin</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold">Edit Plant Sample</h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="view_sample.php?id=<?php echo $sample['id']; ?>" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>View
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" id="editSampleForm">
                    <!-- Plant Sample Detail Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-leaf me-2"></i>Plant Sample Detail</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="plant_name" class="form-label fw-bold">Plant Common Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="plant_name" name="plant_name" 
                                           value="<?php echo htmlspecialchars($sample['plant_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sample_date" class="form-label fw-bold">Sample Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="sample_date" name="sample_date" 
                                           value="<?php echo substr($sample['sample_date'], 0, 10); ?>" 
                                           max="<?php echo date('Y-m-d'); ?>" required>
                                    <small class="form-text text-muted">Must be today or earlier</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="species" class="form-label fw-bold">Species (Scientific) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="species" name="species" 
                                           value="<?php echo htmlspecialchars($sample['species']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="location_sampled" class="form-label fw-bold">Location Sampled <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="location_sampled" name="location_sampled" 
                                           value="<?php echo htmlspecialchars($sample['location_sampled']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Sample Description</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($plant_sample_detail['sample_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Sampling Location Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Sampling Location</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="0.0001"
                                           value="<?php echo htmlspecialchars($sampling_location['latitude'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="0.0001"
                                           value="<?php echo htmlspecialchars($sampling_location['longitude'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="location_description" class="form-label">Location Description</label>
                                <input type="text" class="form-control" id="location_description" name="location_description" 
                                       value="<?php echo htmlspecialchars($sampling_location['location_description'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="region_country" class="form-label">Region/Country</label>
                                <input type="text" class="form-control" id="region_country" name="region_country" 
                                       value="<?php echo htmlspecialchars($sampling_location['region_country'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Environmental Conditions Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-cloud me-2"></i>Environmental Conditions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="soil_ph" class="form-label">Soil pH</label>
                                    <input type="number" class="form-control" id="soil_ph" name="soil_ph" step="0.1" min="0" max="14"
                                           value="<?php echo htmlspecialchars($environmental_conditions['soil_composition']['ph'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="soil_nutrients" class="form-label">Soil Nutrients</label>
                                    <input type="text" class="form-control" id="soil_nutrients" name="soil_nutrients" 
                                           value="<?php echo htmlspecialchars($environmental_conditions['soil_composition']['nutrients'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="soil_type" class="form-label">Soil Type</label>
                                    <input type="text" class="form-control" id="soil_type" name="soil_type" 
                                           value="<?php echo htmlspecialchars($environmental_conditions['soil_composition']['type'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="temperature_celsius" class="form-label">Temperature (°C)</label>
                                    <input type="number" class="form-control" id="temperature_celsius" name="temperature_celsius" step="0.1"
                                           value="<?php echo htmlspecialchars($environmental_conditions['temperature_celsius'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="humidity_percent" class="form-label">Humidity (%)</label>
                                    <input type="number" class="form-control" id="humidity_percent" name="humidity_percent" step="1" min="0" max="100"
                                           value="<?php echo htmlspecialchars($environmental_conditions['humidity_percent'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="altitude_meters" class="form-label">Altitude (meters)</label>
                                    <input type="number" class="form-control" id="altitude_meters" name="altitude_meters" step="1" min="0"
                                           value="<?php echo htmlspecialchars($environmental_conditions['altitude_meters'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Sample Info
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Sample ID</small>
                            <p class="fw-bold"><?php echo htmlspecialchars($sample['sample_id']); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Current Status</small>
                            <p>
                                <?php if ($sample['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif ($sample['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending Review</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo htmlspecialchars($sample['status']); ?></span>
                                <?php endif; ?>
                            </p>
                            <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i>Saving will reset status to pending</small>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Created</small>
                            <p class="fw-bold"><?php echo date('M j, Y H:i', strtotime($sample['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editSampleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok');
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'index.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to update sample'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the sample');
            });
        });
    </script>
</body>
</html>
