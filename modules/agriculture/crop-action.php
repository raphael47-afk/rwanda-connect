<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Agriculture: Crop Action Handler
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rwanda_connect/modules/agriculture/');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirectWith('/rwanda_connect/modules/agriculture/', 'error', 'Invalid request.');
}

$cropId = (int)($_POST['crop_id'] ?? 0);
$action = $_POST['action'] ?? '';
$farmId = (int)($_POST['farm_id'] ?? 0);

if (!$cropId || !$action) {
    redirectWith('/rwanda_connect/modules/agriculture/', 'error', 'Invalid parameters.');
}

// Verify ownership
$stmt = $db->prepare('
    SELECT fc.*, f.user_id, f.name as farm_name, c.name as crop_name
    FROM farm_crops fc
    JOIN user_farms f ON fc.farm_id = f.id
    JOIN crops c ON fc.crop_id = c.id
    WHERE fc.id = ? AND f.user_id = ?
');
$stmt->execute([$cropId, $userId]);
$crop = $stmt->fetch();

if (!$crop) {
    redirectWith('/rwanda_connect/modules/agriculture/', 'error', 'Crop not found.');
}

try {
    $db->beginTransaction();
    
    switch ($action) {
        case 'harvest':
            $yield = !empty($_POST['yield_kg']) ? (float)$_POST['yield_kg'] : null;
            $harvestDate = $_POST['harvest_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $db->prepare('UPDATE farm_crops SET status = "harvested", actual_harvest_date = ?, yield_kg = ?, notes = CONCAT(IFNULL(notes, ""), IF(IFNULL(notes, "") = "", "", "\n"), "Harvested: ", ?), updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$harvestDate, $yield, $notes, $cropId]);
            
            logActivity('crop_harvested', "Harvested {$crop['crop_name']} on {$crop['farm_name']}", $userId);
            $message = 'Crop marked as harvested!';
            break;
            
        case 'update_status':
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['planted', 'growing', 'harvested', 'failed'];
            
            if (!in_array($newStatus, $validStatuses, true)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $db->prepare('UPDATE farm_crops SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$newStatus, $cropId]);
            
            $message = 'Crop status updated!';
            break;
            
        case 'add_notes':
            $notes = trim($_POST['notes'] ?? '');
            
            $stmt = $db->prepare('UPDATE farm_crops SET notes = CONCAT(IFNULL(notes, ""), IF(IFNULL(notes, "") = "", "", "\n"), ?, "\n"), updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$notes, $cropId]);
            
            $message = 'Notes added!';
            break;
            
        default:
            throw new Exception('Unknown action');
    }
    
    $db->commit();
    redirectWith("/rwanda_connect/modules/agriculture/farm-detail.php?id={$farmId}", 'success', $message);
    
} catch (Exception $e) {
    $db->rollBack();
    redirectWith("/rwanda_connect/modules/agriculture/farm-detail.php?id={$farmId}", 'error', 'Action failed: ' . $e->getMessage());
}