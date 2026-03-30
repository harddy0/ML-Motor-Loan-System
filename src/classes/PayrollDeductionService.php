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
                
                // Read exact date from EXCEL
                $dateStr = !empty($row['iso_date']) ? $row['iso_date'] : (!empty($row['date']) ? $row['date'] : date('Y-m-d'));
                $payrollDate = date('Y-m-d', strtotime($dateStr));

                // 1. STRICT ID MATCH PRIMARY
                $borrower = $this->findBorrower($empId, $fname, $lname);
                if (!$borrower) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower not found for ID: '{$empId}'.";
                    continue;
                }

                $actualEmpId = $borrower['employe_id'];
                
                // Active loan check
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    if ($this->hasVoidedLoan($actualEmpId)) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower ID {$actualEmpId} is VOIDED. Payment rejected.";
                    } else {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan found for Borrower ID {$actualEmpId}.";
                    }
                    continue;
                }

                $loanId = $loan['loan_id'];

                // 2. DUPLICATE PAYMENT GUARD
                $stmtDupe = $this->db->prepare("
                    SELECT COUNT(*) FROM Payroll_deductions
                    WHERE loan_id = ? AND deduction_date = ? AND match_status != 'VOIDED'
                ");
                $stmtDupe->execute([$loanId, $payrollDate]);
                if ($stmtDupe->fetchColumn() > 0) {
                    $results['errors'][] = "Row " . ($index + 1) . " Rejected: A deduction for ID {$actualEmpId} on {$payrollDate} was already recorded.";
                    continue;
                }

                // 3. TARGET RESOLUTION (STRICT DATE MATCHING)
                $targetLedger = null;
                $isAssumed = false;

                $earliestAssumed = $this->findEarliestAssumedLedger($loanId);

                if ($earliestAssumed) {
                    // STRICT DATE CHECK WITH FORMATTED ERROR
                    if ($earliestAssumed['scheduled_date'] !== $payrollDate) {
                        $formattedAssumedDate = date('m/d/Y', strtotime($earliestAssumed['scheduled_date']));
                        $formattedPayrollDate = date('m/d/Y', strtotime($payrollDate));
                        
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower ID {$actualEmpId} has a pending ASSUMED payment for {$formattedAssumedDate}. You must settle that exact date first before paying {$formattedPayrollDate}.";
                        continue;
                    }
                    $targetLedger = $earliestAssumed;
                    $isAssumed = true;
                } else {
                    $targetLedger = $this->findLedgerByExactDate($loanId, $payrollDate, 'UNPAID');
                    $isAssumed = false;
                }
                
                if ($targetLedger) {
                    $ledgerId = $targetLedger['ledger_id'];
                    $expectedAmount = (float)$targetLedger['total_payment'];
                    $variance = $amountPaid - $expectedAmount;
                    
                    // RULE 1: If lacking, reject entirely
                    if ($variance < -0.01) {
                        $lbl = $isAssumed ? "Priority ASSUMED" : "UNPAID";
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Payment lacking for {$lbl} balance. (Expected: ₱" . number_format($expectedAmount, 2) . ", Paid: ₱" . number_format($amountPaid, 2) . ")";
                        continue; 
                    }

                    // RULE 2: Exact or Excess is accepted
                    $remarks = $isAssumed ? "Priority ASSUMED cleared." : null;
                    if ($variance > 0.01) {
                        $remarks = ($remarks ? $remarks . " " : "") . "Excess payment of ₱" . number_format($variance, 2);
                        $results['discrepancies'][] = "ID {$actualEmpId} - Excess by ₱" . number_format($variance, 2);
                    }

                    // Update Ledger & Record Deduction
                    $this->updateLedgerStatus($ledgerId, $payrollDate, $remarks);
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');

                    // ✦✦✦ THE FIX: AUTO-CATCH UP FOR RECENT UNPAIDS ✦✦✦
                    // Triggered ONLY after the first ASSUMED payment is successfully paid
                    if ($isAssumed) {
                        // Use the real-world current date to check if UNPAID schedules have been missed
                        $realCurrentDate = date('Y-m-d'); 
                        $oldestUnpaid = $this->findOldestUnpaidLedger($loanId);
                        
                        while ($oldestUnpaid) {
                            $diffDays = $this->getDaysDifference($oldestUnpaid['scheduled_date'], $realCurrentDate);
                            
                            // If the UNPAID schedule is 10+ days older than TODAY, mark as NO DEDUCTION and shift
                            if ($diffDays >= 10) {
                                $this->processMissedLedger($loanId, $oldestUnpaid);
                                $oldestUnpaid = $this->findOldestUnpaidLedger($loanId); // Re-fetch
                            } else {
                                break; // "If all is paid then its good already"
                            }
                        }
                    }
                    // ✦✦✦ END FIX ✦✦✦
                    
                    $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                    
                    if (!$isAssumed) {
                        $snapshotsToGenerate[$loanId][] = $payrollDate;
                    }
                    
                    $results['success_count']++;

                } else {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No exact schedule match found for {$payrollDate} for ID {$actualEmpId}.";
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
            $results['errors'][] = "Processing failed due to a system issue: " . $e->getMessage();
            $results['success_count'] = 0;
        }

        return $results;
    }

    // =========================================================================
    // RESTORED MISSED LEDGER / DATE SHIFTING METHODS
    // =========================================================================

    private function getDaysDifference($scheduledDate, $compareToDate) {
        $sDateObj = new DateTime($scheduledDate);
        $pDateObj = new DateTime($compareToDate);
        return (int)$sDateObj->diff($pDateObj)->format('%R%a');
    }

    private function processMissedLedger($loanId, $missedLedger) {
        $missedDate = $missedLedger['scheduled_date'];
        $missedInstallmentNo = $missedLedger['installment_no'];

        // 1. Get Previous Balance
        $stmtPrev = $this->db->prepare("SELECT remaining_bal FROM Amortization_Ledger WHERE loan_id = ? AND installment_no < ? ORDER BY installment_no DESC LIMIT 1");
        $stmtPrev->execute([$loanId, $missedInstallmentNo]);
        $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        
        $prevBal = $prevRow ? $prevRow['remaining_bal'] : $this->getLoanAmount($loanId);

        // 2. Fetch ALL remaining UNPAID rows
        $stmtUnpaid = $this->db->prepare("SELECT ledger_id, scheduled_date, installment_no FROM Amortization_Ledger WHERE loan_id = ? AND status = 'UNPAID' ORDER BY installment_no DESC");
        $stmtUnpaid->execute([$loanId]);
        $unpaidRows = $stmtUnpaid->fetchAll(PDO::FETCH_ASSOC);

        $stmtUpdate = $this->db->prepare("UPDATE Amortization_Ledger SET scheduled_date = ?, installment_no = ? WHERE ledger_id = ?");
        $newMaturityDate = null;

        // 3. Shift every unpaid row forward
        foreach ($unpaidRows as $row) {
            $newDate = $this->getNextSemiMonthlyDate($row['scheduled_date']);
            $newInstNo = $row['installment_no'] + 1;
            
            $stmtUpdate->execute([$newDate, $newInstNo, $row['ledger_id']]);
            
            if ($newMaturityDate === null) $newMaturityDate = $newDate; 
        }

        // 4. Insert the DUMMY ZERO ROW for NO DEDUCTION
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

    private function getNextSemiMonthlyDate($dateStr) {
        $date  = new DateTime($dateStr);
        $day   = (int)$date->format('d');
        $year  = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $daysInMonth = (int)(new DateTime("$year-$month-01"))->format('t');

        if ($day == 15) {
            $targetDay = min(30, $daysInMonth);
            $date->setDate($year, $month, $targetDay);
        } else {
            $date->modify('first day of next month');
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), 15);
        }

        return $date->format('Y-m-d');
    }

    // =========================================================================
    // STANDARD HELPER METHODS
    // =========================================================================

    private function findEarliestAssumedLedger($loanId) {
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment, status 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'ASSUMED' 
            ORDER BY scheduled_date ASC LIMIT 1
        ");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    private function findLedgerByExactDate($loanId, $targetDate, $status) {
        $stmt = $this->db->prepare("
            SELECT ledger_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment, status 
            FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = ? AND scheduled_date = ?
            LIMIT 1
        ");
        $stmt->execute([$loanId, $status, $targetDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

        // STRICT ID MATCH PRIMARY
        if (!empty($id)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE employe_id = ? LIMIT 1");
            $stmt->execute([$id]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;
        }
        
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
        return $this->db->query("
            SELECT b.employe_id as id, DATE_FORMAT(pd.deduction_date, '%m/%d/%Y') as p_date,
                DATE_FORMAT(pd.deduction_date, '%Y-%m-%d') as raw_p_date, b.last_name as last,
                b.first_name as first, pd.amount, b.region_code,
                DATE_FORMAT(pd.imported_at, '%m/%d/%Y %h:%i %p') as i_date, 
                DATE_FORMAT(pd.imported_at, '%Y-%m-%d') as raw_i_date, pd.match_status
            FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id
            ORDER BY pd.deduction_id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginatedDeductions(int $page = 1, int $limit = 100, string $search = '', string $fromDate = '', string $toDate = '') {
        // [Existing pagination code unchanged]
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($search)) {
            $where .= " AND (b.employe_id LIKE :search OR b.first_name LIKE :search OR b.last_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if (!empty($fromDate)) { $where .= " AND DATE(pd.deduction_date) >= :from_date"; $params[':from_date'] = $fromDate; }
        if (!empty($toDate)) { $where .= " AND DATE(pd.deduction_date) <= :to_date"; $params[':to_date'] = $toDate; }

        $overallStmt = $this->db->query("SELECT COUNT(*), COALESCE(SUM(pd.amount), 0) FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id");
        [$total_overall, $total_amount_overall] = $overallStmt->fetch(\PDO::FETCH_NUM);

        $filteredStmt = $this->db->prepare("SELECT COUNT(*), COALESCE(SUM(pd.amount), 0) FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id $where");
        $filteredStmt->execute($params);
        [$total_filtered, $total_amount_filtered] = $filteredStmt->fetch(\PDO::FETCH_NUM);

        $dataSql = "
            SELECT b.employe_id AS id, DATE_FORMAT(pd.deduction_date, '%m/%d/%Y') AS p_date,
                DATE_FORMAT(pd.deduction_date, '%Y-%m-%d') AS raw_p_date, b.last_name AS last,
                b.first_name AS first, pd.amount, b.region_code,
                DATE_FORMAT(pd.imported_at, '%m/%d/%Y %h:%i %p') AS i_date,
                DATE_FORMAT(pd.imported_at, '%Y-%m-%d') AS raw_i_date, pd.match_status, l.pn_number
            FROM Payroll_deductions pd JOIN Borrowers b ON pd.employe_id = b.employe_id LEFT JOIN Loan l ON pd.loan_id = l.loan_id
            $where ORDER BY pd.deduction_id DESC LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC), 'total_overall' => (int)$total_overall,
            'total_filtered' => (int)$total_filtered, 'total_amount_overall' => (float)$total_amount_overall,
            'total_amount_filtered' => (float)$total_amount_filtered, 'total_pages' => (int) ceil((int)$total_filtered / $limit),
            'current_page' => $page,
        ];
    }

    /**
     * Bulk updates UNPAID ledgers to ASSUMED for a specific date range.
     */
    public function assumePaymentsForPeriod($startDate, $endDate, $userId = null) {
        try {
            $this->db->beginTransaction();

            // 1. Update the ledger
            $stmt = $this->db->prepare("
                UPDATE Amortization_Ledger al
                JOIN Loan l ON al.loan_id = l.loan_id
                SET al.status = 'ASSUMED'
                WHERE al.scheduled_date BETWEEN ? AND ? 
                AND al.status = 'UNPAID'
                AND l.current_status = 'ONGOING'
            ");
            $stmt->execute([$startDate, $endDate]);
            $affectedRows = $stmt->rowCount();

            // 2. Fetch affected loans to update AR
            $stmtLoans = $this->db->prepare("
                SELECT DISTINCT l.loan_id 
                FROM Amortization_Ledger al
                JOIN Loan l ON al.loan_id = l.loan_id
                WHERE al.scheduled_date BETWEEN ? AND ? 
                AND al.status = 'ASSUMED'
            ");
            $stmtLoans->execute([$startDate, $endDate]);
            $affectedLoans = $stmtLoans->fetchAll(PDO::FETCH_COLUMN);

            $this->db->commit();

            // 3. Trigger AR recalculation
            if (!empty($affectedLoans)) {
                $arService = new RunningReceivablesService($this->db);
                foreach ($affectedLoans as $loanId) {
                    if(method_exists($arService, 'generateSnapshot')) {
                        $arService->generateSnapshot($loanId, $endDate);
                    }
                }
            }

            return [
                'success' => true, 
                'message' => "Successfully assumed payments for {$affectedRows} records.",
                'affected_rows' => $affectedRows
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error assuming payments: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error while assuming payments.'];
        }
    }
}