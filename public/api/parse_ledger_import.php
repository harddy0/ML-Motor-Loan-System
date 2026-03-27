<?php
$noLayout = true;

// Prevent PHP fatals from breaking the JSON response
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Fatal Server Error: ' . $error['message']]);
        exit;
    }
});

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/LedgerImportService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);

if (!in_array(strtolower($ext), ['xls', 'xlsx', 'csv'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Upload an Excel or CSV file.']);
    exit;
}

try {
    // UPDATED: Pass $pdo2 for branch validation
    $service = new \App\LedgerImportService($pdo, isset($pdo2) ? $pdo2 : null);
    $result = $service->parseExcel($file['tmp_name']);
    
    if (ob_get_length()) ob_clean();
    echo json_encode($result);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}