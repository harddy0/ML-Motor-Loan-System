<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

header('Content-Type: application/json');

if (!isset($_GET['loan_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing loan_id parameter']);
    exit;
}

try {
    // Instantiate the service using the $pdo from init.php
    $loanService = new \App\LoanService($pdo);
    
    // Fetch the ledger details
    $transactions = $loanService->getLedgerTransactions($_GET['loan_id']);
    
    echo json_encode([
        'success' => true, 
        'data' => $transactions
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}