<?php
require_once __DIR__ . '/../../src/includes/init.php';

// 1. ABSOLUTE SECURITY CHECK: ONLY ADMINS CAN DELETE
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    $_SESSION['error_msg'] = "UNAUTHORIZED: You do not have permission to delete records.";
    header('Location: /ML-MOTOR-LOAN-SYSTEM/public/borrower-mgt/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $employeId = $_POST['employe_id'] ?? null;
    $borrowerName = $_POST['borrower_name'] ?? 'Unknown Borrower';

    if (!$employeId) {
        $_SESSION['error_msg'] = "Invalid Request. No Employee ID provided.";
    } else {
        // Initialize LoanService to handle the deletion
        $loanService = new \App\LoanService($pdo);
        $result = $loanService->deleteBorrower($employeId);

        if ($result['success']) {
            $_SESSION['success_msg'] = "SUCCESS: All records for {$borrowerName} (Loans, Ledgers, Deductions) have been permanently wiped.";
        } else {
            $_SESSION['error_msg'] = "FAILED: " . $result['error'];
        }
    }

    header('Location: /ML-MOTOR-LOAN-SYSTEM/public/borrower-mgt/');
    exit;
}

// Direct access prevention
header('Location: /ML-MOTOR-LOAN-SYSTEM/public/borrower-mgt/');
exit;