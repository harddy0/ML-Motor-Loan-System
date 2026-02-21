<?php
namespace App;

use PDO;
use Exception;

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
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan for {$borrower['first_name']} {$borrower['last_name']}";
                    continue;
                }

                $loanId = $loan['loan_id'];
                $ledger = $this->findNextPendingLedger($loanId);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    $scheduledDate = $ledger['scheduled_date'];

                    // 3. STRICT DATE CHECK
                    if ($payrollDate !== $scheduledDate) {
                        $results['errors'][] = "Row " . ($index + 1) . " Failed: Payroll Date ($payrollDate) does NOT match Scheduled Date ($scheduledDate) for {$borrower['first_name']} {$borrower['last_name']}.";
                        continue;
                    }

                    $variance = $amountPaid - $expectedAmount;

                    if ($variance < -0.01) {
                        $note = "Money is lacking by ₱" . number_format(abs($variance), 2);
                        $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Lacking by ₱" . number_format(abs($variance), 2);
                    } elseif ($variance > 0.01) {
                        $note = "Money is excess by ₱" . number_format($variance, 2);
                        $results['discrepancies'][] = "{$borrower['first_name']} {$borrower['last_name']} - Excess by ₱" . number_format($variance, 2);
                    } else {
                        $note = "Exact amount paid.";
                    }

                    $this->updateLedgerStatus($ledgerId, $payrollDate, $note);
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');
                    
                    $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                    
                    $snapshotsToGenerate[$loanId][] = $payrollDate;
                    
                    $results['success_count']++;
                } else {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No pending payment schedules left for {$borrower['first_name']} {$borrower['last_name']}";
                }
            }

            // 4. TRANSACTION DECISION POINT
            if (count($results['errors']) > 0) {
                // If ANY errors occurred, cancel everything!
                $this->db->rollBack();
                $results['success_count'] = 0;
                $results['discrepancies'] = []; 
            } else {
                // No errors! Make the changes permanent.
                $this->db->commit();

                // 5. GENERATE SNAPSHOTS ONLY AFTER SUCCESSFUL COMMIT
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

    private function findNextPendingLedger($loanId) {
        $stmt = $this->db->prepare("SELECT ledger_id, total_payment, scheduled_date FROM Amortization_Ledger WHERE loan_id = ? AND status = 'PENDING' ORDER BY installment_no ASC LIMIT 1");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateLedgerStatus($ledgerId, $datePaid, $note) {
        $stmt = $this->db->prepare("UPDATE Amortization_Ledger SET status = 'PAID', date_paid = ?, payment_notes = ? WHERE ledger_id = ?");
        $stmt->execute([$datePaid, $note, $ledgerId]);
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
                DATE_FORMAT(pd.imported_at, '%Y-%m-%d') as raw_i_date, /* <-- ADDED FOR JS FILTERING */
                pd.match_status
            FROM Payroll_deductions pd
            JOIN Borrowers b ON pd.employe_id = b.employe_id
            ORDER BY pd.deduction_id DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}