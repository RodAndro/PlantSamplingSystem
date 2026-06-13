<?php
session_start();
require_once 'config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=3');
    exit;
}

$page_title = 'Admin Dashboard - Plant Sampling System';

// Fetch statistics
try {
    // Total samples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM plant_samples");
    $total_samples = $stmt->fetch()['total'];

    // Pending samples
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM plant_samples WHERE status = 'pending'");
    $pending_samples = $stmt->fetch()['pending'];

    // Approved samples
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM plant_samples WHERE status = 'approved'");
    $approved_samples = $stmt->fetch()['approved'];

    $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM plant_samples WHERE status = 'rejected'");
    $rejected_samples = $stmt->fetch()['rejected'];

    // Pending samples for approval
    $stmt = $pdo->query("
        SELECT 
            ps.*,
            u.name as collector_name,
            u.email as collector_email
        FROM plant_samples ps
        LEFT JOIN users u ON ps.collected_by = u.id
        WHERE ps.status = 'pending'
        ORDER BY ps.created_at DESC
    ");
    $pending_list = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT 
            dr.*,
            ps.sample_id,
            ps.plant_name,
            u.name as requester_name,
            u.email as requester_email,
            a.name as approver_name
        FROM deletion_requests dr
        LEFT JOIN plant_samples ps ON dr.sample_id = ps.id
        LEFT JOIN users u ON dr.requested_by = u.id
        LEFT JOIN users a ON dr.approved_by = a.id
        ORDER BY dr.created_at DESC
    ");
    $deletion_requests = $stmt->fetchAll();

} catch(PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}
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
                <a class="nav-link" href="dashboard.php">Admin</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 fw-bold">Admin Dashboard</h1>
                <p class="lead text-muted">Manage and approve plant samples</p>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-leaf fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $total_samples ?? 0; ?></h4>
                        <p class="mb-0">Total Samples</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $pending_samples ?? 0; ?></h4>
                        <p class="mb-0">Pending Review</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $approved_samples ?? 0; ?></h4>
                        <p class="mb-0">Approved Samples</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4 class="fw-bold"><?php echo $rejected_samples ?? 0; ?></h4>
                        <p class="mb-0">Rejected Samples</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Samples for Approval -->
        <?php if (!empty($pending_list)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Pending Approval (<?php echo count($pending_list); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sample ID</th>
                                        <th>Plant Name</th>
                                        <th>Location</th>
                                        <th>Collector</th>
                                        <th>Sample Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_list as $sample): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sample['sample_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($sample['plant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($sample['location_sampled']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($sample['collector_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sample['collector_email']); ?></small>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($sample['sample_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="approveSample(<?php echo $sample['id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectSample(<?php echo $sample['id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                                <a href="edit_sample.php?id=<?php echo $sample['id']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4>No Pending Samples</h4>
                        <p class="text-muted">All samples have been reviewed.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Deletion Requests Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trash me-2"></i>Deletion Requests (<?php echo count($deletion_requests); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($deletion_requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sample ID</th>
                                            <th>Plant Name</th>
                                            <th>Requested By</th>
                                            <th>Reason</th>
                                            <th>Requested At</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deletion_requests as $req): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($req['sample_id'] ?? 'Deleted'); ?></strong></td>
                                                <td><?php echo htmlspecialchars($req['plant_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($req['requester_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($req['requester_email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($req['reason'] ?? 'No reason provided'); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($req['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($req['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($req['status'] === 'pending'): ?>
                                                        <button class="btn btn-success btn-sm" onclick="approveDeletion(<?php echo $req['id']; ?>)">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" onclick="rejectDeletion(<?php echo $req['id']; ?>)">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    <?php else: ?>
                                                        <small class="text-muted"><?php echo ucfirst($req['status']); ?> by <?php echo htmlspecialchars($req['approver_name'] ?? 'System'); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-muted">No pending deletion requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveSample(sampleId) {
            if (confirm('Are you sure you want to approve this sample?')) {
                fetch('api/approve_sample.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: sampleId, action: 'approve' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sample approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to approve'));
                    }
                });
            }
        }

        function rejectSample(sampleId) {
            if (confirm('Are you sure you want to reject this sample? This action cannot be undone.')) {
                fetch('api/approve_sample.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: sampleId, action: 'reject' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sample rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to reject'));
                    }
                });
            }
        }

        function approveDeletion(requestId) {
            if (confirm('Are you sure you want to approve this deletion? The sample will be permanently deleted.')) {
                fetch('api/approve_deletion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: 'approve' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Deletion approved! Sample has been deleted.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to approve'));
                    }
                });
            }
        }

        function rejectDeletion(requestId) {
            if (confirm('Are you sure you want to reject this deletion request?')) {
                fetch('api/approve_deletion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: 'reject' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Deletion request rejected!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to reject'));
                    }
                });
            }
        }
    </script>
</body>
</html>
