<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Authentication Functions
 * Handles user registration, login, logout, password reset
 */

require_once __DIR__ . '/functions.php';

/**
 * Register a new user
 */
function registerUser(array $data): array {
    $db = getDb();
    
    // Validate required fields
    $required = ['full_name', 'email', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'errors' => [$field => ucfirst(str_replace('_', ' ', $field)) . ' is required']];
        }
    }
    
    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'errors' => ['email' => 'Invalid email format']];
    }
    
    // Check if email exists
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['email' => 'Email already registered']];
    }
    
    // Validate password
    $passwordErrors = validatePassword($data['password']);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'errors' => ['password' => implode(', ', $passwordErrors)]];
    }
    
    // Check password match
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'errors' => ['confirm_password' => 'Passwords do not match']];
    }
    
    // Validate phone if provided
    if (!empty($data['phone']) && !validatePhone($data['phone'])) {
        return ['success' => false, 'errors' => ['phone' => 'Invalid Rwanda phone number format']];
    }
    
    // Validate national ID if provided
    if (!empty($data['national_id']) && !validateNationalId($data['national_id'])) {
        return ['success' => false, 'errors' => ['national_id' => 'National ID must be 16 digits']];
    }
    
    // Check national ID uniqueness
    if (!empty($data['national_id'])) {
        $stmt = $db->prepare('SELECT id FROM users WHERE national_id = ?');
        $stmt->execute([$data['national_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['national_id' => 'National ID already registered']];
        }
    }
    
    // Check if registration is enabled
    if (!getSetting('registration_enabled', '1')) {
        return ['success' => false, 'errors' => ['general' => 'Registration is currently disabled']];
    }
    
    try {
        $db->beginTransaction();
        
        // Insert user
        $passwordHash = hashPassword($data['password']);
        $stmt = $db->prepare('INSERT INTO users (full_name, email, phone, national_id, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['national_id'] ?? null,
            $passwordHash,
            'citizen'
        ]);
        
        $userId = (int)$db->lastInsertId();
        
        // Create empty profile
        $stmt = $db->prepare('INSERT INTO user_profiles (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        
        $db->commit();
        
        logActivity('user_registered', "New user registered: {$data['email']}", $userId);
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Registration error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'Registration failed. Please try again.']];
    }
}

/**
 * Login user
 */
function loginUser(string $email, string $password, bool $remember = false): array {
    $db = getDb();
    
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'errors' => ['general' => 'Invalid email or password']];
    }
    
    if (!verifyPassword($password, $user['password_hash'])) {
        return ['success' => false, 'errors' => ['general' => 'Invalid email or password']];
    }
    
    if ($user['status'] !== 'active') {
        return ['success' => false, 'errors' => ['general' => 'Account is ' . $user['status'] . '. Please contact support.']];
    }
    
    // Update last login
    $stmt = $db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$user['id']]);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_verified'] = (bool)$user['email_verified_at'];
    
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = new DateTime('+30 days');
        $stmt = $db->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], hash('sha256', $token), $expires->format('Y-m-d H:i:s')]);
        setcookie('remember_token', $token, $expires->getTimestamp(), '/', '', true, true);
    }
    
    logActivity('user_login', "User logged in: {$user['email']}", $user['id']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser(): void {
    $userId = getCurrentUserId();
    
    // Clear remember token cookie
    if (isset($_COOKIE['remember_token'])) {
        $db = getDb();
        $tokenHash = hash('sha256', $_COOKIE['remember_token']);
        $stmt = $db->prepare('DELETE FROM remember_tokens WHERE token = ?');
        $stmt->execute([$tokenHash]);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    if ($userId) {
        logActivity('user_logout', 'User logged out', $userId);
    }
    
    session_destroy();
    
    // Start new session for flash messages
    session_start();
}

/**
 * Check remember token
 */
function checkRememberToken(): bool {
    if (isLoggedIn() || !isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $db = getDb();
    $tokenHash = hash('sha256', $_COOKIE['remember_token']);
    $stmt = $db->prepare('SELECT rt.*, u.* FROM remember_tokens rt JOIN users u ON rt.user_id = u.id WHERE rt.token = ? AND rt.expires_at > NOW()');
    $stmt->execute([$tokenHash]);
    $result = $stmt->fetch();
    
    if ($result && $result['status'] === 'active') {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['user_name'] = $result['full_name'];
        $_SESSION['user_email'] = $result['email'];
        $_SESSION['user_role'] = $result['role'];
        $_SESSION['user_verified'] = (bool)$result['email_verified_at'];
        
        // Update last login
        $stmt = $db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$result['id']]);
        
        logActivity('user_login', "User logged in via remember token: {$result['email']}", $result['id']);
        
        return true;
    }
    
    // Invalid token, delete cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    return false;
}

/**
 * Update user profile
 */
function updateProfile(int $userId, array $data): array {
    $db = getDb();
    
    $allowedFields = ['full_name', 'phone', 'date_of_birth', 'gender', 'province', 'district', 'sector', 'cell', 'village', 'education_level', 'occupation', 'skills', 'bio'];
    $userFields = ['full_name', 'phone'];
    $profileFields = array_diff($allowedFields, $userFields);
    
    $userUpdates = [];
    $profileUpdates = [];
    $userParams = [];
    $profileParams = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $userFields, true)) {
            if ($key === 'phone' && !empty($value) && !validatePhone($value)) {
                return ['success' => false, 'errors' => ['phone' => 'Invalid phone number']];
            }
            $userUpdates[] = "$key = ?";
            $userParams[] = $value ?: null;
        } elseif (in_array($key, $profileFields, true)) {
            $profileUpdates[] = "$key = ?";
            $profileParams[] = $value ?: null;
        }
    }
    
    try {
        $db->beginTransaction();
        
        if (!empty($userUpdates)) {
            $userParams[] = $userId;
            $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $userUpdates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute($userParams);
        }
        
        if (!empty($profileUpdates)) {
            $profileParams[] = $userId;
            $stmt = $db->prepare('UPDATE user_profiles SET ' . implode(', ', $profileUpdates) . ', updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
            $stmt->execute($profileParams);
        }
        
        $db->commit();
        
        logActivity('profile_updated', 'User profile updated', $userId);
        
        // Refresh session data if name changed
        if (isset($data['full_name'])) {
            $_SESSION['user_name'] = $data['full_name'];
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Profile update error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'Failed to update profile']];
    }
}

/**
 * Change password
 */
function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array {
    $db = getDb();
    
    // Get current hash
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'errors' => ['current_password' => 'Current password is incorrect']];
    }
    
    $passwordErrors = validatePassword($newPassword);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'errors' => ['new_password' => implode(', ', $passwordErrors)]];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'errors' => ['confirm_password' => 'Passwords do not match']];
    }
    
    $newHash = hashPassword($newPassword);
    $stmt = $db->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$newHash, $userId]);
    
    // Invalidate all remember tokens
    $stmt = $db->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);
    
    logActivity('password_changed', 'User changed password', $userId);
    
    return ['success' => true];
}

/**
 * Request password reset
 */
function requestPasswordReset(string $email): array {
    $db = getDb();
    
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Always return success to prevent email enumeration
    if (!$user) {
        return ['success' => true];
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = new DateTime('+1 hour');
    
    $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], hash('sha256', $token), $expires->format('Y-m-d H:i:s')]);
    
    // TODO: Send email with reset link
    // For now, log the token (in production, send via email)
    error_log("Password reset token for {$email}: {$token}");
    
    logActivity('password_reset_requested', "Password reset requested for: {$email}", $user['id']);
    
    return ['success' => true];
}

/**
 * Reset password with token
 */
function resetPassword(string $token, string $password, string $confirmPassword): array {
    $db = getDb();
    
    $tokenHash = hash('sha256', $token);
    $stmt = $db->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW()');
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        return ['success' => false, 'errors' => ['general' => 'Invalid or expired reset token']];
    }
    
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'errors' => ['password' => implode(', ', $passwordErrors)]];
    }
    
    if ($password !== $confirmPassword) {
        return ['success' => false, 'errors' => ['confirm_password' => 'Passwords do not match']];
    }
    
    $newHash = hashPassword($password);
    $stmt = $db->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$newHash, $reset['user_id']]);
    
    // Delete used token
    $stmt = $db->prepare('DELETE FROM password_resets WHERE id = ?');
    $stmt->execute([$reset['id']]);
    
    // Invalidate all remember tokens
    $stmt = $db->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
    $stmt->execute([$reset['user_id']]);
    
    logActivity('password_reset', "Password reset completed for: {$reset['email']}", $reset['user_id']);
    
    return ['success' => true];
}

/**
 * Verify email (placeholder for future implementation)
 */
function verifyEmail(int $userId, string $token): array {
    // Implementation for email verification
    return ['success' => false, 'errors' => ['general' => 'Not implemented yet']];
}

/**
 * Get user by ID
 */
function getUserById(int $userId): ?array {
    $db = getDb();
    $stmt = $db->prepare('SELECT u.*, up.* FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Check if user owns resource
 */
function userOwnsResource(int $userId, string $table, int $resourceId, string $userColumn = 'user_id'): bool {
    $db = getDb();
    $stmt = $db->prepare("SELECT 1 FROM {$table} WHERE id = ? AND {$userColumn} = ?");
    $stmt->execute([$resourceId, $userId]);
    return (bool)$stmt->fetch();
}