<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Withdraw Application
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rwanda_connect/modules/employment/applications.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'error', 'Invalid request.');
}

$applicationId = (int)($_POST['application_id'] ?? 0);

if (!$applicationId) {
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'error', 'Invalid application.');
}

// Verify ownership and get job info
$stmt = $db->prepare('
    SELECT ja.*, jp.title 
    FROM job_applications ja
    JOIN job_postings jp ON ja.job_id = jp.id
    WHERE ja.id = ? AND ja.user_id = ?
');
$stmt->execute([$applicationId, $userId]);
$application = $stmt->fetch();

if (!$application) {
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'error', 'Application not found.');
}

if (!in_array($application['status'], ['pending', 'reviewed'], true)) {
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'error', 'Cannot withdraw application at this stage.');
}

try {
    $stmt = $db->prepare('UPDATE job_applications SET status = "withdrawn", updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$applicationId]);
    
    // Decrement applications count
    $stmt = $db->prepare('UPDATE job_postings SET applications_count = applications_count - 1 WHERE id = ? AND applications_count > 0');
    $stmt->execute([$application['job_id']]);
    
    logActivity('job_application_withdrawn', "Withdrew application for {$application['title']}", $userId);
    
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'success', 'Application withdrawn successfully.');
    
} catch (Exception $e) {
    redirectWith('/rwanda_connect/modules/employment/applications.php', 'error', 'Failed to withdraw application.');
}