<?php
session_start();

// Using relative paths from the current directory
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// DEFENSIVE DEFINITIONS: Only define if not already set in config.php
if (!defined('ASSET_URL')) {
    define('ASSET_URL', '/ML-MOTOR-LOAN-SYSTEM/public/assets/');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ML-MOTOR-LOAN-SYSTEM'); 
}

// ==========================================================
// 1. PRIMARY DATABASE CONNECTION (Strict / Fatal if fails)
// ==========================================================
try {
    $pdo = new \PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("Primary DB Connection failed: " . $e->getMessage());
}

// ==========================================================
// 2. SECONDARY DATABASE CONNECTION (Optional / Non-Fatal)
// ==========================================================
$pdo2 = null; // Defaults to null. Other files can check: if ($pdo2) { ... }

try {
    // Only attempt connection if you actually defined the constants in config.php
    if (defined('DB2_HOST') && defined('DB2_NAME') && defined('DB2_USER')) {
        $pdo2 = new \PDO(
            "mysql:host=" . DB2_HOST . ";dbname=" . DB2_NAME . ";charset=utf8mb4", 
            DB2_USER, 
            DB2_PASS
        );
        $pdo2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
} catch (\PDOException $e) {
    // We intentionally DO NOT use die() here.
    // If it fails, $pdo2 just stays null and the app keeps running!
    
    // Optional: Log the error in the background so you know it's down
    // error_log("Secondary DB Connection failed: " . $e->getMessage());
}

// Initialize Services (Using Primary DB)
$auth = new \App\AuthService($pdo);
$taskService = new \App\TaskService($pdo);

// ==========================================================
// GLOBAL AUTHENTICATION MIDDLEWARE
// ==========================================================
// Identify the current page the user is trying to access
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Define paths that DO NOT require a login (Whitelist)
$isLoginPage = strpos($currentPath, '/login/') !== false;
$isLoginAction = strpos($currentPath, '/actions/login.php') !== false;

// If the user is NOT logged in AND they are NOT on a whitelisted page
if (!$auth->isLoggedIn() && !$isLoginPage && !$isLoginAction) {
    // Save an error message to show on the login screen
    $_SESSION['error'] = "You must be logged in to access this system.";
    
    // Redirect them instantly to the login page and kill the script
    header('Location: ' . BASE_URL . '/public/login/');
    exit;
}
// ==========================================================

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
    $layoutPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
    if (file_exists($layoutPath)) {
        require $layoutPath;
    } else {
        echo $content; // Fallback if layout is missing
    }
});