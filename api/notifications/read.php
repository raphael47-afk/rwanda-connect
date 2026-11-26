<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Mark Notification as Read API
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireLogin();

$userId = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$notificationId = $input['notification_id'] ?? 0;

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit;
}

$db = getDb();

$stmt = $db->prepare('UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
$stmt->execute([$notificationId, $userId]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Notification not found']);
}