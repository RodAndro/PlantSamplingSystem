<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$sample_id = intval($data['id'] ?? $data['sample_id'] ?? 0);

if (!$sample_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sample ID is required']);
    exit;
}

try {
    // Fetch the sample to check ownership and status
    $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sample) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
        exit;
    }

    if ($_SESSION['user_role'] === 'admin') {
        // Admin can delete any sample directly
        $stmt = $pdo->prepare("DELETE FROM plant_samples WHERE id = ?");
        $stmt->execute([$sample_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sample deleted successfully by admin'
        ]);
    } else {
        // User can only request deletion if sample is pending or approved
        if ($sample['status'] !== 'pending' && $sample['status'] !== 'approved') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Can only request deletion for pending or approved samples'
            ]);
            exit;
        }

        // Check if user is the owner
        if ($sample['collected_by'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'You can only delete your own samples'
            ]);
            exit;
        }

        // User must request approval - insert into deletion_requests table
        $stmt = $pdo->prepare("
            INSERT INTO deletion_requests (sample_id, requested_by, reason, status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
            ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW()
        ");
        
        $reason = $data['reason'] ?? '';
        $stmt->execute([$sample_id, $_SESSION['user_id'], $reason]);

        echo json_encode([
            'success' => true,
            'message' => 'Deletion request submitted to admin for approval'
        ]);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
