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
                
                // Active loan check now ignores KPTN status
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    if ($this->hasVoidedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower {$borrower['first_name']} {$borrower['last_name']} is VOIDED. Payment rejected.";
                    } else {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan found for {$borrower['first_name']} {$borrower['last_name']}.";
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
                
                // 3. DUPLICATE PAYMENT GUARD
                // Check if a deduction for this exact loan and payroll date was already
                // recorded. If so, reject — do not silently slide it to the next schedule.
                $stmtDupe = $this->db->prepare("
                    SELECT COUNT(*) FROM Payroll_deductions
                    WHERE loan_id = ? AND deduction_date = ? AND match_status != 'VOIDED'
                ");
                $stmtDupe->execute([$loanId, $payrollDate]);
                if ($stmtDupe->fetchColumn() > 0) {
                    $results['errors'][] = "Row " . ($index + 1) . " Rejected: A payroll deduction for {$borrower['first_name']} {$borrower['last_name']} on this date was already recorded. Upload aborted to prevent double entry.";
                    continue;
                }

                // 4. FIND TARGET LEDGER (STRICT DATE MATCH REQUIRED)
                $ledger = $this->findLedgerForPayroll($loanId, $payrollDate);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    
                    // 5. PROCESS CURRENT PAYMENT
                    $variance = $amountPaid - $expectedAmount;
                    
                    // ✦ RULE 1: If lacking, reject entirely! (Hard Error)
                    if ($variance < -0.01) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Payment is lacking for {$borrower['first_name']} {$borrower['last_name']}. (Expected: ₱" . number_format($expectedAmount, 2) . ", Paid: ₱" . number_format($amountPaid, 2) . ")";
                        continue; 
                    }

                    // ✦ RULE 2: If exact or excess, accept it. If excess, show in info modal!
                    $remarks = null;
                    if ($variance > 0.01) {
                        $remarks = "Excess payment of ₱" . number_format($variance, 2);
                        $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Excess by ₱" . number_format($variance, 2);
                    }

                    $this->updateLedgerStatus($ledgerId, $payrollDate, $remarks);
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');
                    
                    $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                    
                    // Queue for snapshot processing (Unaffected)
                    $snapshotsToGenerate[$loanId][] = $payrollDate;
                    $results['success_count']++;

                } else {
                    // Update error message to clearly indicate a date mismatch
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Payroll date ({$payrollDate}) does not exactly match any UNPAID schedule for {$borrower['first_name']} {$borrower['last_name']}.";
                }
            }

            if (count($results['errors']) > 0) {
                $this->db->rollBack();
                $results['success_count'] = 0;
                $results['discrepancies'] = []; 
            } else {
                $this->db->commit();

                // Process Running Receivables Snapshots (Unaffected)
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

    // ✦ SEMI-MONTHLY DATE CALCULATOR — 15/30 CYCLE ✦
    private function getNextSemiMonthlyDate($dateStr) {
        $date  = new DateTime($dateStr);
        $day   = (int)$date->format('d');
        $year  = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $daysInMonth = (int)(new DateTime("$year-$month-01"))->format('t');

        // Removed legacy 10/25 adjustment per instructions.
        // It now assumes schedules strictly follow 15th/EOM rules based on the new borrower import.

        if ($day == 15) {
            // Next payroll → 30th (or last day if month has < 30 days)
            $targetDay = min(30, $daysInMonth);
            $date->setDate($year, $month, $targetDay);
        } else {
            // day == 30 or EOM → next payroll is the 15th of next month
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
        // 1. EXACT DATE MATCH ONLY
        // Removed the fallback to `findOldestUnpaidLedger`. 
        // This enforces strict matching so in-between dates (16, 17, 25) fail unless they match a schedule.
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'UNPAID' AND scheduled_date = ?
            LIMIT 1
        ");
        $stmt->execute([$loanId, $payrollDate]);
        $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $exactMatch ?: false; 
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
        $stmt = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING' LIMIT 1");
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


    // ============================================================
    // PAGINATED DEDUCTIONS — used by reports/deduction page
    // Export always calls getAllDeductions() — unaffected.
    // ============================================================
    public function getPaginatedDeductions(int $page = 1, int $limit = 100, string $search = '', string $fromDate = '', string $toDate = '') {

        $offset = ($page - 1) * $limit;
        $params = [];

        $where = 'WHERE 1=1';

        if (!empty($search)) {
            $where .= " AND (b.employe_id LIKE :search OR b.first_name LIKE :search OR b.last_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($fromDate)) {
            $where .= " AND DATE(pd.imported_at) >= :from_date";
            $params[':from_date'] = $fromDate;
        }

        if (!empty($toDate)) {
            $where .= " AND DATE(pd.imported_at) <= :to_date";
            $params[':to_date'] = $toDate;
        }

        // Total count (all records, unfiltered — matches the "Total Records" card)
        $countAllStmt = $this->db->query("SELECT COUNT(*) FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id");
        $total_overall = (int) $countAllStmt->fetchColumn();

        // Count matching the current filter
        $countSql = "SELECT COUNT(*) FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id $where";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total_filtered = (int) $countStmt->fetchColumn();

        // Paginated data
        $dataSql = "
            SELECT
                b.employe_id        AS id,
                DATE_FORMAT(pd.deduction_date, '%m/%d/%Y') AS p_date,
                b.last_name         AS last,
                b.first_name        AS first,
                pd.amount,
                b.region,
                DATE_FORMAT(pd.imported_at, '%m/%d/%Y %h:%i %p') AS i_date,
                DATE_FORMAT(pd.imported_at, '%Y-%m-%d')           AS raw_i_date,
                pd.match_status
            FROM Payroll_deductions pd
            JOIN Borrowers b ON pd.employe_id = b.employe_id
            $where
            ORDER BY pd.deduction_id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'data'           => $data,
            'total_overall'  => $total_overall,
            'total_filtered' => $total_filtered,
            'total_pages'    => (int) ceil($total_filtered / $limit),
            'current_page'   => $page,
        ];
    }

}