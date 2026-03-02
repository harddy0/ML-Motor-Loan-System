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

    // --- STRICT DUPLICATE CHECK FOR MANUAL ENTRY ---
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
    
    // --- ADD THE LOGGED IN USER AS THE UPLOADER ---
    $loanData['uploaded_by_employe_id'] = $_SESSION['user_id'] ?? null;

    // 1. First, save the Database record (Loan + Borrower + Ledger)
    $result = $loanService->saveLoanApplication($loanData, $scheduleData);

    // 2. If the Database save was successful, save the KPTN file to Storage
    if ($result['success'] === true && isset($_FILES['kptn_receipt'])) {
        try {
            $docService = new \App\LoanDocumentService($pdo);
            $loanId = $result['loan_id'];
            $uploadedBy = $_SESSION['user_id'] ?? null;
            
            // Pass the file Array to the service to be parsed, stored, and recorded to DB
            $docService->uploadKptnReceipt($loanId, $uploadedBy, $_FILES['kptn_receipt'], "Initial manual loan entry proof");
            
        } catch (Exception $e) {
            // Optional: The loan saved successfully, but the upload failed.
            // We return success but attach a warning so the frontend can alert the user to upload it manually later.
            $result['warning'] = "Database record saved, but KPTN document upload failed: " . $e->getMessage();
        }
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>