<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/PayrollDeductionService.php'; // Hard load to prevent class-not-found errors

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

// Read the incoming JSON from JS
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['deductions']) || !is_array($input['deductions'])) {
    echo json_encode(['success' => false, 'error' => 'No deduction data provided to process.']);
    exit;
}

try {
    // Instantiate using the global $pdo from init.php
    $service = new \App\PayrollDeductionService($pdo);
    
    // Process the batch
    $result = $service->processBatch($input['deductions']);
    
    // Always return success if the script finishes, even if some specific rows failed
    echo json_encode([
        'success' => true,
        'success_count' => $result['success_count'],
        'errors' => $result['errors'] // Front-end will display these
    ]);

} catch (Exception $e) {
    // This catches fatal DB execution errors
    echo json_encode(['success' => false, 'error' => "System Error: " . $e->getMessage()]);
}