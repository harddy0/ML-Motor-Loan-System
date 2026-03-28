<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $employeId = $payload['employe_id'] ?? null;
    $voidReason = trim((string)($payload['void_reason'] ?? ''));

    if (empty($employeId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Employee ID is required.']);
        exit;
    }

    if ($voidReason === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Void reason is required.']);
        exit;
    }

    $voidedByEmployeId = $_SESSION['employe_id'] ?? ($_SESSION['user_id'] ?? null);
    if (empty($voidedByEmployeId)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Missing authenticated user context.']);
        exit;
    }

    $loanService = new \App\LoanService($pdo);
    $result = $loanService->voidBorrowerLoans($employeId, $voidedByEmployeId, $voidReason);

    if (!empty($result['success'])) {
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to void borrower.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error while voiding borrower.']);
}
