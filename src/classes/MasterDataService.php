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
            return ['regions' => [], 'divisions' => []];
        }

        try {
            // UPDATED: If region_code is blank/null, use the maa_region name as the value
            $stmtRegions = $this->dbSecondary->query("
                SELECT 
                    COALESCE(NULLIF(TRIM(region_code), ''), NULLIF(TRIM(maa_region), ''), NULLIF(TRIM(region_description), '')) as value, 
                    COALESCE(NULLIF(TRIM(maa_region), ''), NULLIF(TRIM(region_description), '')) as label 
                FROM region_masterfile 
                WHERE (maa_region IS NOT NULL AND maa_region != '')
                   OR (region_description IS NOT NULL AND region_description != '')
                ORDER BY label
            ");
            $regions = $stmtRegions->fetchAll(PDO::FETCH_ASSOC);

            // Fetch division_code as value, description as label
            $stmtDivisions = $this->dbPrimary->query("
                SELECT division_code as value, description as label FROM Divisions ORDER BY description
            ");
            $divisions = $stmtDivisions->fetchAll(PDO::FETCH_ASSOC);

            return [
                'regions' => !empty($regions) ? $regions : [],
                'divisions' => !empty($divisions) ? $divisions : []
            ];
        } catch (Exception $e) {
            return ['regions' => [], 'divisions' => []];
        }
    }

    public function getBranchesByRegion($regionCode) {
        if (!$this->dbSecondary) return [];
        try {
            // Use named parameters for safer binding
            $stmt = $this->dbSecondary->prepare("
                SELECT branch_id AS value, ml_matic_branch_name AS label 
                FROM branch_profile 
                WHERE (
                    region_code = :rc 
                    OR UPPER(TRIM(maa_region)) = UPPER(TRIM(:rc))
                ) 
                  AND ml_matic_branch_name IS NOT NULL
                ORDER BY ml_matic_branch_name
            ");
            
            $stmt->execute(['rc' => $regionCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // DO NOT silently return []. Throw the error so the API can return it.
            // If you get an error after this, it means 'region_name' or 'region_code' 
            // does not match your actual branch_profile table columns.
            throw new Exception("Unable to load branches for the selected region right now. Please try again.");
        }
    }

    public function getValidRegions(): array {
        if (!$this->dbSecondary) return [];
        
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
            return [];
        }
    }

    public function getRegionCodeByName($regionName) {
        if (!$this->dbSecondary) return null; 
        try {
            $search = strtoupper(trim($regionName));

            // 1. Exact match across code, name, or description
            $stmt = $this->dbSecondary->prepare("
                SELECT COALESCE(NULLIF(TRIM(region_code), ''), NULLIF(TRIM(maa_region), ''), NULLIF(TRIM(region_description), '')) 
                FROM region_masterfile 
                WHERE UPPER(TRIM(region_code)) = :name 
                   OR UPPER(TRIM(maa_region)) = :name 
                   OR UPPER(TRIM(region_description)) = :name 
                LIMIT 1
            ");
            $stmt->execute(['name' => $search]);
            $code = $stmt->fetchColumn();
            
            if ($code) return $code;

            // 2. LIKE match fallback (if they typed part of the name)
            $stmtLike = $this->dbSecondary->prepare("
                SELECT COALESCE(NULLIF(TRIM(region_code), ''), NULLIF(TRIM(maa_region), ''), NULLIF(TRIM(region_description), '')) 
                FROM region_masterfile 
                WHERE UPPER(TRIM(region_code)) LIKE :search 
                   OR UPPER(TRIM(maa_region)) LIKE :search 
                   OR UPPER(TRIM(region_description)) LIKE :search 
                LIMIT 1
            ");
            $stmtLike->execute(['search' => $search . '%']);
            return $stmtLike->fetchColumn() ?: null;

        } catch (Exception $e) {
            return null; 
        }
    }

    public function getBranchIdByName($branchName) {
        if (!$this->dbSecondary) return null; 
        try {
            $branchNameUpper = strtoupper(trim($branchName));
            
            // 1. Check if the user entered the Branch ID directly (e.g., "466")
            if (is_numeric($branchNameUpper)) {
                $stmtId = $this->dbSecondary->prepare("
                    SELECT branch_id FROM branch_profile 
                    WHERE branch_id = :id LIMIT 1
                ");
                $stmtId->execute(['id' => $branchNameUpper]);
                $id = $stmtId->fetchColumn();
                if ($id) return $id; // Return the ID if it's valid
            }
            
            // 2. Exact name match check
            $stmtName = $this->dbSecondary->prepare("
                SELECT branch_id FROM branch_profile 
                WHERE UPPER(TRIM(ml_matic_branch_name)) = :name LIMIT 1
            ");
            $stmtName->execute(['name' => $branchNameUpper]);
            $id = $stmtName->fetchColumn();
            if ($id) return $id;
            
            // 3. Try adding "ML " prefix if it doesn't have it
            if (strpos($branchNameUpper, 'ML ') !== 0) {
                $stmtName->execute(['name' => 'ML ' . $branchNameUpper]);
                $id = $stmtName->fetchColumn();
                if ($id) return $id;
            }
            
            // 4. Try removing "ML " prefix if it already has it
            if (strpos($branchNameUpper, 'ML ') === 0) {
                $strippedName = trim(substr($branchNameUpper, 3));
                $stmtName->execute(['name' => $strippedName]);
                $id = $stmtName->fetchColumn();
                if ($id) return $id;
            }

            return null; // Branch definitely not found
        } catch (Exception $e) {
            return null; 
        }
    }
    
    public function getDivisionCodeByName($divisionName) {
        if (!$this->dbPrimary) return null;
        try {
            $stmt = $this->dbPrimary->prepare("
                SELECT division_code FROM Divisions 
                WHERE UPPER(TRIM(description)) = :name OR UPPER(TRIM(division_code)) = :name LIMIT 1
            ");
            $stmt->execute(['name' => strtoupper(trim($divisionName))]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}