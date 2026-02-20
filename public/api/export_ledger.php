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

// 2. Fetch Ledger Transactions
$loanService = new \App\LoanService($pdo);
$transactions = $loanService->getLedgerTransactions($loanId);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ledger');

// --- 4. Report Header Info ---
$sheet->setCellValue('A1', 'ML MOTORCYCLE LOAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFE11D48']] // ML Red
]);

$sheet->setCellValue('A2', 'LEDGER REPORT');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E293B']] // Slate-800
]);

// Account Details Setup
$sheet->setCellValue('A4', 'Borrower:');
$sheet->setCellValue('B4', strtoupper($loan['name']));
$sheet->setCellValue('D4', 'Employee ID:');
$sheet->setCellValue('E4', $loan['employe_id']);

$sheet->setCellValue('A5', 'PN Number:');
$sheet->setCellValue('B5', $loan['pn_number'] ?: '--');
$sheet->setCellValue('D5', 'Account Status:');
$sheet->setCellValue('E5', $loan['current_status']);

$sheet->setCellValue('A6', 'Date Granted:');
$sheet->setCellValue('B6', $loan['date_granted'] ?: '--');
$sheet->setCellValue('D6', 'Maturity Date:');
$sheet->setCellValue('E6', $loan['maturity_date'] ?: '--');

$sheet->setCellValue('A7', 'Principal Amt:');
$sheet->setCellValue('B7', $loan['loan_amount']);
$sheet->setCellValue('D7', 'Amortization:');
$sheet->setCellValue('E7', $loan['semi_monthly_amt']);

$sheet->setCellValue('A8', 'Terms:');
$sheet->setCellValue('B8', $loan['term_months'] . ' Months');
$sheet->setCellValue('D8', 'Add-on Rate:');
$sheet->setCellValue('E8', number_format($loan['add_on_rate'], 2) . '%');

$sheet->getStyle('A4:A8')->getFont()->setBold(true)->getColor()->setArgb('FF64748B'); // Slate-500
$sheet->getStyle('D4:D8')->getFont()->setBold(true)->getColor()->setArgb('FF64748B');
$sheet->getStyle('B7')->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('E7')->getNumberFormat()->setFormatCode('#,##0.00');

// --- 5. Table Headers ---
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

// Global Header Styling (Slate-900)
$sheet->getStyle("A{$startRow}:G{$startRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
]);

// Exact Modal Color Matching for Headers
$sheet->getStyle("B{$startRow}")->getFill()->getStartColor()->setArgb('FF1E293B'); // Slate-800 for Date Paid
$sheet->getStyle("E{$startRow}")->getFont()->getColor()->setArgb('FFFACC15'); // Yellow-400 text for Total Due
$sheet->getStyle("F{$startRow}")->getFill()->getStartColor()->setArgb('FFE11D48'); // ML Red bg for Balance

$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(15);

// --- 6. Populate Data ---
$row = $startRow + 1;
$totalPrincipalPaid = 0;
$totalInterestPaid = 0;
$totalPaid = 0;
$finalBalance = $loan['loan_amount'];

$currencyFormat = '#,##0.00';

foreach ($transactions as $txn) {
    $principalAmt = (float)$txn['principal'];
    $interestAmt = (float)$txn['interest'];
    $totalAmt = (float)$txn['total'];
    $balAmt = (float)$txn['balance'];
    $isPaid = ($txn['status'] === 'PAID');

    if ($isPaid) {
        $totalPrincipalPaid += $principalAmt;
        $totalInterestPaid += $interestAmt;
        $totalPaid += $totalAmt;
        $finalBalance = $balAmt;
    }

    $sheet->setCellValue('A' . $row, $txn['scheduled_date']);
    $sheet->setCellValue('B' . $row, $txn['date_paid'] ?: '--');
    $sheet->setCellValue('C' . $row, $principalAmt);
    $sheet->setCellValue('D' . $row, $interestAmt);
    $sheet->setCellValue('E' . $row, $totalAmt);
    $sheet->setCellValue('F' . $row, $balAmt);
    $sheet->setCellValue('G' . $row, $txn['status']);

    $sheet->getStyle("C{$row}:F{$row}")->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Balance column text is always Red
    $sheet->getStyle("F{$row}")->getFont()->getColor()->setArgb('FFE11D48');
    $sheet->getStyle("F{$row}")->getFont()->setBold(true);

    // Color Formatting Rules based on Status
    if (!$isPaid) {
        // PENDING: Yellow Row (bg-yellow-50)
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFBEB']],
            'font' => ['color' => ['argb' => 'FF64748B']] // Text Slate-500
        ]);
        // Status text is dark yellow (text-yellow-700)
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FFA16207');
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    } else {
        // PAID: Default White Row. Total Due column gets yellow bg. Status gets Green text.
        $sheet->getStyle("E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setArgb('FFFFFBEB');
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setArgb('FF15803D'); // Green-700
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    }

    $row++;
}

// Apply borders to table body
if ($row > ($startRow + 1)) {
    $sheet->getStyle("A" . ($startRow + 1) . ":G" . ($row - 1))->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
    ]);
}

// --- 7. Payment Summary ---
$row += 2;
$sheet->setCellValue('D' . $row, 'Principal Paid:');
$sheet->setCellValue('E' . $row, $totalPrincipalPaid);

$row++;
$sheet->setCellValue('D' . $row, 'Interest Paid:');
$sheet->setCellValue('E' . $row, $totalInterestPaid);

$row++;
$sheet->setCellValue('D' . $row, 'TOTAL COLLECTED:');
$sheet->setCellValue('E' . $row, $totalPaid);

// Style Summary
$sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true);
$sheet->getStyle("D{$row}:E{$row}")->getFont()->getColor()->setArgb('FF15803D'); // Green

$sheet->getStyle("E" . ($row - 2) . ":E" . $row)->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle("D" . ($row - 2) . ":D" . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// --- 8. Output to Browser ---
$filename = "Ledger_" . str_replace(' ', '_', $loan['employe_id']) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;