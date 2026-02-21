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
            trim($_POST['full_name']),
            trim($_POST['username']),
            $_POST['password'],
            $_POST['user_type'],
            $_POST['status']
        );

        if ($result['success']) {
            $_SESSION['success_msg'] = "Account for {$_POST['full_name']} successfully created.";
        } else {
            $_SESSION['error_msg'] = $result['error'];
        }
    } 
    elseif ($action === 'update') {
        $userId = $_POST['user_id'];
        
        // Prevent Admin from restricting themselves by accident
        if ($userId == $_SESSION['user_id'] && $_POST['status'] === 'RESTRICTED') {
            $_SESSION['error_msg'] = "You cannot restrict your own account.";
        } else {
            $auth->updateUserStatusAndRole($userId, $_POST['user_type'], $_POST['status']);
            $_SESSION['success_msg'] = "User settings updated.";
        }
    }

    header('Location: /ML-MOTOR-LOAN-SYSTEM/public/user-mgt/');
    exit;
}