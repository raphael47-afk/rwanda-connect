<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Logout
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();

redirectWith('/rwanda_connect/auth/login.php', 'success', 'You have been logged out successfully.');