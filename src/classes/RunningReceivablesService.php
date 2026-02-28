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
     * PREVENTS STATUS TIME-TRAVEL AND STRICT-MODE CRASHES.
     * EXCLUDES VOIDED RECORDS ENTIRELY.
     */
    public function getReportData($yearMonth, $periodHalf = null, $statusFilter = 'ONGOING', $regionFilter = 'ALL') {
        $reportingPeriod = $yearMonth . '-01';
        $params = [$reportingPeriod];
        
        $halfFilter = "";
        if ($periodHalf === '1ST' || $periodHalf === '2ND') {
            $halfFilter = " AND r.period_half = ? ";
            $params[] = $periodHalf;
        }

        $regionCondition = "";
        if ($regionFilter !== 'ALL') {
            $regionCondition = " AND b.region = ? ";
            $params[] = $regionFilter;
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
        // TIME-TRAVEL PROOF STATUS FILTER + VOID EXCLUSION
        // =========================================================
        $statusCondition = "";
        if ($statusFilter === 'ONGOING') {
            $statusCondition = " AND (l.date_completed IS NULL OR l.date_completed > '$cutoffDate') ";
        } elseif ($statusFilter === 'FULLY_PAID') {
            $statusCondition = " AND l.date_completed BETWEEN '$periodStart' AND '$cutoffDate' ";
        } else {
            $statusCondition = " AND (l.date_completed IS NULL OR l.date_completed >= '$periodStart') ";
        }
        $statusCondition .= " AND l.current_status != 'VOIDED' ";

        // =========================================================
        // STEP 1: Get RR Data
        // =========================================================
        $stmt = $this->db->prepare("
            SELECT 
                r.loan_id, b.employe_id, CONCAT(b.first_name, ' ', b.last_name) as name, 
                CASE WHEN b.region = 'N/A' OR b.region = '' THEN b.division ELSE b.region END as region_division,
                MAX(r.loan_granted) as loan_granted, 
                MAX(l.term_months) as term_months,
                MAX(r.loan_amount) as loan_amount,
                MAX(l.semi_monthly_amt * l.total_periods - l.loan_amount) as interest_amount,
                MAX(l.semi_monthly_amt * l.total_periods) as gross_amount,
                MAX(r.accumulated_payments) as principal_paid, 
                COALESCE((SELECT SUM(interest_amt) FROM Amortization_Ledger WHERE loan_id = r.loan_id AND status = 'PAID' AND date_paid <= '$cutoffDate'), 0) as interest_paid,
                MIN(r.outstanding_balance) as running_ar_principal,
                CASE WHEN MAX(l.date_completed) BETWEEN '$periodStart' AND '$cutoffDate' THEN 'FULLY PAID' ELSE 'ONGOING' END as loan_status
            FROM Running_AR_Summary r
            JOIN Loan l ON r.loan_id = l.loan_id
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE r.reporting_period = ? 
            $halfFilter
            $statusCondition
            $regionCondition
            GROUP BY r.loan_id, b.employe_id, b.first_name, b.last_name, b.region, b.division
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
                l.loan_id, b.employe_id, CONCAT(b.first_name, ' ', b.last_name) as name, 
                CASE WHEN b.region = 'N/A' OR b.region = '' THEN b.division ELSE b.region END as region_division,
                CASE 
                    WHEN l.date_granted IS NOT NULL AND l.date_granted > '2000-01-01' THEN l.date_granted
                    WHEN l.pn_date IS NOT NULL AND l.pn_date > '2000-01-01' THEN l.pn_date
                    ELSE 'No Date'
                END as loan_granted,
                l.term_months,
                l.loan_amount, 
                (l.semi_monthly_amt * l.total_periods - l.loan_amount) as interest_amount,
                (l.semi_monthly_amt * l.total_periods) as gross_amount,
                COALESCE((SELECT SUM(principal_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid <= ?), 0) as principal_paid,
                COALESCE((SELECT SUM(interest_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid <= ?), 0) as interest_paid,
                (l.loan_amount - COALESCE((SELECT SUM(principal_amt) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID' AND date_paid <= ?), 0)) as running_ar_principal,
                CASE WHEN l.date_completed BETWEEN '$periodStart' AND '$cutoffDate' THEN 'FULLY PAID' ELSE 'ONGOING' END as loan_status
            FROM Loan l
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE 1=1 
            $statusCondition
            AND COALESCE(l.date_granted, l.pn_date) <= ?
            $regionCondition
            $inQuery
        ";

        $finalMissingParams = [$cutoffDate, $cutoffDate, $cutoffDate, $cutoffDate];
        if ($regionFilter !== 'ALL') {
            $finalMissingParams[] = $regionFilter;
        }
        $finalMissingParams = array_merge($finalMissingParams, $missingParams);
        
        $stmtMissing = $this->db->prepare($sqlMissing);
        $stmtMissing->execute($finalMissingParams);
        $missingData = $stmtMissing->fetchAll(PDO::FETCH_ASSOC);

        foreach ($missingData as &$row) { $row['has_rr_record'] = 0; } unset($row);

        // =========================================================
        // STEP 4: Merge & Sort (Ordered by Release Date as per Excel)
        // =========================================================
        $finalData = array_merge($rrData, $missingData);

        usort($finalData, function($a, $b) {
            $dateA = strtotime($a['loan_granted']);
            $dateB = strtotime($b['loan_granted']);
            
            if ($dateA === $dateB) {
                return strcmp($a['name'], $b['name']);
            }
            return $dateA - $dateB;
        });

        return $finalData;
    }
}