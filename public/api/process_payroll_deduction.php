<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/PayrollDeductionService.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['deductions']) || !is_array($input['deductions'])) {
    echo json_encode(['success' => false, 'error' => 'No deduction data provided to process.']);
    exit;
}

try {
    $service = new \App\PayrollDeductionService($pdo);
    
    $result = $service->processBatch($input['deductions']);
    
    echo json_encode([
        'success' => true,
        'success_count' => $result['success_count'],
        'errors' => $result['errors'],
        'discrepancies' => $result['discrepancies'] ?? [] // [ADDED] Return discrepancies
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => "System Error: " . $e->getMessage()]);
}