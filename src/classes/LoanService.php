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
     * Added $pnOffset to allow batch imports to generate sequential PNs in preview.
     */
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
        
        // Use the offset here
        $result['pn_number'] = $this->generatePnNumber($pnOffset);
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
            if (!empty($schedule['rows'])) {
                $lastRow = end($schedule['rows']); 
                $trueMaturityDate = $lastRow['date_obj'];
            } else {
                // For BATCH imports, the schedule is empty, so we use the maturity date from the payload
                $trueMaturityDate = date('Y-m-d', strtotime($data['pn_maturity']));
            }
            
            $addOnRateToSave = isset($data['add_on_rate_decimal']) ? floatval($data['add_on_rate_decimal']) : 0.015;
            
            $addOnRateToSave = isset($data['add_on_rate_decimal']) ? floatval($data['add_on_rate_decimal']) : 0.015;

            // --- STEP 3: Insert Loan Record (UPDATED FOR KPTN & DEPOSIT) ---
            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, uploaded_by_employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                    pn_date, date_granted, maturity_date, current_status,
                    entry_type, deposit_amount, kptn
                ) VALUES (
                    :eid, :uploader_id, :ref, :pn, :amount, :addon, :terms, :periods, 
                    :periodic_rate, :annual_yield, :deduction, :granted, 
                    :granted, :maturity, 'ONGOING',
                    'MANUAL', :deposit_amount, :kptn
                )
            ");

            $stmtLoan->execute([
                ':eid' => $data['employe_id'],
                ':uploader_id' => $data['uploaded_by_employe_id'] ?? null, 
                ':ref' => !empty($data['reference_number']) ? $data['reference_number'] : null,
                ':pn' => $pnNumber,
                ':amount' => $principal,
                ':addon' => $addOnRateToSave, 
                ':terms' => $termsMonths,
                ':periods' => $totalPeriods,
                ':periodic_rate' => $periodicRate,
                ':annual_yield' => $annualYield,  
                ':deduction' => $deduction,
                ':granted' => $data['loan_granted'],
                ':maturity' => $trueMaturityDate,
                ':deposit_amount' => $data['deposit_amount'] ?? 2500.00, // DEFAULT TO 2500
                ':kptn' => $data['kptn'] // BIND NEW KPTN VAR
            ]);

            $loanId = $this->db->lastInsertId();

            // --- STEP 4: Insert Amortization Ledger ONLY IF KPTN EXISTS ---
            if ($data['kptn'] !== null && !empty($schedule['rows'])) {
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
            }

            // --- STEP 5: Trigger Notification to specific roles ---
            if (isset($data['entry_type']) && $data['entry_type'] !== 'BATCH') {
                $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

                $this->notifyUsersOnLoanCreation(
                    $loanId, 
                    $data['uploaded_by_employe_id'] ?? null, 
                    $fullName, 
                    $pnNumber, 
                    ['ADMIN', 'REVIEWER'] 
                );
            }

            $this->db->commit();
            return ['success' => true, 'loan_id' => $loanId];

        } catch (\Exception $e) {
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
     */
    private function buildAmortizationTable($principal, $deduction, $rate, $periods, $dateGranted, $firstDeduction = null, $lastDeduction = null) {
        $rows = [];
        $balance = $principal;

        // --- FIRST DEDUCTION LOGIC ---
        if ($firstDeduction) {
            $currentDate = new \DateTime($firstDeduction);
            // Cap to 30th if it's the 31st
            if ((int)$currentDate->format('d') == 31) {
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 30);
            }
        } else {
            $currentDate = new \DateTime($dateGranted);
            // Manual entry fallback
            $day = (int)$currentDate->format('d');
            if ($day >= 11 && $day <= 25) {
                $currentDate->modify('last day of this month');
                // Cap to 30th if it's the 31st
                if ((int)$currentDate->format('d') == 31) {
                    $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 30);
                }
            } elseif ($day >= 26) {
                $currentDate->modify('first day of next month');
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
            } else {
                $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 15);
            }
        }

        $totalInterest = 0;

        for ($i = 1; $i <= $periods; $i++) {
            
            if ($i == $periods) {
                // LAST ROW FIX: Force balance to zero and snap to exact Last Deduction Date
                $principalPart = $balance;
                $interest = round($deduction - $principalPart, 2);
                if ($interest < 0) $interest = 0; 
                $balance = 0; 
                
                if ($lastDeduction) {
                    $currentDate = new \DateTime($lastDeduction);
                    // Cap to 30th if it's the 31st
                    if ((int)$currentDate->format('d') == 31) {
                        $currentDate->setDate((int)$currentDate->format('Y'), (int)$currentDate->format('m'), 30);
                    }
                }
            } else {
                // NORMAL ROWS
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
     * Smart Next Date: Detects 10th/25th vs 15th/EOM cycle
     */
    private function getNextSemiMonthlyDate(\DateTime $date) {
        $nextDate = clone $date;
        $day = (int)$nextDate->format('d');
        $month = (int)$nextDate->format('m');
        $year = (int)$nextDate->format('Y');

        if ($day == 10) {
            // Next is 25th
            $nextDate->setDate($year, $month, 25);
        } elseif ($day == 25) {
            // Next is 10th of next month
            $nextDate->modify('first day of next month');
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 10);
        } elseif ($day == 15) {
            // Next is End of Month
            $nextDate->modify('last day of this month');
            // Cap to 30th if it's the 31st
            if ((int)$nextDate->format('d') == 31) {
                $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 30);
            }
        } else {
            // Assume End of Month (28, 29, 30). Next is 15th of next month
            $nextDate->modify('first day of next month');
            $nextDate->setDate((int)$nextDate->format('Y'), (int)$nextDate->format('m'), 15);
        }
        return $nextDate;
    }

    /**
     * Helper: Generate sequential PN Number: PN-YYYY-XXXX
     * Accepts an offset so bulk imports can simulate sequence before saving
     */
    private function generatePnNumber($offset = 0) {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(loan_id) FROM Loan WHERE YEAR(date_granted) = ?");
        $stmt->execute([$year]);
        
        // Add the offset to the count to simulate sequence
        $count = $stmt->fetchColumn() + 1 + $offset;
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
                l.loan_ref_no as reference_no,
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
            WHERE l.kptn IS NOT NULL -- FIX: Only show loans with KPTN attached
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
                WHERE l.kptn IS NOT NULL -- FIX: Only include loans with KPTN attached
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

            // FIX: Changed voided_by_user_id to voided_by_employe_id to match the DB schema
            $stmtLoan = $this->db->prepare("
                UPDATE Loan 
                SET current_status = 'VOIDED', 
                    voided_at = CURRENT_TIMESTAMP, 
                    voided_by_employe_id = ?, 
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

    /**
     * Broadcasts a notification to specific user roles when a loan is added.
     * @param array $targetRoles Array of user_types to notify, e.g., ['ADMIN', 'MANAGER']
     */
    private function notifyUsersOnLoanCreation($loanId, $triggeredByEmployeId, $borrowerName, $pnNumber, $targetRoles = ['ADMIN']) {
        if (empty($targetRoles)) return;

        // Dynamically create the placeholders for the IN clause (?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($targetRoles), '?'));
        
        // Fetch all active users matching the target roles
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

        // STRICT NULL CASTING: Prevents Foreign Key constraint errors if the session ID is empty
        $cleanTriggeredBy = !empty($triggeredByEmployeId) ? $triggeredByEmployeId : null;

        foreach ($recipients as $recipientId) {
            // I HAVE REMOVED THE "SKIP" CONDITION HERE.
            // Now, even if you are the Admin uploading the loan, you will still receive the notification.
            
            $insertStmt->execute([$recipientId, $cleanTriggeredBy, $loanId, $message]);
        }
    }

    public function getPendingKptnLoans() {
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
                l.semi_monthly_amt as deduction
            FROM Borrowers b
            JOIN Loan l ON b.employe_id = l.employe_id
            WHERE l.kptn IS NULL
            ORDER BY l.date_granted DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. ADD THIS NEW METHOD to activate the loan and generate the ledger
    public function activateBatchLoan($loanId, $kptnCode, $verifiedByEmployeId = null) {
        // Fetch loan AND borrower details needed for schedule & notification
        $stmt = $this->db->prepare("
            SELECT l.loan_amount, l.semi_monthly_amt, l.term_months, l.date_granted, l.periodic_rate, l.pn_number,
                   b.first_name, b.last_name
            FROM Loan l
            JOIN Borrowers b ON l.employe_id = b.employe_id
            WHERE l.loan_id = ? AND l.kptn IS NULL
        ");
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$loan) {
            throw new Exception("Pending loan not found or already activated.");
        }

        // Rebuild the schedule sequence using existing logic
        $scheduleData = $this->buildAmortizationTable(
            floatval($loan['loan_amount']),
            floatval($loan['semi_monthly_amt']),
            floatval($loan['periodic_rate']),
            intval($loan['term_months']) * 2,
            $loan['date_granted']
        );

        try {
            $this->db->beginTransaction();

            // Attach the KPTN code
            $upd = $this->db->prepare("UPDATE Loan SET kptn = ? WHERE loan_id = ?");
            $upd->execute([$kptnCode, $loanId]);

            // Insert Amortization Ledger
            $stmtLedger = $this->db->prepare("
                INSERT INTO Amortization_Ledger (
                    loan_id, installment_no, scheduled_date, 
                    principal_amt, interest_amt, total_payment, 
                    remaining_bal, status
                ) VALUES (
                    :lid, :no, :date, :princ, :int, :total, :bal, 'UNPAID'
                )
            ");

            foreach ($scheduleData['schedule'] as $row) {
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

            // --- TRIGGER NOTIFICATION UPON BATCH ACTIVATION ---
            $fullName = trim($loan['first_name'] . ' ' . $loan['last_name']);
            $this->notifyUsersOnLoanCreation(
                $loanId, 
                $verifiedByEmployeId, 
                $fullName, 
                $loan['pn_number'], 
                ['ADMIN', 'REVIEWER'] 
            );

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}