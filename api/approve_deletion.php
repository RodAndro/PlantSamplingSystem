<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only admins can approve deletions']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$request_id = intval($data['request_id'] ?? 0);
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Get the deletion request
    $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Deletion request not found']);
        exit;
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare("DELETE FROM plant_samples WHERE id = ?");
        $stmt->execute([$request['sample_id']]);

        // Update deletion request status
        $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Deletion approved and sample deleted'
        ]);
    } else {
        // Reject the deletion request
        $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Deletion request rejected'
        ]);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
