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
     * Calculates EIR and builds the array for the frontend modal.
     */
    public function generatePreview($principal, $deduction, $termsInMonths, $dateGranted) {
        $totalPeriods = $termsInMonths * 2; // Semi-monthly
        
        // A. Calculate Effective Interest Rate (EIR) using Binary Search
        // This ensures the schedule mathematically zeroes out at the end.
        $periodicRate = $this->getPeriodicRate($principal, $deduction, $totalPeriods);
        
        // B. Generate the Date and Payment Schedule
        return $this->buildAmortizationTable($principal, $deduction, $periodicRate, $totalPeriods, $dateGranted);
    }

   /**
     * 2. SAVE MODE: Writes the finalized data to the database.
     * Populates all columns in the Loan table as per the schema.
     */
    public function saveLoanApplication($data, $schedule) {
        try {
            $this->db->beginTransaction();

            // Default fallback logic for Region and Division
            $division = !empty($data['division']) ? strtoupper(trim($data['division'])) : 'N/A';
            $region = !empty($data['region']) ? strtoupper(trim($data['region'])) : 'N/A';

            // --- STEP 1: Insert or Update Borrower ---
            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, division, region)
                VALUES (:eid, :fname, :lname, :contact, :division, :region)
                ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    division = VALUES(division),
                    region = VALUES(region)
            ");
            
            $stmtBorrower->execute([
                ':eid' => $data['employe_id'],
                ':fname' => $data['first_name'],
                ':lname' => $data['last_name'],
                ':contact' => $data['contact_number'],
                ':division' => $division,
                ':region' => $region
            ]);

            // --- STEP 2: Calculate Derived Loan Values (FIXED AOR) ---
            $principal = floatval($data['loan_amount']);
            $deduction = floatval($data['deduction']);
            $termsMonths = intval($data['terms']);
            
            $totalPeriods = $termsMonths * 2; 
            $periodicRate = floatval($schedule['periodic_rate']);
            $annualYield = $periodicRate * 24;

            // EXACT MATCH TO Find AOR.txt
            $totalRepayment = $deduction * $totalPeriods;
            $totalInterest = $totalRepayment - $principal;

            $addOnRate = 0;
            if ($principal > 0) {
                // Total Interest / Principal (No division by years)
                $addOnRate = $totalInterest / $principal; 
            }

            // --- GET TRUE MATURITY DATE ---
            $lastRow = end($schedule['rows']); 
            $trueMaturityDate = $lastRow['date_obj'];

            // --- STEP 3: Insert Loan Record ---
            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                    pn_date, date_granted, maturity_date, current_status
                ) VALUES (
                    :eid, :pn, :amount, :addon, :terms, :periods, 
                    :periodic_rate, :annual_yield, :deduction, :granted, 
                    :granted, :maturity, 'ONGOING'
                )
            ");

            $stmtLoan->execute([
                ':eid' => $data['employe_id'],
                ':pn' => $data['pn_number'],
                ':amount' => $principal,
                ':addon' => $addOnRate, 
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
                    :lid, :no, :date, :princ, :int, :total, :bal, 'PENDING'
                )
            ");

            // Change from $schedule['schedule'] to $schedule['rows']
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
     * Corresponds to the 'E.Y function.txt' logic provided.
     */
    private function getPeriodicRate($principal, $payment, $periods) {
        // Safety check to avoid division by zero
        if ($principal <= 0 || $payment <= 0 || $periods <= 0) return 0;
        
        // If Total Payments < Principal, it's negative interest (impossible in this context), return 0
        if (($payment * $periods) <= $principal) return 0;

        $low = 0;
        $high = 1; // 100% per period upper bound (safe enough)
        $precision = 0.0000001;
        $guess = 0;

        for ($i = 0; $i < 100; $i++) { // Max 100 iterations to prevent infinite loops
            $guess = ($low + $high) / 2;
            if ($guess <= 0) { $low = 0.0000001; continue; }

            // PV formula: Payment * [ (1 - (1+r)^-n) / r ]
            $testPrincipal = $payment * (1 - pow(1 + $guess, -$periods)) / $guess;

            if (abs($testPrincipal - $principal) < 0.01) {
                break; // Found it within 1 cent accuracy
            }

            if ($testPrincipal > $principal) {
                // Rate is too low (PV is too high)
                $low = $guess;
            } else {
                // Rate is too high (PV is too low)
                $high = $guess;
            }
        }
        return $guess;
    }

    /**
     * Builds the full schedule array.
     * Logic: Diminishing Balance (Typical Amortization)
     */
    /**
     * Builds the full schedule array.
     * Logic: Diminishing Balance (Typical Amortization)
     */
    /**
     * Builds the full schedule array.
     * Logic: Diminishing Balance (Typical Amortization)
     */
    private function buildAmortizationTable($principal, $deduction, $rate, $periods, $dateGranted) {
        $rows = [];
        $balance = $principal;
        $currentDate = new \DateTime($dateGranted);

        // --- STRICT CUT-OFF LOGIC FOR FIRST PAYMENT ---
        $day = (int)$currentDate->format('d');
        
        if ($day >= 11 && $day <= 25) {
            // PN Date 11th to 25th -> First payment is End of the Current Month
            $currentDate->modify('last day of this month');
        } elseif ($day >= 26) {
            // PN Date 26th to End -> First payment is 15th of the Next Month
            $currentDate->modify('first day of next month');
            $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
        } else {
            // PN Date 1st to 10th -> First payment is 15th of the Current Month
            $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
        }

        $totalInterest = 0;

        for ($i = 1; $i <= $periods; $i++) {
            // 1. Calculate Interest for this period
            $interest = round($balance * $rate, 2);
            
            // 2. Calculate Principal
            if ($i == $periods) {
                $principalPart = $balance;
                $deduction = $principalPart + $interest; // Adjust final payment slightly if needed
            } else {
                $principalPart = round($deduction - $interest, 2);
            }

            // 3. New Balance
            $balance = round($balance - $principalPart, 2);
            if ($balance < 0) $balance = 0;

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

            // Move to next semi-monthly date for the next loop iteration
            $currentDate = $this->getNextSemiMonthlyDate($currentDate);
        }

        // 1. Calculate Annual Effective Yield (E.Y.)
        $effectiveYield = $rate * 24 * 100;

        // 2. --- FIXED: Total Add-on Rate (A.O.R.) ---
        $addOnRate = 0;
        if ($principal > 0) {
            // Formula matches Find AOR.txt logic, multiplied by 100 for the frontend percentage display
            $addOnRate = ($totalInterest / $principal) * 100; 
        }

        return [
            'success' => true,
            'periodic_rate' => $rate, 
            'effective_yield' => number_format($effectiveYield, 2),
            'add_on_rate' => number_format($addOnRate, 2), 
            'total_interest' => round($totalInterest, 2),
            'schedule' => $rows
        ];
    }

    /**
     * Determines the next 15th or End of Month.
     * @param \DateTime $date Starting date
     * @param int $minDaysOffset Minimum days to add (Grace Period buffer)
     */
    /**
     * Determines the next 15th or End of Month based strictly on the current payment date.
     * @param \DateTime $date Starting date (which should already be a 15th or EOM)
     */
    private function getNextSemiMonthlyDate(\DateTime $date) {
        $nextDate = clone $date;
        $day = (int)$nextDate->format('d');

        if ($day == 15) {
            // Currently on the 15th -> Move to End of Current Month
            $nextDate->modify('last day of this month');
        } else {
            // Currently on End of Month -> Move to 15th of Next Month
            $nextDate->modify('first day of next month');
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 15);
        }

        return $nextDate;
    }

    /**
     * FETCH ALL BORROWERS (For the Index List)
     */
    public function getAllBorrowers() {
        $sql = "
            SELECT 
                b.employe_id as id, 
                CONCAT(b.first_name, ' ', b.last_name) as name,
                b.first_name,
                b.last_name,
                b.contact_number as contact,
                b.region,
                l.pn_number as pn_no,
                DATE_FORMAT(l.date_granted, '%m / %d / %Y') as date,
                l.date_granted as raw_date, /* <-- ADD THIS LINE */
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

    /**
     * GET NEXT AVAILABLE ID
     * Finds the highest current Employee ID and adds 1.
     * Default Start: 20260001 (Year + Sequence) if table is empty.
     */
    public function getNextBorrowerId() {
        $stmt = $this->db->query("SELECT MAX(employe_id) as max_id FROM Borrowers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $maxId = $result['max_id'];
        
        if ($maxId) {
            return intval($maxId) + 1;
        } else {
            // Default starting ID if database is empty
            return 20260001; 
        }
    }

    /**
     * Fetch all loans for the Ledger Master List
     */
    /**
     * Fetch all loans for the Ledger Master List
     */
    public function getAllLedgerLoans() {
        $sql = "SELECT 
                    b.employe_id, 
                    CONCAT(b.first_name, ' ', b.last_name) AS name,
                    l.loan_id,
                    l.pn_number,
                    l.date_granted AS g_date,
                    l.maturity_date,
                    l.current_status,
                    l.loan_amount,
                    l.term_months,
                    l.semi_monthly_amt,
                    l.add_on_rate -- <-- ADD THIS LINE
                FROM Loan l
                JOIN Borrowers b ON l.employe_id = b.employe_id
                ORDER BY l.date_granted DESC";
                
        // FIXED: Changed $this->pdo to $this->db
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch the granular amortization schedule for a specific loan
     */

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
                    payment_notes AS notes
                FROM Amortization_Ledger
                WHERE loan_id = :loan_id
                ORDER BY installment_no ASC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':loan_id' => $loan_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ==========================================================
     * ADMIN ONLY: NUCLEAR WIPE (DELETE BORROWER)
     * ==========================================================
     * Because the DB schema uses ON DELETE CASCADE, deleting the 
     * borrower will automatically wipe their Loans, Ledgers, 
     * Payroll Deductions, and AR Summaries without orphaned data.
     */
    public function deleteBorrower($employeId) {
        try {
            // Start transaction just to be absolutely safe
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM Borrowers WHERE employe_id = :id");
            $stmt->execute([':id' => $employeId]);

            // Check if a row was actually deleted
            if ($stmt->rowCount() > 0) {
                $this->db->commit();
                return ['success' => true];
            } else {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Borrower not found or already deleted.'];
            }
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Finds an existing borrower by first and last name.
     * Returns the employe_id if found, null otherwise.
     */
    public function getBorrowerByName($firstName, $lastName) {
        $stmt = $this->db->prepare("
            SELECT employe_id 
            FROM Borrowers 
            WHERE TRIM(UPPER(first_name)) = TRIM(UPPER(:fname)) 
              AND TRIM(UPPER(last_name))  = TRIM(UPPER(:lname))
            LIMIT 1
        ");
        $stmt->execute([
            ':fname' => $firstName,
            ':lname' => $lastName
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result['employe_id'] : null;
    }

    /**
     * Checks if a borrower already exists based on strict First Name and Last Name matching.
     * Prevents database duplication across all entry points.
     */
    public function isBorrowerExists($firstName, $lastName) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM Borrowers 
            WHERE TRIM(UPPER(first_name)) = TRIM(UPPER(:fname)) 
              AND TRIM(UPPER(last_name))  = TRIM(UPPER(:lname))
        ");
        $stmt->execute([
            ':fname' => $firstName,
            ':lname' => $lastName
        ]);
        return $stmt->fetchColumn() > 0;
    }

}