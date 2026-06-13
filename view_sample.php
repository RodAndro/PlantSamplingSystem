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

try {
    $stmt = $pdo->prepare("
        SELECT 
            ps.*,
            u.name as collector_name,
            u.email as collector_email
        FROM plant_samples ps
        LEFT JOIN users u ON ps.collected_by = u.id
        WHERE ps.id = ?
    ");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sample) {
        header('Location: index.php?error=2');
        exit;
    }

    $isOwner = ($sample['collected_by'] == $_SESSION['user_id']);
    $isAdmin = ($_SESSION['user_role'] === 'admin');
    $canEdit = ($isOwner || $isAdmin);

    $plant_sample_detail = $sample['plant_sample_detail'] ? json_decode($sample['plant_sample_detail'], true) : [];
    $sampling_location = $sample['sampling_location'] ? json_decode($sample['sampling_location'], true) : [];
    $environmental_conditions = $sample['environmental_conditions'] ? json_decode($sample['environmental_conditions'], true) : [];

} catch(PDOException $e) {
    $error_message = "Error fetching sample: " . $e->getMessage();
}

$page_title = 'View Sample - Plant Sampling System';
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
                <h1 class="display-5 fw-bold">View Plant Sample</h1>
                <?php if (!$canEdit): ?>
                    <p class="text-muted"><i class="fas fa-lock me-2"></i>View-only mode (not your sample)</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($canEdit): ?>
                    <a href="edit_sample.php?id=<?php echo $sample['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit Sample
                    </a>
                <?php endif; ?>
                <!-- Delete button with role-based workflow -->
                <?php if ($canEdit): ?>
                    <?php if ($isOwner && !$isAdmin): ?>
                        <button class="btn btn-danger" onclick="requestDeletion(<?php echo $sample['id']; ?>, '<?php echo htmlspecialchars($sample['status']); ?>')">
                            <i class="fas fa-trash me-2"></i>Request Delete
                        </button>
                    <?php elseif ($isAdmin): ?>
                        <button class="btn btn-danger" onclick="deleteSampleDirect(<?php echo $sample['id']; ?>)">
                            <i class="fas fa-trash me-2"></i>Delete Sample
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Plant Sample Detail Card -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-leaf me-2"></i>Plant Sample Detail
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Sample ID</h6>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($sample['sample_id']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Status</h6>
                                <p>
                                    <?php if ($sample['status'] === 'approved'): ?>
                                        <span class="badge bg-success fs-6">Approved</span>
                                    <?php elseif ($sample['status'] === 'pending'): ?>
                                        <span class="badge bg-warning fs-6">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger fs-6"><?php echo htmlspecialchars($sample['status']); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Plant Common Name</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sample['plant_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Species (Scientific)</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sample['species']); ?></p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Sample Date</h6>
                                <p class="fs-5"><?php echo date('M j, Y', strtotime($sample['sample_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Created At</h6>
                                <p class="fs-5"><?php echo date('M j, Y H:i', strtotime($sample['created_at'])); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($plant_sample_detail['sample_description'])): ?>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-muted text-uppercase">Sample Description</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($plant_sample_detail['sample_description']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sampling Location Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>Sampling Location
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Latitude</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sampling_location['latitude'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Longitude</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sampling_location['longitude'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Location Description</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sampling_location['location_description'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted text-uppercase">Region/Country</h6>
                                <p class="fs-5"><?php echo htmlspecialchars($sampling_location['region_country'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Environmental Conditions Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cloud me-2"></i>Environmental Conditions
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase mb-3">Soil Composition</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <small class="text-muted">pH</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['soil_composition']['ph'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Nutrients</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['soil_composition']['nutrients'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Type</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['soil_composition']['type'] ?? 'N/A'); ?></p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">Temperature</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['temperature_celsius'] ?? 'N/A'); ?>°C</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Humidity</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['humidity_percent'] ?? 'N/A'); ?>%</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Altitude</small>
                                <p class="fs-5 fw-bold"><?php echo htmlspecialchars($environmental_conditions['altitude_meters'] ?? 'N/A'); ?>m</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Collector Info -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Collector Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Name</small>
                            <p class="fw-bold"><?php echo htmlspecialchars($sample['collector_name'] ?? 'Unknown'); ?></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Email</small>
                            <p class="fw-bold"><?php echo htmlspecialchars($sample['collector_email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-json me-2"></i>JSON Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted d-block mb-2">Plant Sample Detail</small>
                        <pre class="bg-light p-2 rounded mb-3" style="font-size: 0.75rem;"><code><?php echo htmlspecialchars(json_encode($plant_sample_detail, JSON_PRETTY_PRINT)); ?></code></pre>
                        
                        <small class="text-muted d-block mb-2">Sampling Location</small>
                        <pre class="bg-light p-2 rounded mb-3" style="font-size: 0.75rem;"><code><?php echo htmlspecialchars(json_encode($sampling_location, JSON_PRETTY_PRINT)); ?></code></pre>
                        
                        <small class="text-muted d-block mb-2">Environmental Conditions</small>
                        <pre class="bg-light p-2 rounded" style="font-size: 0.75rem;"><code><?php echo htmlspecialchars(json_encode($environmental_conditions, JSON_PRETTY_PRINT)); ?></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function requestDeletion(sampleId, status) {
            if (status !== 'pending' && status !== 'approved') {
                alert('You can only request deletion for pending or approved samples.');
                return;
            }

            const reason = prompt('Please provide a reason for deletion (optional):');
            if (reason !== null) {
                fetch('api/request_deletion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        sample_id: sampleId,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Deletion request submitted! Status: ' + data.status + '. Waiting for admin approval.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to submit deletion request'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }

        function deleteSampleDirect(sampleId) {
            if (confirm('Are you sure you want to permanently delete this sample?')) {
                fetch('api/delete_sample.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sample_id: sampleId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sample deleted successfully!');
                        window.location.href = 'index.php';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete sample'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            }
        }
    </script>
</body>
</html>
