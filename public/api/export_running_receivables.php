<?php
// public/api/export_running_receivables.php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// 1. Get Filters from GET request
$selectedPeriod = $_GET['period'] ?? date('Y-m'); 
$selectedHalf   = $_GET['half'] ?? 'ALL'; 
$selectedStatus = $_GET['status'] ?? 'ONGOING';
$selectedRegion = $_GET['region'] ?? 'ALL';

// 2. Fetch Data
$rrService = new \App\RunningReceivablesService($pdo);
$data = $rrService->getReportData($selectedPeriod, $selectedHalf === 'ALL' ? null : $selectedHalf, $selectedStatus, $selectedRegion);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Running Receivables');

// Ensure correct timezone for the "As of" date
date_default_timezone_set('Asia/Manila');
$currentDateTime = date('F j, Y g:i A');

// Format text for the Filters display
$displayMonth = date('F Y', strtotime($selectedPeriod . '-01'));

$displayHalf = "Full Month";
if ($selectedHalf === '1ST') $displayHalf = "First Half";
if ($selectedHalf === '2ND') $displayHalf = "Second Half";

$displayStatus = "Ongoing Accounts";
if ($selectedStatus === 'FULLY_PAID') $displayStatus = "Fully Paid Accounts";
if ($selectedStatus === 'ALL') $displayStatus = "All Accounts";

$displayRegion = ($selectedRegion === 'ALL') ? "All Regions" : strtoupper($selectedRegion);

// ==========================================
// HEADER & FILTERS SECTION (Rows 1–4)
// ==========================================
$sheet->setCellValue('A1', 'Motorcycle Loan');
$sheet->setCellValue('A2', 'Running Accounts Receivable');
$sheet->setCellValue('A3', 'As of: ' . $currentDateTime);
$sheet->setCellValue('A4', "Filters Applied  ➔  Period: $displayMonth  |  Coverage: $displayHalf  |  Status: $displayStatus  |  Region: $displayRegion");

$sheet->getStyle('A1:A3')->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 11,
        'bold' => true
    ]
]);

$sheet->getStyle('A4')->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 11,
        'bold' => true,
        'color' => ['argb' => 'FF475569'] // Slate-600 color for distinction
    ]
]);

// ==========================================
// COLUMN HEADERS (Rows 6–7)
// ==========================================
$headersRow6 = [
    'A' => 'Released Date',
    'B' => 'Borrower',
    'C' => 'Region / Division',
    'D' => 'Term (months)',
    'E' => 'Loan Amount',
    'F' => 'Interest Amount',
    'G' => 'Gross Amount',
    'H' => 'Total Payment Received',
    'J' => 'Running Accounts Receivable (PRINCIPAL)'
];

foreach ($headersRow6 as $col => $val) {
    $sheet->setCellValue($col . '6', $val);
}

// Sub-headers for Row 7
$sheet->setCellValue('H7', 'Principal');
$sheet->setCellValue('I7', 'Interest');

// Merge Cells for headers
$sheet->mergeCells('A6:A7');
$sheet->mergeCells('B6:B7');
$sheet->mergeCells('C6:C7');
$sheet->mergeCells('D6:D7');
$sheet->mergeCells('E6:E7');
$sheet->mergeCells('F6:F7');
$sheet->mergeCells('G6:G7');
$sheet->mergeCells('H6:I6');
$sheet->mergeCells('J6:J7');

// Style Column Headers
$sheet->getStyle('A6:J7')->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 12,
        'bold' => true
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]
]);

// Set Column Widths
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(35);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(16);
$sheet->getColumnDimension('I')->setWidth(16);
$sheet->getColumnDimension('J')->setWidth(22);

// ==========================================
// DATA ROWS (Starting Row 8)
// ==========================================
$row = 8;
$totals = ['E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0];

$accountingFormat = '_-* #,##0.00_-;\-* #,##0.00_-;_-* "-"??_-;_-@_-';

foreach ($data as $item) {
    
    // Column A: Date
    if ($item['loan_granted'] !== 'No Date' && !empty($item['loan_granted'])) {
        $excelDate = Date::PHPToExcel(strtotime($item['loan_granted']));
        $sheet->setCellValue('A' . $row, $excelDate);
        $sheet->getStyle('A' . $row)->getNumberFormat()->setFormatCode('mm-dd-yy');
    } else {
        $sheet->setCellValue('A' . $row, 'No Date');
    }
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => false],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);

    // Column B & C: Borrower & Region
    $sheet->setCellValue('B' . $row, $item['name']);
    $sheet->setCellValue('C' . $row, $item['region_division'] ?? 'N/A');
    $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray([
        'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => false],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
    ]);

    // Column D: Term
    $sheet->setCellValue('D' . $row, $item['term_months']);
    $sheet->getStyle('D' . $row)->applyFromArray([
        'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);

    // Column E-J: Financials
    $sheet->setCellValue('E' . $row, $item['loan_amount']);
    $sheet->setCellValue('F' . $row, $item['interest_amount']);
    $sheet->setCellValue('G' . $row, $item['gross_amount']);
    $sheet->setCellValue('H' . $row, $item['principal_paid']);
    $sheet->setCellValue('I' . $row, $item['interest_paid']);
    $sheet->setCellValue('J' . $row, $item['running_ar_principal']);

    $sheet->getStyle('E' . $row . ':J' . $row)->applyFromArray([
        'font' => ['name' => 'Arial', 'size' => 10, 'bold' => false],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getStyle('E' . $row . ':J' . $row)->getNumberFormat()->setFormatCode($accountingFormat);

    // Accumulate Totals
    $totals['E'] += $item['loan_amount'];
    $totals['F'] += $item['interest_amount'];
    $totals['G'] += $item['gross_amount'];
    $totals['H'] += $item['principal_paid'];
    $totals['I'] += $item['interest_paid'];
    $totals['J'] += $item['running_ar_principal'];

    $row++;
}

// ==========================================
// TOTALS ROW (After Data)
// ==========================================
$totalsRow = $row; 

$sheet->setCellValue('D' . $totalsRow, 'TOTALS:');
$sheet->getStyle('D' . $totalsRow)->applyFromArray([
    'font' => ['name' => 'Calibri', 'size' => 12, 'bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER]
]);

foreach ($totals as $col => $val) {
    $sheet->setCellValue($col . $totalsRow, $val);
}

$sheet->getStyle('E' . $totalsRow . ':J' . $totalsRow)->applyFromArray([
    'font' => ['name' => 'Calibri', 'size' => 12, 'bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER]
]);
$sheet->getStyle('E' . $totalsRow . ':J' . $totalsRow)->getNumberFormat()->setFormatCode($accountingFormat);

// ==========================================
// APPLY BORDERS TO THE ENTIRE TABLE
// ==========================================
// This applies a thin black border to every cell from the headers down to the totals row.
$sheet->getStyle('A6:J' . $totalsRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000']
        ]
    ]
]);

// ==========================================
// Output to Browser
// ==========================================
$filename = "Running_Receivables_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;