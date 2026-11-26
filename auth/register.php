<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Citizen Registration
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /rwanda_connect/dashboard/');
    exit;
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'phone' => trim($_POST['phone'] ?? ''),
            'national_id' => trim($_POST['national_id'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
        ];
        
        $result = registerUser($formData);
        
        if ($result['success']) {
            // Auto-login after registration
            $loginResult = loginUser($formData['email'], $formData['password']);
            if ($loginResult['success']) {
                redirectWith('/rwanda_connect/dashboard/', 'success', 'Welcome to Rwanda Connect! Your account has been created.');
            } else {
                redirectWith('/rwanda_connect/auth/login.php', 'success', 'Registration successful! Please log in.');
            }
        } else {
            $errors = $result['errors'] ?? ['general' => 'Registration failed'];
        }
    }
}

$csrfToken = generateCsrfToken();
$provinces = getProvinces();

$pageTitle = 'Register - Rwanda Connect';
$pageDescription = 'Create your citizen account to access government services, job opportunities, education resources, and healthcare information.';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="brand-mark">RC</span>
                </div>
                <h1>Create Your Account</h1>
                <p>Join Rwanda Connect to access services, opportunities, and community resources.</p>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required" aria-hidden="true">*</span></label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?= e($formData['full_name'] ?? '') ?>" 
                               required autocomplete="name"
                               aria-describedby="full_name_error"
                               class="<?= isset($errors['full_name']) ? 'error' : '' ?>">
                        <?php if (isset($errors['full_name'])): ?>
                            <span id="full_name_error" class="error-message"><?= e($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
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
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?= e($formData['phone'] ?? '') ?>" 
                               placeholder="+250 7XX XXX XXX"
                               autocomplete="tel"
                               aria-describedby="phone_error phone_help"
                               class="<?= isset($errors['phone']) ? 'error' : '' ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <span id="phone_error" class="error-message"><?= e($errors['phone']) ?></span>
                        <?php endif; ?>
                        <span id="phone_help" class="form-hint">Rwanda format: +250 7XX XXX XXX or 07XX XXX XXX</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="national_id">National ID (Optional)</label>
                        <input type="text" id="national_id" name="national_id" 
                               value="<?= e($formData['national_id'] ?? '') ?>" 
                               placeholder="16-digit national ID"
                               maxlength="16"
                               autocomplete="off"
                               aria-describedby="national_id_error national_id_help"
                               class="<?= isset($errors['national_id']) ? 'error' : '' ?>">
                        <?php if (isset($errors['national_id'])): ?>
                            <span id="national_id_error" class="error-message"><?= e($errors['national_id']) ?></span>
                        <?php endif; ?>
                        <span id="national_id_help" class="form-hint">16-digit Rwanda national ID number</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required" aria-hidden="true">*</span></label>
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
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="/rwanda_connect/terms.php">Terms of Service</a> and <a href="/rwanda_connect/privacy.php">Privacy Policy</a></label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="/rwanda_connect/auth/login.php">Sign in</a></p>
            </div>
        </div>
        
        <div class="auth-benefits">
            <h2>Why Join Rwanda Connect?</h2>
            <ul class="benefits-list">
                <li>
                    <span class="benefit-icon">🌱</span>
                    <div>
                        <strong>Agriculture</strong>
                        <p>Market prices, farming tips, season calendars</p>
                    </div>
                </li>
                <li>
                    <span class="benefit-icon">💼</span>
                    <div>
                        <strong>Employment</strong>
                        <p>Job listings, career resources, job alerts</p>
                    </div>
                </li>
                <li>
                    <span class="benefit-icon">🎓</span>
                    <div>
                        <strong>Education</strong>
                        <p>Scholarships, courses, institution directory</p>
                    </div>
                </li>
                <li>
                    <span class="benefit-icon">🏥</span>
                    <div>
                        <strong>Healthcare</strong>
                        <p>Facility finder, appointments, health records</p>
                    </div>
                </li>
            </ul>
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
    
    // Form validation on submit
    const form = document.querySelector('.auth-form');
    form?.addEventListener('submit', (e) => {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if (password !== confirm) {
            e.preventDefault();
            const confirmInput = document.getElementById('confirm_password');
            confirmInput.classList.add('error');
            let errorEl = confirmInput.parentElement.querySelector('.error-message');
            if (!errorEl) {
                errorEl = document.createElement('span');
                errorEl.className = 'error-message';
                confirmInput.parentElement.appendChild(errorEl);
            }
            errorEl.textContent = 'Passwords do not match';
        }
    });
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    phoneInput?.addEventListener('input', (e) => {
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
});
</script>