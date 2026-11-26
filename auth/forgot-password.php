<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Forgot Password
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
$formData = [];
$step = 'request'; // request, reset
$token = $_GET['token'] ?? '';

if ($token) {
    $step = 'reset';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } elseif ($step === 'request') {
        $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
        
        if (empty($formData['email']) || !validateEmail($formData['email'])) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $result = requestPasswordReset($formData['email']);
            if ($result['success']) {
                setFlash('success', 'If an account exists with that email, a password reset link has been sent.');
                header('Location: /rwanda_connect/auth/forgot-password.php');
                exit;
            }
        }
    } elseif ($step === 'reset') {
        $formData['password'] = $_POST['password'] ?? '';
        $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
        
        $result = resetPassword($token, $formData['password'], $formData['confirm_password']);
        
        if ($result['success']) {
            setFlash('success', 'Your password has been reset. You can now log in.');
            header('Location: /rwanda_connect/auth/login.php');
            exit;
        } else {
            $errors = $result['errors'] ?? ['general' => 'Password reset failed'];
        }
    }
}

$csrfToken = generateCsrfToken();

$pageTitle = $step === 'reset' ? 'Reset Password - Rwanda Connect' : 'Forgot Password - Rwanda Connect';
$pageDescription = $step === 'reset' ? 'Set a new password for your account.' : 'Request a password reset link for your Rwanda Connect account.';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="brand-mark">RC</span>
                </div>
                <?php if ($step === 'request'): ?>
                    <h1>Forgot Password?</h1>
                    <p>Enter your email and we'll send you a link to reset your password.</p>
                <?php else: ?>
                    <h1>Reset Password</h1>
                    <p>Enter your new password below.</p>
                <?php endif; ?>
            </div>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= e(getFlash('success')[0]) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <?= e($errors['general']) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <?php if ($step === 'request'): ?>
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
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Send Reset Link</button>
                <?php else: ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">New Password <span class="required" aria-hidden="true">*</span></label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" 
                                       required autocomplete="new-password"
                                       aria-describedby="password_error password_help password_strength"
                                       class="<?= isset($errors['password']) ? 'error' : '' ?>">
                                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                                    <span class="eye-open">👁</span>
                                    <span class="eye-closed" hidden>🙈</span>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span id="password_error" class="error-message"><?= e($errors['password']) ?></span>
                            <?php endif; ?>
                            <span id="password_help" class="form-hint">Minimum 8 characters with uppercase, lowercase, and number</span>
                            <div id="password_strength" class="password-strength" aria-live="polite" hidden>
                                <div class="strength-bar"><div class="strength-fill"></div></div>
                                <span class="strength-text"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required" aria-hidden="true">*</span></label>
                            <div class="password-field">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       required autocomplete="new-password"
                                       aria-describedby="confirm_password_error"
                                       class="<?= isset($errors['confirm_password']) ? 'error' : '' ?>">
                                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                                    <span class="eye-open">👁</span>
                                    <span class="eye-closed" hidden>🙈</span>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span id="confirm_password_error" class="error-message"><?= e($errors['confirm_password']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Reset Password</button>
                <?php endif; ?>
            </form>
            
            <div class="auth-footer">
                <?php if ($step === 'request'): ?>
                    <p>Remember your password? <a href="/rwanda_connect/auth/login.php">Sign in</a></p>
                <?php else: ?>
                    <p><a href="/rwanda_connect/auth/forgot-password.php">Request a new link</a></p>
                <?php endif; ?>
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
    
    // Password strength meter
    const passwordInput = document.getElementById('password');
    const strengthMeter = document.getElementById('password_strength');
    const strengthFill = strengthMeter?.querySelector('.strength-fill');
    const strengthText = strengthMeter?.querySelector('.strength-text');
    
    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
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
    
    // Focus first input
    document.querySelector('input[type="email"], input[type="password"]')?.focus();
});
</script>