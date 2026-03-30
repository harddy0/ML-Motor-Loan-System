<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';

try {
    $loanService = new \App\LoanService($pdo);

    // Reuse ledger stats which already separates ongoing/paid/inactive/voided.
    $result = $loanService->getAllLedgerLoans(true, 1, 1, '', '', '', '');
    $stats = $result['stats'] ?? [];

    echo json_encode([
        'success' => true,
        'payload' => [
            'active' => (int)($stats['ongoing'] ?? 0),
            'fully_paid' => (int)($stats['paid'] ?? 0),
            'inactive' => (int)($stats['inactive'] ?? 0),
            'void' => (int)($stats['voided'] ?? 0),
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
