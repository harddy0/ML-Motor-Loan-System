<?php
// 1. DISABLE LAYOUT (Prevent HTML wrappers)
$noLayout = true;

// 2. SET HEADER TO JSON
header('Content-Type: application/json');

// 3. INITIALIZE APP
require_once __DIR__ . '/../../src/includes/init.php';

// 4. CHECK REQUEST METHOD
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

try {
    // 5. VALIDATE REQUIRED FIELDS
    // We check for a few key fields to ensure the form isn't empty
    if (empty($_POST['employe_id']) || empty($_POST['loan_amount']) || empty($_POST['schedule'])) {
        throw new Exception("Missing required loan data.");
    }

    // 6. PREPARE DATA FOR SERVICE
    // The JS sends the schedule array as a JSON string, so we must decode it.
    $scheduleRows = json_decode($_POST['schedule'], true);
    
    if (!is_array($scheduleRows)) {
        throw new Exception("Invalid schedule data format.");
    }

    // Reconstruct the 'schedule' array structure expected by LoanService::saveLoanApplication
    $scheduleData = [
        'rows' => $scheduleRows,
        'periodic_rate' => $_POST['periodic_rate'] ?? 0 // Ensure rate is passed
    ];

    // The rest of the form data is flat, so $_POST works fine.
    // We pass $_POST directly as the $data array.
    $loanData = $_POST;

    // 7. CALL THE SERVICE
    $loanService = new \App\LoanService($pdo);
    $result = $loanService->saveLoanApplication($loanData, $scheduleData);

    // 8. OUTPUT RESULT
    echo json_encode($result);

} catch (Exception $e) {
    // Return error as JSON
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>