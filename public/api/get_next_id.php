<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

try {
    $loanService = new \App\LoanService($pdo);
    $nextId = $loanService->getNextBorrowerId();
    echo json_encode(['success' => true, 'next_id' => $nextId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>