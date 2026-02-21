<?php
namespace App;

use PDO;
use Exception;

class RunningReceivablesService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Calculates and upserts the snapshot for a given loan and cutoff date.
     */
    public function generateSnapshot($loanId, $payrollDate) {
        $timestamp = strtotime($payrollDate);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);

        // Define reporting period boundary
        $reportingPeriod = "$year-$month-01";
        
        // Define period half and cutoff
        if ($day <= 15) {
            $periodHalf = '1ST';
            $cutoffDate = date('Y-m-15', $timestamp);
            $periodStart = "$year-$month-01";
        } else {
            $periodHalf = '2ND';
            $cutoffDate = date('Y-m-t', $timestamp); 
            $periodStart = "$year-$month-16";
        }

        // 1. Get Loan and Borrower Details
        $stmt = $this->db->prepare("
            SELECT l.loan_amount, l.date_granted, l.current_status, 
                   b.region, b.division as dealer 
            FROM Loan l
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE l.loan_id = ?
        ");
        $stmt->execute([$loanId]);
        $loanInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loanInfo) return;

        // 2. Calculate Period Principal and Income (Paid in this cutoff)
        $stmt = $this->db->prepare("
            SELECT IFNULL(SUM(principal_amt), 0) as period_principal,
                   IFNULL(SUM(interest_amt), 0) as period_income
            FROM Amortization_Ledger
            WHERE loan_id = ? AND status = 'PAID' AND date_paid BETWEEN ? AND ?
        ");
        $stmt->execute([$loanId, $periodStart, $cutoffDate]);
        $periodTotals = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Calculate Prior Payments (Paid strictly before this period)
        $stmt = $this->db->prepare("
            SELECT IFNULL(SUM(principal_amt), 0) as prior_principal
            FROM Amortization_Ledger
            WHERE loan_id = ? AND status = 'PAID' AND date_paid < ?
        ");
        $stmt->execute([$loanId, $periodStart]);
        $priorTotals = $stmt->fetch(PDO::FETCH_ASSOC);

        $periodPrincipal = (float)$periodTotals['period_principal'];
        $periodIncome = (float)$periodTotals['period_income'];
        $priorPayments = (float)$priorTotals['prior_principal'];
        
        $accumulatedPayments = $priorPayments + $periodPrincipal;
        $outstandingBalance = (float)$loanInfo['loan_amount'] - $accumulatedPayments;

        // 4. Upsert Snapshot
        $sql = "
            INSERT INTO Running_AR_Summary 
            (loan_id, reporting_period, period_half, cutoff_date, loan_granted, region, dealer, 
             loan_amount, period_principal, prior_payments, accumulated_payments, outstanding_balance, 
             period_income, loan_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            loan_granted = VALUES(loan_granted),
            region = VALUES(region),
            dealer = VALUES(dealer),
            loan_amount = VALUES(loan_amount),
            period_principal = VALUES(period_principal),
            prior_payments = VALUES(prior_payments),
            accumulated_payments = VALUES(accumulated_payments),
            outstanding_balance = VALUES(outstanding_balance),
            period_income = VALUES(period_income),
            loan_status = VALUES(loan_status),
            generated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $loanId, $reportingPeriod, $periodHalf, $cutoffDate, 
            $loanInfo['date_granted'] ?: date('Y-m-d'),
            $loanInfo['region'], $loanInfo['dealer'], $loanInfo['loan_amount'], 
            $periodPrincipal, $priorPayments, $accumulatedPayments, 
            $outstandingBalance, $periodIncome, $loanInfo['current_status']
        ]);
    }


    /**
     * Aggregates the snapshot view, appending new loans and safely handling dates.
     * PREVENTS STATUS TIME-TRAVEL AND STRICT-MODE CRASHES
     */
    public function getReportData($yearMonth, $periodHalf = null, $statusFilter = 'ONGOING') {
        $reportingPeriod = $yearMonth . '-01';
        $params = [$reportingPeriod];
        
        $halfFilter = "";
        if ($periodHalf === '1ST' || $periodHalf === '2ND') {
            $halfFilter = " AND r.period_half = ? ";
            $params[] = $periodHalf;
        }

        // Determine precise cutoff dates for ledger calculations
        $year = substr($yearMonth, 0, 4);
        $month = substr($yearMonth, 5, 2);
        
        if ($periodHalf === '1ST') {
            $periodStart = "$year-$month-01";
            $cutoffDate = "$year-$month-15";
        } elseif ($periodHalf === '2ND') {
            $periodStart = "$year-$month-16";
            $cutoffDate = date('Y-m-t', strtotime("$year-$month-01"));
        } else { // Whole Month
            $periodStart = "$year-$month-01";
            $cutoffDate = date('Y-m-t', strtotime("$year-$month-01"));
        }

        // =========================================================
        // TIME-TRAVEL PROOF STATUS FILTER
        // =========================================================
        $statusCondition = "";
        if ($statusFilter === 'ONGOING') {
            // It was ongoing if it wasn't completed yet, or completed AFTER this report's cutoff
            $statusCondition = " AND (l.date_completed IS NULL OR l.date_completed > '$cutoffDate') ";
        } elseif ($statusFilter === 'FULLY_PAID') {
            // Only show if they finished paying EXACTLY during this specific period window
            $statusCondition = " AND l.date_completed BETWEEN '$periodStart' AND '$cutoffDate' ";
        } else {
            // ALL (Ongoing during this period + anything completed during/after this period)
            $statusCondition = " AND (l.date_completed IS NULL OR l.date_completed >= '$periodStart') ";
        }

        // =========================================================
        // STEP 1: Get RR Data
        // =========================================================
        $stmt = $this->db->prepare("
            SELECT 
                r.loan_id, b.employe_id, CONCAT(b.first_name, ' ', b.last_name) as name, b.region,
                MAX(r.loan_granted) as loan_granted, MAX(r.loan_amount) as loan_amount,
                SUM(r.period_principal) as period_principal, MIN(r.prior_payments) as prior_payments, 
                MAX(r.accumulated_payments) as accumulated_payments, MIN(r.outstanding_balance) as outstanding_balance,
                SUM(r.period_income) as period_income, 
                
                -- Determine their historical status dynamically!
                CASE 
                    WHEN MAX(l.date_completed) BETWEEN '$periodStart' AND '$cutoffDate' THEN 'FULLY PAID'
                    ELSE 'ONGOING'
                END as loan_status

            FROM Running_AR_Summary r
            JOIN Loan l ON r.loan_id = l.loan_id
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE r.reporting_period = ? 
            $halfFilter
            $statusCondition
            GROUP BY r.loan_id, b.employe_id, b.first_name, b.last_name, b.region
        ");
        $stmt->execute($params);
        $rrData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rrData as &$row) { $row['has_rr_record'] = 1; } unset($row);

        // =========================================================
        // STEP 2: Setup parameters to exclude existing RR users
        // =========================================================
        $existingLoanIds = array_column($rrData, 'loan_id');
        $inQuery = "";
        $missingParams = [];
        
        if (!empty($existingLoanIds)) {
            $placeholders = str_repeat('?,', count($existingLoanIds) - 1) . '?';
            $inQuery = " AND l.loan_id NOT IN ($placeholders) ";
            $missingParams = $existingLoanIds;
        }

        // =========================================================
        // STEP 3: Query "Special Entities" (People missing from RR)
        // =========================================================
        $sqlMissing = "
            SELECT 
                l.loan_id, b.employe_id, CONCAT(b.first_name, ' ', b.last_name) as name, b.region,
                
                -- Strict-mode compliant date fetch
                CASE 
                    WHEN l.date_granted IS NOT NULL AND l.date_granted > '2000-01-01' THEN l.date_granted
                    WHEN l.pn_date IS NOT NULL AND l.pn_date > '2000-01-01' THEN l.pn_date
                    ELSE 'No Date'
                END as loan_granted,

                l.loan_amount, 0 as period_principal, 
                
                COALESCE((SELECT SUM(principal_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid < ?), 0) as prior_payments,
                COALESCE((SELECT SUM(principal_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid <= ?), 0) as accumulated_payments,
                (l.loan_amount - COALESCE((SELECT SUM(principal_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid <= ?), 0)) as outstanding_balance,
                
                0 as period_income, 
                
                -- Determine their historical status dynamically!
                CASE 
                    WHEN l.date_completed BETWEEN '$periodStart' AND '$cutoffDate' THEN 'FULLY PAID'
                    ELSE 'ONGOING'
                END as loan_status

            FROM Loan l
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE 1=1 
            $statusCondition
            
            -- FIX: Time-travel prevention that DOES NOT crash MySQL Strict Mode
            AND COALESCE(l.date_granted, l.pn_date) <= ?
            
            $inQuery
        ";

        // Bind exactly 4 dates (3 for the ledgers, 1 for the WHERE) + the excluded IDs
        $finalMissingParams = array_merge([$periodStart, $cutoffDate, $cutoffDate, $cutoffDate], $missingParams);
        $stmtMissing = $this->db->prepare($sqlMissing);
        $stmtMissing->execute($finalMissingParams);
        $missingData = $stmtMissing->fetchAll(PDO::FETCH_ASSOC);

        foreach ($missingData as &$row) { $row['has_rr_record'] = 0; } unset($row);

        // =========================================================
        // STEP 4: Merge & Sort
        // =========================================================
        $finalData = array_merge($rrData, $missingData);

        usort($finalData, function($a, $b) {
            $aPaid = $a['period_principal'] > 0 ? 1 : 0;
            $bPaid = $b['period_principal'] > 0 ? 1 : 0;
            if ($aPaid !== $bPaid) return $bPaid - $aPaid; 
            if ($a['has_rr_record'] !== $b['has_rr_record']) return $b['has_rr_record'] - $a['has_rr_record'];
            return strcmp($a['name'], $b['name']); 
        });

        return $finalData;
    }

}