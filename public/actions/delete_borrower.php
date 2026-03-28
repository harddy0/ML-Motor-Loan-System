<?php
require_once __DIR__ . '/../../src/includes/init.php';

// 1. SECURITY CHECK: ALLOW ADMIN AND REVIEWER TO VOID
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])) {
    $_SESSION['error_msg'] = "UNAUTHORIZED: You do not have permission to modify records.";
    header('Location: ' . BASE_URL . '/public/borrowers/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'void') {
    $employeId    = $_POST['employe_id'] ?? null;
    $borrowerName = $_POST['borrower_name'] ?? 'Unknown Borrower';
    $voidReason   = $_POST['void_reason'] ?? '';
    
    $userId = $_SESSION['employe_id'] ?? ($_SESSION['user_id'] ?? 1); 

    if (!$employeId) {
        $_SESSION['error_msg'] = "Invalid Request. No Employee ID provided.";
    } elseif (empty(trim($voidReason))) {
        $_SESSION['error_msg'] = "Action Failed: A void reason is strictly required for auditing.";
    } else {
        // Initialize LoanService to handle the voiding
        $loanService = new \App\LoanService($pdo);
        $result = $loanService->voidBorrowerLoans($employeId, $userId, $voidReason);

        if ($result['success']) {
            $_SESSION['success_msg'] = "SUCCESS: All records for {$borrowerName} have been VOIDED.";
        } else {
            $_SESSION['error_msg'] = "FAILED: " . $result['error'];
        }
    }

    // Redirect back to the Borrowers table
    header('Location: ' . BASE_URL . '/public/borrowers/');
    exit;
}

// Direct access prevention
header('Location: ' . BASE_URL . '/public/borrowers/');
exit;