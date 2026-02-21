<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';

try {
    // Pass $pdo2 (the secondary connection from init.php)
    $masterService = new \App\MasterDataService($pdo2);
    
    // Fetch the data safely
    $data = $masterService->getRegionsAndDivisions();

    echo json_encode([
        'success' => true, 
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}