<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

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
            'pn_number' => $borrower['pn_number'],
            'loan_amount' => $borrower['loan_amount'],
            'terms' => $borrower['terms'],
            'deduction' => $borrower['deduction'],
            'loan_granted' => $borrower['loan_granted'],
            'pn_maturity' => $borrower['pn_maturity']
        ];

        // Ensure schedule structure is correct
        $scheduleData = [
            'rows' => $borrower['schedule'],
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