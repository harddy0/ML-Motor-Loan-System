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

ob_start();

// Register a shutdown function to render the layout automatically
register_shutdown_function(function() {
    $content = ob_get_clean(); // Capture everything echoed so far
    
    // Logic to skip layout for API or AJAX calls
    if (str_contains($_SERVER['REQUEST_URI'], '/api/')) {
        echo $content;
        return;
    }

    // Use dirname(__DIR__) to go UP one level from 'includes' to 'src'
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
});