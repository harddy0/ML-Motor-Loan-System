<?php
// public/api/export_running_receivables.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// 1. Get Filters from GET request
$selectedPeriod = $_GET['period'] ?? date('Y-m'); 
$selectedHalf   = $_GET['half'] ?? 'ALL'; 
$selectedStatus = $_GET['status'] ?? 'ONGOING';

// Format display text for the headers
$displayMonth = date('F Y', strtotime($selectedPeriod . '-01'));

// --- UPDATED WORDING LOGIC ---
$displayHalf = "Full Month";
if ($selectedHalf === '1ST') $displayHalf = "First Half";
if ($selectedHalf === '2ND') $displayHalf = "Second Half";

$displayStatus = "Ongoing Accounts";
if ($selectedStatus === 'FULLY_PAID') $displayStatus = "Fully Paid Accounts";
if ($selectedStatus === 'ALL') $displayStatus = "All Accounts";

// 2. Fetch Data
$rrService = new \App\RunningReceivablesService($pdo);
$data = $rrService->getReportData($selectedPeriod, $selectedHalf === 'ALL' ? null : $selectedHalf, $selectedStatus);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Running Receivables');

// --- 4. Add "Text Before It" (Report Titles & Info) ---

// ML Motorcycle Loan Header
$sheet->setCellValue('A1', 'ML MOTORCYCLE LOAN');
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFE11D48']] // ML Red
]);

// Report Title
$sheet->setCellValue('A2', 'RUNNING RECEIVABLES REPORT');
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E293B']] // Slate-800
]);

// Report Filters / Period Info
$sheet->setCellValue('A3', "Report Period: $displayMonth | $displayHalf");
$sheet->setCellValue('A4', "Account Status: $displayStatus");
$sheet->getStyle('A3:A4')->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF64748B']] // Slate-500
]);

// --- 5. Style Definitions for Table ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFE11D48'] // ML Red Background for columns
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
];

$dataStyle = [
    'font' => ['size' => 10],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]], // Slate-300 borders
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

$currencyFormat = '#,##0.00';

// --- 6. Set Table Headers (Starting on Row 6) ---
$startRow = 6;
$headers = [
    'A' => 'EMPLOYEE ID',
    'B' => 'BORROWER',
    'C' => 'LOAN GRANTED',
    'D' => 'AMOUNT LOAN',
    'E' => 'MONTHLY PRINCIPAL',
    'F' => 'PRIOR PRINCIPAL',
    'G' => 'PAYMENT (TOTAL)',
    'H' => 'OUTSTANDING BAL',
    'I' => 'MONTHLY INCOME',
    'J' => 'STATUS'
];

foreach ($headers as $col => $value) {
    $sheet->setCellValue($col . $startRow, $value);
}
$sheet->getStyle("A{$startRow}:J{$startRow}")->applyFromArray($headerStyle);
$sheet->getRowDimension($startRow)->setRowHeight(30);

// Set Column Widths
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(18);
$sheet->getColumnDimension('J')->setWidth(15);

// --- 7. Populate Data & Calculate Totals ---
$row = $startRow + 1;
$totals = [
    'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0
];

foreach ($data as $item) {
    $sheet->setCellValue('A' . $row, $item['employe_id']);
    $sheet->setCellValue('B' . $row, strtoupper($item['name']));
    
    // Dates formatting
    $sheet->setCellValue('C' . $row, ($item['loan_granted'] === 'No Date') ? 'No Date' : date('Y-m-d', strtotime($item['loan_granted'])));
    
    $sheet->setCellValue('D' . $row, $item['loan_amount']);
    $sheet->setCellValue('E' . $row, $item['period_principal']);
    $sheet->setCellValue('F' . $row, $item['prior_payments']);
    $sheet->setCellValue('G' . $row, $item['accumulated_payments']);
    $sheet->setCellValue('H' . $row, $item['outstanding_balance']);
    $sheet->setCellValue('I' . $row, $item['period_income']);
    $sheet->setCellValue('J' . $row, $item['loan_status']);

    // Apply currency format
    $sheet->getStyle("D{$row}:I{$row}")->getNumberFormat()->setFormatCode($currencyFormat);
    
    // Center specific columns
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Accumulate totals
    $totals['D'] += $item['loan_amount'];
    $totals['E'] += $item['period_principal'];
    $totals['F'] += $item['prior_payments'];
    $totals['G'] += $item['accumulated_payments'];
    $totals['H'] += $item['outstanding_balance'];
    $totals['I'] += $item['period_income'];

    $row++;
}

// Apply borders to data cells
if ($row > ($startRow + 1)) {
    $sheet->getStyle("A" . ($startRow + 1) . ":J" . ($row - 1))->applyFromArray($dataStyle);
}

// --- 8. Add Totals Row ---
$sheet->setCellValue('C' . $row, 'TOTALS:');
$sheet->getStyle('C' . $row)->getFont()->setBold(true);
$sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

foreach ($totals as $col => $sum) {
    $sheet->setCellValue($col . $row, $sum);
    $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle($col . $row)->getFont()->setBold(true);
}

// Style the totals row (Slate-100 background to match frontend)
$sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFF8FAFC'] // Slate-50
    ],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
]);

// --- 9. Output to Browser ---
$filename = "Running_Receivables_" . str_replace('-', '', $selectedPeriod) . "_" . str_replace(' ', '', $displayHalf) . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;