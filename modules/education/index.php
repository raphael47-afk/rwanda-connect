<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Education Module Home
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// Get user stats
$stmt = $db->prepare('SELECT COUNT(*) as count FROM scholarship_applications WHERE user_id = ?');
$stmt->execute([$userId]);
$scholarshipAppsCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM user_education WHERE user_id = ?');
$stmt->execute([$userId]);
$educationRecordsCount = (int)$stmt->fetchColumn();

// Get active scholarships
$stmt = $db->query('
    SELECT * FROM scholarships 
    WHERE is_active = TRUE AND deadline >= CURDATE()
    ORDER BY deadline ASC
    LIMIT 6
');
$activeScholarships = $stmt->fetchAll();

// Get user's scholarship applications
$stmt = $db->prepare('
    SELECT sa.*, s.title, s.provider, s.deadline, s.coverage
    FROM scholarship_applications sa
    JOIN scholarships s ON sa.scholarship_id = s.id
    WHERE sa.user_id = ?
    ORDER BY sa.applied_at DESC
    LIMIT 4
');
$stmt->execute([$userId]);
$userScholarshipApps = $stmt->fetchAll();

// Get user education
$stmt = $db->prepare('
    SELECT ue.*, i.name as institution_name
    FROM user_education ue
    LEFT JOIN institutions i ON ue.institution_id = i.id
    WHERE ue.user_id = ?
    ORDER BY ue.is_current DESC, ue.end_year DESC
');
$stmt->execute([$userId]);
$userEducation = $stmt->fetchAll();

// Get institutions
$stmt = $db->query('SELECT * FROM institutions WHERE is_accredited = TRUE ORDER BY name LIMIT 8');
$institutions = $stmt->fetchAll();

// Get featured courses
$stmt = $db->query('
    SELECT c.*, i.name as institution_name
    FROM courses c
    JOIN institutions i ON c.institution_id = i.id
    WHERE c.is_active = TRUE
    ORDER BY c.created_at DESC
    LIMIT 6
');
$featuredCourses = $stmt->fetchAll();

$pageTitle = 'Education - Rwanda Connect';
$pageDescription = 'Explore scholarships, courses, institutions, and manage your education profile.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page education-page">
    <div class="module-container">
        <!-- Module Header -->
        <header class="module-header">
            <div class="module-brand">
                <span class="module-icon">🎓</span>
                <div>
                    <h1>Education</h1>
                    <p>Scholarships, courses, institutions, and learning opportunities</p>
                </div>
            </div>
            <div class="module-actions">
                <a href="/rwanda_connect/modules/education/scholarships.php" class="btn btn-primary">💰 Browse Scholarships</a>
                <a href="/rwanda_connect/modules/education/courses.php" class="btn btn-secondary">📚 Browse Courses</a>
            </div>
        </header>
        
        <!-- Stats Overview -->
        <section class="module-stats" aria-label="Education Statistics">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($activeScholarships) ?></span>
                    <span class="stat-label">Active Scholarships</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $scholarshipAppsCount ?></span>
                    <span class="stat-label">Applications</span>
                </div>
                <a href="/rwanda_connect/modules/education/scholarships.php?tab=applications" class="stat-link">Track →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-content">
                    <span class="stat-value"><?= $educationRecordsCount ?></span>
                    <span class="stat-label">Education Records</span>
                </div>
                <a href="/rwanda_connect/modules/education/profile.php" class="stat-link">View →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏫</div>
                <div class="stat-content">
                    <span class="stat-value"><?= count($institutions) ?></span>
                    <span class="stat-label">Institutions</span>
                </div>
                <a href="/rwanda_connect/modules/education/institutions.php" class="stat-link">Explore →</a>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="module-section actions-section">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <a href="/rwanda_connect/modules/education/scholarships.php" class="action-card">
                    <span class="action-icon">💰</span>
                    <h3>Find Scholarships</h3>
                    <p>Browse and apply for scholarships</p>
                </a>
                <a href="/rwanda_connect/modules/education/courses.php" class="action-card">
                    <span class="action-icon">📚</span>
                    <h3>Explore Courses</h3>
                    <p>Find programs and degrees</p>
                </a>
                <a href="/rwanda_connect/modules/education/institutions.php" class="action-card">
                    <span class="action-icon">🏫</span>
                    <h3>Institutions</h3>
                    <p>Universities, colleges, TVETs</p>
                </a>
                <a href="/rwanda_connect/modules/education/profile.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <h3>My Education</h3>
                    <p>Manage your education profile</p>
                </a>
                <a href="/rwanda_connect/modules/education/scholarships.php?tab=applications" class="action-card">
                    <span class="action-icon">📄</span>
                    <h3>My Applications</h3>
                    <p>Track scholarship applications</p>
                </a>
                <a href="/rwanda_connect/modules/education/resources.php" class="action-card">
                    <span class="action-icon">📖</span>
                    <h3>Learning Resources</h3>
                    <p>Study guides, tips, and tools</p>
                </a>
            </div>
        </section>
        
        <!-- Featured Scholarships -->
        <section class="module-section">
            <div class="section-header">
                <h2>Featured Scholarships</h2>
                <a href="/rwanda_connect/modules/education/scholarships.php" class="section-link">View All →</a>
            </div>
            
            <?php if (!empty($activeScholarships)): ?>
                <div class="scholarships-grid">
                    <?php foreach ($activeScholarships as $scholarship): 
                        $daysLeft = (new DateTime($scholarship['deadline']))->diff(new DateTime())->days;
                    ?>
                        <article class="scholarship-card">
                            <div class="scholarship-header">
                                <span class="scholarship-coverage"><?= e(ucfirst($scholarship['coverage'])) ?></span>
                                <span class="scholarship-deadline <?= $daysLeft <= 7 ? 'urgent' : '' ?>">
                                    📅 <?= $daysLeft ?> days left
                                </span>
                            </div>
                            <h3><?= e($scholarship['title']) ?></h3>
                            <p class="scholarship-provider">🏢 <?= e($scholarship['provider']) ?></p>
                            <p class="scholarship-description"><?= e(substr($scholarship['description'], 0, 150)) ?>...</p>
                            <div class="scholarship-meta">
                                <span class="scholarship-level"><?= e(ucfirst(str_replace('_', ' ', $scholarship['target_level']))) ?></span>
                                <?php if ($scholarship['amount_rwf']): ?>
                                    <span class="scholarship-amount"><?= formatCurrency($scholarship['amount_rwf']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="/rwanda_connect/modules/education/scholarship-detail.php?id=<?= $scholarship['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-inline">
                    <span class="empty-icon">💰</span>
                    <p>No active scholarships at the moment</p>
                    <a href="/rwanda_connect/modules/education/scholarships.php" class="btn btn-primary">Browse All</a>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- My Applications & Education -->
        <div class="module-grid">
            <!-- My Scholarship Applications -->
            <section class="module-section">
                <div class="section-header">
                    <h2>My Applications</h2>
                    <a href="/rwanda_connect/modules/education/scholarships.php?tab=applications" class="section-link">View All →</a>
                </div>
                
                <?php if (!empty($userScholarshipApps)): ?>
                    <div class="applications-list">
                        <?php foreach ($userScholarshipApps as $app): ?>
                            <article class="application-item">
                                <div class="application-info">
                                    <h4><?= e($app['title']) ?></h4>
                                    <p class="scholarship-provider"><?= e($app['provider']) ?></p>
                                </div>
                                <div class="application-status">
                                    <span class="status-badge status-<?= $app['status'] ?>">
                                        <?= e(ucfirst(str_replace('_', ' ', $app['status']))) ?>
                                    </span>
                                    <span class="application-date">Deadline: <?= formatDate($app['deadline']) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">📄</span>
                        <p>No applications yet</p>
                        <a href="/rwanda_connect/modules/education/scholarships.php" class="btn btn-sm btn-primary">Apply Now</a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- My Education -->
            <section class="module-section">
                <div class="section-header">
                    <h2>My Education</h2>
                    <a href="/rwanda_connect/modules/education/profile.php" class="section-link">Manage →</a>
                </div>
                
                <?php if (!empty($userEducation)): ?>
                    <div class="education-list">
                        <?php foreach ($userEducation as $edu): ?>
                            <article class="education-item">
                                <div class="education-info">
                                    <h4><?= e($edu['qualification']) ?></h4>
                                    <?php if ($edu['field_of_study']): ?>
                                        <p class="education-field"><?= e($edu['field_of_study']) ?></p>
                                    <?php endif; ?>
                                    <p class="education-institution"><?= e($edu['institution_name'] ?? 'Institution not specified') ?></p>
                                </div>
                                <div class="education-dates">
                                    <span><?= $edu['start_year'] ? e($edu['start_year']) : 'N/A' ?> - <?= $edu['is_current'] ? 'Present' : ($edu['end_year'] ? e($edu['end_year']) : 'N/A') ?></span>
                                    <?php if ($edu['is_current']): ?>
                                        <span class="current-badge">Current</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-inline">
                        <span class="empty-icon">🎓</span>
                        <p>No education records yet</p>
                        <a href="/rwanda_connect/modules/education/profile.php" class="btn btn-sm btn-primary">Add Education</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Featured Institutions -->
        <section class="module-section">
            <div class="section-header">
                <h2>Featured Institutions</h2>
                <a href="/rwanda_connect/modules/education/institutions.php" class="section-link">View All →</a>
            </div>
            
            <div class="institutions-grid">
                <?php foreach ($institutions as $inst): ?>
                    <article class="institution-card">
                        <?php if ($inst['logo']): ?>
                            <img src="<?= e($inst['logo']) ?>" alt="" class="institution-logo">
                        <?php else: ?>
                            <div class="institution-logo placeholder"><?= e(strtoupper(substr($inst['name'], 0, 1))) ?></div>
                        <?php endif; ?>
                        <div class="institution-info">
                            <h3><?= e($inst['name']) ?></h3>
                            <p class="institution-type"><?= e(ucfirst(str_replace('_', ' ', $inst['type']))) ?></p>
                            <p class="institution-location">📍 <?= e($inst['province'] ?? 'Rwanda') ?><?= $inst['district'] ? ', ' . e($inst['district']) : '' ?></p>
                            <?php if ($inst['is_accredited']): ?>
                                <span class="accredited-badge">✓ Accredited</span>
                            <?php endif; ?>
                        </div>
                        <a href="/rwanda_connect/modules/education/institution-detail.php?id=<?= $inst['id'] ?>" class="btn btn-sm btn-outline">View Courses</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>