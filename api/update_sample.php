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
$sample_id = $data['sample_id'] ?? null;

if (!$sample_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sample ID is required']);
    exit;
}

try {
    // Find sample by sample_id
    $stmt = $pdo->prepare("SELECT id FROM plant_samples WHERE sample_id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch();

    if (!$sample) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
        exit;
    }

    // Build update query
    $updates = [];
    $params = [];
    
    if (isset($data['plant_name'])) {
        $updates[] = "plant_name = ?";
        $params[] = $data['plant_name'];
    }
    if (isset($data['species'])) {
        $updates[] = "species = ?";
        $params[] = $data['species'];
    }
    if (isset($data['location_sampled'])) {
        $updates[] = "location_sampled = ?";
        $params[] = $data['location_sampled'];
    }
    if (isset($data['sample_date'])) {
        $updates[] = "sample_date = ?";
        $params[] = $data['sample_date'];
    }
    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $data['notes'];
    }
    
    if (isset($data['plant_sample_detail'])) {
        $updates[] = "plant_sample_detail = ?";
        $params[] = is_array($data['plant_sample_detail']) ? json_encode($data['plant_sample_detail']) : $data['plant_sample_detail'];
    }
    if (isset($data['sampling_location'])) {
        $updates[] = "sampling_location = ?";
        $params[] = is_array($data['sampling_location']) ? json_encode($data['sampling_location']) : $data['sampling_location'];
    }
    if (isset($data['environmental_conditions'])) {
        $updates[] = "environmental_conditions = ?";
        $params[] = is_array($data['environmental_conditions']) ? json_encode($data['environmental_conditions']) : $data['environmental_conditions'];
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $params[] = $sample_id;
    $sql = "UPDATE plant_samples SET " . implode(", ", $updates) . " WHERE sample_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Sample updated successfully'
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
