<?php
namespace App;

use PDO;

class DashboardService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Aggregates live stats directly from core tables for real-time dashboard viewing.
     */
    public function getDashboardStats() {
        $financials = [
            'total_loaned'              => 0,
            'total_collected'           => 0,
            'total_income'              => 0,
            'net_outstanding'           => 0,

            // Monthly expected (scheduled this month)
            'month_expected_principal'  => 0,
            'month_expected_interest'   => 0,
            'month_expected_total'      => 0,

            // Monthly collected (paid this month)
            'month_collected_principal' => 0,
            'month_collected_interest'  => 0,
            'month_collected_total'     => 0,

            // Outstanding split
            'outstanding_principal'     => 0,
            'outstanding_interest'      => 0,

            // Progress (this month's collected vs this month's expected)
            'progress_percent'          => 0,
            'progress_label'            => '₱0.00 collected of ₱0.00 expected',
        ];

        // 1. Total Loaned all-time (kept for reference / future use)
        $loanStats = $this->db->query("
            SELECT SUM(loan_amount) as total_loaned
            FROM Loan
            WHERE current_status != 'VOIDED'
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_loaned'] = (float)($loanStats['total_loaned'] ?? 0);

        // 2. All-time total collected & interest income
        $paidStats = $this->db->query("
            SELECT
                SUM(total_payment) as total_collected,
                SUM(interest_amt)  as total_income
            FROM Amortization_Ledger
            WHERE status = 'PAID'
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_collected'] = (float)($paidStats['total_collected'] ?? 0);
        $financials['total_income']    = (float)($paidStats['total_income']    ?? 0);

        // 3. This month's EXPECTED (all scheduled rows — PAID or UNPAID — due this month)
        //    Includes PAID rows too so the denominator is the full month's obligation.
        $expectedStats = $this->db->query("
            SELECT
                IFNULL(SUM(principal_amt), 0) as exp_principal,
                IFNULL(SUM(interest_amt),  0) as exp_interest,
                IFNULL(SUM(total_payment), 0) as exp_total
            FROM Amortization_Ledger
            WHERE status IN ('PAID', 'UNPAID')
              AND YEAR(scheduled_date)  = YEAR(CURDATE())
              AND MONTH(scheduled_date) = MONTH(CURDATE())
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['month_expected_principal'] = (float)($expectedStats['exp_principal'] ?? 0);
        $financials['month_expected_interest']  = (float)($expectedStats['exp_interest']  ?? 0);
        $financials['month_expected_total']     = (float)($expectedStats['exp_total']     ?? 0);

        // 4. This month's COLLECTED (date_paid falls within this month)
        $collectedStats = $this->db->query("
            SELECT
                IFNULL(SUM(principal_amt), 0) as col_principal,
                IFNULL(SUM(interest_amt),  0) as col_interest,
                IFNULL(SUM(total_payment), 0) as col_total
            FROM Amortization_Ledger
            WHERE status = 'PAID'
              AND YEAR(date_paid)  = YEAR(CURDATE())
              AND MONTH(date_paid) = MONTH(CURDATE())
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['month_collected_principal'] = (float)($collectedStats['col_principal'] ?? 0);
        $financials['month_collected_interest']  = (float)($collectedStats['col_interest']  ?? 0);
        $financials['month_collected_total']     = (float)($collectedStats['col_total']      ?? 0);

        // 5. Outstanding balance split — unpaid principal + interest remaining
        //    Restricted to ONGOING loans only (excludes FULLY PAID, DEFAULTED, VOIDED)
        $unpaidStats = $this->db->query("
            SELECT
                IFNULL(SUM(principal_amt), 0) as outstanding_principal,
                IFNULL(SUM(interest_amt),  0) as outstanding_interest
            FROM Amortization_Ledger
            WHERE status = 'UNPAID'
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status = 'ONGOING')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['outstanding_principal'] = (float)($unpaidStats['outstanding_principal'] ?? 0);
        $financials['outstanding_interest']  = (float)($unpaidStats['outstanding_interest']  ?? 0);
        // Keep the combined key so existing JS references don't break
        $financials['net_outstanding'] = $financials['outstanding_principal'] + $financials['outstanding_interest'];

        // 6. Monthly collection progress (this month collected vs this month expected)
        $monthExpected  = $financials['month_expected_total'];
        $monthCollected = $financials['month_collected_total'];
        if ($monthExpected > 0) {
            $pct = round(($monthCollected / $monthExpected) * 100, 1);
            $financials['progress_percent'] = min($pct, 100);
        } elseif ($monthCollected > 0) {
            $financials['progress_percent'] = 100;
        }

        $fmtPeso = fn($v) => '₱' . number_format($v, 2);
        $financials['progress_label'] = $fmtPeso($monthCollected) . ' collected of ' . $fmtPeso($monthExpected) . ' expected';

        // 7. Count metrics
        $counts = $this->db->query("
            SELECT
                (SELECT COUNT(*)
                 FROM Amortization_Ledger
                 WHERE status = 'UNPAID'
                   AND YEAR(scheduled_date)  = YEAR(CURDATE())
                   AND MONTH(scheduled_date) = MONTH(CURDATE())
                   AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status = 'ONGOING')
                ) as due_this_month,
                (SELECT COUNT(DISTINCT employe_id)
                 FROM Loan WHERE current_status = 'ONGOING'
                ) as active_borrowers,
                (SELECT COUNT(*)
                 FROM Loan WHERE current_status = 'FULLY PAID'
                ) as fully_paid
            FROM DUAL
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'metrics' => [
                'due_this_month'   => number_format($counts['due_this_month']   ?? 0),
                'active_borrowers' => number_format($counts['active_borrowers'] ?? 0),
                'fully_paid'       => number_format($counts['fully_paid']       ?? 0),
            ],
            'financials' => $financials,
        ];
    }
}