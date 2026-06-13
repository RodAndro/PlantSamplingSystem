<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=3');
    exit;
}

$page_title = 'Plant Samples - Plant Sampling System';

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent';

// Fetch all plant samples with sorting
try {
    $order_by = "ps.sample_date DESC"; // default recent
    
    if ($sort === 'sample_id_asc') {
        $order_by = "ps.sample_id ASC";
    } elseif ($sort === 'sample_id_desc') {
        $order_by = "ps.sample_id DESC";
    } elseif ($sort === 'recent') {
        $order_by = "ps.sample_date DESC";
    } elseif ($sort === 'oldest') {
        $order_by = "ps.sample_date ASC";
    }
    
    $stmt = $pdo->query("
        SELECT 
            ps.*,
            u.name as collector_name
        FROM plant_samples ps
        LEFT JOIN users u ON ps.collected_by = u.id
        ORDER BY " . $order_by
    );
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $samples = [];
    $error_message = "Error fetching samples: " . $e->getMessage();
}

// Count statistics
$total_samples = count($samples);
$pending_samples = count(array_filter($samples, fn($s) => $s['status'] === 'pending'));
$approved_samples = count(array_filter($samples, fn($s) => $s['status'] === 'approved'));
$rejected_samples = count(array_filter($samples, fn($s) => $s['status'] === 'rejected'));
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a class="nav-link" href="index.php">Samples</a>
                    <a class="nav-link" href="add_sample.php">Add Sample</a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a class="nav-link" href="dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold">Plant Sampling System</h1>
                <p class="lead text-muted">Manage and track plant samples</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="add_sample.php" class="btn btn-success btn-lg">
                    <i class="fas fa-plus me-2"></i>Add Sample
                </a>
            </div>
        </div>

        <!-- Statistics Cards with Rejected Samples -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-leaf fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $total_samples; ?></h4>
                        <p class="mb-0">Total Samples</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $pending_samples; ?></h4>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $approved_samples; ?></h4>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $rejected_samples; ?></h4>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Plant Samples List
                        </h5>
                        <!-- Add sorting dropdown menu -->
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-1"></i>Sort By
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item <?php echo $sort === 'recent' ? 'active' : ''; ?>" href="?sort=recent">Recent (Newest First)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'oldest' ? 'active' : ''; ?>" href="?sort=oldest">Oldest (Oldest First)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item <?php echo $sort === 'sample_id_asc' ? 'active' : ''; ?>" href="?sort=sample_id_asc">Sample ID (Low to High)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'sample_id_desc' ? 'active' : ''; ?>" href="?sort=sample_id_desc">Sample ID (High to Low)</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($samples)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h4>No samples found</h4>
                                <p class="text-muted mb-3">Start by adding your first plant sample</p>
                                <a href="add_sample.php" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Add Sample
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sample ID</th>
                                            <th>Plant Name</th>
                                            <th>Location</th>
                                            <th>Sample Date</th>
                                            <th>Collector</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($samples as $sample): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($sample['sample_id']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($sample['plant_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sample['location_sampled']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($sample['sample_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sample['collector_name'] ?? 'Unknown'); ?></td>
                                                <td>
                                                    <?php if ($sample['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php elseif ($sample['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_sample.php?id=<?php echo $sample['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <!-- Only show edit button if user is the owner or admin -->
                                                    <?php if ($_SESSION['user_role'] === 'admin' || $sample['collected_by'] == $_SESSION['user_id']): ?>
                                                        <a href="edit_sample.php?id=<?php echo $sample['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <!-- Delete button with role-based and ownership permissions -->
                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSample(<?php echo $sample['id']; ?>, '<?php echo htmlspecialchars($sample['sample_id']); ?>', true)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php elseif (($sample['status'] === 'pending' || $sample['status'] === 'approved') && $sample['collected_by'] == $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSample(<?php echo $sample['id']; ?>, '<?php echo htmlspecialchars($sample['sample_id']); ?>', false)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add delete sample function with role-based handling -->
    <script>
        function deleteSample(sampleId, sampleIdText, isAdmin) {
            if (isAdmin) {
                if (confirm(`Are you sure you want to permanently delete sample ${sampleIdText}? This cannot be undone.`)) {
                    fetch('api/delete_sample.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: sampleId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Sample deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to delete'));
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            } else {
                // User deletion - requires admin approval
                const reason = prompt(`Request deletion of sample ${sampleIdText}?\n\nOptional: Enter a reason for deletion:`);
                if (reason !== null) {
                    fetch('api/delete_sample.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: sampleId, reason: reason })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Deletion request submitted! Admin will review and approve soon.');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to request deletion'));
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            }
        }
    </script>
</body>
</html>
