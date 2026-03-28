<?php
require_once __DIR__ . '/../../src/includes/init.php';

$noLayout = true;
header('Content-Type: application/json');

// Security Check: Only logged in users (or admins) can fetch this
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized Access']);
    exit;
}

try {
    // Fetch the setting and join with Users to get the name of who updated it
    $stmt = $pdo->prepare("
        SELECT s.setting_value, s.updated_at, u.first_name, u.last_name 
        FROM System_Settings s
        LEFT JOIN Users u ON s.updated_by = u.employe_id
        WHERE s.setting_key = 'add_on_rate'
    ");
    $stmt->execute();
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    // Default calculations if no row exists yet
    $decimalValue = $setting ? (float)$setting['setting_value'] : 0.015;
    $percentValue = $decimalValue * 100;

    $updatedAt = ($setting && $setting['updated_at']) 
        ? date('F d, Y \a\t h:i A', strtotime($setting['updated_at'])) 
        : 'Never Updated';

    $updatedBy = ($setting && $setting['first_name']) 
        ? mb_strtoupper($setting['first_name'] . ' ' . $setting['last_name']) 
        : 'SYSTEM DEFAULT';

    // Send back the formatted JSON data
    echo json_encode([
        'success' => true,
        'data' => [
            'rate_percent' => $percentValue,
            'updated_at'   => $updatedAt,
            'updated_by'   => $updatedBy
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Unable to load system settings right now. Please try again.']);
}
exit;