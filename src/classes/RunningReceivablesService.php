<?php
namespace App;

use PDO;
use Exception;

class RunningReceivablesService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function generateSnapshot(int $loanId, string $payrollDate): void
    {
        $ts    = strtotime($payrollDate);
        $year  = (int) date('Y', $ts);
        $month = (int) date('m', $ts);
        $day   = (int) date('d', $ts);

        $reportingPeriod = sprintf('%04d-%02d-01', $year, $month);

        if ($day <= 15) {
            $periodHalf  = '1ST';
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $cutoffDate  = sprintf('%04d-%02d-15', $year, $month);
        } else {
            $periodHalf  = '2ND';
            $periodStart = sprintf('%04d-%02d-16', $year, $month);
            $cutoffDate  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        }

        $stmt = $this->db->prepare("
            SELECT l.loan_amount,
                   COALESCE(l.date_granted, l.pn_date) AS effective_date,
                   l.current_status,
                   b.region_code,
                   b.division AS dealer
            FROM   Loan l
            JOIN   Borrowers b ON b.employe_id = l.employe_id
            WHERE  l.loan_id        = :loan_id
              AND  l.current_status != 'VOIDED'
        ");
        $stmt->execute([':loan_id' => $loanId]);
        $loanInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loanInfo) {
            return;
        }

        // Calculate both period stats and total lifetime stats up to the cutoff
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(CASE WHEN scheduled_date BETWEEN :period_start AND :cutoff
                                THEN principal_amt END), 0) AS period_principal,

                IFNULL(SUM(CASE WHEN scheduled_date BETWEEN :period_start2 AND :cutoff2
                                THEN interest_amt END), 0)  AS period_income,

                IFNULL(SUM(CASE WHEN scheduled_date < :before_period
                                THEN principal_amt END), 0) AS prior_principal,
                                
                IFNULL(SUM(CASE WHEN scheduled_date <= :max_cutoff_int
                                THEN interest_amt END), 0)  AS total_accumulated_interest
            FROM Amortization_Ledger
            WHERE loan_id = :loan_id
              AND status  = 'PAID'
              AND scheduled_date <= :max_cutoff
        ");
        $stmt->execute([
            ':loan_id'        => $loanId,
            ':period_start'   => $periodStart,
            ':cutoff'         => $cutoffDate,
            ':period_start2'  => $periodStart,   
            ':cutoff2'        => $cutoffDate,
            ':before_period'  => $periodStart,
            ':max_cutoff_int' => $cutoffDate,
            ':max_cutoff'     => $cutoffDate,
        ]);
        $agg = $stmt->fetch(PDO::FETCH_ASSOC);

        $periodPrincipal     = (float) $agg['period_principal'];
        $periodIncome        = (float) $agg['period_income'];
        $priorPayments       = (float) $agg['prior_principal'];
        $accumulatedPayments = $priorPayments + $periodPrincipal;
        $outstandingBalance  = (float) $loanInfo['loan_amount'] - $accumulatedPayments;
        
        $accumulatedIncome   = (float) $agg['total_accumulated_interest'];

        $stmt = $this->db->prepare("
            INSERT INTO Running_AR_Summary
                (loan_id, reporting_period, period_half, cutoff_date, loan_granted,
                 region_code, dealer, loan_amount,
                 period_principal, prior_payments, accumulated_payments,
                 outstanding_balance, period_income, accumulated_income, loan_status)
            VALUES
                (:loan_id, :reporting_period, :period_half, :cutoff_date, :loan_granted,
                 :region_code, :dealer, :loan_amount,
                 :period_principal, :prior_payments, :accumulated_payments,
                 :outstanding_balance, :period_income, :accumulated_income, :loan_status)
            ON DUPLICATE KEY UPDATE
                loan_granted         = VALUES(loan_granted),
                region_code          = VALUES(region_code),
                dealer               = VALUES(dealer),
                loan_amount          = VALUES(loan_amount),
                period_principal     = VALUES(period_principal),
                prior_payments       = VALUES(prior_payments),
                accumulated_payments = VALUES(accumulated_payments),
                outstanding_balance  = VALUES(outstanding_balance),
                period_income        = VALUES(period_income),
                accumulated_income   = VALUES(accumulated_income),
                loan_status          = VALUES(loan_status),
                generated_at         = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':loan_id'              => $loanId,
            ':reporting_period'     => $reportingPeriod,
            ':period_half'          => $periodHalf,
            ':cutoff_date'          => $cutoffDate,
            ':loan_granted'         => $loanInfo['effective_date'] ?: date('Y-m-d'),
            ':region_code'          => $loanInfo['region_code'],
            ':dealer'               => $loanInfo['dealer'],
            ':loan_amount'          => $loanInfo['loan_amount'],
            ':period_principal'     => $periodPrincipal,
            ':prior_payments'       => $priorPayments,
            ':accumulated_payments' => $accumulatedPayments,
            ':outstanding_balance'  => $outstandingBalance,
            ':period_income'        => $periodIncome,
            ':accumulated_income'   => $accumulatedIncome,
            ':loan_status'          => $loanInfo['current_status'],
        ]);
    }

    public function getReportData(
        string  $yearMonth,
        ?string $periodHalf   = null,
        string  $statusFilter = 'ONGOING',
        string  $regionFilter = 'ALL'
    ): array {
        $year  = (int) substr($yearMonth, 0, 4);
        $month = (int) substr($yearMonth, 5, 2);

        if ($periodHalf === '1ST') {
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $cutoffDate  = sprintf('%04d-%02d-15', $year, $month);
        } elseif ($periodHalf === '2ND') {
            $periodStart = sprintf('%04d-%02d-16', $year, $month);
            $cutoffDate  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        } else {
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $cutoffDate  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        }

        $reportingPeriod = sprintf('%04d-%02d-01', $year, $month);

        $whereClauses = ["l.current_status != 'VOIDED'"];
        $params       = [];

        if ($statusFilter === 'ONGOING') {
            $whereClauses[] = "(l.date_completed IS NULL OR l.date_completed > :cutoff_status)";
            $params[':cutoff_status'] = $cutoffDate;
        } elseif ($statusFilter === 'FULLY_PAID') {
            $whereClauses[] = "l.date_completed BETWEEN :period_start_status AND :cutoff_status";
            $params[':period_start_status'] = $periodStart;
            $params[':cutoff_status']       = $cutoffDate;
        } else {
            $whereClauses[] = "(l.date_completed IS NULL OR l.date_completed >= :period_start_status)";
            $params[':period_start_status'] = $periodStart;
        }

        $whereClauses[] = "(
            l.date_granted <= :cutoff_grant
            OR (l.date_granted IS NULL AND l.pn_date <= :cutoff_pn)
        )";
        $params[':cutoff_grant'] = $cutoffDate;
        $params[':cutoff_pn']    = $cutoffDate;

        if ($regionFilter !== 'ALL' && trim($regionFilter) !== '') {
            $whereClauses[] = "(b.region_code = :region OR UPPER(b.region_code) = UPPER(:region_fallback))";
            $params[':region'] = trim($regionFilter);
            $params[':region_fallback'] = trim($regionFilter);
        }

        $halfJoinCondition = "";
        if ($periodHalf === '1ST' || $periodHalf === '2ND') {
            $halfJoinCondition        = "AND r.period_half = :period_half";
            $params[':period_half']   = $periodHalf;
        }

        $whereSQL = implode("\n             AND ", $whereClauses);

        $params[':reporting_period'] = $reportingPeriod;
        $params[':period_start_ls']  = $periodStart;
        $params[':cutoff_ls']        = $cutoffDate;

        $sql = "
            SELECT
                l.loan_id,
                b.employe_id,
                CONCAT(b.first_name, ' ', b.last_name) AS name,
                CASE WHEN COALESCE(b.region_code, '') IN ('', 'N/A') THEN b.division ELSE b.region_code END AS region_division,
                CASE WHEN l.date_granted IS NOT NULL AND l.date_granted > '2000-01-01' THEN l.date_granted WHEN l.pn_date IS NOT NULL AND l.pn_date > '2000-01-01' THEN l.pn_date ELSE 'No Date' END AS loan_granted,
                
                l.term_months,
                l.loan_amount,
                l.total_interest_amount AS interest_amount,
                l.gross_loan_amount     AS gross_amount,

                r.accumulated_payments  AS rr_principal_paid,
                r.outstanding_balance   AS rr_running_ar_principal,
                r.accumulated_income    AS rr_interest_paid,

                CASE WHEN l.date_completed BETWEEN :period_start_ls AND :cutoff_ls THEN 'FULLY PAID' ELSE 'ONGOING' END AS loan_status,
                CASE WHEN r.loan_id IS NULL THEN 0 ELSE 1 END AS has_rr_record

            FROM Loan l
            JOIN Borrowers b ON b.employe_id = l.employe_id
            
            LEFT JOIN Running_AR_Summary r
                ON  r.loan_id          = l.loan_id
                AND r.reporting_period = :reporting_period
                $halfJoinCondition

            WHERE $whereSQL
            ORDER BY
                CASE WHEN l.date_granted IS NOT NULL AND l.date_granted > '2000-01-01' THEN l.date_granted ELSE l.pn_date END ASC,
                b.last_name  ASC,
                b.first_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $missingLoanIds = [];
        foreach ($loans as $loan) {
            if ($loan['has_rr_record'] == 0) {
                $missingLoanIds[] = $loan['loan_id'];
            }
        }

        $liveAggregations = [];
        if (!empty($missingLoanIds)) {
            $placeholders = implode(',', array_fill(0, count($missingLoanIds), '?'));
            $aggSql = "
                SELECT 
                    loan_id,
                    SUM(principal_amt) AS principal_paid,
                    SUM(interest_amt)  AS interest_paid
                FROM Amortization_Ledger
                WHERE status = 'PAID' AND scheduled_date <= ? AND loan_id IN ($placeholders)
                GROUP BY loan_id
            ";
            $aggStmt = $this->db->prepare($aggSql);
            $aggParams = array_merge([$cutoffDate], $missingLoanIds);
            $aggStmt->execute($aggParams);
            
            while ($row = $aggStmt->fetch(PDO::FETCH_ASSOC)) {
                $liveAggregations[$row['loan_id']] = $row;
            }
        }

        foreach ($loans as &$loan) {
            if ($loan['has_rr_record'] == 1) {
                $loan['principal_paid']       = (float)$loan['rr_principal_paid'];
                $loan['running_ar_principal'] = (float)$loan['rr_running_ar_principal'];
                $loan['interest_paid']        = (float)$loan['rr_interest_paid'];
            } else {
                $liveData = $liveAggregations[$loan['loan_id']] ?? ['principal_paid' => 0, 'interest_paid' => 0];
                
                $loan['principal_paid']       = (float)$liveData['principal_paid'];
                $loan['running_ar_principal'] = (float)$loan['loan_amount'] - (float)$liveData['principal_paid'];
                $loan['interest_paid']        = (float)$liveData['interest_paid'];
            }
            
            unset($loan['rr_principal_paid'], $loan['rr_running_ar_principal'], $loan['rr_interest_paid']);
        }
        
        return $loans;
    }
}