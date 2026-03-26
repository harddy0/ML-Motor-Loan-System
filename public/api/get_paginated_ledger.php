<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

header('Content-Type: application/json');

try {
    $loanService = new \App\LoanService($pdo);
    $masterService = new \App\MasterDataService($pdo, $pdo2);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
    
    $search = $_GET['search'] ?? '';
    $fromDate = $_GET['from'] ?? '';
    $toDate = $_GET['to'] ?? '';
    $status = $_GET['status'] ?? '';

    $result = $loanService->getAllLedgerLoans(true, $page, $limit, $search, $fromDate, $toDate, $status);

    // =========================================================
    // MAP REGION CODES TO REGION NAMES FOR DISPLAY
    // =========================================================
    $masterData = $masterService->getRegionsAndDivisions();
    $regionMap = [];
    
    if (!empty($masterData['regions'])) {
        foreach ($masterData['regions'] as $r) {
            // Trim to ensure perfect match
            $regionMap[trim($r['value'])] = strtoupper(trim($r['label']));
        }
    }

    if (isset($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as &$row) {
            // UPDATED: Look for region_code instead of the empty 'region'
            $code = trim($row['region_code'] ?? '');
            if (isset($regionMap[$code])) {
                $row['region'] = $regionMap[$code];
            } else {
                $row['region'] = $code; // Fallback to raw code
            }
        }
        unset($row);
    }
    // =========================================================

    echo json_encode(['success' => true, 'payload' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}