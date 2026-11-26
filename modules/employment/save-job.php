<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Save/Unsave Job Handler
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rwanda_connect/modules/employment/jobs.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirectWith('/rwanda_connect/modules/employment/jobs.php', 'error', 'Invalid request.');
}

$jobId = (int)($_POST['job_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$jobId || !in_array($action, ['add', 'remove'], true)) {
    redirectWith('/rwanda_connect/modules/employment/jobs.php', 'error', 'Invalid parameters.');
}

// Verify job exists and is published
$stmt = $db->prepare('SELECT id, title FROM job_postings WHERE id = ? AND status = "published"');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    redirectWith('/rwanda_connect/modules/employment/jobs.php', 'error', 'Job not found.');
}

try {
    if ($action === 'add') {
        $stmt = $db->prepare('INSERT IGNORE INTO user_saved_jobs (user_id, job_id) VALUES (?, ?)');
        $stmt->execute([$userId, $jobId]);
        logActivity('job_saved', "Saved job: {$job['title']}", $userId);
        $message = 'Job saved successfully!';
    } else {
        $stmt = $db->prepare('DELETE FROM user_saved_jobs WHERE user_id = ? AND job_id = ?');
        $stmt->execute([$userId, $jobId]);
        $message = 'Job removed from saved.';
    }
    
    // Redirect back to referer
    $referer = $_SERVER['HTTP_REFERER'] ?? '/rwanda_connect/modules/employment/jobs.php';
    redirectWith($referer, 'success', $message);
    
} catch (Exception $e) {
    redirectWith('/rwanda_connect/modules/employment/jobs.php', 'error', 'Action failed.');
}