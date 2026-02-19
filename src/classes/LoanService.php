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
     * Wraps inserts in a Transaction for safety.
     */
    public function saveLoanApplication($data, $schedule) {
        try {
            $this->db->beginTransaction();

            // Step 1: Insert or Update Borrower
            // Check if ID exists to decide between INSERT or UPDATE, or just INSERT IGNORE
            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, division, region)
                VALUES (:eid, :fname, :lname, :contact, 'N/A', :region)
                ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    region = VALUES(region)
            ");
            
            $stmtBorrower->execute([
                ':eid' => $data['employe_id'],
                ':fname' => $data['first_name'],
                ':lname' => $data['last_name'],
                ':contact' => $data['contact_number'],
                ':region' => $data['region']
            ]);

            // Step 2: Insert Loan Record
            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, semi_monthly_amt, 
                    pn_date, date_granted, maturity_date, current_status
                ) VALUES (
                    :eid, :pn, :amount, :addon, :terms, 
                    :periods, :rate, :deduction, 
                    :granted, :granted, :maturity, 'ONGOING'
                )
            ");

            // Calculate 'Add-on Rate' for record keeping (Total Interest / Principal / Years)
            $totalPayment = $data['deduction'] * ($data['terms'] * 2);
            $totalInterest = $totalPayment - $data['loan_amount'];
            $annualAddOn = 0;
            if($data['loan_amount'] > 0 && $data['terms'] > 0) {
                 $annualAddOn = ($totalInterest / $data['loan_amount']) / ($data['terms'] / 12);
            }

            $stmtLoan->execute([
                ':eid' => $data['employe_id'],
                ':pn' => $data['pn_number'],
                ':amount' => $data['loan_amount'],
                ':addon' => $annualAddOn, // Stored as decimal (e.g., 0.18 for 18%)
                ':terms' => $data['terms'],
                ':periods' => $data['terms'] * 2,
                ':rate' => $schedule['periodic_rate'], // The EIR used for calculation
                ':deduction' => $data['deduction'],
                ':granted' => $data['loan_granted'],
                ':maturity' => $data['pn_maturity']
            ]);

            $loanId = $this->db->lastInsertId();

            // Step 3: Insert Amortization Ledger
            $stmtLedger = $this->db->prepare("
                INSERT INTO Amortization_Ledger (
                    loan_id, installment_no, scheduled_date, 
                    principal_amt, interest_amt, total_payment, 
                    remaining_bal, status
                ) VALUES (
                    :lid, :no, :date, :princ, :int, :total, :bal, 'PENDING'
                )
            ");

            foreach ($schedule['rows'] as $row) {
                $stmtLedger->execute([
                    ':lid' => $loanId,
                    ':no' => $row['installment_no'],
                    ':date' => $row['date_obj'], // Ensure Y-m-d format
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
    private function buildAmortizationTable($principal, $deduction, $rate, $periods, $dateGranted) {
        $rows = [];
        $balance = $principal;
        $currentDate = new \DateTime($dateGranted);

        // --- GRACE PERIOD LOGIC ---
        // If granted date is close to a standard payday, skip to the next one.
        // Rule: First payment must be at least 7 days after granting.
        $firstPaymentDate = $this->getNextSemiMonthlyDate($currentDate, 7); 
        $currentDate = $firstPaymentDate;

        $totalInterest = 0;

        for ($i = 1; $i <= $periods; $i++) {
            // 1. Calculate Interest for this period
            $interest = round($balance * $rate, 2);
            
            // 2. Calculate Principal
            // If it's the last period, adjust principal to clear the balance exactly
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

            // Move to next semi-monthly date
            $currentDate = $this->getNextSemiMonthlyDate($currentDate);
        }

        // Calculate Annual Effective Yield (Periodic Rate * 24 periods * 100 for percentage)
        $effectiveYield = $rate * 24 * 100;

        // --- UPDATED RETURN STRUCTURE TO MATCH JS ---
        return [
            'success' => true,  // Required by JS
            'periodic_rate' => $rate,
            'effective_yield' => number_format($effectiveYield, 2), // Required by JS
            'total_interest' => $totalInterest,
            'schedule' => $rows // Required by JS (was previously 'rows')
        ];
    }

    /**
     * Determines the next 15th or End of Month.
     * @param \DateTime $date Starting date
     * @param int $minDaysOffset Minimum days to add (Grace Period buffer)
     */
    private function getNextSemiMonthlyDate(\DateTime $date, $minDaysOffset = 0) {
        $nextDate = clone $date;
        
        if ($minDaysOffset > 0) {
            $nextDate->modify("+$minDaysOffset days");
        }

        $day = (int)$nextDate->format('d');
        $year = (int)$nextDate->format('Y');
        $month = (int)$nextDate->format('m');

        if ($day <= 15) {
            // Move to 15th of this month
            $nextDate->setDate($year, $month, 15);
        } else {
            // Move to End of this month
            $nextDate->modify('last day of this month');
        }
        
        // Loop logic: If the calculated date is BEFORE or SAME as the starting date (due to offset logic), 
        // move to the next period.
        if ($nextDate <= $date) {
            if ($day <= 15) {
                // Move to End of Month
                $nextDate->modify('last day of this month');
            } else {
                // Move to 15th of NEXT month
                $nextDate->modify('first day of next month');
                $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 15);
            }
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

}