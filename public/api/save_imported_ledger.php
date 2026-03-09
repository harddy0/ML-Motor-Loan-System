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

try {
    $service = new \App\LedgerImportService($pdo);
    $result = $service->saveImportedLedger($input);
    
    if (ob_get_length()) ob_clean();
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}