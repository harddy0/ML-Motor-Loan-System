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

    $result = $loanService->getAllBorrowers(true, $page, $limit, $search, $fromDate, $toDate, $status);

    $masterData = $masterService->getRegionsAndDivisions();
    $regionMap = [];
    if (!empty($masterData['regions'])) {
        foreach ($masterData['regions'] as $r) {
            $regionMap[$r['value']] = strtoupper($r['label']);
        }
    }

    // MAP REGION CODE -> REGION NAME FOR FRONTEND
    if (isset($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as &$row) {
            $code = $row['region_code'] ?? '';
            $row['region'] = isset($regionMap[$code]) ? $regionMap[$code] : $code;
        }
        unset($row);
    }

    echo json_encode(['success' => true, 'payload' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}