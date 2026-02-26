<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';

try {
    $regionCode = $_GET['region_code'] ?? '';
    
    $masterService = new \App\MasterDataService($pdo, $pdo2);
    $branches = $masterService->getBranchesByRegion($regionCode);

    echo json_encode(['success' => true, 'data' => $branches]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}