<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

try {
    if (empty($_POST['employe_id']) || empty($_POST['loan_amount']) || empty($_POST['schedule'])) {
        throw new Exception("Missing required loan data.");
    }

    $loanService = new \App\LoanService($pdo);

    // --- NEW: STRICT DUPLICATE CHECK FOR MANUAL ENTRY ---
    if ($loanService->isBorrowerExists($_POST['first_name'], $_POST['last_name'])) {
        throw new Exception("DUPLICATE ENTRY REJECTED:\nBorrower '" . strtoupper(trim($_POST['first_name']) . " " . trim($_POST['last_name'])) . "' is already registered in the database.");
    }

    $scheduleRows = json_decode($_POST['schedule'], true);
    if (!is_array($scheduleRows)) {
        throw new Exception("Invalid schedule data format.");
    }

    $scheduleData = [
        'rows' => $scheduleRows,
        'periodic_rate' => $_POST['periodic_rate'] ?? 0
    ];

    $loanData = $_POST;
    $result = $loanService->saveLoanApplication($loanData, $scheduleData);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>