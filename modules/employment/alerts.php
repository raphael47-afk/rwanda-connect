<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Job Alerts
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$alertId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'edit')) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'category_id' => $_POST['category_id'] ? (int)$_POST['category_id'] : null,
            'province' => $_POST['province'] ?? '',
            'employment_type' => $_POST['employment_type'] ?? 'any',
            'min_salary' => $_POST['min_salary'] ? (float)$_POST['min_salary'] : null,
            'frequency' => $_POST['frequency'] ?? 'daily',
        ];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Alert name is required';
        }
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                if ($action === 'edit' && $alertId) {
                    $stmt = $db->prepare('SELECT id FROM job_alerts WHERE id = ? AND user_id = ?');
                    $stmt->execute([$alertId, $userId]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Alert not found');
                    }
                    
                    $stmt = $db->prepare('UPDATE job_alerts SET name = ?, keywords = ?, category_id = ?, province = ?, employment_type = ?, min_salary = ?, frequency = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                    $stmt->execute([$data['name'], $data['keywords'] ?: null, $data['category_id'], $data['province'] ?: null, $data['employment_type'], $data['min_salary'], $data['frequency'], $alertId]);
                    $success = 'Alert updated successfully!';
                } else {
                    $stmt = $db->prepare('INSERT INTO job_alerts (user_id, name, keywords, category_id, province, employment_type, min_salary, frequency) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $data['name'], $data['keywords'] ?: null, $data['category_id'], $data['province'] ?: null, $data['employment_type'], $data['min_salary'], $data['frequency']]);
                    $success = 'Alert created successfully!';
                }
                
                $db->commit();
                header('Location: /rwanda_connect/modules/employment/alerts.php');
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                $errors['general'] = 'Failed to save alert: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $alertId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $stmt = $db->prepare('SELECT name FROM job_alerts WHERE id = ? AND user_id = ?');
        $stmt->execute([$alertId, $userId]);
        $alert = $stmt->fetch();
        
        if ($alert) {
            $stmt = $db->prepare('DELETE FROM job_alerts WHERE id = ?');
            $stmt->execute([$alertId]);
            $success = 'Alert deleted successfully!';
        } else {
            $errors['general'] = 'Alert not found.';
        }
    }
}

// Handle toggle
if ($action === 'toggle' && $alertId) {
    $stmt = $db->prepare('UPDATE job_alerts SET is_active = NOT is_active WHERE id = ? AND user_id = ?');
    $stmt->execute([$alertId, $userId]);
    header('Location: /rwanda_connect/modules/employment/alerts.php');
    exit;
}

// Get alert for editing
$editAlert = null;
if ($action === 'edit' && $alertId) {
    $stmt = $db->prepare('SELECT * FROM job_alerts WHERE id = ? AND user_id = ?');
    $stmt->execute([$alertId, $userId]);
    $editAlert = $stmt->fetch();
    if (!$editAlert) {
        $action = 'list';
    }
}

// Get all alerts
$stmt = $db->prepare('SELECT * FROM job_alerts WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$alerts = $stmt->fetchAll();

// Get categories
$stmt = $db->query('SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name');
$categories = $stmt->fetchAll();

$provinces = getProvinces();
$csrfToken = generateCsrfToken();

$pageTitle = 'Job Alerts - Employment - Rwanda Connect';
$pageDescription = 'Set up email notifications for new job opportunities.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page alerts-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/employment/" class="back-link">← Employment</a>
                <div>
                    <h1>Job Alerts</h1>
                    <p>Get notified when new jobs match your criteria</p>
                </div>
            </div>
            <?php if ($action === 'list'): ?>
                <a href="?action=create" class="btn btn-primary">➕ Create Alert</a>
            <?php endif; ?>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Alert Form -->
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <section class="module-section form-section">
                <h2><?= $action === 'edit' ? 'Edit Alert' : 'Create New Alert' ?></h2>
                
                <form method="POST" action="" class="alert-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Alert Name <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" 
                                   value="<?= e($editAlert['name'] ?? '') ?>" required
                                   placeholder="e.g., IT Jobs in Kigali"
                                   class="<?= isset($errors['name']) ? 'error' : '' ?>">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?= e($errors['name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="keywords">Keywords</label>
                            <input type="text" id="keywords" name="keywords" 
                                   value="<?= e($editAlert['keywords'] ?? '') ?>" 
                                   placeholder="e.g., software, developer, python">
                            <span class="form-hint">Comma-separated keywords to match in job titles/descriptions</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">Any Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($editAlert['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['icon'] ?? '💼') . ' ' . e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="province">Province</label>
                            <select id="province" name="province">
                                <option value="">Any Province</option>
                                <?php foreach ($provinces as $key => $name): ?>
                                    <option value="<?= e($key) ?>" <?= ($editAlert['province'] ?? '') === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employment_type">Employment Type</label>
                            <select id="employment_type" name="employment_type">
                                <option value="any" <?= ($editAlert['employment_type'] ?? '') === 'any' ? 'selected' : '' ?>>Any Type</option>
                                <option value="full_time" <?= ($editAlert['employment_type'] ?? '') === 'full_time' ? 'selected' : '' ?>>Full-time</option>
                                <option value="part_time" <?= ($editAlert['employment_type'] ?? '') === 'part_time' ? 'selected' : '' ?>>Part-time</option>
                                <option value="contract" <?= ($editAlert['employment_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
                                <option value="internship" <?= ($editAlert['employment_type'] ?? '') === 'internship' ? 'selected' : '' ?>>Internship</option>
                                <option value="volunteer" <?= ($editAlert['employment_type'] ?? '') === 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                                <option value="remote" <?= ($editAlert['employment_type'] ?? '') === 'remote' ? 'selected' : '' ?>>Remote</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="min_salary">Min Salary (RWF/month)</label>
                            <input type="number" id="min_salary" name="min_salary" 
                                   value="<?= e($editAlert['min_salary'] ?? '') ?>" 
                                   min="0" step="10000" placeholder="e.g., 500000">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="frequency">Notification Frequency</label>
                            <select id="frequency" name="frequency">
                                <option value="instant" <?= ($editAlert['frequency'] ?? '') === 'instant' ? 'selected' : '' ?>>Instant (as soon as posted)</option>
                                <option value="daily" <?= ($editAlert['frequency'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily Digest</option>
                                <option value="weekly" <?= ($editAlert['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly Digest</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="/rwanda_connect/modules/employment/alerts.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Alert</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <!-- Alerts List -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Your Alerts</h2>
                    <a href="?action=create" class="btn btn-sm btn-primary">Create Alert</a>
                </div>
                
                <?php if (empty($alerts)): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon">🔔</div>
                        <h2>No job alerts yet</h2>
                        <p>Create alerts to get notified when new jobs match your preferences.</p>
                        <a href="?action=create" class="btn btn-primary">Create Your First Alert</a>
                    </div>
                <?php else: ?>
                    <div class="alerts-list">
                        <?php foreach ($alerts as $alert): ?>
                            <article class="alert-card <?= !$alert['is_active'] ? 'inactive' : '' ?>">
                                <div class="alert-header">
                                    <div>
                                        <h3><?= e($alert['name']) ?></h3>
                                        <div class="alert-status">
                                            <span class="status-indicator <?= $alert['is_active'] ? 'active' : 'inactive' ?>"></span>
                                            <span><?= $alert['is_active'] ? 'Active' : 'Paused' ?></span>
                                        </div>
                                    </div>
                                    <div class="alert-toggle">
                                        <a href="?action=toggle&id=<?= $alert['id'] ?>" class="btn btn-sm <?= $alert['is_active'] ? 'btn-outline' : 'btn-primary' ?>">
                                            <?= $alert['is_active'] ? 'Pause' : 'Activate' ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="alert-criteria">
                                    <?php if ($alert['keywords']): ?>
                                        <span class="criteria-item">🔍 Keywords: <?= e($alert['keywords']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($alert['category_id']): ?>
                                        <?php $cat = array_filter($categories, fn($c) => $c['id'] == $alert['category_id']); $cat = array_values($cat)[0] ?? null; ?>
                                        <?php if ($cat): ?>
                                            <span class="criteria-item"><?= e($cat['icon'] ?? '💼') ?> Category: <?= e($cat['name']) ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($alert['province']): ?>
                                        <span class="criteria-item">📍 Location: <?= e($provinces[$alert['province']] ?? $alert['province']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($alert['employment_type'] !== 'any'): ?>
                                        <span class="criteria-item">📋 Type: <?= e(ucfirst(str_replace('_', ' ', $alert['employment_type']))) ?></span>
                                    <?php endif; ?>
                                    <?php if ($alert['min_salary']): ?>
                                        <span class="criteria-item">💰 Min Salary: <?= formatCurrency($alert['min_salary']) ?>/month</span>
                                    <?php endif; ?>
                                    <span class="criteria-item">📧 Frequency: <?= e(ucfirst($alert['frequency'])) ?></span>
                                </div>
                                
                                <div class="alert-meta">
                                    <span>Created: <?= formatDate($alert['created_at']) ?></span>
                                    <?php if ($alert['last_sent_at']): ?>
                                        <span>Last sent: <?= formatDateTime($alert['last_sent_at']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="alert-actions">
                                    <a href="?action=edit&id=<?= $alert['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" action="?action=delete&id=<?= $alert['id'] ?>" style="display: inline;" onsubmit="return confirm('Delete this alert?')">
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