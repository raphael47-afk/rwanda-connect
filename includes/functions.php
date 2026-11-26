<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Core Functions
 * Reusable utility functions for the application
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 */
function getDb(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = '127.0.0.1';
        $dbname = 'rwanda_connect_db';
        $username = 'root';
        $password = '';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $exception) {
            error_log('Rwanda Connect database connection failed: ' . $exception->getMessage());
            http_response_code(500);
            exit('Database connection failed. Check that MySQL is running and follow README.txt to create the database.');
        }
    }
    return $pdo;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user === null) {
        $db = getDb();
        $stmt = $db->prepare('SELECT u.*, up.* FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole(string ...$roles): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied. Insufficient permissions.');
    }
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Get flash messages
 */
function getFlash(string $type): array {
    $messages = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $messages;
}

/**
 * Check if has flash messages
 */
function hasFlash(string $type): bool {
    return !empty($_SESSION['flash'][$type]);
}

/**
 * Sanitize output for HTML
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output for HTML attributes
 */
function ea(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate(?string $date, string $format = 'M j, Y'): string {
    if (!$date) return '';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return $date;
    }
}

/**
 * Format datetime
 */
function formatDateTime(?string $datetime, string $format = 'M j, Y H:i'): string {
    if (!$datetime) return '';
    try {
        return (new DateTime($datetime))->format($format);
    } catch (Exception) {
        return $datetime;
    }
}

/**
 * Time ago format
 */
function timeAgo(string $datetime): string {
    try {
        $time = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->diff($time);
        
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    } catch (Exception) {
        return $datetime;
    }
}

/**
 * Format currency (RWF)
 */
function formatCurrency(float|int|null $amount, string $currency = 'RWF'): string {
    if ($amount === null) return 'N/A';
    return number_format($amount, 0, '.', ',') . ' ' . $currency;
}

/**
 * Generate slug from string
 */
function slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Redirect with flash message
 */
function redirectWith(string $url, string $type, string $message): void {
    setFlash($type, $message);
    header('Location: ' . $url);
    exit;
}

/**
 * Get setting value
 */
function getSetting(string $key, $default = null) {
    static $settings = [];
    if (empty($settings)) {
        $db = getDb();
        $stmt = $db->query('SELECT `key`, `value` FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['key']] = $row['value'];
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Log activity
 */
function logActivity(string $action, ?string $description = null, ?int $userId = null): void {
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId ?? getCurrentUserId(),
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Create notification
 */
function createNotification(int $userId, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null, ?string $actionUrl = null): void {
    $db = getDb();
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, message, related_type, related_id, action_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $title, $message, $relatedType, $relatedId, $actionUrl]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount(): int {
    $userId = getCurrentUserId();
    if (!$userId) return 0;
    
    $db = getDb();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Pagination helper
 */
function paginate(int $totalItems, int $perPage = 10, int $currentPage = 1): array {
    $totalPages = (int)ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
    ];
}

/**
 * Build pagination URL
 */
function buildPageUrl(int $page, array $params = []): string {
    $query = array_merge($_GET, $params, ['page' => $page]);
    return '?' . http_build_query($query);
}

/**
 * Validate email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (Rwanda format)
 */
function validatePhone(string $phone): bool {
    // Rwanda: +250XXXXXXXXX or 0XXXXXXXXX
    return preg_match('/^(\+250|0)[78]\d{8}$/', $phone) === 1;
}

/**
 * Validate national ID (Rwanda - 16 digits)
 */
function validateNationalId(string $id): bool {
    return preg_match('/^\d{16}$/', $id) === 1;
}

/**
 * Strong password validation
 */
function validatePassword(string $password): array {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    return $errors;
}

/**
 * Hash password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Handle file upload
 */
function handleFileUpload(array $file, string $destinationDir, array $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], int $maxSize = 10485760): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed (' . ($maxSize / 1048576) . 'MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    $extension = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        default => 'bin'
    };
    
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $destinationDir . '/' . $filename;
    
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return ['success' => true, 'filename' => $filename, 'path' => $destination, 'mime' => $mimeType];
}

/**
 * Get provinces of Rwanda
 */
function getProvinces(): array {
    return [
        'Kigali City' => 'Kigali City',
        'Northern Province' => 'Northern Province',
        'Southern Province' => 'Southern Province',
        'Eastern Province' => 'Eastern Province',
        'Western Province' => 'Western Province',
    ];
}

/**
 * Get districts by province
 */
function getDistricts(string $province): array {
    $districts = [
        'Kigali City' => ['Gasabo', 'Kicukiro', 'Nyarugenge'],
        'Northern Province' => ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
        'Southern Province' => ['Gisagara', 'Huye', 'Kamonyi', 'Muhanga', 'Nyamagabe', 'Nyanza', 'Nyaruguru', 'Ruhango'],
        'Eastern Province' => ['Bugesera', 'Gatsibo', 'Kayonza', 'Kirehe', 'Ngoma', 'Nyagatare', 'Rwamagana'],
        'Western Province' => ['Karongi', 'Ngororero', 'Nyabihu', 'Nyamasheke', 'Rubavu', 'Rusizi', 'Rutsiro'],
    ];
    return $districts[$province] ?? [];
}

/**
 * Get asset URL with version
 */
function asset(string $path): string {
    $version = file_exists(__DIR__ . '/../' . $path) ? filemtime(__DIR__ . '/../' . $path) : time();
    return $path . '?v=' . $version;
}

/**
 * Check if current page matches
 */
function isActive(string $path): string {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return str_ends_with($current, $path) ? 'active' : '';
}

/**
 * JSON response
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Error response
 */
function errorResponse(string $message, int $status = 400, array $errors = []): void {
    jsonResponse(['success' => false, 'message' => $message, 'errors' => $errors], $status);
}

/**
 * Success response
 */
function successResponse(string $message, array $data = []): void {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}