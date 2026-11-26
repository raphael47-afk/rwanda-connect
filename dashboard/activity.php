<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Activity Log
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $db->prepare('SELECT COUNT(*) FROM activity_log WHERE user_id = ?');
$stmt->execute([$userId]);
$total = (int)$stmt->fetchColumn();

// Get activities
$stmt = $db->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$userId, $perPage, $offset]);
$activities = $stmt->fetchAll();

$pagination = paginate($total, $perPage, $page);

$pageTitle = 'Activity Log - Rwanda Connect';
$pageDescription = 'View your account activity history.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="dashboard-page activity-page">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Activity Log</h1>
                <p class="welcome-subtitle">Track your account activity and security events.</p>
            </div>
            <a href="/rwanda_connect/dashboard/" class="btn btn-outline">← Dashboard</a>
        </header>
        
        <?php if (empty($activities)): ?>
            <div class="empty-state-card">
                <div class="empty-icon">📋</div>
                <h2>No activity recorded</h2>
                <p>Your account activity will appear here.</p>
            </div>
        <?php else: ?>
            <div class="activity-timeline">
                <?php foreach ($activities as $activity): ?>
                    <article class="activity-item">
                        <div class="activity-marker">
                            <span class="activity-icon"><?= match($activity['action']) {
                                'user_login' => '🔑',
                                'user_registered' => '👤',
                                'profile_updated' => '✏️',
                                'password_changed' => '🔒',
                                'job_application_submitted' => '📄',
                                'job_saved' => '⭐',
                                'scholarship_application_submitted' => '🎓',
                                'appointment_booked' => '📅',
                                'farm_created' => '🌾',
                                default => '📝'
                            } ?></span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-header">
                                <h4><?= e(ucfirst(str_replace('_', ' ', $activity['action']))) ?></h4>
                                <time><?= formatDateTime($activity['created_at']) ?></time>
                            </div>
                            <?php if ($activity['description']): ?>
                                <p><?= e($activity['description']) ?></p>
                            <?php endif; ?>
                            <?php if ($activity['ip_address']): ?>
                                <div class="activity-meta">
                                    <span>IP: <?= e($activity['ip_address']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="pagination" aria-label="Activity pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="<?= buildPageUrl($pagination['prev_page']) ?>" class="page-btn">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                    </span>
                    
                    <?php if ($pagination['has_next']): ?>
                        <a href="<?= buildPageUrl($pagination['next_page']) ?>" class="page-btn">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>