<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Agriculture: Farm Detail
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$farmId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDb();

if (!$farmId) {
    header('Location: /rwanda_connect/modules/agriculture/farms.php');
    exit;
}

// Get farm
$stmt = $db->prepare('SELECT * FROM user_farms WHERE id = ? AND user_id = ? AND is_active = TRUE');
$stmt->execute([$farmId, $userId]);
$farm = $stmt->fetch();

if (!$farm) {
    header('Location: /rwanda_connect/modules/agriculture/farms.php');
    exit;
}

// Get crops for this farm
$stmt = $db->prepare('
    SELECT fc.*, c.name as crop_name, c.category, c.growing_period_days
    FROM farm_crops fc
    JOIN crops c ON fc.crop_id = c.id
    WHERE fc.farm_id = ?
    ORDER BY fc.created_at DESC
');
$stmt->execute([$farmId]);
$crops = $stmt->fetchAll();

// Handle crop addition
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_crop'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $cropId = (int)($_POST['crop_id'] ?? 0);
        $variety = trim($_POST['variety'] ?? '');
        $area = (float)($_POST['area_hectares'] ?? 0);
        $plantingDate = $_POST['planting_date'] ?? null;
        $expectedHarvest = $_POST['expected_harvest_date'] ?? null;
        
        if (!$cropId) $errors['crop_id'] = 'Please select a crop';
        if ($area <= 0) $errors['area_hectares'] = 'Area must be greater than 0';
        if ($area > $farm['size_hectares']) $errors['area_hectares'] = 'Area cannot exceed farm size';
        
        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO farm_crops (farm_id, crop_id, variety, area_hectares, planting_date, expected_harvest_date, status) VALUES (?, ?, ?, ?, ?, ?, "planted")');
            $stmt->execute([$farmId, $cropId, $variety, $area, $plantingDate ?: null, $expectedHarvest ?: null]);
            logActivity('crop_planted', "Planted crop on farm: {$farm['name']}", $userId);
            $success = 'Crop added successfully!';
            header('Location: /rwanda_connect/modules/agriculture/farm-detail.php?id=' . $farmId);
            exit;
        }
    }
}

// Get available crops
$stmt = $db->query('SELECT * FROM crops WHERE is_active = TRUE ORDER BY name');
$availableCrops = $stmt->fetchAll();

$csrfToken = generateCsrfToken();

$pageTitle = e($farm['name']) . ' - Agriculture - Rwanda Connect';
$pageDescription = 'Manage crops and track progress for ' . e($farm['name']);

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page agriculture-page farm-detail-page">
    <div class="module-container">
        <!-- Header -->
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/agriculture/farms.php" class="back-link">← My Farms</a>
                <div>
                    <h1><?= e($farm['name']) ?></h1>
                    <p><?= number_format($farm['size_hectares'], 2) ?> ha • <?= e($farm['district'] . ', ' . $farm['province']) ?></p>
                </div>
            </div>
            <div class="module-actions">
                <button class="btn btn-primary" data-modal="add-crop">➕ Add Crop</button>
                <a href="/rwanda_connect/modules/agriculture/farms.php?action=edit&id=<?= $farmId ?>" class="btn btn-secondary">Edit Farm</a>
            </div>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Farm Info Cards -->
        <section class="farm-info-cards">
            <div class="info-card">
                <span class="info-icon">🌱</span>
                <div>
                    <span class="info-label">Soil Type</span>
                    <span class="info-value"><?= e($farm['soil_type'] ?? 'Not specified') ?></span>
                </div>
            </div>
            <div class="info-card">
                <span class="info-icon">💧</span>
                <div>
                    <span class="info-label">Irrigation</span>
                    <span class="info-value"><?= e(ucfirst(str_replace('_', ' ', $farm['irrigation_type'] ?? 'Rainfed'))) ?></span>
                </div>
            </div>
            <div class="info-card">
                <span class="info-icon">📍</span>
                <div>
                    <span class="info-label">Location</span>
                    <span class="info-value"><?= e($farm['district'] . ', ' . $farm['province']) ?></span>
                </div>
            </div>
            <?php if ($farm['gps_latitude'] && $farm['gps_longitude']): ?>
                <div class="info-card">
                    <span class="info-icon">📡</span>
                    <div>
                        <span class="info-label">GPS</span>
                        <span class="info-value"><?= $farm['gps_latitude'] ?>, <?= $farm['gps_longitude'] ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Crops Section -->
        <section class="module-section crops-section">
            <div class="section-header">
                <h2>Crops on this Farm</h2>
            </div>
            
            <?php if (empty($crops)): ?>
                <div class="empty-state-inline">
                    <span class="empty-icon">🌱</span>
                    <p>No crops planted yet</p>
                    <button class="btn btn-primary" data-modal="add-crop">Plant Your First Crop</button>
                </div>
            <?php else: ?>
                <div class="crops-table-wrapper">
                    <table class="crops-table">
                        <thead>
                            <tr>
                                <th>Crop</th>
                                <th>Variety</th>
                                <th>Area (ha)</th>
                                <th>Planted</th>
                                <th>Expected Harvest</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($crops as $crop): 
                                $progress = 0;
                                if ($crop['planting_date'] && $crop['expected_harvest_date']) {
                                    $start = new DateTime($crop['planting_date']);
                                    $end = new DateTime($crop['expected_harvest_date']);
                                    $now = new DateTime();
                                    if ($now >= $start && $now <= $end) {
                                        $total = $start->diff($end)->days;
                                        $elapsed = $start->diff($now)->days;
                                        $progress = min(100, max(0, round(($elapsed / $total) * 100)));
                                    } elseif ($now > $end) {
                                        $progress = 100;
                                    }
                                }
                                $statusClass = match($crop['status']) {
                                    'planted' => 'status-planted',
                                    'growing' => 'status-growing',
                                    'harvested' => 'status-harvested',
                                    'failed' => 'status-failed',
                                    default => ''
                                };
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($crop['crop_name']) ?></strong>
                                        <?php if ($crop['variety']): ?>
                                            <br><small><?= e($crop['variety']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($crop['variety'] ?? '-') ?></td>
                                    <td><?= number_format($crop['area_hectares'], 2) ?></td>
                                    <td><?= $crop['planting_date'] ? formatDate($crop['planting_date']) : '-' ?></td>
                                    <td><?= $crop['expected_harvest_date'] ? formatDate($crop['expected_harvest_date']) : '-' ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= e(ucfirst($crop['status'])) ?></span></td>
                                    <td>
                                        <div class="progress-bar-sm">
                                            <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <span><?= $progress ?>%</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($crop['status'] === 'growing'): ?>
                                                <form method="POST" action="/rwanda_connect/modules/agriculture/crop-action.php" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <input type="hidden" name="crop_id" value="<?= $crop['id'] ?>">
                                                    <input type="hidden" name="action" value="harvest">
                                                    <input type="hidden" name="farm_id" value="<?= $farmId ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Harvested">✂️ Harvest</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="/rwanda_connect/modules/agriculture/crop-detail.php?id=<?= $crop['id'] ?>" class="btn btn-sm btn-outline" title="View Details">👁</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Add Crop Modal -->
        <div class="modal" id="add-crop-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Add New Crop</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="" class="modal-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="add_crop" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="crop_id">Crop <span class="required" aria-hidden="true">*</span></label>
                            <select id="crop_id" name="crop_id" required class="<?= isset($errors['crop_id']) ? 'error' : '' ?>">
                                <option value="">Select Crop</option>
                                <?php foreach ($availableCrops as $crop): ?>
                                    <option value="<?= $crop['id'] ?>"><?= e($crop['name']) ?> (<?= e(ucfirst($crop['category'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['crop_id'])): ?>
                                <span class="error-message"><?= e($errors['crop_id']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="variety">Variety</label>
                            <input type="text" id="variety" name="variety" placeholder="e.g., Hybrid 501">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="area_hectares">Area (Hectares) <span class="required" aria-hidden="true">*</span></label>
                            <input type="number" id="area_hectares" name="area_hectares" 
                                   step="0.01" min="0.01" max="<?= $farm['size_hectares'] ?>" required
                                   class="<?= isset($errors['area_hectares']) ? 'error' : '' ?>">
                            <?php if (isset($errors['area_hectares'])): ?>
                                <span class="error-message"><?= e($errors['area_hectares']) ?></span>
                            <?php endif; ?>
                            <span class="form-hint">Max: <?= number_format($farm['size_hectares'], 2) ?> ha</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="planting_date">Planting Date</label>
                            <input type="date" id="planting_date" name="planting_date" max="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="expected_harvest_date">Expected Harvest Date</label>
                            <input type="date" id="expected_harvest_date" name="expected_harvest_date" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Plant Crop</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal handling
    const modal = document.getElementById('add-crop-modal');
    const openBtns = document.querySelectorAll('[data-modal="add-crop"]');
    const closeBtns = document.querySelectorAll('[data-close-modal]');
    
    function openModal() {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('crop_id').focus();
    }
    
    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
    
    openBtns.forEach(btn => btn.addEventListener('click', openModal));
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
    
    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
    
    // Auto-calculate harvest date based on crop selection
    document.getElementById('crop_id')?.addEventListener('change', (e) => {
        const cropId = e.target.value;
        const plantingDate = document.getElementById('planting_date').value || new Date().toISOString().split('T')[0];
        
        if (cropId) {
            // Find crop growing period
            const crops = <?= json_encode($availableCrops) ?>;
            const crop = crops.find(c => c.id == cropId);
            if (crop && crop.growing_period_days) {
                const harvest = new Date(plantingDate);
                harvest.setDate(harvest.getDate() + parseInt(crop.growing_period_days));
                document.getElementById('expected_harvest_date').value = harvest.toISOString().split('T')[0];
            }
        }
    });
    
    // Update harvest when planting date changes
    document.getElementById('planting_date')?.addEventListener('change', (e) => {
        const cropId = document.getElementById('crop_id').value;
        if (cropId) {
            const crops = <?= json_encode($availableCrops) ?>;
            const crop = crops.find(c => c.id == cropId);
            if (crop && crop.growing_period_days) {
                const harvest = new Date(e.target.value);
                harvest.setDate(harvest.getDate() + parseInt(crop.growing_period_days));
                document.getElementById('expected_harvest_date').value = harvest.toISOString().split('T')[0];
            }
        }
    });
});
</script>