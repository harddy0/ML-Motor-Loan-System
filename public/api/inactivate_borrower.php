<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');
// Suppress PHP warnings from being output to the response to ensure valid JSON
ini_set('display_errors', '0');
error_reporting(0);

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'UNAUTHORIZED: You do not have permission to inactivate records.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$loanId = $input['loan_id'] ?? null;
$reason = trim($input['reason'] ?? '');

if (!$loanId) {
    echo json_encode(['success' => false, 'error' => 'Missing loan_id parameter.']);
    exit;
}

try {
    // Only change status if loan is currently ONGOING
    // Note: Database `current_status` enum doesn't include 'INACTIVE' on some installs.
    // To avoid enum truncation we set status to 'VOIDED' and store the reason/timestamp/user.
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("UPDATE Loan SET current_status = 'VOIDED', void_reason = ?, voided_at = CURRENT_TIMESTAMP, voided_by_employe_id = ? WHERE loan_id = ? AND current_status = 'ONGOING'");
    $stmt->execute([$reason, $userId, $loanId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'No matching ongoing loan found or already inactive.']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
