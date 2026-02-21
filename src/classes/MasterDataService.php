<?php
namespace App;

use PDO;
use Exception;

class MasterDataService {
    private $db;

    public function __construct($dbConnection) {
        // This expects $pdo2 from init.php
        $this->db = $dbConnection; 
    }

    public function getRegionsAndDivisions() {
        // SAFEGUARD: If the secondary DB connection failed/is null, return default fallbacks
        if (!$this->db) {
            return $this->getFallbackData();
        }

        try {
            // 1. Fetch Regions (from region_masterfile, column para_region)
            $stmtRegions = $this->db->query("
                SELECT DISTINCT para_region 
                FROM region_masterfile 
                WHERE para_region IS NOT NULL AND para_region != ''
                ORDER BY para_region
            ");
            $regions = $stmtRegions->fetchAll(PDO::FETCH_COLUMN);

            // 2. Fetch Divisions/Departments (from partner_masterfile, column partner_name)
            $stmtDivisions = $this->db->query("
                SELECT DISTINCT partner_name 
                FROM partner_masterfile 
                WHERE partner_name IS NOT NULL AND partner_name != ''
                ORDER BY partner_name
            ");
            $divisions = $stmtDivisions->fetchAll(PDO::FETCH_COLUMN);

            return [
                'regions' => !empty($regions) ? $regions : $this->getFallbackData()['regions'],
                'divisions' => !empty($divisions) ? $divisions : $this->getFallbackData()['divisions']
            ];

        } catch (Exception $e) {
            // If the query fails (e.g., table doesn't exist or DB goes down), fail gracefully to the fallback
            // Optional: error_log("Master DB Query Failed: " . $e->getMessage());
            return $this->getFallbackData();
        }
    }

    private function getFallbackData() {
        return [
            'regions' => ['DAVAO', 'CEBU', 'MANILA', 'HEAD OFFICE', 'N/A'],
            'divisions' => ['OPERATIONS', 'HR', 'IT', 'FINANCE', 'N/A']
        ];
    }
}