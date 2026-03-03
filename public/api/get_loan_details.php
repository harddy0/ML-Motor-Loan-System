<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';

if (!isset($_SESSION['employe_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['loan_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing loan_id parameter']);
    exit;
}

$loanId = intval($_GET['loan_id']);

try {
    $stmt = $pdo->prepare("SELECT l.loan_id, l.pending_kptn, l.pn_number, b.first_name, b.last_name FROM Loan l JOIN Borrowers b ON l.employe_id = b.employe_id WHERE l.loan_id = ? LIMIT 1");
    $stmt->execute([$loanId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Loan not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
