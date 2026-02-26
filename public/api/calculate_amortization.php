<?php
// 1. DISABLE THE HTML LAYOUT WRAPPER
$noLayout = true; 

// 2. SET HEADER TO JSON
header('Content-Type: application/json');

// 3. INITIALIZE APP
require_once __DIR__ . '/../../src/includes/init.php'; 

// 4. HANDLE REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

// Get Input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['loan_amount']) || !isset($input['terms']) || !isset($input['date_granted'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $loanService = new \App\LoanService($pdo);
    
    // Call the updated 1.5% fixed generation logic
    $result = $loanService->generatePreview(
        floatval($input['loan_amount']), 
        intval($input['terms']), 
        $input['date_granted']
    );

    if (ob_get_length()) ob_clean();

    echo json_encode($result);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>