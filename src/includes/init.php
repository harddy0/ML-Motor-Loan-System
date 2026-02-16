<?php
session_start();

// Using relative paths from the current directory
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
define('ASSET_URL', '/ML-Motor-Loan-System/assets/');

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
    // 1. Grab the content from the bucket
    $content = ob_get_clean();
    
    // 2. Access the global $noLayout variable
    global $noLayout;

    // 3. IF the flag is set, just echo the content and stop
    if (isset($noLayout) && $noLayout === true) {
        echo $content;
        return;
    }

    // 4. OTHERWISE, wrap it in the layout
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
});