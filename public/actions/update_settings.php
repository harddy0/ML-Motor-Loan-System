<?php
require_once '../../src/includes/init.php';
use App\SettingsService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/loan-settings/?error=Invalid request');
    exit;
}

if ($_SESSION['user_type'] !== 'ADMIN') {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$ratePercent = filter_input(INPUT_POST, 'add_on_rate', FILTER_VALIDATE_FLOAT);

if ($ratePercent === false || $ratePercent < 0) {
    header('Location: ' . BASE_URL . '/loan-settings/?error=Invalid rate provided.');
    exit;
}

// Convert percentage back to decimal for database storage (e.g., 1.5 -> 0.015)
$decimalRate = $ratePercent / 100;

try {
    $settingsService = new SettingsService($pdo);
    $settingsService->updateSetting('add_on_rate', $decimalRate, $_SESSION['user_id']);
    
    header('Location: ' . BASE_URL . '/loan-settings/?success=Settings updated successfully');
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '/loan-settings/?error=Database Error: ' . urlencode($e->getMessage()));
}
exit;