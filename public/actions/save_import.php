<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

// Get the logged-in user's Employee ID from the session
$uploaderId = $_SESSION['user_id'] ?? null; 

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['borrowers']) || !is_array($input['borrowers'])) {
        throw new Exception("No borrower data received");
    }

    $loanService = new \App\LoanService($pdo);
    $successCount = 0;
    $errors = [];

    foreach ($input['borrowers'] as $borrower) {
        
       // Reconstruct the payload expected by saveLoanApplication
        $loanData = [
            'employe_id' => $borrower['id'], 
            'first_name' => $borrower['first_name'],
            'last_name' => $borrower['last_name'],
            'contact_number' => $borrower['contact_number'] ?? '000-000-0000',
            'region' => $borrower['region'] ?? 'N/A',
            'division' => $borrower['division'] ?? 'N/A',
            'reference_number' => $borrower['reference_number'] ?? null, 
            'pn_number' => $borrower['pn_number'],
            'loan_amount' => $borrower['loan_amount'],
            'terms' => $borrower['terms'],
            'deduction' => $borrower['deduction'],
            'loan_granted' => $borrower['loan_granted'],
            'pn_maturity' => $borrower['pn_maturity'],
            'add_on_rate_decimal' => $borrower['add_on_rate_decimal'] ?? 0.015,
            'uploaded_by_employe_id' => $uploaderId,
            
            // NEW FIELDS FOR BATCH LOGIC
            'entry_type' => 'BATCH',
            'kptn' => null
        ];

        // Ensure schedule structure is correct (passing empty rows for batch)
        $scheduleData = [
            'rows' => [], 
            'periodic_rate' => $borrower['periodic_rate']
        ];

        $result = $loanService->saveLoanApplication($loanData, $scheduleData);

        if ($result['success']) {
            $successCount++;
        } else {
            $errors[] = "Failed ID {$borrower['id']}: " . ($result['error'] ?? 'Unknown');
        }
    }

    echo json_encode([
        'success' => true, 
        'imported_count' => $successCount, 
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>