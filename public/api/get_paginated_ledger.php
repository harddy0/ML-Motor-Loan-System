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
    // 1. MAP REGION CODES TO REGION NAMES
    // =========================================================
    $masterData = $masterService->getRegionsAndDivisions();
    $regionMap = [];
    
    if (!empty($masterData['regions'])) {
        foreach ($masterData['regions'] as $r) {
            $regionMap[trim($r['value'])] = strtoupper(trim($r['label']));
        }
    }

    // =========================================================
    // 2. MAP BRANCH IDs TO BRANCH NAMES
    // =========================================================
    $branchMap = [];
    if (isset($pdo2)) {
        try {
            // Fetch all branches from the secondary DB
            $stmtB = $pdo2->query("SELECT branch_id, ml_matic_branch_name FROM branch_profile WHERE ml_matic_branch_name IS NOT NULL");
            $branches = $stmtB->fetchAll(PDO::FETCH_ASSOC);
            foreach ($branches as $b) {
                $branchMap[trim($b['branch_id'])] = strtoupper(trim($b['ml_matic_branch_name']));
            }
        } catch(Exception $e) {
            // Silently ignore DB errors on secondary, it will safely fallback to the ID
        }
    }

    // =========================================================
    // 3. APPLY MAPPINGS TO THE RESULT DATA
    // =========================================================
    if (isset($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as &$row) {
            
            // Apply Region Map
            $rCode = trim($row['region_code'] ?? '');
            if (isset($regionMap[$rCode])) {
                $row['region'] = $regionMap[$rCode];
            } else {
                $row['region'] = $rCode; // Fallback to raw code
            }

            // Apply Branch Map
            $bId = trim($row['branch_id'] ?? '');
            if (isset($branchMap[$bId])) {
                $row['branch'] = $branchMap[$bId];
            } else {
                $row['branch'] = $bId; // Fallback to raw ID
            }
        }
        unset($row);
    }

    echo json_encode(['success' => true, 'payload' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}