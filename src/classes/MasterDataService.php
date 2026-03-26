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
            $stmtRegions = $this->dbSecondary->query("
                SELECT 
                    region_code as value, 
                    COALESCE(NULLIF(TRIM(maa_region), ''), NULLIF(TRIM(region_description), '')) as label 
                FROM region_masterfile 
                WHERE (maa_region IS NOT NULL AND maa_region != '')
                   OR (region_description IS NOT NULL AND region_description != '')
                ORDER BY label
            ");
            $regions = $stmtRegions->fetchAll(PDO::FETCH_ASSOC);

            $stmtDivisions = $this->dbPrimary->query("
                SELECT description FROM Divisions ORDER BY description
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
            $stmt = $this->dbSecondary->prepare("
                SELECT branch_id AS value, ml_matic_branch_name AS label 
                FROM branch_profile 
                WHERE region_code = ? AND ml_matic_branch_name IS NOT NULL
                ORDER BY ml_matic_branch_name
            ");
            $stmt->execute([$regionCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getValidRegions(): array {
        if (!$this->dbSecondary) {
            return array_map(fn($r) => strtoupper($r['label']), $this->getFallbackData()['regions']);
        }
        try {
            $stmt = $this->dbSecondary->query("
                SELECT DISTINCT maa_region, region_description
                FROM region_masterfile
                WHERE (maa_region IS NOT NULL AND maa_region != '')
                   OR (region_description IS NOT NULL AND region_description != '')
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $valid = [];
            foreach ($rows as $row) {
                if (!empty($row['maa_region'])) { $valid[] = strtoupper(trim($row['maa_region'])); }
                if (!empty($row['region_description'])) { $valid[] = strtoupper(trim($row['region_description'])); }
            }
            return array_values(array_unique($valid));
        } catch (Exception $e) {
            return array_map(fn($r) => strtoupper($r['label']), $this->getFallbackData()['regions']);
        }
    }

    private function getFallbackData() {
        return [
            'regions' => [ ['value' => '01', 'label' => 'HEAD OFFICE'], ['value' => '02', 'label' => 'CEBU'] ],
            'divisions' => ['CAD', 'IAD', 'MKD', 'FND', 'MMD']
        ];
    }

    public function getRegionCodeByName($regionName) {
        if (!$this->dbSecondary) return null; 
        try {
            $stmt = $this->dbSecondary->prepare("
                SELECT region_code FROM region_masterfile 
                WHERE UPPER(TRIM(maa_region)) = :name OR UPPER(TRIM(region_description)) = :name LIMIT 1
            ");
            $stmt->execute(['name' => strtoupper(trim($regionName))]);
            return $stmt->fetchColumn() ?: null; 
        } catch (Exception $e) {
            return null; 
        }
    }

    public function getBranchIdByName($branchName) {
        if (!$this->dbSecondary) return null; 
        try {
            $stmt = $this->dbSecondary->prepare("
                SELECT branch_id FROM branch_profile 
                WHERE UPPER(TRIM(ml_matic_branch_name)) = :name LIMIT 1
            ");
            $stmt->execute(['name' => strtoupper(trim($branchName))]);
            return $stmt->fetchColumn() ?: null; 
        } catch (Exception $e) {
            return null; 
        }
    }
}