<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Mark All Notifications as Read
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

if (!verifyCsrfToken($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$db = getDb();

$stmt = $db->prepare('UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = FALSE');
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'count' => $stmt->rowCount()]);