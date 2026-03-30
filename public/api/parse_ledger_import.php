<?php
$noLayout = true;

// Prevent PHP fatals from breaking the JSON response
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Fatal Server Error: ' . $error['message']]);
        exit;
    }
});

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/LedgerImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

if (!in_array(strtolower($ext), ['xls', 'xlsx', 'csv'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Upload an Excel or CSV file.']);
    exit;
}

try {
    // UPDATED: Pass $pdo2 for branch validation
    $service = new \App\LedgerImportService($pdo, isset($pdo2) ? $pdo2 : null);
    $result = $service->parseExcel($file['tmp_name']);

    if (!empty($result['success']) && !empty($result['borrower']) && is_array($result['borrower'])) {
        $masterService = new \App\MasterDataService($pdo, isset($pdo2) ? $pdo2 : null);
        $regionMap = [];
        $branchMap = [];

        $masterData = $masterService->getRegionsAndDivisions();
        if (!empty($masterData['regions']) && is_array($masterData['regions'])) {
            foreach ($masterData['regions'] as $regionRow) {
                $value = trim((string)($regionRow['value'] ?? ''));
                $label = trim((string)($regionRow['label'] ?? ''));
                if ($value !== '' && $label !== '') {
                    $regionMap[$value] = strtoupper($label);
                }
            }
        }

        if (isset($pdo2)) {
            $stmtB = $pdo2->query("SELECT branch_id, ml_matic_branch_name FROM branch_profile WHERE ml_matic_branch_name IS NOT NULL");
            $branches = $stmtB->fetchAll(PDO::FETCH_ASSOC);
            foreach ($branches as $branchRow) {
                $id = trim((string)($branchRow['branch_id'] ?? ''));
                $name = trim((string)($branchRow['ml_matic_branch_name'] ?? ''));
                if ($id !== '' && $name !== '') {
                    $branchMap[$id] = strtoupper($name);
                }
            }
        }

        $regionCode = trim((string)($result['borrower']['region'] ?? ''));
        $branchId = trim((string)($result['borrower']['branch'] ?? ''));

        $regionNameFromFile = trim((string)($result['borrower']['region_name'] ?? ''));
        $branchNameFromFile = trim((string)($result['borrower']['branch_name'] ?? ''));

        if (isset($regionMap[$regionCode])) {
            $result['borrower']['region_display'] = $regionMap[$regionCode];
        } elseif ($regionNameFromFile !== '') {
            $result['borrower']['region_display'] = $regionNameFromFile;
        } else {
            $result['borrower']['region_display'] = $regionCode !== '' ? $regionCode : 'N/A';
        }

        if (isset($branchMap[$branchId])) {
            $result['borrower']['branch_display'] = $branchMap[$branchId];
        } elseif ($branchNameFromFile !== '') {
            $result['borrower']['branch_display'] = $branchNameFromFile;
        } else {
            $result['borrower']['branch_display'] = $branchId !== '' ? $branchId : 'N/A';
        }
    }
    
    if (ob_get_length()) ob_clean();
    echo json_encode($result);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
