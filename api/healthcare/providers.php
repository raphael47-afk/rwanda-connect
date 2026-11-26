<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Healthcare Providers API
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
    SELECT hp.id, u.full_name as name, hp.specialization, hp.consultation_fee_rwf, hp.languages, hp.rating
    FROM healthcare_providers hp
    JOIN users u ON hp.user_id = u.id
    WHERE hp.facility_id = ? AND hp.is_available = TRUE AND u.status = "active"
    ORDER BY hp.rating DESC, u.full_name
');
$stmt->execute([$facilityId]);
$providers = $stmt->fetchAll();

echo json_encode($providers);