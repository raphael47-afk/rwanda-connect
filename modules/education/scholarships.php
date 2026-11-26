<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Education: Scholarships
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

$tab = $_GET['tab'] ?? 'browse'; // browse, applications

// Handle scholarship application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_scholarship'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        redirectWith('/rwanda_connect/modules/education/scholarships.php', 'error', 'Invalid request.');
    }
    
    $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
    $motivationLetter = trim($_POST['motivation_letter'] ?? '');
    
    if (!$scholarshipId) {
        redirectWith('/rwanda_connect/modules/education/scholarships.php', 'error', 'Invalid scholarship.');
    }
    
    // Check if already applied
    $stmt = $db->prepare('SELECT id FROM scholarship_applications WHERE scholarship_id = ? AND user_id = ?');
    $stmt->execute([$scholarshipId, $userId]);
    if ($stmt->fetch()) {
        redirectWith('/rwanda_connect/modules/education/scholarships.php', 'error', 'You have already applied to this scholarship.');
    }
    
    // Handle document upload
    $documentsPath = null;
    if (!empty($_FILES['documents']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/scholarships';
        $result = handleFileUpload($_FILES['documents'], $uploadDir, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'], 10485760);
        if ($result['success']) {
            $documentsPath = 'uploads/scholarships/' . $result['filename'];
        }
    }
    
    $stmt = $db->prepare('INSERT INTO scholarship_applications (scholarship_id, user_id, motivation_letter, documents_path) VALUES (?, ?, ?, ?)');
    $stmt->execute([$scholarshipId, $userId, $motivationLetter, $documentsPath]);
    
    // Update applications count
    $stmt = $db->prepare('UPDATE scholarships SET applications_count = applications_count + 1 WHERE id = ?');
    $stmt->execute([$scholarshipId]);
    
    logActivity('scholarship_application_submitted', "Applied for scholarship", $userId);
    
    redirectWith('/rwanda_connect/modules/education/scholarships.php?tab=applications', 'success', 'Scholarship application submitted!');
}

// Filters for browse tab
$search = trim($_GET['q'] ?? '');
$levelFilter = $_GET['level'] ?? '';
$coverageFilter = $_GET['coverage'] ?? '';
$countryFilter = $_GET['country'] ?? '';

// Build query for browse
$where = ['s.is_active = TRUE', 's.deadline >= CURDATE()'];
$params = [];

if ($search) {
    $where[] = '(s.title LIKE ? OR s.provider LIKE ? OR s.description LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($levelFilter) {
    $where[] = 's.target_level = ?';
    $params[] = $levelFilter;
}

if ($coverageFilter) {
    $where[] = 's.coverage = ?';
    $params[] = $coverageFilter;
}

if ($countryFilter) {
    $where[] = '(s.target_country = ? OR s.target_country IS NULL OR s.target_country = "")';
    $params[] = $countryFilter;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM scholarships s WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get scholarships
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT * FROM scholarships s 
    WHERE {$whereClause}
    ORDER BY s.deadline ASC, s.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$scholarships = $stmt->fetchAll();

// Get user's applied scholarship IDs
$stmt = $db->prepare('SELECT scholarship_id FROM scholarship_applications WHERE user_id = ?');
$stmt->execute([$userId]);
$appliedIds = array_column($stmt->fetchAll(), 'scholarship_id');

// Get applications for applications tab
$applications = [];
if ($tab === 'applications') {
    $stmt = $db->prepare('
        SELECT sa.*, s.title, s.provider, s.deadline, s.coverage, s.amount_rwf
        FROM scholarship_applications sa
        JOIN scholarships s ON sa.scholarship_id = s.id
        WHERE sa.user_id = ?
        ORDER BY sa.applied_at DESC
    ');
    $stmt->execute([$userId]);
    $applications = $stmt->fetchAll();
}

$pagination = paginate($total, $perPage, $page);
$csrfToken = generateCsrfToken();

$pageTitle = 'Scholarships - Education - Rwanda Connect';
$pageDescription = 'Find and apply for scholarships and funding opportunities.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page education-page scholarships-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/education/" class="back-link">← Education</a>
                <div>
                    <h1>Scholarships</h1>
                    <p>Discover funding opportunities for your education</p>
                </div>
            </div>
        </header>
        
        <!-- Tabs -->
        <nav class="module-tabs" aria-label="Scholarship tabs">
            <a href="?tab=browse" class="<?= $tab === 'browse' ? 'active' : '' ?>">🔍 Browse Scholarships</a>
            <a href="?tab=applications" class="<?= $tab === 'applications' ? 'active' : '' ?>">📄 My Applications</a>
        </nav>
        
        <?php if ($tab === 'browse'): ?>
            <!-- Filters -->
            <section class="module-section filters-section">
                <form method="GET" action="" class="search-filters">
                    <input type="hidden" name="tab" value="browse">
                    
                    <div class="filter-row">
                        <div class="form-group search-group">
                            <span class="search-icon">🔍</span>
                            <input type="search" name="q" placeholder="Search scholarships..." value="<?= e($search) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="level">Level</label>
                            <select id="level" name="level">
                                <option value="">Any Level</option>
                                <option value="secondary" <?= $levelFilter === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                                <option value="diploma" <?= $levelFilter === 'diploma' ? 'selected' : '' ?>>Diploma</option>
                                <option value="bachelor" <?= $levelFilter === 'bachelor' ? 'selected' : '' ?>>Bachelor's</option>
                                <option value="master" <?= $levelFilter === 'master' ? 'selected' : '' ?>>Master's</option>
                                <option value="phd" <?= $levelFilter === 'phd' ? 'selected' : '' ?>>PhD</option>
                                <option value="any" <?= $levelFilter === 'any' ? 'selected' : '' ?>>Any</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="coverage">Coverage</label>
                            <select id="coverage" name="coverage">
                                <option value="">Any Coverage</option>
                                <option value="full" <?= $coverageFilter === 'full' ? 'selected' : '' ?>>Full</option>
                                <option value="partial" <?= $coverageFilter === 'partial' ? 'selected' : '' ?>>Partial</option>
                                <option value="tuition_only" <?= $coverageFilter === 'tuition_only' ? 'selected' : '' ?>>Tuition Only</option>
                                <option value="living_only" <?= $coverageFilter === 'living_only' ? 'selected' : '' ?>>Living Only</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Target Country</label>
                            <input type="text" id="country" name="country" value="<?= e($countryFilter) ?>" placeholder="e.g., Rwanda, USA, UK" list="countries-list">
                            <datalist id="countries-list">
                                <option value="Rwanda">
                                <option value="USA">
                                <option value="United Kingdom">
                                <option value="Canada">
                                <option value="Australia">
                                <option value="Germany">
                                <option value="China">
                                <option value="India">
                                <option value="South Africa">
                                <option value="Kenya">
                            </datalist>
                        </div>
                        
                        <div class="form-group filter-actions">
                            <a href="?tab=browse" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </section>
            
            <!-- Scholarships Grid -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Available Scholarships</h2>
                    <span class="results-count"><?= $total ?> scholarship<?= $total !== 1 ? 's' : '' ?> found</span>
                </div>
                
                <?php if (empty($scholarships)): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon">💰</div>
                        <h2>No scholarships found</h2>
                        <p>Try adjusting your filters or check back later for new opportunities.</p>
                        <a href="?tab=browse" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="scholarships-grid">
                        <?php foreach ($scholarships as $scholarship): 
                            $daysLeft = (new DateTime($scholarship['deadline']))->diff(new DateTime())->days;
                            $isApplied = in_array($scholarship['id'], $appliedIds);
                        ?>
                            <article class="scholarship-card">
                                <div class="scholarship-header">
                                    <span class="scholarship-coverage"><?= e(ucfirst($scholarship['coverage'])) ?></span>
                                    <span class="scholarship-deadline <?= $daysLeft <= 7 ? 'urgent' : '' ?>">
                                        📅 <?= $daysLeft === 0 ? 'Today' : ($daysLeft === 1 ? 'Tomorrow' : $daysLeft . ' days left') ?>
                                    </span>
                                </div>
                                <h3><?= e($scholarship['title']) ?></h3>
                                <p class="scholarship-provider">🏢 <?= e($scholarship['provider']) ?></p>
                                <p class="scholarship-description"><?= e(substr($scholarship['description'], 0, 200)) ?>...</p>
                                
                                <div class="scholarship-meta">
                                    <span class="scholarship-level"><?= e(ucfirst(str_replace('_', ' ', $scholarship['target_level']))) ?></span>
                                    <?php if ($scholarship['target_field']): ?>
                                        <span class="scholarship-field">📚 <?= e($scholarship['target_field']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($scholarship['target_country']): ?>
                                        <span class="scholarship-country">🌍 <?= e($scholarship['target_country']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($scholarship['amount_rwf']): ?>
                                        <span class="scholarship-amount"><?= formatCurrency($scholarship['amount_rwf']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="scholarship-actions">
                                    <?php if ($isApplied): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>✓ Applied</button>
                                    <?php else: ?>
                                        <a href="/rwanda_connect/modules/education/scholarship-detail.php?id=<?= $scholarship['id'] ?>" class="btn btn-sm btn-primary">View & Apply</a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav class="pagination" aria-label="Scholarships pagination">
                            <?php
                            $baseParams = array_filter([
                                'tab' => 'browse',
                                'q' => $search,
                                'level' => $levelFilter,
                                'coverage' => $coverageFilter,
                                'country' => $countryFilter,
                            ]);
                            ?>
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?" . http_build_query(array_merge($baseParams, ['page' => $pagination['prev_page']])) class="page-btn">← Previous</a>
                            <?php endif; ?>
                            
                            <span class="page-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?" . http_build_query(array_merge($baseParams, ['page' => $pagination['next_page']])) class="page-btn">Next →</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
            
        <?php elseif ($tab === 'applications'): ?>
            <!-- My Applications -->
            <section class="module-section">
                <div class="section-header">
                    <h2>My Scholarship Applications</h2>
                </div>
                
                <?php if (empty($applications)): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon">📄</div>
                        <h2>No applications yet</h2>
                        <p>Start applying for scholarships to track them here.</p>
                        <a href="?tab=browse" class="btn btn-primary">Browse Scholarships</a>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $app): 
                            $daysUntilDeadline = (new DateTime($app['deadline']))->diff(new DateTime())->days;
                        ?>
                            <article class="application-card">
                                <div class="application-main">
                                    <div class="application-header">
                                        <h3><?= e($app['title']) ?></h3>
                                        <span class="status-badge status-<?= $app['status'] ?>">
                                            <?= e(ucfirst(str_replace('_', ' ', $app['status']))) ?>
                                        </span>
                                    </div>
                                    <p class="application-provider">🏢 <?= e($app['provider']) ?></p>
                                    
                                    <div class="application-meta">
                                        <span>📅 Deadline: <?= formatDate($app['deadline']) ?> (<?= $daysUntilDeadline >= 0 ? $daysUntilDeadline . ' days left' : 'Expired' ?>)</span>
                                        <?php if ($app['amount_rwf']): ?>
                                            <span>💰 Amount: <?= formatCurrency($app['amount_rwf']) ?></span>
                                        <?php endif; ?>
                                        <span>📋 Coverage: <?= e(ucfirst($app['coverage'])) ?></span>
                                    </div>
                                    
                                    <?php if ($app['motivation_letter']): ?>
                                        <div class="application-motivation">
                                            <strong>Your Motivation:</strong>
                                            <p><?= e(substr($app['motivation_letter'], 0, 300)) ?><?= strlen($app['motivation_letter']) > 300 ? '...' : '' ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="application-actions">
                                    <span class="application-date">Applied: <?= formatDate($app['applied_at']) ?></span>
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