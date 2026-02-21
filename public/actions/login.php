<?php
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $loginResult = $auth->login($username, $password);
    
    if (is_array($loginResult) && $loginResult['success'] === true) {
        // Success: Redirect to Dashboard
        header('Location: /ML-MOTOR-LOAN-SYSTEM/public/dashboard/');
        exit;
    } else {
        // Failure: Set the specific error (Invalid / Restricted)
        $_SESSION['error'] = is_array($loginResult) ? $loginResult['error'] : "System Error. Try again.";
        header('Location: /ML-MOTOR-LOAN-SYSTEM/public/login/');
        exit;
    }
}

// Direct access prevention
header('Location: /ML-MOTOR-LOAN-SYSTEM/public/login/');
exit;