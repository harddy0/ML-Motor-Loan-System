<?php
namespace App;

use PDO;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LedgerImportService {
    private $db;
    private $db2;
    public function __construct(PDO $db, PDO $db2 = null) {
        $this->db = $db;
        $this->db2 = $db2;
    }

public function parseExcel($filePath) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $accountName     = trim((string) $sheet->getCell('C2')->getValue());
            $contact         = trim((string) $sheet->getCell('C3')->getValue());
            $idNumber        = trim((string) $sheet->getCell('C4')->getValue());
            $referenceNumber = trim((string) $sheet->getCell('C5')->getValue());
            $region          = trim((string) $sheet->getCell('C6')->getValue());
            $branch          = trim((string) $sheet->getCell('C7')->getValue());
            $pnNumber        = trim((string) $sheet->getCell('C8')->getValue());

            $loanAmountRaw   = $sheet->getCell('E8')->getValue();
            $dateReleasedRaw = $sheet->getCell('C9');
            $termsRaw        = $sheet->getCell('E9')->getValue();
            $interestFileRaw = $sheet->getCell('E10')->getValue();
            $maturityDateRaw = $sheet->getCell('C10');
            $amortizationRaw = $sheet->getCell('F11')->getValue();

            // ── Name Splitting ─────────────────────────────────────────────────
            $nameParts = explode(' ', $accountName);
            $lastName  = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            // 🌟 CAPTURE RAW NAMES FOR THE UI PREVIEW BEFORE VALIDATION 🌟
            $regionNameForUI = $region;
            $branchNameForUI = $branch;

            // ── Validation ─────────────────────────────────────────────────────
            if (empty($accountName) && empty($idNumber)) {
                throw new Exception("Invalid file format: Missing Account Name (C2) and ID Number (C4).");
            }

            if (empty($accountName)) {
                throw new Exception("Invalid file format: Missing Account Name in C2.");
            }

            if (empty($idNumber)) {
                throw new Exception("Invalid file format: Missing ID Number in C4.");
            }

            if (empty($referenceNumber) || strtolower($referenceNumber) === 'n/a') {
                throw new Exception("Import Rejected: Reference Number is missing or invalid.");
            }

            // ── Region and Branch Validation ───────────────────────────────────
            $masterService = new \App\MasterDataService($this->db, $this->db2);

            if (!empty($region) && strtoupper($region) !== 'N/A') {
                $regionCode = $masterService->getRegionCodeByName($region);
                if (!$regionCode) {
                    throw new Exception("Import Rejected: Region '{$region}' does not exist in the system.");
                }
                $region = $regionCode; // Converts to ID for saving
            }

            if (!empty($branch) && strtoupper($branch) !== 'N/A') {
                $branchId = $masterService->getBranchIdByName($branch);
                if (!$branchId) {
                    throw new Exception("Import Rejected: We couldn't find the branch '{$branch}'. Please check the branch name and try again.");
                }
                $branch = $branchId; // Converts to ID for saving
            }

            // ── Duplicate Checks ───────────────────────────────────────────────
            $stmtCheckActive = $this->db->prepare(
                "SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING'"
            );
            $stmtCheckActive->execute([$idNumber]);
            if ($stmtCheckActive->fetchColumn()) {
                throw new Exception("Import Rejected: Employee ID {$idNumber} already has an ONGOING loan in the system.");
            }

            $stmtCheckRef = $this->db->prepare(
                "SELECT loan_id FROM Loan WHERE loan_ref_no = ? AND current_status != 'VOIDED'"
            );
            $stmtCheckRef->execute([$referenceNumber]);
            if ($stmtCheckRef->fetchColumn()) {
                throw new Exception("Import Rejected: Loan Reference Number '{$referenceNumber}' already exists.");
            }

            if (!empty($pnNumber)) {
                $stmtCheckPn = $this->db->prepare(
                    "SELECT loan_id FROM Loan WHERE pn_number = ? AND current_status != 'VOIDED'"
                );
                $stmtCheckPn->execute([$pnNumber]);
                if ($stmtCheckPn->fetchColumn()) {
                    throw new Exception("Import Rejected: PN Number '{$pnNumber}' already exists.");
                }
            }

            // ── Numeric Parsing ────────────────────────────────────────────────
            $principal    = floatval(str_replace(['%', ','], '', (string) $loanAmountRaw));
            $deduction    = floatval(str_replace(['%', ','], '', (string) $amortizationRaw));
            $termsMonths  = intval(str_replace(['%', ','], '', (string) $termsRaw));
            $totalPeriods = $termsMonths * 2;

            $dateGranted  = $this->formatDate($dateReleasedRaw) ?: $this->enforceDay30(date('Y-m-d'));
            $maturityDate = $this->formatDate($maturityDateRaw) ?: $this->enforceDay30(date('Y-m-d', strtotime('+' . $termsMonths . ' months')));

            $periodicRate   = $this->getPeriodicRate($principal, $deduction, $totalPeriods);
            $annualYield    = $periodicRate * 24;
            $totalRepayment = $deduction * $totalPeriods;
            $totalInterest  = $totalRepayment - $principal;

            $addOnRate = 0;
            if ($principal > 0 && $termsMonths > 0) {
                $addOnRate = ($totalInterest / $principal) / $termsMonths;
            }

            // ── Borrower Data ──────────────────────────────────────────────────
            $borrowerData = [
                'employe_id'                => $idNumber,
                'first_name'                => $firstName,
                'last_name'                 => $lastName,
                'reference_number'          => $referenceNumber,
                'region'                    => $region ?: 'N/A',            // ID for DB
                'region_name'               => $regionNameForUI ?: 'N/A',   // Raw string for UI
                'branch'                    => $branch ?: 'N/A',            // ID for DB
                'branch_name'               => $branchNameForUI ?: 'N/A',   // Raw string for UI
                'contact_number'            => $contact ?: 'N/A',
                'pn_number'                 => $pnNumber,
                'possible_pn_number'        => !empty($pnNumber) ? $pnNumber : $this->generatePnNumber(),
                'loan_amount'               => $principal,
                'date_released'             => $dateGranted,
                'terms'                     => $termsMonths,
                'maturity_date'             => $maturityDate,
                'file_interest_rate'        => str_replace('%', '', (string) $interestFileRaw),
                'semi_monthly_amortization' => $deduction,
                'total_periods'             => $totalPeriods,
                'periodic_rate'             => $periodicRate,
                'annual_yield'              => $annualYield,
                'add_on_rate'               => $addOnRate,
                'total_interest'            => $totalInterest,
            ];

            // ── Ledger Rows (starts at row 15) ────────────────────────────────
            $ledgerData = [];
            $row = 15;

            while (true) {
                $indexVal = trim((string) $sheet->getCell("A{$row}")->getValue());

                if ($indexVal === '' || !is_numeric($indexVal)) {
                    break;
                }

                $dateCell = $sheet->getCell("B{$row}");

                $statusG = strtoupper(trim(str_replace("'", '', (string) $sheet->getCell("G{$row}")->getValue())));
                $statusL = strtoupper(trim(str_replace("'", '', (string) $sheet->getCell("L{$row}")->getValue())));

                $rawStatus = '';
                if ($statusG !== '' && !is_numeric($statusG)) {
                    $rawStatus = $statusG;
                } elseif ($statusL !== '') {
                    $rawStatus = $statusL;
                }

                if ($rawStatus === 'PAID') {
                    $mappedStatus = 'PAID';
                } elseif (strpos($rawStatus, 'NO DEDUCTION') !== false) {
                    $mappedStatus = 'NO DEDUCTION';
                } else {
                    $mappedStatus = 'UNPAID';
                }

                $ledgerData[] = [
                    'installment_no' => intval($indexVal),
                    'date'           => $this->formatDate($dateCell),
                    'principal'      => floatval(str_replace(',', '', (string) $sheet->getCell("C{$row}")->getValue())),
                    'interest'       => floatval(str_replace(',', '', (string) $sheet->getCell("D{$row}")->getValue())),
                    'total'          => floatval(str_replace(',', '', (string) $sheet->getCell("E{$row}")->getValue())),
                    'balance'        => floatval(str_replace(',', '', (string) $sheet->getCell("F{$row}")->getValue())),
                    'status'         => $mappedStatus,
                ];
                $row++;
            }

            if (!empty($ledgerData)) {
                $lastLedgerRow = end($ledgerData);
                if (!empty($lastLedgerRow['date'])) {
                    $borrowerData['maturity_date'] = $lastLedgerRow['date'];
                }
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

            $stmtCheckActive = $this->db->prepare(
                "SELECT loan_id FROM Loan WHERE employe_id = ? AND current_status = 'ONGOING'"
            );
            $stmtCheckActive->execute([$b['employe_id']]);
            if ($stmtCheckActive->fetchColumn()) {
                throw new Exception("Save Failed: Employee ID {$b['employe_id']} already has an ONGOING loan. A new loan can only be created once the existing loan is fully paid.");
            }

            // UPDATED: Use branch_id and region_code
            $stmtBorrower = $this->db->prepare("
                INSERT INTO Borrowers (employe_id, first_name, last_name, contact_number, branch_id, region_code)
                VALUES (:eid, :fname, :lname, :contact, :branch_id, :region_code)
                ON DUPLICATE KEY UPDATE 
                    first_name     = VALUES(first_name),
                    last_name      = VALUES(last_name),
                    contact_number = VALUES(contact_number),
                    branch_id      = VALUES(branch_id),
                    region_code    = VALUES(region_code)
            ");

            $stmtBorrower->execute([
                ':eid'         => $b['employe_id'],
                ':fname'       => $b['first_name'],
                ':lname'       => $b['last_name'],
                ':contact'     => $b['contact_number'],
                ':branch_id'   => $b['branch'], 
                ':region_code' => $b['region']  
            ]);

            $uploaderId   = $data['uploaded_by_employe_id'] ?? null;
            $requiresKptn = isset($data['requires_kptn']) ? filter_var($data['requires_kptn'], FILTER_VALIDATE_BOOLEAN) : false;
            $kptnCode     = !empty($data['kptn_code']) ? trim($data['kptn_code']) : null;

            $pnToSave = !empty($b['pn_number']) ? trim($b['pn_number']) : $this->generatePnNumber();

            if (!$requiresKptn) {
                $depositAmount = 0.00;
                $kptnToSave    = uniqid('NR_');
            } else {
                $depositAmount = isset($data['deposit_amount']) ? floatval($data['deposit_amount']) : 0.00;
                $kptnToSave    = $kptnCode; 
            }

            $totalInterestAmount = isset($b['total_interest']) ? (float)$b['total_interest'] : 0.00;
            $grossLoanAmount = (float)$b['loan_amount'] + $totalInterestAmount;

            $stmtLoan = $this->db->prepare("
                INSERT INTO Loan (
                    employe_id, uploaded_by_employe_id, loan_ref_no, pn_number, loan_amount, add_on_rate, term_months,
                    total_periods, periodic_rate, annual_yield, semi_monthly_amt,
                    total_interest_amount, gross_loan_amount,
                    pn_date, date_granted, maturity_date, current_status,
                    entry_type, requires_kptn, deposit_amount, kptn
                ) VALUES (
                    :eid, :uploader_id, :ref, :pn, :amount, :addon, :terms, :periods,
                    :periodic_rate, :annual_yield, :deduction,
                    :total_interest, :gross_amount,
                    :granted, :granted, :maturity, 'ONGOING',
                    'BATCH', :requires_kptn, :deposit_amount, :kptn
                )
            ");

            $stmtLoan->execute([
                ':eid'            => $b['employe_id'],
                ':uploader_id'    => $uploaderId,
                ':ref'            => $b['reference_number'],
                ':pn'             => $pnToSave,
                ':amount'         => $b['loan_amount'],
                ':addon'          => $b['add_on_rate'],
                ':terms'          => $b['terms'],
                ':periods'        => $b['total_periods'],
                ':periodic_rate'  => $periodicRate ?? $b['periodic_rate'], 
                ':annual_yield'   => $annualYield ?? $b['annual_yield'],
                ':deduction'      => $b['semi_monthly_amortization'],
                ':total_interest' => $totalInterestAmount, 
                ':gross_amount'   => $grossLoanAmount,     
                ':granted'        => $b['date_released'],
                ':maturity'       => $b['maturity_date'],
                ':requires_kptn'  => $requiresKptn ? 1 : 0,
                ':deposit_amount' => $depositAmount, 
                ':kptn'           => $kptnToSave
            ]);

            $loanId = $this->db->lastInsertId();

            if ($requiresKptn && !empty($_FILES['kptn_receipt']) && $_FILES['kptn_receipt']['error'] === UPLOAD_ERR_OK) {
                $docService = new \App\LoanDocumentService($this->db);
                $docService->uploadKptnReceipt($loanId, $uploaderId, $_FILES['kptn_receipt'], 'Ledger Import KPTN Receipt');
            }

            $stmtLedger = $this->db->prepare("
                INSERT INTO Amortization_Ledger (
                    loan_id, installment_no, scheduled_date, principal_amt, interest_amt, total_payment, remaining_bal, status, date_paid
                ) VALUES (:lid, :no, :date, :princ, :int, :total, :bal, :status, :date_paid)
            ");

            foreach ($data['ledger'] as $row) {
                $datePaid = ($row['status'] === 'PAID') ? $row['date'] : null;
                $stmtLedger->execute([
                    ':lid' => $loanId, ':no' => $row['installment_no'], ':date' => $row['date'],
                    ':princ' => $row['principal'], ':int' => $row['interest'], ':total' => $row['total'],
                    ':bal' => $row['balance'], ':status' => $row['status'], ':date_paid' => $datePaid
                ]);
            }

            $stmtUnpaid = $this->db->prepare("SELECT COUNT(*) FROM Amortization_Ledger WHERE loan_id = ? AND status = 'UNPAID'");
            $stmtUnpaid->execute([$loanId]);
 
            if ((int)$stmtUnpaid->fetchColumn() === 0) {
                $stmtMaxPaid = $this->db->prepare("SELECT MAX(date_paid) FROM Amortization_Ledger WHERE loan_id = ? AND status = 'PAID'");
                $stmtMaxPaid->execute([$loanId]);
                $stmtClose = $this->db->prepare("UPDATE Loan SET current_status = 'FULLY PAID', date_completed = ? WHERE loan_id = ?");
                $stmtClose->execute([$stmtMaxPaid->fetchColumn() ?: date('Y-m-d'), $loanId]);
            }

            $this->notifyUsersOnLoanCreation($loanId, $uploaderId, trim($b['first_name'] . ' ' . $b['last_name']), $pnToSave, ['ADMIN', 'REVIEWER']);

            $this->db->commit();
            return ['success' => true, 'loan_id' => $loanId];

        } catch (Exception $e) {
            $this->db->rollBack();
            $errorMsg = $e->getMessage();
            if (stripos($errorMsg, 'Duplicate entry') !== false && stripos($errorMsg, 'unique_kptn') !== false) {
                $errorMsg = "KPTN code already exists.";
            }
            return ['success' => false, 'error' => $errorMsg];
        }
    }

    // ===============================================
    // HELPERS & NOTIFICATIONS
    // ===============================================

    private function notifyUsersOnLoanCreation($loanId, $triggeredByEmployeId, $borrowerName, $pnNumber, $targetRoles = ['ADMIN', 'REVIEWER']) {
        if (empty($targetRoles)) return;

        $placeholders = implode(',', array_fill(0, count($targetRoles), '?'));
        $sql = "SELECT employe_id FROM Users WHERE user_type IN ($placeholders) AND status = 'ACTIVE'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($targetRoles);
        $recipients = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($recipients)) return;

        $message = strtoupper("Old Ledger Import ($pnNumber) added for borrower $borrowerName.");

        $insertStmt = $this->db->prepare("
            INSERT INTO Notifications (recipient_employe_id, triggered_by_employe_id, loan_id, type, message)
            VALUES (?, ?, ?, 'LOAN_ADDED', ?)
        ");

        $cleanTriggeredBy = !empty($triggeredByEmployeId) ? $triggeredByEmployeId : null;

        foreach ($recipients as $recipientId) {
            $insertStmt->execute([$recipientId, $cleanTriggeredBy, $loanId, $message]);
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

    /**
     * Caps a date to a valid semi-monthly payroll day.
     *  - Day 10  → 15  (legacy 10/25 cycle → 15/30)
     *  - Day 25  → 30  (legacy)
     *  - Day 31  → 30  (or last day of month if month has < 30 days)
     *  - February and short months: cap to actual last day
     */
    private function enforceDay30($dateStr) {
        if (!$dateStr) return null;
        $dateObj = new \DateTime($dateStr);
        $year  = (int)$dateObj->format('Y');
        $month = (int)$dateObj->format('m');
        $day   = (int)$dateObj->format('d');
        $daysInMonth = (int)(new \DateTime("$year-$month-01"))->format('t');

        if ($day == 10) {
            $day = 15;
        } elseif ($day == 25) {
            $day = min(30, $daysInMonth);
        } elseif ($day > $daysInMonth || $day == 31) {
            $day = $daysInMonth;
        } elseif ($day == 30 && $daysInMonth < 30) {
            $day = $daysInMonth;
        }

        $dateObj->setDate($year, $month, $day);
        return $dateObj->format('Y-m-d');
    }

    private function formatDate($cell) {
        if (!$cell) return null;
        $val = trim((string)$cell->getValue());
        if ($val === '') return null;
        
        $dateStr = null;
        if (is_numeric($val)) {
            try { 
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val);
                $dateStr = $dateObj->format('Y-m-d');
            } catch (Exception $e) {}
        } else {
            $timestamp = strtotime(str_replace('/', '-', $val));
            if ($timestamp !== false && $timestamp > 0) {
                $dateStr = date('Y-m-d', $timestamp);
            }
        }
        
        return $this->enforceDay30($dateStr);
    }

    private function generatePnNumber($offset = 0) {
    $year = date('Y');
    $stmt = $this->db->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(pn_number, '-', -1) AS UNSIGNED))
         FROM Loan
         WHERE pn_number LIKE ? AND pn_number IS NOT NULL"
    );
    $stmt->execute(["PN-{$year}-%"]);

    $max   = (int) $stmt->fetchColumn();
    $count = $max + 1 + $offset;
    return "PN-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
}

}