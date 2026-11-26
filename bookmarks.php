<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Bookmarks
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Get bookmarked items from all modules
$bookmarks = [];

// Bookmarked jobs
$stmt = $db->prepare('
    SELECT usj.*, jp.title, jp.province, jp.district, jp.application_deadline, e.company_name, e.logo, "job" as type
    FROM user_saved_jobs usj
    JOIN job_postings jp ON usj.job_id = jp.id
    JOIN employers e ON jp.employer_id = e.id
    WHERE usj.user_id = ? AND jp.status = "published"
    ORDER BY usj.saved_at DESC
');
$stmt->execute([$userId]);
$bookmarks = array_merge($bookmarks, $stmt->fetchAll());

// For now, we'll add placeholders for other bookmark types
// In a full implementation, you'd have bookmark tables for each module

$pageTitle = 'Bookmarks - Rwanda Connect';
$pageDescription = 'Your saved items across all modules.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="page bookmarks-page">
    <div class="page-container">
        <header class="page-header">
            <h1>Bookmarks</h1>
            <p>Your saved items across all modules - jobs, scholarships, courses, and more.</p>
        </header>
        
        <!-- Filter Tabs -->
        <nav class="bookmark-tabs" aria-label="Bookmark categories">
            <a href="#" class="active" data-filter="all">All <span class="count"><?= count($bookmarks) ?></span></a>
            <a href="#" data-filter="job">💼 Jobs <span class="count"><?= count(array_filter($bookmarks, fn($b) => $b['type'] === 'job')) ?></span></a>
            <a href="#" data-filter="scholarship">🎓 Scholarships <span class="count">0</span></a>
            <a href="#" data-filter="course">📚 Courses <span class="count">0</span></a>
            <a href="#" data-filter="facility">🏥 Facilities <span class="count">0</span></a>
            <a href="#" data-filter="article">📖 Articles <span class="count">0</span></a>
        </nav>
        
        <?php if (empty($bookmarks)): ?>
            <div class="empty-state-card">
                <span class="empty-icon">🔖</span>
                <h2>No bookmarks yet</h2>
                <p>Save jobs, scholarships, courses, facilities, and articles to access them quickly later.</p>
                <div class="empty-actions">
                    <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-primary">Browse Jobs</a>
                    <a href="/rwanda_connect/modules/education/scholarships.php" class="btn btn-secondary">Browse Scholarships</a>
                    <a href="/rwanda_connect/modules/education/courses.php" class="btn btn-secondary">Browse Courses</a>
                </div>
            </div>
        <?php else: ?>
            <div class="bookmarks-grid" id="bookmarks-grid">
                <?php foreach ($bookmarks as $item): ?>
                    <article class="bookmark-card" data-type="<?= e($item['type']) ?>">
                        <div class="bookmark-header">
                            <span class="bookmark-type"><?= match($item['type']) {
                                'job' => '💼',
                                'scholarship' => '🎓',
                                'course' => '📚',
                                'facility' => '🏥',
                                default => '🔖'
                            } ?></span>
                            <form method="POST" action="/rwanda_connect/modules/employment/save-job.php" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                <input type="hidden" name="job_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="bookmark-remove" title="Remove bookmark" onclick="event.stopPropagation()">🔖</button>
                            </form>
                        </div>
                        
                        <div class="bookmark-content">
                            <h3><a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $item['id'] ?>"><?= e($item['title']) ?></a></h3>
                            <p class="bookmark-source"><?= e($item['company_name']) ?></p>
                            
                            <div class="bookmark-meta">
                                <?php if ($item['province']): ?>
                                    <span class="meta-item">📍 <?= e($item['province']) ?><?= $item['district'] ? ', ' . e($item['district']) : '' ?></span>
                                <?php endif; ?>
                                <?php if ($item['salary_min'] || $item['salary_max']): ?>
                                    <span class="meta-item">💰 <?= $item['salary_min'] ? formatCurrency($item['salary_min']) : 'Negotiable' ?><?= $item['salary_max'] ? ' - ' . formatCurrency($item['salary_max']) : '' ?></span>
                                <?php endif; ?>
                                <?php if ($item['application_deadline']): ?>
                                    <span class="meta-item deadline">📅 Deadline: <?= formatDate($item['application_deadline']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bookmark-actions">
                                <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                <button class="btn btn-sm btn-outline" onclick="shareBookmark('<?= e($item['title']) ?>', '<?= e($item['company_name']) ?>')">Share</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Filter tabs
    document.querySelectorAll('.bookmark-tabs a').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.bookmark-tabs a').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            const filter = tab.dataset.filter;
            document.querySelectorAll('.bookmark-card').forEach(card => {
                if (filter === 'all' || card.dataset.type === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Share bookmark
    window.shareBookmark = (title, company) => {
        const text = `Check out this opportunity: ${title} at ${company}`;
        if (navigator.share) {
            navigator.share({ title, text });
        } else {
            navigator.clipboard.writeText(`${text} - ${window.location.origin}/rwanda_connect/modules/employment/jobs.php`).then(() => {
                alert('Link copied to clipboard!');
            });
        }
    };
});
</script>

<style>
/* Bookmarks Page Styles */
.bookmarks-page {
    min-height: 100vh;
}

.page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 24px;
}

.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    margin: 0 0 8px;
    font-size: clamp(2rem, 4vw, 3rem);
}

.page-header p {
    margin: 0;
    color: var(--muted);
}

.bookmark-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 32px;
    padding: 8px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
}

.bookmark-tabs a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: var(--radius-md);
    color: var(--muted);
    font-weight: 500;
    text-decoration: none;
    transition: all var(--transition);
}

.bookmark-tabs a:hover {
    background: var(--green-light);
    color: var(--green);
}

.bookmark-tabs a.active {
    background: var(--green);
    color: white;
}

.bookmark-tabs .count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 700;
}

.bookmark-tabs a.active .count {
    background: rgba(255,255,255,0.3);
}

.bookmarks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.bookmark-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all var(--transition);
}

.bookmark-card:hover {
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
    transform: translateY(-2px);
}

.bookmark-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px;
    background: var(--background);
    border-bottom: 1px solid var(--line);
}

.bookmark-type {
    font-size: 1.5rem;
}

.bookmark-remove {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px;
    border-radius: var(--radius-sm);
    transition: all var(--transition);
}

.bookmark-remove:hover {
    background: var(--error-bg);
    color: var(--error);
}

.bookmark-content {
    padding: 20px;
}

.bookmark-content h3 {
    margin: 0 0 8px;
    font-size: 1.1rem;
}

.bookmark-content h3 a {
    color: var(--ink);
}

.bookmark-content h3 a:hover {
    color: var(--green);
}

.bookmark-source {
    margin: 0 0 12px;
    color: var(--green);
    font-weight: 600;
    font-size: .9rem;
}

.bookmark-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
    font-size: .8rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--muted);
    font-size: .75rem;
}

.meta-item.deadline {
    color: var(--warning);
    font-weight: 600;
}

.bookmark-actions {
    display: flex;
    gap: 8px;
}

@media (max-width: 768px) {
    .bookmarks-grid {
        grid-template-columns: 1fr;
    }
}
</style>