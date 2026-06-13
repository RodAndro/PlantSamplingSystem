<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$sample_id = intval($input['id']);
$action = trim($input['action']);

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE plant_samples 
            SET status = 'approved', approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $sample_id])) {
            // Fetch updated sample data
            $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
            $stmt->execute([$sample_id]);
            $updated_sample = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sample approved successfully',
                'data' => $updated_sample
            ]);
        } else {
            throw new Exception('Failed to approve sample');
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("
            UPDATE plant_samples 
            SET status = 'rejected', approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $sample_id])) {
            // Fetch updated sample data
            $stmt = $pdo->prepare("SELECT * FROM plant_samples WHERE id = ?");
            $stmt->execute([$sample_id]);
            $updated_sample = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sample rejected successfully',
                'data' => $updated_sample
            ]);
        } else {
            throw new Exception('Failed to reject sample');
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
