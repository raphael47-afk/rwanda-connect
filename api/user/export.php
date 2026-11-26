<?php
declare(strict_types=1);

/**
 * Rwanda Connect - User Data Export API
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
$db = getDb();

// Generate export token
$token = bin2hex(random_bytes(32));
$expires = new DateTime('+1 hour');

$stmt = $db->prepare('INSERT INTO user_exports (user_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$userId, $token, $expires->format('Y-m-d H:i:s')]);

$exportId = (int)$db->lastInsertId();

// In a real implementation, this would trigger a background job
// For now, we'll generate the export immediately
try {
    $exportData = generateUserExport($userId, $db);
    $filename = "rwanda_connect_export_{$userId}_" . date('Ymd_His') . ".json";
    $filepath = __DIR__ . '/../../../exports/' . $filename;
    
    if (!is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));
    
    // Update export record
    $stmt = $db->prepare('UPDATE user_exports SET file_path = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$filepath, $exportId]);
    
    echo json_encode([
        'success' => true,
        'download_url' => "/rwanda_connect/api/user/download.php?token={$token}"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
}

function generateUserExport(int $userId, PDO $db): array {
    $data = [];
    
    // User profile
    $stmt = $db->prepare('SELECT u.*, up.* FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $data['profile'] = $stmt->fetch() ?: [];
    unset($data['profile']['password_hash']);
    
    // Farms
    $stmt = $db->prepare('SELECT * FROM user_farms WHERE user_id = ?');
    $stmt->execute([$userId]);
    $data['farms'] = $stmt->fetchAll();
    
    // Farm crops
    $stmt = $db->prepare('SELECT fc.*, c.name as crop_name FROM farm_crops fc JOIN crops c ON fc.crop_id = c.id JOIN user_farms f ON fc.farm_id = f.id WHERE f.user_id = ?');
    $stmt->execute([$userId]);
    $data['crops'] = $stmt->fetchAll();
    
    // Job applications
    $stmt = $db->prepare('SELECT ja.*, jp.title, e.company_name FROM job_applications ja JOIN job_postings jp ON ja.job_id = jp.id JOIN employers e ON jp.employer_id = e.id WHERE ja.user_id = ?');
    $stmt->execute([$userId]);
    $data['job_applications'] = $stmt->fetchAll();
    
    // Saved jobs
    $stmt = $db->prepare('SELECT usj.*, jp.title, e.company_name FROM user_saved_jobs usj JOIN job_postings jp ON usj.job_id = jp.id JOIN employers e ON jp.employer_id = e.id WHERE usj.user_id = ?');
    $stmt->execute([$userId]);
    $data['saved_jobs'] = $stmt->fetchAll();
    
    // Job alerts
    $stmt = $db->prepare('SELECT * FROM job_alerts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $data['job_alerts'] = $stmt->fetchAll();
    
    // Education
    $stmt = $db->prepare('SELECT ue.*, i.name as institution_name FROM user_education ue LEFT JOIN institutions i ON ue.institution_id = i.id WHERE ue.user_id = ?');
    $stmt->execute([$userId]);
    $data['education'] = $stmt->fetchAll();
    
    // Scholarship applications
    $stmt = $db->prepare('SELECT sa.*, s.title, s.provider FROM scholarship_applications sa JOIN scholarships s ON sa.scholarship_id = s.id WHERE sa.user_id = ?');
    $stmt->execute([$userId]);
    $data['scholarship_applications'] = $stmt->fetchAll();
    
    // Appointments
    $stmt = $db->prepare('SELECT a.*, hf.name as facility_name FROM appointments a JOIN health_facilities hf ON a.facility_id = hf.id WHERE a.user_id = ?');
    $stmt->execute([$userId]);
    $data['appointments'] = $stmt->fetchAll();
    
    // Health records
    $stmt = $db->prepare('SELECT * FROM health_records WHERE user_id = ?');
    $stmt->execute([$userId]);
    $data['health_records'] = $stmt->fetchAll();
    
    // Activity log
    $stmt = $db->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 1000');
    $stmt->execute([$userId]);
    $data['activity_log'] = $stmt->fetchAll();
    
    // Notifications
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 500');
    $stmt->execute([$userId]);
    $data['notifications'] = $stmt->fetchAll();
    
    $data['exported_at'] = date('c');
    $data['export_version'] = '1.0';
    
    return $data;
}