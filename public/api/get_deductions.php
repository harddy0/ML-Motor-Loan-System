<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/PayrollDeductionService.php'; // Explicit load

try {
    $service = new \App\PayrollDeductionService($pdo);
    $data = $service->getAllDeductions();
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}