<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment Module Home
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Get user stats
$stmt = $db->prepare('SELECT COUNT(*) as count FROM user_saved_jobs WHERE user_id = ?');
$stmt->execute([$userId]);
$savedJobsCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM job_applications WHERE user_id = ?');
$stmt->execute([$userId]);
$applicationsCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM job_alerts WHERE user_id = ? AND is_active = TRUE');
$stmt->execute([$userId]);
$activeAlertsCount = (int)$stmt->fetchColumn();

// Get recent job postings
$stmt = $db->query('
    SELECT jp.*, e.company_name, jc.name as category_name
    FROM job_postings jp
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE jp.status = "published" AND (jp.application_deadline IS NULL OR jp.application_deadline >= CURDATE())
    ORDER BY jp.published_at DESC
    LIMIT 6
');
$recentJobs = $stmt->fetchAll();

// Get saved jobs
$stmt = $db->prepare('
    SELECT jp.*, e.company_name, jc.name as category_name
    FROM user_saved_jobs usj
    JOIN job_postings jp ON usj.job_id = jp.id
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE usj.user_id = ? AND jp.status = "published"
    ORDER BY usj.saved_at DESC
    LIMIT 4
');
$stmt->execute([$userId]);
$savedJobs = $stmt->fetchAll();

// Get applications
$stmt = $db->prepare('
    SELECT ja.*, jp.title, e.company_name, jc.name as category_name
    FROM job_applications ja
    JOIN job_postings jp ON ja.job_id = jp.id
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE ja.user_id = ?
    ORDER BY ja.applied_at DESC
    LIMIT 4
');
$stmt->execute([$userId]);
$recentApplications = $stmt->fetchAll();

// Get categories
$stmt = $db->query('SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name');
$categories = $stmt->fetchAll();

$pageTitle = 'Employment - Rwanda Connect';
$pageDescription = 'Find jobs, track applications, and manage your career opportunities.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page">
    <div class="module-container">
        <!-- Module Header -->
        <header class="module-header">
            <div class="module-brand">
                <span class="module-icon">💼</span>
                <div>
                    <h1>Employment</h1>
                    <p>Job search, applications, and career development</p>
                </div>
            </div>
            <div class="module-actions">
                <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-primary">🔍 Search Jobs</a>
                <a href="/rwanda_connect/modules/employment/alerts.php" class="btn btn-secondary">🔔 Job Alerts</a>
            </div>
        </header>
        
        <!-- Stats Overview -->
        <section class="module-stats" aria-label="Employment Statistics">
            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($recentJobs) ?></span>
                    <span class="stat-label">Active Listings</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $savedJobsCount ?></span>
                    <span class="stat-label">Saved Jobs</span>
                </div>
                <a href="/rwanda_connect/modules/employment/saved-jobs.php" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $applicationsCount ?></span>
                    <span class="stat-label">Applications</span>
                </div>
                <a href="/rwanda_connect/modules/employment/applications.php" class="stat-link">Track →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔔</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $activeAlertsCount ?></span>
                    <span class="stat-label">Active Alerts</span>
                </div>
                <a href="/rwanda_connect/modules/employment/alerts.php" class="stat-link">Manage →</a>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="module-section actions-section">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="/rwanda_connect/modules/employment/jobs.php" class="action-card">
                    <span class="action-icon">🔍</span>
                    <h3>Search Jobs</h3>
                    <p>Browse and filter job listings</p>
                </a>
                <a href="/rwanda_connect/modules/employment/saved-jobs.php" class="action-card">
                    <span class="action-icon">⭐</span>
                    <h3>Saved Jobs</h3>
                    <p>View and manage saved listings</p>
                </a>
                <a href="/rwanda_connect/modules/employment/applications.php" class="action-card">
                    <span class="action-icon">📄</span>
                    <h3>My Applications</h3>
                    <p>Track application status</p>
                </a>
                <a href="/rwanda_connect/modules/employment/alerts.php" class="action-card">
                    <span class="action-icon">🔔</span>
                    <h3>Job Alerts</h3>
                    <p>Set up email notifications</p>
                </a>
                <a href="/rwanda_connect/modules/employment/career-resources.php" class="action-card">
                    <span class="action-icon">📚</span>
                    <h3>Career Resources</h3>
                    <p>CV tips, interview guides, skills</p>
                </a>
                <a href="/rwanda_connect/modules/employment/employers.php" class="action-card">
                    <span class="action-icon">🏢</span>
                    <h3>Companies</h3>
                    <p>Explore employers and reviews</p>
                </a>
            </div>
        </section>
        
        <!-- Featured Jobs -->
        <section class="module-section">
            <div class="section-header">
                <h2>Latest Job Opportunities</h2>
                <a href="/rwanda_connect/modules/employment/jobs.php" class="section-link">View All →</a>
            </div>
            
            <?php if (!empty($recentJobs)): ?>
                <div class="jobs-grid">
                    <?php foreach ($recentJobs as $job): ?>
                        <article class="job-card">
                            <div class="job-header">
                                <div class="job-company">
                                    <span class="company-name"><?= e($job['company_name']) ?></span>
                                    <span class="job-category"><?= e($job['category_name'] ?? 'General') ?></span>
                                </div>
                                <?php if ($job['featured_until'] && new DateTime($job['featured_until']) >= new DateTime()): ?>
                                    <span class="featured-badge">⭐ Featured</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="job-title"><?= e($job['title']) ?></h3>
                            <div class="job-meta">
                                <span class="job-location">📍 <?= e($job['province'] ?? 'Remote') ?><?= $job['district'] ? ', ' . e($job['district']) : '' ?></span>
                                <span class="job-type"><?= e(ucfirst(str_replace('_', ' ', $job['employment_type']))) ?></span>
                            </div>
                            <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                <div class="job-salary">
                                    <?= formatCurrency($job['salary_min']) ?><?= $job['salary_max'] ? ' - ' . formatCurrency($job['salary_max']) : '' ?>
                                    <span>/<?= e($job['salary_period']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="job-footer">
                                <?php if ($job['application_deadline']): ?>
                                    <span class="deadline <?= new DateTime($job['application_deadline']) < new DateTime() ? 'expired' : '' ?>">
                                        📅 Deadline: <?= formatDate($job['application_deadline']) ?>
                                    </span>
                                <?php endif; ?>
                                <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-inline">
                    <span class="empty-icon">💼</span>
                    <p>No active job listings at the moment</p>
                    <a href="/rwanda_connect/modules/employment/alerts.php" class="btn btn-primary">Set Up Job Alerts</a>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Saved Jobs & Applications -->
        <div class="module-grid">
            <!-- Saved Jobs -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Saved Jobs</h2>
                    <a href="/rwanda_connect/modules/employment/saved-jobs.php" class="section-link">View All →</a>
                </div>
                
                <?php if (!empty($savedJobs)): ?>
                    <div class="saved-jobs-list">
                        <?php foreach ($savedJobs as $job): ?>
                            <article class="saved-job-item">
                                <div class="saved-job-info">
                                    <h4><?= e($job['title']) ?></h4>
                                    <p class="job-company"><?= e($job['company_name']) ?></p>
                                </div>
                                <div class="saved-job-actions">
                                    <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    <form method="POST" action="/rwanda_connect/modules/employment/save-job.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn btn-sm btn-outline" title="Remove from saved">⭐</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">⭐</span>
                        <p>No saved jobs yet</p>
                        <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-sm btn-primary">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Recent Applications -->
            <section class="module-section">
                <div class="section-header">
                    <h2>Recent Applications</h2>
                    <a href="/rwanda_connect/modules/employment/applications.php" class="section-link">View All →</a>
                </div>
                
                <?php if (!empty($recentApplications)): ?>
                    <div class="applications-list">
                        <?php foreach ($recentApplications as $app): ?>
                            <article class="application-item">
                                <div class="application-info">
                                    <h4><?= e($app['title']) ?></h4>
                                    <p class="job-company"><?= e($app['company_name']) ?></p>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge status-<?= $app['status'] ?>"><?= e(ucfirst(str_replace('_', ' ', $app['status']))) ?></span>
                                    <span class="application-date"><?= formatDate($app['applied_at']) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">📄</span>
                        <p>No applications yet</p>
                        <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-sm btn-primary">Apply to Jobs</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Categories -->
        <section class="module-section">
            <h2>Browse by Category</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="/rwanda_connect/modules/employment/jobs.php?category=<?= $category['id'] ?>" class="category-card">
                        <span class="category-icon"><?= e($category['icon'] ?? '💼') ?></span>
                        <h3><?= e($category['name']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>