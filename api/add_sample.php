<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['sample_id', 'plant_name', 'location_sampled', 'sample_date'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

try {
    // Check if sample_id already exists
    $stmt = $pdo->prepare("SELECT id FROM plant_samples WHERE sample_id = ?");
    $stmt->execute([$data['sample_id']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Sample ID already exists']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO plant_samples 
        (sample_id, plant_name, species, location_sampled, sample_date, notes, collected_by, status, plant_sample_detail, sampling_location, environmental_conditions)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
    ");

    $stmt->execute([
        $data['sample_id'],
        $data['plant_name'],
        $data['species'] ?? null,
        $data['location_sampled'],
        $data['sample_date'],
        $data['notes'] ?? null,
        $_SESSION['user_id'],
        isset($data['plant_sample_detail']) ? json_encode($data['plant_sample_detail']) : null,
        isset($data['sampling_location']) ? json_encode($data['sampling_location']) : null,
        isset($data['environmental_conditions']) ? json_encode($data['environmental_conditions']) : null
    ]);

    $sample_db_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Sample added successfully',
        'id' => $sample_db_id,
        'sample_id' => $data['sample_id']
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
