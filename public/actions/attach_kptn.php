<?php
$noLayout = true;
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

try {
    if (empty($_POST['loan_id']) || empty($_POST['kptn_number'])) {
        throw new Exception("Missing Loan ID or KPTN Number.");
    }
    
    if (!isset($_FILES['kptn_receipt']) || $_FILES['kptn_receipt']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("A valid KPTN receipt file is required.");
    }

    $loanId = intval($_POST['loan_id']);
    $kptnCode = trim($_POST['kptn_number']);
    $uploaderId = $_SESSION['user_id'] ?? null;

    $loanService = new \App\LoanService($pdo);
    $docService = new \App\LoanDocumentService($pdo);

    // 1. Attach KPTN (Ledger is already active per the Do-It-Later workflow)
    $activationResult = $loanService->activateBatchLoan($loanId, $kptnCode, $uploaderId);

    if (!$activationResult['success']) {
        throw new Exception("Failed to attach KPTN code: " . $activationResult['error']);
    }

    // 2. Upload Document Proof
    $docService->uploadKptnReceipt($loanId, $uploaderId, $_FILES['kptn_receipt'], "Batch Import KPTN Verification");

    // 3. Resolve Sticky Notification Automatically
    require_once __DIR__ . '/../../src/classes/NotificationService.php';
    $notifService = new \App\NotificationService($pdo);
    $notifService->resolvePendingKptnNotification($loanId);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>