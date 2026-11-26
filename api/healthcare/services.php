<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare Services API
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireLogin();

$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

if (!$facilityId) {
    echo json_encode([]);
    exit;
}

$db = getDb();

$stmt = $db->prepare('
    SELECT id, name, category, cost_rwf, insurance_accepted, requires_appointment
    FROM health_services
    WHERE facility_id = ? AND is_active = TRUE
    ORDER BY name
');
$stmt->execute([$facilityId]);
$services = $stmt->fetchAll();

echo json_encode($services);