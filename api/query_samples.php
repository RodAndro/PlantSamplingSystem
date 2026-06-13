<?php
/**
 * API endpoint to query samples with filters
 * Supports both GET and POST for flexibility
 */

require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - please login first'
    ]);
    exit;
}

try {
    $query = "
        SELECT 
            ps.*,
            u.name as collector_name,
            u.email as collector_email
        FROM plant_samples ps
        LEFT JOIN users u ON ps.collected_by = u.id
        WHERE 1=1
    ";
    $params = [];

    $filters = $_REQUEST;

    // Filter by status
    if (!empty($filters['status'])) {
        $query .= " AND ps.status = ?";
        $params[] = trim($filters['status']);
    }

    // Filter by sample ID
    if (!empty($filters['sample_id'])) {
        $query .= " AND ps.sample_id LIKE ?";
        $params[] = '%' . trim($filters['sample_id']) . '%';
    }

    // Filter by plant name
    if (!empty($filters['plant_name'])) {
        $query .= " AND ps.plant_name LIKE ?";
        $params[] = '%' . trim($filters['plant_name']) . '%';
    }

    // Filter by date range
    if (!empty($filters['date_from'])) {
        $query .= " AND ps.sample_date >= ?";
        $params[] = trim($filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
        $query .= " AND ps.sample_date <= ?";
        $params[] = trim($filters['date_to']);
    }

    // Filter by collector
    if (!empty($filters['collected_by'])) {
        $query .= " AND ps.collected_by = ?";
        $params[] = intval($filters['collected_by']);
    }

    $query .= " ORDER BY ps.sample_date DESC LIMIT 100";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($samples as &$sample) {
        $sample['plant_sample_detail'] = $sample['plant_sample_detail'] ? json_decode($sample['plant_sample_detail'], true) : null;
        $sample['sampling_location'] = $sample['sampling_location'] ? json_decode($sample['sampling_location'], true) : null;
        $sample['environmental_conditions'] = $sample['environmental_conditions'] ? json_decode($sample['environmental_conditions'], true) : null;
    }

    echo json_encode([
        'success' => true,
        'count' => count($samples),
        'data' => $samples
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
