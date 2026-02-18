<?php
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        // Success: Redirect to Dashboard
        header('Location: ' . BASE_URL . '/public/dashboard/');
        exit;
    } else {
        // Failure: Set error and go back to login
        $_SESSION['error'] = "Invalid Username or Password.";
        header('Location: ' . BASE_URL . '/public/login/');
        exit;
    }
}

// If accessed directly without POST, redirect to login
header('Location: ' . BASE_URL . '/public/login/');
exit;