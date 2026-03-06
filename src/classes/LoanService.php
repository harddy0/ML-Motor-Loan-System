<?php
namespace App;

use PDO;
use Exception;

class LoanService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function generatePreview($principal, $termsInMonths, $dateGranted, $customDeduction = null, $firstDeduction = null, $lastDeduction = null, $pnOffset = 0) {
        $totalPeriods = $termsInMonths * 2; 
        
        if ($customDeduction !== null && floatval($customDeduction) > 0) {
            $deduction = floatval($customDeduction);
        } else {
            $totalInterest = ($principal * 0.015) * $termsInMonths;
            $totalRepayment = $principal + $totalInterest;
            $deduction = $totalRepayment / $totalPeriods;
        }
        
        $periodicRate = $this->getPeriodicRate($principal, $deduction, $totalPeriods);
        $result = $this->buildAmortizationTable($principal, $deduction, $periodicRate, $totalPeriods, $dateGranted, $firstDeduction, $lastDeduction);
        
        $result['pn_number'] = $this->generatePnNumber($pnOffset);
        $result['deduction'] = round($deduction, 2);

        return $result;
    }

    public function saveLoanApplication($data, $schedule) {
    try {
        $this->db->beginTransaction();

        $division = !empty($data['division']) ? strtoupper(trim($data['division'])) : 'N/A';
        $region   = !empty($data['region'])   ? strtoupper(trim($data['region']))   : 'N/A';
        $branch   = !empty($data['branch'])   ? strtoupper(trim($data['branch']))   : 'N/A';

        // --- GUARD: Block if employee already has an ONGOING loan ---
        // New loan is only allowed once the existing loan is FULLY PAID.
        // VOIDED loans do not block — they are treated as cancelled entries.
        $stmtOngoing = $this->db->prepare("
            SELECT COUNT(*) FROM Loan 
            WHERE employe_id = ? AND current_status = 'ONGOING'
        ");
        $stmtOngoing->execute([$data['employe_id']]);
        if ((int)$stmtOngoing->fetchColumn() > 0) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'This employee already has an ONGOING loan. A new loan can only be created once the existing loan is fully paid.'];
        }

        // --- UPSERT BORROWER PROFILE ---
        // ON DUPLICATE KEY UPDATE handles returning borrowers (previously voided).
        $stmtBorrower = $this->db->prepare("
            INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, division, branch, region)
            VALUES (:eid, :fname, :lname, :contact, :division, :branch, :region)
            ON DUPLICATE KEY UPDATE 
                first_name     = VALUES(first_name),
                last_name      = VALUES(last_name),
                contact_number = VALUES(contact_number),
                division       = VALUES(division),
                branch         = VALUES(branch),
                region         = VALUES(region)
        ");

        $stmtBorrower->execute([
            ':eid'      => $data['employe_id'],
            ':fname'    => $data['first_name'],
            ':lname'    => $data['last_name'],
            ':contact'  => $data['contact_number'],
            ':division' => $division,
            ':branch'   => $branch,
            ':region'   => $region
        ]);

        $principal    = floatval($data['loan_amount']);
        $deduction    = floatval($data['deduction']);
        $termsMonths  = intval($data['terms']);
        $totalPeriods = $termsMonths * 2;
        $periodicRate = floatval($schedule['periodic_rate']);
        $annualYield  = $periodicRate * 24;

        $pnNumber        = $this->generatePnNumber();
        $addOnRateToSave = isset($data['add_on_rate_decimal']) ? floatval($data['add_on_rate_decimal']) : 0.015;

        // --- DETERMINE KPTN REQUIREMENTS & DEPOSIT AMOUNT ---
        $requiresKptn = isset($data['requires_kptn']) ? filter_var($data['requires_kptn'], FILTER_VALIDATE_BOOLEAN) : true;
        $entryType    = (isset($data['entry_type']) && $data['entry_type'] === 'BATCH') ? 'BATCH' : 'MANUAL';

        if (!$requiresKptn) {
            $depositAmount = 0.00;
            $kptnToSave    = 'NOT_REQUIRED';
        } else {
            $depositAmount = isset($data['deposit_amount']) ? floatval($data['deposit_amount']) : 2500.00;
            $kptnToSave    = $data['kptn'] ?? null;
        }

        // --- FORCE SCHEDULE GENERATION FOR BATCH (Active Immediately) ---
        if ($entryType === 'BATCH' && empty($schedule['rows'])) {
            $scheduleResult   = $this->buildAmortizationTable($principal, $deduction, $periodicRate, $totalPeriods, $data['loan_granted']);
            $schedule['rows'] = $scheduleResult['schedule'];
        }

        if (!empty($schedule['rows'])) {
            $lastRow          = end($schedule['rows']);
            $trueMaturityDate = $lastRow['date_obj'];
        } else {
            $trueMaturityDate = date('Y-m-d', strtotime($data['pn_maturity']));
        }

        // --- INSERT LOAN RECORD ---
        $stmtLoan = $this->db->prepare("
            INSERT INTO Loan (
                employe_id, uploaded_by_employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months, 
                total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                pn_date, date_granted, maturity_date, current_status,
                entry_type, requires_kptn, deposit_amount, pending_kptn, kptn
            ) VALUES (
                :eid, :uploader_id, :ref, :pn, :amount, :addon, :terms, :periods, 
                :periodic_rate, :annual_yield, :deduction, :granted, 
                :granted, :maturity, 'ONGOING',
                :entry_type, :requires_kptn, :deposit_amount, :pending_kptn, :kptn
            )
        ");

        $stmtLoan->execute([
            ':eid'            => $data['employe_id'],
            ':uploader_id'    => $data['uploaded_by_employe_id'] ?? null,
            ':ref'            => !empty($data['reference_number']) ? $data['reference_number'] : null,
            ':pn'             => $pnNumber,
            ':amount'         => $principal,
            ':addon'          => $addOnRateToSave,
            ':terms'          => $termsMonths,
            ':periods'        => $totalPeriods,
            ':periodic_rate'  => $periodicRate,
            ':annual_yield'   => $annualYield,
            ':deduction'      => $deduction,
            ':granted'        => $data['loan_granted'],
            ':maturity'       => $trueMaturityDate,
            ':entry_type'     => $entryType,
            ':requires_kptn'  => $requiresKptn ? 1 : 0,
            ':deposit_amount' => $depositAmount,
            ':pending_kptn'   => !empty($data['pending_kptn']) ? $data['pending_kptn'] : null,
            ':kptn'           => $kptnToSave
        ]);

        $loanId = $this->db->lastInsertId();

        // --- INSERT AMORTIZATION ---
        if (!empty($schedule['rows'])) {
            $stmtLedger = $this->db->prepare("
                INSERT INTO Amortization_Ledger (
                    loan_id, installment_no, scheduled_date, 
                    principal_amt, interest_amt, total_payment, 
                    remaining_bal, status
                ) VALUES (
                    :lid, :no, :date, :princ, :int, :total, :bal, 'UNPAID'
                )
            ");

            foreach ($schedule['rows'] as $row) {
                $stmtLedger->execute([
                    ':lid'   => $loanId,
                    ':no'    => $row['installment_no'],
                    ':date'  => $row['date_obj'],
                    ':princ' => $row['principal'],
                    ':int'   => $row['interest'],
                    ':total' => $row['total'],
                    ':bal'   => $row['balance']
                ]);
            }
        }

        // --- TRIGGER NOTIFICATION ---
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

        if ($entryType === 'BATCH' && $requiresKptn) {
            $this->notifyPendingKptn($loanId, $data['uploaded_by_employe_id'] ?? null, $fullName, $pnNumber, ['ADMIN', 'REVIEWER']);
        } else {
            $this->notifyUsersOnLoanCreation($loanId, $data['uploaded_by_employe_id'] ?? null, $fullName, $pnNumber, ['ADMIN', 'REVIEWER']);
        }

        $this->db->commit();
        return ['success' => true, 'loan_id' => $loanId];

    } catch (\Exception $e) {
        $this->db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

    private function getPeriodicRate($principal, $payment, $periods) {
        if ($principal <= 0 || $payment <= 0 || $periods <= 0) return 0;
        if (($payment * $periods) <= $principal) return 0;

        $low = 0;
        $high = 1; 
        $guess = 0;

        for ($i = 0; $i < 100; $i++) { 
            $guess = ($low + $high) / 2;
            if ($guess <= 0) { $low = 0.0000001; continue; }

            $testPrincipal = $payment * (1 - pow(1 + $guess, -$periods)) / $guess;

            if (abs($testPrincipal - $principal) < 0.01) break; 
            
            if ($testPrincipal > $principal) {
                $low = $guess;
            } else {
                $high = $guess;
            }
        }
        return $guess;
    }

    private function buildAmortizationTable($principal, $deduction, $rate, $periods, $dateGranted, $firstDeduction = null, $lastDeduction = null) {
        $rows = [];
        $balance = $principal;

        if ($firstDeduction) {
            $currentDate = new \DateTime($firstDeduction);
            $currentDate = $this->capToValidPayrollDay($currentDate);
        } else {
            $currentDate = new \DateTime($dateGranted);
            $day = (int)$currentDate->format('d');
            // Dates 1–15: first payment on the 15th of the same month
            // Dates 16–end: first payment on the 30th of the same month
            //   (or last day of month if month has fewer than 30 days, e.g. Feb)
            if ($day <= 15) {
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
            } else {
                $currentDate = $this->setToEndOfSemiMonth($currentDate);
            }
        }

        $totalInterest = 0;

        for ($i = 1; $i <= $periods; $i++) {
            if ($i == $periods) {
                $principalPart = $balance;
                $interest = round($deduction - $principalPart, 2);
                if ($interest < 0) $interest = 0; 
                $balance = 0; 
                
                if ($lastDeduction) {
                    $currentDate = new \DateTime($lastDeduction);
                    $currentDate = $this->capToValidPayrollDay($currentDate);
                }
            } else {
                $interest = round($balance * $rate, 2);
                $principalPart = round($deduction - $interest, 2);
                $balance = round($balance - $principalPart, 2);
            }

            $totalInterest += $interest;

            $rows[] = [
                'installment_no' => $i,
                'date' => $currentDate->format('M d, Y'),
                'date_obj' => $currentDate->format('Y-m-d'), 
                'principal' => $principalPart,
                'interest' => $interest,
                'total' => $deduction,
                'balance' => $balance
            ];

            $currentDate = $this->getNextSemiMonthlyDate($currentDate);
        }

        $effectiveYield = $rate * 24 * 100;
        $addOnRate = ($totalInterest / $principal) * 100; 
        $addOnRateDecimal = $principal > 0 ? ($totalInterest / $principal) / ($periods / 2) : 0; 
        $lastRow = end($rows);

        return [
            'success' => true,
            'periodic_rate' => $rate, 
            'effective_yield' => number_format($effectiveYield, 2),
            'add_on_rate' => number_format($addOnRate, 2), 
            'add_on_rate_decimal' => $addOnRateDecimal,
            'total_interest' => round($totalInterest, 2),
            'maturity_date' => $lastRow['date'], 
            'schedule' => $rows
        ];
    }

    /**
     * Returns the next semi-monthly payroll date.
     * Schedule is strictly 15th and 30th (or last day of month if month has < 30 days).
     * - 10 or 25 legacy dates are normalised: treat as 15 or 30 respectively.
     * - 15th  → 30th (or last day if < 30)
     * - 30/EOM → 15th of next month
     */
    private function getNextSemiMonthlyDate(\DateTime $date): \DateTime {
        $nextDate = clone $date;
        $day = (int)$nextDate->format('d');

        // Normalise legacy 10/25 cycle to 15/30
        if ($day == 10) $day = 15;
        elseif ($day == 25) $day = 30;

        if ($day == 15) {
            // Next payroll is the 30th of this month (or last day if month has < 30 days)
            $nextDate = $this->setToEndOfSemiMonth($nextDate);
        } else {
            // day == 30 or EOM — next payroll is the 15th of next month
            $nextDate->modify('first day of next month');
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 15);
        }
        return $nextDate;
    }

    /**
     * Sets the date to the 30th of the current month,
     * or the last day of the month if the month has fewer than 30 days (e.g. February).
     */
    private function setToEndOfSemiMonth(\DateTime $date): \DateTime {
        $d = clone $date;
        $year  = (int)$d->format('Y');
        $month = (int)$d->format('m');
        $daysInMonth = (int)(new \DateTime("$year-$month-01"))->format('t');
        $targetDay = min(30, $daysInMonth);
        $d->setDate($year, $month, $targetDay);
        return $d;
    }

    /**
     * Caps a DateTime to a valid semi-monthly payroll day:
     * - Day 10  → 15
     * - Day 25  → 30 (or last day if < 30)
     * - Day 31  → 30 (or last day if < 30)
     * - Otherwise unchanged (15 or 30 pass through as-is)
     */
    private function capToValidPayrollDay(\DateTime $date): \DateTime {
        $d = clone $date;
        $day = (int)$d->format('d');
        if ($day == 10) {
            $d->setDate((int)$d->format('Y'), (int)$d->format('m'), 15);
        } elseif ($day == 25 || $day == 31) {
            $d = $this->setToEndOfSemiMonth($d);
        }
        return $d;
    }

    private function generatePnNumber($offset = 0) {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(loan_id) FROM Loan WHERE YEAR(date_granted) = ?");
        $stmt->execute([$year]);
        
        $count = $stmt->fetchColumn() + 1 + $offset;
        return "PN-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

public function getAllBorrowers($paginate = false, $page = 1, $limit = 50, $search = '', $fromDate = '', $toDate = '', $status = '') {
        $where = "WHERE 1=1";
        $params = [];

        // 1. Build dynamic WHERE clauses
        if (!empty($search)) {
            $where .= " AND (b.employe_id LIKE ? OR CONCAT(b.first_name, ' ', b.last_name) LIKE ?)";
            $searchParam = "%{$search}%";
            array_push($params, $searchParam, $searchParam);
        }
        if (!empty($fromDate)) {
            $where .= " AND l.date_granted >= ?";
            $params[] = $fromDate;
        }
        if (!empty($toDate)) {
            $where .= " AND l.date_granted <= ?";
            $params[] = $toDate;
        }
        if (!empty($status)) {
            $where .= " AND l.current_status = ?";
            $params[] = $status;
        }

        $baseSql = "
            FROM Borrowers b
            JOIN Loan l ON b.employe_id = l.employe_id
            $where
        ";

        $selectCols = "
            b.employe_id as id, 
            l.loan_id, 
            CONCAT(b.first_name, ' ', b.last_name) as name,
            b.first_name,
            b.last_name,
            b.contact_number as contact,
            b.region,
            l.loan_ref_no as reference_no,
            l.pn_number as pn_no,
            DATE_FORMAT(l.date_granted, '%m / %d / %Y') as date,
            l.date_granted as raw_date,
            DATE_FORMAT(l.maturity_date, '%m / %d / %Y') as pn_maturity,
            l.loan_amount,
            l.term_months as terms,
            l.semi_monthly_amt as deduction,
            l.current_status,
            l.requires_kptn,
            (SELECT file_path FROM Loan_Documents WHERE loan_id = l.loan_id ORDER BY document_id DESC LIMIT 1) as file_path,
            (SELECT mime_type FROM Loan_Documents WHERE loan_id = l.loan_id ORDER BY document_id DESC LIMIT 1) as mime_type,
            (SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID') as paid_count
        ";

        // ==========================================
        // LEGACY MODE (No Pagination)
        // ==========================================
        if (!$paginate) {
            $sql = "SELECT " . $selectCols . $baseSql . " ORDER BY l.date_granted DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // ==========================================
        // PAGINATED MODE
        // ==========================================
        $countSql = "SELECT COUNT(*) " . $baseSql;
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();

        // Overall System Total (Unaffected by filters) for the Tab Counter
        $statsSql = "SELECT COUNT(*) as total FROM Loan l JOIN Borrowers b ON l.employe_id = b.employe_id";
        $totalOverall = (int)$this->db->query($statsSql)->fetchColumn();

        $offset = ($page - 1) * $limit;
        $dataSql = "SELECT " . $selectCols . $baseSql . " ORDER BY l.date_granted DESC LIMIT ? OFFSET ?";
        
        $stmtData = $this->db->prepare($dataSql);
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmtData->bindValue($paramIndex++, $param);
        }
        $stmtData->bindValue($paramIndex++, (int)$limit, \PDO::PARAM_INT);
        $stmtData->bindValue($paramIndex++, (int)$offset, \PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'total_overall' => $totalOverall,
            'total_filtered' => $totalFiltered,
            'data' => $stmtData->fetchAll(\PDO::FETCH_ASSOC),
            'total_pages' => max(1, ceil($totalFiltered / $limit)),
            'current_page' => (int)$page
        ];
    }

    public function getNextBorrowerId() {
        $stmt = $this->db->query("SELECT MAX(employe_id) as max_id FROM Borrowers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $maxId = $result['max_id'];
        if ($maxId) { return intval($maxId) + 1; } 
        else { return 20260001; }
    }

    public function getAllLedgerLoans($paginate = false, $page = 1, $limit = 50, $search = '', $fromDate = '', $toDate = '', $status = '') {
        $where = "WHERE 1=1";
        $params = [];

        // 1. Build dynamic WHERE clauses
        if (!empty($search)) {
            $where .= " AND (l.pn_number LIKE ? OR b.employe_id LIKE ? OR CONCAT(b.first_name, ' ', b.last_name) LIKE ?)";
            $searchParam = "%{$search}%";
            array_push($params, $searchParam, $searchParam, $searchParam);
        }
        if (!empty($fromDate)) {
            $where .= " AND l.date_granted >= ?";
            $params[] = $fromDate;
        }
        if (!empty($toDate)) {
            $where .= " AND l.date_granted <= ?";
            $params[] = $toDate;
        }
        if (!empty($status)) {
            $where .= " AND l.current_status = ?";
            $params[] = $status;
        }

        $baseSql = "
            FROM Loan l
            JOIN Borrowers b ON l.employe_id = b.employe_id
            $where
        ";

        // ==========================================
        // LEGACY MODE (No Pagination)
        // Returns the flat array to prevent breaking existing code
        // ==========================================
        if (!$paginate) {
            $sql = "SELECT b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name, b.region, b.branch, b.contact_number, l.loan_id, l.loan_ref_no, l.pn_number, l.date_granted AS g_date, l.maturity_date, l.current_status, l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate, l.deposit_amount, l.requires_kptn " . $baseSql . " ORDER BY l.date_granted DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // ==========================================
        // PAGINATED MODE
        // Returns associative array with metadata
        // ==========================================
        
        // Count for Pagination Math
        $countSql = "SELECT COUNT(*) " . $baseSql;
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();

        // Overall System Stats (Unaffected by filters)
        $statsSql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN current_status = 'ONGOING' THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN current_status = 'FULLY PAID' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN current_status = 'VOIDED' THEN 1 ELSE 0 END) as voided
            FROM Loan
        ";
        $stats = $this->db->query($statsSql)->fetch(\PDO::FETCH_ASSOC);

        // Fetch paginated data
        $offset = ($page - 1) * $limit;
        $dataSql = "SELECT b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name, b.region, b.branch, b.contact_number, l.loan_id, l.loan_ref_no, l.pn_number, l.date_granted AS g_date, l.maturity_date, l.current_status, l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate, l.deposit_amount, l.requires_kptn " . $baseSql . " ORDER BY l.date_granted DESC LIMIT ? OFFSET ?";
        
        $stmtData = $this->db->prepare($dataSql);
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmtData->bindValue($paramIndex++, $param);
        }
        $stmtData->bindValue($paramIndex++, (int)$limit, \PDO::PARAM_INT);
        $stmtData->bindValue($paramIndex++, (int)$offset, \PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'stats' => [
                'total' => (int)$stats['total'],
                'ongoing' => (int)$stats['ongoing'],
                'paid' => (int)$stats['paid'],
                'voided' => (int)$stats['voided']
            ],
            'total_filtered' => $totalFiltered,
            'data' => $stmtData->fetchAll(\PDO::FETCH_ASSOC),
            'total_pages' => max(1, ceil($totalFiltered / $limit)),
            'current_page' => (int)$page
        ];
    }

    public function getLedgerTransactions($loan_id) {
        $sql = "SELECT 
                    installment_no,
                    scheduled_date,
                    principal_amt AS principal,
                    interest_amt AS interest,
                    total_payment AS total,
                    remaining_bal AS balance,
                    status,
                    date_paid,
                    remarks 
                FROM Amortization_Ledger
                WHERE loan_id = :loan_id
                ORDER BY installment_no ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':loan_id' => $loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

public function voidBorrowerLoans($employeId, $userId, $voidReason) {
    try {
        $this->db->beginTransaction();

        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = :id AND current_status = 'ONGOING'");
        $stmt->execute([':id' => $employeId]);
        $loans = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($loans)) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'No active loans found for this borrower to void.'];
        }

        $inQuery = implode(',', array_fill(0, count($loans), '?'));

        // --- GUARD: Block void if any payment has already been collected ---
        $stmtPaidCheck = $this->db->prepare(
            "SELECT COUNT(*) FROM Amortization_Ledger 
             WHERE loan_id IN ($inQuery) AND status = 'PAID'"
        );
        $stmtPaidCheck->execute($loans);
        if ((int)$stmtPaidCheck->fetchColumn() > 0) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Cannot void: This loan already has recorded payments. Voiding is only allowed before any payment has been collected.'];
        }

        // --- VOID LOAN + FREE pn_number and loan_ref_no ---
        // Setting pn_number and loan_ref_no to NULL releases those values so
        // the same loan can be re-entered correctly without duplicate errors.
        // Full audit trail (loan_id, voided_at, voided_by, void_reason) stays intact.
        $stmtLoan = $this->db->prepare("
            UPDATE Loan 
            SET current_status       = 'VOIDED',
                voided_at            = CURRENT_TIMESTAMP,
                voided_by_employe_id = ?,
                void_reason          = ?,
                pn_number            = NULL,
                loan_ref_no          = NULL
            WHERE employe_id = ?
              AND current_status = 'ONGOING'
        ");
        $stmtLoan->execute([$userId, $voidReason, $employeId]);

        $stmtLedger = $this->db->prepare(
            "UPDATE Amortization_Ledger SET status = 'VOIDED' WHERE loan_id IN ($inQuery)"
        );
        $stmtLedger->execute($loans);

        $stmtDeductions = $this->db->prepare(
            "UPDATE Payroll_deductions SET match_status = 'VOIDED' WHERE loan_id IN ($inQuery)"
        );
        $stmtDeductions->execute($loans);

        $stmtAR = $this->db->prepare(
            "UPDATE Running_AR_Summary SET loan_status = 'VOIDED' WHERE loan_id IN ($inQuery)"
        );
        $stmtAR->execute($loans);

        $this->db->commit();
        return ['success' => true];

    } catch (\Exception $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage()];
    }
}
    
    public function getBorrowerByName($firstName, $lastName) {
        $stmt = $this->db->prepare("
            SELECT employe_id 
            FROM Borrowers 
            WHERE TRIM(UPPER(first_name)) = TRIM(UPPER(:fname)) 
              AND TRIM(UPPER(last_name))  = TRIM(UPPER(:lname))
            LIMIT 1
        ");
        $stmt->execute([':fname' => $firstName, ':lname' => $lastName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['employe_id'] : null;
    }

    public function isBorrowerExists($firstName, $lastName) {
    // Only block if the employee has an ONGOING loan.
    // Voided or fully paid — they are free to get a new loan.
    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM Borrowers b
        JOIN Loan l ON b.employe_id = l.employe_id
        WHERE TRIM(UPPER(b.first_name)) = TRIM(UPPER(:fname))
          AND TRIM(UPPER(b.last_name))  = TRIM(UPPER(:lname))
          AND l.current_status = 'ONGOING'
    ");
    $stmt->execute([':fname' => $firstName, ':lname' => $lastName]);
    return (int)$stmt->fetchColumn() > 0;
}

    private function notifyUsersOnLoanCreation($loanId, $triggeredByEmployeId, $borrowerName, $pnNumber, $targetRoles = ['ADMIN']) {
        if (empty($targetRoles)) return;

        $placeholders = implode(',', array_fill(0, count($targetRoles), '?'));
        $sql = "SELECT employe_id FROM Users WHERE user_type IN ($placeholders) AND status = 'ACTIVE'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($targetRoles);
        $recipients = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($recipients)) return; 

        $message = strtoupper("New Loan ($pnNumber) uploaded for borrower $borrowerName.");

        $insertStmt = $this->db->prepare("
            INSERT INTO Notifications (recipient_employe_id, triggered_by_employe_id, loan_id, type, message)
            VALUES (?, ?, ?, 'LOAN_ADDED', ?)
        ");

        $cleanTriggeredBy = !empty($triggeredByEmployeId) ? $triggeredByEmployeId : null;

        foreach ($recipients as $recipientId) {
            $insertStmt->execute([$recipientId, $cleanTriggeredBy, $loanId, $message]);
        }
    }

    public function getPendingKptnLoans() {
       // CHANGED: Added `AND l.requires_kptn = TRUE`
       $sql = "
            SELECT 
                b.employe_id as id, 
                CONCAT(b.first_name, ' ', b.last_name) as name,
                b.region,
                l.loan_id,
                l.pn_number as pn_no,
                DATE_FORMAT(l.date_granted, '%M %d, %Y') as date,
                l.date_granted as raw_date, 
                l.loan_amount,
                l.term_months as terms,
                l.semi_monthly_amt as deduction,
                l.pending_kptn,
                l.deposit_amount
            FROM Borrowers b
            JOIN Loan l ON b.employe_id = l.employe_id
            WHERE l.kptn IS NULL
              AND l.requires_kptn = TRUE 
            ORDER BY l.date_granted DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // CHANGED: Since Ledger is already created, this ONLY updates KPTN code.
    public function activateBatchLoan($loanId, $kptnCode, $verifiedByEmployeId = null) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE loan_id = ? AND kptn IS NULL");
        $stmt->execute([$loanId]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Pending loan not found or already has a KPTN attached.");
        }

        try {
            // Attach the KPTN code to complete the loan record
            $upd = $this->db->prepare("UPDATE Loan SET kptn = ? WHERE loan_id = ?");
            $upd->execute([$kptnCode, $loanId]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function notifyPendingKptn($loanId, $triggeredByEmployeId, $borrowerName, $pnNumber, $targetRoles = ['ADMIN', 'REVIEWER']) {
        if (empty($targetRoles)) return;

        $placeholders = implode(',', array_fill(0, count($targetRoles), '?'));
        $sql = "SELECT employe_id FROM Users WHERE user_type IN ($placeholders) AND status = 'ACTIVE'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($targetRoles);
        $recipients = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($recipients)) return; 

        $message = strtoupper("ACTION REQUIRED: Attach KPTN Receipt for Batch Upload ($pnNumber) - $borrowerName.");

        $insertStmt = $this->db->prepare("
            INSERT INTO Notifications (recipient_employe_id, triggered_by_employe_id, loan_id, type, message)
            VALUES (?, ?, ?, 'PENDING_KPTN', ?)
        ");

        $cleanTriggeredBy = !empty($triggeredByEmployeId) ? $triggeredByEmployeId : null;

        foreach ($recipients as $recipientId) {
            $insertStmt->execute([$recipientId, $cleanTriggeredBy, $loanId, $message]);
        }
    }
}