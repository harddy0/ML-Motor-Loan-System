<?php
namespace App;

use PDO;
use Exception;
use DateTime; // Added for precise date math

class PayrollDeductionService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function processBatch(array $deductions) {
        $results = [
            'success_count' => 0,
            'errors' => [],
            'discrepancies' => [] 
        ];
        
        $snapshotsToGenerate = [];

        try {
            // 1. START TRANSACTION (All-or-Nothing)
            $this->db->beginTransaction();

            foreach ($deductions as $index => $row) {
                $empId = !empty($row['id']) ? trim($row['id']) : null;
                $fname = trim($row['fname'] ?? '');
                $lname = trim($row['lname'] ?? '');
                $amountPaid = (float)str_replace([',', ' '], '', $row['amount'] ?? '0');
                $dateStr = !empty($row['date']) ? $row['date'] : date('Y-m-d');
                $payrollDate = date('Y-m-d', strtotime($dateStr));

                // 2. STRICT NAME & ID CHECK
                $borrower = $this->findBorrower($empId, $fname, $lname);
                if (!$borrower) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower ID and Name mismatch or not found ($empId - $fname $lname).";
                    continue;
                }

                $actualEmpId = $borrower['employe_id'];
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    // --- NEW: Check if the loan is voided and reject with a specific warning ---
                    if ($this->hasVoidedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower {$borrower['first_name']} {$borrower['last_name']} is VOIDED. Payment rejected.";
                    } else {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan for {$borrower['first_name']} {$borrower['last_name']}";
                    }
                    continue;
                }

                $loanId = $loan['loan_id'];
                $ledger = $this->findNextPendingLedger($loanId);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    $scheduledDate = $ledger['scheduled_date'];

                    // Calculate precise difference in days (Negative = Early, Positive = Late)
                    $diffDays = $this->getDaysDifference($scheduledDate, $payrollDate);

                    // =========================================================================
                    // 3. MISSED PAYMENT DETECTION (Using the 10-Day Threshold)
                    // =========================================================================
                    // If the payroll date is 10 or more days PAST the expected date, 
                    // it clearly skipped into the next cutoff cycle. Mark as missed.
                    while ($diffDays >= 10) {
                        
                        // Action: Mark missed, append extension to end of loan, push maturity
                        $this->processMissedLedger($loanId, $ledger);

                        // Fetch the next pending ledger in line
                        $ledger = $this->findNextPendingLedger($loanId);
                        
                        if (!$ledger) {
                            break; // Edge case: No more ledgers left to skip
                        }

                        // Update loop variables
                        $ledgerId = $ledger['ledger_id'];
                        $expectedAmount = (float)$ledger['total_payment'];
                        $scheduledDate = $ledger['scheduled_date'];
                        
                        // Recalculate diff against the NEW ledger
                        $diffDays = $this->getDaysDifference($scheduledDate, $payrollDate);
                    }

                    // =========================================================================
                    // 4. PROCESS CURRENT PAYMENT (With Grace Window)
                    // =========================================================================
                    // We allow payments up to 7 days EARLY, and up to 9 days LATE.
                    if ($ledger && $diffDays >= -7 && $diffDays < 10) {
                        
                        $variance = $amountPaid - $expectedAmount;
                        $remarks = null; // Default to null when amount is exactly matched

                        if ($variance < -0.01) {
                            $remarks = "Money is lacking by ₱" . number_format(abs($variance), 2);
                            $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Lacking by ₱" . number_format(abs($variance), 2);
                        } elseif ($variance > 0.01) {
                            $remarks = "Money is excess by ₱" . number_format($variance, 2);
                            $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Excess by ₱" . number_format($variance, 2);
                        }

                        $this->updateLedgerStatus($ledgerId, $payrollDate, $remarks);
                        $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');
                        
                        $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                        
                        $snapshotsToGenerate[$loanId][] = $payrollDate;
                        $results['success_count']++;

                    } elseif ($ledger && $diffDays < -7) {
                        // Reject severely misaligned early dates (e.g., paying on the 1st for the 15th)
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Payroll Date ($payrollDate) is too EARLY. System expects ($scheduledDate) for {$borrower['first_name']} {$borrower['last_name']}.";
                    } else {
                         // Missing ledger entirely
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No pending payment schedules left for {$borrower['first_name']} {$borrower['last_name']}";
                    }

                } else {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No pending payment schedules left for {$borrower['first_name']} {$borrower['last_name']}";
                }
            }

            // 5. TRANSACTION DECISION POINT
            if (count($results['errors']) > 0) {
                // If ANY errors occurred, cancel everything!
                $this->db->rollBack();
                $results['success_count'] = 0;
                $results['discrepancies'] = []; 
            } else {
                // No errors! Make the changes permanent.
                $this->db->commit();

                // 6. GENERATE SNAPSHOTS ONLY AFTER SUCCESSFUL COMMIT
                if (!empty($snapshotsToGenerate)) {
                    $rrService = new RunningReceivablesService($this->db);
                    foreach ($snapshotsToGenerate as $lId => $dates) {
                        $uniqueDates = array_unique($dates);
                        foreach ($uniqueDates as $pDate) {
                            $rrService->generateSnapshot($lId, $pDate);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            // Cancel everything on fatal crash
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $results['errors'][] = "Critical Database Error: " . $e->getMessage();
            $results['success_count'] = 0;
        }

        return $results;
    }

    /**
     * Helper Function: Calculates the robust mathematical difference in days.
     * Returns NEGATIVE if payroll is early, POSITIVE if payroll is late.
     */
    private function getDaysDifference($scheduledDate, $payrollDate) {
        $sDateObj = new DateTime($scheduledDate);
        $pDateObj = new DateTime($payrollDate);
        return (int)$sDateObj->diff($pDateObj)->format('%R%a');
    }

    /**
     * Executes the Missed Payment Business Rules:
     * 1. Marks current row as missed.
     * 2. Appends an exact copy of P & I to the end of the term (no capitalization).
     * 3. Pushes the loan maturity date forward.
     */
    private function processMissedLedger($loanId, $missedLedger) {
        // 1. Mark skipped installment as MISSED (Changed payment_notes to remarks)
        $stmtMiss = $this->db->prepare("UPDATE Amortization_Ledger SET status = 'MISSED', remarks = 'Missed payment auto-detected. Extended to end of term.' WHERE ledger_id = ?");
        $stmtMiss->execute([$missedLedger['ledger_id']]);

        // 2. Get the current last row of the ledger to determine the new date and installment_no
        $stmtLast = $this->db->prepare("SELECT installment_no, scheduled_date FROM Amortization_Ledger WHERE loan_id = ? ORDER BY installment_no DESC LIMIT 1");
        $stmtLast->execute([$loanId]);
        $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);

        $newInstallmentNo = $lastRow['installment_no'] + 1;
        $newDate = $this->getNextSemiMonthlyDate($lastRow['scheduled_date']);

        // 3. Insert the appended row (is_extended = 1) (Changed payment_notes to remarks)
        // Remaining Balance is safely marked as 0 since this represents the final settling of the deferred principal
        $stmtInsert = $this->db->prepare("
            INSERT INTO Amortization_Ledger (
                loan_id, installment_no, scheduled_date, 
                principal_amt, interest_amt, total_payment, 
                remaining_bal, status, is_extended, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'PENDING', 1, ?)
        ");
        
        $stmtInsert->execute([
            $loanId, 
            $newInstallmentNo, 
            $newDate,
            $missedLedger['principal_amt'], 
            $missedLedger['interest_amt'], 
            $missedLedger['total_payment'],
            "Extension for missed installment #" . $missedLedger['installment_no']
        ]);

        // 4. Update the Master Loan Record to reflect the pushed maturity date and extra period
        $stmtUpdateLoan = $this->db->prepare("UPDATE Loan SET maturity_date = ?, total_periods = total_periods + 1 WHERE loan_id = ?");
        $stmtUpdateLoan->execute([$newDate, $loanId]);
    }

    /**
     * Determines the next 15th or End of Month exactly based on standard scheduling logic.
     */
    private function getNextSemiMonthlyDate($dateStr) {
        $date = new DateTime($dateStr);
        $day = (int)$date->format('d');

        if ($day == 15) {
            $date->modify('last day of this month');
        } else {
            $date->modify('first day of next month');
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), 15);
        }

        return $date->format('Y-m-d');
    }

    private function checkAndUpdateLoanStatus($loanId, $dateCompleted) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = ? AND status = 'PENDING'");
        $stmt->execute([$loanId]);
        $pendingCount = $stmt->fetchColumn();

        if ($pendingCount == 0) {
            $stmt = $this->db->prepare("UPDATE Loan SET current_status = 'FULLY PAID', date_completed = ? WHERE loan_id = ? AND current_status != 'FULLY PAID'");
            $stmt->execute([$dateCompleted, $loanId]);
        }
    }

    private function findBorrower($id, $fname, $lname) {
        $id = trim($id);
        $fname = trim(strtolower($fname));
        $lname = trim(strtolower($lname));

        // A. Match precisely by ID first
        if (!empty($id)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE employe_id = ? LIMIT 1");
            $stmt->execute([$id]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($b) {
                // If Excel provided names, STRICTLY verify they match the database
                if (!empty($fname) && !empty($lname)) {
                    $dbFname = strtolower(trim($b['first_name']));
                    $dbLname = strtolower(trim($b['last_name']));
                    
                    // Allow normal match or swapped names (First <-> Last)
                    if (($dbFname == $fname && $dbLname == $lname) || ($dbFname == $lname && $dbLname == $fname)) {
                        return $b;
                    }
                    // Name mismatch! Force a rejection.
                    return false; 
                }
                return $b;
            }
        }
        
        // B. Fallback to name only if ID is entirely missing from the file
        if (!empty($fname) || !empty($lname)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE LOWER(first_name) = ? AND LOWER(last_name) = ? LIMIT 1");
            $stmt->execute([$fname, $lname]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;

            // C. Match by SWAPPED Name
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE LOWER(first_name) = ? AND LOWER(last_name) = ? LIMIT 1");
            $stmt->execute([$lname, $fname]); 
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;
        }

        return false;
    }

    private function findActiveLoan($empId) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING' LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Helper Method: Checks if the borrower has a voided loan.
     * Used to provide a specific rejection reason in payroll processing.
     */
    private function hasVoidedLoan($empId) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'VOIDED' LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // UPDATED to pull all necessary monetary values to perfectly clone the missed row for the extension
    private function findNextPendingLedger($loanId) {
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'PENDING' 
            ORDER BY installment_no ASC LIMIT 1
        ");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateLedgerStatus($ledgerId, $datePaid, $remarks) {
        // Changed payment_notes to remarks
        $stmt = $this->db->prepare("UPDATE Amortization_Ledger SET status = 'PAID', date_paid = ?, remarks = ? WHERE ledger_id = ?");
        $stmt->execute([$datePaid, $remarks, $ledgerId]);
    }

    private function recordDeduction($empId, $loanId, $date, $amount, $ledgerId, $matchStatus) {
        $stmt = $this->db->prepare("INSERT INTO Payroll_deductions (employe_id, loan_id, deduction_date, amount, ledger_id, match_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$empId, $loanId, $date, $amount, $ledgerId, $matchStatus]);
    }

    public function getAllDeductions() {
        $sql = "
            SELECT 
                b.employe_id as id, 
                DATE_FORMAT(pd.deduction_date, '%m/%d/%Y') as p_date,
                b.last_name as last,
                b.first_name as first,
                pd.amount,
                b.region,
                DATE_FORMAT(pd.imported_at, '%m/%d/%Y %h:%i %p') as i_date, 
                DATE_FORMAT(pd.imported_at, '%Y-%m-%d') as raw_i_date,
                pd.match_status
            FROM Payroll_deductions pd
            JOIN Borrowers b ON pd.employe_id = b.employe_id
            ORDER BY pd.deduction_id DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}