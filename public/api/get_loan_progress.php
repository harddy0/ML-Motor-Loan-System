<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/DashboardService.php';

function normalizeDateParam($value): ?string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return null;
    }

    $d = \DateTime::createFromFormat('Y-m-d', $raw);
    if (!$d || $d->format('Y-m-d') !== $raw) {
        return null;
    }

    return $raw;
}

try {
    $service  = new \App\DashboardService($pdo);

    $status = strtoupper(trim((string)($_GET['status'] ?? 'ONGOING')));
    $status = str_replace('_', ' ', $status);
    if (!in_array($status, ['ONGOING', 'FULLY PAID', 'INACTIVE', 'ALL'], true)) {
        $status = 'ONGOING';
    }

    $limitRaw = isset($_GET['limit']) ? trim((string)$_GET['limit']) : '5';
    $limit = null;
    if ($limitRaw !== '') {
        $limitInt = (int)$limitRaw;
        $limit = $limitInt > 0 ? $limitInt : null;
    }

    $fromDate = normalizeDateParam($_GET['from'] ?? '');
    $toDate = normalizeDateParam($_GET['to'] ?? '');
    $search = trim((string)($_GET['search'] ?? ''));

    if (($fromDate && !$toDate) || (!$fromDate && $toDate)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Both from and to dates are required for date filtering.',
        ]);
        exit;
    }

    if ($fromDate && $toDate && strcmp($fromDate, $toDate) > 0) {
        [$fromDate, $toDate] = [$toDate, $fromDate];
    }

    $progress = $service->getLoanProgress($status, $limit, $fromDate, $toDate, $search);

    echo json_encode([
        'success' => true,
        'data'    => $progress,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}