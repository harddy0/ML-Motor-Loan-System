<?php
namespace App;

use PDO;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LedgerImportService {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function parseExcel($filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $accountName = $sheet->getCell('C2')->getValue();
            
            $nameParts = explode(' ', trim((string)$accountName));
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            $idNumber        = trim((string)$sheet->getCell('C3')->getValue());
            $referenceNumber = trim((string)$sheet->getCell('C4')->getValue());
            $region          = trim((string)$sheet->getCell('C5')->getValue());
            $branch          = trim((string)$sheet->getCell('C6')->getValue());
            $contact         = trim((string)$sheet->getCell('C7')->getValue());
            $pnNumber        = trim((string)$sheet->getCell('C8')->getValue());
            
            $loanAmountRaw   = $sheet->getCell('E8')->getValue();
            $dateReleasedRaw = $sheet->getCell('C9');
            $termsRaw        = $sheet->getCell('E9')->getValue();
            $interestFileRaw = $sheet->getCell('E10')->getValue();
            $maturityDateRaw = $sheet->getCell('C10');
            $amortizationRaw = $sheet->getCell('F11')->getValue();

            if (empty($idNumber) || empty($accountName)) {
                throw new Exception("Invalid file format: Missing ID Number or Account Name in C2/C3.");
            }

            // ==========================================
            // STRICT DUPLICATE VALIDATION
            // ==========================================
            
            // 1. Check if Employee already has an active loan
            $stmtCheckActive = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING'");
            $stmtCheckActive->execute([$idNumber]);
            if ($stmtCheckActive->fetchColumn()) {
                throw new Exception("Import Rejected: Employee ID {$idNumber} already has an ONGOING loan in the system.");
            }

            // 2. Check if Reference Number already exists
            if (!empty($referenceNumber)) {
                $stmtCheckRef = $this->db->prepare("SELECT loan_id FROM Loan WHERE loan_ref_no = ?");
                $stmtCheckRef->execute([$referenceNumber]);
                if ($stmtCheckRef->fetchColumn()) {
                    throw new Exception("Import Rejected: Loan Reference Number '{$referenceNumber}' already exists in the database.");
                }
            }

            // 3. Check if PN Number already exists
            if (!empty($pnNumber)) {
                $stmtCheckPn = $this->db->prepare("SELECT loan_id FROM Loan WHERE pn_number = ?");
                $stmtCheckPn->execute([$pnNumber]);
                if ($stmtCheckPn->fetchColumn()) {
                    throw new Exception("Import Rejected: PN Number '{$pnNumber}' already exists in the database.");
                }
            }

            // ==========================================

            $principal    = floatval(str_replace(['%', ','], '', (string)$loanAmountRaw));
            $deduction    = floatval(str_replace(['%', ','], '', (string)$amortizationRaw));
            $termsMonths  = intval(str_replace(['%', ','], '', (string)$termsRaw));
            $totalPeriods = $termsMonths * 2;
            
            $dateGranted  = $this->formatDate($dateReleasedRaw) ?: date('Y-m-d');
            $maturityDate = $this->formatDate($maturityDateRaw) ?: date('Y-m-d', strtotime('+'.$termsMonths.' months'));

            $periodicRate = $this->getPeriodicRate($principal, $deduction, $totalPeriods);
            $annualYield = $periodicRate * 24;
            $totalRepayment = $deduction * $totalPeriods;
            $totalInterest = $totalRepayment - $principal;
            
            $addOnRate = 0;
            if ($principal > 0) {
                $addOnRate = $totalInterest / $principal; 
            }

            $borrowerData = [
                'employe_id' => $idNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'reference_number' => $referenceNumber,
                'region' => $region ?: 'N/A',
                'branch' => $branch ?: 'N/A',
                'contact_number' => $contact ?: 'N/A',
                'pn_number' => $pnNumber,
                'loan_amount' => $principal,
                'date_released' => $dateGranted,
                'terms' => $termsMonths,
                'maturity_date' => $maturityDate,
                'file_interest_rate' => str_replace('%', '', (string)$interestFileRaw),
                'semi_monthly_amortization' => $deduction,
                'total_periods' => $totalPeriods,
                'periodic_rate' => $periodicRate,
                'annual_yield' => $annualYield,
                'add_on_rate' => $addOnRate,
                'total_interest' => $totalInterest
            ];

            $ledgerData = [];
            $row = 15;
            
            while (true) {
                $indexVal = trim((string)$sheet->getCell("A{$row}")->getValue());
                
                if ($indexVal === '' || !is_numeric($indexVal)) {
                    break;
                }

                $dateCell = $sheet->getCell("B{$row}");
                
                // STATUS PARSING
                $rawStatus = strtoupper(trim((string)$sheet->getCell("G{$row}")->getValue()));
                if ($rawStatus === 'PAID') {
                    $mappedStatus = 'PAID';
                } elseif (strpos($rawStatus, 'NO DEDUCTION') !== false) {
                    $mappedStatus = 'NO DEDUCTION';
                } else {
                    $mappedStatus = 'UNPAID';
                }

                $ledgerData[] = [
                    'installment_no' => intval($indexVal),
                    'date' => $this->formatDate($dateCell),
                    'principal' => floatval(str_replace(',', '', (string)$sheet->getCell("C{$row}")->getValue())),
                    'interest' => floatval(str_replace(',', '', (string)$sheet->getCell("D{$row}")->getValue())),
                    'total' => floatval(str_replace(',', '', (string)$sheet->getCell("E{$row}")->getValue())),
                    'balance' => floatval(str_replace(',', '', (string)$sheet->getCell("F{$row}")->getValue())),
                    'status' => $mappedStatus
                ];
                $row++;
            }

            return ['success' => true, 'borrower' => $borrowerData, 'ledger' => $ledgerData];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function saveImportedLedger($data) {
        try {
            $this->db->beginTransaction();
            $b = $data['borrower'];

            // Double Check Before Final Save (Prevents concurrent race conditions)
            $stmtCheckActive = $this->db->prepare("SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING'");
            $stmtCheckActive->execute([$b['employe_id']]);
            if ($stmtCheckActive->fetchColumn()) {
                throw new Exception("Save Failed: Employee ID {$b['employe_id']} already has an ONGOING loan.");
            }

            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, branch, region)
                VALUES (:eid, :fname, :lname, :contact, :branch, :region)
                ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    branch = VALUES(branch),
                    region = VALUES(region)
            ");
            
            $stmtBorrower->execute([
                ':eid' => $b['employe_id'],
                ':fname' => $b['first_name'],
                ':lname' => $b['last_name'],
                ':contact' => $b['contact_number'],
                ':branch' => $b['branch'],
                ':region' => $b['region']
            ]);

            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months, 
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt, 
                    pn_date, date_granted, maturity_date, current_status
                ) VALUES (
                    :eid, :ref, :pn, :amount, :addon, :terms, :periods, 
                    :periodic_rate, :annual_yield, :deduction, :granted, 
                    :granted, :maturity, 'ONGOING'
                )
            ");

            $stmtLoan->execute([
                ':eid' => $b['employe_id'],
                ':ref' => $b['reference_number'],
                ':pn' => $b['pn_number'],
                ':amount' => $b['loan_amount'],
                ':addon' => $b['add_on_rate'], 
                ':terms' => $b['terms'],
                ':periods' => $b['total_periods'],
                ':periodic_rate' => $b['periodic_rate'],
                ':annual_yield' => $b['annual_yield'],
                ':deduction' => $b['semi_monthly_amortization'],
                ':granted' => $b['date_released'],
                ':maturity' => $b['maturity_date']
            ]);

            $loanId = $this->db->lastInsertId();

            $stmtLedger = $this->db->prepare("
                INSERT INTO Amortization_Ledger (
                    loan_id, installment_no, scheduled_date, 
                    principal_amt, interest_amt, total_payment, 
                    remaining_bal, status, date_paid
                ) VALUES (
                    :lid, :no, :date, :princ, :int, :total, :bal, :status, :date_paid
                )
            ");

            foreach ($data['ledger'] as $row) {
                $datePaid = ($row['status'] === 'PAID') ? $row['date'] : null;
                
                $stmtLedger->execute([
                    ':lid' => $loanId,
                    ':no' => $row['installment_no'],
                    ':date' => $row['date'], 
                    ':princ' => $row['principal'],
                    ':int' => $row['interest'],
                    ':total' => $row['total'],
                    ':bal' => $row['balance'],
                    ':status' => $row['status'],
                    ':date_paid' => $datePaid
                ]);
            }

            $this->db->commit();
            return ['success' => true, 'loan_id' => $loanId];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getPeriodicRate($principal, $payment, $periods) {
        if ($principal <= 0 || $payment <= 0 || $periods <= 0) return 0;
        if (($payment * $periods) <= $principal) return 0;
        $low = 0; $high = 1; $guess = 0;
        for ($i = 0; $i < 100; $i++) { 
            $guess = ($low + $high) / 2;
            if ($guess <= 0) { $low = 0.0000001; continue; }
            $testPrincipal = $payment * (1 - pow(1 + $guess, -$periods)) / $guess;
            if (abs($testPrincipal - $principal) < 0.01) break; 
            if ($testPrincipal > $principal) { $low = $guess; } else { $high = $guess; }
        }
        return $guess;
    }

    private function formatDate($cell) {
        if (!$cell) return null;
        $val = trim((string)$cell->getValue());
        if ($val === '') return null;
        if (is_numeric($val)) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d'); } catch (Exception $e) {}
        }
        $timestamp = strtotime(str_replace('/', '-', $val));
        if ($timestamp !== false && $timestamp > 0) return date('Y-m-d', $timestamp);
        return null;
    }
}