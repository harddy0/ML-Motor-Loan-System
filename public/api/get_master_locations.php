<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';

try {
    // Pass BOTH $pdo (primary) and $pdo2 (secondary)
    $masterService = new \App\MasterDataService($pdo, $pdo2);
    
    $data = $masterService->getRegionsAndDivisions();

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}