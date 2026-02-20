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
     * Aggregates the 1st and 2nd half snapshots into a "Whole Month" view for reports.
     */
   public function getReportData($yearMonth, $periodHalf = null) {
        $reportingPeriod = $yearMonth . '-01';
        $params = [$reportingPeriod];
        
        // Add filter if a specific half of the month is selected
        $halfFilter = "";
        if ($periodHalf === '1ST' || $periodHalf === '2ND') {
            $halfFilter = " AND r.period_half = ? ";
            $params[] = $periodHalf;
        }

        $stmt = $this->db->prepare("
            SELECT 
                r.loan_id,
                b.employe_id,
                CONCAT(b.first_name, ' ', b.last_name) as name,
                MAX(r.loan_granted) as loan_granted,
                MAX(r.loan_amount) as loan_amount,
                SUM(r.period_principal) as period_principal,
                MIN(r.prior_payments) as prior_payments, 
                MAX(r.accumulated_payments) as accumulated_payments,
                MIN(r.outstanding_balance) as outstanding_balance,
                SUM(r.period_income) as period_income,
                MAX(r.loan_status) as loan_status
            FROM Running_AR_Summary r
            JOIN Loan l ON r.loan_id = l.loan_id
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE r.reporting_period = ?
            $halfFilter
            GROUP BY r.loan_id, b.employe_id, b.first_name, b.last_name
            ORDER BY b.last_name ASC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}