<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: My Applications
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Status filter
$statusFilter = $_GET['status'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['ja.user_id = ?'];
$params = [$userId];

if ($statusFilter) {
    $where[] = 'ja.status = ?';
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM job_applications ja JOIN job_postings jp ON ja.job_id = jp.id WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get applications
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT ja.*, jp.title, jp.province, jp.district, jp.application_deadline, e.company_name, e.logo, jc.name as category_name, jc.icon as category_icon
    FROM job_applications ja
    JOIN job_postings jp ON ja.job_id = jp.id
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE {$whereClause}
    ORDER BY ja.applied_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$applications = $stmt->fetchAll();

// Get status counts for filter
$stmt = $db->prepare('
    SELECT status, COUNT(*) as count 
    FROM job_applications 
    WHERE user_id = ? 
    GROUP BY status
');
$stmt->execute([$userId]);
$statusCounts = [];
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['count'];
}

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'My Applications - Employment - Rwanda Connect';
$pageDescription = 'Track the status of your job applications.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page applications-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/employment/" class="back-link">← Employment</a>
                <div>
                    <h1>My Applications</h1>
                    <p>Track your job application progress</p>
                </div>
            </div>
        </header>
        
        <!-- Status Filter -->
        <section class="module-section filters-section">
            <div class="status-filters">
                <a href="/rwanda_connect/modules/employment/applications.php" class="status-filter <?= !$statusFilter ? 'active' : '' ?>">
                    All <span class="count"><?= $total ?></span>
                </a>
                <?php
                $statusOrder = ['pending', 'reviewed', 'shortlisted', 'interviewed', 'hired', 'rejected'];
                $statusLabels = [
                    'pending' => 'Pending',
                    'reviewed' => 'Reviewed',
                    'shortlisted' => 'Shortlisted',
                    'interviewed' => 'Interviewed',
                    'hired' => 'Hired',
                    'rejected' => 'Rejected'
                ];
                foreach ($statusOrder as $status): 
                    $count = $statusCounts[$status] ?? 0;
                    if ($count > 0 || $statusFilter === $status):
                ?>
                    <a href="?status=<?= $status ?>" class="status-filter <?= $statusFilter === $status ? 'active' : '' ?>">
                        <?= $statusLabels[$status] ?? ucfirst($status) ?>
                        <span class="count"><?= $count ?></span>
                    </a>
                <?php endif; endforeach; ?>
            </div>
        </section>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state-card">
                <div class="empty-icon">📄</div>
                <h2><?= $statusFilter ? 'No applications with this status' : 'No applications yet' ?></h2>
                <p><?= $statusFilter ? 'Try a different filter' : 'Start applying to jobs to see them here.' ?></p>
                <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $app): 
                    $daysSinceApplied = (new DateTime($app['applied_at']))->diff(new DateTime())->days;
                ?>
                    <article class="application-card">
                        <div class="application-main">
                            <div class="application-header">
                                <div class="company-info">
                                    <?php if ($app['logo']): ?>
                                        <img src="<?= e($app['logo']) ?>" alt="" class="company-logo">
                                    <?php else: ?>
                                        <div class="company-logo placeholder"><?= e(strtoupper(substr($app['company_name'], 0, 1))) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="application-title">
                                            <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $app['job_id'] ?>"><?= e($app['title']) ?></a>
                                        </h3>
                                        <span class="company-name"><?= e($app['company_name']) ?></span>
                                    </div>
                                </div>
                                <div class="application-status-badge">
                                    <span class="status-badge status-<?= $app['status'] ?>">
                                        <?= $statusLabels[$app['status']] ?? ucfirst($app['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="application-meta">
                                <span class="application-category"><?= $app['category_icon'] ?? '💼' ?> <?= e($app['category_name'] ?? 'General') ?></span>
                                <?php if ($app['province']): ?>
                                    <span class="application-location">📍 <?= e($app['province']) ?><?= $app['district'] ? ', ' . e($app['district']) : '' ?></span>
                                <?php endif; ?>
                                <span class="application-date">Applied: <?= formatDate($app['applied_at']) ?> (<?= $daysSinceApplied ?> days ago)</span>
                            </div>
                            
                            <?php if ($app['cover_letter']): ?>
                                <div class="application-cover-letter">
                                    <strong>Cover Letter:</strong>
                                    <p><?= e(substr($app['cover_letter'], 0, 300)) ?><?= strlen($app['cover_letter']) > 300 ? '...' : '' ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="application-actions">
                            <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $app['job_id'] ?>" class="btn btn-sm btn-primary">View Job</a>
                            
                            <?php if ($app['resume_path']): ?>
                                <a href="/rwanda_connect/<?= e($app['resume_path']) ?>" class="btn btn-sm btn-outline" target="_blank">📄 View Resume</a>
                            <?php endif; ?>
                            
                            <?php if (in_array($app['status'], ['pending', 'reviewed'], true)): ?>
                                <form method="POST" action="/rwanda_connect/modules/employment/withdraw-application.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Withdraw this application?')">Withdraw</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="pagination" aria-label="Applications pagination">
                    <?php
                    $params = ['status' => $statusFilter];
                    $params = array_filter($params);
                    ?>
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?" . http_build_query(array_merge($params, ['page' => $pagination['prev_page']])) class="page-btn">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
                    
                    <?php if ($pagination['has_next']): ?>
                        <a href="?" . http_build_query(array_merge($params, ['page' => $pagination['next_page']])) class="page-btn">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>