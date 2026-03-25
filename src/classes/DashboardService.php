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
            'month_expected_principal'  => 0,
            'month_expected_interest'   => 0,
            'month_expected_total'      => 0,
            'month_collected_principal' => 0,
            'month_collected_interest'  => 0,
            'month_collected_total'     => 0,
            'outstanding_principal'     => 0,
            'outstanding_interest'      => 0,
            'progress_percent'          => 0,
            'progress_label'            => '₱0.00 collected of ₱0.00 expected',
        ];

        $loanStats = $this->db->query("
            SELECT SUM(loan_amount) as total_loaned
            FROM Loan
            WHERE current_status != 'VOIDED'
        ")->fetch(PDO::FETCH_ASSOC);
        $financials['total_loaned'] = (float)($loanStats['total_loaned'] ?? 0);

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
        $financials['net_outstanding'] = $financials['outstanding_principal'] + $financials['outstanding_interest'];

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

        $currentDay      = (int)date('d');
        $currentHalf     = $currentDay <= 15 ? '1ST' : '2ND';
        $cutoffDateStart = $currentDay <= 15 ? date('Y-m-01') : date('Y-m-16');
        $cutoffDateEnd   = $currentDay <= 15 ? date('Y-m-15') : date('Y-m-t');

        $countsStmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(DISTINCT employe_id)
                 FROM Loan WHERE current_status = 'ONGOING'
                ) as active_borrowers,
                (SELECT COUNT(*)
                 FROM Loan WHERE current_status = 'FULLY PAID'
                ) as fully_paid
            FROM DUAL
        ");
        $countsStmt->execute();
        $counts = $countsStmt->fetch(PDO::FETCH_ASSOC);

        $cutoffLabel = $currentHalf === '1ST'
            ? 'for 1st Half (1–15)'
            : 'for 2nd Half (16–' . date('t') . ')';

        return [
            'success' => true,
            'metrics' => [
                'active_borrowers' => number_format($counts['active_borrowers'] ?? 0),
                'fully_paid'       => number_format($counts['fully_paid']       ?? 0),
                'cutoff_label'     => $cutoffLabel,
                'cutoff_half'      => $currentHalf,
            ],
            'financials' => $financials,
        ];
    }

    /**
     * Loan progress per borrower — top borrowers closest to finishing.
     *
     * Percentage logic:
     *   - NO DEDUCTION rows are missed-payment extensions — they inflate total_periods
     *     on the Loan table and add extra rows. They must be EXCLUDED entirely.
     *   - Denominator : PAID + UNPAID rows only  (the real contracted schedule)
     *   - Numerator   : PAID rows only
     *   - Remaining   : UNPAID rows only
     *
     * This means a borrower who missed payments does NOT get a falsely high %
     * just because their NO DEDUCTION rows were counted as "done".
     *
     * Sorted: most PAID rows first (closest to finishing at top).
     */
    public function getLoanProgress(string $status = 'ONGOING', ?int $limit = 5): array {
        $normalizedStatus = strtoupper(trim(str_replace('_', ' ', $status)));
        if (!in_array($normalizedStatus, ['ONGOING', 'FULLY PAID', 'ALL'], true)) {
            $normalizedStatus = 'ONGOING';
        }

        $whereClause = $normalizedStatus === 'ALL'
            ? "l.current_status IN ('ONGOING', 'FULLY PAID')"
            : "l.current_status = :status";

        $limitSql = '';
        if ($limit !== null && $limit > 0) {
            $limitSql = ' LIMIT ' . (int)$limit;
        }

        $sql = "
            SELECT
                b.last_name,
                b.first_name,
                b.employe_id,
                l.loan_id,
                l.maturity_date,

                MAX(CASE WHEN al.status = 'PAID' THEN al.scheduled_date END) AS last_paid_due_date,

                ROUND(
                    COALESCE(l.loan_amount, 0)
                    + (
                        COALESCE(l.loan_amount, 0)
                        * COALESCE(l.add_on_rate, 0)
                        * COALESCE(l.term_months, 0)
                    ),
                    2
                ) AS gross_total,

                ROUND(
                    SUM(
                        CASE
                            WHEN al.status = 'PAID' THEN COALESCE(al.total_payment, 0)
                            ELSE 0
                        END
                    ),
                    2
                ) AS payment_total,

                ROUND(
                    (
                        COALESCE(l.loan_amount, 0)
                        + (
                            COALESCE(l.loan_amount, 0)
                            * COALESCE(l.add_on_rate, 0)
                            * COALESCE(l.term_months, 0)
                        )
                    )
                    - SUM(
                        CASE
                            WHEN al.status = 'PAID' THEN COALESCE(al.total_payment, 0)
                            ELSE 0
                        END
                    ),
                    2
                ) AS balance_total,

                -- real contracted schedule (NO DEDUCTION excluded)
                COUNT(CASE WHEN al.status IN ('PAID', 'UNPAID') THEN 1 END)
                    AS total_periods,

                -- actual payments made
                COUNT(CASE WHEN al.status = 'PAID' THEN 1 END)
                    AS completed_periods,

                -- genuine remaining obligations
                COUNT(CASE WHEN al.status = 'UNPAID' THEN 1 END)
                    AS remaining_periods,

                -- true progress %
                ROUND(
                    COUNT(CASE WHEN al.status = 'PAID' THEN 1 END)
                    / NULLIF(COUNT(CASE WHEN al.status IN ('PAID', 'UNPAID') THEN 1 END), 0)
                    * 100
                ) AS pct_done

            FROM Loan l
            JOIN Borrowers b
                ON b.employe_id = l.employe_id
            INNER JOIN Amortization_Ledger al
                ON al.loan_id = l.loan_id
            WHERE $whereClause
            GROUP BY
                l.loan_id,
                b.last_name,
                b.first_name
            ORDER BY
    completed_periods DESC,
    pct_done DESC
" . $limitSql;

        $stmt = $this->db->prepare($sql);
        if ($normalizedStatus !== 'ALL') {
            $stmt->bindValue(':status', $normalizedStatus);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            return [
                'borrower_name'     => strtoupper($r['last_name']) . ', ' . ucfirst(strtolower($r['first_name'])),
                'employe_id'        => (string)($r['employe_id'] ?? ''),
                'loan_id'           => (int) $r['loan_id'],
                'maturity_date'     => $r['maturity_date'] ?? null,
                'last_paid_due_date'=> $r['last_paid_due_date'] ?? null,
                'gross_total'       => (float) $r['gross_total'],
                'payment_total'     => (float) $r['payment_total'],
                'balance_total'     => (float) $r['balance_total'],
                'total_periods'     => (int) $r['total_periods'],
                'completed_periods' => (int) $r['completed_periods'],
                'remaining_periods' => (int) $r['remaining_periods'],
                'pct_done'          => (int) $r['pct_done'],
            ];
        }, $rows);
    }
}