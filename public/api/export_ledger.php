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
// Expanded merge cells to H for the Notes column
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'ML MOTORCYCLE LOAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFE11D48']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'SEMI - MONTHLY AMORTIZATION SCHEDULE');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1E293B']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$cleanLoanAmount = (float)str_replace(['₱', ',', ' '], '', $loan['loan_amount']);
$cleanSemiAmort  = (float)str_replace(['₱', ',', ' '], '', $loan['semi_monthly_amt']);

$displayGranted = formatLongDate($loan['date_granted']);
$displayMaturity = formatLongDate($loan['maturity_date']);

// --- 5. Account Details Block ---
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

$currencyFormat = '#,##0.00';
for ($i = 4; $i <= 8; $i++) {
    $sheet->mergeCells("B$i:C$i"); 
    $sheet->mergeCells("F$i:H$i"); // Extend right side to H
    
    $sheet->getStyle("A$i:H$i")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
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
    'G' => 'STATUS',
    'H' => 'NOTES' // Added Notes
];

foreach ($headers as $col => $value) {
    $sheet->setCellValue($col . $startRow, $value);
}

// Format up to H
$sheet->getStyle("A{$startRow}:H{$startRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
]);

$sheet->getStyle("B{$startRow}")->getFill()->getStartColor()->setArgb('FF1E293B'); 
$sheet->getStyle("E{$startRow}")->getFont()->getColor()->setArgb('FFFACC15'); 
$sheet->getStyle("F{$startRow}")->getFill()->getStartColor()->setArgb('FFE11D48'); 

// Set Column Widths
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(35); // Wider column for Notes

// --- 7. Populate Data ---
$dataStartRow = $startRow + 1;
$row = $dataStartRow;

$collectedPrincipal = 0;
$collectedInterest = 0;
$collectedTotal = 0;
$totalLacking = 0; // Initialize our new lacking tracker

foreach ($transactions as $txn) {
    $principalAmt = (float)str_replace(['₱', ',', ' '], '', $txn['principal']);
    $interestAmt  = (float)str_replace(['₱', ',', ' '], '', $txn['interest']);
    $totalAmt     = (float)str_replace(['₱', ',', ' '], '', $txn['total']);
    $balAmt       = (float)str_replace(['₱', ',', ' '], '', $txn['balance']);

    $status = trim(strtoupper($txn['status']));
    $isPaid = ($status === 'PAID');

    $displaySchedDate = formatLongDate($txn['scheduled_date']);
    $displayPaidDate = formatLongDate($txn['date_paid']);

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
    $sheet->setCellValue('H' . $row, $txn['notes'] ?: ''); // Print Notes

    // ==========================================================
    // PARSE LACKING MONEY FROM NOTES
    // ==========================================================
    if (!empty($txn['notes'])) {
        $cleanNotes = str_replace(',', '', $txn['notes']); // Remove commas from numbers
        $lackingAmt = 0;
        
        // 1. Match explicit keywords (Lacking, Short, Kulang, etc.)
        if (preg_match('/(?:lack|short|kulang|less|rem|balance|bal|miss|due|-)[\s:₱a-z]*([0-9]+(?:\.[0-9]+)?)/i', $cleanNotes, $matches)) {
            $lackingAmt = (float)$matches[1];
        } 
        // 2. Match exact 2 decimal places (standard currency entry like 50.00)
        elseif (preg_match('/([0-9]+\.[0-9]{2})/', $cleanNotes, $matches)) {
            $lackingAmt = (float)$matches[1];
        }
        // 3. Match numbers preceded by currency sign
        elseif (preg_match('/(?:₱|php)\s*([0-9]+(?:\.[0-9]+)?)/i', $cleanNotes, $matches)) {
            $lackingAmt = (float)$matches[1];
        }
        // 4. Fallback: any standalone number that doesn't look like a year/date
        elseif (preg_match('/(?:^|\s)([0-9]+)(?:\s|$)/', $cleanNotes, $matches)) {
            $val = (float)$matches[1];
            if ($val > 31 && $val != date('Y')) {
                $lackingAmt = $val;
            }
        }
        $totalLacking += $lackingAmt;
    }
    // ==========================================================

    $sheet->getStyle("F{$row}")->getFont()->getColor()->setArgb('FFE11D48'); 
    $sheet->getStyle("F{$row}")->getFont()->setBold(true);
    $sheet->getStyle("E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setArgb('FFFFFBEB'); 

    if (!$isPaid) {
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8E7']],
            'font' => ['color' => ['argb' => 'FF64748B']] 
        ]);
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FFA16207'); 
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    } else {
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FF15803D'); 
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    }

    $row++;
}
$endDataRow = $row - 1;

// Bulk Alignment & Formatting for Data Area (Expanded to H)
$sheet->getStyle("A{$dataStartRow}:B{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C{$dataStartRow}:F{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("G{$dataStartRow}:G{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("H{$dataStartRow}:H{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Notes Left Aligned
$sheet->getStyle("C{$dataStartRow}:F{$endDataRow}")->getNumberFormat()->setFormatCode($currencyFormat);

$sheet->getStyle("A{$dataStartRow}:H{$endDataRow}")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
]);

// --- 8. Table Column Totals ---
$sheet->setCellValue("B$row", "SCHEDULE TOTALS:");
$sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("B$row")->getFont()->setBold(true);

$sheet->setCellValue("C$row", "=SUM(C{$dataStartRow}:C{$endDataRow})");
$sheet->setCellValue("D$row", "=SUM(D{$dataStartRow}:D{$endDataRow})");
$sheet->setCellValue("E$row", "=SUM(E{$dataStartRow}:E{$endDataRow})");

$sheet->getStyle("C$row:E$row")->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle("C$row:E$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("C$row:E$row")->getFont()->setBold(true);

// Background for total row (Expanded to H)
$sheet->getStyle("A$row:H$row")->applyFromArray([
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
]);

// --- 9. Green Text Totals (Paid) ---
$row += 2;
$sheet->setCellValue("D$row", "Principal Collected (Paid):");
$sheet->setCellValue("E$row", $collectedPrincipal);

$row++;
$sheet->setCellValue("D$row", "Interest Collected (Paid):");
$sheet->setCellValue("E$row", $collectedInterest);

$row++;
$sheet->setCellValue("D$row", "TOTAL COLLECTED:");
$sheet->setCellValue("E$row", $collectedTotal);

$summaryStart = $row - 2;
$sheet->getStyle("D{$summaryStart}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$summaryStart}:E{$row}")->getFont()->setBold(true)->getColor()->setArgb('FF15803D'); 
$sheet->getStyle("E{$summaryStart}:E{$row}")->getNumberFormat()->setFormatCode($currencyFormat);

// --- 10. RED TEXT: Lacking / Short Totals ---
$row += 2;
$sheet->setCellValue("D$row", "TOTAL LACKING / SHORT:");
$sheet->setCellValue("E$row", $totalLacking);

$sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true)->getColor()->setArgb('FFE11D48'); // Red Color
$sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode($currencyFormat);


// --- 11. Add Footer (Generated By & Timestamp) ---
$row += 3; // Leave an empty row for spacing

// Fetch user's full name from session, default to 'System User' if missing
$generatedBy = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System User';

// FIX: Set timezone to Philippines so the timestamp is perfectly accurate
date_default_timezone_set('Asia/Manila');
$generationDate = date('F j, Y h:i A');

$sheet->setCellValue('A' . $row, 'Generated By:');
$sheet->setCellValue('B' . $row, strtoupper($generatedBy));
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$row++;
$sheet->setCellValue('A' . $row, 'Date Generated:');
$sheet->setCellValue('B' . $row, $generationDate);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Optional: Apply subtle text color to the footer
$sheet->getStyle("A" . ($row - 1) . ":B{$row}")->applyFromArray([
    'font' => ['color' => ['argb' => 'FF64748B']] // Slate-500
]);

// --- 12. Output to Browser ---
$filename = "Ledger_Account_" . str_replace(' ', '_', $loan['employe_id']) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;