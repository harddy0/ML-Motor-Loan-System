<?php
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }

    // Process Update
    $success = $auth->changeUserPassword($_SESSION['user_id'], $newPassword);
    
    if ($success) {
        $_SESSION['must_change_password'] = false; // Lift the restriction locally
        $_SESSION['success'] = "Password updated successfully."; // Optional: Catch this on dashboard UI
        header('Location: ' . BASE_URL . '/public/dashboard/');
        exit;
    } else {
        $_SESSION['error'] = "Failed to update password. Please try again.";
        header('Location: ' . BASE_URL . '/public/change_password/');
        exit;
    }
}

// Block direct GET access
header('Location: ' . BASE_URL . '/public/change_password/');
exit;