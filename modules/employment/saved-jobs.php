<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Saved Jobs
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Handle remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saved'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        redirectWith('/rwanda_connect/modules/employment/saved-jobs.php', 'error', 'Invalid request.');
    }
    
    $jobId = (int)($_POST['job_id'] ?? 0);
    if ($jobId) {
        $stmt = $db->prepare('DELETE FROM user_saved_jobs WHERE user_id = ? AND job_id = ?');
        $stmt->execute([$userId, $jobId]);
        redirectWith('/rwanda_connect/modules/employment/saved-jobs.php', 'success', 'Job removed from saved.');
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare('SELECT COUNT(*) FROM user_saved_jobs WHERE user_id = ?');
$stmt->execute([$userId]);
$total = (int)$stmt->fetchColumn();

// Get saved jobs
$stmt = $db->prepare('
    SELECT usj.*, jp.*, e.company_name, e.logo, jc.name as category_name, jc.icon as category_icon
    FROM user_saved_jobs usj
    JOIN job_postings jp ON usj.job_id = jp.id
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE usj.user_id = ? AND jp.status = "published"
    ORDER BY usj.saved_at DESC
    LIMIT ? OFFSET ?
');
$stmt->execute([$userId, $perPage, $offset]);
$savedJobs = $stmt->fetchAll();

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Saved Jobs - Employment - Rwanda Connect';
$pageDescription = 'Your saved job listings for later review.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page saved-jobs-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/employment/" class="back-link">← Employment</a>
                <div>
                    <h1>Saved Jobs</h1>
                    <p>Jobs you've bookmarked for later</p>
                </div>
            </div>
        </header>
        
        <?php if (empty($savedJobs)): ?>
            <div class="empty-state-card">
                <div class="empty-icon">⭐</div>
                <h2>No saved jobs yet</h2>
                <p>Save jobs you're interested in to compare and apply later.</p>
                <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        <?php else: ?>
            <div class="saved-jobs-list">
                <?php foreach ($savedJobs as $job): 
                    $daysUntilDeadline = $job['application_deadline'] ? (new DateTime($job['application_deadline']))->diff(new DateTime())->days : null;
                    $isExpired = $daysUntilDeadline !== null && $daysUntilDeadline < 0;
                ?>
                    <article class="saved-job-card">
                        <div class="saved-job-main">
                            <div class="job-header">
                                <div class="company-info">
                                    <?php if ($job['logo']): ?>
                                        <img src="<?= e($job['logo']) ?>" alt="" class="company-logo">
                                    <?php else: ?>
                                        <div class="company-logo placeholder"><?= e(strtoupper(substr($job['company_name'], 0, 1))) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="job-title">
                                            <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>"><?= e($job['title']) ?></a>
                                        </h3>
                                        <span class="company-name"><?= e($job['company_name']) ?></span>
                                    </div>
                                </div>
                                <div class="job-badges">
                                    <?= $job['category_icon'] ? '<span class="category-badge">' . e($job['category_icon']) . ' ' . e($job['category_name']) . '</span>' : '' ?>
                                    <?php if ($job['is_remote']): ?><span class="remote-badge">🏠 Remote</span><?php endif; ?>
                                    <?php if ($job['featured_until'] && new DateTime($job['featured_until']) >= new DateTime()): ?><span class="featured-badge">⭐ Featured</span><?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="job-meta">
                                <?php if ($job['province']): ?>
                                    <span class="job-location">📍 <?= e($job['province']) ?><?= $job['district'] ? ', ' . e($job['district']) : '' ?></span>
                                <?php endif; ?>
                                <span class="job-type"><?= e(ucfirst(str_replace('_', ' ', $job['employment_type']))) ?></span>
                                <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                    <span class="job-salary"><?= $job['salary_min'] ? formatCurrency($job['salary_min']) : 'Negotiable' ?><?= $job['salary_max'] ? ' - ' . formatCurrency($job['salary_max']) : '' ?>/<?= e($job['salary_period']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-meta">
                                <span class="saved-date">Saved: <?= formatDate($job['saved_at']) ?></span>
                                <?php if ($job['application_deadline']): ?>
                                    <span class="deadline <?= $isExpired ? 'expired' : ($daysUntilDeadline <= 7 ? 'urgent' : '') ?>">
                                        📅 <?= $isExpired ? 'Expired' : ($daysUntilDeadline . ' days left') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="saved-job-actions">
                            <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-primary">View Details</a>
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                <input type="hidden" name="remove_saved" value="1">
                                <button type="submit" class="btn btn-outline" title="Remove from saved">⭐ Remove</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="pagination" aria-label="Saved jobs pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=<?= $pagination['prev_page'] ?>" class="page-btn">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></span>
                    
                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=<?= $pagination['next_page'] ?>" class="page-btn">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>