<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

$uploaderId = $_SESSION['user_id'] ?? null; 

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['borrowers']) || !is_array($input['borrowers'])) {
        throw new Exception("No borrower data received");
    }

    $loanService  = new \App\LoanService($pdo);
    
    // --- NEW: Initialize MasterDataService to translate text to codes ---
    // $pdo2 is available here because it is initialized in init.php
    $masterService = new \App\MasterDataService($pdo, $pdo2);
    
    // FETCH LIVE SYSTEM SETTING
    $settingsService = new \App\SettingsService($pdo);
    $dbAddOnRate = floatval($settingsService->getSetting('add_on_rate') ?? 0.015);
    
    $successCount = 0;
    $pnOffset     = 0; 
    $errors       = [];

    foreach ($input['borrowers'] as $borrower) {
        
        $requiresKptn = isset($borrower['requires_kptn']) ? filter_var($borrower['requires_kptn'], FILTER_VALIDATE_BOOLEAN) : true;

        // --- SWAP EXCEL NAMES FOR DATABASE CODES ---
        $regionCode = $borrower['region'] ?? 'N/A';
        if ($regionCode !== 'N/A') {
            $regionCode = $masterService->getRegionCodeByName($regionCode);
        }
        
        $branchId = $borrower['branch'] ?? 'N/A';
        if ($branchId !== 'N/A') {
            $branchId = $masterService->getBranchIdByName($branchId);
        }
        // --------------------------------------------

        $loanData = [
            'employe_id'             => $borrower['id'], 
            'first_name'             => $borrower['first_name'],
            'last_name'              => $borrower['last_name'],
            'contact_number'         => $borrower['contact_number'] ?? '000-000-0000',
            
            // ASSIGN THE NEW CODES HERE
            'region'                 => $regionCode,
            'branch'                 => $branchId,
            
            'division'               => $borrower['division'] ?? 'N/A',
            'reference_number'       => $borrower['reference_number'] ?? null,
            'pn_number'              => $borrower['pn_number'],
            'loan_amount'            => $borrower['loan_amount'],
            'terms'                  => $borrower['terms'],
            'deduction'              => $borrower['deduction'],
            'loan_granted'           => $borrower['loan_granted'],
            'pn_maturity'            => $borrower['pn_maturity'],
            
            // STRICTLY USE CALCULATED OR DATABASE RATE
            'add_on_rate_decimal'    => $borrower['add_on_rate_decimal'] ?? $dbAddOnRate,
            
            'uploaded_by_employe_id' => $uploaderId,
            
            'entry_type'             => 'BATCH',
            'requires_kptn'          => $requiresKptn,
            'kptn'                   => !$requiresKptn ? uniqid('NR_') : null, 
            'pending_kptn'           => $borrower['pending_kptn'] ?? null,
            'deposit_amount'         => $requiresKptn ? ($borrower['kptn_amount'] ?? 2500.00) : 0.00,
            'first_deduction'        => $borrower['first_deduction'] ?? null,
            'last_deduction'         => $borrower['last_deduction']  ?? null,
            'loan_month'             => !empty($borrower['loan_month'])      ? $borrower['loan_month']      : null,
            'mode_of_payment'        => !empty($borrower['mode_of_payment']) ? $borrower['mode_of_payment'] : null,
        ];

        $scheduleData = [
            'rows'          => [], 
            'periodic_rate' => $borrower['periodic_rate'] ?? 0
        ];
        $loanData['pn_offset'] = $pnOffset;
        
        $result = $loanService->saveLoanApplication($loanData, $scheduleData);

        if ($result['success']) {
            $successCount++;
            $pnOffset++;
        } else {
            $errors[] = "Failed ID {$borrower['id']}: " . ($result['error'] ?? 'Unknown');
        }
    }

    echo json_encode([
        'success'        => true, 
        'imported_count' => $successCount, 
        'errors'         => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>