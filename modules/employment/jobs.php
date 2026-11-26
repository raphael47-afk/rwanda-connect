<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Job Search
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$db = getDb();

// Filters
$search = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$locationFilter = $_GET['location'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$experienceFilter = $_GET['experience'] ?? '';
$salaryMin = $_GET['salary_min'] ?? '';
$remoteFilter = isset($_GET['remote']) ? '1' : '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where = ['jp.status = "published"', '(jp.application_deadline IS NULL OR jp.application_deadline >= CURDATE())'];
$params = [];

if ($search) {
    $where[] = '(jp.title LIKE ? OR jp.description LIKE ? OR e.company_name LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($categoryFilter) {
    $where[] = 'jp.category_id = ?';
    $params[] = $categoryFilter;
}

if ($locationFilter) {
    $where[] = '(jp.province LIKE ? OR jp.district LIKE ?)';
    $params = array_merge($params, ["%{$locationFilter}%", "%{$locationFilter}%"]);
}

if ($typeFilter) {
    $where[] = 'jp.employment_type = ?';
    $params[] = $typeFilter;
}

if ($experienceFilter) {
    $where[] = 'jp.experience_level = ?';
    $params[] = $experienceFilter;
}

if ($salaryMin) {
    $where[] = 'jp.salary_max >= ?';
    $params[] = $salaryMin;
}

if ($remoteFilter) {
    $where[] = 'jp.is_remote = TRUE';
}

$whereClause = implode(' AND ', $where);

// Sorting
$orderBy = match($sort) {
    'oldest' => 'jp.published_at ASC',
    'salary_high' => 'jp.salary_max DESC',
    'salary_low' => 'jp.salary_min ASC',
    'deadline' => 'jp.application_deadline ASC',
    default => 'jp.published_at DESC'
};

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) FROM job_postings jp JOIN employers e ON jp.employer_id = e.id WHERE {$whereClause}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Get jobs
$paramsPaged = [...$params, $perPage, $offset];
$stmt = $db->prepare("
    SELECT jp.*, e.company_name, e.logo, jc.name as category_name, jc.icon as category_icon
    FROM job_postings jp
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT ? OFFSET ?
");
$stmt->execute($paramsPaged);
$jobs = $stmt->fetchAll();

// Get categories for filter
$stmt = $db->query('SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name');
$categories = $stmt->fetchAll();

// Get provinces for location filter
$stmt = $db->query('SELECT DISTINCT province FROM job_postings WHERE province IS NOT NULL ORDER BY province');
$provinces = $stmt->fetchAll();

// Check saved jobs
$savedJobIds = [];
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $stmt = $db->prepare('SELECT job_id FROM user_saved_jobs WHERE user_id = ?');
    $stmt->execute([$userId]);
    $savedJobIds = array_column($stmt->fetchAll(), 'job_id');
}

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Job Search - Employment - Rwanda Connect';
$pageDescription = 'Search and filter job opportunities across Rwanda.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page jobs-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/employment/" class="back-link">← Employment</a>
                <div>
                    <h1>Job Search</h1>
                    <p>Find your next career opportunity</p>
                </div>
            </div>
        </header>
        
        <!-- Search & Filters -->
        <section class="module-section filters-section">
            <form method="GET" action="" class="search-filters">
                <div class="search-bar">
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="search" name="q" id="search" placeholder="Job title, keywords, company..." value="<?= e($search) ?>" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
                
                <div class="filter-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['icon'] ?? '💼') . ' ' . e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <select id="location" name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= e($prov['province']) ?>" <?= $locationFilter === $prov['province'] ? 'selected' : '' ?>><?= e($prov['province']) ?></option>
                            <?php endforeach; ?>
                            <option value="remote" <?= $locationFilter === 'remote' ? 'selected' : '' ?>>Remote</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Job Type</label>
                        <select id="type" name="type">
                            <option value="">All Types</option>
                            <option value="full_time" <?= $typeFilter === 'full_time' ? 'selected' : '' ?>>Full-time</option>
                            <option value="part_time" <?= $typeFilter === 'part_time' ? 'selected' : '' ?>>Part-time</option>
                            <option value="contract" <?= $typeFilter === 'contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="internship" <?= $typeFilter === 'internship' ? 'selected' : '' ?>>Internship</option>
                            <option value="volunteer" <?= $typeFilter === 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                            <option value="remote" <?= $typeFilter === 'remote' ? 'selected' : '' ?>>Remote</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience">Experience</label>
                        <select id="experience" name="experience">
                            <option value="">Any Level</option>
                            <option value="entry" <?= $experienceFilter === 'entry' ? 'selected' : '' ?>>Entry Level</option>
                            <option value="junior" <?= $experienceFilter === 'junior' ? 'selected' : '' ?>>Junior</option>
                            <option value="mid" <?= $experienceFilter === 'mid' ? 'selected' : '' ?>>Mid Level</option>
                            <option value="senior" <?= $experienceFilter === 'senior' ? 'selected' : '' ?>>Senior</option>
                            <option value="lead" <?= $experienceFilter === 'lead' ? 'selected' : '' ?>>Lead</option>
                            <option value="executive" <?= $experienceFilter === 'executive' ? 'selected' : '' ?>>Executive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_min">Min Salary (RWF/month)</label>
                        <input type="number" id="salary_min" name="salary_min" value="<?= e($salaryMin) ?>" placeholder="e.g., 500000" min="0" step="10000">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="remote" value="1" <?= $remoteFilter ? 'checked' : '' ?>> Remote Only
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="salary_high" <?= $sort === 'salary_high' ? 'selected' : '' ?>>Salary: High to Low</option>
                            <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>>Salary: Low to High</option>
                            <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Deadline Soonest</option>
                        </select>
                    </div>
                    
                    <div class="form-group filter-actions">
                        <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </section>
        
        <!-- Results -->
        <section class="module-section">
            <div class="section-header">
                <h2>Job Results</h2>
                <span class="results-count"><?= $total ?> job<?= $total !== 1 ? 's' : '' ?> found</span>
            </div>
            
            <?php if (empty($jobs)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon">🔍</div>
                    <h2>No jobs found</h2>
                    <p>Try adjusting your search criteria or browse all categories.</p>
                    <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-primary">Clear All Filters</a>
                </div>
            <?php else: ?>
                <div class="jobs-list">
                    <?php foreach ($jobs as $job): 
                        $isSaved = in_array($job['id'], $savedJobIds);
                        $daysUntilDeadline = $job['application_deadline'] ? (new DateTime($job['application_deadline']))->diff(new DateTime())->days : null;
                    ?>
                        <article class="job-card">
                            <div class="job-main">
                                <div class="job-header">
                                    <div class="job-company-info">
                                        <?php if ($job['logo']): ?>
                                            <img src="<?= e($job['logo']) ?>" alt="" class="company-logo">
                                        <?php else: ?>
                                            <div class="company-logo placeholder"><?= e(strtoupper(substr($job['company_name'], 0, 1))) ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="company-name"><?= e($job['company_name']) ?></span>
                                            <span class="job-category"><?= e($job['category_icon'] ?? '💼') . ' ' . e($job['category_name'] ?? 'General') ?></span>
                                        </div>
                                    </div>
                                    <div class="job-badges">
                                        <?php if ($job['featured_until'] && new DateTime($job['featured_until']) >= new DateTime()): ?>
                                            <span class="featured-badge">⭐ Featured</span>
                                        <?php endif; ?>
                                        <?php if ($job['is_remote']): ?>
                                            <span class="remote-badge">🏠 Remote</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <h3 class="job-title">
                                    <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>"><?= e($job['title']) ?></a>
                                </h3>
                                
                                <div class="job-meta">
                                    <?php if ($job['province']): ?>
                                        <span class="job-location">📍 <?= e($job['province']) ?><?= $job['district'] ? ', ' . e($job['district']) : '' ?></span>
                                    <?php endif; ?>
                                    <span class="job-type"><?= e(ucfirst(str_replace('_', ' ', $job['employment_type']))) ?></span>
                                    <?php if ($job['experience_level']): ?>
                                        <span class="job-experience">📈 <?= e(ucfirst($job['experience_level'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                    <div class="job-salary">
                                        <span class="salary-range">
                                            <?= $job['salary_min'] ? formatCurrency($job['salary_min']) : 'Negotiable' ?>
                                            <?= $job['salary_max'] ? ' - ' . formatCurrency($job['salary_max']) : '' ?>
                                        </span>
                                        <span class="salary-period">/<?= e($job['salary_period']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="job-description"><?= e(substr($job['description'], 0, 200)) ?>...</p>
                            </div>
                            
                            <div class="job-footer">
                                <div class="job-deadline">
                                    <?php if ($job['application_deadline']): ?>
                                        <span class="deadline <?= $daysUntilDeadline !== null && $daysUntilDeadline < 0 ? 'expired' : ($daysUntilDeadline !== null && $daysUntilDeadline <= 7 ? 'urgent' : '') ?>">
                                            📅 <?= $daysUntilDeadline !== null && $daysUntilDeadline < 0 ? 'Expired' : ($daysUntilDeadline !== null ? "{$daysUntilDeadline} days left" : 'No deadline') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-actions">
                                    <form method="POST" action="/rwanda_connect/modules/employment/save-job.php" class="save-form" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <input type="hidden" name="action" value="<?= $isSaved ? 'remove' : 'add' ?>">
                                        <button type="submit" class="btn btn-sm <?= $isSaved ? 'btn-secondary' : 'btn-outline' ?>" title="<?= $isSaved ? 'Remove from saved' : 'Save job' ?>">
                                            <?= $isSaved ? '⭐ Saved' : '☆ Save' ?>
                                        </button>
                                    </form>
                                    <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="pagination" aria-label="Job search pagination">
                        <?php
                        $baseParams = [
                            'q' => $search,
                            'category' => $categoryFilter,
                            'location' => $locationFilter,
                            'type' => $typeFilter,
                            'experience' => $experienceFilter,
                            'salary_min' => $salaryMin,
                            'remote' => $remoteFilter,
                            'sort' => $sort,
                        ];
                        $baseParams = array_filter($baseParams, fn($v) => $v !== '');
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
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>