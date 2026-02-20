<?php
namespace App;

use PDO;

class DashboardService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Aggregates live stats and latest snapshot financials for the dashboard.
     */
    public function getDashboardStats() {
        // 1. Identify the latest snapshot date in the system
        $latest = $this->db->query("SELECT MAX(cutoff_date) as latest FROM Running_AR_Summary")->fetch(PDO::FETCH_ASSOC);
        $latestCutoff = $latest['latest'] ?? null;

        // 2. Aggregate Financials from the latest snapshot (Official AR Summary)
        // This ensures Interest Income and Net Outstanding match the RR Reports.
        $financials = [
            'total_loaned' => 0,
            'total_collected' => 0,
            'total_income' => 0,
            'net_outstanding' => 0,
            'progress_percent' => 0
        ];

        if ($latestCutoff) {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(loan_amount) as total_loaned,
                    SUM(accumulated_payments) as total_collected,
                    SUM(period_income) as total_income,
                    SUM(outstanding_balance) as net_outstanding
                FROM Running_AR_Summary 
                WHERE cutoff_date = ?
            ");
            $stmt->execute([$latestCutoff]);
            $snap = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($snap) {
                $financials['total_loaned'] = (float)$snap['total_loaned'];
                $financials['total_collected'] = (float)$snap['total_collected'];
                $financials['total_income'] = (float)$snap['total_income'];
                $financials['net_outstanding'] = (float)$snap['net_outstanding'];
                
                if ($financials['total_loaned'] > 0) {
                    $financials['progress_percent'] = round(($financials['total_collected'] / $financials['total_loaned']) * 100, 1);
                }
            }
        }

        // 3. Fetch Count Metrics
        // Fixed: Active Ledgers now groups by loan_id to count active accounts, not rows.
        $counts = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM Payroll_deductions) as units_processed,
                (SELECT COUNT(DISTINCT loan_id) FROM Amortization_Ledger WHERE status = 'PENDING') as active_ledgers,
                (SELECT COUNT(DISTINCT employe_id) FROM Loan WHERE current_status = 'ONGOING') as active_borrowers,
                (SELECT COUNT(*) FROM Loan WHERE current_status = 'FULLY PAID') as fully_paid
            FROM DUAL
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'metrics' => [
                'units_processed' => number_format($counts['units_processed'] ?? 0),
                'active_ledgers' => number_format($counts['active_ledgers'] ?? 0),
                'active_borrowers' => number_format($counts['active_borrowers'] ?? 0),
                'fully_paid' => number_format($counts['fully_paid'] ?? 0)
            ],
            'financials' => $financials,
            'latest_cutoff' => $latestCutoff ? date('M d, Y', strtotime($latestCutoff)) : 'N/A'
        ];
    }
}