<?php
namespace App;

use PDO;
use Exception;
use DateTime; 

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
                
                // Format the incoming MM/DD/YYYY to the Database's YYYY-MM-DD
                $dateStr = !empty($row['date']) ? $row['date'] : date('Y-m-d');
                $payrollDate = date('Y-m-d', strtotime($dateStr));

                // 2. STRICT NAME & ID CHECK (ID First, Name fallback)
                $borrower = $this->findBorrower($empId, $fname, $lname);
                if (!$borrower) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower ID and Name mismatch or not found ($empId - $fname $lname).";
                    continue;
                }

                $actualEmpId = $borrower['employe_id'];
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    if ($this->hasVoidedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower {$borrower['first_name']} {$borrower['last_name']} is VOIDED. Payment rejected.";
                    } else {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan for {$borrower['first_name']} {$borrower['last_name']}";
                    }
                    continue;
                }

                $loanId = $loan['loan_id'];
                
                // 3. FIND LEDGER BY PAYROLL DATE
                $ledger = $this->findLedgerForPayroll($loanId, $payrollDate);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    $scheduledDate = $ledger['scheduled_date'];

                    // Calculate precise difference in days
                    $diffDays = $this->getDaysDifference($scheduledDate, $payrollDate);

                    // =========================================================================
                    // 4. PROCESS CURRENT PAYMENT
                    // =========================================================================
                    
                    $variance = $amountPaid - $expectedAmount;
                    $remarks = null;

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

                } else {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No UNPAID schedule matched the date $payrollDate for {$borrower['first_name']} {$borrower['last_name']}";
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
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $results['errors'][] = "Critical Database Error: " . $e->getMessage();
            $results['success_count'] = 0;
        }

        return $results;
    }

    private function getDaysDifference($scheduledDate, $payrollDate) {
        $sDateObj = new DateTime($scheduledDate);
        $pDateObj = new DateTime($payrollDate);
        return (int)$sDateObj->diff($pDateObj)->format('%R%a');
    }

    private function processMissedLedger($loanId, $missedLedger) {
        // FIXED: Using database ENUM 'NO DEDUCTION' instead of 'MISSED'
        $stmtMiss = $this->db->prepare("UPDATE Amortization_Ledger SET status = 'NO DEDUCTION', remarks = 'Missed payment auto-detected. Extended to end of term.' WHERE ledger_id = ?");
        $stmtMiss->execute([$missedLedger['ledger_id']]);

        $stmtLast = $this->db->prepare("SELECT installment_no, scheduled_date FROM Amortization_Ledger WHERE loan_id = ? ORDER BY installment_no DESC LIMIT 1");
        $stmtLast->execute([$loanId]);
        $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);

        $newInstallmentNo = $lastRow['installment_no'] + 1;
        $newDate = $this->getNextSemiMonthlyDate($lastRow['scheduled_date']);

        // FIXED: Appended row inserts with 'UNPAID' instead of 'PENDING'
        $stmtInsert = $this->db->prepare("
            INSERT INTO Amortization_Ledger (
                loan_id, installment_no, scheduled_date, 
                principal_amt, interest_amt, total_payment, 
                remaining_bal, status, is_extended, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'UNPAID', 1, ?)
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

        $stmtUpdateLoan = $this->db->prepare("UPDATE Loan SET maturity_date = ?, total_periods = total_periods + 1 WHERE loan_id = ?");
        $stmtUpdateLoan->execute([$newDate, $loanId]);
    }

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
        // FIXED: Checks for 'UNPAID' instead of 'PENDING'
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = ? AND status = 'UNPAID'");
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
                if (!empty($fname) && !empty($lname)) {
                    $dbFname = strtolower(trim($b['first_name']));
                    $dbLname = strtolower(trim($b['last_name']));
                    
                    if (($dbFname == $fname && $dbLname == $lname) || ($dbFname == $lname && $dbLname == $fname)) {
                        return $b;
                    }
                    return false; 
                }
                return $b;
            }
        }
        
        // B. Fallback to name
        if (!empty($fname) || !empty($lname)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE LOWER(first_name) = ? AND LOWER(last_name) = ? LIMIT 1");
            $stmt->execute([$fname, $lname]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;

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

    private function hasVoidedLoan($empId) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'VOIDED' LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // NEW: Matches ledger row by the Exact Date first, falls back to the oldest UNPAID
    private function findLedgerForPayroll($loanId, $payrollDate) {
        // 1. Exact Date Match
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'UNPAID' AND scheduled_date = ?
            LIMIT 1
        ");
        $stmt->execute([$loanId, $payrollDate]);
        $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exactMatch) {
            return $exactMatch;
        }

        // 2. Fallback: Get oldest UNPAID if the exact date is slightly off
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'UNPAID' 
            ORDER BY installment_no ASC LIMIT 1
        ");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateLedgerStatus($ledgerId, $datePaid, $remarks) {
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