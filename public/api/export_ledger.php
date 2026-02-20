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

// 1. Fetch Master Loan Info
$stmt = $pdo->prepare("
    SELECT 
        b.employe_id, CONCAT(b.first_name, ' ', b.last_name) AS name,
        l.pn_number, l.date_granted, l.maturity_date, l.current_status,
        l.loan_amount, l.term_months, l.semi_monthly_amt, l.add_on_rate
    FROM Loan l
    JOIN Borrowers b ON l.employe_id = b.employe_id
    WHERE l.loan_id = ?
");
$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) die("Loan not found.");

// Helper function to format dates as "December 2, 2027"
function formatLongDate($dateStr) {
    if (empty($dateStr) || $dateStr === '--') return '--';
    return date('F j, Y', strtotime($dateStr));
}

// 2. Fetch Ledger Transactions
$loanService = new \App\LoanService($pdo);
$transactions = $loanService->getLedgerTransactions($loanId);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ledger Report');

// --- 4. Report Header Info ---
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'ML MOTORCYCLE LOAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFE11D48']], // ML Red
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A2', 'SEMI - MONTHLY AMORTIZATION SCHEDULE');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1E293B']], // Slate-800
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// Clean the header amounts for proper Excel formatting
$cleanLoanAmount = (float)str_replace(['₱', ',', ' '], '', $loan['loan_amount']);
$cleanSemiAmort  = (float)str_replace(['₱', ',', ' '], '', $loan['semi_monthly_amt']);

// Format Header Dates
$displayGranted = formatLongDate($loan['date_granted']);
$displayMaturity = formatLongDate($loan['maturity_date']);

// --- 5. Account Details Block (Strictly Left Aligned) ---
$sheet->setCellValue('A4', 'Borrower Name:'); $sheet->setCellValue('B4', strtoupper($loan['name']));
$sheet->setCellValue('E4', 'Employee ID:'); $sheet->setCellValue('F4', $loan['employe_id']);

$sheet->setCellValue('A5', 'PN Number:'); $sheet->setCellValue('B5', $loan['pn_number'] ?: '--');
$sheet->setCellValue('E5', 'Account Status:'); $sheet->setCellValue('F5', $loan['current_status']);

$sheet->setCellValue('A6', 'Date Granted:'); $sheet->setCellValue('B6', $displayGranted);
$sheet->setCellValue('E6', 'Maturity Date:'); $sheet->setCellValue('F6', $displayMaturity);

$sheet->setCellValue('A7', 'Principal Amount:'); $sheet->setCellValue('B7', $cleanLoanAmount);
$sheet->setCellValue('E7', 'Term (Months):'); $sheet->setCellValue('F7', $loan['term_months'] . ' Months');

$sheet->setCellValue('A8', 'Amortization:'); $sheet->setCellValue('B8', $cleanSemiAmort);
$sheet->setCellValue('E8', 'Add-on Rate:'); $sheet->setCellValue('F8', number_format($loan['add_on_rate'], 2) . '%');

// Format the details block
$currencyFormat = '#,##0.00';
for ($i = 4; $i <= 8; $i++) {
    $sheet->mergeCells("B$i:C$i"); 
    $sheet->mergeCells("F$i:G$i");
    
    $sheet->getStyle("A$i:G$i")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle("A$i")->getFont()->setBold(true)->getColor()->setArgb('FF64748B');
    $sheet->getStyle("E$i")->getFont()->setBold(true)->getColor()->setArgb('FF64748B');
}
$sheet->getStyle('B7')->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle('B8')->getNumberFormat()->setFormatCode($currencyFormat);

// --- 6. Table Headers ---
$startRow = 10;
$headers = [
    'A' => 'DUE DATE',
    'B' => 'DATE PAID',
    'C' => 'PRINCIPAL',
    'D' => 'INTEREST',
    'E' => 'TOTAL DUE',
    'F' => 'BALANCE',
    'G' => 'STATUS'
];

foreach ($headers as $col => $value) {
    $sheet->setCellValue($col . $startRow, $value);
}

// Global Header Styling
$sheet->getStyle("A{$startRow}:G{$startRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']], // Slate-900
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
]);

$sheet->getStyle("B{$startRow}")->getFill()->getStartColor()->setArgb('FF1E293B'); // Slate-800
$sheet->getStyle("E{$startRow}")->getFont()->getColor()->setArgb('FFFACC15'); // Yellow text
$sheet->getStyle("F{$startRow}")->getFill()->getStartColor()->setArgb('FFE11D48'); // Red background

// Set Column Widths (Adjusted for longer dates)
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);

// --- 7. Populate Data & BULLETPROOF MATH ---
$dataStartRow = $startRow + 1;
$row = $dataStartRow;

// Pure math trackers for the Green summary
$collectedPrincipal = 0;
$collectedInterest = 0;
$collectedTotal = 0;

foreach ($transactions as $txn) {
    // 100% BULLETPROOF MATH: Strip commas and ₱ before calculation
    $principalAmt = (float)str_replace(['₱', ',', ' '], '', $txn['principal']);
    $interestAmt  = (float)str_replace(['₱', ',', ' '], '', $txn['interest']);
    $totalAmt     = (float)str_replace(['₱', ',', ' '], '', $txn['total']);
    $balAmt       = (float)str_replace(['₱', ',', ' '], '', $txn['balance']);

    $status = trim(strtoupper($txn['status']));
    $isPaid = ($status === 'PAID');

    // Format the Row Dates
    $displaySchedDate = formatLongDate($txn['scheduled_date']);
    $displayPaidDate = formatLongDate($txn['date_paid']);

    // Add to actual money collected (green text) ONLY if paid
    if ($isPaid) {
        $collectedPrincipal += $principalAmt;
        $collectedInterest += $interestAmt;
        $collectedTotal += $totalAmt;
    }

    $sheet->setCellValue('A' . $row, $displaySchedDate);
    $sheet->setCellValue('B' . $row, $displayPaidDate);
    $sheet->setCellValue('C' . $row, $principalAmt);
    $sheet->setCellValue('D' . $row, $interestAmt);
    $sheet->setCellValue('E' . $row, $totalAmt);
    $sheet->setCellValue('F' . $row, $balAmt);
    $sheet->setCellValue('G' . $row, $txn['status']);

    // Strict Structure Enforcement per row
    $sheet->getStyle("F{$row}")->getFont()->getColor()->setArgb('FFE11D48'); // Balance is always Red
    $sheet->getStyle("F{$row}")->getFont()->setBold(true);
    $sheet->getStyle("E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setArgb('FFFFFBEB'); 

    if (!$isPaid) {
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8E7']], // Soft yellow row
            'font' => ['color' => ['argb' => 'FF64748B']] // Slate text
        ]);
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FFA16207'); // Dark yellow status
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    } else {
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FF15803D'); // Green status
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    }

    $row++;
}
$endDataRow = $row - 1;

// Bulk Alignment & Formatting for Data Area
$sheet->getStyle("A{$dataStartRow}:B{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C{$dataStartRow}:F{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("G{$dataStartRow}:G{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C{$dataStartRow}:F{$endDataRow}")->getNumberFormat()->setFormatCode($currencyFormat);

$sheet->getStyle("A{$dataStartRow}:G{$endDataRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
]);

// --- 8. Table Column Totals (Using perfect Excel formulas) ---
$sheet->setCellValue("B$row", "SCHEDULE TOTALS:");
$sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("B$row")->getFont()->setBold(true);

$sheet->setCellValue("C$row", "=SUM(C{$dataStartRow}:C{$endDataRow})");
$sheet->setCellValue("D$row", "=SUM(D{$dataStartRow}:D{$endDataRow})");
$sheet->setCellValue("E$row", "=SUM(E{$dataStartRow}:E{$endDataRow})");

$sheet->getStyle("C$row:E$row")->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle("C$row:E$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("C$row:E$row")->getFont()->setBold(true);

$sheet->getStyle("A$row:G$row")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
]);

// --- 9. Green Text Totals (Perfect PHP Math) ---
$row += 2;
$sheet->setCellValue("D$row", "Principal Collected (Paid):");
$sheet->setCellValue("E$row", $collectedPrincipal);

$row++;
$sheet->setCellValue("D$row", "Interest Collected (Paid):");
$sheet->setCellValue("E$row", $collectedInterest);

$row++;
$sheet->setCellValue("D$row", "TOTAL COLLECTED:");
$sheet->setCellValue("E$row", $collectedTotal);

// Format Summary Block
$summaryStart = $row - 2;
$sheet->getStyle("D{$summaryStart}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$summaryStart}:E{$row}")->getFont()->setBold(true)->getColor()->setArgb('FF15803D'); // Green text
$sheet->getStyle("E{$summaryStart}:E{$row}")->getNumberFormat()->setFormatCode($currencyFormat);

// --- 10. Output to Browser ---
$filename = "Ledger_Account_" . str_replace(' ', '_', $loan['employe_id']) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;