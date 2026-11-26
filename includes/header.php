<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Header Component
 * Reusable header with navigation
 */

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$unreadNotifications = isLoggedIn() ? getUnreadNotificationCount() : 0;
$csrfToken = generateCsrfToken();
$siteName = getSetting('site_name', 'Rwanda Connect');
$pageTitle = $pageTitle ?? $siteName;
$pageDescription = $pageDescription ?? 'Connecting citizens to opportunities and services across Rwanda.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/rwanda_connect/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-container">
        <a class="brand" href="/rwanda_connect/index.php" aria-label="Rwanda Connect Home">
            <span class="brand-mark">RC</span>
            <span class="brand-text">Rwanda Connect</span>
        </a>
        
        <nav class="main-nav" aria-label="Main navigation">
            <ul class="nav-list">
                <li><a href="/rwanda_connect/index.php" class="<?= isActive('index.php') ?>">Home</a></li>
                <li><a href="/rwanda_connect/services.php" class="<?= isActive('services.php') ?>">Services</a></li>
                
                <?php if ($currentUser): ?>
                    <li class="nav-dropdown">
                        <button class="nav-dropdown-toggle" aria-expanded="false" aria-haspopup="true">
                            <span class="nav-icon">📋</span>
                            Modules
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" role="menu">
                            <a href="/rwanda_connect/modules/agriculture/" role="menuitem" class="<?= isActive('modules/agriculture/') ?>">
                                <span>🌱</span> Agriculture
                            </a>
                            <a href="/rwanda_connect/modules/employment/" role="menuitem" class="<?= isActive('modules/employment/') ?>">
                                <span>💼</span> Employment
                            </a>
                            <a href="/rwanda_connect/modules/education/" role="menuitem" class="<?= isActive('modules/education/') ?>">
                                <span>🎓</span> Education
                            </a>
                            <a href="/rwanda_connect/modules/healthcare/" role="menuitem" class="<?= isActive('modules/healthcare/') ?>">
                                <span>🏥</span> Healthcare
                            </a>
                        </div>
                    </li>
                    
                    <li class="nav-dropdown">
                        <button class="nav-dropdown-toggle" aria-expanded="false" aria-haspopup="true">
                            <span class="nav-icon">👤</span>
                            <?= e($currentUser['full_name'] ?? 'Account') ?>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="nav-dropdown-menu" role="menu">
                            <a href="/rwanda_connect/dashboard/" role="menuitem" class="<?= isActive('dashboard/') ?>">
                                <span>📊</span> Dashboard
                            </a>
                            <a href="/rwanda_connect/dashboard/profile.php" role="menuitem">
                                <span>👤</span> Profile
                            </a>
                            <a href="/rwanda_connect/dashboard/settings.php" role="menuitem">
                                <span>⚙️</span> Settings
                            </a>
                            <hr class="dropdown-divider">
                            <form method="POST" action="/rwanda_connect/auth/logout.php" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="dropdown-item logout-btn" role="menuitem">
                                    <span>🚪</span> Logout
                                </button>
                            </form>
                        </div>
                    </li>
                    
                    <?php if ($unreadNotifications > 0): ?>
                        <li class="nav-notifications">
                            <a href="/rwanda_connect/dashboard/notifications.php" class="notification-bell" aria-label="Notifications">
                                <span class="bell-icon">🔔</span>
                                <span class="notification-badge"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="/rwanda_connect/auth/login.php" class="nav-button secondary">Login</a></li>
                    <li><a href="/rwanda_connect/auth/register.php" class="nav-button primary">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span class="hamburger"></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" role="dialog" aria-modal="true" aria-label="Mobile navigation">
    <nav class="mobile-nav">
        <div class="mobile-nav-header">
            <a class="brand" href="/rwanda_connect/index.php">
                <span class="brand-mark">RC</span>
                <span class="brand-text">Rwanda Connect</span>
            </a>
            <button class="mobile-nav-close" aria-label="Close menu">✕</button>
        </div>
        <ul class="mobile-nav-list">
            <li><a href="/rwanda_connect/index.php">Home</a></li>
            <li><a href="/rwanda_connect/services.php">Services</a></li>
            
            <?php if ($currentUser): ?>
                <li class="mobile-nav-section">
                    <span class="mobile-nav-section-title">Modules</span>
                    <li><a href="/rwanda_connect/modules/agriculture/">🌱 Agriculture</a></li>
                    <li><a href="/rwanda_connect/modules/employment/">💼 Employment</a></li>
                    <li><a href="/rwanda_connect/modules/education/">🎓 Education</a></li>
                    <li><a href="/rwanda_connect/modules/healthcare/">🏥 Healthcare</a></li>
                </li>
                
                <li class="mobile-nav-section">
                    <span class="mobile-nav-section-title">Account</span>
                    <li><a href="/rwanda_connect/dashboard/">📊 Dashboard</a></li>
                    <li><a href="/rwanda_connect/dashboard/profile.php">👤 Profile</a></li>
                    <li><a href="/rwanda_connect/dashboard/settings.php">⚙️ Settings</a></li>
                    <li>
                        <form method="POST" action="/rwanda_connect/auth/logout.php" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="mobile-nav-btn">🚪 Logout</button>
                        </form>
                    </li>
                </li>
            <?php else: ?>
                <li class="mobile-nav-section">
                    <a href="/rwanda_connect/auth/login.php" class="mobile-nav-btn primary">Login</a>
                    <a href="/rwanda_connect/auth/register.php" class="mobile-nav-btn secondary">Register</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>