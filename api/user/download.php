<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Download User Export
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    exit('Invalid token');
}

$db = getDb();

$stmt = $db->prepare('SELECT * FROM user_exports WHERE token = ? AND user_id = ? AND expires_at > NOW()');
$stmt->execute([$token, $userId]);
$export = $stmt->fetch();

if (!$export || !$export['file_path'] || !file_exists($export['file_path'])) {
    http_response_code(404);
    exit('Export not found or expired');
}

$filename = basename($export['file_path']);
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($export['file_path']));
readfile($export['file_path']);

// Clean up - delete after download (optional)
// unlink($export['file_path']);
// $stmt = $db->prepare('DELETE FROM user_exports WHERE id = ?');
// $stmt->execute([$export['id']]);