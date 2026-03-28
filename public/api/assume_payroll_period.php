<?php
require_once '../../src/includes/init.php';

$noLayout = true;
// ADD THIS LINE to import the namespaced class
use App\PayrollDeductionService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$userType = $_SESSION['user_type'] ?? '';
if (!in_array($userType, ['ADMIN', 'REVIEWER'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access. Only Administrators and Reviewers can provision payments.']);
    exit;
}

$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;
$userId = $_SESSION['employe_id'] ?? null;

if (!$startDate || !$endDate || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// Now the system knows exactly which class to instantiate
$payrollService = new PayrollDeductionService($pdo);
$result = $payrollService->assumePaymentsForPeriod($startDate, $endDate, $userId);

echo json_encode($result);