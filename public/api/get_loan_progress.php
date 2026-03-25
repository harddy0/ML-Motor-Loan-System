<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/DashboardService.php';

try {
    $service  = new \App\DashboardService($pdo);

    $status = strtoupper(trim((string)($_GET['status'] ?? 'ONGOING')));
    $status = str_replace('_', ' ', $status);
    if (!in_array($status, ['ONGOING', 'FULLY PAID', 'ALL'], true)) {
        $status = 'ONGOING';
    }

    $limitRaw = isset($_GET['limit']) ? trim((string)$_GET['limit']) : '5';
    $limit = null;
    if ($limitRaw !== '') {
        $limitInt = (int)$limitRaw;
        $limit = $limitInt > 0 ? $limitInt : null;
    }

    $progress = $service->getLoanProgress($status, $limit);

    echo json_encode([
        'success' => true,
        'data'    => $progress,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}