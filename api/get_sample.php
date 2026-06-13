<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sample_id = $_GET['sample_id'] ?? null;

if (!$sample_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sample ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            ps.*,
            u.name as collector_name,
            u.email as collector_email
        FROM plant_samples ps
        LEFT JOIN users u ON ps.collected_by = u.id
        WHERE ps.sample_id = ?
    ");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sample) {
        $sample['plant_sample_detail'] = $sample['plant_sample_detail'] ? json_decode($sample['plant_sample_detail'], true) : null;
        $sample['sampling_location'] = $sample['sampling_location'] ? json_decode($sample['sampling_location'], true) : null;
        $sample['environmental_conditions'] = $sample['environmental_conditions'] ? json_decode($sample['environmental_conditions'], true) : null;
        
        echo json_encode([
            'success' => true,
            'data' => $sample
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
