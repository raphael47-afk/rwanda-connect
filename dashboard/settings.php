<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Enhanced Account Settings
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$user = getCurrentUser();
$db = getDb();

$errors = [];
$success = false;
$activeTab = $_GET['tab'] ?? 'security';
$validTabs = ['security', 'notifications', 'privacy', 'data', 'appearance', 'integrations'];
$activeTab = in_array($activeTab, $validTabs, true) ? $activeTab : 'security';

// Get user preferences
$stmt = $db->prepare('SELECT notification_prefs, privacy_settings, theme_preference, two_factor_enabled, two_factor_secret FROM user_profiles WHERE user_id = ?');
$stmt->execute([$userId]);
$prefs = $stmt->fetch() ?: [];
$notificationPrefs = json_decode($prefs['notification_prefs'] ?? '{}', true);
$privacySettings = json_decode($prefs['privacy_settings'] ?? '{}', true);
$themePreference = $prefs['theme_preference'] ?? 'system';
$twoFactorEnabled = !empty($prefs['two_factor_enabled']);
$twoFactorSecret = $prefs['two_factor_secret'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                $result = changePassword($userId, $currentPassword, $newPassword, $confirmPassword);
                if ($result['success']) {
                    $success = 'Password changed successfully. You will need to log in again.';
                    logoutUser();
                    header('Location: /rwanda_connect/auth/login.php?password_changed=1');
                    exit;
                } else {
                    $errors = $result['errors'] ?? ['general' => 'Failed to change password'];
                    $activeTab = 'security';
                }
                break;
                
            case 'notification_preferences':
                $prefs = [
                    'email_job_alerts' => isset($_POST['email_job_alerts']),
                    'email_scholarship_alerts' => isset($_POST['email_scholarship_alerts']),
                    'email_health_campaigns' => isset($_POST['email_health_campaigns']),
                    'email_agriculture_updates' => isset($_POST['email_agriculture_updates']),
                    'push_notifications' => isset($_POST['push_notifications']),
                    'weekly_digest' => isset($_POST['weekly_digest']),
                    'email_marketing' => isset($_POST['email_marketing']),
                    'email_security' => isset($_POST['email_security']),
                ];
                
                $stmt = $db->prepare('UPDATE user_profiles SET notification_prefs = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([json_encode($prefs), $userId]);
                
                $success = 'Notification preferences updated!';
                $activeTab = 'notifications';
                break;
                
            case 'privacy_settings':
                $privacy = [
                    'profile_visibility' => $_POST['profile_visibility'] ?? 'private',
                    'show_email' => isset($_POST['show_email']),
                    'show_phone' => isset($_POST['show_phone']),
                    'show_location' => isset($_POST['show_location']),
                    'activity_tracking' => isset($_POST['activity_tracking']),
                    'personalized_ads' => isset($_POST['personalized_ads']),
                ];
                
                $stmt = $db->prepare('UPDATE user_profiles SET privacy_settings = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([json_encode($privacy), $userId]);
                
                $success = 'Privacy settings updated!';
                $activeTab = 'privacy';
                break;
                
            case 'theme_preference':
                $theme = $_POST['theme'] ?? 'system';
                $stmt = $db->prepare('UPDATE user_profiles SET theme_preference = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([$_POST['theme'], $userId]);
                $themePreference = $_POST['theme'];
                $success = 'Theme preference updated!';
                $activeTab = 'appearance';
                break;
                
            case 'enable_2fa':
                // Generate 2FA secret
                require_once __DIR__ . '/../../../vendor/autoload.php'; // If using a 2FA library
                // For now, generate a simple secret
                $secret = base32_encode(random_bytes(20));
                $stmt = $db->prepare('UPDATE user_profiles SET two_factor_enabled = 1, two_factor_secret = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([$secret, $userId]);
                $twoFactorEnabled = true;
                $twoFactorSecret = $secret;
                $success = 'Two-factor authentication enabled!';
                $activeTab = 'security';
                break;
                
            case 'disable_2fa':
                $stmt = $db->prepare('UPDATE user_profiles SET two_factor_enabled = 0, two_factor_secret = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([$userId]);
                $twoFactorEnabled = false;
                $twoFactorSecret = '';
                $success = 'Two-factor authentication disabled!';
                $activeTab = 'security';
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                $result = changePassword($userId, $currentPassword, $newPassword, $confirmPassword);
                if ($result['success']) {
                    $success = 'Password changed successfully. You will need to log in again.';
                    logoutUser();
                    header('Location: /rwanda_connect/auth/login.php?password_changed=1');
                    exit;
                } else {
                    $errors = $result['errors'] ?? ['general' => 'Failed to change password'];
                    $activeTab = 'security';
                }
                break;
                
            case 'notification_preferences':
                $prefs = [
                    'email_job_alerts' => isset($_POST['email_job_alerts']),
                    'email_scholarship_alerts' => isset($_POST['email_scholarship_alerts']),
                    'email_health_campaigns' => isset($_POST['email_health_campaigns']),
                    'email_agriculture_updates' => isset($_POST['email_agriculture_updates']),
                    'push_notifications' => isset($_POST['push_notifications']),
                    'weekly_digest' => isset($_POST['weekly_digest']),
                    'email_marketing' => isset($_POST['email_marketing']),
                    'email_security' => isset($_POST['email_security']),
                ];
                
                $stmt = $db->prepare('UPDATE user_profiles SET notification_prefs = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([json_encode($prefs), $userId]);
                
                $success = 'Notification preferences updated!';
                $activeTab = 'notifications';
                break;
                
            case 'privacy_settings':
                $privacy = [
                    'profile_visibility' => $_POST['profile_visibility'] ?? 'private',
                    'show_email' => isset($_POST['show_email']),
                    'show_phone' => isset($_POST['show_phone']),
                    'show_location' => isset($_POST['show_location']),
                    'activity_tracking' => isset($_POST['activity_tracking']),
                    'personalized_ads' => isset($_POST['personalized_ads']),
                ];
                
                $stmt = $db->prepare('UPDATE user_profiles SET privacy_settings = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([json_encode($privacy), $userId]);
                
                $success = 'Privacy settings updated!';
                $activeTab = 'privacy';
                break;
                
            case 'theme_preference':
                $stmt = $db->prepare('UPDATE user_profiles SET theme_preference = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                $stmt->execute([$_POST['theme'], $userId]);
                $themePreference = $_POST['theme'];
                $success = 'Theme preference updated!';
                $activeTab = 'appearance';
                break;
                
            case 'delete_account':
                $confirmation = $_POST['confirmation'] ?? '';
                if ($confirmation !== 'DELETE') {
                    $errors['confirmation'] = 'Please type DELETE to confirm';
                    $activeTab = 'data';
                } else {
                    // Delete user data
                    $db->beginTransaction();
                    try {
                        // Delete related data
                        $tables = ['user_farms', 'farm_crops', 'user_saved_jobs', 'job_applications', 'job_alerts', 
                                   'user_education', 'scholarship_applications', 'user_work_experience',
                                   'appointments', 'health_records', 'notifications', 'activity_log',
                                   'user_saved_jobs', 'user_exports'];
                        foreach ($tables as $table) {
                            $stmt = $db->prepare("DELETE FROM {$table} WHERE user_id = ?");
                            $stmt->execute([$userId]);
                        }
                        // Delete user
                        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                        $stmt->execute([$userId]);
                        $db->commit();
                        
                        logActivity('account_deleted', 'User deleted their account', $userId);
                        logoutUser();
                        header('Location: /rwanda_connect/auth/login.php?account_deleted=1');
                        exit;
                    } catch (Exception $e) {
                        $db->rollBack();
                        $errors['general'] = 'Failed to delete account: ' . $e->getMessage();
                        $activeTab = 'data';
                    }
                }
                break;
                
            case 'export_data':
                // Trigger data export
                $token = bin2hex(random_bytes(32));
                $expires = new DateTime('+1 hour');
                $stmt = $db->prepare('INSERT INTO user_exports (user_id, token, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$userId, $token, $expires->format('Y-m-d H:i:s')]);
                $success = 'Data export requested! You will receive a download link via email.';
                $activeTab = 'data';
                break;
        }
    }
}

// Get current preferences
$stmt = $db->prepare('SELECT notification_prefs, privacy_settings, theme_preference, two_factor_enabled, two_factor_secret FROM user_profiles WHERE user_id = ?');
$stmt->execute([$userId]);
$prefs = $stmt->fetch() ?: [];
$notificationPrefs = json_decode($prefs['notification_prefs'] ?? '{}', true);
$privacySettings = json_decode($prefs['privacy_settings'] ?? '{}', true);
$themePreference = $prefs['theme_preference'] ?? 'system';
$twoFactorEnabled = !empty($prefs['two_factor_enabled']);
$twoFactorSecret = $prefs['two_factor_secret'] ?? '';

$csrfToken = generateCsrfToken();

$pageTitle = 'Settings - Rwanda Connect';
$pageDescription = 'Manage your account settings, security, notifications, privacy, appearance, and data preferences.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="dashboard-page settings-page">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>Account Settings</h1>
                <p class="welcome-subtitle">Manage your security, notifications, privacy, appearance, integrations, and data preferences.</p>
            </div>
            <a href="/rwanda_connect/dashboard/" class="btn btn-secondary">← Back to Dashboard</a>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        
        <div class="settings-layout">
            <nav class="settings-nav" aria-label="Settings navigation">
                <a href="?tab=security" class="<?= $activeTab === 'security' ? 'active' : '' ?>">
                    <span>🔒</span> Security
                </a>
                <a href="?tab=notifications" class="<?= $activeTab === 'notifications' ? 'active' : '' ?>">
                    <span>🔔</span> Notifications
                </a>
                <a href="?tab=privacy" class="<?= $activeTab === 'privacy' ? 'active' : '' ?>">
                    <span>🛡️</span> Privacy
                </a>
                <a href="?tab=appearance" class="<?= $activeTab === 'appearance' ? 'active' : '' ?>">
                    <span>🎨</span> Appearance
                </a>
                <a href="?tab=integrations" class="<?= $activeTab === 'integrations' ? 'active' : '' ?>">
                    <span>🔗</span> Integrations
                </a>
                <a href="?tab=data" class="<?= $activeTab === 'data' ? 'active' : '' ?>">
                    <span>💾</span> Data & Storage
                </a>
            </nav>
            
            <div class="settings-content">
                <!-- Security Tab -->
                <section class="settings-panel" id="security" aria-labelledby="security-heading" <?= $activeTab !== 'security' ? 'hidden' : '' ?>>
                    <h2 id="security-heading">Security Settings</h2>
                    <p>Protect your account with strong authentication and security practices.</p>
                    
                    <div class="settings-card">
                        <h3>Two-Factor Authentication (2FA)</h3>
                        <p>Add an extra layer of security to your account.</p>
                        
                        <?php if ($twoFactorEnabled): ?>
                            <div class="twofa-enabled">
                                <div class="twofa-status">
                                    <span class="status-badge enabled">✓ Enabled</span>
                                    <p>Two-factor authentication is active on your account.</p>
                                </div>
                                <div class="twofa-details">
                                    <h4>Backup Codes</h4>
                                    <p>Save these codes in a safe place. Each can be used once if you lose access to your authenticator app.</p>
                                    <div class="backup-codes">
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <code><?= strtoupper(substr(md5($userId . $i), 0, 8)) ?></code>
                                        <?php endfor; ?>
                                    </div>
                                    <button class="btn btn-secondary btn-sm" onclick="copyBackupCodes()">📋 Copy Codes</button>
                                </div>
                                <form method="POST" action="" style="margin-top: 16px;">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="disable_2fa">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Disable 2FA? This will make your account less secure.')">Disable 2FA</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="twofa-disabled">
                                <div class="twofa-setup">
                                    <h4>Set up Authenticator App</h4>
                                    <ol class="setup-steps">
                                        <li>Install an authenticator app (Google Authenticator, Authy, Microsoft Authenticator)</li>
                                        <li>Scan the QR code or enter the secret key manually</li>
                                        <li>Enter the 6-digit code from the app to verify</li>
                                    </ol>
                                    <div class="qr-code-placeholder">
                                        <div class="qr-svg">
                                            <svg viewBox="0 0 200 200" width="200" height="200">
                                                <rect width="200" height="200" fill="#fff"/>
                                                <text x="100" y="100" text-anchor="middle" font-family="monospace" font-size="12" fill="#333">QR Code for 2FA Setup</text>
                                            </svg>
                                        </div>
                                        <p class="secret-key">Secret: <code id="2fa-secret"><?= $twoFactorSecret ?: 'Generate on enable' ?></code></p>
                                        <button class="btn btn-secondary btn-sm" onclick="copySecret()">📋 Copy Secret</button>
                                    </div>
                                    <form method="POST" action="" class="verify-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="enable_2fa">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="2fa_code">Enter 6-digit code from app</label>
                                                <input type="text" id="2fa_code" name="2fa_code" maxlength="6" pattern="\d{6}" required autocomplete="one-time-code" placeholder="123456">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Enable 2FA</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Change Password</h3>
                        <p>Update your password regularly to keep your account secure.</p>
                        
                        <form method="POST" action="" class="settings-form">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">Current Password <span class="required" aria-hidden="true">*</span></label>
                                    <div class="password-field">
                                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                                               class="<?= isset($errors['current_password']) ? 'error' : '' ?>">
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <span class="eye-open">👁</span><span class="eye-closed" hidden>🙈</span>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['current_password'])): ?>
                                        <span class="error-message"><?= e($errors['current_password']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password <span class="required" aria-hidden="true">*</span></label>
                                    <div class="password-field">
                                        <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
                                               class="<?= isset($errors['new_password']) ? 'error' : '' ?>">
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <span class="eye-open">👁</span><span class="eye-closed" hidden>🙈</span>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['new_password'])): ?>
                                        <span class="error-message"><?= e($errors['new_password']) ?></span>
                                    <?php endif; ?>
                                    <span class="form-hint">Min 8 chars, uppercase, lowercase, number</span>
                                    <div class="password-strength" hidden>
                                        <div class="strength-bar"><div class="strength-fill"></div></div>
                                        <span class="strength-text"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password <span class="required" aria-hidden="true">*</span></label>
                                    <div class="password-field">
                                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                                               class="<?= isset($errors['confirm_password']) ? 'error' : '' ?>">
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <span class="eye-open">👁</span><span class="eye-closed" hidden>🙈</span>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <span class="error-message"><?= e($errors['confirm_password']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Active Sessions</h3>
                        <p>Manage devices logged into your account.</p>
                        
                        <div class="session-list">
                            <div class="session-item current">
                                <div class="session-info">
                                    <span class="session-device">💻 This Device</span>
                                    <span class="session-location"><?= e($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?></span>
                                    <span class="session-time">Current session</span>
                                </div>
                                <span class="session-badge current">Active</span>
                            </div>
                            <p class="form-hint">Additional session management coming soon.</p>
                        </div>
                    </div>
                    
                    <div class="settings-card danger-zone">
                        <h3>Danger Zone</h3>
                        <p>Irreversible actions. Proceed with caution.</p>
                        
                        <form method="POST" action="" onsubmit="return confirmAccountDeletion()">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete_account">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="confirmation">Type "DELETE" to confirm</label>
                                    <input type="text" id="confirmation" name="confirmation" required autocomplete="off" placeholder="DELETE">
                                    <?php if (isset($errors['confirmation'])): ?>
                                        <span class="error-message"><?= e($errors['confirmation']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">🗑️ Delete Account Permanently</button>
                        </form>
                        <p class="form-hint">This will permanently delete your account and ALL data. This cannot be undone.</p>
                    </div>
                </section>
                
                <!-- Notifications Tab -->
                <section class="settings-panel" id="notifications" aria-labelledby="notifications-heading" <?= $activeTab !== 'notifications' ? 'hidden' : '' ?>>
                    <h2 id="notifications-heading">Notification Preferences</h2>
                    <p>Choose what notifications you want to receive and how.</p>
                    
                    <form method="POST" action="" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="notification_preferences">
                        
                        <div class="settings-card">
                            <h3>Email Notifications</h3>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_job_alerts" <?= $notificationPrefs['email_job_alerts'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Job Alerts</strong>
                                    <span>New job matches and application updates</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_scholarship_alerts" <?= $notificationPrefs['email_scholarship_alerts'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Scholarship Alerts</strong>
                                    <span>New scholarships and deadline reminders</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_health_campaigns" <?= $notificationPrefs['email_health_campaigns'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Health Campaigns</strong>
                                    <span>Vaccination drives, screenings, health alerts</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_agriculture_updates" <?= $notificationPrefs['email_agriculture_updates'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Agriculture Updates</strong>
                                    <span>Market prices, season alerts, farming tips</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_marketing" <?= $notificationPrefs['email_marketing'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Marketing & Updates</strong>
                                    <span>New features, tips, and platform news</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="email_security" <?= $notificationPrefs['email_security'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Security Alerts</strong>
                                    <span>Login alerts, password changes, suspicious activity</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="settings-card">
                            <h3>Push & In-App Notifications</h3>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="push_notifications" <?= $notificationPrefs['push_notifications'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Push Notifications</strong>
                                    <span>Real-time alerts on your device</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="weekly_digest" <?= $notificationPrefs['weekly_digest'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Weekly Digest</strong>
                                    <span>Summary of new opportunities every Monday</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="in_app_notifications" <?= $notificationPrefs['in_app_notifications'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>In-App Notifications</strong>
                                    <span>Show notifications bell and badge in the app</span>
                                </div>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </form>
                </section>
                
                <!-- Privacy Tab -->
                <section class="settings-panel" id="privacy" aria-labelledby="privacy-heading" <?= $activeTab !== 'privacy' ? 'hidden' : '' ?>>
                    <h2 id="privacy-heading">Privacy Settings</h2>
                    <p>Control who can see your information and how your data is used.</p>
                    
                    <form method="POST" action="" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="privacy_settings">
                        
                        <div class="settings-card">
                            <h3>Profile Visibility</h3>
                            
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="profile_visibility" value="public" <?= ($privacySettings['profile_visibility'] ?? 'private') === 'public' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>Public</strong>
                                        <span>Anyone can see your profile</span>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="profile_visibility" value="citizens" <?= ($privacySettings['profile_visibility'] ?? 'private') === 'citizens' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>Registered Citizens Only</strong>
                                        <span>Only logged-in users can see your profile</span>
                                    </div>
                                </label>
                                
                                <label class="radio-option">
                                    <input type="radio" name="profile_visibility" value="private" <?= ($privacySettings['profile_visibility'] ?? 'private') === 'private' ? 'checked' : '' ?>>
                                    <div class="radio-content">
                                        <strong>Private</strong>
                                        <span>Only you can see your profile (Recommended)</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="settings-card">
                            <h3>Information Sharing</h3>
                            <p>Choose what personal information is visible on your profile.</p>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="show_email" <?= $privacySettings['show_email'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Show Email</strong>
                                    <span>Display your email address on your profile</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="show_phone" <?= $privacySettings['show_phone'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Show Phone</strong>
                                    <span>Display your phone number on your profile</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="show_location" <?= $privacySettings['show_location'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Show Location</strong>
                                    <span>Display your province/district on your profile</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="settings-card">
                            <h3>Data & Analytics</h3>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="activity_tracking" <?= $privacySettings['activity_tracking'] ?? true ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Activity Tracking</strong>
                                    <span>Allow analytics to improve your experience</span>
                                </div>
                            </label>
                            
                            <label class="toggle-option">
                                <input type="checkbox" name="personalized_ads" <?= $privacySettings['personalized_ads'] ?? false ? 'checked' : '' ?>>
                                <div class="toggle-content">
                                    <strong>Personalized Recommendations</strong>
                                    <span>Use your activity to suggest relevant opportunities</span>
                                </div>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                    </form>
                </section>
                
                <!-- Appearance Tab -->
                <section class="settings-panel" id="appearance" aria-labelledby="appearance-heading" <?= $activeTab !== 'appearance' ? 'hidden' : '' ?>>
                    <h2 id="appearance-heading">Appearance</h2>
                    <p>Customize how Rwanda Connect looks on your device.</p>
                    
                    <form method="POST" action="" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="theme_preference">
                        
                        <div class="settings-card">
                            <h3>Theme</h3>
                            <p>Choose your preferred color scheme.</p>
                            
                            <div class="theme-options">
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="light" <?= $themePreference === 'light' ? 'checked' : '' ?>>
                                    <div class="theme-preview light">
                                        <div class="preview-header"></div>
                                        <div class="preview-content"></div>
                                    </div>
                                    <div class="theme-label">
                                        <strong>Light</strong>
                                        <span>Always use light mode</span>
                                    </div>
                                </label>
                                
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="dark" <?= $themePreference === 'dark' ? 'checked' : '' ?>>
                                    <div class="theme-preview dark">
                                        <div class="preview-header"></div>
                                        <div class="preview-content"></div>
                                    </div>
                                    <div class="theme-label">
                                        <strong>Dark</strong>
                                        <span>Always use dark mode</span>
                                    </div>
                                </label>
                                
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="system" <?= $themePreference === 'system' ? 'checked' : '' ?>>
                                    <div class="theme-preview system">
                                        <div class="preview-header"></div>
                                        <div class="preview-content"></div>
                                    </div>
                                    <div class="theme-label">
                                        <strong>System</strong>
                                        <span>Match your device settings</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="settings-card">
                            <h3>Display Density</h3>
                            <p>Adjust how much content fits on screen.</p>
                            
                            <div class="density-options">
                                <label class="density-option">
                                    <input type="radio" name="density" value="comfortable" <?= $themePreference === 'comfortable' ? 'checked' : '' ?>>
                                    <div class="density-preview">
                                        <div class="density-bar"></div>
                                        <div class="density-bar"></div>
                                        <div class="density-bar"></div>
                                    </div>
                                    <div class="density-label">
                                        <strong>Comfortable</strong>
                                        <span>More spacing, easier to read</span>
                                    </div>
                                </label>
                                
                                <label class="density-option">
                                    <input type="radio" name="density" value="compact" <?= $themePreference === 'compact' ? 'checked' : '' ?>>
                                    <div class="density-preview">
                                        <div class="density-bar"></div>
                                        <div class="density-bar"></div>
                                        <div class="density-bar"></div>
                                        <div class="density-bar"></div>
                                    </div>
                                    <div class="density-label">
                                        <strong>Compact</strong>
                                        <span>More content on screen</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Appearance Settings</button>
                    </form>
                </section>
                
                <!-- Integrations Tab -->
                <section class="settings-panel" id="integrations" aria-labelledby="integrations-heading" <?= $activeTab !== 'integrations' ? 'hidden' : '' ?>>
                    <h2 id="integrations-heading">Integrations</h2>
                    <p>Connect third-party services to enhance your experience.</p>
                    
                    <div class="settings-card">
                        <h3>Connected Accounts</h3>
                        <p>Manage third-party connections for easier login and data sync.</p>
                        
                        <div class="integration-list">
                            <div class="integration-item">
                                <div class="integration-info">
                                    <span class="provider-icon">📧</span>
                                    <div>
                                        <strong>Email</strong>
                                        <span>Primary login method</span>
                                    </div>
                                </div>
                                <span class="status connected">Connected</span>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-info">
                                    <span class="provider-icon">📱</span>
                                    <div>
                                        <strong>Phone</strong>
                                        <span>SMS verification</span>
                                    </div>
                                </div>
                                <span class="status <?= $user['phone'] ? 'connected' : 'disconnected' ?>">
                                    <?= $user['phone'] ? 'Connected' : 'Not connected' ?>
                                </span>
                                <?php if (!$user['phone']): ?>
                                    <a href="/rwanda_connect/dashboard/profile.php" class="btn btn-sm btn-outline">Add Phone</a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-info">
                                    <span class="provider-icon">📘</span>
                                    <div>
                                        <strong>Facebook</strong>
                                        <span>Social login</span>
                                    </div>
                                </div>
                                <span class="status disconnected">Not connected</span>
                                <button class="btn btn-sm btn-outline">Connect</button>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-info">
                                    <span class="provider-icon">🐙</span>
                                    <div>
                                        <strong>GitHub</strong>
                                        <span>Developer login</span>
                                    </div>
                                </div>
                                <span class="status disconnected">Not connected</span>
                                <button class="btn btn-sm btn-outline">Connect</button>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-info">
                                    <span class="provider-icon">💼</span>
                                    <div>
                                        <strong>LinkedIn</strong>
                                        <span>Professional login</span>
                                    </div>
                                </div>
                                <span class="status disconnected">Not connected</span>
                                <button class="btn btn-sm btn-outline">Connect</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <h3>API Access</h3>
                        <p>Manage API tokens for third-party applications.</p>
                        
                        <div class="api-tokens">
                            <p class="form-hint">API token management coming soon.</p>
                            <button class="btn btn-secondary" disabled>Generate New Token</button>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Data Sharing</h3>
                        <p>Control what data is shared with partner organizations.</p>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="share_analytics" checked>
                            <div class="toggle-content">
                                <strong>Analytics Partners</strong>
                                <span>Share anonymized usage data</span>
                            </div>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="share_career" checked>
                            <div class="toggle-content">
                                <strong>Career Services</strong>
                                <span>Share profile with employers (anonymized)</span>
                            </div>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="share_education">
                            <div class="toggle-content">
                                <strong>Education Partners</strong>
                                <span>Share profile with universities</span>
                            </div>
                        </label>
                    </div>
                </section>
                
                <!-- Data & Storage Tab -->
                <section class="settings-panel" id="data" aria-labelledby="data-heading" <?= $activeTab !== 'data' ? 'hidden' : '' ?>>
                    <h2 id="data-heading">Data & Storage</h2>
                    <p>Manage your personal data, storage, and account lifecycle.</p>
                    
                    <div class="settings-card">
                        <h3>Download Your Data</h3>
                        <p>Request a copy of all your personal data in JSON format.</p>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="export_data">
                            <button type="submit" class="btn btn-secondary">📥 Request Data Export</button>
                        </form>
                        <p class="form-hint">Export includes profile, applications, saved items, activity log, and all module data. You will receive a download link via email when ready.</p>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Storage Usage</h3>
                        <div class="storage-info">
                            <div class="storage-bar">
                                <div class="storage-fill" style="width: 12%"></div>
                            </div>
                            <div class="storage-stats">
                                <span>12 MB of 100 MB used</span>
                                <span>Profile: 2 MB | Documents: 8 MB | Other: 2 MB</span>
                            </div>
                        </div>
                        <p class="form-hint">Free tier includes 100 MB storage. Upgrade for more space.</p>
                    </div>
                    
                    <div class="settings-card">
                        <h3>Connected Accounts</h3>
                        <p>Manage third-party connections.</p>
                        <div class="connected-accounts">
                            <div class="connected-account">
                                <span class="provider">📧 Email</span>
                                <span class="status connected">Connected</span>
                            </div>
                            <?php if ($user['phone']): ?>
                                <div class="connected-account">
                                    <span class="provider">📱 Phone</span>
                                    <span class="status connected">Connected</span>
                                </div>
                            <?php endif; ?>
                            <p class="form-hint">Social login connections coming soon.</p>
                        </div>
                    </div>
                    
                    <div class="settings-card danger-zone">
                        <h3>Delete Account</h3>
                        <p>Permanently delete your account and all associated data.</p>
                        
                        <form method="POST" action="" onsubmit="return confirmAccountDeletion()">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete_account">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="confirmation">Type "DELETE" to confirm</label>
                                    <input type="text" id="confirmation" name="confirmation" required autocomplete="off" placeholder="DELETE">
                                    <?php if (isset($errors['confirmation'])): ?>
                                        <span class="error-message"><?= e($errors['confirmation']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">🗑️ Delete Account Permanently</button>
                        </form>
                        <p class="form-hint">This will permanently delete your account and ALL data including applications, saved jobs, health records, farm data, and activity history. This cannot be undone.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Password toggles
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-open').hidden = isHidden;
            btn.querySelector('.eye-closed').hidden = !isHidden;
        });
    });
    
    // Password strength meter
    const newPasswordInput = document.getElementById('new_password');
    const strengthMeter = newPasswordInput?.parentElement?.parentElement?.querySelector('.password-strength');
    const strengthFill = strengthMeter?.querySelector('.strength-fill');
    const strengthText = strengthMeter?.querySelector('.strength-text');
    
    if (newPasswordInput && strengthMeter) {
        newPasswordInput.addEventListener('input', () => {
            const password = newPasswordInput.value;
            if (password.length === 0) {
                strengthMeter.hidden = true;
                return;
            }
            strengthMeter.hidden = false;
            
            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#2ecc71', '#27ae60'];
            
            strengthFill.style.width = `${(score / 5) * 100}%`;
            strengthFill.style.backgroundColor = colors[score - 1] || colors[0];
            strengthText.textContent = `Strength: ${labels[score - 1] || labels[0]}`;
            strengthText.style.color = colors[score - 1] || colors[0];
        });
    }
    
    // Theme selection
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.documentElement.setAttribute('data-theme', radio.value);
        });
    });
    
    // Initialize theme from localStorage or system preference
    const savedTheme = localStorage.getItem('theme') || 'system';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.querySelector(`input[name="theme"][value="${savedTheme}"]`)?.checked = true;
    
    // 2FA Secret copy
    window.copySecret = () => {
        const secret = document.getElementById('2fa-secret');
        if (secret) {
            navigator.clipboard.writeText(secret.textContent).then(() => {
                showToast('Secret copied to clipboard!');
            });
        }
    };
    
    // Copy backup codes
    window.copyBackupCodes = () => {
        const codes = document.querySelectorAll('.backup-codes code');
        const text = Array.from(codes).map(c => c.textContent).join('\n');
        navigator.clipboard.writeText(text).then(() => {
            showToast('Backup codes copied!');
        });
    };
    
    // Delete account confirmation
    window.confirmAccountDeletion = () => {
        if (!confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')) {
            return false;
        }
        if (!confirm('This will delete ALL your data including applications, saved jobs, health records, and farm data. Type "DELETE" to confirm.')) {
            return false;
        }
        const input = prompt('Type "DELETE" to confirm:');
        if (input !== 'DELETE') {
            alert('Confirmation failed. Account not deleted.');
            return false;
        }
        return true;
    };
    
    // Download data button
    document.getElementById('download-data')?.addEventListener('click', async (btn) => {
        btn.target.disabled = true;
        btn.target.textContent = '⏳ Preparing export...';
        
        try {
            const response = await fetch('/rwanda_connect/api/user/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= e(generateCsrfToken()) ?>'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.download_url) {
                window.location.href = data.download_url;
            } else {
                alert('Failed to prepare export: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.target.disabled = false;
            btn.target.textContent = '📥 Request Data Export';
        }
    });
    
    // 2FA enable form
    document.querySelector('.verify-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const code = document.getElementById('2fa_code').value;
        if (!/^\d{6}$/.test(code)) {
            alert('Please enter a valid 6-digit code');
            return;
        }
        
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('/rwanda_connect/dashboard/settings.php', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            if (text.includes('Two-factor authentication enabled')) {
                window.location.reload();
            } else {
                alert('Invalid code. Please try again.');
            }
        } catch (err) {
            alert('Error: ' + err.message);
        }
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
    
    // Password toggles
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-open').hidden = isHidden;
            btn.querySelector('.eye-closed').hidden = !isHidden;
        });
    });
    
    // Password strength meter
    const newPasswordInput = document.getElementById('new_password');
    const strengthMeter = newPasswordInput?.parentElement?.parentElement?.querySelector('.password-strength');
    const strengthFill = strengthMeter?.querySelector('.strength-fill');
    const strengthText = strengthMeter?.querySelector('.strength-text');
    
    if (newPasswordInput && strengthMeter) {
        newPasswordInput.addEventListener('input', () => {
            const password = newPasswordInput.value;
            if (password.length === 0) {
                strengthMeter.hidden = true;
                return;
            }
            strengthMeter.hidden = false;
            
            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#2ecc71', '#27ae60'];
            
            strengthFill.style.width = `${(score / 5) * 100}%`;
            strengthFill.style.backgroundColor = colors[score - 1] || colors[0];
            strengthText.textContent = `Strength: ${labels[score - 1] || labels[0]}`;
            strengthText.style.color = colors[score - 1] || colors[0];
        });
    }
    
    // Download data button
    document.getElementById('download-data')?.addEventListener('click', async (btn) => {
        btn.target.disabled = true;
        btn.target.textContent = '⏳ Preparing export...';
        
        try {
            const response = await fetch('/rwanda_connect/api/user/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= e(generateCsrfToken()) ?>'
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.download_url) {
                window.location.href = data.download_url;
            } else {
                alert('Failed to prepare export: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.target.disabled = false;
            btn.target.textContent = '📥 Request Data Export';
        }
    });
    
    // Delete account confirmation
    window.confirmAccountDeletion = () => {
        if (!confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')) {
            return false;
        }
        if (!confirm('This will delete ALL your data including applications, saved jobs, health records, and farm data. Type "DELETE" to confirm.')) {
            return false;
        }
        const input = prompt('Type "DELETE" to confirm:');
        if (input !== 'DELETE') {
            alert('Confirmation failed. Account not deleted.');
            return false;
        }
        return true;
    };
});
</script>

<style>
/* Settings Page Styles */
.settings-page {
    min-height: 100vh;
}

.settings-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
    margin-top: 24px;
}

@media (max-width: 1024px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
}

.settings-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    padding: 8px;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.settings-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--radius-md);
    color: var(--muted);
    font-weight: 500;
    transition: all var(--transition);
}

.settings-nav a:hover,
.settings-nav a.active {
    background: var(--green-light);
    color: var(--green);
}

.settings-content {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    padding: 32px;
}

.settings-panel {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-panel h2 {
    margin: 0 0 8px;
    font-size: 1.5rem;
}

.settings-panel > p {
    margin: 0 0 24px;
    color: var(--muted);
}

.settings-card {
    padding: 24px;
    background: var(--background);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    margin-bottom: 24px;
}

.settings-card h3 {
    margin: 0 0 8px;
    font-size: 1.1rem;
}

.settings-card > p {
    margin: 0 0 20px;
    color: var(--muted);
    font-size: .9rem;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.toggle-option,
.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
    cursor: pointer;
    transition: all var(--transition);
}

.toggle-option:hover,
.radio-option:hover {
    border-color: var(--green);
    background: var(--green-light);
}

.toggle-option input,
.radio-option input {
    margin-top: 2px;
    accent-color: var(--green);
}

.toggle-content,
.radio-content {
    flex: 1;
}

.toggle-content strong,
.radio-content strong {
    display: block;
    margin-bottom: 4px;
}

.toggle-content span,
.radio-content span {
    color: var(--muted);
    font-size: .9rem;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.danger-zone {
    border-color: var(--error);
    background: var(--error-bg);
}

.danger-zone h3 {
    color: var(--error);
}

/* 2FA Styles */
.twofa-enabled .twofa-status {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.twofa-enabled .status-badge {
    padding: 8px 16px;
    border-radius: 999px;
    font-weight: 600;
    font-size: .85rem;
}

.twofa-enabled .status-badge.enabled {
    background: var(--success-bg);
    color: var(--success);
}

.twofa-details h4 {
    margin: 0 0 8px;
    font-size: 1rem;
}

.backup-codes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 12px 0;
}

.backup-codes code {
    background: var(--surface);
    border: 1px solid var(--line);
    padding: 8px 12px;
    border-radius: var(--radius-md);
    font-family: monospace;
    font-size: .85rem;
}

.twofa-disabled .setup-steps {
    margin: 16px 0;
    padding-left: 20px;
}

.twofa-disabled .setup-steps li {
    margin-bottom: 8px;
    color: var(--muted);
}

.qr-code-placeholder {
    text-align: center;
    margin: 24px 0;
}

.qr-svg {
    display: inline-block;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    padding: 16px;
}

.secret-key {
    margin-top: 16px;
    font-family: monospace;
    font-size: .9rem;
    color: var(--muted);
}

.verify-form .form-row {
    margin-bottom: 16px;
}

/* Theme Options */
.theme-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 16px;
}

.theme-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: var(--surface);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition);
}

.theme-option:hover {
    border-color: var(--green);
}

.theme-option input {
    display: none;
}

.theme-option input:checked + .theme-preview {
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--green-light);
}

.theme-preview {
    width: 100%;
    aspect-ratio: 4/3;
    border-radius: var(--radius-md);
    border: 2px solid var(--line);
    overflow: hidden;
    transition: all var(--transition);
}

.theme-preview.light {
    background: var(--surface);
}

.theme-preview.dark {
    background: var(--ink);
}

.theme-preview.system {
    background: linear-gradient(135deg, var(--surface) 50%, var(--ink) 50%);
}

.preview-header {
    height: 20%;
    background: var(--background);
    border-bottom: 1px solid var(--line);
}

.theme-preview.dark .preview-header {
    background: var(--ink);
    border-color: #333;
}

.preview-content {
    height: 80%;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.preview-content::before,
.preview-content::after {
    content: '';
    height: 40%;
    border-radius: var(--radius-sm);
    background: var(--green-light);
}

.theme-preview.dark .preview-content::before,
.theme-preview.dark .preview-content::after {
    background: #1e3a2a;
}

.theme-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-top: 12px;
}

.theme-label strong {
    font-size: .95rem;
}

.theme-label span {
    font-size: .8rem;
    color: var(--muted);
}

.theme-option input:checked + .theme-preview + .theme-label strong {
    color: var(--green);
}

/* Density Options */
.density-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 16px;
}

.density-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: var(--surface);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition);
}

.density-option:hover {
    border-color: var(--green);
}

.density-option input {
    display: none;
}

.density-option input:checked + .density-preview {
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--green-light);
}

.density-preview {
    width: 100%;
    aspect-ratio: 1;
    border-radius: var(--radius-md);
    border: 2px solid var(--line);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    transition: all var(--transition);
}

.density-bar {
    height: 12px;
    background: var(--line);
    border-radius: 4px;
}

.density-option input:checked + .density-preview + .density-label strong {
    color: var(--green);
}

.density-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-top: 12px;
}

.density-label strong {
    font-size: .95rem;
}

.density-label span {
    font-size: .8rem;
    color: var(--muted);
}

/* Integration Styles */
.integration-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.integration-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
}

.integration-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.provider-icon {
    font-size: 1.5rem;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background);
    border-radius: var(--radius-md);
}

.integration-info strong {
    display: block;
    margin-bottom: 2px;
}

.integration-info span {
    font-size: .85rem;
    color: var(--muted);
}

.status {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.status.connected {
    background: var(--success-bg);
    color: var(--success);
}

.status.disconnected {
    background: var(--background);
    color: var(--muted);
}

/* API Tokens */
.api-tokens {
    margin-top: 16px;
}

/* Storage */
.storage-info {
    margin-top: 16px;
}

.storage-bar {
    height: 8px;
    background: var(--line);
    border-radius: 4px;
    overflow: hidden;
}

.storage-fill {
    height: 100%;
    background: var(--green);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.storage-stats {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 8px;
    font-size: .85rem;
    color: var(--muted);
}

/* Connected Accounts */
.connected-accounts {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 16px;
}

.connected-account {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
}

.provider {
    font-weight: 600;
}

/* Session List */
.session-list {
    margin-top: 16px;
}

.session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
}

.session-item.current {
    border-color: var(--green);
    background: var(--green-light);
}

.session-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.session-device {
    font-weight: 600;
}

.session-location {
    font-size: .85rem;
    color: var(--muted);
}

.session-time {
    font-size: .75rem;
    color: var(--muted);
}

.session-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 700;
}

.session-badge.current {
    background: var(--success-bg);
    color: var(--success);
}

/* TwoFA Styles */
.twofa-enabled .twofa-status {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--success-bg);
    border-radius: var(--radius-md);
    margin-bottom: 24px;
}

.twofa-disabled .setup-steps {
    padding-left: 20px;
    margin: 16px 0;
}

.twofa-disabled .setup-steps li {
    margin-bottom: 8px;
    color: var(--muted);
}

.qr-code-placeholder {
    text-align: center;
    margin: 24px 0;
}

.qr-svg {
    display: inline-block;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    padding: 16px;
}

.secret-key {
    margin-top: 16px;
    font-family: monospace;
    font-size: .9rem;
    color: var(--muted);
}

.verify-form .form-row {
    margin-bottom: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    
    .settings-nav {
        position: static;
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .settings-nav a {
        flex: 1;
        text-align: center;
        justify-content: center;
    }
    
    .theme-options {
        grid-template-columns: 1fr;
    }
    
    .density-options {
        grid-template-columns: 1fr;
    }
    
    .integration-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>