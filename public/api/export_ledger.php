<?php
// public/api/export_ledger.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

if (!isset($_GET['loan_id'])) {
    die("Loan ID is required.");
}
$loanId = $_GET['loan_id'];

// 1. Fetch Master Loan Info + Borrower Info
$stmt = $pdo->prepare("
    SELECT 
        b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name,
        b.region, b.branch, b.contact_number,
        l.pn_number, l.loan_ref_no, l.date_granted, l.maturity_date, l.current_status,
        l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate
    FROM Loan l
    JOIN Borrowers b ON l.employe_id = b.employe_id
    WHERE l.loan_id = ?
");
$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) die("Loan not found.");

function formatLongDate($dateStr) {
    if (empty($dateStr) || $dateStr === '--' || $dateStr === '0000-00-00') return '--';
    return date('F j, Y', strtotime($dateStr));
}

// 2. Fetch Ledger Transactions
$loanService = new \App\LoanService($pdo);
$transactions = $loanService->getLedgerTransactions($loanId);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ledger Report');

// --- Layout Base Setup ---
$sheet->getDefaultRowDimension()->setRowHeight(15);
$sheet->getColumnDimension('A')->setWidth(16);
$sheet->getColumnDimension('B')->setWidth(14);
$sheet->getColumnDimension('C')->setWidth(16);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(13);
$sheet->getColumnDimension('F')->setWidth(16);
$sheet->getColumnDimension('G')->setWidth(16);

$cleanLoanAmount = (float)str_replace(['₱', ',', ' '], '', (string)($loan['loan_amount'] ?? '0'));
$cleanSemiAmort  = (float)str_replace(['₱', ',', ' '], '', (string)($loan['semi_monthly_amt'] ?? '0'));
$displayGranted  = formatLongDate($loan['date_granted'] ?? null);
$displayMaturity = formatLongDate($loan['maturity_date'] ?? null);

// Helper functions for precise borders
function setAllBorders($sheet, $range) {
    $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}
function setOutlineBorder($sheet, $range) {
    $sheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
}

// ==========================================
// HEADER BLOCK matching sample layout:
// ROW 1  - Title
// ROW 2  - Account Name
// ROW 3  - ID Number
// ROW 4  - Reference Number
// ROW 5  - Region
// ROW 6  - Branch
// ROW 7  - Contact Number (label A, value C)
// ROW 8  - PN Number merged A:C  |  Loan Amount D + value E
// ROW 9  - Date Released C       |  Terms D + value E + months F
// ROW 10 - Maturity Date C       |  Interest D + value E + % F
// ROW 11 - Semi-Monthly Amortization
// ROW 12 - APPLICATION header
// ROW 13 - DATE/PRINCIPAL/INTEREST cols
// ROW 14 - Opening Balance
// ROW 15+ - Transactions
// ==========================================

// ROW 1 - Title
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'SEMI - MONTHLY AMORTIZATION SCHEDULE');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setOutlineBorder($sheet, 'A1:G1');

// ROW 2 - Account Name
$sheet->mergeCells('A2:B2');
$sheet->setCellValue('A2', 'Account Name :');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
setOutlineBorder($sheet, 'A2:B2');

$sheet->mergeCells('C2:G2');
$sheet->setCellValue('C2', strtoupper((string)($loan['name'] ?? '')));
$sheet->getStyle('C2')->getFont()->setBold(true)->setSize(11);
setOutlineBorder($sheet, 'C2:G2');

// ROWS 3 to 7 — ID Number, Reference Number, PN Number, Region, Branch
$refNo = !empty($loan['loan_ref_no']) ? $loan['loan_ref_no'] : ($loan['pn_number'] ?? '');
$rowsConfig = [
    3 => ['label' => 'ID Number:',        'val' => $loan['employe_id'] ?? ''],
    4 => ['label' => 'Reference Number:',  'val' => $refNo],
    5 => ['label' => 'PN Number:',         'val' => $loan['pn_number'] ?? ''],
    6 => ['label' => 'Region:',            'val' => $loan['region'] ?? ''],
    7 => ['label' => 'Branch:',            'val' => (!empty($loan['branch']) && strtoupper(trim($loan['branch'])) !== 'N/A') ? $loan['branch'] : ''],
];

foreach ($rowsConfig as $r => $data) {
    $sheet->setCellValue('A'.$r, $data['label']);
    $sheet->setCellValue('C'.$r, $data['val']);
    $sheet->getStyle('A'.$r)->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('C'.$r)->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle("C$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $sheet->getStyle("A$r")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A$r")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A$r")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("B$r")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("B$r")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("B$r")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("C$r")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("C$r")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("C$r")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("D$r:F$r")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("D$r:F$r")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("G$r")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("G$r")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("G$r")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
}

// ROW 8 - Contact Number (label A, value C) | Loan Amount D | value E
$sheet->setCellValue('A8', 'Contact Number');
$sheet->getStyle('A8')->getFont()->setBold(true)->setSize(11);

$sheet->setCellValue('C8', $loan['contact_number'] ?? '');
$sheet->getStyle('C8')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('C8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$sheet->getStyle("A8")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A8")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A8")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("B8")->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("B8")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("B8")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("C8")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("C8")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("C8")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

$sheet->setCellValue('D8', 'Loan Amount :');
$sheet->getStyle('D8')->getFont()->setBold(true)->setSize(11);
setAllBorders($sheet, 'D8');

$sheet->setCellValue('E8', $cleanLoanAmount);
$sheet->getStyle('E8')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('E8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('E8')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'E8');
setAllBorders($sheet, 'F8');
setAllBorders($sheet, 'G8');

// ROW 9 - Date Released | Terms
$sheet->mergeCells('A9:B9');
$sheet->setCellValue('A9', 'Date Released :');
$sheet->getStyle('A9')->getFont()->setBold(true)->setSize(11);
setOutlineBorder($sheet, 'A9:B9');

$sheet->setCellValue('C9', $displayGranted);
$sheet->getStyle('C9')->getFont()->setSize(11);
$sheet->getStyle('C9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setAllBorders($sheet, 'C9');

$sheet->setCellValue('D9', 'Terms:');
$sheet->getStyle('D9')->getFont()->setBold(true)->setSize(11);
setAllBorders($sheet, 'D9');

$sheet->setCellValue('E9', $loan['term_months'] ?? '');
$sheet->getStyle('E9')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('E9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
setAllBorders($sheet, 'E9');

$sheet->setCellValue('F9', 'months');
$sheet->getStyle('F9')->getFont()->setSize(11);
setAllBorders($sheet, 'F9');
setAllBorders($sheet, 'G9');

// ROW 10 - Maturity Date | Interest
$addOnRateDecimal = floatval($loan['add_on_rate'] ?? 0);
// MODIFIED: Show monthly interest rate instead of calculating the total term rate
$monthlyRatePercent = (float)number_format($addOnRateDecimal * 100, 2, '.', '');

$sheet->mergeCells('A10:B10');
$sheet->setCellValue('A10', ' Maturity Date:');
$sheet->getStyle('A10')->getFont()->setBold(true)->setSize(11);
setOutlineBorder($sheet, 'A10:B10');

$sheet->setCellValue('C10', $displayMaturity);
$sheet->getStyle('C10')->getFont()->setSize(11);
$sheet->getStyle('C10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setAllBorders($sheet, 'C10');

$sheet->setCellValue('D10', 'Interest/mo :'); // Updated Label
$sheet->getStyle('D10')->getFont()->setBold(true)->setSize(11);
setAllBorders($sheet, 'D10');

$sheet->setCellValue('E10', $monthlyRatePercent);
$sheet->getStyle('E10')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('E10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
setAllBorders($sheet, 'E10');

$sheet->setCellValue('F10', '%');
$sheet->getStyle('F10')->getFont()->setSize(11);
setAllBorders($sheet, 'F10');
setAllBorders($sheet, 'G10');

// ==========================================
// TRANSITION AND HEADERS (ROWS 11 - 14)
// ==========================================

// ROW 11 - Semi-Monthly Amortization
$sheet->mergeCells('A11:C11');

$sheet->setCellValue('D11', 'Semi-Monthly Amortization');
$sheet->getStyle('D11')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('D11')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('D11')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('D11')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

setAllBorders($sheet, 'E11');

$sheet->setCellValue('F11', $cleanSemiAmort);
$sheet->getStyle('F11')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('F11')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'F11');
setAllBorders($sheet, 'G11');

// ROW 12 & 13 - APPLICATION header
$sheet->mergeCells('A12:B12'); setOutlineBorder($sheet, 'A12:B12');
$sheet->mergeCells('C12:D12'); $sheet->setCellValue('C12', 'APPLICATION');
$sheet->getStyle('C12')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('C12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setOutlineBorder($sheet, 'C12:D12');

$sheet->mergeCells('E12:E13'); $sheet->setCellValue('E12', 'TOTAL AMOUNT');
$sheet->getStyle('E12')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('E12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('E12')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'E12:E13');

$sheet->mergeCells('F12:F13'); $sheet->setCellValue('F12', 'PRINCIPAL BALANCE');
$sheet->getStyle('F12')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('F12')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'F12:F13');

$sheet->mergeCells('G12:G13'); $sheet->setCellValue('G12', 'STATUS');
$sheet->getStyle('G12')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('G12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G12')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'G12:G13');

// ROW 13 - Lower halves
$sheet->setCellValue('A13', ''); setAllBorders($sheet, 'A13');
$sheet->setCellValue('B13', 'DATE'); $sheet->getStyle('B13')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('B13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'B13');
$sheet->setCellValue('C13', 'PRINCIPAL'); $sheet->getStyle('C13')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('C13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'C13');
$sheet->setCellValue('D13', 'INTEREST'); $sheet->getStyle('D13')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('D13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'D13');

// ROW 14 - Opening Balance
$sheet->getStyle('A14')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A14')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A14')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B14:E14')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B14:E14')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

$sheet->setCellValue('F14', $cleanLoanAmount);
$sheet->getStyle('F14')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('F14')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'F14');

$sheet->getStyle('G14')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('G14')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('G14')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

// ==========================================
// TABLE TRANSACTIONS (ROWS 15+)
// ==========================================
$row = 15;
$payNo = 1;

$collectedPrincipal = 0;
$collectedInterest  = 0;
$collectedTotal     = 0;

foreach ($transactions as $txn) {
    $status  = trim(strtoupper((string)($txn['status'] ?? '')));
    $isPaid  = ($status === 'PAID');
    $isNoDeduction = ($status === 'NO DEDUCTION');

    $principalAmt = (float)str_replace(['₱', ',', ' '], '', (string)($txn['principal'] ?? '0'));
    $interestAmt  = (float)str_replace(['₱', ',', ' '], '', (string)($txn['interest'] ?? '0'));
    $totalAmt     = (float)str_replace(['₱', ',', ' '], '', (string)($txn['total'] ?? '0'));
    $balAmt       = (float)str_replace(['₱', ',', ' '], '', (string)($txn['balance'] ?? '0'));

    if ($isPaid) {
        $collectedPrincipal += $principalAmt;
        $collectedInterest  += $interestAmt;
        $collectedTotal     += $totalAmt;
    }

    $sheet->setCellValue('A' . $row, $payNo);
    $sheet->getStyle('A' . $row)->getFont()->setSize(11);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $displayDate = $txn['scheduled_date'];
    if ($isPaid && !empty($txn['date_paid']) && $txn['date_paid'] !== '--' && $txn['date_paid'] !== '0000-00-00') {
        $displayDate = $txn['date_paid'];
    }

    $sheet->setCellValue('B' . $row, date('m/d/Y', strtotime($displayDate)));
    $sheet->getStyle('B' . $row)->getFont()->setSize(11);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('C' . $row, $principalAmt);
    $sheet->setCellValue('D' . $row, $interestAmt);
    $sheet->setCellValue('E' . $row, $totalAmt);
    $sheet->setCellValue('F' . $row, $balAmt);

    $sheet->getStyle("C{$row}:F{$row}")->getFont()->setSize(11);
    $sheet->getStyle("C{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle("C{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->setCellValue('G' . $row, $status);
    $sheet->getStyle('G' . $row)->getFont()->setSize(11)->setBold(true);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    setAllBorders($sheet, "A{$row}:G{$row}");

    // Status-based text coloring only (no row fill highlight)
    if ($isPaid) {
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
    } elseif ($isNoDeduction) {
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setARGB(Color::COLOR_RED);
    }

    $row++;
    $payNo++;
}

// ==========================================
// SUBTOTALS & FOOTER
// ==========================================
$endDataRow = $row - 1;

$sheet->setCellValue("B$row", "SUBTOTALS:");
$sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("B$row")->getFont()->setBold(true);

$sheet->setCellValue("C$row", "=SUM(C15:C{$endDataRow})");
$sheet->setCellValue("D$row", "=SUM(D15:D{$endDataRow})");
$sheet->setCellValue("E$row", "=SUM(E15:E{$endDataRow})");

$sheet->getStyle("C$row:E$row")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("C$row:E$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("C$row:E$row")->getFont()->setBold(true);

setAllBorders($sheet, "A$row:G$row");

$row += 2;

$grossPrincipal = $cleanLoanAmount;
$grossInterest  = $cleanLoanAmount * $addOnRateDecimal * $termMonths;
$grossTotal     = $grossPrincipal + $grossInterest;

$balancePrincipal = $grossPrincipal - $collectedPrincipal;
$balanceInterest  = $grossInterest - $collectedInterest;
$balanceTotal     = $grossTotal - $collectedTotal;

$summaryHeaderRow = $row;
$sheet->mergeCells("A{$summaryHeaderRow}:B{$summaryHeaderRow}");
$sheet->mergeCells("C{$summaryHeaderRow}:D{$summaryHeaderRow}");
$sheet->mergeCells("E{$summaryHeaderRow}:F{$summaryHeaderRow}");

$sheet->setCellValue("A{$summaryHeaderRow}", 'GROSS');
$sheet->setCellValue("C{$summaryHeaderRow}", 'PAYMENT');
$sheet->setCellValue("E{$summaryHeaderRow}", 'BALANCE');

$sheet->getStyle("A{$summaryHeaderRow}:F{$summaryHeaderRow}")->getFont()->setBold(true);
$sheet->getStyle("A{$summaryHeaderRow}:F{$summaryHeaderRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$r1 = $summaryHeaderRow + 1;
$r2 = $summaryHeaderRow + 2;
$r3 = $summaryHeaderRow + 3;

$sheet->setCellValue("A{$r1}", 'Principal Gross');
$sheet->setCellValue("B{$r1}", $grossPrincipal);
$sheet->setCellValue("C{$r1}", 'Principal Paid');
$sheet->setCellValue("D{$r1}", $collectedPrincipal);
$sheet->setCellValue("E{$r1}", 'Principal Balance');
$sheet->setCellValue("F{$r1}", $balancePrincipal);

$sheet->setCellValue("A{$r2}", 'Interest Gross');
$sheet->setCellValue("B{$r2}", $grossInterest);
$sheet->setCellValue("C{$r2}", 'Interest Paid');
$sheet->setCellValue("D{$r2}", $collectedInterest);
$sheet->setCellValue("E{$r2}", 'Interest Balance');
$sheet->setCellValue("F{$r2}", $balanceInterest);

$sheet->setCellValue("A{$r3}", 'Total Gross');
$sheet->setCellValue("B{$r3}", $grossTotal);
$sheet->setCellValue("C{$r3}", 'Total Payment');
$sheet->setCellValue("D{$r3}", $collectedTotal);
$sheet->setCellValue("E{$r3}", 'Outstanding Balance');
$sheet->setCellValue("F{$r3}", $balanceTotal);

$sheet->getStyle("A{$r1}:F{$r3}")->getFont()->setBold(true);
$sheet->getStyle("B{$r1}:B{$r3}")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("D{$r1}:D{$r3}")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("F{$r1}:F{$r3}")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("B{$r1}:B{$r3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$r1}:D{$r3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("F{$r1}:F{$r3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$r1}:D{$r3}")->getFont()->getColor()->setArgb('FF15803D');

setAllBorders($sheet, "A{$summaryHeaderRow}:F{$r3}");

$row = $r3 + 3;
$generatedBy = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System User';
date_default_timezone_set('Asia/Manila');

$sheet->setCellValue('A' . $row, 'Generated By:');
$sheet->setCellValue('B' . $row, strtoupper($generatedBy));
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

$row++;
$sheet->setCellValue('A' . $row, 'Date Generated:');
$sheet->setCellValue('B' . $row, date('F j, Y h:i A'));
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

$sheet->getStyle("A" . ($row - 1) . ":B{$row}")->applyFromArray([
    'font' => ['color' => ['argb' => 'FF64748B']]
]);

// 4. Output
while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = "Ledger_Account_" . str_replace(' ', '_', $loan['employe_id'] ?? 'Export') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
