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
            // 1. Fetch Regions (Prioritize para_region, fallback to region_description)
            $stmtRegions = $this->dbSecondary->query("
                SELECT 
                    region_code as value, 
                    COALESCE(NULLIF(TRIM(para_region), ''), NULLIF(TRIM(region_description), '')) as label 
                FROM region_masterfile 
                WHERE (para_region IS NOT NULL AND para_region != '')
                   OR (region_description IS NOT NULL AND region_description != '')
                ORDER BY label
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

    /**
     * Returns the canonical list of valid region names for Excel import validation.
     * Fetches BOTH para_region (used by the add modal) AND region_description
     * from region_masterfile so that Excel values can match either field.
     *
     * Returns a flat array of uppercase strings for fast in_array() checks.
     * Falls back to getFallbackData() if the secondary DB is unavailable.
     */
    public function getValidRegions(): array {
        if (!$this->dbSecondary) {
            // Return fallback labels uppercased
            return array_map(
                fn($r) => strtoupper($r['label']),
                $this->getFallbackData()['regions']
            );
        }

        try {
            $stmt = $this->dbSecondary->query("
                SELECT DISTINCT para_region, region_description
                FROM region_masterfile
                WHERE (para_region IS NOT NULL AND para_region != '')
                   OR (region_description IS NOT NULL AND region_description != '')
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $valid = [];
            foreach ($rows as $row) {
                if (!empty($row['para_region'])) {
                    $valid[] = strtoupper(trim($row['para_region']));
                }
                if (!empty($row['region_description'])) {
                    $valid[] = strtoupper(trim($row['region_description']));
                }
            }

            // Deduplicate and re-index
            return array_values(array_unique($valid));

        } catch (Exception $e) {
            // Graceful fallback — never hard-crash import over a lookup failure
            return array_map(
                fn($r) => strtoupper($r['label']),
                $this->getFallbackData()['regions']
            );
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