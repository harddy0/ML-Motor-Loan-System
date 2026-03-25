<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/PayrollDeductionService.php';

try {
    $service = new \App\PayrollDeductionService($pdo);
    
    // NEW: Initialize Master Data Service
    $masterService = new \App\MasterDataService($pdo, $pdo2);
    
    $data = $service->getAllDeductions();
    
    // =========================================================
    // MAP REGION CODES TO REGION NAMES
    // =========================================================
    $masterData = $masterService->getRegionsAndDivisions();
    $regionMap = [];
    
    if (!empty($masterData['regions'])) {
        foreach ($masterData['regions'] as $r) {
            $regionMap[$r['value']] = strtoupper($r['label']);
        }
    }

    if (is_array($data)) {
        foreach ($data as &$row) {
            $code = $row['region'] ?? '';
            if (isset($regionMap[$code])) {
                $row['region'] = $regionMap[$code];
            }
        }
        unset($row);
    }
    // =========================================================
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}