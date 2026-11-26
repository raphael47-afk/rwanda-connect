<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Employment: Job Detail
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDb();

if (!$jobId) {
    header('Location: /rwanda_connect/modules/employment/jobs.php');
    exit;
}

// Get job details
$stmt = $db->prepare('
    SELECT jp.*, e.company_name, e.company_description, e.website as company_website, e.logo, e.province as company_province, e.district as company_district, e.contact_email, e.contact_phone, jc.name as category_name, jc.icon as category_icon
    FROM job_postings jp
    JOIN employers e ON jp.employer_id = e.id
    LEFT JOIN job_categories jc ON jp.category_id = jc.id
    WHERE jp.id = ?
');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job || $job['status'] !== 'published') {
    header('Location: /rwanda_connect/modules/employment/jobs.php');
    exit;
}

// Increment view count
$stmt = $db->prepare('UPDATE job_postings SET views_count = views_count + 1 WHERE id = ?');
$stmt->execute([$jobId]);

// Check if saved
$stmt = $db->prepare('SELECT 1 FROM user_saved_jobs WHERE user_id = ? AND job_id = ?');
$stmt->execute([$userId, $jobId]);
$isSaved = (bool)$stmt->fetch();

// Check if already applied
$stmt = $db->prepare('SELECT * FROM job_applications WHERE user_id = ? AND job_id = ?');
$stmt->execute([$userId, $jobId]);
$existingApplication = $stmt->fetch();

// Handle application
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } elseif ($existingApplication) {
        $errors['general'] = 'You have already applied to this job.';
    } else {
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        
        // Handle resume upload
        $resumePath = null;
        if (!empty($_FILES['resume']['name'])) {
            $uploadDir = __DIR__ . '/../../../uploads/resumes';
            $result = handleFileUpload($_FILES['resume'], $uploadDir, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 5242880);
            if ($result['success']) {
                $resumePath = 'uploads/resumes/' . $result['filename'];
            } else {
                $errors['resume'] = $result['error'];
            }
        }
        
        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO job_applications (job_id, user_id, cover_letter, resume_path) VALUES (?, ?, ?, ?)');
            $stmt->execute([$jobId, $userId, $coverLetter, $resumePath]);
            
            // Update applications count
            $stmt = $db->prepare('UPDATE job_postings SET applications_count = applications_count + 1 WHERE id = ?');
            $stmt->execute([$jobId]);
            
            logActivity('job_application_submitted', "Applied to {$job['title']} at {$job['company_name']}", $userId);
            
            $success = 'Application submitted successfully!';
            $existingApplication = ['status' => 'pending', 'applied_at' => date('Y-m-d H:i:s')];
        }
    }
}

// Handle save/unsave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_job'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $action = $_POST['save_action'] ?? 'add';
        
        if ($action === 'add') {
            $stmt = $db->prepare('INSERT IGNORE INTO user_saved_jobs (user_id, job_id) VALUES (?, ?)');
            $stmt->execute([$userId, $jobId]);
            $isSaved = true;
        } else {
            $stmt = $db->prepare('DELETE FROM user_saved_jobs WHERE user_id = ? AND job_id = ?');
            $stmt->execute([$userId, $jobId]);
            $isSaved = false;
        }
    }
}

$csrfToken = generateCsrfToken();
$daysUntilDeadline = $job['application_deadline'] ? (new DateTime($job['application_deadline']))->diff(new DateTime())->days : null;

$pageTitle = e($job['title']) . ' - Employment - Rwanda Connect';
$pageDescription = 'View details and apply for ' . e($job['title']) . ' at ' . e($job['company_name']);

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page employment-page job-detail-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/employment/jobs.php" class="back-link">← Job Search</a>
                <div>
                    <h1><?= e($job['title']) ?></h1>
                    <p><?= e($job['company_name']) ?> • <?= e($job['category_icon'] ?? '💼') . ' ' . e($job['category_name'] ?? 'General') ?></p>
                </div>
            </div>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <div class="job-detail-layout">
            <!-- Main Content -->
            <div class="job-detail-main">
                <!-- Job Header -->
                <div class="job-detail-header">
                    <div class="company-info">
                        <?php if ($job['logo']): ?>
                            <img src="<?= e($job['logo']) ?>" alt="<?= e($job['company_name']) ?>" class="company-logo-large">
                        <?php else: ?>
                            <div class="company-logo-large placeholder"><?= e(strtoupper(substr($job['company_name'], 0, 1))) ?></div>
                        <?php endif; ?>
                        <div>
                            <span class="company-name-large"><?= e($job['company_name']) ?></span>
                            <div class="job-meta-detail">
                                <span class="job-category"><?= e($job['category_icon'] ?? '💼') . ' ' . e($job['category_name'] ?? 'General') ?></span>
                                <?php if ($job['featured_until'] && new DateTime($job['featured_until']) >= new DateTime()): ?>
                                    <span class="featured-badge">⭐ Featured</span>
                                <?php endif; ?>
                                <?php if ($job['is_remote']): ?>
                                    <span class="remote-badge">🏠 Remote</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="job-meta-grid">
                        <?php if ($job['province']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📍</span>
                                <div>
                                    <span class="meta-label">Location</span>
                                    <span class="meta-value"><?= e($job['province']) ?><?= $job['district'] ? ', ' . e($job['district']) : '' ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <span class="meta-icon">📋</span>
                            <div>
                                <span class="meta-label">Type</span>
                                <span class="meta-value"><?= e(ucfirst(str_replace('_', ' ', $job['employment_type']))) ?></span>
                            </div>
                        </div>
                        
                        <?php if ($job['experience_level']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📈</span>
                                <div>
                                    <span class="meta-label">Experience</span>
                                    <span class="meta-value"><?= e(ucfirst($job['experience_level'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($job['education_level']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">🎓</span>
                                <div>
                                    <span class="meta-label">Education</span>
                                    <span class="meta-value"><?= e(ucfirst(str_replace('_', ' ', $job['education_level']))) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($job['salary_min'] || $job['salary_max']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">💰</span>
                                <div>
                                    <span class="meta-label">Salary</span>
                                    <span class="meta-value">
                                        <?= $job['salary_min'] ? formatCurrency($job['salary_min']) : 'Negotiable' ?>
                                        <?= $job['salary_max'] ? ' - ' . formatCurrency($job['salary_max']) : '' ?>
                                        /<?= e($job['salary_period']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($job['application_deadline']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📅</span>
                                <div>
                                    <span class="meta-label">Deadline</span>
                                    <span class="meta-value deadline <?= $daysUntilDeadline !== null && $daysUntilDeadline < 0 ? 'expired' : ($daysUntilDeadline !== null && $daysUntilDeadline <= 7 ? 'urgent' : '') ?>">
                                        <?= $daysUntilDeadline !== null && $daysUntilDeadline < 0 ? 'Expired' : ($daysUntilDeadline !== null ? "{$daysUntilDeadline} days left" : formatDate($job['application_deadline'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Job Description -->
                <section class="job-section">
                    <h2>Job Description</h2>
                    <div class="job-content">
                        <?= nl2br(e($job['description'])) ?>
                    </div>
                </section>
                
                <?php if ($job['requirements']): ?>
                    <section class="job-section">
                        <h2>Requirements</h2>
                        <div class="job-content">
                            <?= nl2br(e($job['requirements'])) ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <?php if ($job['responsibilities']): ?>
                    <section class="job-section">
                        <h2>Responsibilities</h2>
                        <div class="job-content">
                            <?= nl2br(e($job['responsibilities'])) ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <?php if ($job['benefits']): ?>
                    <section class="job-section">
                        <h2>Benefits</h2>
                        <div class="job-content">
                            <?= nl2br(e($job['benefits'])) ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Company Info -->
                <?php if ($job['company_description']): ?>
                    <section class="job-section company-section">
                        <h2>About the Company</h2>
                        <div class="company-description">
                            <?= nl2br(e($job['company_description'])) ?>
                        </div>
                        <?php if ($job['company_website']): ?>
                            <a href="<?= e($job['company_website']) ?>" target="_blank" rel="noopener" class="btn btn-outline">Visit Company Website</a>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar Actions -->
            <aside class="job-detail-sidebar">
                <div class="sidebar-card apply-card">
                    <?php if ($existingApplication): ?>
                        <div class="application-status">
                            <h3>Application Submitted</h3>
                            <div class="status-badge status-<?= $existingApplication['status'] ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $existingApplication['status']))) ?>
                            </div>
                            <p class="application-date">Applied on <?= formatDate($existingApplication['applied_at']) ?></p>
                            
                            <?php if ($existingApplication['status'] === 'pending' || $existingApplication['status'] === 'reviewed'): ?>
                                <p class="status-message">Your application is being reviewed. You will be notified of any updates.</p>
                            <?php elseif ($existingApplication['status'] === 'shortlisted'): ?>
                                <p class="status-message success">Congratulations! You have been shortlisted. Expect contact from the employer soon.</p>
                            <?php elseif ($existingApplication['status'] === 'interviewed'): ?>
                                <p class="status-message">You have completed the interview stage. Awaiting final decision.</p>
                            <?php elseif ($existingApplication['status'] === 'hired'): ?>
                                <p class="status-message success">Congratulations! You have been hired. Welcome aboard!</p>
                            <?php else: ?>
                                <p class="status-message">Thank you for your interest. We wish you the best in your job search.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h3>Apply for this Job</h3>
                        <p class="apply-note">Prepare your cover letter and resume.</p>
                        
                        <form method="POST" action="" class="apply-form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="apply" value="1">
                            
                            <div class="form-group">
                                <label for="cover_letter">Cover Letter <span class="required" aria-hidden="true">*</span></label>
                                <textarea id="cover_letter" name="cover_letter" rows="6" required placeholder="Explain why you are a good fit for this position..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="resume">Resume/CV (PDF, DOC, DOCX - Max 5MB)</label>
                                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                                <?php if (isset($errors['resume'])): ?>
                                    <span class="error-message"><?= e($errors['resume']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Application</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="sidebar-card save-card">
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="save_job" value="1">
                        <input type="hidden" name="save_action" value="<?= $isSaved ? 'remove' : 'add' ?>">
                        <button type="submit" class="btn btn-block <?= $isSaved ? 'btn-secondary' : 'btn-outline' ?>" style="width: 100%;">
                            <?= $isSaved ? '⭐ Saved' : '☆ Save Job' ?>
                        </button>
                    </form>
                    <p class="save-note"><?= $isSaved ? 'Saved to your job list' : 'Save for later comparison' ?></p>
                </div>
                
                <div class="sidebar-card share-card">
                    <h3>Share this Job</h3>
                    <div class="share-buttons">
                        <button class="btn btn-sm btn-outline" onclick="shareJob('linkedin')">💼 LinkedIn</button>
                        <button class="btn btn-sm btn-outline" onclick="shareJob('twitter')">𝕏 Twitter</button>
                        <button class="btn btn-sm btn-outline" onclick="shareJob('facebook')">𝖿 Facebook</button>
                        <button class="btn btn-sm btn-outline" onclick="shareJob('whatsapp')">📱 WhatsApp</button>
                        <button class="btn btn-sm btn-outline" onclick="copyJobLink()">🔗 Copy Link</button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
function shareJob(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.querySelector('h1').textContent);
    const text = encodeURIComponent(`Check out this job: ${document.querySelector('h1').textContent} at ${document.querySelector('.company-name-large').textContent}`);
    
    let shareUrl = '';
    switch(platform) {
        case 'linkedin': shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`; break;
        case 'twitter': shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`; break;
        case 'facebook': shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`; break;
        case 'whatsapp': shareUrl = `https://wa.me/?text=${text} ${url}`; break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyJobLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link copied to clipboard!');
    });
}
</script>