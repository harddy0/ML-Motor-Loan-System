<?php
$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/NotificationService.php';

if (!isset($_SESSION['employe_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

$notifId = $_POST['notification_id'] ?? null;

if ($notifId) {
    try {
        $service = new \App\NotificationService($pdo);
        $service->markAsRead($notifId, $_SESSION['employe_id']);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing Notification ID']);
}