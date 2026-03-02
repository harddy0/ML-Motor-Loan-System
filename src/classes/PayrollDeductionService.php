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
            $this->db->beginTransaction();

            foreach ($deductions as $index => $row) {
                $empId = !empty($row['id']) ? trim($row['id']) : null;
                $fname = trim($row['fname'] ?? '');
                $lname = trim($row['lname'] ?? '');
                $amountPaid = (float)str_replace([',', ' '], '', $row['amount'] ?? '0');
                
                $dateStr = !empty($row['date']) ? $row['date'] : date('Y-m-d');
                $payrollDate = date('Y-m-d', strtotime($dateStr));

                // 1. STRICT ID/NAME CHECK
                $borrower = $this->findBorrower($empId, $fname, $lname);
                if (!$borrower) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower ID and Name mismatch or not found ($empId - $fname $lname).";
                    continue;
                }

                $actualEmpId = $borrower['employe_id'];
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    if ($this->hasUnverifiedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower {$borrower['first_name']} {$borrower['last_name']} has a pending loan that is NOT verified yet (Missing KPTN receipt). Payment rejected.";
                    } elseif ($this->hasVoidedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower {$borrower['first_name']} {$borrower['last_name']} is VOIDED. Payment rejected.";
                    } else {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No active verified loan for {$borrower['first_name']} {$borrower['last_name']}.";
                    }
                    continue;
                }

                $loanId = $loan['loan_id'];

                // 2. AUTO-CATCH UP: PROCESS MISSED PAYMENTS (DATE SHIFTING)
                $oldestUnpaid = $this->findOldestUnpaidLedger($loanId);
                
                while ($oldestUnpaid) {
                    $diffDays = $this->getDaysDifference($oldestUnpaid['scheduled_date'], $payrollDate);
                    
                    // If the expected schedule is 10 or more days OLDER than the payroll date, 
                    // it means it was missed. Shift the schedule forward!
                    if ($diffDays >= 10) {
                        $this->processMissedLedger($loanId, $oldestUnpaid);
                        $oldestUnpaid = $this->findOldestUnpaidLedger($loanId); // Re-fetch after the shift
                    } else {
                        break; // Schedule has caught up to the payroll date
                    }
                }
                
                // 3. FIND TARGET LEDGER
                $ledger = $this->findLedgerForPayroll($loanId, $payrollDate);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    
                    // 4. PROCESS CURRENT PAYMENT
                    $variance = $amountPaid - $expectedAmount;
                    
                    // ✦ RULE 1: If lacking, reject entirely! (Hard Error)
                    if ($variance < -0.01) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Payment is lacking for {$borrower['first_name']} {$borrower['last_name']}. (Expected: ₱" . number_format($expectedAmount, 2) . ", Paid: ₱" . number_format($amountPaid, 2) . ")";
                        continue; 
                    }

                    // ✦ RULE 2: If exact or excess, accept it. If excess, show in info modal!
                    $remarks = null;
                    if ($variance > 0.01) {
                        $remarks = "Money is excess by ₱" . number_format($variance, 2);
                        $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Excess by ₱" . number_format($variance, 2);
                    }

                    $this->updateLedgerStatus($ledgerId, $payrollDate, $remarks);
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');
                    
                    $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                    
                    $snapshotsToGenerate[$loanId][] = $payrollDate;
                    $results['success_count']++;

                } else {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No UNPAID schedule left for {$borrower['first_name']} {$borrower['last_name']}";
                }
            }

            if (count($results['errors']) > 0) {
                $this->db->rollBack();
                $results['success_count'] = 0;
                $results['discrepancies'] = []; 
            } else {
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

    // ✦ NEW DATE SHIFTING ALGORITHM ✦
    private function processMissedLedger($loanId, $missedLedger) {
        $missedDate = $missedLedger['scheduled_date'];
        $missedInstallmentNo = $missedLedger['installment_no'];

        // 1. Get Previous Balance (so the zero-row maintains the balance cascade)
        $stmtPrev = $this->db->prepare("SELECT remaining_bal FROM Amortization_Ledger WHERE loan_id = ? AND installment_no < ? ORDER BY installment_no DESC LIMIT 1");
        $stmtPrev->execute([$loanId, $missedInstallmentNo]);
        $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        
        $prevBal = $prevRow ? $prevRow['remaining_bal'] : $this->getLoanAmount($loanId);

        // 2. Fetch ALL remaining UNPAID rows (we process DESC to avoid constraint collisions)
        $stmtUnpaid = $this->db->prepare("SELECT ledger_id, scheduled_date, installment_no FROM Amortization_Ledger WHERE loan_id = ? AND status = 'UNPAID' ORDER BY installment_no DESC");
        $stmtUnpaid->execute([$loanId]);
        $unpaidRows = $stmtUnpaid->fetchAll(PDO::FETCH_ASSOC);

        $stmtUpdate = $this->db->prepare("UPDATE Amortization_Ledger SET scheduled_date = ?, installment_no = ? WHERE ledger_id = ?");
        $newMaturityDate = null;

        // 3. Shift every unpaid row forward by 1 period and +1 installment_no
        foreach ($unpaidRows as $row) {
            $newDate = $this->getNextSemiMonthlyDate($row['scheduled_date']);
            $newInstNo = $row['installment_no'] + 1;
            
            $stmtUpdate->execute([$newDate, $newInstNo, $row['ledger_id']]);
            
            // Capture the absolute latest date for maturity tracking
            if ($newMaturityDate === null) $newMaturityDate = $newDate; 
        }

        // 4. Insert the DUMMY ZERO ROW in the spot that was just vacated
        // MODIFICATION: Set remarks to NULL to remove the "Auto-shifted" note
        $stmtInsert = $this->db->prepare("
            INSERT INTO Amortization_Ledger (
                loan_id, installment_no, scheduled_date, 
                principal_amt, interest_amt, total_payment, 
                remaining_bal, status, is_extended, remarks
            ) VALUES (?, ?, ?, 0, 0, 0, ?, 'NO DEDUCTION', 1, NULL)
        ");
        
        $stmtInsert->execute([$loanId, $missedInstallmentNo, $missedDate, $prevBal]);

        // 5. Extend Loan Maturity
        if ($newMaturityDate) {
            $stmtUpdateLoan = $this->db->prepare("UPDATE Loan SET maturity_date = ?, total_periods = total_periods + 1 WHERE loan_id = ?");
            $stmtUpdateLoan->execute([$newMaturityDate, $loanId]);
        }
    }

    private function getLoanAmount($loanId) {
        $stmt = $this->db->prepare("SELECT loan_amount FROM Loan WHERE loan_id = ?");
        $stmt->execute([$loanId]);
        return $stmt->fetchColumn() ?: 0;
    }

    // ✦ DYNAMIC 10/25 & 15/EOM CALCULATOR ✦
    private function getNextSemiMonthlyDate($dateStr) {
        $date = new DateTime($dateStr);
        $day = (int)$date->format('d');
        $month = (int)$date->format('m');
        $year = (int)$date->format('Y');

        if ($day == 10) {
            $date->setDate($year, $month, 25);
        } elseif ($day == 25) {
            $date->modify('first day of next month');
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), 10);
        } elseif ($day == 15) {
            $date->modify('last day of this month');
        } else {
            // Treat 28, 29, 30, 31 as EOM -> push to next 15th
            $date->modify('first day of next month');
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), 15);
        }

        return $date->format('Y-m-d');
    }

    private function findOldestUnpaidLedger($loanId) {
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'UNPAID' 
            ORDER BY installment_no ASC LIMIT 1
        ");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
        
        if ($exactMatch) return $exactMatch;

        // 2. Fallback: Get oldest UNPAID
        return $this->findOldestUnpaidLedger($loanId);
    }

    private function checkAndUpdateLoanStatus($loanId, $dateCompleted) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = ? AND status = 'UNPAID'");
        $stmt->execute([$loanId]);
        
        if ($stmt->fetchColumn() == 0) {
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
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING' AND kptn IS NOT NULL LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function hasVoidedLoan($empId) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'VOIDED' LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
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

    private function hasUnverifiedLoan($empId) {
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND kptn IS NULL LIMIT 1");
        $stmt->execute([$empId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

}