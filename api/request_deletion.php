<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$sample_id = intval($data['sample_id'] ?? 0);
$reason = trim($data['reason'] ?? '');

if (!$sample_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid sample ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sample) {
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
        exit;
    }

    if ($sample['collected_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You can only request deletion of your own samples']);
        exit;
    }

    if ($sample['status'] !== 'pending' && $sample['status'] !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'You can only request deletion for pending or approved samples']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM deletion_requests 
        WHERE sample_id = ? AND status = 'pending'
    ");
    $stmt->execute([$sample_id]);
    $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_request) {
        echo json_encode(['success' => false, 'message' => 'A deletion request is already pending for this sample']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO deletion_requests (sample_id, requested_by, reason, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");

    if ($stmt->execute([$sample_id, $_SESSION['user_id'], $reason])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Deletion request submitted successfully',
            'status' => 'Pending Admin Approval'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create deletion request']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
