<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

header('Content-Type: application/json');

try {
    $service = new \App\PayrollDeductionService($pdo);

    $page   = isset($_GET['page'])   ? max(1, intval($_GET['page']))   : 1;
    $limit  = isset($_GET['limit'])  ? max(1, intval($_GET['limit']))  : 100;

    $search   = $_GET['search'] ?? '';
    $fromDate = $_GET['from']   ?? '';
    $toDate   = $_GET['to']     ?? '';

    $result = $service->getPaginatedDeductions($page, $limit, $search, $fromDate, $toDate);

    echo json_encode(['success' => true, 'payload' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}