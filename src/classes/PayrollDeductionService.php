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
            'errors' => []
        ];
        
        // Array to collect which loans and dates need RR snapshots updated
        $snapshotsToGenerate = [];

        foreach ($deductions as $index => $row) {
            try {
                $empId = !empty($row['id']) ? trim($row['id']) : null;
                $fname = trim($row['fname'] ?? '');
                $lname = trim($row['lname'] ?? '');
                $amountPaid = (float)str_replace([',', ' '], '', $row['amount'] ?? '0');
                $dateStr = !empty($row['date']) ? $row['date'] : date('Y-m-d');
                $payrollDate = date('Y-m-d', strtotime($dateStr));

                $borrower = $this->findBorrower($empId, $fname, $lname);
                if (!$borrower) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: Borrower not found.";
                    continue;
                }

                $actualEmpId = $borrower['employe_id'];
                $loan = $this->findActiveLoan($actualEmpId);
                
                if (!$loan) {
                    $results['errors'][] = "Row " . ($index + 1) . " Failed: No active loan for " . $borrower['first_name'] . " " . $borrower['last_name'];
                    continue;
                }

                $loanId = $loan['loan_id'];
                $ledger = $this->findNextPendingLedger($loanId);
                
                if ($ledger) {
                    $ledgerId = $ledger['ledger_id'];
                    $expectedAmount = (float)$ledger['total_payment'];
                    $variance = $amountPaid - $expectedAmount;

                    if ($variance < -0.01) {
                        $note = "Money is lacking by " . number_format(abs($variance), 2);
                    } elseif ($variance > 0.01) {
                        $note = "Money is excess by " . number_format($variance, 2);
                    } else {
                        $note = "Exact amount paid.";
                    }

                    $this->updateLedgerStatus($ledgerId, $payrollDate, $note);
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, $ledgerId, 'MATCHED');
                    
                    // 1. Check if the balance is fully settled
                    $this->checkAndUpdateLoanStatus($loanId, $payrollDate);
                    
                    // 2. Queue RR snapshot update 
                    $snapshotsToGenerate[$loanId][] = $payrollDate;
                    
                    $results['success_count']++;
                } else {
                    $this->recordDeduction($actualEmpId, $loanId, $payrollDate, $amountPaid, null, 'EXCEPTION');
                    $results['errors'][] = "Row " . ($index + 1) . " Warning: Logged as EXCEPTION (no pending schedules left).";
                    $results['success_count']++; 
                }
            } catch (Exception $e) {
                $results['errors'][] = "Row " . ($index + 1) . " DB Error: " . $e->getMessage();
            }
        }

        // Generate snapshots after the batch finishes
        if (!empty($snapshotsToGenerate)) {
            $rrService = new RunningReceivablesService($this->db);
            foreach ($snapshotsToGenerate as $lId => $dates) {
                // Ensure we only process unique cutoff dates for efficiency
                $uniqueDates = array_unique($dates);
                foreach ($uniqueDates as $pDate) {
                    $rrService->generateSnapshot($lId, $pDate);
                }
            }
        }

        return $results;
    }

    // [ADD THIS METHOD TO YOUR CLASS]
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
        // A. Match precisely by ID first
        if (!empty($id)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE employe_id = ? LIMIT 1");
            $stmt->execute([$id]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;
        }
        
        // B. Match by Exact Name
        if (!empty($fname) || !empty($lname)) {
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$fname, $lname]);
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($b) return $b;

            // C. Match by SWAPPED Name (Excel often puts Last Name in the First Name column)
            $stmt = $this->db->prepare("SELECT employe_id, first_name, last_name FROM Borrowers WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$lname, $fname]); // Swapped
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
        // Always gets the earliest pending schedule based on installment number
        $stmt = $this->db->prepare("SELECT ledger_id, total_payment FROM Amortization_Ledger WHERE loan_id = ? AND status = 'PENDING' ORDER BY installment_no ASC LIMIT 1");
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

    /**
     * Fetch all payroll deductions for the Reports page
     */
    /**
     * Fetch all payroll deductions for the Reports page
     */
    public function getAllDeductions() {
        $sql = "
            SELECT 
                b.employe_id as id, 
                DATE_FORMAT(pd.deduction_date, '%m/%d/%Y') as p_date,
                b.last_name as last,
                b.first_name as first,
                pd.amount,
                b.region,
                -- Use the new imported_at timestamp for the Date Imported column
                DATE_FORMAT(pd.imported_at, '%m/%d/%Y %h:%i %p') as i_date, 
                pd.match_status
            FROM Payroll_deductions pd
            JOIN Borrowers b ON pd.employe_id = b.employe_id
            ORDER BY pd.deduction_id DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

}