<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

try {
    if (empty($_POST['employe_id']) || empty($_POST['loan_amount']) || empty($_POST['schedule'])) {
        throw new Exception("Missing required loan data.");
    }

    $loanService = new \App\LoanService($pdo);

    if ($loanService->isBorrowerExists($_POST['first_name'], $_POST['last_name'])) {
        throw new Exception("DUPLICATE ENTRY REJECTED:\nBorrower '" . strtoupper(trim($_POST['first_name']) . " " . trim($_POST['last_name'])) . "' is already registered.");
    }

    $scheduleRows = json_decode($_POST['schedule'], true);
    if (!is_array($scheduleRows)) {
        throw new Exception("Invalid schedule data format.");
    }

   $scheduleData = [
        'rows' => $scheduleRows,
        'periodic_rate' => $_POST['periodic_rate'] ?? 0
    ];

    $loanData = $_POST;
    $masterService = new \App\MasterDataService($pdo, $pdo2);

    // =========================================================================
    // 1. REGION MAPPING (WITH "HO" ALIAS SUPPORT)
    // =========================================================================
    $regionName = strtoupper(trim($_POST['region_name'] ?? ''));
    $isHO = ($regionName === 'HO' || strpos($regionName, 'HEAD OFFICE') !== false);

    if (!empty($_POST['region_code'])) {
        $loanData['region'] = trim($_POST['region_code']); // Passes code to LoanService
    } else {
        if ($isHO) {
            // Alias 'HO' to 'HEAD OFFICE' to ensure lookup success, default to '01' if DB is empty
            $resolvedRegion = $masterService->getRegionCodeByName('HEAD OFFICE') ?: '01';
        } else {
            $resolvedRegion = $masterService->getRegionCodeByName($regionName);
        }
        
        if ($resolvedRegion === null) {
            throw new Exception("Submission Rejected: Region '{$regionName}' is not recognized.");
        }
        $loanData['region'] = $resolvedRegion;
    }

    // =========================================================================
    // 2. BRANCH VS DIVISION MAPPING
    // =========================================================================
    if ($isHO || $loanData['region'] === '01') {
        // For Head Office: Branch is N/A, Division is captured
        $loanData['branch']   = 'N/A';
        $loanData['division'] = !empty($_POST['division']) ? strtoupper(trim($_POST['division'])) : 'N/A';
    } else {
        // For Standard Regions: Division is N/A, Branch is captured
        $loanData['division'] = 'N/A';
        
        if (!empty($_POST['branch_id'])) {
            $loanData['branch'] = trim($_POST['branch_id']);
        } else {
            $branchName = $_POST['branch_name'] ?? '';
            if (trim($branchName) === '' || strtoupper(trim($branchName)) === 'N/A') {
                $loanData['branch'] = 'N/A';
            } else {
                $resolvedBranch = $masterService->getBranchIdByName($branchName);
                if ($resolvedBranch === null) {
                    throw new Exception("Submission Rejected: Branch '{$branchName}' is not recognized.");
                }
                $loanData['branch'] = $resolvedBranch;
            }
        }
    }
    
    // =========================================================================
    // 3. KPTN AND DEPOSIT LOGIC
    // =========================================================================
    $requiresKptn = isset($_POST['requires_kptn']) ? filter_var($_POST['requires_kptn'], FILTER_VALIDATE_BOOLEAN) : true;
    $loanData['requires_kptn'] = $requiresKptn;
    $loanData['uploaded_by_employe_id'] = $_SESSION['user_id'] ?? null;
    
    if (!$requiresKptn) {
        $loanData['deposit_amount'] = 0.00;
        $loanData['kptn'] = uniqid('NR_');
    } else {
        $loanData['deposit_amount'] = 2500.00;
    }

    // 4. Save to Database
    $result = $loanService->saveLoanApplication($loanData, $scheduleData);

    if ($result['success'] === false) {
        if (stripos($result['error'], 'Duplicate entry') !== false && stripos($result['error'], 'unique_kptn') !== false) {
            throw new Exception("Duplicate KPTN: The receipt code you entered is already associated with an existing loan.");
        }
        throw new Exception($result['error']); 
    }

    // 5. File Upload Process
    if ($result['success'] === true && $requiresKptn && isset($_FILES['kptn_receipt']) && $_FILES['kptn_receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $docService = new \App\LoanDocumentService($pdo);
            $docService->uploadKptnReceipt($result['loan_id'], $_SESSION['user_id'] ?? null, $_FILES['kptn_receipt'], "Initial manual loan entry proof");
        } catch (Exception $e) {
            $result['warning'] = "Database record saved, but KPTN document upload failed: " . $e->getMessage();
        }
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}