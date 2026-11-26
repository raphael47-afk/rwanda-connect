<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Notifications
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Mark as read if requested
if (isset($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    $stmt = $db->prepare('UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);
    header('Location: /rwanda_connect/dashboard/notifications.php');
    exit;
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '') && $db->prepare('UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = FALSE')->execute([$userId]);
    header('Location: /rwanda_connect/dashboard/notifications.php');
    exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$stmt->execute([$userId]);
$total = (int)$stmt->fetchColumn();

// Get notifications
$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->execute([$userId, $perPage, $offset]);
$notifications = $stmt->fetchAll();

$pagination = paginate($total, $perPage, $page);
$csrfToken = generateCsrfToken();

$pageTitle = 'Notifications - Rwanda Connect';
$pageDescription = 'View and manage your notifications.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="dashboard-page notifications-page">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Notifications</h1>
                <p class="welcome-subtitle">Stay updated with alerts from all your services.</p>
            </div>
            <div class="header-actions">
                <?php 
                $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE');
                $stmt->execute([$userId]);
                $unreadCount = (int)$stmt->fetchColumn();
                if ($unreadCount > 0): ?>
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="mark_all_read" value="1">
                        <button type="submit" class="btn btn-secondary">Mark all read (<?= $unreadCount ?>)</button>
                    </form>
                <?php endif; ?>
                <a href="/rwanda_connect/dashboard/" class="btn btn-outline">← Dashboard</a>
            </div>
        </header>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state-card">
                <div class="empty-icon">🔔</div>
                <h2>No notifications yet</h2>
                <p>You'll see alerts here for job matches, scholarship deadlines, health campaigns, and more.</p>
                <a href="/rwanda_connect/services.php" class="btn btn-primary">Explore Services</a>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <article class="notification-card <?= !$notification['is_read'] ? 'unread' : '' ?>" data-id="<?= $notification['id'] ?>">
                        <div class="notification-icon">
                            <?= match($notification['type']) {
                                'job' => '💼',
                                'scholarship' => '🎓',
                                'health' => '🏥',
                                'agriculture' => '🌱',
                                'system' => '⚙️',
                                default => '🔔'
                            } ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-header">
                                <h3><?= e($notification['title']) ?></h3>
                                <time><?= timeAgo($notification['created_at']) ?></time>
                            </div>
                            <p><?= e($notification['message']) ?></p>
                            <?php if ($notification['action_url']): ?>
                                <a href="<?= e($notification['action_url']) ?>" class="notification-action">View Details →</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <a href="?mark_read=<?= $notification['id'] ?>" class="mark-read-btn" aria-label="Mark as read" title="Mark as read">✓</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav class="pagination" aria-label="Notifications pagination">
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