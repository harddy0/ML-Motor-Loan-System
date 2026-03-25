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

    // ============================================================
    // generateSnapshot()
    //
    // Called during payroll import — writes one pre-computed row
    // per loan per period into Running_AR_Summary.
    //
    // FIX: Use scheduled_date (not date_paid) so the snapshot and
    // getReportData() measure the same thing.  Both now say:
    // "principal collected for installments DUE on or before cutoff".
    // ============================================================
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

        // --- 1. Loan + Borrower details (single row lookup) ---
        $stmt = $this->db->prepare("
            SELECT l.loan_amount,
                   COALESCE(l.date_granted, l.pn_date) AS effective_date,
                   l.current_status,
                   b.region,
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

        // --- 2. Single aggregate query — period + prior in one pass ---
        //
        // Uses scheduled_date (not date_paid).
        // CASE splits paid rows into "this period" vs "before this period"
        // so we only hit Amortization_Ledger once per snapshot write.
        //
        // Required index (add if not already present):
        //   ALTER TABLE Amortization_Ledger
        //     ADD INDEX idx_al_snapshot (loan_id, status, scheduled_date);
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(CASE WHEN scheduled_date BETWEEN :period_start AND :cutoff
                                THEN principal_amt END), 0) AS period_principal,

                IFNULL(SUM(CASE WHEN scheduled_date BETWEEN :period_start2 AND :cutoff2
                                THEN interest_amt END), 0)  AS period_income,

                IFNULL(SUM(CASE WHEN scheduled_date < :before_period
                                THEN principal_amt END), 0) AS prior_principal
            FROM Amortization_Ledger
            WHERE loan_id = :loan_id
              AND status  = 'PAID'
              AND scheduled_date <= :max_cutoff
        ");
        $stmt->execute([
            ':loan_id'       => $loanId,
            ':period_start'  => $periodStart,
            ':cutoff'        => $cutoffDate,
            ':period_start2' => $periodStart,   // PDO requires unique keys — rebind same value
            ':cutoff2'       => $cutoffDate,
            ':before_period' => $periodStart,
            ':max_cutoff'    => $cutoffDate,
        ]);
        $agg = $stmt->fetch(PDO::FETCH_ASSOC);

        $periodPrincipal     = (float) $agg['period_principal'];
        $periodIncome        = (float) $agg['period_income'];
        $priorPayments       = (float) $agg['prior_principal'];
        $accumulatedPayments = $priorPayments + $periodPrincipal;
        $outstandingBalance  = (float) $loanInfo['loan_amount'] - $accumulatedPayments;

        // --- 3. Upsert snapshot ---
        $stmt = $this->db->prepare("
            INSERT INTO Running_AR_Summary
                (loan_id, reporting_period, period_half, cutoff_date, loan_granted,
                 region, dealer, loan_amount,
                 period_principal, prior_payments, accumulated_payments,
                 outstanding_balance, period_income, loan_status)
            VALUES
                (:loan_id, :reporting_period, :period_half, :cutoff_date, :loan_granted,
                 :region, :dealer, :loan_amount,
                 :period_principal, :prior_payments, :accumulated_payments,
                 :outstanding_balance, :period_income, :loan_status)
            ON DUPLICATE KEY UPDATE
                loan_granted         = VALUES(loan_granted),
                region               = VALUES(region),
                dealer               = VALUES(dealer),
                loan_amount          = VALUES(loan_amount),
                period_principal     = VALUES(period_principal),
                prior_payments       = VALUES(prior_payments),
                accumulated_payments = VALUES(accumulated_payments),
                outstanding_balance  = VALUES(outstanding_balance),
                period_income        = VALUES(period_income),
                loan_status          = VALUES(loan_status),
                generated_at         = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':loan_id'              => $loanId,
            ':reporting_period'     => $reportingPeriod,
            ':period_half'          => $periodHalf,
            ':cutoff_date'          => $cutoffDate,
            ':loan_granted'         => $loanInfo['effective_date'] ?: date('Y-m-d'),
            ':region'               => $loanInfo['region'],
            ':dealer'               => $loanInfo['dealer'],
            ':loan_amount'          => $loanInfo['loan_amount'],
            ':period_principal'     => $periodPrincipal,
            ':prior_payments'       => $priorPayments,
            ':accumulated_payments' => $accumulatedPayments,
            ':outstanding_balance'  => $outstandingBalance,
            ':period_income'        => $periodIncome,
            ':loan_status'          => $loanInfo['current_status'],
        ]);
    }


    // ============================================================
    // getReportData()
    //
    // Replaces the old 2-query + PHP-merge approach with a single
    // query.  Zero correlated subqueries.  Zero N+1.
    //
    // Strategy
    // ─────────
    // 1. Drive from Loan (all loans, not just the ones that have a
    //    Running_AR_Summary row).
    // 2. LEFT JOIN Running_AR_Summary — tells us has_rr_record.
    // 3. LEFT JOIN a pre-aggregated derived table (al_agg) built from
    //    Amortization_Ledger — ONE scan of the ledger table, grouped
    //    by loan_id, done before the outer join.  No per-row subquery.
    // 4. ORDER BY inside MySQL — no PHP usort().
    // 5. All dynamic values are named bound parameters — no string
    //    interpolation, query plan cache can reuse the compiled plan.
    //
    // Output columns (identical to old code — callers unchanged):
    //   loan_id, employe_id, name, region_division, loan_granted,
    //   term_months, loan_amount, interest_amount, gross_amount,
    //   principal_paid, interest_paid, running_ar_principal,
    //   loan_status, has_rr_record
    // ============================================================
    public function getReportData(
        string  $yearMonth,
        ?string $periodHalf   = null,
        string  $statusFilter = 'ONGOING',
        string  $regionFilter = 'ALL'
    ): array {
        // --- Compute all date boundaries in PHP (clean, testable) ---
        $year  = (int) substr($yearMonth, 0, 4);
        $month = (int) substr($yearMonth, 5, 2);

        if ($periodHalf === '1ST') {
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $cutoffDate  = sprintf('%04d-%02d-15', $year, $month);
        } elseif ($periodHalf === '2ND') {
            $periodStart = sprintf('%04d-%02d-16', $year, $month);
            $cutoffDate  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        } else {
            // Full month
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $cutoffDate  = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        }

        $reportingPeriod = sprintf('%04d-%02d-01', $year, $month);

        // ── Build the WHERE clauses ────────────────────────────────
        //
        // All date values are bound params (:cutoff, :period_start,
        // :period_end).  No string interpolation anywhere.
        //
        // statusCondition uses l.date_completed (a DATE column) compared
        // directly to bound :cutoff / :period_start — index-safe.
        //
        // VOID exclusion stays as a literal string because 'VOIDED' never
        // changes and keeps the query string stable for plan caching.

        $whereClauses = ["l.current_status != 'VOIDED'"];
        $params       = [];

        // Status / time-travel filter
        if ($statusFilter === 'ONGOING') {
            $whereClauses[] = "(l.date_completed IS NULL OR l.date_completed > :cutoff_status)";
            $params[':cutoff_status'] = $cutoffDate;
        } elseif ($statusFilter === 'FULLY_PAID') {
            $whereClauses[] = "l.date_completed BETWEEN :period_start_status AND :cutoff_status";
            $params[':period_start_status'] = $periodStart;
            $params[':cutoff_status']       = $cutoffDate;
        } else {
            // ALL — exclude loans that hadn't started yet by this period
            $whereClauses[] = "(l.date_completed IS NULL OR l.date_completed >= :period_start_status)";
            $params[':period_start_status'] = $periodStart;
        }

        // FIX: sargable date-of-grant filter — no COALESCE() wrapping the column.
        // The old COALESCE(l.date_granted, l.pn_date) <= ? killed the index.
        // This form lets MySQL use idx on date_granted for the common case,
        // and only falls through to pn_date for the rare NULL date_granted rows.
        $whereClauses[] = "(
            l.date_granted <= :cutoff_grant
            OR (l.date_granted IS NULL AND l.pn_date <= :cutoff_pn)
        )";
        $params[':cutoff_grant'] = $cutoffDate;
        $params[':cutoff_pn']    = $cutoffDate;

        // Optional region filter
        if ($regionFilter !== 'ALL') {
            $whereClauses[] = "b.region = :region";
            $params[':region'] = $regionFilter;
        }

        // Optional half filter on the snapshot join
        $halfJoinCondition = "";
        if ($periodHalf === '1ST' || $periodHalf === '2ND') {
            $halfJoinCondition        = "AND r.period_half = :period_half";
            $params[':period_half']   = $periodHalf;
        }

        $whereSQL = implode("\n              AND ", $whereClauses);

        // ── Bind the aggregation cutoff (used by the derived table) ─
        $params[':al_cutoff']       = $cutoffDate;
        $params[':reporting_period'] = $reportingPeriod;
        $params[':period_start_ls'] = $periodStart;   // for loan_status CASE
        $params[':cutoff_ls']       = $cutoffDate;    // for loan_status CASE

        // ── THE SINGLE QUERY ────────────────────────────────────────
        //
        // al_agg  — derived table: ONE Amortization_Ledger scan,
        //           grouped by loan_id before the outer join.
        //           Covered by index: (loan_id, status, scheduled_date).
        //           Returns principal_paid + interest_paid per loan.
        //           No correlated subquery.  No per-row re-scan.
        //
        // r       — LEFT JOIN Running_AR_Summary so every Loan row
        //           appears regardless of whether a snapshot exists.
        //           CASE WHEN r.loan_id IS NULL → has_rr_record = 0.
        //           Replaces the old PHP NOT IN round-trip entirely.
        //
        // region_division — NULL-safe: COALESCE handles NULL region
        //           before the IN check, so NULL never silently becomes
        //           the ELSE branch with a wrong value.
        //
        // loan_granted — the CASE in SELECT uses the raw columns (not
        //           wrapped in COALESCE) for the date-boundary check;
        //           COALESCE is only used for the display value, which
        //           is fine in SELECT (no index needed there).
        //
        // ORDER BY — lives in MySQL, uses the same expression as the
        //           date_granted display column.  No PHP usort().

        $sql = "
            SELECT
                l.loan_id,
                b.employe_id,
                CONCAT(b.first_name, ' ', b.last_name)                    AS name,

                -- NULL-safe region display
                CASE
                    WHEN COALESCE(b.region, '') IN ('', 'N/A') THEN b.division
                    ELSE b.region
                END                                                        AS region_division,

                -- Date display (COALESCE in SELECT is fine — not in WHERE)
                CASE
                    WHEN l.date_granted IS NOT NULL
                         AND l.date_granted > '2000-01-01' THEN l.date_granted
                    WHEN l.pn_date IS NOT NULL
                         AND l.pn_date > '2000-01-01'      THEN l.pn_date
                    ELSE 'No Date'
                END                                                        AS loan_granted,

                l.term_months,
                l.loan_amount,
                l.total_interest_amount                                    AS interest_amount,
                l.gross_loan_amount                                        AS gross_amount,

                -- principal_paid / interest_paid come from the pre-aggregated
                -- derived table (al_agg) — ONE Amortization_Ledger scan total.
                -- COALESCE here is in SELECT, not WHERE — no index impact.
                COALESCE(al_agg.principal_paid, 0)                        AS principal_paid,
                COALESCE(al_agg.interest_paid,  0)                        AS interest_paid,
                l.loan_amount - COALESCE(al_agg.principal_paid, 0)        AS running_ar_principal,

                -- Time-travel-proof status: what was the status AT the cutoff?
                CASE
                    WHEN l.date_completed BETWEEN :period_start_ls AND :cutoff_ls
                    THEN 'FULLY PAID'
                    ELSE 'ONGOING'
                END                                                        AS loan_status,

                -- has_rr_record: 1 = snapshot existed, 0 = fallback live calc
                CASE WHEN r.loan_id IS NULL THEN 0 ELSE 1 END             AS has_rr_record

            FROM Loan l

            -- Every Loan row is included; r columns are NULL if no snapshot
            JOIN Borrowers b
                ON b.employe_id = l.employe_id

            -- LEFT JOIN replaces the old PHP NOT IN round-trip.
            -- If r.loan_id IS NULL → loan has no snapshot → has_rr_record = 0.
            LEFT JOIN Running_AR_Summary r
                ON  r.loan_id          = l.loan_id
                AND r.reporting_period = :reporting_period
                $halfJoinCondition

            -- ──────────────────────────────────────────────────────
            -- al_agg: pre-aggregate Amortization_Ledger ONCE.
            -- This derived table is evaluated before the outer join.
            -- MySQL scans the ledger table exactly one time, groups
            -- by loan_id, and the result is then joined.
            --
            -- Covering index required (add once, keeps forever fast):
            --   ALTER TABLE Amortization_Ledger
            --     ADD INDEX idx_al_rr (loan_id, status, scheduled_date,
            --                          principal_amt, interest_amt);
            --
            -- With that index MySQL never touches the actual row data
            -- (index-only scan).  Without it, it still runs correctly
            -- but does a full-table sequential scan.
            -- ──────────────────────────────────────────────────────
            LEFT JOIN (
                SELECT
                    loan_id, 
                    SUM(principal_amt) AS principal_paid,
                    SUM(interest_amt)  AS interest_paid
                FROM Amortization_Ledger
                WHERE status        = 'PAID'
                  AND scheduled_date <= :al_cutoff
                GROUP BY loan_id
            ) al_agg ON al_agg.loan_id = l.loan_id

            WHERE $whereSQL

            ORDER BY
                -- Sort in MySQL — no PHP usort() needed.
                -- Uses the same raw columns as the date display CASE above.
                CASE
                    WHEN l.date_granted IS NOT NULL AND l.date_granted > '2000-01-01'
                    THEN l.date_granted
                    ELSE l.pn_date
                END ASC,
                b.last_name  ASC,
                b.first_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}