<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Agriculture: My Farms
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$farmId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle farm creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'edit')) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'size_hectares' => (float)($_POST['size_hectares'] ?? 0),
            'province' => $_POST['province'] ?? '',
            'district' => $_POST['district'] ?? '',
            'sector' => trim($_POST['sector'] ?? ''),
            'cell' => trim($_POST['cell'] ?? ''),
            'village' => trim($_POST['village'] ?? ''),
            'soil_type' => trim($_POST['soil_type'] ?? ''),
            'irrigation_type' => $_POST['irrigation_type'] ?? 'rainfed',
            'gps_latitude' => !empty($_POST['gps_latitude']) ? (float)$_POST['gps_latitude'] : null,
            'gps_longitude' => !empty($_POST['gps_longitude']) ? (float)$_POST['gps_longitude'] : null,
        ];
        
        // Validate
        if (empty($data['name'])) $errors['name'] = 'Farm name is required';
        if ($data['size_hectares'] <= 0) $errors['size_hectares'] = 'Size must be greater than 0';
        if (empty($data['province'])) $errors['province'] = 'Province is required';
        if (empty($data['district'])) $errors['district'] = 'District is required';
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                if ($action === 'edit' && $farmId) {
                    // Verify ownership
                    $stmt = $db->prepare('SELECT id FROM user_farms WHERE id = ? AND user_id = ?');
                    $stmt->execute([$farmId, $userId]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Farm not found');
                    }
                    
                    $stmt = $db->prepare('UPDATE user_farms SET name = ?, size_hectares = ?, province = ?, district = ?, sector = ?, cell = ?, village = ?, soil_type = ?, irrigation_type = ?, gps_latitude = ?, gps_longitude = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                    $stmt->execute([...array_values($data), $farmId]);
                    $success = 'Farm updated successfully!';
                } else {
                    $stmt = $db->prepare('INSERT INTO user_farms (user_id, name, size_hectares, province, district, sector, cell, village, soil_type, irrigation_type, gps_latitude, gps_longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, ...array_values($data)]);
                    $success = 'Farm created successfully!';
                }
                
                $db->commit();
                
                if ($action === 'create') {
                    logActivity('farm_created', "Created farm: {$data['name']}", $userId);
                }
                
                // Redirect to list
                header('Location: /rwanda_connect/modules/agriculture/farms.php');
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                $errors['general'] = 'Failed to save farm: ' . $e->getMessage();
            }
        }
    }
}

// Handle farm deletion
if ($action === 'delete' && $farmId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $stmt = $db->prepare('SELECT name FROM user_farms WHERE id = ? AND user_id = ?');
        $stmt->execute([$farmId, $userId]);
        $farm = $stmt->fetch();
        
        if ($farm) {
            $stmt = $db->prepare('UPDATE user_farms SET is_active = FALSE WHERE id = ?');
            $stmt->execute([$farmId]);
            logActivity('farm_deleted', "Deleted farm: {$farm['name']}", $userId);
            $success = 'Farm deleted successfully!';
        } else {
            $errors['general'] = 'Farm not found.';
        }
    }
}

// Get farm for editing
$editFarm = null;
if ($action === 'edit' && $farmId) {
    $stmt = $db->prepare('SELECT * FROM user_farms WHERE id = ? AND user_id = ? AND is_active = TRUE');
    $stmt->execute([$farmId, $userId]);
    $editFarm = $stmt->fetch();
    if (!$editFarm) {
        $action = 'list';
    }
}

// Get farms list
$stmt = $db->prepare('SELECT * FROM user_farms WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC');
$stmt->execute([$userId]);
$farms = $stmt->fetchAll();

$provinces = getProvinces();
$districts = $editFarm && $editFarm['province'] ? getDistricts($editFarm['province']) : [];
$csrfToken = generateCsrfToken();

$pageTitle = 'My Farms - Agriculture - Rwanda Connect';
$pageDescription = 'Manage your farms, track crops, and monitor farm performance.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page agriculture-page farms-page">
    <div class="module-container">
        <!-- Module Header -->
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/agriculture/" class="back-link">← Agriculture</a>
                <div>
                    <h1>My Farms</h1>
                    <p>Manage your farm portfolio and track performance</p>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=create" class="btn btn-primary">➕ Add Farm</a>
            <?php endif; ?>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Farm Form -->
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <section class="module-section form-section">
                <h2><?= $action === 'edit' ? 'Edit Farm' : 'Add New Farm' ?></h2>
                
                <form method="POST" action="" class="farm-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Farm Name <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" 
                                   value="<?= e($editFarm['name'] ?? '') ?>" required
                                   placeholder="e.g., Kigali Valley Farm"
                                   class="<?= isset($errors['name']) ? 'error' : '' ?>">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?= e($errors['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="size_hectares">Size (Hectares) <span class="required" aria-hidden="true">*</span></label>
                            <input type="number" id="size_hectares" name="size_hectares" 
                                   value="<?= e($editFarm['size_hectares'] ?? '') ?>" 
                                   required step="0.01" min="0.01"
                                   class="<?= isset($errors['size_hectares']) ? 'error' : '' ?>">
                            <?php if (isset($errors['size_hectares'])): ?>
                                <span class="error-message"><?= e($errors['size_hectares']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="province">Province <span class="required" aria-hidden="true">*</span></label>
                            <select id="province" name="province" required onchange="updateDistricts(this.value)"
                                    class="<?= isset($errors['province']) ? 'error' : '' ?>">
                                <option value="">Select Province</option>
                                <?php foreach ($provinces as $key => $name): ?>
                                    <option value="<?= e($key) ?>" <?= ($editFarm['province'] ?? '') === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['province'])): ?>
                                <span class="error-message"><?= e($errors['province']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="district">District <span class="required" aria-hidden="true">*</span></label>
                            <select id="district" name="district" required
                                    class="<?= isset($errors['district']) ? 'error' : '' ?>">
                                <option value="">Select District</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= e($district) ?>" <?= ($editFarm['district'] ?? '') === $district ? 'selected' : '' ?>><?= e($district) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['district'])): ?>
                                <span class="error-message"><?= e($errors['district']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sector">Sector</label>
                            <input type="text" id="sector" name="sector" value="<?= e($editFarm['sector'] ?? '') ?>" placeholder="Sector">
                        </div>
                        <div class="form-group">
                            <label for="cell">Cell</label>
                            <input type="text" id="cell" name="cell" value="<?= e($editFarm['cell'] ?? '') ?>" placeholder="Cell">
                        </div>
                        <div class="form-group">
                            <label for="village">Village</label>
                            <input type="text" id="village" name="village" value="<?= e($editFarm['village'] ?? '') ?>" placeholder="Village">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="soil_type">Soil Type</label>
                            <select id="soil_type" name="soil_type">
                                <option value="">Select</option>
                                <option value="clay" <?= ($editFarm['soil_type'] ?? '') === 'clay' ? 'selected' : '' ?>>Clay</option>
                                <option value="loam" <?= ($editFarm['soil_type'] ?? '') === 'loam' ? 'selected' : '' ?>>Loam</option>
                                <option value="sandy" <?= ($editFarm['soil_type'] ?? '') === 'sandy' ? 'selected' : '' ?>>Sandy</option>
                                <option value="silty" <?= ($editFarm['soil_type'] ?? '') === 'silty' ? 'selected' : '' ?>>Silty</option>
                                <option value="peaty" <?= ($editFarm['soil_type'] ?? '') === 'peaty' ? 'selected' : '' ?>>Peaty</option>
                                <option value="chalky" <?= ($editFarm['soil_type'] ?? '') === 'chalky' ? 'selected' : '' ?>>Chalky</option>
                                <option value="other" <?= ($editFarm['soil_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="irrigation_type">Irrigation Type</label>
                            <select id="irrigation_type" name="irrigation_type">
                                <option value="rainfed" <?= ($editFarm['irrigation_type'] ?? '') === 'rainfed' ? 'selected' : '' ?>>Rainfed</option>
                                <option value="drip" <?= ($editFarm['irrigation_type'] ?? '') === 'drip' ? 'selected' : '' ?>>Drip Irrigation</option>
                                <option value="sprinkler" <?= ($editFarm['irrigation_type'] ?? '') === 'sprinkler' ? 'selected' : '' ?>>Sprinkler</option>
                                <option value="flood" <?= ($editFarm['irrigation_type'] ?? '') === 'flood' ? 'selected' : '' ?>>Flood Irrigation</option>
                                <option value="other" <?= ($editFarm['irrigation_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gps_latitude">GPS Latitude</label>
                            <input type="number" id="gps_latitude" name="gps_latitude" 
                                   value="<?= e($editFarm['gps_latitude'] ?? '') ?>" 
                                   step="any" placeholder="-1.9441" min="-90" max="90">
                            <span class="form-hint">Decimal degrees (e.g., -1.9441)</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="gps_longitude">GPS Longitude</label>
                            <input type="number" id="gps_longitude" name="gps_longitude" 
                                   value="<?= e($editFarm['gps_longitude'] ?? '') ?>" 
                                   step="any" placeholder="30.0619" min="-180" max="180">
                            <span class="form-hint">Decimal degrees (e.g., 30.0619)</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/rwanda_connect/modules/agriculture/farms.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Farm</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <!-- Farms List -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Your Farms</h2>
                    <a href="?action=create" class="btn btn-sm btn-primary">Add Farm</a>
                </div>
                
                <?php if (empty($farms)): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon">🏞️</div>
                        <h2>No farms yet</h2>
                        <p>Start by adding your first farm to track crops, yields, and manage your agricultural activities.</p>
                        <a href="?action=create" class="btn btn-primary">Add Your First Farm</a>
                    </div>
                <?php else: ?>
                    <div class="farms-grid">
                        <?php foreach ($farms as $farm): ?>
                            <article class="farm-card">
                                <div class="farm-header">
                                    <h3><?= e($farm['name']) ?></h3>
                                    <span class="farm-size"><?= number_format($farm['size_hectares'], 2) ?> ha</span>
                                </div>
                                <div class="farm-location">📍 <?= e($farm['district'] . ', ' . $farm['province']) ?></div>
                                <?php if ($farm['sector']): ?>
                                    <div class="farm-location">📍 <?= e($farm['sector']) ?><?= $farm['cell'] ? ', ' . $farm['cell'] : '' ?><?= $farm['village'] ? ', ' . $farm['village'] : '' ?></div>
                                <?php endif; ?>
                                <div class="farm-details">
                                    <span>🌱 Soil: <?= e($farm['soil_type'] ?? 'Not specified') ?></span>
                                    <span>💧 Irrigation: <?= e(ucfirst(str_replace('_', ' ', $farm['irrigation_type'] ?? 'Rainfed'))) ?></span>
                                </div>
                                <div class="farm-actions">
                                    <a href="/rwanda_connect/modules/agriculture/farm-detail.php?id=<?= $farm['id'] ?>" class="btn btn-sm btn-primary">Manage</a>
                                    <a href="?action=edit&id=<?= $farm['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" action="?action=delete&id=<?= $farm['id'] ?>" class="delete-form" onsubmit="return confirm('Delete this farm? This cannot be undone.')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
const districtsByProvince = {
    'Kigali City': ['Gasabo', 'Kicukiro', 'Nyarugenge'],
    'Northern Province': ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
    'Southern Province': ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
    'Eastern Province': ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
    'Western Province': ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro']
};

function updateDistricts(province) {
    const districtSelect = document.getElementById('district');
    const currentValue = districtSelect.value;
    
    districtSelect.innerHTML = '<option value="">Select District</option>';
    
    if (province && districtsByProvince[province]) {
        districtsByProvince[province].forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (district === currentValue) option.selected = true;
            districtSelect.appendChild(option);
        });
    }
}

// Initialize districts on page load
document.addEventListener('DOMContentLoaded', () => {
    const provinceSelect = document.getElementById('province');
    if (provinceSelect && provinceSelect.value) {
        updateDistricts(provinceSelect.value);
    }
});
</script>