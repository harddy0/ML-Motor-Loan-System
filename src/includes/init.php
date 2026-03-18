<?php
session_start();
 
// Using relative paths from the current directory
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
 
// DEFENSIVE DEFINITIONS: Only define if not already set in config.php
// AUTO-DETECT the subfolder name from the script path so this works on any PC
// regardless of what the project folder is named.
if (!defined('BASE_URL')) {
    // Derive the base URL from the actual folder structure at runtime.
    // e.g. if hosted at /ML-MOTOR-LOAN-SYSTEM/public/dashboard/ this returns /ML-MOTOR-LOAN-SYSTEM
    // e.g. if hosted at /MyApp/public/dashboard/ this returns /MyApp
    // e.g. if hosted at the web root it returns ''
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']); // normalize Windows slashes
    $publicPos  = strpos($scriptPath, '/public/');
    $autoBase   = ($publicPos !== false) ? substr($scriptPath, 0, $publicPos) : '';
    define('BASE_URL', $autoBase);
}
if (!defined('ASSET_URL')) {
    define('ASSET_URL', BASE_URL . '/public/assets/');
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
$normalizedPath = strtolower(rtrim($currentPath, '/'));
if ($normalizedPath === '') {
    $normalizedPath = '/';
}

// Define paths that DO NOT require a login (Whitelist)
$isLandingPage = in_array($normalizedPath, [
    '/',
    '/ml-motor-loan-system',
    '/ml-motor-loan-system/public',
    '/ml-motor-loan-system/public/index.php',
], true);
$isLoginPage = strpos($normalizedPath, '/login') !== false;
$isLoginAction = strpos($normalizedPath, '/actions/login.php') !== false;

// ADDED: Whitelist the forgot password routes
$isForgotPasswordPage = strpos($normalizedPath, '/forgot_password') !== false;
$isResetAction = strpos($normalizedPath, '/actions/reset_password.php') !== false;

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
    $isChangePasswordRoute = strpos($normalizedPath, '/change_password') !== false;
    $isUpdateActionRoute   = strpos($normalizedPath, '/actions/update_password.php') !== false;
    $isLogoutRoute         = strpos($normalizedPath, '/actions/logout.php') !== false;

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