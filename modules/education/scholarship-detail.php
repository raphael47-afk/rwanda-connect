<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Education: Scholarship Detail
 */

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireLogin();

$scholarshipId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = getCurrentUserId();
$db = getDb();

if (!$scholarshipId) {
    header('Location: /rwanda_connect/modules/education/scholarships.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM scholarships WHERE id = ? AND is_active = TRUE');
$stmt->execute([$scholarshipId]);
$scholarship = $stmt->fetch();

if (!$scholarship) {
    header('Location: /rwanda_connect/modules/education/scholarships.php');
    exit;
}

// Check if user already applied
$stmt = $db->prepare('SELECT * FROM scholarship_applications WHERE scholarship_id = ? AND user_id = ?');
$stmt->execute([$scholarshipId, $userId]);
$existingApplication = $stmt->fetch();

// Handle application
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_scholarship'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } elseif ($existingApplication) {
        $errors['general'] = 'You have already applied to this scholarship.';
    } else {
        $motivationLetter = trim($_POST['motivation_letter'] ?? '');
        
        // Handle document upload
        $documentsPath = null;
        if (!empty($_FILES['documents']['name'])) {
            $uploadDir = __DIR__ . '/../../../uploads/scholarships';
            $result = handleFileUpload($_FILES['documents'], $uploadDir, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'], 10485760);
            if ($result['success']) {
                $documentsPath = 'uploads/scholarships/' . $result['filename'];
            } else {
                $errors['documents'] = $result['error'];
            }
        }
        
        if (empty($errors)) {
            $stmt = $db->prepare('INSERT INTO scholarship_applications (scholarship_id, user_id, motivation_letter, documents_path) VALUES (?, ?, ?, ?)');
            $stmt->execute([$scholarshipId, $userId, $motivationLetter, $documentsPath]);
            
            // Update applications count
            $stmt = $db->prepare('UPDATE scholarships SET applications_count = applications_count + 1 WHERE id = ?');
            $stmt->execute([$scholarshipId]);
            
            logActivity('scholarship_application_submitted', "Applied for scholarship: {$scholarship['title']}", $userId);
            
            $success = 'Application submitted successfully!';
            $existingApplication = ['status' => 'pending', 'applied_at' => date('Y-m-d H:i:s')];
        }
    }
}

$daysLeft = $scholarship['deadline'] ? (new DateTime($scholarship['deadline']))->diff(new DateTime())->days : null;
$csrfToken = generateCsrfToken();

$pageTitle = e($scholarship['title']) . ' - Education - Rwanda Connect';
$pageDescription = 'View details and apply for ' . e($scholarship['title']) . ' scholarship.';

require_once __DIR__ . '/../../../includes/header.php';
?>

<main class="module-page education-page scholarship-detail-page">
    <div class="module-container">
        <header class="module-header">
            <div class="module-brand">
                <a href="/rwanda_connect/modules/education/scholarships.php" class="back-link">← Scholarships</a>
                <div>
                    <h1><?= e($scholarship['title']) ?></h1>
                    <p><?= e($scholarship['provider']) ?> • <?= e(ucfirst($scholarship['coverage'])) ?> Coverage</p>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <div class="scholarship-detail-layout">
            <!-- Main Content -->
            <div class="scholarship-main">
                <!-- Header Card -->
                <div class="scholarship-header-card">
                    <div class="scholarship-badges">
                        <span class="scholarship-coverage"><?= e(ucfirst($scholarship['coverage'])) ?> Coverage</span>
                        <span class="scholarship-deadline <?= $daysLeft !== null && $daysLeft <= 7 ? 'urgent' : '' ?>">
                            📅 <?= $daysLeft !== null ? ($daysLeft === 0 ? 'Deadline today!' : ($daysLeft === 1 ? 'Deadline tomorrow!' : $daysLeft . ' days left')) : 'No deadline' ?>
                        </span>
                    </div>
                    <h2><?= e($scholarship['title']) ?></h2>
                    <p class="scholarship-provider">🏢 <?= e($scholarship['provider']) ?></p>

                    <div class="scholarship-meta-grid">
                        <div class="meta-item">
                            <span class="meta-icon">🎓</span>
                            <div>
                                <span class="meta-label">Target Level</span>
                                <span class="meta-value"><?= e(ucfirst(str_replace('_', ' ', $scholarship['target_level']))) ?></span>
                            </div>
                        </div>
                        <?php if ($scholarship['target_field']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">📚</span>
                                <div>
                                    <span class="meta-label">Field of Study</span>
                                    <span class="meta-value"><?= e($scholarship['target_field']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($scholarship['target_country']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">🌍</span>
                                <div>
                                    <span class="meta-label">Target Country</span>
                                    <span class="meta-value"><?= e($scholarship['target_country']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($scholarship['amount_rwf']): ?>
                            <div class="meta-item">
                                <span class="meta-icon">💰</span>
                                <div>
                                    <span class="meta-label">Amount</span>
                                    <span class="meta-value"><?= formatCurrency($scholarship['amount_rwf']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span class="meta-icon">📋</span>
                            <div>
                                <span class="meta-label">Coverage</span>
                                <span class="meta-value"><?= e(ucfirst($scholarship['coverage'])) ?></span>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-icon">📅</span>
                            <div>
                                <span class="meta-label">Deadline</span>
                                <span class="meta-value"><?= formatDate($scholarship['deadline']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <section class="detail-section">
                    <h2>Description</h2>
                    <div class="scholarship-content">
                        <?= nl2br(e($scholarship['description'])) ?>
                    </div>
                </section>

                <!-- Eligibility Criteria -->
                <section class="detail-section">
                    <h2>Eligibility Criteria</h2>
                    <div class="scholarship-content">
                        <?= nl2br(e($scholarship['eligibility_criteria'])) ?>
                    </div>
                </section>

                <!-- Benefits -->
                <?php if ($scholarship['benefits']): ?>
                    <section class="detail-section">
                        <h2>Benefits</h2>
                        <div class="scholarship-content">
                            <?= nl2br(e($scholarship['benefits'])) ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Application Info -->
                <?php if ($scholarship['application_url']): ?>
                    <section class="detail-section">
                        <h2>External Application</h2>
                        <p>This scholarship requires application through an external portal.</p>
                        <a href="<?= e($scholarship['application_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary">Apply on External Site</a>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="scholarship-sidebar">
                <div class="sidebar-card apply-card">
                    <?php if ($existingApplication): ?>
                        <div class="application-status">
                            <h3>Application Submitted</h3>
                            <div class="status-badge status-<?= $existingApplication['status'] ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $existingApplication['status']))) ?>
                            </div>
                            <p class="application-date">Applied on <?= formatDate($existingApplication['applied_at']) ?></p>
                            
                            <?php if (in_array($existingApplication['status'], ['pending', 'submitted', 'under_review'], true)): ?>
                                <p class="status-message">Your application is being reviewed. You will be notified of any updates.</p>
                            <?php elseif ($existingApplication['status'] === 'shortlisted'): ?>
                                <p class="status-message success">Congratulations! You have been shortlisted. Expect contact from the provider soon.</p>
                            <?php elseif ($existingApplication['status'] === 'awarded'): ?>
                                <p class="status-message success">Congratulations! You have been awarded this scholarship!</p>
                            <?php else: ?>
                                <p class="status-message">Thank you for applying. We wish you the best in your scholarship search.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h3>Apply for this Scholarship</h3>
                        <p class="apply-note">Prepare your motivation letter and supporting documents.</p>

                        <form method="POST" action="" class="apply-form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="apply_scholarship" value="1">

                            <div class="form-group">
                                <label for="motivation_letter">Motivation Letter <span class="required" aria-hidden="true">*</span></label>
                                <textarea id="motivation_letter" name="motivation_letter" rows="6" required placeholder="Explain why you deserve this scholarship, your academic goals, and how this will help you..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="documents">Supporting Documents (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</label>
                                <input type="file" id="documents" name="documents" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <?php if (isset($errors['documents'])): ?>
                                    <span class="error-message"><?= e($errors['documents']) ?></span>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Application</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="sidebar-card share-card">
                    <h3>Share this Scholarship</h3>
                    <div class="share-buttons">
                        <button class="btn btn-sm btn-outline" onclick="shareScholarship('linkedin')">💼 LinkedIn</button>
                        <button class="btn btn-sm btn-outline" onclick="shareScholarship('twitter')">𝕏 Twitter</button>
                        <button class="btn btn-sm btn-outline" onclick="shareScholarship('facebook')">𝖿 Facebook</button>
                        <button class="btn btn-sm btn-outline" onclick="shareScholarship('whatsapp')">📱 WhatsApp</button>
                        <button class="btn btn-sm btn-outline" onclick="copyScholarshipLink()">🔗 Copy Link</button>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3>Quick Facts</h3>
                    <dl class="detail-list">
                        <dt>Provider</dt>
                        <dd><?= e($scholarship['provider']) ?></dd>
                        <dt>Deadline</dt>
                        <dd><?= formatDate($scholarship['deadline']) ?></dd>
                        <dt>Coverage</dt>
                        <dd><?= e(ucfirst($scholarship['coverage'])) ?></dd>
                        <?php if ($scholarship['amount_rwf']): ?>
                            <dt>Amount</dt>
                            <dd><?= formatCurrency($scholarship['amount_rwf']) ?></dd>
                        <?php endif; ?>
                        <dt>Level</dt>
                        <dd><?= e(ucfirst(str_replace('_', ' ', $scholarship['target_level']))) ?></dd>
                        <?php if ($scholarship['target_field']): ?>
                            <dt>Field</dt>
                            <dd><?= e($scholarship['target_field']) ?></dd>
                        <?php endif; ?>
                        <?php if ($scholarship['target_country']): ?>
                            <dt>Country</dt>
                            <dd><?= e($scholarship['target_country']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<script>
function shareScholarship(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.querySelector('h1').textContent);
    const text = encodeURIComponent(`Check out this scholarship: ${document.querySelector('h1').textContent} from ${document.querySelector('.scholarship-provider').textContent.replace('🏢 ', '')}`);
    
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

function copyScholarshipLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        if (window.showToast) window.showToast('Link copied to clipboard!');
        else alert('Link copied to clipboard!');
    });
}
</script>