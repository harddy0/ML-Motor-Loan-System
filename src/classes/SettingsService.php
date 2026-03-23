<?php
namespace App;

use PDO;
use Exception;

class SettingsService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM System_Settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function updateSetting($key, $value, $userId) {
        $stmt = $this->db->prepare("
            INSERT INTO System_Settings (setting_key, setting_value, updated_by) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_by = VALUES(updated_by)
        ");
        return $stmt->execute([$key, $value, $userId]);
    }
}