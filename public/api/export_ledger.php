<?php
// public/api/export_ledger.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
// HEADER BLOCK (ROWS 1 - 12)
// ==========================================

// ROW 1
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'SEMI - MONTHLY AMORTIZATION SCHEDULE');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setOutlineBorder($sheet, 'A1:G1');

// ROW 2 (For the Period Covered)
$sheet->mergeCells('A2:B2');
$sheet->setCellValue('A2', 'For the Period Covered:');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(9);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Row 2 Borders (Left wall, Right wall, Underline C and E)
$sheet->getStyle('A2')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('C2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN); // Underline C
$sheet->getStyle('E2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN); // Underline E
$sheet->getStyle('G2')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

// ROW 3 (Blank but needs outer side borders)
$sheet->getStyle('A3')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('G3')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

// ROW 4
$sheet->mergeCells('A4:B4');
$sheet->setCellValue('A4', 'Account Name :');
$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'A4:B4');

$sheet->mergeCells('C4:G4');
$sheet->setCellValue('C4', strtoupper((string)($loan['name'] ?? '')));
$sheet->getStyle('C4')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'C4:G4');

// ROWS 5 to 8
$refNo = !empty($loan['loan_ref_no']) ? $loan['loan_ref_no'] : ($loan['pn_number'] ?? '');
$rowsConfig = [
    5 => ['label' => 'ID Number:', 'val' => $loan['employe_id'] ?? ''],
    6 => ['label' => 'Reference Number:', 'val' => $refNo],
    7 => ['label' => 'Region:', 'val' => $loan['region'] ?? ''],
    8 => ['label' => 'Branch:', 'val' => $loan['branch'] ?? ''],
];

foreach ($rowsConfig as $r => $data) {
    $sheet->setCellValue('A'.$r, $data['label']);
    $sheet->setCellValue('C'.$r, $data['val']);
    $sheet->getStyle('A'.$r)->getFont()->setBold(true)->setSize(9);
    $sheet->getStyle('C'.$r)->getFont()->setBold(true)->setSize(9);
    
    // Force Left Alignment for ID Number and others in C
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

// ROW 9 
$sheet->mergeCells('A9:B9');
$sheet->setCellValue('A9', 'Contact Number');
$sheet->getStyle('A9')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'A9:B9');

$sheet->mergeCells('C9:G9');
$sheet->setCellValue('C9', $loan['contact_number'] ?? '');
$sheet->getStyle('C9')->getFont()->setBold(true)->setSize(9);
$sheet->getStyle('C9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Ensure left align
setOutlineBorder($sheet, 'C9:G9');

// ROW 10 
$sheet->mergeCells('A10:C10');
// Added extra spacing here so it's not squished
$sheet->setCellValue('A10', 'PN Number :     ' . ($loan['pn_number'] ?? ''));
$sheet->getStyle('A10')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'A10:C10');

$sheet->setCellValue('D10', 'Loan Amount :');
$sheet->getStyle('D10')->getFont()->setBold(true)->setSize(9);
setAllBorders($sheet, 'D10');

$sheet->setCellValue('E10', $cleanLoanAmount);
$sheet->getStyle('E10')->getFont()->setBold(true)->setSize(9);
$sheet->getStyle('E10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('E10')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'E10');
setAllBorders($sheet, 'F10');
setAllBorders($sheet, 'G10');

// ROW 11 
$sheet->mergeCells('A11:B11');
$sheet->setCellValue('A11', 'Date Released :');
$sheet->getStyle('A11')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'A11:B11');

$sheet->setCellValue('C11', $displayGranted);
$sheet->getStyle('C11')->getFont()->setSize(11);
$sheet->getStyle('C11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setAllBorders($sheet, 'C11');

$sheet->setCellValue('D11', 'Terms:');
$sheet->getStyle('D11')->getFont()->setBold(true)->setSize(9);
setAllBorders($sheet, 'D11');

$sheet->setCellValue('E11', $loan['term_months'] ?? '');
$sheet->getStyle('E11')->getFont()->setBold(true)->setSize(9);
$sheet->getStyle('E11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
setAllBorders($sheet, 'E11');

$sheet->setCellValue('F11', 'months');
$sheet->getStyle('F11')->getFont()->setSize(9);
setAllBorders($sheet, 'F11');
setAllBorders($sheet, 'G11');

// ROW 12 
$sheet->mergeCells('A12:B12');
$sheet->setCellValue('A12', ' Maturity Date:');
$sheet->getStyle('A12')->getFont()->setBold(true)->setSize(9);
setOutlineBorder($sheet, 'A12:B12');

$sheet->setCellValue('C12', $displayMaturity);
$sheet->getStyle('C12')->getFont()->setSize(11);
$sheet->getStyle('C12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setAllBorders($sheet, 'C12');

$sheet->setCellValue('D12', 'Interest :');
$sheet->getStyle('D12')->getFont()->setBold(true)->setSize(9);
setAllBorders($sheet, 'D12');

// Interest Rate
$addOnRateDecimal = floatval($loan['add_on_rate'] ?? 0);
$termMonths = intval($loan['term_months'] ?? 0);
$totalRatePercent = number_format($addOnRateDecimal * $termMonths * 100, 0);

$sheet->setCellValue('E12', $totalRatePercent);
$sheet->getStyle('E12')->getFont()->setBold(true)->setSize(9);
$sheet->getStyle('E12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
setAllBorders($sheet, 'E12');

$sheet->setCellValue('F12', '%');
$sheet->getStyle('F12')->getFont()->setSize(9);
setAllBorders($sheet, 'F12');
setAllBorders($sheet, 'G12');

// ==========================================
// TRANSITION AND HEADERS (ROWS 13 - 16)
// ==========================================

// ROW 13 
$sheet->mergeCells('A13:C13'); // Zero borders

$sheet->setCellValue('D13', 'Semi-Monthly Amortization');
$sheet->getStyle('D13')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('D13')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('D13')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('D13')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

setAllBorders($sheet, 'E13');

$sheet->setCellValue('F13', $cleanSemiAmort);
$sheet->getStyle('F13')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('F13')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'F13');
setAllBorders($sheet, 'G13');

// ROW 14 & 15 
$sheet->mergeCells('A14:B14'); setOutlineBorder($sheet, 'A14:B14');
$sheet->mergeCells('C14:D14'); $sheet->setCellValue('C14', 'APPLICATION');
$sheet->getStyle('C14')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('C14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setOutlineBorder($sheet, 'C14:D14');

$sheet->mergeCells('E14:E15'); $sheet->setCellValue('E14', 'TOTAL AMOUNT');
$sheet->getStyle('E14')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('E14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('E14')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'E14:E15');

$sheet->mergeCells('F14:F15'); $sheet->setCellValue('F14', 'PRINCIPAL BALANCE');
$sheet->getStyle('F14')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('F14')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'F14:F15');

$sheet->mergeCells('G14:G15'); $sheet->setCellValue('G14', 'STATUS'); 
$sheet->getStyle('G14')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('G14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G14')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
setOutlineBorder($sheet, 'G14:G15');

// ROW 15 Lower halves
$sheet->setCellValue('A15', ''); setAllBorders($sheet, 'A15');
$sheet->setCellValue('B15', 'DATE'); $sheet->getStyle('B15')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('B15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'B15');
$sheet->setCellValue('C15', 'PRINCIPAL'); $sheet->getStyle('C15')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('C15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'C15');
$sheet->setCellValue('D15', 'INTEREST'); $sheet->getStyle('D15')->getFont()->setBold(true)->setSize(11); $sheet->getStyle('D15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); setAllBorders($sheet, 'D15');

// ROW 16 (Opening Balance)
$sheet->getStyle('A16')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A16')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A16')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B16:E16')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B16:E16')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

$sheet->setCellValue('F16', $cleanLoanAmount);
$sheet->getStyle('F16')->getFont()->setBold(true)->setSize(11);
$sheet->getStyle('F16')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('F16')->getNumberFormat()->setFormatCode('#,##0.00');
setAllBorders($sheet, 'F16');

$sheet->getStyle('G16')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('G16')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('G16')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);


// ==========================================
// TABLE TRANSACTIONS (ROWS 17+)
// ==========================================
$row = 17;
$payNo = 1;

$collectedPrincipal = 0;
$collectedInterest = 0;
$collectedTotal = 0;

foreach ($transactions as $txn) {
    $status = trim(strtoupper((string)($txn['status'] ?? '')));
    $isPaid = ($status === 'PAID');

    // Values setup
    $principalAmt = (float)str_replace(['₱', ',', ' '], '', (string)($txn['principal'] ?? '0'));
    $interestAmt  = (float)str_replace(['₱', ',', ' '], '', (string)($txn['interest'] ?? '0'));
    $totalAmt     = (float)str_replace(['₱', ',', ' '], '', (string)($txn['total'] ?? '0'));
    $balAmt       = (float)str_replace(['₱', ',', ' '], '', (string)($txn['balance'] ?? '0'));

    if ($isPaid) {
        $collectedPrincipal += $principalAmt;
        $collectedInterest += $interestAmt;
        $collectedTotal += $totalAmt;
    }

    $sheet->setCellValue('A' . $row, $payNo);
    $sheet->getStyle('A' . $row)->getFont()->setSize(11);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Use Scheduled Date by default if not paid yet, else Paid Date
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

    if ($isPaid) {
        $sheet->getStyle("A{$row}:G{$row}")->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setArgb('FFE599'); // Yellow fill for PAID
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

$sheet->setCellValue("C$row", "=SUM(C17:C{$endDataRow})");
$sheet->setCellValue("D$row", "=SUM(D17:D{$endDataRow})");
$sheet->setCellValue("E$row", "=SUM(E17:E{$endDataRow})");

$sheet->getStyle("C$row:E$row")->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle("C$row:E$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("C$row:E$row")->getFont()->setBold(true);

setAllBorders($sheet, "A$row:G$row");

// Spacing
$row += 2;

// Totals Collected
$sheet->setCellValue("D$row", "Principal Collected:");
$sheet->setCellValue("E$row", $collectedPrincipal);
$row++;
$sheet->setCellValue("D$row", "Interest Collected:");
$sheet->setCellValue("E$row", $collectedInterest);
$row++;
$sheet->setCellValue("D$row", "TOTAL COLLECTED:");
$sheet->setCellValue("E$row", $collectedTotal);

$summaryStart = $row - 2;
$sheet->getStyle("D{$summaryStart}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$summaryStart}:E{$row}")->getFont()->setBold(true)->getColor()->setArgb('FF15803D'); // Green Text
$sheet->getStyle("E{$summaryStart}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

// Add Timestamp Footer
$row += 3; 
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

// 4. Output Configuration - Strictly Clears Buffer to Prevent Corrupt Blank Excel File
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