<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Citizen Login
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /rwanda_connect/dashboard/');
    exit;
}

// Check remember token
checkRememberToken();
if (isLoggedIn()) {
    header('Location: /rwanda_connect/dashboard/');
    exit;
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'password' => $_POST['password'] ?? '',
            'remember' => isset($_POST['remember']),
        ];
        
        $result = loginUser($formData['email'], $formData['password'], $formData['remember']);
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? '/rwanda_connect/dashboard/';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors = $result['errors'] ?? ['general' => 'Login failed'];
        }
    }
}

$csrfToken = generateCsrfToken();

$pageTitle = 'Login - Rwanda Connect';
$pageDescription = 'Sign in to your Rwanda Connect account to access personalized services and opportunities.';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="brand-mark">RC</span>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to access your personalized dashboard and services.</p>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= e(getFlash('success')[0]) ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-error">
                    <?= e(getFlash('error')[0]) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <?= e($errors['general']) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required" aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?= e($formData['email'] ?? '') ?>" 
                               required autocomplete="email"
                               aria-describedby="email_error"
                               class="<?= isset($errors['email']) ? 'error' : '' ?>">
                        <?php if (isset($errors['email'])): ?>
                            <span id="email_error" class="error-message"><?= e($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label-row">
                            <label for="password">Password <span class="required" aria-hidden="true">*</span></label>
                            <a href="/rwanda_connect/auth/forgot-password.php" class="forgot-link">Forgot password?</a>
                        </div>
                        <div class="password-field">
                            <input type="password" id="password" name="password" 
                                   required autocomplete="current-password"
                                   aria-describedby="password_error"
                                   class="<?= isset($errors['password']) ? 'error' : '' ?>">
                            <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                                <span class="eye-open">👁</span>
                                <span class="eye-closed" hidden>🙈</span>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <span id="password_error" class="error-message"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="remember" name="remember" 
                               <?= !empty($formData['remember']) ? 'checked' : '' ?>>
                        <label for="remember">Remember me for 30 days</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </form>
            
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="/rwanda_connect/auth/register.php">Create one</a></p>
            </div>
        </div>
        
        <div class="auth-features">
            <h2>What You Can Access</h2>
            <div class="features-grid">
                <article class="feature-card">
                    <span class="feature-icon">📊</span>
                    <h3>Personal Dashboard</h3>
                    <p>Track your applications, saved jobs, and service requests in one place.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">🔔</span>
                    <h3>Smart Notifications</h3>
                    <p>Get alerts for job matches, scholarship deadlines, and health campaigns.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">📱</span>
                    <h3>Mobile Friendly</h3>
                    <p>Access all services on any device - phone, tablet, or computer.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3>Secure & Private</h3>
                    <p>Your data is protected with encryption and privacy best practices.</p>
                </article>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Password visibility toggle
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden);
            btn.querySelector('.eye-open').hidden = isHidden;
            btn.querySelector('.eye-closed').hidden = !isHidden;
        });
    });
    
    // Focus email field on load
    document.getElementById('email')?.focus();
});
</script>