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
        $deduction = floatval($customDeduction); 
        
        // Let the table builder figure out the real rate
        $result = $this->buildAmortizationTable($principal, $deduction, 0, $totalPeriods, $dateGranted, $firstDeduction, $lastDeduction);
        
        $result['pn_number'] = $this->generatePnNumber($pnOffset);
        $result['deduction'] = $result['schedule'][0]['total'] ?? round($deduction, 2);

        return $result;
    }

     public function saveLoanApplication($data, $schedule) {
        try {
            $this->db->beginTransaction();

            $division = !empty($data['division']) ? strtoupper(trim($data['division'])) : 'N/A';
            $region   = !empty($data['region'])   ? strtoupper(trim($data['region']))   : 'N/A';
            $branch   = !empty($data['branch'])   ? strtoupper(trim($data['branch']))   : 'N/A';

            $stmtOngoing = $this->db->prepare("
                SELECT COUNT(*) FROM Loan 
                WHERE employe_id = ? AND current_status = 'ONGOING'
            ");
            $stmtOngoing->execute([$data['employe_id']]);
            if ((int)$stmtOngoing->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'This employee already has an ONGOING loan. A new loan can only be created once the existing loan is fully paid.'];
            }

            // UPDATED: Use branch_id and region_code
            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, division, branch_id, region_code)
                VALUES (:eid, :fname, :lname, :contact, :division, :branch_id, :region_code)
                ON DUPLICATE KEY UPDATE 
                    first_name     = VALUES(first_name),
                    last_name      = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    division       = VALUES(division),
                    branch_id      = VALUES(branch_id),
                    region_code    = VALUES(region_code)
            ");

            $stmtBorrower->execute([
                ':eid'         => $data['employe_id'],
                ':fname'       => $data['first_name'],
                ':lname'       => $data['last_name'],
                ':contact'     => $data['contact_number'],
                ':division'    => $division,
                ':branch_id'   => $branch,
                ':region_code' => $region
            ]);

            $principal    = floatval($data['loan_amount']);
            $deduction    = floatval($data['deduction']);
            $termsMonths  = intval($data['terms']);
            $totalPeriods = $termsMonths * 2;
            $periodicRate = floatval($schedule['periodic_rate'] ?? 0);
            $annualYield  = $periodicRate * 24;

            $pnNumber        = $this->generatePnNumber((int)($data['pn_offset'] ?? 0));
            $globalRate = $this->getGlobalAddOnRate();
            $addOnRateToSave = isset($data['add_on_rate_decimal']) ? floatval($data['add_on_rate_decimal']) : $globalRate;

            $entryType = (isset($data['entry_type']) && $data['entry_type'] === 'BATCH') ? 'BATCH' : 'MANUAL';

            if (isset($data['requires_kptn'])) {
                $requiresKptn = filter_var($data['requires_kptn'], FILTER_VALIDATE_BOOLEAN);
            } else {
                $requiresKptn = ($entryType === 'BATCH') ? true : false;
            }

            if (!$requiresKptn) {
                $depositAmount = 0.00;
                $kptnToSave    = uniqid('NR_'); 
            } else {
                $depositAmount = isset($data['deposit_amount']) ? floatval($data['deposit_amount']) : 2500.00;
                $kptnToSave    = $data['kptn'] ?? null;
            }

            if ($entryType === 'BATCH' && empty($schedule['rows']) && empty($schedule['schedule'])) {
                $batchFirstDeduction = !empty($data['first_deduction']) ? $data['first_deduction'] : null;
                $batchLastDeduction  = !empty($data['last_deduction'])  ? $data['last_deduction']  : null;
                $schedule = $this->buildAmortizationTable(
                    $principal, $deduction, $periodicRate, $totalPeriods,
                    $data['loan_granted'],
                    $batchFirstDeduction,
                    $batchLastDeduction
                );
            }

            if (isset($schedule['add_on_rate_decimal'])) {
                $addOnRateToSave = $schedule['add_on_rate_decimal'];
                if ($deduction <= 0 && !empty($schedule['schedule'])) {
                    $deduction = $schedule['schedule'][0]['total'];
                }
            }

            $ledgerRows = $schedule['rows'] ?? $schedule['schedule'] ?? [];

            if (!empty($ledgerRows)) {
                $lastRow          = end($ledgerRows);
                $trueMaturityDate = $lastRow['date_obj'];
            } else {
                $trueMaturityDate = date('Y-m-d', strtotime($data['pn_maturity']));
            }

            if (!empty($schedule['success']) && isset($schedule['total_interest'])) {
                $totalInterestAmount = $schedule['total_interest'];
                $grossLoanAmount     = $schedule['gross_amount'];
            } else {
                $termsInMonths       = $totalPeriods / 2;
                $totalInterestAmount = round($principal * $addOnRateToSave * $termsInMonths, 2);
                $grossLoanAmount     = $principal + $totalInterestAmount;
            }

             $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, uploaded_by_employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                    total_interest_amount, gross_loan_amount,
                    pn_date, date_granted, maturity_date, current_status,
                    entry_type, loan_month, mode_of_payment, requires_kptn, deposit_amount, pending_kptn, kptn
                ) VALUES (
                    :eid, :uploader_id, :ref, :pn, :amount, :addon, :terms, :periods, 
                    :periodic_rate, :annual_yield, :deduction, 
                    :total_interest, :gross_amount,
                    :granted, :granted, :maturity, 'ONGOING',
                    :entry_type, :loan_month, :mode_of_payment, :requires_kptn, :deposit_amount, :pending_kptn, :kptn
                )
            ");

            $stmtLoan->execute([
                ':eid'              => $data['employe_id'],
                ':uploader_id'      => $data['uploaded_by_employe_id'] ?? null,
                ':ref'              => !empty($data['reference_number']) ? $data['reference_number'] : null,
                ':pn'               => $pnNumber,
                ':amount'           => $principal,
                ':addon'            => $addOnRateToSave,
                ':terms'            => $termsMonths,
                ':periods'          => $totalPeriods,
                ':periodic_rate'    => $periodicRate,
                ':annual_yield'     => $annualYield,
                ':deduction'        => $deduction,
                ':total_interest'   => $totalInterestAmount,
                ':gross_amount'     => $grossLoanAmount,
                ':granted'          => $data['loan_granted'],
                ':maturity'         => $trueMaturityDate,
                ':entry_type'       => $entryType,
                ':loan_month'       => !empty($data['loan_month'])      ? strtoupper(trim($data['loan_month']))      : null,
                ':mode_of_payment'  => !empty($data['mode_of_payment']) ? strtoupper(trim($data['mode_of_payment'])) : null,
                ':requires_kptn'    => $requiresKptn ? 1 : 0,
                ':deposit_amount'   => $depositAmount,
                ':pending_kptn'     => !empty($data['pending_kptn']) ? $data['pending_kptn'] : null,
                ':kptn'             => $kptnToSave
            ]);

            $loanId = $this->db->lastInsertId();

            if (!empty($ledgerRows)) {
                $stmtLedger = $this->db->prepare("
                    INSERT INTO Amortization_Ledger (
                        loan_id, installment_no, scheduled_date, 
                        principal_amt, interest_amt, total_payment, 
                        remaining_bal, status
                    ) VALUES (
                        :lid, :no, :date, :princ, :int, :total, :bal, 'UNPAID'
                    )
                ");

                foreach ($ledgerRows as $row) {
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
        $balance = (float)$principal;
        $termsInMonths = $periods / 2;

        // ===============================================================
        // SMART TOGGLE: DERIVE vs SYSTEM DEFAULT
        // ===============================================================
        if ($deduction > 0) {
            // Excel provided a deduction. Calculate interest backwards.
            $uniformDeduction = round((float)$deduction, 2);
            $targetGross = round($uniformDeduction * $periods, 2);
            $targetTotalInterest = $targetGross - $principal;
            
            if ($targetTotalInterest < 0) {
                $targetTotalInterest = 0.00; 
            }
        } else {
            // Deduction is empty. Fetch System Settings rate and calculate forwards.
            $globalRate = $this->getGlobalAddOnRate();
            $targetTotalInterest = round($principal * $globalRate * $termsInMonths, 2);
            $targetGross = $principal + $targetTotalInterest;
            $uniformDeduction = ceil($targetGross / $periods); 
        }

        $exactRate = $this->getPeriodicRate($principal, $uniformDeduction, $periods);

        // --- Schedule Date Logic ---
        $useFirstDeduction = false;
 
        if (!empty($firstDeduction)) {
            try {
                $fdDate = new \DateTime($firstDeduction);
                $gdDate = new \DateTime($dateGranted);
                if ($fdDate > $gdDate) {
                    $useFirstDeduction = true;
                }
            } catch (\Exception $e) {
                $useFirstDeduction = false;
            }
        }
 
        if ($useFirstDeduction) {
            $currentDate = new \DateTime($firstDeduction);
            $currentDate = $this->capToValidPayrollDay($currentDate);
        } else {
            $currentDate = new \DateTime($dateGranted);
            $day = (int)$currentDate->format('d');
            if ($day <= 10) {
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
            } elseif ($day <= 25) {
                $currentDate = $this->setToEndOfSemiMonth($currentDate);
            } else {
                $currentDate->modify('first day of next month');
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
            }
        }

        for ($i = 1; $i <= $periods; $i++) {
            if ($i == $periods) {
                $principalPart = round($balance, 2);
                $interest = round($uniformDeduction - $principalPart, 2);
                if ($interest < 0) $interest = 0.00;
                $totalPayment = $uniformDeduction;
                $displayBalance = 0.00;
            } else {
                $interest = round($balance * $exactRate, 2);
                $principalPart = round($uniformDeduction - $interest, 2);

                if ($balance - $principalPart < 0) {
                    $principalPart = round($balance, 2);
                    $interest = round($uniformDeduction - $principalPart, 2);
                }

                $balance -= $principalPart; 
                $displayBalance = round($balance, 2);
                $totalPayment = $uniformDeduction;
            }

            $rows[] = [
                'installment_no' => $i,
                'date'           => $currentDate->format('M d, Y'),
                'date_obj'       => $currentDate->format('Y-m-d'),
                'principal'      => number_format($principalPart, 2, '.', ''),
                'interest'       => number_format($interest, 2, '.', ''),
                'total'          => number_format($totalPayment, 2, '.', ''),
                'balance'        => number_format($displayBalance, 2, '.', '')
            ];

            $currentDate = $this->getNextSemiMonthlyDate($currentDate);
        }

        $effectiveYield = $exactRate * 24 * 100;
        
        // Calculate the raw Add On Rate
        $rawAddOnRateDecimal = $principal > 0 ? ($targetTotalInterest / $principal) / $termsInMonths : 0;
        
        // MODIFIED: Round the exact rate to a clean 4 decimal places (e.g. 0.0133 instead of 0.01333333333)
        $cleanAddOnRateDecimal = round($rawAddOnRateDecimal, 4);
        $addOnRatePercent = number_format($cleanAddOnRateDecimal * 100, 2, '.', '');

        $lastRow = end($rows);

        return [
            'success'             => true,
            'periodic_rate'       => $exactRate,
            'effective_yield'     => number_format($effectiveYield, 2, '.', ''),
            'add_on_rate'         => $addOnRatePercent, // Rounded to cleanly display e.g. 1.33
            'add_on_rate_decimal' => $cleanAddOnRateDecimal, // Sent to DB as 0.0133
            'total_interest'      => $targetTotalInterest, 
            'gross_amount'        => $targetGross,         
            'maturity_date'       => $lastRow['date'],
            'schedule'            => $rows
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
    $stmt = $this->db->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(pn_number, '-', -1) AS UNSIGNED))
         FROM Loan
         WHERE pn_number LIKE ? AND pn_number IS NOT NULL"
    );
    $stmt->execute(["PN-{$year}-%"]);

    $max   = (int) $stmt->fetchColumn();
    $count = $max + 1 + $offset;
    return "PN-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

public function getAllBorrowers($paginate = false, $page = 1, $limit = 50, $search = '', $fromDate = '', $toDate = '', $status = '') {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (b.employe_id LIKE ? OR CONCAT(b.first_name, ' ', b.last_name) LIKE ?)";
            array_push($params, "%{$search}%", "%{$search}%");
        }
        if (!empty($fromDate)) { $where .= " AND l.date_granted >= ?"; $params[] = $fromDate; }
        if (!empty($toDate)) { $where .= " AND l.date_granted <= ?"; $params[] = $toDate; }
        if (!empty($status)) {
            if ($status === 'VOIDED') {
                // Include loans that are explicitly VOIDED OR have an inactivate reason (AWOL/RESIGNED)
                $where .= " AND (l.current_status = 'VOIDED' OR UPPER(COALESCE(l.void_reason,'')) IN ('AWOL','RESIGNED'))";
            } else {
                $where .= " AND l.current_status = ?"; $params[] = $status;
            }
        }

        $baseSql = " FROM Borrowers b JOIN Loan l ON b.employe_id = l.employe_id $where";

        // UPDATED COLUMNS
        $selectCols = "
            b.employe_id as id, l.loan_id, CONCAT(b.first_name, ' ', b.last_name) as name,
            b.first_name, b.last_name, b.contact_number as contact, b.region_code,
            l.loan_ref_no as reference_no, l.pn_number as pn_no,
            DATE_FORMAT(l.date_granted, '%m / %d / %Y') as date, l.date_granted as raw_date,
            DATE_FORMAT(l.maturity_date, '%m / %d / %Y') as pn_maturity,
            l.loan_amount, l.term_months as terms, l.semi_monthly_amt as deduction, l.add_on_rate,
            l.deposit_amount, l.pending_kptn, l.kptn, l.current_status, l.requires_kptn,
            l.void_reason as inactivate_reason, l.voided_at as inactivated_at, l.voided_by_employe_id,
            (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM Users u WHERE u.employe_id = l.voided_by_employe_id LIMIT 1) as inactivated_by,
            (SELECT file_path FROM Loan_Documents WHERE loan_id = l.loan_id ORDER BY document_id DESC LIMIT 1) as file_path,
            (SELECT mime_type FROM Loan_Documents WHERE loan_id = l.loan_id ORDER BY document_id DESC LIMIT 1) as mime_type,
            (SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = l.loan_id AND status = 'PAID') as paid_count
        ";

        if (!$paginate) {
            $stmt = $this->db->prepare("SELECT " . $selectCols . $baseSql . " ORDER BY l.date_granted DESC");
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $baseSql);
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();

        $totalOverall = (int)$this->db->query("SELECT COUNT(*) FROM Loan l JOIN Borrowers b ON l.employe_id = b.employe_id")->fetchColumn();

        $offset = ($page - 1) * $limit;
        $stmtData = $this->db->prepare("SELECT " . $selectCols . $baseSql . " ORDER BY l.date_granted DESC LIMIT ? OFFSET ?");
        $paramIndex = 1;
        foreach ($params as $param) { $stmtData->bindValue($paramIndex++, $param); }
        $stmtData->bindValue($paramIndex++, (int)$limit, \PDO::PARAM_INT);
        $stmtData->bindValue($paramIndex++, (int)$offset, \PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'total_overall' => $totalOverall, 'total_filtered' => $totalFiltered,
            'data' => $stmtData->fetchAll(\PDO::FETCH_ASSOC),
            'total_pages' => max(1, ceil($totalFiltered / $limit)), 'current_page' => (int)$page
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

        if (!empty($search)) {
            $where .= " AND (l.pn_number LIKE ? OR b.employe_id LIKE ? OR CONCAT(b.first_name, ' ', b.last_name) LIKE ?)";
            array_push($params, "%{$search}%", "%{$search}%", "%{$search}%");
        }
        if (!empty($fromDate)) { $where .= " AND l.date_granted >= ?"; $params[] = $fromDate; }
        if (!empty($toDate)) { $where .= " AND l.date_granted <= ?"; $params[] = $toDate; }
        if (!empty($status)) { $where .= " AND l.current_status = ?"; $params[] = $status; }

        $baseSql = " FROM Loan l JOIN Borrowers b ON l.employe_id = b.employe_id $where";

        // UPDATED COLUMNS
        if (!$paginate) {
            $sql = "SELECT b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name, b.region_code, b.branch_id, b.contact_number, l.loan_id, l.loan_ref_no, l.pn_number, l.date_granted AS g_date, l.maturity_date, l.current_status, l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate, l.deposit_amount, l.requires_kptn " . $baseSql . " ORDER BY l.loan_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        $stmtCount = $this->db->prepare("SELECT COUNT(*) " . $baseSql);
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();

        $stats = $this->db->query("SELECT COUNT(*) as total, SUM(CASE WHEN current_status = 'ONGOING' THEN 1 ELSE 0 END) as ongoing, SUM(CASE WHEN current_status = 'FULLY PAID' THEN 1 ELSE 0 END) as paid, SUM(CASE WHEN current_status = 'VOIDED' THEN 1 ELSE 0 END) as voided FROM Loan")->fetch(\PDO::FETCH_ASSOC);

        $offset = ($page - 1) * $limit;
        // UPDATED COLUMNS
        $dataSql = "SELECT b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name, b.region_code, b.branch_id, b.contact_number, l.loan_id, l.loan_ref_no, l.pn_number, l.date_granted AS g_date, l.maturity_date, l.current_status, l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate, l.deposit_amount, l.requires_kptn " . $baseSql . " ORDER BY l.loan_id DESC LIMIT ? OFFSET ?";
        
        $stmtData = $this->db->prepare($dataSql);
        $paramIndex = 1;
        foreach ($params as $param) { $stmtData->bindValue($paramIndex++, $param); }
        $stmtData->bindValue($paramIndex++, (int)$limit, \PDO::PARAM_INT);
        $stmtData->bindValue($paramIndex++, (int)$offset, \PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'stats' => ['total' => (int)$stats['total'], 'ongoing' => (int)$stats['ongoing'], 'paid' => (int)$stats['paid'], 'voided' => (int)$stats['voided']],
            'total_filtered' => $totalFiltered, 'data' => $stmtData->fetchAll(\PDO::FETCH_ASSOC),
            'total_pages' => max(1, ceil($totalFiltered / $limit)), 'current_page' => (int)$page
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
        return ['success' => false, 'error' => 'Unable to void the loan right now. Please try again.'];
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
       return $this->db->query("
            SELECT b.employe_id as id, CONCAT(b.first_name, ' ', b.last_name) as name, b.region_code,
                l.loan_id, l.pn_number as pn_no, DATE_FORMAT(l.date_granted, '%M %d, %Y') as date,
                l.date_granted as raw_date, l.loan_amount, l.term_months as terms, l.semi_monthly_amt as deduction,
                l.pending_kptn, l.deposit_amount, l.loan_ref_no as reference_no
            FROM Borrowers b JOIN Loan l ON b.employe_id = l.employe_id
            WHERE l.kptn IS NULL AND l.requires_kptn = TRUE ORDER BY l.date_granted DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // CHANGED: Since Ledger is already created, this ONLY updates KPTN code.
    public function activateBatchLoan($loanId, $kptnCode, $verifiedByEmployeId = null, $depositAmount = null) {
        $stmt = $this->db->prepare("SELECT loan_id, kptn FROM Loan WHERE loan_id = ?");
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loan) {
            throw new Exception("Loan record not found.");
        }

        $existingKptn = trim((string)($loan['kptn'] ?? ''));
        $isPlaceholderNoKptn = ($existingKptn === '' || stripos($existingKptn, 'NR_') === 0);

        // Allow attaching only if loan is pending (NULL/blank) or originally saved as no-KPTN placeholder.
        if (!$isPlaceholderNoKptn && $existingKptn !== '') {
            throw new Exception("KPTN is already attached for this loan.");
        }

        try {
            // Convert/complete the loan as a KPTN-backed loan and clear pending marker.
            $upd = $this->db->prepare("UPDATE Loan SET requires_kptn = 1, kptn = ?, deposit_amount = ?, pending_kptn = NULL WHERE loan_id = ?");
            $upd->execute([$kptnCode, $depositAmount, $loanId]);

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
    private function getGlobalAddOnRate() {
    $stmt = $this->db->prepare("SELECT setting_value FROM System_Settings WHERE setting_key = 'add_on_rate'");
    $stmt->execute();
    $val = $stmt->fetchColumn();
    // Default to 0.015 (1.5%) if nothing is found
    return $val !== false ? floatval($val) : 0.015; 
}

}