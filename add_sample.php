<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=3');
    exit;
}

$success_message = '';
$error_fields = array();
$form_data = array(
    'sample_id' => '',
    'plant_name' => '',
    'species' => '',
    'location_sampled' => '',
    'sample_date' => '',
    'notes' => '',
    'latitude' => '',
    'longitude' => '',
    'location_description' => '',
    'region_country' => '',
    'soil_ph' => '',
    'soil_nutrients' => '',
    'soil_type' => '',
    'temperature_celsius' => '',
    'humidity_percent' => '',
    'altitude_meters' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = array(
        'sample_id' => trim($_POST['sample_id'] ?? ''),
        'plant_name' => trim($_POST['plant_name'] ?? ''),
        'species' => trim($_POST['species'] ?? ''),
        'location_sampled' => trim($_POST['location_sampled'] ?? ''),
        'sample_date' => $_POST['sample_date'] ?? '',
        'notes' => trim($_POST['notes'] ?? ''),
        'latitude' => $_POST['latitude'] ?? '',
        'longitude' => $_POST['longitude'] ?? '',
        'location_description' => trim($_POST['location_description'] ?? ''),
        'region_country' => trim($_POST['region_country'] ?? ''),
        'soil_ph' => $_POST['soil_ph'] ?? '',
        'soil_nutrients' => trim($_POST['soil_nutrients'] ?? ''),
        'soil_type' => trim($_POST['soil_type'] ?? ''),
        'temperature_celsius' => $_POST['temperature_celsius'] ?? '',
        'humidity_percent' => $_POST['humidity_percent'] ?? '',
        'altitude_meters' => $_POST['altitude_meters'] ?? ''
    );

    $required_fields = array(
        'sample_id' => 'Sample ID',
        'plant_name' => 'Plant Common Name',
        'species' => 'Plant Species',
        'location_sampled' => 'Location',
        'sample_date' => 'Date of Sampling',
        'location_description' => 'Location Description',
        'region_country' => 'Region/Country',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude'
    );

    foreach ($required_fields as $field => $label) {
        $value = $form_data[$field];
        if ($field === 'latitude' || $field === 'longitude') {
            // For coordinates, check if value is provided and is a valid number
            if ($value === '' || $value === null) {
                $error_fields[$field] = "$label is required";
            }
        } else {
            // For other fields, check if not empty
            if (empty($value)) {
                $error_fields[$field] = "$label is required";
            }
        }
    }

    if (!empty($form_data['soil_ph']) && (floatval($form_data['soil_ph']) < 0 || floatval($form_data['soil_ph']) > 14)) {
        $error_fields['soil_ph'] = 'Soil pH must be between 0 and 14';
    }

    if (!empty($form_data['humidity_percent']) && (intval($form_data['humidity_percent']) < 0 || intval($form_data['humidity_percent']) > 100)) {
        $error_fields['humidity_percent'] = 'Humidity must be between 0 and 100 percentage';
    }

    if (!empty($form_data['altitude_meters']) && intval($form_data['altitude_meters']) < 0) {
        $error_fields['altitude_meters'] = 'Altitude must be a positive value in meters';
    }

    if (!empty($form_data['sample_date'])) {
        $sample_dt = new DateTime($form_data['sample_date']);
        $current_dt = new DateTime();
        if ($sample_dt > $current_dt) {
            $error_fields['sample_date'] = 'Sample date cannot be in the future. Date must be today or earlier.';
        }
    }

    if (empty($error_fields)) {
        try {
            $plant_sample_detail = json_encode([
                'date_of_sampling' => $form_data['sample_date'],
                'plant_species_scientific' => $form_data['species'],
                'plant_common_name' => $form_data['plant_name'],
                'sample_description' => $form_data['notes']
            ]);
            
            $sampling_location = json_encode([
                'latitude' => floatval($form_data['latitude']),
                'longitude' => floatval($form_data['longitude']),
                'location_description' => $form_data['location_description'],
                'region_country' => $form_data['region_country']
            ]);

            $environmental_conditions = json_encode([
                'soil_composition' => [
                    'ph' => !empty($form_data['soil_ph']) ? floatval($form_data['soil_ph']) : null,
                    'nutrients' => $form_data['soil_nutrients'] ?: null,
                    'type' => $form_data['soil_type'] ?: null
                ],
                'temperature_celsius' => !empty($form_data['temperature_celsius']) ? floatval($form_data['temperature_celsius']) : null,
                'humidity_percent' => !empty($form_data['humidity_percent']) ? intval($form_data['humidity_percent']) : null,
                'altitude_meters' => !empty($form_data['altitude_meters']) ? intval($form_data['altitude_meters']) : null
            ]);

            $status = ($_SESSION['user_role'] === 'admin') ? 'approved' : 'pending';
            $approved_by = ($_SESSION['user_role'] === 'admin') ? $_SESSION['user_id'] : NULL;
            $approved_at = ($_SESSION['user_role'] === 'admin') ? date('Y-m-d H:i:s') : NULL;
            
            $stmt = $pdo->prepare("
                INSERT INTO plant_samples 
                (sample_id, plant_name, species, location_sampled, sample_date, notes, collected_by, status, approved_by, approved_at, plant_sample_detail, sampling_location, environmental_conditions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$form_data['sample_id'], $form_data['plant_name'], $form_data['species'], $form_data['location_sampled'], $form_data['sample_date'], $form_data['notes'], $_SESSION['user_id'], $status, $approved_by, $approved_at, $plant_sample_detail, $sampling_location, $environmental_conditions])) {
                $message = ($_SESSION['user_role'] === 'admin') 
                    ? 'Sample added and automatically approved!' 
                    : 'Sample added successfully! Awaiting admin approval.';
                
                $success_message = $message;
                // Clear form after successful submission
                $form_data = array_fill_keys(array_keys($form_data), '');
            }

        } catch(PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_fields['sample_id'] = 'Sample ID already exists.';
            } else {
                error_log("Database error in add_sample.php: " . $e->getMessage());
                $error_fields['_general'] = "Database error occurred. Please check that all required columns exist and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample - Plant Sampling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath fill='%23dc3545' d='M8 4a.5.5 0 0 0-.707.293L6.414 6l.879.879A.5.5 0 1 1 6.586 7.293L6 6.707l-.586.586a.5.5 0 1 1-.707-.707L5.293 6l-.879-.879A.5.5 0 1 1 5.414 4.293L6 4.879l.586-.586A.5.5 0 0 0 8 4z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            padding-right: calc(1.5em + 0.75rem);
        }
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
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
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="card-title mb-0"><i class="fas fa-plus me-2"></i>Add Plant Sample</h4>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <small class="text-info">Samples created by admins are automatically approved</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                <hr>
                                <a href="index.php" class="btn btn-success btn-sm">Back to Samples</a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($error_fields)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><strong>Please fill in the required fields:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($error_fields as $field => $message): ?>
                                            <?php if ($field !== '_general'): ?>
                                                <li><?php echo htmlspecialchars($message); ?></li>
                                            <?php else: ?>
                                                <li><?php echo htmlspecialchars($message); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-leaf me-2"></i>Plant Sample Detail</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="sample_id" class="form-label">Sample ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control <?php echo isset($error_fields['sample_id']) ? 'is-invalid' : ''; ?>" id="sample_id" name="sample_id" value="<?php echo htmlspecialchars($form_data['sample_id']); ?>" required>
                                                <?php if (isset($error_fields['sample_id'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['sample_id']); ?></div>
                                                <?php endif; ?>
                                                <small class="form-text text-muted">Unique identifier for this sample</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="sample_date" class="form-label">Date of Sampling <span class="text-danger">*</span></label>
                                                <!-- Changed from datetime-local to date input and limited to today max -->
                                                <input type="date" class="form-control <?php echo isset($error_fields['sample_date']) ? 'is-invalid' : ''; ?>" id="sample_date" name="sample_date" 
                                                       value="<?php echo htmlspecialchars($form_data['sample_date']); ?>"
                                                       max="<?php echo date('Y-m-d'); ?>" required>
                                                <?php if (isset($error_fields['sample_date'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['sample_date']); ?></div>
                                                <?php endif; ?>
                                                <small class="form-text text-muted">Must be today or earlier</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="species" class="form-label">Plant Species (Scientific) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="species" name="species" value="<?php echo htmlspecialchars($form_data['species']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="plant_name" class="form-label">Plant Common Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control <?php echo isset($error_fields['plant_name']) ? 'is-invalid' : ''; ?>" id="plant_name" name="plant_name" value="<?php echo htmlspecialchars($form_data['plant_name']); ?>" required>
                                                <?php if (isset($error_fields['plant_name'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['plant_name']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Sample Description</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional description of the sample"><?php echo htmlspecialchars($form_data['notes']); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Sampling Location</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['latitude']) ? 'is-invalid' : ''; ?>" id="latitude" name="latitude" step="0.0001" value="<?php echo htmlspecialchars($form_data['latitude']); ?>" required>
                                                <?php if (isset($error_fields['latitude'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['latitude']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['longitude']) ? 'is-invalid' : ''; ?>" id="longitude" name="longitude" step="0.0001" value="<?php echo htmlspecialchars($form_data['longitude']); ?>" required>
                                                <?php if (isset($error_fields['longitude'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['longitude']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location_description" class="form-label">Location Description <span class="text-danger">*</span></label>
                                            <select class="form-control <?php echo isset($error_fields['location_description']) ? 'is-invalid' : ''; ?>" id="location_description" name="location_description" required>
                                                <option value="">Select location type...</option>
                                                <option value="Forest" <?php echo $form_data['location_description'] === 'Forest' ? 'selected' : ''; ?>>Forest</option>
                                                <option value="Meadow" <?php echo $form_data['location_description'] === 'Meadow' ? 'selected' : ''; ?>>Meadow</option>
                                                <option value="Wetland" <?php echo $form_data['location_description'] === 'Wetland' ? 'selected' : ''; ?>>Wetland</option>
                                                <option value="Mountain" <?php echo $form_data['location_description'] === 'Mountain' ? 'selected' : ''; ?>>Mountain</option>
                                                <option value="Urban" <?php echo $form_data['location_description'] === 'Urban' ? 'selected' : ''; ?>>Urban</option>
                                                <option value="Agricultural" <?php echo $form_data['location_description'] === 'Agricultural' ? 'selected' : ''; ?>>Agricultural</option>
                                                <option value="Other" <?php echo $form_data['location_description'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <?php if (isset($error_fields['location_description'])): ?>
                                                <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['location_description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location_sampled" class="form-label">Location <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($error_fields['location_sampled']) ? 'is-invalid' : ''; ?>" id="location_sampled" name="location_sampled" value="<?php echo htmlspecialchars($form_data['location_sampled']); ?>" required>
                                            <?php if (isset($error_fields['location_sampled'])): ?>
                                                <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['location_sampled']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="region_country" class="form-label">Region/Country <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($error_fields['region_country']) ? 'is-invalid' : ''; ?>" id="region_country" name="region_country" value="<?php echo htmlspecialchars($form_data['region_country']); ?>" required>
                                            <?php if (isset($error_fields['region_country'])): ?>
                                                <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['region_country']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-cloud me-2"></i>Environmental Conditions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="soil_ph" class="form-label">Soil pH</label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['soil_ph']) ? 'is-invalid' : ''; ?>" id="soil_ph" name="soil_ph" step="0.1" min="0" max="14" value="<?php echo htmlspecialchars($form_data['soil_ph']); ?>">
                                                <?php if (isset($error_fields['soil_ph'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['soil_ph']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="soil_nutrients" class="form-label">Soil Nutrients</label>
                                                <input type="text" class="form-control <?php echo isset($error_fields['soil_nutrients']) ? 'is-invalid' : ''; ?>" id="soil_nutrients" name="soil_nutrients" placeholder="e.g., High nitrogen" value="<?php echo htmlspecialchars($form_data['soil_nutrients']); ?>">
                                                <?php if (isset($error_fields['soil_nutrients'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['soil_nutrients']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="soil_type" class="form-label">Soil Type</label>
                                                <input type="text" class="form-control <?php echo isset($error_fields['soil_type']) ? 'is-invalid' : ''; ?>" id="soil_type" name="soil_type" placeholder="e.g., Loamy" value="<?php echo htmlspecialchars($form_data['soil_type']); ?>">
                                                <?php if (isset($error_fields['soil_type'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['soil_type']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="temperature_celsius" class="form-label">Temperature (°C)</label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['temperature_celsius']) ? 'is-invalid' : ''; ?>" id="temperature_celsius" name="temperature_celsius" step="0.1" value="<?php echo htmlspecialchars($form_data['temperature_celsius']); ?>">
                                                <?php if (isset($error_fields['temperature_celsius'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['temperature_celsius']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="humidity_percent" class="form-label">Humidity (%)</label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['humidity_percent']) ? 'is-invalid' : ''; ?>" id="humidity_percent" name="humidity_percent" step="1" min="0" max="100" value="<?php echo htmlspecialchars($form_data['humidity_percent']); ?>">
                                                <?php if (isset($error_fields['humidity_percent'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['humidity_percent']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="altitude_meters" class="form-label">Altitude (meters)</label>
                                                <input type="number" class="form-control <?php echo isset($error_fields['altitude_meters']) ? 'is-invalid' : ''; ?>" id="altitude_meters" name="altitude_meters" step="1" min="0" value="<?php echo htmlspecialchars($form_data['altitude_meters']); ?>">
                                                <?php if (isset($error_fields['altitude_meters'])): ?>
                                                    <div class="invalid-feedback" style="display: block;"><?php echo htmlspecialchars($error_fields['altitude_meters']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Add Sample
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
