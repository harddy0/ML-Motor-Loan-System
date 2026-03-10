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

        // 1. Total Loaned (Sum of principal for all ONGOING loans)
        $loanStats = $this->db->query("
            SELECT SUM(loan_amount) as total_loaned 
            FROM Loan 
            WHERE current_status = 'ONGOING'
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_loaned'] = (float)($loanStats['total_loaned'] ?? 0);

        // 2. All-time Total Collected & Interest Income (Sum of all PAID ledgers)
$paidStats = $this->db->query("
    SELECT 
        SUM(total_payment) as total_collected,
        SUM(interest_amt) as total_income
    FROM Amortization_Ledger 
    WHERE status = 'PAID' 
      AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
")->fetch(PDO::FETCH_ASSOC);
$financials['total_collected'] = (float)($paidStats['total_collected'] ?? 0);
$financials['total_income']    = (float)($paidStats['total_income'] ?? 0);

// 2b. This month's collected payments only (based on date_paid)
$thisMonthStats = $this->db->query("
    SELECT SUM(total_payment) as month_collected
    FROM Amortization_Ledger
    WHERE status = 'PAID'
      AND YEAR(date_paid)  = YEAR(CURDATE())
      AND MONTH(date_paid) = MONTH(CURDATE())
      AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status != 'VOIDED')
")->fetch(PDO::FETCH_ASSOC);
$financials['month_collected'] = (float)($thisMonthStats['month_collected'] ?? 0);

        // 3. Net Outstanding (Sum of principal amounts left to pay for ONGOING loans)
        $unpaidStats = $this->db->query("
            SELECT SUM(principal_amt) as net_outstanding 
            FROM Amortization_Ledger 
            WHERE status = 'UNPAID' 
              AND loan_id IN (SELECT loan_id FROM Loan WHERE current_status = 'ONGOING')
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['net_outstanding'] = (float)($unpaidStats['net_outstanding'] ?? 0);

        // 4. Calculate True Collection Progress
        if ($financials['total_loaned'] > 0) {
            // Compare collected against total expected principal
            $financials['progress_percent'] = round(($financials['total_collected'] / $financials['total_loaned']) * 100, 1);
            
            // Cap at 100% just in case of overpayments 
            if ($financials['progress_percent'] > 100) {
                $financials['progress_percent'] = 100;
            }
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