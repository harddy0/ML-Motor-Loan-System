<?php
require_once __DIR__ . '/../../src/includes/init.php';

// HARD SECURITY CHECK
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    die("Unauthorized Access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $auth->registerUser(
            trim($_POST['employe_id']),
            strtoupper(trim($_POST['first_name'])),
            strtoupper(trim($_POST['middle_name'] ?? '')),
            strtoupper(trim($_POST['last_name'])),
            strtoupper(trim($_POST['username'])),   // force uppercase — backend guarantee
            $_POST['user_type'],
            $_POST['status']
        );

        if ($result['success']) {
            $_SESSION['success_msg'] = "Account for {$_POST['first_name']} {$_POST['last_name']} created with default password (Mlinc1234@). User will be required to change it on first login.";
        } else {
            $_SESSION['error_msg'] = $result['error'];
        }

    } elseif ($action === 'update') {
        $employeId = $_POST['employe_id'];

        // Prevent Admin from restricting themselves by accident
        if ($employeId == $_SESSION['employe_id'] && $_POST['status'] === 'RESTRICTED') {
            $_SESSION['error_msg'] = "You cannot restrict your own account.";
        } else {
            $auth->updateUserStatusAndRole($employeId, $_POST['user_type'], $_POST['status']);
            $_SESSION['success_msg'] = "User settings updated.";
        }

    } elseif ($action === 'reset_password') {
        $employeId = $_POST['employe_id'];
        $result    = $auth->resetPassword($employeId);

        if ($result['success']) {
            $_SESSION['success_msg'] = "Password reset to default (Mlinc1234@). User will be required to change it on next login.";
        } else {
            $_SESSION['error_msg'] = $result['error'];
        }
    }

    header('Location: ' . BASE_URL . '/public/user-mgt/');
    exit;
}