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
$pdo2 = null; 

try {
    if (defined('DB2_HOST') && defined('DB2_NAME') && defined('DB2_USER')) {
        $pdo2 = new \PDO(
            "mysql:host=" . DB2_HOST . ";dbname=" . DB2_NAME . ";charset=utf8mb4", 
            DB2_USER, 
            DB2_PASS
        );
        $pdo2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
} catch (\PDOException $e) {
    // Fails silently
}

// Initialize Services
$auth = new \App\AuthService($pdo);
$taskService = new \App\TaskService($pdo);

// ==========================================================
// GLOBAL AUTHENTICATION MIDDLEWARE
// ==========================================================
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Define paths that DO NOT require a login (Whitelist)
$isLandingPage = $currentPath === '/' || $currentPath === '/ML-MOTOR-LOAN-SYSTEM/' || $currentPath === '/ML-MOTOR-LOAN-SYSTEM';
$isLoginPage = strpos($currentPath, '/login/') !== false;
$isLoginAction = strpos($currentPath, '/actions/login.php') !== false;

// ADDED: Whitelist the forgot password routes
$isForgotPasswordPage = strpos($currentPath, '/forgot_password/') !== false;
$isResetAction = strpos($currentPath, '/actions/reset_password.php') !== false;

// If the user is NOT logged in AND they are NOT on a whitelisted page
if (!$auth->isLoggedIn() && !$isLandingPage && !$isLoginPage && !$isLoginAction && !$isForgotPasswordPage && !$isResetAction) {
    $_SESSION['error'] = "You must be logged in to access this system.";
    header('Location: ' . BASE_URL . '/public/login/');
    exit;
}

// ==========================================================
// FORCE PASSWORD CHANGE MIDDLEWARE
// ==========================================================
if ($auth->isLoggedIn() && !empty($_SESSION['must_change_password'])) {
    
    // Use strpos for flexible matching (ignores trailing slash or BASE_URL differences)
    $isChangePasswordRoute = strpos($currentPath, '/change_password') !== false;
    $isUpdateActionRoute   = strpos($currentPath, '/actions/update_password.php') !== false;
    $isLogoutRoute         = strpos($currentPath, '/actions/logout.php') !== false;

    // If trying to access a restricted path, force redirect to change password
    if (!$isChangePasswordRoute && !$isUpdateActionRoute && !$isLogoutRoute) {
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }
}
// ==========================================================

ob_start();

register_shutdown_function(function() {
    $content = ob_get_clean();
    global $noLayout;

    if (isset($noLayout) && $noLayout === true) {
        echo $content;
        return;
    }

    $layoutPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
    if (file_exists($layoutPath)) {
        require $layoutPath;
    } else {
        echo $content;
    }
});