<?php
declare(strict_types=1);

/*
 * Rwanda Connect database connection.
 * Default settings below are commonly used by a fresh local XAMPP installation.
 * If you have changed your MySQL username or password, update these values.
 */
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
    // Do not display database credentials or technical details to website visitors.
    error_log('Rwanda Connect database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check that MySQL is running and follow README.txt to create the database.');
}
