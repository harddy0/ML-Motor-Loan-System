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
        throw new Exception("DUPLICATE ENTRY REJECTED:\nBorrower '" . strtoupper(trim($_POST['first_name']) . " " . trim($_POST['last_name'])) . "' is already registered in the database.");
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
    
    // --- FORCE KPTN AND DEPOSIT RULES ---
    $requiresKptn = isset($_POST['requires_kptn']) ? filter_var($_POST['requires_kptn'], FILTER_VALIDATE_BOOLEAN) : true;
    $loanData['requires_kptn'] = $requiresKptn;
    $loanData['uploaded_by_employe_id'] = $_SESSION['user_id'] ?? null;
    
    if (!$requiresKptn) {
        $loanData['deposit_amount'] = 0.00;
        $loanData['kptn'] = uniqid('NR_');
    } else {
        $loanData['deposit_amount'] = 2500.00;
    }

    // 1. Save to Database
    $result = $loanService->saveLoanApplication($loanData, $scheduleData);

    // Intercept duplicate KPTN constraint violation
    if ($result['success'] === false) {
        if (stripos($result['error'], 'Duplicate entry') !== false && stripos($result['error'], 'unique_kptn') !== false) {
            throw new Exception("Duplicate KPTN: The receipt code you entered is already associated with an existing loan.");
        }
        throw new Exception($result['error']); // rethrow standard errors
    }

    // 2. Only upload if SUCCESS, KPTN REQUIRED, and File exists
    if ($result['success'] === true && $requiresKptn && isset($_FILES['kptn_receipt']) && $_FILES['kptn_receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $docService = new \App\LoanDocumentService($pdo);
            $loanId = $result['loan_id'];
            $uploadedBy = $_SESSION['user_id'] ?? null;
            
            $docService->uploadKptnReceipt($loanId, $uploadedBy, $_FILES['kptn_receipt'], "Initial manual loan entry proof");
            
        } catch (Exception $e) {
            $result['warning'] = "Database record saved, but KPTN document upload failed: " . $e->getMessage();
        }
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>