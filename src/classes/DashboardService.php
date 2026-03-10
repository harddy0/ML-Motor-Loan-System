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
            'total_loaned' => 0,
            'total_collected' => 0,
            'total_income' => 0,
            'net_outstanding' => 0,
            'progress_percent' => 0
        ];

        // 1. Total Loaned (Sum of principal for all non-voided loans — all-time)
        $loanStats = $this->db->query("
            SELECT SUM(loan_amount) as total_loaned 
            FROM Loan 
            WHERE current_status != 'VOIDED'
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_loaned'] = (float)($loanStats['total_loaned'] ?? 0);

        // 2a. Total Collected All-time & Interest Income (Sum of PAID ledgers for non-voided loans)
        $paidAllStats = $this->db->query("
            SELECT 
                SUM(total_payment) as total_collected_all_time,
                SUM(interest_amt) as total_income_all_time
            FROM Amortization_Ledger 
            WHERE status = 'PAID' 
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
        ")->fetch(PDO::FETCH_ASSOC);
        $totalCollectedAllTime = (float)($paidAllStats['total_collected_all_time'] ?? 0);
        $financials['total_income'] = (float)($paidAllStats['total_income_all_time'] ?? 0);

        // 2b. Total Collected (This month) — used for the "Payments (This month)" card
                $thisMonthStats = $this->db->query("
                        SELECT SUM(total_payment) as total_collected_this_month
                        FROM Amortization_Ledger
                        WHERE status = 'PAID'
                            AND MONTH(date_paid) = MONTH(CURRENT_DATE())
                            AND YEAR(date_paid) = YEAR(CURRENT_DATE())
                            AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
                ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_collected'] = (float)($thisMonthStats['total_collected_this_month'] ?? 0);

        // 3. Net Outstanding (Sum of unpaid principal for all non-voided loans)
        $unpaidStats = $this->db->query("
            SELECT SUM(principal_amt) as net_outstanding 
            FROM Amortization_Ledger 
            WHERE status = 'UNPAID' 
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['net_outstanding'] = (float)($unpaidStats['net_outstanding'] ?? 0);

        // 4. Calculate True Collection Progress (use all-time collected vs all-time loaned)
        if ($financials['total_loaned'] > 0) {
            $financials['progress_percent'] = round(($totalCollectedAllTime / $financials['total_loaned']) * 100, 1);
            if ($financials['progress_percent'] > 100) {
                $financials['progress_percent'] = 100;
            }
        } elseif ($totalCollectedAllTime > 0) {
            // If there is collected money but total_loaned is zero (edge-case), show 100%
            $financials['progress_percent'] = 100;
        }

        // 5. Fetch Count Metrics (Fixed 'PENDING' typo to match schema 'UNPAID')
        $counts = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM Payroll_deductions) as units_processed,
                (SELECT COUNT(DISTINCT employe_id) FROM Loan WHERE current_status = 'ONGOING') as active_borrowers,
                (SELECT COUNT(*) FROM Loan WHERE current_status = 'FULLY PAID') as fully_paid
            FROM DUAL
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'metrics' => [
                'units_processed' => number_format($counts['units_processed'] ?? 0),
                'active_borrowers' => number_format($counts['active_borrowers'] ?? 0),
                'fully_paid' => number_format($counts['fully_paid'] ?? 0)
            ],
            'financials' => $financials
        ];
    }
}