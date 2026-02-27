<?php
namespace App;

use PDO;
use Exception;

class LoanService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * 1. PREVIEW MODE: Generates the schedule without saving.
     */
    public function generatePreview($principal, $termsInMonths, $dateGranted) {
        $totalPeriods = $termsInMonths * 2; // Semi-monthly
        
        // --- NEW BUSINESS MATH: 1.5% Monthly Add-On ---
        // 1. Original Amount * 1.5% * Term = Total Interest
        $totalInterest = ($principal * 0.015) * $termsInMonths;
        
        // 2. Added back to base loan amount
        $totalRepayment = $principal + $totalInterest;
        
        // 3. Divided by terms and finally divided by 2
        $deduction = $totalRepayment / $totalPeriods;
        
        // A. Calculate Effective Interest Rate (EIR) using Binary Search
        // Required to properly split Principal and Interest (Diminishing Balance)
        $periodicRate = $this->getPeriodicRate($principal, $deduction, $totalPeriods);
        
        // B. Generate the Date and Payment Schedule (Original Logic)
        $result = $this->buildAmortizationTable($principal, $deduction, $periodicRate, $totalPeriods, $dateGranted);
        
        // C. Attach the generated PN Number to show in the preview modal
        $result['pn_number'] = $this->generatePnNumber();
        $result['deduction'] = round($deduction, 2);

        return $result;
    }

   /**
     * 2. SAVE MODE: Writes the finalized data to the database.
     */
    public function saveLoanApplication($data, $schedule) {
        try {
            $this->db->beginTransaction();

            $division = !empty($data['division']) ? strtoupper(trim($data['division'])) : 'N/A';
            $region = !empty($data['region']) ? strtoupper(trim($data['region'])) : 'N/A';
            $branch = !empty($data['branch']) ? strtoupper(trim($data['branch'])) : 'N/A';

            // --- STEP 1: Insert or Update Borrower ---
            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, division, branch, region)
                VALUES (:eid, :fname, :lname, :contact, :division, :branch, :region)
                ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    division = VALUES(division),
                    branch = VALUES(branch),
                    region = VALUES(region)
            ");
            
            $stmtBorrower->execute([
                ':eid' => $data['employe_id'],
                ':fname' => $data['first_name'],
                ':lname' => $data['last_name'],
                ':contact' => $data['contact_number'],
                ':division' => $division,
                ':branch' => $branch,
                ':region' => $region
            ]);

            // --- STEP 2: Calculate Derived Loan Values ---
            $principal = floatval($data['loan_amount']);
            $deduction = floatval($data['deduction']);
            $termsMonths = intval($data['terms']);
            
            $totalPeriods = $termsMonths * 2; 
            $periodicRate = floatval($schedule['periodic_rate']);
            $annualYield = $periodicRate * 24;

            // Securely re-generate the PN at the moment of saving to prevent duplicates
            $pnNumber = $this->generatePnNumber();

            // --- GET TRUE MATURITY DATE ---
            $lastRow = end($schedule['rows']); 
            $trueMaturityDate = $lastRow['date_obj'];

            // --- STEP 3: Insert Loan Record ---
            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                    pn_date, date_granted, maturity_date, current_status
                ) VALUES (
                    :eid, :ref, :pn, :amount, :addon, :terms, :periods, 
                    :periodic_rate, :annual_yield, :deduction, :granted, 
                    :granted, :maturity, 'ONGOING'
                )
            ");

            $stmtLoan->execute([
                ':eid' => $data['employe_id'],
                ':ref' => !empty($data['reference_number']) ? $data['reference_number'] : null,
                ':pn' => $pnNumber,
                ':amount' => $principal,
                ':addon' => 0.015, // Using the new 1.5% fixed logic constraint
                ':terms' => $termsMonths,
                ':periods' => $totalPeriods,
                ':periodic_rate' => $periodicRate,
                ':annual_yield' => $annualYield,  
                ':deduction' => $deduction,
                ':granted' => $data['loan_granted'],
                ':maturity' => $trueMaturityDate
            ]);

            $loanId = $this->db->lastInsertId();

            // --- STEP 4: Insert Amortization Ledger ---
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
                    ':lid' => $loanId,
                    ':no' => $row['installment_no'],
                    ':date' => $row['date_obj'], 
                    ':princ' => $row['principal'],
                    ':int' => $row['interest'],
                    ':total' => $row['total'],
                    ':bal' => $row['balance']
                ]);
            }

            $this->db->commit();
            return ['success' => true, 'loan_id' => $loanId];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================================
    // PRIVATE HELPER METHODS (The Core Math)
    // ==========================================================

    /**
     * Finds the Effective Interest Rate (Periodic) using Binary Search.
     */
    private function getPeriodicRate($principal, $payment, $periods) {
        if ($principal <= 0 || $payment <= 0 || $periods <= 0) return 0;
        if (($payment * $periods) <= $principal) return 0;

        $low = 0;
        $high = 1; 
        $precision = 0.0000001;
        $guess = 0;

        for ($i = 0; $i < 100; $i++) { 
            $guess = ($low + $high) / 2;
            if ($guess <= 0) { $low = 0.0000001; continue; }

            $testPrincipal = $payment * (1 - pow(1 + $guess, -$periods)) / $guess;

            if (abs($testPrincipal - $principal) < 0.01) {
                break; 
            }

            if ($testPrincipal > $principal) {
                $low = $guess;
            } else {
                $high = $guess;
            }
        }
        return $guess;
    }

    /**
     * Builds the full schedule array.
     * Logic: ORIGINAL Diminishing Balance (Typical Amortization)
     */
    private function buildAmortizationTable($principal, $deduction, $rate, $periods, $dateGranted) {
        $rows = [];
        $balance = $principal;
        $currentDate = new \DateTime($dateGranted);

        // --- STRICT CUT-OFF LOGIC FOR FIRST PAYMENT ---
        $day = (int)$currentDate->format('d');
        if ($day >= 11 && $day <= 25) {
            $currentDate->modify('last day of this month');
        } elseif ($day >= 26) {
            $currentDate->modify('first day of next month');
            $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
        } else {
            $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
        }

        $totalInterest = 0;

        for ($i = 1; $i <= $periods; $i++) {
            
            if ($i == $periods) {
                // LAST ROW FIX: 
                $principalPart = $balance;
                $interest = round($deduction - $principalPart, 2);
                if ($interest < 0) $interest = 0; 
                $balance = 0; 
            } else {
                // NORMAL ROWS: Standard amortization calculation
                $interest = round($balance * $rate, 2);
                $principalPart = round($deduction - $interest, 2);
                $balance = round($balance - $principalPart, 2);
            }

            $totalInterest += $interest;

            $rows[] = [
                'installment_no' => $i,
                'date' => $currentDate->format('M d, Y'),
                'date_obj' => $currentDate->format('Y-m-d'), // For DB
                'principal' => $principalPart,
                'interest' => $interest,
                'total' => $deduction,
                'balance' => $balance
            ];

            // Move to next semi-monthly date
            $currentDate = $this->getNextSemiMonthlyDate($currentDate);
        }

        $effectiveYield = $rate * 24 * 100;
        $addOnRate = ($totalInterest / $principal) * 100; // Recalculated to display e.g. 36%

        $lastRow = end($rows);

        return [
            'success' => true,
            'periodic_rate' => $rate, 
            'effective_yield' => number_format($effectiveYield, 2),
            'add_on_rate' => number_format($addOnRate, 2), 
            'total_interest' => round($totalInterest, 2),
            'maturity_date' => $lastRow['date'], // Passed to UI
            'schedule' => $rows
        ];
    }

    private function getNextSemiMonthlyDate(\DateTime $date) {
        $nextDate = clone $date;
        $day = (int)$nextDate->format('d');

        if ($day == 15) {
            $nextDate->modify('last day of this month');
        } else {
            $nextDate->modify('first day of next month');
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 15);
        }
        return $nextDate;
    }

    /**
     * Helper: Generate sequential PN Number: PN-YYYY-XXXX
     */
    private function generatePnNumber() {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(loan_id) FROM Loan WHERE YEAR(date_granted) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetchColumn() + 1;
        return "PN-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ==========================================================
    // PUBLIC FETCH METHODS
    // ==========================================================

    public function getAllBorrowers() {
        $sql = "
            SELECT 
                b.employe_id as id, 
                CONCAT(b.first_name, ' ', b.last_name) as name,
                b.first_name,
                b.last_name,
                b.contact_number as contact,
                b.region,
                l.loan_ref_no as reference_no, /* <-- ADDED THIS LINE */
                l.pn_number as pn_no,
                DATE_FORMAT(l.date_granted, '%m / %d / %Y') as date,
                l.date_granted as raw_date,
                DATE_FORMAT(l.maturity_date, '%m / %d / %Y') as pn_maturity,
                l.loan_amount,
                l.term_months as terms,
                l.semi_monthly_amt as deduction,
                l.current_status
            FROM Borrowers b
            JOIN Loan l ON b.employe_id = l.employe_id
            ORDER BY l.date_granted DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextBorrowerId() {
        $stmt = $this->db->query("SELECT MAX(employe_id) as max_id FROM Borrowers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $maxId = $result['max_id'];
        if ($maxId) { return intval($maxId) + 1; } 
        else { return 20260001; }
    }

    public function getAllLedgerLoans() {
        // FIX: Added b.region, b.branch, b.contact_number, and l.loan_ref_no
        $sql = "SELECT 
                    b.employe_id, 
                    CONCAT(b.first_name, ' ', b.last_name) AS name,
                    b.region,
                    b.branch,
                    b.contact_number,
                    l.loan_id,
                    l.loan_ref_no,
                    l.pn_number,
                    l.date_granted AS g_date,
                    l.maturity_date,
                    l.current_status,
                    l.loan_amount,
                    l.term_months,
                    l.semi_monthly_amt,
                    l.add_on_rate
                FROM Loan l
                JOIN Borrowers b ON l.employe_id = b.employe_id
                ORDER BY l.date_granted DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

            $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = :id");
            $stmt->execute([':id' => $employeId]);
            $loans = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($loans)) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'No active loans found for this borrower to void.'];
            }

            $inQuery = implode(',', array_fill(0, count($loans), '?'));

            $stmtLoan = $this->db->prepare("
                UPDATE Loan 
                SET current_status = 'VOIDED', 
                    voided_at = CURRENT_TIMESTAMP, 
                    voided_by_user_id = ?, 
                    void_reason = ? 
                WHERE employe_id = ?
            ");
            $stmtLoan->execute([$userId, $voidReason, $employeId]);

            $stmtLedger = $this->db->prepare("UPDATE Amortization_Ledger SET status = 'VOIDED' WHERE loan_id IN ($inQuery)");
            $stmtLedger->execute($loans);

            $stmtDeductions = $this->db->prepare("UPDATE Payroll_deductions SET match_status = 'VOIDED' WHERE loan_id IN ($inQuery)");
            $stmtDeductions->execute($loans);

            $stmtAR = $this->db->prepare("UPDATE Running_AR_Summary SET loan_status = 'VOIDED' WHERE loan_id IN ($inQuery)");
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
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM Borrowers 
            WHERE TRIM(UPPER(first_name)) = TRIM(UPPER(:fname)) 
              AND TRIM(UPPER(last_name))  = TRIM(UPPER(:lname))
        ");
        $stmt->execute([':fname' => $firstName, ':lname' => $lastName]);
        return $stmt->fetchColumn() > 0;
    }
}