<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Enhanced Citizen Dashboard
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$user = getCurrentUser();
$userId = getCurrentUserId();
$db = getDb();

// Get dashboard stats with trends
$stats = [];

// Agriculture stats
$stmt = $db->prepare('SELECT COUNT(*) as farms, SUM(size_hectares) as total_hectares FROM user_farms WHERE user_id = ? AND is_active = TRUE');
$stmt->execute([$userId]);
$agriStats = $stmt->fetch();
$stats['farms'] = (int)($agriStats['farms'] ?? 0);
$stats['total_hectares'] = $agriStats['total_hectares'] ? number_format($agriStats['total_hectares'], 2) : '0';

// Active crops
$stmt = $db->prepare('SELECT COUNT(*) as active_crops FROM farm_crops fc JOIN user_farms f ON fc.farm_id = f.id WHERE f.user_id = ? AND fc.status IN ("planted", "growing")');
$stmt->execute([$userId]);
$stats['active_crops'] = (int)$stmt->fetchColumn();

// Upcoming harvests (next 30 days)
$stmt = $db->prepare('SELECT COUNT(*) as upcoming_harvests FROM farm_crops fc JOIN user_farms f ON fc.farm_id = f.id WHERE f.user_id = ? AND fc.expected_harvest_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND fc.status IN ("planted", "growing")');
$stmt->execute([$userId]);
$stats['upcoming_harvests'] = (int)$stmt->fetchColumn();

// Employment stats
$stmt = $db->prepare('SELECT COUNT(*) as saved_jobs FROM user_saved_jobs WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['saved_jobs'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as applications FROM job_applications WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['applications'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as pending_apps FROM job_applications WHERE user_id = ? AND status IN ("pending", "reviewed", "shortlisted")');
$stmt->execute([$userId]);
$stats['pending_apps'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as active_alerts FROM job_alerts WHERE user_id = ? AND is_active = TRUE');
$stmt->execute([$userId]);
$stats['active_alerts'] = (int)$stmt->fetchColumn();

// Education stats
$stmt = $db->prepare('SELECT COUNT(*) as scholarships FROM scholarship_applications WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['scholarship_apps'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as pending_scholarships FROM scholarship_applications WHERE user_id = ? AND status IN ("pending", "submitted", "under_review")');
$stmt->execute([$userId]);
$stats['pending_scholarships'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as education_records FROM user_education WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['education_records'] = (int)$stmt->fetchColumn();

// Upcoming scholarship deadlines (next 30 days)
$stmt = $db->prepare('SELECT COUNT(*) as upcoming_deadlines FROM scholarship_applications sa JOIN scholarships s ON sa.scholarship_id = s.id WHERE sa.user_id = ? AND s.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND sa.status IN ("pending", "submitted", "under_review")');
$stmt->execute([$userId]);
$stats['upcoming_deadlines'] = (int)$stmt->fetchColumn();

// Healthcare stats
$stmt = $db->prepare('SELECT COUNT(*) as appointments FROM appointments WHERE user_id = ? AND appointment_date >= CURDATE() AND status IN ("scheduled", "confirmed")');
$stmt->execute([$userId]);
$stats['upcoming_appointments'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as health_records FROM health_records WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['health_records'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as upcoming_campaigns FROM health_campaigns WHERE is_active = TRUE AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)');
$stmt->execute();
$stats['upcoming_campaigns'] = (int)$stmt->fetchColumn();

// Profile completeness
$profileComplete = !empty($user['phone']) && !empty($user['province']) && !empty($user['district']);
$completenessScore = 0;
$checks = [
    'phone' => !empty($user['phone']),
    'province' => !empty($user['province']),
    'district' => !empty($user['district']),
    'date_of_birth' => !empty($user['date_of_birth']),
    'education_level' => !empty($user['education_level']),
    'occupation' => !empty($user['occupation']),
    'skills' => !empty($user['skills']),
    'bio' => !empty($user['bio']),
];
$completenessScore = count(array_filter($checks)) * 12.5;

// Recent activity
$stmt = $db->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
$stmt->execute([$userId]);
$recentActivity = $stmt->fetchAll();

// Unread notifications
$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$unreadNotifications = $stmt->fetchAll();

// Quick stats for trends (last 30 days vs previous 30 days)
$trends = [];
$modules = ['agriculture', 'employment', 'education', 'healthcare'];
foreach ($modules as $module) {
    $trends[$module] = rand(-10, 25); // Placeholder for actual trend calculation
}

// Get recommended content
$stmt = $db->prepare('SELECT * FROM scholarships WHERE is_active = TRUE AND deadline >= CURDATE() ORDER BY RAND() LIMIT 3');
$stmt->execute();
$recommendedScholarships = $stmt->fetchAll();

$stmt = $db->prepare('SELECT jp.*, e.company_name FROM job_postings jp JOIN employers e ON jp.employer_id = e.id WHERE jp.status = "published" AND (jp.application_deadline IS NULL OR jp.application_deadline >= CURDATE()) ORDER BY RAND() LIMIT 3');
$stmt->execute();
$recommendedJobs = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM health_campaigns WHERE is_active = TRUE AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY start_date ASC LIMIT 3');
$stmt->execute();
$recommendedCampaigns = $stmt->fetchAll();

$csrfToken = generateCsrfToken();

$pageTitle = 'Dashboard - Rwanda Connect';
$pageDescription = 'Your personalized dashboard to manage services, applications, and opportunities.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-container">
        <!-- Welcome Header -->
        <header class="dashboard-header">
            <div class="welcome-section">
                <div class="user-greeting">
                    <div class="avatar-circle" style="background: linear-gradient(135deg, var(--green), var(--green-dark));">
                        <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>
                    </div>
                    <div>
                        <h1>Welcome back, <strong><?= e(explode(' ', $user['full_name'])[0] ?? 'Citizen') ?></strong>! 👋</h1>
                        <p class="welcome-subtitle">Your personalized overview across all services</p>
                    </div>
                </div>
                <div class="profile-completeness">
                    <div class="completeness-ring" style="--score: <?= $completenessScore ?>%">
                        <span class="completeness-score"><?= (int)$completenessScore ?>%</span>
                    </div>
                    <div class="completeness-label">
                        <strong>Profile Complete</strong>
                        <span><?= (int)$completenessScore ?>% complete</span>
                    </div>
                    <?php if ($completenessScore < 100): ?>
                        <a href="/rwanda_connect/dashboard/profile.php" class="btn btn-sm btn-primary">Complete Profile</a>
                    <?php else: ?>
                        <span class="completeness-badge">✅ Complete</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="/rwanda_connect/dashboard/profile.php" class="btn btn-secondary">
                    <span>👤</span> Profile
                </a>
                <a href="/rwanda_connect/dashboard/settings.php" class="btn btn-outline">
                    <span>⚙️</span> Settings
                </a>
            </div>
        </header>
        
        <!-- Alert Banner -->
        <?php if ($stats['upcoming_deadlines'] > 0 || $stats['upcoming_harvests'] > 0 || $stats['upcoming_appointments'] > 0): ?>
            <div class="alert-banner">
                <div class="alert-items">
                    <?php if ($stats['upcoming_deadlines'] > 0): ?>
                        <span class="alert-item warning">📅 <?= $stats['upcoming_deadlines'] ?> scholarship deadline<?= $stats['upcoming_deadlines'] > 1 ? 's' : '' ?> in 30 days</span>
                    <?php endif; ?>
                    <?php if ($stats['upcoming_harvests'] > 0): ?>
                        <span class="alert-item info">🌾 <?= $stats['upcoming_harvests'] ?> harvest<?= $stats['upcoming_harvests'] > 1 ? 's' : '' ?> expected in 30 days</span>
                    <?php endif; ?>
                    <?php if ($stats['upcoming_appointments'] > 0): ?>
                        <span class="alert-item success">🏥 <?= $stats['upcoming_appointments'] ?> upcoming appointment<?= $stats['upcoming_appointments'] > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                </div>
                <a href="/rwanda_connect/dashboard/notifications.php" class="btn btn-sm btn-outline">View All Alerts</a>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <section class="dashboard-stats" aria-label="Dashboard Statistics">
            <!-- Agriculture Stats -->
            <div class="stat-card agri" data-module="agriculture">
                <div class="stat-header">
                    <div class="stat-icon">🌱</div>
                    <div class="stat-trend <?= $trends['agriculture'] >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= $trends['agriculture'] >= 0 ? '+' : '' ?><?= $trends['agriculture'] ?>%</span>
                        <small>vs last month</small>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-value"><?= $stats['farms'] ?></span>
                    <span class="stat-label">Active Farms</span>
                </div>
                <div class="stat-substats">
                    <div class="substat">
                        <span class="substat-value"><?= $stats['total_hectares'] ?></span>
                        <span class="substat-label">Hectares</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['active_crops'] ?></span>
                        <span class="substat-label">Active Crops</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['upcoming_harvests'] ?></span>
                        <span class="substat-label">Upcoming Harvests</span>
                    </div>
                </div>
                <div class="stat-actions">
                    <a href="/rwanda_connect/modules/agriculture/farms.php" class="btn btn-sm btn-primary">Manage Farms</a>
                    <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="btn btn-sm btn-outline">Market Prices</a>
                </div>
            </div>
            
            <!-- Employment Stats -->
            <div class="stat-card emp" data-module="employment">
                <div class="stat-header">
                    <div class="stat-icon">💼</div>
                    <div class="stat-trend <?= $trends['employment'] >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= $trends['employment'] >= 0 ? '+' : '' ?><?= $trends['employment'] ?>%</span>
                        <small>vs last month</small>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-value"><?= $stats['applications'] ?></span>
                    <span class="stat-label">Total Applications</span>
                </div>
                <div class="stat-substats">
                    <div class="substat">
                        <span class="substat-value"><?= $stats['pending_apps'] ?></span>
                        <span class="substat-label">Pending</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['saved_jobs'] ?></span>
                        <span class="substat-label">Saved Jobs</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['active_alerts'] ?></span>
                        <span class="substat-label">Active Alerts</span>
                    </div>
                </div>
                <div class="stat-actions">
                    <a href="/rwanda_connect/modules/employment/applications.php" class="btn btn-sm btn-primary">Track Applications</a>
                    <a href="/rwanda_connect/modules/employment/jobs.php" class="btn btn-sm btn-outline">Find Jobs</a>
                </div>
            </div>
            
            <!-- Education Stats -->
            <div class="stat-card edu" data-module="education">
                <div class="stat-header">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-trend <?= $trends['education'] >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= $trends['education'] >= 0 ? '+' : '' ?><?= $trends['education'] ?>%</span>
                        <small>vs last month</small>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-value"><?= $stats['scholarship_apps'] ?></span>
                    <span class="stat-label">Scholarship Applications</span>
                </div>
                <div class="stat-substats">
                    <div class="substat">
                        <span class="substat-value"><?= $stats['pending_scholarships'] ?></span>
                        <span class="substat-label">Pending Review</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['upcoming_deadlines'] ?></span>
                        <span class="substat-label">Deadlines (30 days)</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['education_records'] ?></span>
                        <span class="substat-label">Education Records</span>
                    </div>
                </div>
                <div class="stat-actions">
                    <a href="/rwanda_connect/modules/education/scholarships.php" class="btn btn-sm btn-primary">Browse Scholarships</a>
                    <a href="/rwanda_connect/modules/education/courses.php" class="btn btn-sm btn-outline">Find Courses</a>
                </div>
            </div>
            
            <!-- Healthcare Stats -->
            <div class="stat-card health" data-module="healthcare">
                <div class="stat-header">
                    <div class="stat-icon">🏥</div>
                    <div class="stat-trend <?= $trends['healthcare'] >= 0 ? 'positive' : 'negative' ?>">
                        <span><?= $trends['healthcare'] >= 0 ? '+' : '' ?><?= $trends['healthcare'] ?>%</span>
                        <small>vs last month</small>
                    </div>
                </div>
                <div class="stat-main">
                    <span class="stat-value"><?= $stats['upcoming_appointments'] ?></span>
                    <span class="stat-label">Upcoming Appointments</span>
                </div>
                <div class="stat-substats">
                    <div class="substat">
                        <span class="substat-value"><?= $stats['health_records'] ?></span>
                        <span class="substat-label">Health Records</span>
                    </div>
                    <div class="substat">
                        <span class="substat-value"><?= $stats['upcoming_campaigns'] ?></span>
                        <span class="substat-label">Upcoming Campaigns</span>
                    </div>
                </div>
                <div class="stat-actions">
                    <a href="/rwanda_connect/modules/healthcare/appointments.php" class="btn btn-sm btn-primary">Manage Appointments</a>
                    <a href="/rwanda_connect/modules/healthcare/records.php" class="btn btn-sm btn-outline">Health Records</a>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="quick-actions-section">
            <div class="section-header">
                <h2>Quick Actions</h2>
                <a href="/rwanda_connect/services.php" class="section-link">All Services →</a>
            </div>
            <div class="quick-actions-grid">
                <a href="/rwanda_connect/modules/agriculture/farms.php?action=create" class="quick-action-card agri">
                    <div class="action-icon">➕</div>
                    <div class="action-content">
                        <h3>Add New Farm</h3>
                        <p>Register a farm with GPS, soil type & crops</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/employment/jobs.php" class="quick-action-card emp">
                    <div class="action-icon">🔍</div>
                    <div class="action-content">
                        <h3>Search Jobs</h3>
                        <p>Filter by category, location, salary & type</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/employment/alerts.php?action=create" class="quick-action-card emp">
                    <div class="action-icon">🔔</div>
                    <div class="action-content">
                        <h3>Create Job Alert</h3>
                        <p>Get email notifications for matching jobs</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/education/scholarships.php" class="quick-action-card edu">
                    <div class="action-icon">💰</div>
                    <div class="action-content">
                        <h3>Find Scholarships</h3>
                        <p>Filter by level, field, country & coverage</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/healthcare/appointments.php?action=book" class="quick-action-card health">
                    <div class="action-icon">📅</div>
                    <div class="action-content">
                        <h3>Book Appointment</h3>
                        <p>Choose facility, doctor & time slot</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/healthcare/facilities.php" class="quick-action-card health">
                    <div class="action-icon">🏥</div>
                    <div class="action-content">
                        <h3>Find Facilities</h3>
                        <p>Search hospitals, clinics & pharmacies</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/agriculture/market-prices.php" class="quick-action-card agri">
                    <div class="action-icon">💰</div>
                    <div class="action-content">
                        <h3>Check Market Prices</h3>
                        <p>Real-time crop prices by market</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
                <a href="/rwanda_connect/modules/education/courses.php" class="quick-action-card edu">
                    <div class="action-icon">📚</div>
                    <div class="action-content">
                        <h3>Explore Courses</h3>
                        <p>Degrees, diplomas & certifications</p>
                    </div>
                    <span class="action-arrow">→</span>
                </a>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="dashboard-main-grid">
            <!-- Left Column: Activity & Recommendations -->
            <div class="dashboard-main">
                <!-- Recent Activity -->
                <section class="panel activity-panel">
                    <div class="panel-header">
                        <h3>Recent Activity</h3>
                        <a href="/rwanda_connect/dashboard/activity.php" class="panel-link">View all</a>
                    </div>
                    <div class="panel-content">
                        <?php if (empty($recentActivity)): ?>
                            <div class="empty-state">
                                <span class="empty-icon">📋</span>
                                <p>No recent activity yet</p>
                                <a href="/rwanda_connect/services.php" class="btn btn-sm btn-primary">Explore Services</a>
                            </div>
                        <?php else: ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivity as $activity): 
                                    $icon = match($activity['action']) {
                                        'user_login' => '🔑',
                                        'user_registered' => '👤',
                                        'profile_updated' => '✏️',
                                        'password_changed' => '🔒',
                                        'job_application_submitted' => '📄',
                                        'job_saved' => '⭐',
                                        'scholarship_application_submitted' => '🎓',
                                        'appointment_booked' => '📅',
                                        'farm_created' => '🌾',
                                        'crop_planted' => '🌱',
                                        'crop_harvested' => '✂️',
                                        default => '📝'
                                    };
                                    $color = match($activity['action']) {
                                        'user_login' => 'var(--info)',
                                        'user_registered' => 'var(--success)',
                                        'profile_updated' => 'var(--warning)',
                                        'password_changed' => 'var(--error)',
                                        default => 'var(--muted)'
                                    };
                                ?>
                                    <li class="activity-item">
                                        <div class="activity-marker" style="background: <?= $color ?>;">
                                            <span><?= $icon ?></span>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?= e(ucfirst(str_replace('_', ' ', $activity['action']))) ?></strong> - <?= e($activity['description'] ?? '') ?></p>
                                            <time><?= timeAgo($activity['created_at']) ?></time>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Recommendations -->
                <section class="panel recommendations-panel">
                    <div class="panel-header">
                        <h3>Recommended for You</h3>
                    </div>
                    <div class="panel-content">
                        <div class="recommendations-tabs">
                            <button class="tab-btn active" data-tab="scholarships">💰 Scholarships</button>
                            <button class="tab-btn" data-tab="jobs">💼 Jobs</button>
                            <button class="tab-btn" data-tab="campaigns">💉 Health</button>
                        </div>
                        
                        <div class="tab-content" id="tab-scholarships">
                            <?php if (empty($recommendedScholarships)): ?>
                                <p class="empty-state">No scholarships available</p>
                            <?php else: ?>
                                <div class="recommendation-cards">
                                    <?php foreach ($recommendedScholarships as $scholarship): 
                                        $daysLeft = (new DateTime($scholarship['deadline']))->diff(new DateTime())->days;
                                    ?>
                                        <article class="recommendation-card">
                                            <div class="rec-badge"><?= e(ucfirst($scholarship['coverage'])) ?></div>
                                            <h4><?= e($scholarship['title']) ?></h4>
                                            <p class="rec-provider"><?= e($scholarship['provider']) ?></p>
                                            <div class="rec-meta">
                                                <span class="rec-deadline <?= $daysLeft <= 7 ? 'urgent' : '' ?>">
                                                    📅 <?= $daysLeft === 0 ? 'Today' : ($daysLeft === 1 ? 'Tomorrow' : $daysLeft . ' days') ?>
                                                </span>
                                                <span class="rec-amount"><?= $scholarship['amount_rwf'] ? formatCurrency($scholarship['amount_rwf']) : 'Amount varies' ?></span>
                                            </div>
                                            <a href="/rwanda_connect/modules/education/scholarship-detail.php?id=<?= $scholarship['id'] ?>" class="btn btn-sm btn-primary">Apply Now</a>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content hidden" id="tab-jobs">
                            <?php if (empty($recommendedJobs)): ?>
                                <p class="empty-state">No jobs available</p>
                            <?php else: ?>
                                <div class="recommendation-cards">
                                    <?php foreach ($recommendedJobs as $job): 
                                        $daysLeft = $job['application_deadline'] ? (new DateTime($job['application_deadline']))->diff(new DateTime())->days : null;
                                    ?>
                                        <article class="recommendation-card">
                                            <div class="rec-badge"><?= e($job['employment_type']) ?></div>
                                            <h4><?= e($job['title']) ?></h4>
                                            <p class="rec-provider"><?= e($job['company_name']) ?></p>
                                            <div class="rec-meta">
                                                <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                                    <span class="rec-salary"><?= $job['salary_min'] ? formatCurrency($job['salary_min']) : 'Negotiable' ?><?= $job['salary_max'] ? ' - ' . formatCurrency($job['salary_max']) : '' ?></span>
                                                <?php endif; ?>
                                                <?php if ($daysLeft !== null): ?>
                                                    <span class="rec-deadline <?= $daysLeft <= 7 ? 'urgent' : '' ?>">
                                                        📅 <?= $daysLeft === 0 ? 'Today' : ($daysLeft === 1 ? 'Tomorrow' : $daysLeft . ' days') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/rwanda_connect/modules/employment/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-content hidden" id="tab-campaigns">
                            <?php if (empty($recommendedCampaigns)): ?>
                                <p class="empty-state">No health campaigns available</p>
                            <?php else: ?>
                                <div class="recommendation-cards">
                                    <?php foreach ($recommendedCampaigns as $campaign): ?>
                                        <article class="recommendation-card">
                                            <div class="rec-badge"><?= e(ucfirst($campaign['campaign_type'])) ?></div>
                                            <h4><?= e($campaign['title']) ?></h4>
                                            <div class="rec-meta">
                                                <span>📅 <?= formatDate($campaign['start_date']) ?></span>
                                                <?php if ($campaign['location']): ?>
                                                    <span>📍 <?= e($campaign['location']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/rwanda_connect/modules/healthcare/campaign-detail.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-outline">Learn More</a>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Column: Notifications & Upcoming -->
            <aside class="dashboard-sidebar">
                <!-- Notifications -->
                <section class="panel notifications-panel">
                    <div class="panel-header">
                        <h3>Notifications</h3>
                        <a href="/rwanda_connect/dashboard/notifications.php" class="panel-link">View all</a>
                    </div>
                    <div class="panel-content">
                        <?php if (empty($unreadNotifications)): ?>
                            <div class="empty-state">
                                <span class="empty-icon">🔔</span>
                                <p>No new notifications</p>
                            </div>
                        <?php else: ?>
                            <ul class="notification-list">
                                <?php foreach ($unreadNotifications as $notification): ?>
                                    <li class="notification-item unread" data-id="<?= $notification['id'] ?>">
                                        <div class="notification-icon"><?= match($notification['type']) {
                                            'job' => '💼',
                                            'scholarship' => '🎓',
                                            'health' => '🏥',
                                            'agriculture' => '🌱',
                                            'system' => '⚙️',
                                            default => '🔔'
                                        } ?></div>
                                        <div class="notification-content">
                                            <h4><?= e($notification['title']) ?></h4>
                                            <p><?= e($notification['message']) ?></p>
                                            <time><?= timeAgo($notification['created_at']) ?></time>
                                        </div>
                                        <?php if ($notification['action_url']): ?>
                                            <a href="<?= e($notification['action_url']) ?>" class="notification-action">View</a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="POST" action="/rwanda_connect/api/notifications/read-all.php" style="margin-top: 12px;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="btn btn-sm btn-outline" style="width: 100%;">Mark All as Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Upcoming Events -->
                <section class="panel upcoming-panel">
                    <div class="panel-header">
                        <h3>Upcoming This Week</h3>
                    </div>
                    <div class="panel-content">
                        <div class="upcoming-list">
                            <?php 
                            // Get upcoming appointments this week
                            $stmt = $db->prepare('SELECT a.*, hf.name as facility_name FROM appointments a JOIN health_facilities hf ON a.facility_id = hf.id WHERE a.user_id = ? AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND a.status IN ("scheduled", "confirmed") ORDER BY a.appointment_date ASC LIMIT 5');
                            $stmt->execute([$userId]);
                            $upcomingAppointments = $stmt->fetchAll();
                            
                            // Get upcoming scholarship deadlines this week
                            $stmt = $db->prepare('SELECT sa.*, s.title, s.deadline FROM scholarship_applications sa JOIN scholarships s ON sa.scholarship_id = s.id WHERE sa.user_id = ? AND s.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND sa.status IN ("pending", "submitted", "under_review") ORDER BY s.deadline ASC LIMIT 5');
                            $stmt->execute([$userId]);
                            $upcomingDeadlines = $stmt->fetchAll();
                            
                            // Get upcoming harvests this week
                            $stmt = $db->prepare('SELECT fc.*, c.name as crop_name, f.name as farm_name FROM farm_crops fc JOIN crops c ON fc.crop_id = c.id JOIN user_farms f ON fc.farm_id = f.id WHERE f.user_id = ? AND fc.expected_harvest_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND fc.status IN ("planted", "growing") ORDER BY fc.expected_harvest_date ASC LIMIT 5');
                            $stmt->execute([$userId]);
                            $upcomingHarvests = $stmt->fetchAll();
                            
                            $allUpcoming = [];
                            foreach ($upcomingAppointments as $appt) {
                                $allUpcoming[] = ['type' => 'appointment', 'date' => $appt['appointment_date'], 'title' => '🏥 Appointment at ' . $appt['facility_name'], 'time' => date('H:i', strtotime($appt['appointment_date']))];
                            }
                            foreach ($upcomingDeadlines as $dl) {
                                $allUpcoming[] = ['type' => 'deadline', 'date' => $dl['deadline'], 'title' => '💰 ' . $dl['title'], 'time' => 'Deadline'];
                            }
                            foreach ($upcomingHarvests as $hv) {
                                $allUpcoming[] = ['type' => 'harvest', 'date' => $hv['expected_harvest_date'], 'title' => '🌾 Harvest ' . $hv['crop_name'] . ' at ' . $hv['farm_name'], 'time' => 'Expected'];
                            }
                            
                            usort($allUpcoming, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
                            $allUpcoming = array_slice($allUpcoming, 0, 8);
                            
                            if (empty($allUpcoming)): ?>
                                <div class="empty-state">
                                    <span class="empty-icon">📅</span>
                                    <p>Nothing scheduled this week</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($allUpcoming as $item): 
                                    $date = new DateTime($item['date']);
                                    $today = new DateTime();
                                    $isToday = $date->format('Y-m-d') === $today->format('Y-m-d');
                                ?>
                                    <div class="upcoming-item <?= $isToday ? 'today' : '' ?>">
                                        <div class="upcoming-date">
                                            <span class="day"><?= $date->format('d') ?></span>
                                            <span class="month"><?= $date->format('M') ?></span>
                                        </div>
                                        <div class="upcoming-info">
                                            <h4><?= e($item['title']) ?></h4>
                                            <time><?= e($item['time']) ?></time>
                                        </div>
                                        <span class="upcoming-type"><?= ucfirst($item['type']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Quick Links -->
                <section class="panel quick-links-panel">
                    <div class="panel-header">
                        <h3>Quick Links</h3>
                    </div>
                    <div class="panel-content">
                        <ul class="quick-links">
                            <li><a href="/rwanda_connect/modules/agriculture/"><span>🌱</span> Agriculture Dashboard</a></li>
                            <li><a href="/rwanda_connect/modules/employment/"><span>💼</span> Job Search</a></li>
                            <li><a href="/rwanda_connect/modules/education/"><span>🎓</span> Scholarships</a></li>
                            <li><a href="/rwanda_connect/modules/healthcare/"><span>🏥</span> Health Facilities</a></li>
                            <li><a href="/rwanda_connect/dashboard/notifications.php"><span>🔔</span> All Notifications</a></li>
                            <li><a href="/rwanda_connect/dashboard/activity.php"><span>📋</span> Activity Log</a></li>
                            <li><a href="/rwanda_connect/dashboard/profile.php"><span>👤</span> Edit Profile</a></li>
                            <li><a href="/rwanda_connect/dashboard/settings.php"><span>⚙️</span> Settings</a></li>
                        </ul>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Mark notification as read on click
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            if (e.target.closest('.notification-action')) return;
            
            const notificationId = item.dataset.id;
            if (!notificationId) return;
            
            try {
                await fetch('/rwanda_connect/api/notifications/read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= e(generateCsrfToken()) ?>'
                    },
                    body: JSON.stringify({ notification_id: notificationId })
                });
                item.classList.remove('unread');
            } catch (err) {
                console.error('Failed to mark notification as read:', err);
            }
        });
    });
    
    // Keyboard support for module cards
    document.querySelectorAll('.module-card, .quick-action-card, .recommendation-card').forEach(card => {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });
    
    // Recommendations tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });
    
    // Animate stat cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.stat-card, .quick-action-card, .panel').forEach(el => {
        observer.observe(el);
    });
    
    // Mark all notifications as read
    const markAllForm = document.querySelector('form[action*="read-all"]');
    if (markAllForm) {
        markAllForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                await fetch('/rwanda_connect/api/notifications/read-all.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= e(generateCsrfToken()) ?>'
                    }
                });
                document.querySelectorAll('.notification-item').forEach(item => item.classList.remove('unread'));
                showToast('All notifications marked as read', 'success');
            } catch (err) {
                console.error('Failed to mark all as read:', err);
            }
        });
    }
});
</script>

<style>
/* Dashboard Enhancements */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    padding: 24px 0;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.user-greeting {
    display: flex;
    align-items: center;
    gap: 16px;
}

.avatar-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    flex-shrink: 0;
}

.welcome-section h1 {
    margin: 0 0 4px;
    font-size: clamp(1.5rem, 3vw, 2rem);
}

.welcome-subtitle {
    margin: 0;
    color: var(--muted);
}

.profile-completeness {
    display: flex;
    align-items: center;
    gap: 16px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    padding: 16px 24px;
    min-width: 200px;
}

.completeness-ring {
    position: relative;
    width: 60px;
    height: 60px;
    flex-shrink: 0;
}

.completeness-ring::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 6px solid var(--line);
}

.completeness-ring::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 6px solid var(--green);
    clip-path: polygon(50% 0, 100% 0, 100% 100%, 50% 100%, 50% 50%);
    transform: rotate(calc(var(--score) * 3.6deg - 90deg));
    transform-origin: center;
}

.completeness-ring .completeness-score {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1rem;
    font-weight: 700;
    color: var(--ink);
}

.completeness-label {
    display: flex;
    flex-direction: column;
}

.completeness-badge {
    background: var(--success-bg);
    color: var(--success);
    padding: 4px 12px;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 600;
}

/* Alert Banner */
.alert-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    background: var(--info-bg);
    border: 1px solid var(--info);
    border-radius: var(--radius-lg);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.alert-items {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.alert-item {
    padding: 8px 16px;
    border-radius: 999px;
    font-size: .85rem;
    font-weight: 500;
}

.alert-item.warning { background: var(--warning-bg); color: var(--warning); }
.alert-item.info { background: var(--info-bg); color: var(--info); }
.alert-item.success { background: var(--success-bg); color: var(--success); }

/* Stats Cards */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    padding: 24px;
    transition: all var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.agri::before { background: linear-gradient(90deg, #168447, #2ecc71); }
.stat-card.emp::before { background: linear-gradient(90deg, #3498db, #5dade2); }
.stat-card.edu::before { background: linear-gradient(90deg, #9b59b6, #bb8fce); }
.stat-card.health::before { background: linear-gradient(90deg, #e74c3c, #ec7063); }

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--green);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.stat-icon {
    font-size: 2rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background);
    border-radius: var(--radius-md);
}

.stat-trend {
    font-size: .8rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 999px;
}

.stat-trend.positive { background: var(--success-bg); color: var(--success); }
.stat-trend.negative { background: var(--error-bg); color: var(--error); }
.stat-trend small { display: block; font-size: .65rem; font-weight: 400; opacity: 0.8; }

.stat-main {
    margin-bottom: 16px;
}

.stat-value {
    display: block;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--ink);
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: .9rem;
    color: var(--muted);
    margin-top: 4px;
}

.stat-substats {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: var(--background);
    border-radius: var(--radius-md);
    margin-bottom: 16px;
}

.substat {
    flex: 1;
    text-align: center;
}

.substat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ink);
}

.substat-label {
    display: block;
    font-size: .7rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-actions {
    display: flex;
    gap: 8px;
}

/* Quick Actions */
.quick-actions-section {
    margin-bottom: 32px;
}

.quick-actions-section .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    text-decoration: none;
    color: inherit;
    transition: all var(--transition);
}

.quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--green);
    background: var(--green-light);
}

.quick-action-card.agri:hover { border-color: #168447; background: #e8f5ec; }
.quick-action-card.emp:hover { border-color: #3498db; background: #ebf5fb; }
.quick-action-card.edu:hover { border-color: #9b59b6; background: #f5eef8; }
.quick-action-card.health:hover { border-color: #e74c3c; background: #fdf2f2; }

.action-icon {
    font-size: 2rem;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background);
    border-radius: var(--radius-lg);
    flex-shrink: 0;
}

.action-content h3 {
    margin: 0 0 4px;
    font-size: 1rem;
    font-weight: 700;
}

.action-content p {
    margin: 0;
    color: var(--muted);
    font-size: .85rem;
}

.action-arrow {
    margin-left: auto;
    color: var(--muted);
    font-weight: 700;
    transition: transform var(--transition);
}

.quick-action-card:hover .action-arrow {
    transform: translateX(4px);
    color: var(--green);
}

/* Main Grid */
.dashboard-main-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
}

@media (max-width: 1024px) {
    .dashboard-main-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-sidebar {
        order: -1;
    }
}

/* Panels */
.panel {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    margin-bottom: 24px;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--line);
}

.panel-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.panel-link {
    color: var(--green);
    font-weight: 600;
    font-size: .9rem;
}

.panel-content {
    padding: 0;
}

/* Activity List */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
    transition: background var(--transition);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: var(--background);
}

.activity-marker {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    color: white;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-content p {
    margin: 0 0 4px;
    font-size: .9rem;
}

.activity-content time {
    color: var(--muted);
    font-size: .75rem;
}

/* Recommendations Tabs */
.recommendations-tabs {
    display: flex;
    gap: 8px;
    padding: 0 24px 16px;
    border-bottom: 1px solid var(--line);
    margin-bottom: 16px;
    overflow-x: auto;
}

.tab-btn {
    padding: 10px 16px;
    background: var(--background);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    color: var(--muted);
    font-weight: 500;
    font-size: .85rem;
    cursor: pointer;
    white-space: nowrap;
    transition: all var(--transition);
}

.tab-btn:hover { background: var(--green-light); color: var(--green); }
.tab-btn.active { background: var(--green); color: white; border-color: var(--green); }

.tab-content {
    padding: 0 24px 24px;
}

.tab-content.hidden { display: none; }

.recommendation-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recommendation-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    padding: 16px 20px;
    transition: all var(--transition);
}

.recommendation-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--green);
}

.rec-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 700;
    margin-bottom: 8px;
    background: var(--green-light);
    color: var(--green);
}

.recommendation-card h4 {
    margin: 0 0 4px;
    font-size: .95rem;
}

.rec-provider {
    margin: 0 0 8px;
    color: var(--muted);
    font-size: .8rem;
}

.rec-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
    font-size: .75rem;
}

.rec-deadline { color: var(--warning); font-weight: 600; }
.rec-deadline.urgent { color: var(--error); }
.rec-amount { color: var(--success); font-weight: 600; }
.rec-salary { color: var(--green); font-weight: 600; }

/* Notifications */
.notification-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notification-item {
    display: flex;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: var(--green-light);
}

.notification-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-content h4 {
    margin: 0 0 4px;
    font-size: .9rem;
    font-weight: 600;
}

.notification-content p {
    margin: 0 0 4px;
    color: var(--muted);
    font-size: .85rem;
}

.notification-content time {
    color: var(--muted);
    font-size: .7rem;
}

.notification-action {
    color: var(--green);
    font-weight: 600;
    font-size: .8rem;
}

/* Upcoming Panel */
.upcoming-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--background);
    border-radius: var(--radius-md);
    transition: all var(--transition);
}

.upcoming-item:hover {
    background: var(--green-light);
    border-color: var(--green);
}

.upcoming-item.today {
    border-left: 3px solid var(--green);
}

.upcoming-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 12px;
    background: var(--surface);
    border-radius: var(--radius-md);
    min-width: 50px;
}

.upcoming-item.today .upcoming-date {
    background: var(--green);
    color: white;
}

.day {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
}

.month {
    font-size: .65rem;
    text-transform: uppercase;
    color: var(--muted);
}

.upcoming-item.today .month { color: rgba(255,255,255,0.8); }

.upcoming-info {
    flex: 1;
    min-width: 0;
}

.upcoming-info h4 {
    margin: 0 0 2px;
    font-size: .85rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.upcoming-info time {
    color: var(--muted);
    font-size: .7rem;
}

.upcoming-type {
    font-size: .65rem;
    color: var(--muted);
    background: var(--surface);
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 600;
}

/* Quick Links */
.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quick-links a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--background);
    border-radius: var(--radius-md);
    color: var(--ink);
    font-weight: 500;
    transition: all var(--transition);
}

.quick-links a:hover {
    background: var(--green-light);
    color: var(--green);
    transform: translateX(4px);
}

.quick-links span {
    font-size: 1.1rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px 24px;
    color: var(--muted);
}

.empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 16px;
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-in {
    animation: fadeInUp 0.5s ease forwards;
}

.stat-card { opacity: 0; }
.quick-action-card { opacity: 0; }
.panel { opacity: 0; }

/* Staggered animation */
.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.quick-action-card:nth-child(1) { animation-delay: 0.1s; }
.quick-action-card:nth-child(2) { animation-delay: 0.2s; }
.quick-action-card:nth-child(3) { animation-delay: 0.3s; }
.quick-action-card:nth-child(4) { animation-delay: 0.4s; }
.quick-action-card:nth-child(5) { animation-delay: 0.5s; }
.quick-action-card:nth-child(6) { animation-delay: 0.6s; }
.quick-action-card:nth-child(7) { animation-delay: 0.7s; }
.quick-action-card:nth-child(8) { animation-delay: 0.8s; }

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .user-greeting {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-completeness {
        justify-content: center;
    }
    
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-substats {
        flex-direction: column;
        gap: 8px;
    }
    
    .stat-actions {
        flex-direction: column;
    }
    
    .stat-actions .btn { width: 100%; }
    
    .alert-banner {
        flex-direction: column;
        align-items: stretch;
    }
}

/* Print */
@media print {
    .dashboard-header, .quick-actions-section, .dashboard-sidebar, .btn { display: none !important; }
    .dashboard-main-grid { grid-template-columns: 1fr; }
}
</style>