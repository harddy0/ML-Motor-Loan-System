<?php
namespace App;

use PDO;
use Exception;

class MasterDataService {
    private $dbPrimary;
    private $dbSecondary;

    public function __construct($dbPrimary, $dbSecondary) {
        $this->dbPrimary = $dbPrimary; 
        $this->dbSecondary = $dbSecondary; 
    }

    public function getRegionsAndDivisions() {
        if (!$this->dbSecondary || !$this->dbPrimary) {
            return $this->getFallbackData();
        }

        try {
            // 1. Fetch Regions (Get both code and name from secondary DB)
            $stmtRegions = $this->dbSecondary->query("
                SELECT region_code as value, para_region as label 
                FROM region_masterfile 
                WHERE para_region IS NOT NULL AND para_region != ''
                ORDER BY para_region
            ");
            $regions = $stmtRegions->fetchAll(PDO::FETCH_ASSOC);

            // 2. Fetch Divisions (from the NEW Divisions table in primary DB)
            $stmtDivisions = $this->dbPrimary->query("
                SELECT description 
                FROM Divisions 
                ORDER BY description
            ");
            $divisions = $stmtDivisions->fetchAll(PDO::FETCH_COLUMN);

            return [
                'regions' => !empty($regions) ? $regions : $this->getFallbackData()['regions'],
                'divisions' => !empty($divisions) ? $divisions : $this->getFallbackData()['divisions']
            ];

        } catch (Exception $e) {
            return $this->getFallbackData();
        }
    }

    public function getBranchesByRegion($regionCode) {
        if (!$this->dbSecondary) return [];
        try {
            // Fetch Branches based on the Region Code
            $stmt = $this->dbSecondary->prepare("
                SELECT ml_matic_branch_name 
                FROM branch_profile 
                WHERE region_code = ? AND ml_matic_branch_name IS NOT NULL
                ORDER BY ml_matic_branch_name
            ");
            $stmt->execute([$regionCode]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getFallbackData() {
        return [
            'regions' => [
                ['value' => '01', 'label' => 'HEAD OFFICE'],
                ['value' => '02', 'label' => 'CEBU']
            ],
            'divisions' => ['CAD', 'IAD', 'MKD', 'FND', 'MMD']
        ];
    }
}