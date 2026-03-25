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

$masterService = new \App\MasterDataService($pdo, $pdo2);

// TRANSLATE FILTER: Name -> Code
$regionCodeForFilter = 'ALL';
if ($selectedRegion !== 'ALL') {
    $regionCodeForFilter = $masterService->getRegionCodeByName($selectedRegion) ?? $selectedRegion;
}

// 2. Fetch Data using the Code
$rrService = new \App\RunningReceivablesService($pdo);
$data = $rrService->getReportData($selectedPeriod, $selectedHalf === 'ALL' ? null : $selectedHalf, $selectedStatus, $regionCodeForFilter);

// TRANSLATE DISPLAY: Code -> Name for Excel
$masterData = $masterService->getRegionsAndDivisions();
$regionMap = [];
if (!empty($masterData['regions'])) {
    foreach ($masterData['regions'] as $r) {
        $regionMap[$r['value']] = strtoupper($r['label']);
    }
}

foreach ($data as &$row) {
    $code = $row['region_division'] ?? '';
    if (isset($regionMap[$code])) {
        $row['region_division'] = $regionMap[$code];
    }
}
unset($row);

// 3. Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Running Receivables');

// Ensure correct timezone for the "As of" date
date_default_timezone_set('Asia/Manila');

// Format text for the Filters display
$periodTs = strtotime($selectedPeriod . '-01');
$selectedMonthName = date('F', $periodTs);
$selectedYear = date('Y', $periodTs);
$currentDay = (int)date('j');
$daysInSelectedMonth = (int)date('t', $periodTs);
$asOfDay = min($currentDay, $daysInSelectedMonth);
$asOfLine = sprintf('As of %s %d, %s', $selectedMonthName, $asOfDay, $selectedYear);

$displayHalf = "Full Month";
if ($selectedHalf === '1ST') $displayHalf = "First Half";
if ($selectedHalf === '2ND') $displayHalf = "Second Half";

$displayStatus = "Ongoing Accounts";
if ($selectedStatus === 'FULLY_PAID') $displayStatus = "Fully Paid Accounts";
if ($selectedStatus === 'ALL') $displayStatus = "All Accounts";

$displayRegion = ($selectedRegion === 'ALL') ? "All Regions" : strtoupper($selectedRegion);

// ==========================================
// HEADER & FILTERS SECTION
// ==========================================

$sheet->mergeCells('A1:J1');
$sheet->mergeCells('A2:J2');
$sheet->mergeCells('A3:J3');
$sheet->mergeCells('A4:J4');
$sheet->mergeCells('A5:J5');
$sheet->mergeCells('A6:J6');

$sheet->setCellValue('A1', 'ML Motorcycle Loan');
$sheet->setCellValue('A2', 'Running Accounts Receivable');
$sheet->setCellValue('A3', $asOfLine);
$sheet->setCellValue('A4', "Coverage: {$displayHalf}");
$sheet->setCellValue('A5', "Status: {$displayStatus}");
$sheet->setCellValue('A6', "Region: {$displayRegion}");

$sheet->getStyle('A1:A3')->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 12,
        'bold' => true
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

$sheet->getStyle('A4:A6')->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 11,
        'bold' => true,
        'color' => ['argb' => 'FF475569'] // Slate-600 color for distinction
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// ==========================================
// COLUMN HEADERS
// ==========================================
$tableHeaderTopRow = 8;
$tableHeaderSubRow = 9;
$dataStartRow = 10;

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
    $sheet->setCellValue($col . $tableHeaderTopRow, $val);
}

// Sub-headers for Row 7
$sheet->setCellValue('H' . $tableHeaderSubRow, 'Principal');
$sheet->setCellValue('I' . $tableHeaderSubRow, 'Interest');

// Merge Cells for headers
$sheet->mergeCells('A' . $tableHeaderTopRow . ':A' . $tableHeaderSubRow);
$sheet->mergeCells('B' . $tableHeaderTopRow . ':B' . $tableHeaderSubRow);
$sheet->mergeCells('C' . $tableHeaderTopRow . ':C' . $tableHeaderSubRow);
$sheet->mergeCells('D' . $tableHeaderTopRow . ':D' . $tableHeaderSubRow);
$sheet->mergeCells('E' . $tableHeaderTopRow . ':E' . $tableHeaderSubRow);
$sheet->mergeCells('F' . $tableHeaderTopRow . ':F' . $tableHeaderSubRow);
$sheet->mergeCells('G' . $tableHeaderTopRow . ':G' . $tableHeaderSubRow);
$sheet->mergeCells('H' . $tableHeaderTopRow . ':I' . $tableHeaderTopRow);
$sheet->mergeCells('J' . $tableHeaderTopRow . ':J' . $tableHeaderSubRow);

// Style Column Headers
$sheet->getStyle('A' . $tableHeaderTopRow . ':J' . $tableHeaderSubRow)->applyFromArray([
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
// DATA ROWS
// ==========================================
$row = $dataStartRow;
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
$sheet->getStyle('A' . $tableHeaderTopRow . ':J' . $totalsRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000']
        ]
    ]
]);

// ==========================================
// GENERATED FOOTER (Below Table)
// ==========================================
$generatedRow = $totalsRow + 2;
$generatedByRow = $generatedRow + 1;
$generatedBy = strtoupper((string)($_SESSION['full_name'] ?? 'SYSTEM USER'));
$generatedAt = date('F j, Y g:i A');

$sheet->mergeCells("A{$generatedRow}:J{$generatedRow}");
$sheet->mergeCells("A{$generatedByRow}:J{$generatedByRow}");

$sheet->setCellValue("A{$generatedRow}", "Generated: {$generatedAt}");
$sheet->setCellValue("A{$generatedByRow}", "Generated by: {$generatedBy}");

$sheet->getStyle("A{$generatedRow}:A{$generatedByRow}")->applyFromArray([
    'font' => [
        'name' => 'Calibri',
        'size' => 11,
        'bold' => true,
        'color' => ['argb' => 'FF334155']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
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