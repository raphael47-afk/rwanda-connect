<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Enhanced User Profile
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$user = getCurrentUser();
$db = getDb();

$errors = [];
$success = false;
$activeTab = $_GET['tab'] ?? 'personal'; // personal, education, work, social, privacy
$validTabs = ['personal', 'education', 'work', 'social', 'privacy'];
$activeTab = in_array($activeTab, $validTabs, true) ? $activeTab : 'personal';

$formData = $user ? [
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
    'national_id' => $user['national_id'] ?? '',
    'date_of_birth' => $user['date_of_birth'] ?? '',
    'gender' => $user['gender'] ?? '',
    'province' => $user['province'] ?? '',
    'district' => $user['district'] ?? '',
    'sector' => $user['sector'] ?? '',
    'cell' => $user['cell'] ?? '',
    'village' => $user['village'] ?? '',
    'education_level' => $user['education_level'] ?? '',
    'occupation' => $user['occupation'] ?? '',
    'skills' => $user['skills'] ?? '',
    'bio' => $user['bio'] ?? '',
    'profile_image' => $user['profile_image'] ?? '',
    'cover_image' => $user['cover_image'] ?? '',
    'website' => $user['website'] ?? '',
    'linkedin' => $user['linkedin'] ?? '',
    'twitter' => $user['twitter'] ?? '',
    'github' => $user['github'] ?? '',
    'facebook' => $user['facebook'] ?? '',
] : [];

$provinces = getProvinces();
$districts = $formData['province'] ? getDistricts($formData['province']) : [];

// Get user's education records
$stmt = $db->prepare('SELECT ue.*, i.name as institution_name FROM user_education ue LEFT JOIN institutions i ON ue.institution_id = i.id WHERE ue.user_id = ? ORDER BY ue.is_current DESC, ue.end_year DESC');
$stmt->execute([$userId]);
$userEducation = $stmt->fetchAll();

// Get user's work experience
$stmt = $db->prepare('SELECT * FROM user_work_experience WHERE user_id = ? ORDER BY is_current DESC, end_date DESC');
$stmt->execute([$userId]);
$userWorkExperience = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'update_profile';
        
        switch ($action) {
            case 'update_profile':
                $formData = [
                    'full_name' => trim($_POST['full_name'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'date_of_birth' => $_POST['date_of_birth'] ?? '',
                    'gender' => $_POST['gender'] ?? '',
                    'province' => $_POST['province'] ?? '',
                    'district' => $_POST['district'] ?? '',
                    'sector' => trim($_POST['sector'] ?? ''),
                    'cell' => trim($_POST['cell'] ?? ''),
                    'village' => trim($_POST['village'] ?? ''),
                    'education_level' => $_POST['education_level'] ?? '',
                    'occupation' => trim($_POST['occupation'] ?? ''),
                    'skills' => trim($_POST['skills'] ?? ''),
                    'bio' => trim($_POST['bio'] ?? ''),
                    'website' => trim($_POST['website'] ?? ''),
                    'linkedin' => trim($_POST['linkedin'] ?? ''),
                    'twitter' => trim($_POST['twitter'] ?? ''),
                    'github' => trim($_POST['github'] ?? ''),
                    'facebook' => trim($_POST['facebook'] ?? ''),
                ];
                
                $result = updateProfile($userId, $formData);
                if ($result['success']) {
                    $success = 'Profile updated successfully!';
                    $user = getCurrentUser();
                    $formData = array_merge($formData, ['full_name' => $user['full_name'], 'email' => $user['email']]);
                    $districts = $formData['province'] ? getDistricts($formData['province']) : [];
                } else {
                    $errors = $result['errors'] ?? ['general' => 'Failed to update profile'];
                }
                break;
                
            case 'upload_avatar':
                if (!empty($_FILES['avatar']['name'])) {
                    $uploadDir = __DIR__ . '/../../../uploads/avatars';
                    $result = handleFileUpload($_FILES['avatar'], $uploadDir, ['image/jpeg', 'image/png', 'image/webp'], 5242880);
                    if ($result['success']) {
                        $stmt = $db->prepare('UPDATE user_profiles SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                        $stmt->execute(['uploads/avatars/' . $result['filename'], $userId]);
                        $success = 'Profile photo updated!';
                    } else {
                        $errors['avatar'] = $result['error'];
                    }
                } else {
                    $errors['avatar'] = 'No file selected';
                }
                break;
                
            case 'upload_cover':
                if (!empty($_FILES['cover']['name'])) {
                    $uploadDir = __DIR__ . '/../../../uploads/covers';
                    $result = handleFileUpload($_FILES['cover'], $uploadDir, ['image/jpeg', 'image/png', 'image/webp'], 10485760);
                    if ($result['success']) {
                        $stmt = $db->prepare('UPDATE user_profiles SET cover_image = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                        $stmt->execute(['uploads/covers/' . $result['filename'], $userId]);
                        $success = 'Cover photo updated!';
                    } else {
                        $errors['cover'] = $result['error'];
                    }
                } else {
                    $errors['cover'] = 'No file selected';
                }
                break;
                
            case 'add_education':
                $eduData = [
                    'institution_id' => $_POST['institution_id'] ? (int)$_POST['institution_id'] : null,
                    'institution_name' => trim($_POST['institution_name'] ?? ''),
                    'course_id' => $_POST['course_id'] ? (int)$_POST['course_id'] : null,
                    'qualification' => trim($_POST['qualification'] ?? ''),
                    'field_of_study' => trim($_POST['field_of_study'] ?? ''),
                    'start_year' => $_POST['start_year'] ? (int)$_POST['start_year'] : null,
                    'end_year' => $_POST['end_year'] ? (int)$_POST['end_year'] : null,
                    'is_current' => isset($_POST['is_current']),
                    'grade' => trim($_POST['grade'] ?? ''),
                ];
                
                if (empty($eduData['qualification']) || empty($eduData['institution_name'])) {
                    $errors['education'] = 'Qualification and institution are required';
                } else {
                    $stmt = $db->prepare('INSERT INTO user_education (user_id, institution_id, course_id, qualification, field_of_study, start_year, end_year, is_current, grade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $eduData['institution_id'], $eduData['course_id'], $eduData['qualification'], $eduData['field_of_study'], $eduData['start_year'], $eduData['end_year'] ?: null, $eduData['is_current'], $eduData['grade']]);
                    $success = 'Education record added!';
                    header('Location: /rwanda_connect/dashboard/profile.php?tab=education');
                    exit;
                }
                break;
                
            case 'add_work':
                $workData = [
                    'company_name' => trim($_POST['company_name'] ?? ''),
                    'position' => trim($_POST['position'] ?? ''),
                    'location' => trim($_POST['location'] ?? ''),
                    'start_date' => $_POST['start_date'] ?? null,
                    'end_date' => $_POST['end_date'] ?? null,
                    'is_current' => isset($_POST['is_current']),
                    'description' => trim($_POST['description'] ?? ''),
                ];
                
                if (empty($workData['company_name']) || empty($workData['position'])) {
                    $errors['work'] = 'Company and position are required';
                } else {
                    $stmt = $db->prepare('INSERT INTO user_work_experience (user_id, company_name, position, location, start_date, end_date, is_current, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $workData['company_name'], $workData['position'], $workData['location'], $workData['start_date'] ?: null, $workData['end_date'] ?: null, $workData['is_current'], $workData['description']]);
                    $success = 'Work experience added!';
                    header('Location: /rwanda_connect/dashboard/profile.php?tab=work');
                    exit;
                }
                break;
                
            case 'delete_education':
                $eduId = (int)($_POST['education_id'] ?? 0);
                if ($eduId) {
                    $stmt = $db->prepare('DELETE FROM user_education WHERE id = ? AND user_id = ?');
                    $stmt->execute([$eduId, $userId]);
                    $success = 'Education record deleted!';
                    header('Location: /rwanda_connect/dashboard/profile.php?tab=education');
                    exit;
                }
                break;
                
            case 'delete_work':
                $workId = (int)($_POST['work_id'] ?? 0);
                if ($workId) {
                    $stmt = $db->prepare('DELETE FROM user_work_experience WHERE id = ? AND user_id = ?');
                    $stmt->execute([$workId, $userId]);
                    $success = 'Work experience deleted!';
                    header('Location: /rwanda_connect/dashboard/profile.php?tab=work');
                    exit;
                }
                break;
                
            case 'update_social':
                $socialData = [
                    'website' => trim($_POST['website'] ?? ''),
                    'linkedin' => trim($_POST['linkedin'] ?? ''),
                    'twitter' => trim($_POST['twitter'] ?? ''),
                    'github' => trim($_POST['github'] ?? ''),
                    'facebook' => trim($_POST['facebook'] ?? ''),
                ];
                
                $stmt = $db->prepare('UPDATE user_profiles SET website = ?, linkedin = ?, twitter = ?, github = ?, facebook = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([$socialData['website'], $socialData['linkedin'], $socialData['twitter'], $socialData['github'], $socialData['facebook'], $userId]);
                $success = 'Social links updated!';
                break;
        }
    }
}

// Handle avatar/cover upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_upload'])) {
    header('Content-Type: application/json');
    $type = $_POST['type'] ?? 'avatar';
    
    if ($type === 'avatar' && !empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/avatars';
        $result = handleFileUpload($_FILES['file'], $uploadDir, ['image/jpeg', 'image/png', 'image/webp'], 5242880);
        if ($result['success']) {
            $stmt = $db->prepare('UPDATE user_profiles SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
            $stmt->execute(['uploads/avatars/' . $result['filename'], $userId]);
            echo json_encode(['success' => true, 'url' => '/rwanda_connect/' . 'uploads/avatars/' . $result['filename']]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } elseif ($type === 'cover' && !empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/covers';
        $result = handleFileUpload($_FILES['file'], $uploadDir, ['image/jpeg', 'image/png', 'image/webp'], 10485760);
        if ($result['success']) {
            $stmt = $db->prepare('UPDATE user_profiles SET cover_image = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
            $stmt->execute(['uploads/covers/' . $result['filename'], $userId]);
            echo json_encode(['success' => true, 'url' => '/rwanda_connect/' . 'uploads/covers/' . $result['filename']]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
    }
    exit;
}

$csrfToken = generateCsrfToken();

// Get institutions for education dropdown
$stmt = $db->query('SELECT id, name FROM institutions WHERE is_accredited = TRUE ORDER BY name');
$institutions = $stmt->fetchAll();

// Get courses for education dropdown
$stmt = $db->query('SELECT c.id, c.name, i.name as institution_name FROM courses c JOIN institutions i ON c.institution_id = i.id WHERE c.is_active = TRUE ORDER BY c.name');
$courses = $stmt->fetchAll();

// Calculate profile completeness
$checks = [
    'full_name' => !empty($user['full_name']),
    'email' => !empty($user['email']),
    'phone' => !empty($user['phone']),
    'date_of_birth' => !empty($user['date_of_birth']),
    'gender' => !empty($user['gender']),
    'province' => !empty($user['province']),
    'district' => !empty($user['district']),
    'education_level' => !empty($user['education_level']),
    'occupation' => !empty($user['occupation']),
    'skills' => !empty($user['skills']),
    'bio' => !empty($user['bio']),
    'profile_image' => !empty($user['profile_image']),
    'cover_image' => !empty($user['cover_image']),
    'education_records' => !empty($userEducation),
    'work_experience' => !empty($userWorkExperience),
    'social_links' => !empty($user['linkedin']) || !empty($user['github']) || !empty($user['twitter']),
];
$completenessScore = (count(array_filter($checks)) / count($checks)) * 100;

$pageTitle = 'Profile - Rwanda Connect';
$pageDescription = 'Manage your personal information, education, work experience, and social links.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="dashboard-page profile-page">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Profile</h1>
                <p class="welcome-subtitle">Manage your personal information, education, work experience, and social presence.</p>
            </div>
            <a href="/rwanda_connect/dashboard/" class="btn btn-secondary">&larr; Dashboard</a>
        </header>
        
        <!-- Profile Completeness -->
        <div class="profile-completeness-bar">
            <div class="completeness-progress">
                <div class="completeness-fill" style="width: <?= $completenessScore ?>%"></div>
            </div>
            <div class="completeness-info">
                <span class="completeness-score"><?= (int)$completenessScore ?>% Complete</span>
                <span class="completeness-text"><?= count(array_filter($checks)) ?> of <?= count($checks) ?> sections complete</span>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Profile Header with Cover -->
        <div class="profile-header-card">
            <div class="profile-cover" style="background-image: url('<?= $user['cover_image'] ? '/rwanda_connect/' . e($user['cover_image']) : '/rwanda_connect/assets/images/default-cover.jpg' ?>');">
                <div class="cover-overlay"></div>
                <button class="cover-upload-btn" data-type="cover" title="Change Cover">
                    <span>📷</span> Change Cover
                </button>
            </div>
            
            <div class="profile-info-section">
                <div class="profile-avatar-wrapper">
                    <img id="profile-avatar" src="<?= $user['profile_image'] ? '/rwanda_connect/' . e($user['profile_image']) : '/rwanda_connect/assets/images/default-avatar.png' ?>" alt="<?= e($user['full_name']) ?>">
                    <button class="avatar-upload-btn" data-type="avatar" title="Change Photo">
                        <span>📷</span>
                    </button>
                </div>
                
                <div class="profile-basic-info">
                    <h1><?= e($user['full_name']) ?></h1>
                    <p class="profile-email"><?= e($user['email']) ?></p>
                    <div class="profile-badges">
                        <span class="role-badge citizen">Citizen</span>
                        <?php if (!empty($user['email_verified_at'])): ?>
                            <span class="verified-badge">✓ Verified</span>
                        <?php endif; ?>
                        <span class="member-since">Member since <?= date('M Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($formData['bio'])): ?>
                        <p class="profile-bio"><?= e($formData['bio']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats-grid">
                    <div class="profile-stat-card">
                        <span class="stat-icon">🎓</span>
                        <div>
                            <span class="stat-number"><?= count($userEducation) ?></span>
                            <span class="stat-label">Education</span>
                        </div>
                    </div>
                    <div class="profile-stat-card">
                        <span class="stat-icon">💼</span>
                        <div>
                            <span class="stat-number"><?= count($userWorkExperience) ?></span>
                            <span class="stat-label">Work Experience</span>
                        </div>
                    </div>
                    <div class="profile-stat-card">
                        <span class="stat-icon">🔗</span>
                        <div>
                            <span class="stat-number"><?= (!empty($user['linkedin']) + !empty($user['github']) + !empty($user['twitter']) + !empty($user['facebook'])) ?></span>
                            <span class="stat-label">Social Links</span>
                        </div>
                    </div>
                    <div class="profile-stat-card">
                        <span class="stat-icon">📅</span>
                        <div>
                            <span class="stat-number"><?= date('Y') - date('Y', strtotime($user['created_at'])) + 1 ?></span>
                            <span class="stat-label">Years Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <!-- Profile Tabs -->
        <nav class="profile-tabs" aria-label="Profile sections">
            <a href="?tab=personal" class="<?= $activeTab === 'personal' ? 'active' : '' ?>">👤 Personal Info</a>
            <a href="?tab=education" class="<?= $activeTab === 'education' ? 'active' : '' ?>">🎓 Education</a>
            <a href="?tab=work" class="<?= $activeTab === 'work' ? 'active' : '' ?>">💼 Work Experience</a>
            <a href="?tab=social" class="<?= $activeTab === 'social' ? 'active' : '' ?>">🔗 Social Links</a>
            <a href="?tab=privacy" class="<?= $activeTab === 'privacy' ? 'active' : '' ?>">🛡️ Privacy</a>
        </nav>
        
        <!-- Tab Content -->
        <div class="profile-tab-content">
            <!-- Personal Info Tab -->
            <section class="tab-panel <?= $activeTab === 'personal' ? 'active' : '' ?>" id="tab-personal" aria-labelledby="personal-heading">
                <form method="POST" action="" class="profile-form" novalidate enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <section class="form-section">
                        <h3>Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="required" aria-hidden="true">*</span></label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?= e($formData['full_name']) ?>" required autocomplete="name"
                                       class="<?= isset($errors['full_name']) ? 'error' : '' ?>">
                                <?php if (isset($errors['full_name'])): ?>
                                    <span class="error-message"><?= e($errors['full_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       value="<?= e($formData['email']) ?>" readonly autocomplete="email"
                                       class="readonly-field">
                                <span class="form-hint">Email cannot be changed. Contact support if needed.</span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?= e($formData['phone']) ?>" 
                                       placeholder="+250 7XX XXX XXX"
                                       autocomplete="tel"
                                       class="<?= isset($errors['phone']) ? 'error' : '' ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <span class="error-message"><?= e($errors['phone']) ?></span>
                                <?php endif; ?>
                                <span class="form-hint">Rwanda format: +250 7XX XXX XXX</span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="national_id">National ID</label>
                                <input type="text" id="national_id" name="national_id" 
                                       value="<?= e($formData['national_id'] ?? '') ?>" 
                                       placeholder="16-digit national ID"
                                       maxlength="16"
                                       autocomplete="off"
                                       class="<?= isset($errors['national_id']) ? 'error' : '' ?>">
                                <?php if (isset($errors['national_id'])): ?>
                                    <span class="error-message"><?= e($errors['national_id']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?= e($formData['date_of_birth'] ?? '') ?>"
                                       max="<?= date('Y-m-d', strtotime('-13 years')) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select</option>
                                    <option value="male" <?= ($formData['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($formData['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= ($formData['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </section>
                    
                    <section class="form-section">
                        <h3>Location</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="province">Province</label>
                                <select id="province" name="province" onchange="updateDistricts(this.value)">
                                    <option value="">Select Province</option>
                                    <?php foreach ($provinces as $key => $name): ?>
                                        <option value="<?= e($key) ?>" <?= ($formData['province'] ?? '') === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="district">District</label>
                                <select id="district" name="district">
                                    <option value="">Select District</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?= e($district) ?>" <?= ($formData['district'] ?? '') === $district ? 'selected' : '' ?>><?= e($district) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sector">Sector</label>
                                <input type="text" id="sector" name="sector" value="<?= e($formData['sector'] ?? '') ?>" placeholder="Sector">
                            </div>
                            
                            <div class="form-group">
                                <label for="cell">Cell</label>
                                <input type="text" id="cell" name="cell" value="<?= e($formData['cell'] ?? '') ?>" placeholder="Cell">
                            </div>
                            
                            <div class="form-group">
                                <label for="village">Village</label>
                                <input type="text" id="village" name="village" value="<?= e($formData['village'] ?? '') ?>" placeholder="Village">
                            </div>
                        </div>
                    </section>
                    
                    <section class="form-section">
                        <h3>Education & Work</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="education_level">Education Level</label>
                                <select id="education_level" name="education_level">
                                    <option value="">Select</option>
                                    <option value="primary" <?= ($formData['education_level'] ?? '') === 'primary' ? 'selected' : '' ?>>Primary</option>
                                    <option value="secondary" <?= ($formData['education_level'] ?? '') === 'secondary' ? 'selected' : '' ?>>Secondary</option>
                                    <option value="diploma" <?= ($formData['education_level'] ?? '') === 'diploma' ? 'selected' : '' ?>>Diploma</option>
                                    <option value="bachelor" <?= ($formData['education_level'] ?? '') === 'bachelor' ? 'selected' : '' ?>>Bachelor's Degree</option>
                                    <option value="master" <?= ($formData['education_level'] ?? '') === 'master' ? 'selected' : '' ?>>Master's Degree</option>
                                    <option value="phd" <?= ($formData['education_level'] ?? '') === 'phd' ? 'selected' : '' ?>>PhD</option>
                                    <option value="vocational" <?= ($formData['education_level'] ?? '') === 'vocational' ? 'selected' : '' ?>>Vocational/Training</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="occupation">Current Occupation</label>
                                <input type="text" id="occupation" name="occupation" 
                                       value="<?= e($formData['occupation'] ?? '') ?>" placeholder="e.g., Farmer, Teacher, Software Developer">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="skills">Skills (comma separated)</label>
                                <textarea id="skills" name="skills" rows="3" placeholder="e.g., Farming, Project Management, Data Analysis, Teaching"><?= e($formData['skills'] ?? '') ?></textarea>
                                <div id="skills-tags" class="skills-tags"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="bio">Bio / About Me</label>
                                <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."><?= e($formData['bio'] ?? '') ?></textarea>
                                <span class="form-hint"><span id="bio-count"><?= strlen($formData['bio'] ?? '') ?></span>/500 characters</span>
                            </div>
                        </div>
                    </section>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                    </div>
                </form>
            </section>
            
            <!-- Education Tab -->
            <section class="tab-panel <?= $activeTab === 'education' ? 'active' : '' ?>" id="tab-education" aria-labelledby="education-heading">
                <div class="tab-header">
                    <h2>Education History</h2>
                    <button class="btn btn-primary" data-modal="add-education">➕ Add Education</button>
                </div>
                
                <?php if (empty($userEducation)): ?>
                    <div class="empty-state-card">
                        <span class="empty-icon">🎓</span>
                        <h3>No education records yet</h3>
                        <p>Add your educational background to showcase your qualifications.</p>
                        <button class="btn btn-primary" data-modal="add-education">Add Your First Education</button>
                    </div>
                <?php else: ?>
                    <div class="education-timeline">
                        <?php foreach ($userEducation as $edu): ?>
                            <article class="education-card">
                                <div class="education-icon">
                                    <span>🎓</span>
                                </div>
                                <div class="education-content">
                                    <div class="education-header">
                                        <h3><?= e($edu['qualification']) ?></h3>
                                        <span class="education-institution"><?= e($edu['institution_name'] ?? 'Institution not specified') ?></span>
                                    </div>
                                    <?php if ($edu['field_of_study']): ?>
                                        <p class="education-field">📚 <?= e($edu['field_of_study']) ?></p>
                                    <?php endif; ?>
                                    <div class="education-meta">
                                        <span class="education-dates">
                                            <?= $edu['start_year'] ? e($edu['start_year']) : 'N/A' ?> - <?= $edu['is_current'] ? 'Present' : ($edu['end_year'] ? e($edu['end_year']) : 'N/A') ?>
                                            <?php if ($edu['is_current']): ?>
                                                <span class="current-badge">Current</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($edu['grade']): ?>
                                            <span class="education-grade">Grade: <?= e($edu['grade']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="education-actions">
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this education record?')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete_education">
                                        <input type="hidden" name="education_id" value="<?= $edu['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Work Experience Tab -->
            <section class="tab-panel <?= $activeTab === 'work' ? 'active' : '' ?>" id="tab-work" aria-labelledby="work-heading">
                <div class="tab-header">
                    <h2>Work Experience</h2>
                    <button class="btn btn-primary" data-modal="add-work">➕ Add Experience</button>
                </div>
                
                <?php if (empty($userWorkExperience)): ?>
                    <div class="empty-state-card">
                        <span class="empty-icon">💼</span>
                        <h3>No work experience yet</h3>
                        <p>Add your professional experience to build your profile.</p>
                        <button class="btn btn-primary" data-modal="add-work">Add Your First Job</button>
                    </div>
                <?php else: ?>
                    <div class="work-timeline">
                        <?php foreach ($userWorkExperience as $work): ?>
                            <article class="work-card">
                                <div class="work-icon">
                                    <span>💼</span>
                                </div>
                                <div class="work-content">
                                    <div class="work-header">
                                        <h3><?= e($work['position']) ?></h3>
                                        <span class="work-company"><?= e($work['company_name']) ?></span>
                                    </div>
                                    <?php if ($work['location']): ?>
                                        <p class="work-location">📍 <?= e($work['location']) ?></p>
                                    <?php endif; ?>
                                    <div class="work-meta">
                                        <span class="work-dates">
                                            <?= $work['start_date'] ? formatDate($work['start_date']) : 'N/A' ?> - <?= $work['is_current'] ? 'Present' : ($work['end_date'] ? formatDate($work['end_date']) : 'N/A') ?>
                                            <?php if ($work['is_current']): ?>
                                                <span class="current-badge">Current</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if ($work['description']): ?>
                                        <p class="work-description"><?= e($work['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="work-actions">
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this work experience?')">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete_work">
                                        <input type="hidden" name="work_id" value="<?= $work['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Social Links Tab -->
            <section class="tab-panel <?= $activeTab === 'social' ? 'active' : '' ?>" id="tab-social" aria-labelledby="social-heading">
                <form method="POST" action="" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="update_social">
                    
                    <section class="form-section">
                        <h3>Social Links & Website</h3>
                        <p class="section-description">Add links to your professional profiles. These will be visible on your public profile based on your privacy settings.</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="website">Personal Website / Portfolio</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">🌐</span>
                                    <input type="url" id="website" name="website" 
                                           value="<?= e($formData['website'] ?? '') ?>" 
                                           placeholder="https://yourwebsite.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="linkedin">LinkedIn Profile</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">💼</span>
                                    <input type="url" id="linkedin" name="linkedin" 
                                           value="<?= e($formData['linkedin'] ?? '') ?>" 
                                           placeholder="https://linkedin.com/in/yourprofile">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="github">GitHub Profile</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">🐙</span>
                                    <input type="url" id="github" name="github" 
                                           value="<?= e($formData['github'] ?? '') ?>" 
                                           placeholder="https://github.com/yourusername">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="twitter">Twitter / X Profile</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">𝕏</span>
                                    <input type="url" id="twitter" name="twitter" 
                                           value="<?= e($formData['twitter'] ?? '') ?>" 
                                           placeholder="https://twitter.com/yourusername">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="facebook">Facebook Profile</label>
                                <div class="input-with-icon">
                                    <span class="input-icon">📘</span>
                                    <input type="url" id="facebook" name="facebook" 
                                           value="<?= e($formData['facebook'] ?? '') ?>" 
                                           placeholder="https://facebook.com/yourprofile">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">Save Social Links</button>
                        </div>
                    </section>
                    
                    <section class="form-section">
                        <h3>Profile Visibility</h3>
                        <p class="section-description">Choose what information is visible to other users on your public profile.</p>
                        
                        <div class="visibility-options">
                            <?php
                            $visibilityFields = [
                                'education' => ['label' => 'Education History', 'icon' => '🎓', 'desc' => 'Your degrees and certifications'],
                                'work' => ['label' => 'Work Experience', 'icon' => '💼', 'desc' => 'Your professional background'],
                                'skills' => ['label' => 'Skills', 'icon' => '🛠️', 'desc' => 'Your listed skills and expertise'],
                                'location' => ['label' => 'Location', 'icon' => '📍', 'desc' => 'Your province and district'],
                                'social' => ['label' => 'Social Links', 'icon' => '🔗', 'desc' => 'Your website and social profiles'],
                            ];
                            
                            foreach ($visibilityFields as $key => $field):
                                $isVisible = !empty($user['show_' . $key]) ?? true;
                            ?>
                                <label class="visibility-toggle">
                                    <input type="checkbox" name="show_<?= $key ?>" <?= $isVisible ? 'checked' : '' ?>>
                                    <div class="toggle-content">
                                        <div class="toggle-icon"><?= $field['icon'] ?></div>
                                        <div>
                                            <strong><?= $field['label'] ?></strong>
                                            <span><?= $field['desc'] ?></span>
                                        </div>
                                    </div>
                                    <div class="toggle-switch">
                                        <span class="switch-track"></span>
                                        <span class="switch-thumb"></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </form>
            </section>
            
            <!-- Privacy Tab -->
            <section class="tab-panel <?= $activeTab === 'privacy' ? 'active' : '' ?>" id="tab-privacy" aria-labelledby="privacy-heading">
                <form method="POST" action="/rwanda_connect/dashboard/settings.php" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="privacy_settings">
                    
                    <section class="form-section">
                        <h3>Profile Visibility</h3>
                        
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="profile_visibility" value="public" <?= ($user['profile_visibility'] ?? 'private') === 'public' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>Public</strong>
                                    <span>Anyone can see your profile</span>
                                </div>
                            </label>
                            
                            <label class="radio-option">
                                <input type="radio" name="profile_visibility" value="citizens" <?= ($user['profile_visibility'] ?? 'private') === 'citizens' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>Registered Citizens Only</strong>
                                    <span>Only logged-in users can see your profile</span>
                                </div>
                            </label>
                            
                            <label class="radio-option">
                                <input type="radio" name="profile_visibility" value="private" <?= ($user['profile_visibility'] ?? 'private') === 'private' ? 'checked' : '' ?>>
                                <div class="radio-content">
                                    <strong>Private</strong>
                                    <span>Only you can see your profile (Recommended)</span>
                                </div>
                            </label>
                        </div>
                    </section>
                    
                    <section class="form-section">
                        <h3>Information Sharing</h3>
                        <p>Choose what personal information is visible on your profile.</p>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="show_email" <?= $user['show_email'] ?? false ? 'checked' : '' ?>>
                            <div class="toggle-content">
                                <strong>Show Email</strong>
                                <span>Display your email address on your profile</span>
                            </div>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="show_phone" <?= $user['show_phone'] ?? false ? 'checked' : '' ?>>
                            <div class="toggle-content">
                                <strong>Show Phone</strong>
                                <span>Display your phone number on your profile</span>
                            </div>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="show_location" <?= $user['show_location'] ?? false ? 'checked' : '' ?>>
                            <div class="toggle-content">
                                <strong>Show Location</strong>
                                <span>Display your province/district on your profile</span>
                            </div>
                        </label>
                    </section>
                    
                    <section class="form-section">
                        <h3>Data & Analytics</h3>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="activity_tracking" <?= $user['activity_tracking'] ?? true ? 'checked' : '' ?>>
                            <div class="toggle-content">
                                <strong>Activity Tracking</strong>
                                <span>Allow analytics to improve your experience</span>
                            </div>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="personalized_ads" <?= $user['personalized_ads'] ?? false ? 'checked' : '' ?>>
                            <div class="toggle-content">
                                <strong>Personalized Recommendations</strong>
                                <span>Use your activity to suggest relevant opportunities</span>
                            </div>
                        </label>
                    </section>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">Save Privacy Settings</button>
                    </div>
                </form>
            </section>
        </div>
        
        <!-- Modals -->
        <!-- Add Education Modal -->
        <div class="modal" id="add-education-modal" role="dialog" aria-modal="true" aria-labelledby="add-education-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="add-education-title">Add Education</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="" class="modal-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="add_education">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="institution_name">Institution Name <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="institution_name" name="institution_name" required placeholder="e.g., University of Rwanda" list="institutions-list">
                            <datalist id="institutions-list">
                                <?php foreach ($institutions as $inst): ?>
                                    <option value="<?= e($inst['name']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="qualification">Qualification <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="qualification" name="qualification" required placeholder="e.g., Bachelor of Science in Computer Science">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="field_of_study">Field of Study</label>
                            <input type="text" id="field_of_study" name="field_of_study" placeholder="e.g., Computer Science">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_year">Start Year <span class="required" aria-hidden="true">*</span></label>
                            <input type="number" id="start_year" name="start_year" required min="1950" max="<?= date('Y') ?>" placeholder="2018">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_year">End Year</label>
                            <input type="number" id="end_year" name="end_year" min="1950" max="<?= date('Y') + 5 ?>" placeholder="2022">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_current" name="is_current">
                            <label for="is_current">Currently studying here</label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="grade">Grade / GPA</label>
                            <input type="text" id="grade" name="grade" placeholder="e.g., First Class, 3.8/4.0">
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Education</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Add Work Modal -->
        <div class="modal" id="add-work-modal" role="dialog" aria-modal="true" aria-labelledby="add-work-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="add-work-title">Add Work Experience</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="" class="modal-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="add_work">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_name">Company Name <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="company_name" name="company_name" required placeholder="e.g., Rwanda Development Board">
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="position" name="position" required placeholder="e.g., Software Developer">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" placeholder="e.g., Kigali, Rwanda">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date <span class="required" aria-hidden="true">*</span></label>
                            <input type="month" id="start_date" name="start_date" required max="<?= date('Y-m') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="month" id="end_date" name="end_date" max="<?= date('Y-m') ?>">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="work_is_current" name="is_current">
                            <label for="work_is_current">Currently working here</label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" placeholder="Describe your role, achievements, and responsibilities..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Experience</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Avatar/Cover Upload Modal -->
        <div class="modal" id="upload-modal" role="dialog" aria-modal="true" aria-labelledby="upload-title" hidden>
            <div class="modal-overlay" data-close-modal></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="upload-title">Upload Photo</h2>
                    <button class="modal-close" data-close-modal aria-label="Close">✕</button>
                </div>
                <form method="POST" action="" class="modal-form" enctype="multipart/form-data" id="upload-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="ajax_upload" value="1">
                    <input type="hidden" name="type" id="upload-type" value="avatar">
                    
                    <div class="upload-preview" id="upload-preview" hidden>
                        <img id="preview-image" src="" alt="Preview">
                    </div>
                    
                    <div class="upload-dropzone" id="dropzone">
                        <input type="file" id="file-input" name="file" accept="image/jpeg,image/png,image/webp" hidden>
                        <div class="dropzone-content">
                            <span class="dropzone-icon">📁</span>
                            <p>Drag & drop or click to upload</p>
                            <span class="dropzone-hint">JPG, PNG, WebP • Max 5MB (avatar) / 10MB (cover)</span>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary" id="upload-submit" disabled>Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// District data by province
const districtsByProvince = {
    'Kigali City': ['Gasabo', 'Kicukiro', 'Nyarugenge'],
    'Northern Province': ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
    'Southern Province': ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
    'Eastern Province': ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
    'Western Province': ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro']
};

function updateDistricts(province) {
    const districtSelect = document.getElementById('district');
    const currentValue = districtSelect.value;
    
    districtSelect.innerHTML = '<option value="">Select District</option>';
    
    if (province && districtsByProvince[province]) {
        districtsByProvince[province].forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (district === currentValue) option.selected = true;
            districtSelect.appendChild(option);
        });
    }
}

// Phone formatting
document.getElementById('phone')?.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.startsWith('250')) value = value.substring(3);
    if (value.startsWith('0')) value = value.substring(1);
    if (value.length > 9) value = value.substring(0, 9);
    
    let formatted = '';
    if (value.length > 0) formatted = '+250 ';
    if (value.length > 2) formatted += value.substring(0, 3) + ' ';
    if (value.length > 5) formatted += value.substring(3, 6) + ' ';
    if (value.length > 6) formatted += value.substring(6);
    
    e.target.value = formatted;
});

// National ID formatting (digits only)
document.getElementById('national_id')?.addEventListener('input', (e) => {
    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 16);
});

// Skills tags
const skillsInput = document.getElementById('skills');
const skillsTags = document.getElementById('skills-tags');

function renderSkillsTags() {
    const skills = skillsInput.value.split(',').map(s => s.trim()).filter(s => s);
    skillsTags.innerHTML = skills.map(skill => `
        <span class="skill-tag">
            ${escapeHtml(skill)}
            <button type="button" class="skill-remove" data-skill="${escapeHtml(skill)}">×</button>
        </span>
    `).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

skillsInput?.addEventListener('input', renderSkillsTags);
skillsTags?.addEventListener('click', (e) => {
    if (e.target.classList.contains('skill-remove')) {
        const skill = e.target.dataset.skill;
        const skills = skillsInput.value.split(',').map(s => s.trim()).filter(s => s !== skill);
        skillsInput.value = skills.join(', ');
        renderSkillsTags();
    }
});
renderSkillsTags();

// Bio character count
const bioInput = document.getElementById('bio');
const bioCount = document.getElementById('bio-count');
bioInput?.addEventListener('input', () => {
    if (bioCount) bioCount.textContent = bioInput.value.length;
});

// Tab switching
document.querySelectorAll('.profile-tabs a').forEach(tab => {
    tab.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.profile-tabs a').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.getAttribute('href').split('=')[1]).classList.add('active');
    });
});

// Modal handling
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}

document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modal + '-modal'));
});

document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = btn.closest('.modal');
        if (modal) closeModal(modal.id);
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal:not([hidden])').forEach(m => closeModal(m.id));
    }
});

// Avatar/Cover upload handling
const avatarBtn = document.querySelector('[data-type="avatar"]');
const coverBtn = document.querySelector('[data-type="cover"]');
const uploadModal = document.getElementById('upload-modal');
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const uploadPreview = document.getElementById('upload-preview');
const previewImage = document.getElementById('preview-image');
const uploadType = document.getElementById('upload-type');
const uploadSubmit = document.getElementById('upload-submit');
const uploadForm = document.getElementById('upload-form');

function openUploadModal(type) {
    uploadType.value = type;
    uploadPreview.hidden = true;
    dropzone.classList.remove('has-file');
    fileInput.value = '';
    uploadSubmit.disabled = true;
    openModal('upload-modal');
}

avatarBtn?.addEventListener('click', () => openUploadModal('avatar'));
coverBtn?.addEventListener('click', () => openUploadModal('cover'));

dropzone?.addEventListener('click', () => fileInput.click());
dropzone?.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone?.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(e.dataTransfer.files[0]);
    }
});

fileInput?.addEventListener('change', () => {
    if (fileInput.files.length) handleFileSelect(fileInput.files[0]);
});

function handleFileSelect(file) {
    if (!file.type.match(/image\/(jpeg|png|webp)/)) {
        alert('Please select a valid image file (JPG, PNG, WebP)');
        return;
    }
    
    const maxSize = uploadType.value === 'cover' ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert(`File too large. Maximum size: ${maxSize / 1024 / 1024}MB`);
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.src = e.target.result;
        uploadPreview.hidden = false;
        dropzone.classList.add('has-file');
        uploadSubmit.disabled = false;
    };
    reader.readAsDataURL(file);
}

uploadForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!fileInput.files.length) return;
    
    uploadSubmit.disabled = true;
    uploadSubmit.textContent = 'Uploading...';
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('type', uploadType.value);
    formData.append('ajax_upload', '1');
    formData.append('csrf_token', '<?= e($csrfToken) ?>');
    
    try {
        const response = await fetch('/rwanda_connect/dashboard/profile.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            if (uploadType.value === 'avatar') {
                document.getElementById('profile-avatar').src = result.url + '?t=' + Date.now();
            } else {
                document.querySelector('.profile-cover').style.backgroundImage = `url('${result.url}?t=${Date.now()}')`;
            }
            closeModal('upload-modal');
            showToast('Photo updated successfully!', 'success');
        } else {
            alert('Upload failed: ' + result.error);
        }
    } catch (err) {
        alert('Upload failed: ' + err.message);
    } finally {
        uploadSubmit.disabled = false;
        uploadSubmit.textContent = 'Upload';
    }
});

// Delete confirmations
document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
    form.addEventListener('submit', (e) => {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

// Toast notifications
function showToast(message, type = 'success') {
    const container = document.getElementById('flash-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.textContent = message;
    toast.style.animation = 'slideIn 0.3s ease';
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>

<style>
/* Profile Page Styles */
.profile-page {
    min-height: 100vh;
}

/* Profile Completeness Bar */
.profile-completeness-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    margin-bottom: 24px;
}

.completeness-progress {
    flex: 1;
    height: 8px;
    background: var(--line);
    border-radius: 4px;
    overflow: hidden;
}

.completeness-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--green), var(--green-dark));
    border-radius: 4px;
    transition: width 0.5s ease;
}

.completeness-info {
    display: flex;
    flex-direction: column;
    min-width: 180px;
}

.completeness-score {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--green);
}

.completeness-text {
    font-size: .8rem;
    color: var(--muted);
}

/* Profile Header Card */
.profile-header-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    overflow: hidden;
    margin-bottom: 24px;
}

.profile-cover {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    background-size: cover;
    background-position: center;
}

.cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.4));
}

.cover-upload-btn {
    position: absolute;
    bottom: 20px;
    right: 20px;
    padding: 10px 16px;
    background: var(--surface);
    border: none;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: var(--shadow-md);
    transition: all var(--transition);
}

.cover-upload-btn:hover {
    background: var(--green);
    color: white;
}

.profile-info-section {
    display: flex;
    gap: 24px;
    padding: 24px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.profile-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
    margin-top: -60px;
}

.profile-avatar-wrapper img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--surface);
    box-shadow: var(--shadow-lg);
    object-fit: cover;
}

.avatar-upload-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--green);
    color: white;
    border: 3px solid var(--surface);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition);
}

.avatar-upload-btn:hover {
    background: var(--green-dark);
    transform: scale(1.1);
}

.profile-basic-info {
    flex: 1;
    min-width: 200px;
}

.profile-basic-info h1 {
    margin: 0 0 4px;
    font-size: 1.75rem;
}

.profile-email {
    margin: 0 0 12px;
    color: var(--muted);
}

.profile-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.role-badge {
    padding: 4px 12px;
    background: var(--green-light);
    color: var(--green);
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}

.verified-badge {
    padding: 4px 10px;
    background: var(--success-bg);
    color: var(--success);
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600.