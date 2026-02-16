<?php
session_start();

// Using relative paths from the current directory
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $pdo = new \PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$auth = new \App\AuthService($pdo);
$taskService = new \App\TaskService($pdo);