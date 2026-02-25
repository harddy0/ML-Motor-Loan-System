<?php
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        $_SESSION['error'] = "Please enter your username.";
        header('Location: ' . BASE_URL . '/public/forgot_password/');
        exit;
    }

    $result = $auth->resetPassword($username);

    if ($result['success']) {
        // Redirect to login page with a success message
        $_SESSION['flash_success'] = "Password reset successful! You may now log in using your default password (first 4 letters of your last name + current year). If your last name has fewer than 4 letters, zeros are added to complete it (e.g., sy002026).";
        header('Location: ' . BASE_URL . '/public/login/');
        exit;
    } else {
        // Redirect back to forgot password with an error
        $_SESSION['error'] = $result['error'];
        header('Location: ' . BASE_URL . '/public/forgot_password/');
        exit;
    }
}

// Block direct GET access
header('Location: ' . BASE_URL . '/public/forgot_password/');
exit;