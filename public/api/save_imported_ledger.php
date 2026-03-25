<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/LedgerImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Detect multipart vs JSON
if (!empty($_FILES['kptn_receipt'])) {
    $input = json_decode($_POST['data'] ?? '{}', true);
} else {
    $input = json_decode(file_get_contents('php://input'), true);
}

// ---> ADDED: Inject the uploader's session ID for auditing and notifications <---
$input['uploaded_by_employe_id'] = $_SESSION['user_id'] ?? null;

if (empty($input['borrower']) || empty($input['ledger'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data to save.']);
    exit;
}

// =========================================================================
// REVERSE LOOKUP FOR REGION AND BRANCH CODES (STRICT VALIDATION)
// =========================================================================
$masterService = new \App\MasterDataService($pdo, $pdo2);

// 1. REGION (Usually Mandatory)
$regionName = $input['borrower']['region'] ?? '';
if (trim($regionName) === '' || strtoupper(trim($regionName)) === 'N/A') {
    $input['borrower']['region'] = 'N/A';
} else {
    $regionCode = $masterService->getRegionCodeByName($regionName);
    if ($regionCode === null) {
        echo json_encode(['success' => false, 'error' => "Import Rejected: Region '{$regionName}' is not recognized in the system."]);
        exit;
    }
    $input['borrower']['region'] = $regionCode;
}

// 2. BRANCH (Optional, but strict if provided)
$branchName = $input['borrower']['branch'] ?? '';
if (trim($branchName) === '' || strtoupper(trim($branchName)) === 'N/A') {
    // It is blank or N/A, so we allow it to pass safely
    $input['borrower']['branch'] = 'N/A';
} else {
    // It has text (e.g., 'ADASDW' or 'CEBU MAIN'), so we MUST validate it
    $branchId = $masterService->getBranchIdByName($branchName);
    if ($branchId === null) {
        echo json_encode(['success' => false, 'error' => "Import Rejected: Branch '{$branchName}' is not recognized in the system. Leave it blank if there is no branch."]);
        exit;
    }
    $input['borrower']['branch'] = $branchId;
}
// =========================================================================

try {
    $service = new \App\LedgerImportService($pdo);
    $result = $service->saveImportedLedger($input);
    
    if (ob_get_length()) ob_clean();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}