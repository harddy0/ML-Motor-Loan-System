<?php
// FILE: public/api/export_deductions.php
$noLayout = true; // Prevents HTML wrapper
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // 1. Fetch Data
    $deductionService = new \App\PayrollDeductionService($pdo);
    $data = $deductionService->getAllDeductions();

    // 2. Capture Active Filters from the frontend URL parameters
    $searchTerm = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
    $fromDate = isset($_GET['from']) ? $_GET['from'] : '';
    $toDate = isset($_GET['to']) ? $_GET['to'] : '';

    // 3. Apply Filters
    $filteredData = [];
    foreach ($data as $row) {
        $searchableText = strtolower($row['id'] . ' ' . $row['first'] . ' ' . $row['last']);
        $rowDate = $row['raw_i_date']; 
        
        $matchesSearch = empty($searchTerm) || strpos($searchableText, $searchTerm) !== false;
        
        $matchesDate = true;
        if (!empty($fromDate) && $rowDate < $fromDate) $matchesDate = false;
        if (!empty($toDate) && $rowDate > $toDate) $matchesDate = false;

        if ($matchesSearch && $matchesDate) {
            $filteredData[] = $row;
        }
    }

    // 4. Initialize Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Deductions Report');

    // --- 5. Report Header Info ---
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'MOTORCYCLE LOAN SYSTEM - PAYROLL DEDUCTIONS REPORT');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFE11D48']], // Crimson
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);

    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', 'Generated on: ' . date('F d, Y h:i A'));
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF64748B']], // Slate 500
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);

    // Active Filters Display
    $filterText = "Filters applied: ";
    $filterText .= !empty($searchTerm) ? "Search: '$searchTerm' | " : "Search: None | ";
    $filterText .= !empty($fromDate) ? "From: $fromDate | " : "From: All | ";
    $filterText .= !empty($toDate) ? "To: $toDate" : "To: All";
    
    $sheet->mergeCells('A3:G3');
    $sheet->setCellValue('A3', $filterText);
    $sheet->getStyle('A3')->applyFromArray([
        'font' => ['size' => 9, 'color' => ['argb' => 'FF94A3B8']], 
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);

    // --- 6. Table Headers ---
    $startRow = 5;
    $headers = [
        'A' => 'ID NO.',
        'B' => 'PAYROLL DATE',
        'C' => 'FULL NAME',
        'D' => 'DEDUCTION AMOUNT',
        'E' => 'REGION',
        'F' => 'MATCH STATUS',
        'G' => 'DATE IMPORTED'
    ];

    foreach ($headers as $col => $value) {
        $sheet->setCellValue($col . $startRow, $value);
    }

    // Header Styling
    $sheet->getStyle("A{$startRow}:G{$startRow}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']], // Slate 900
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
    ]);

    // Set Column Widths
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(25);

    // --- 7. Populate Data ---
    $row = $startRow + 1;
    $totalAmount = 0;

    if (empty($filteredData)) {
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'No records found matching current filters.');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setArgb('FF94A3B8');
        $row++;
    } else {
        foreach ($filteredData as $dataRow) {
            $amount = (float)$dataRow['amount'];
            $totalAmount += $amount;
            $status = trim(strtoupper($dataRow['match_status']));

            // Force ID to be text so it doesn't drop leading zeros if they exist
            $sheet->setCellValueExplicit('A' . $row, $dataRow['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            $sheet->setCellValue('B' . $row, $dataRow['p_date']);
            $sheet->setCellValue('C' . $row, strtoupper($dataRow['last'] . ', ' . $dataRow['first']));
            $sheet->setCellValue('D' . $row, $amount);
            $sheet->setCellValue('E' . $row, $dataRow['region']);
            $sheet->setCellValue('F' . $row, $status);
            $sheet->setCellValue('G' . $row, $dataRow['i_date']);

            // Row alignment and borders
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
            ]);
            
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00'); // Currency format
            $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Conditional Status Colors
            if ($status === 'MATCHED') {
                $sheet->getStyle("F{$row}")->getFont()->getColor()->setArgb('FF15803D'); // Green
                $sheet->getStyle("F{$row}")->getFont()->setBold(true);
            } else {
                $sheet->getStyle("F{$row}")->getFont()->getColor()->setArgb('FFDC2626'); // Red
                $sheet->getStyle("F{$row}")->getFont()->setBold(true);
            }

            $row++;
        }
    }

    // --- 8. Table Column Totals ---
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->setCellValue("A{$row}", "TOTAL COLLECTION:");
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);

    $sheet->setCellValue("D{$row}", $totalAmount);
    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle("D{$row}")->getFont()->setBold(true)->getColor()->setArgb('FFE11D48'); // Crimson text
    
    // Background for total row
    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
    ]);

    // --- 9. Output to Browser ---
    $filename = "Deductions_Report_" . date('Y_m_d_His') . ".xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error generating report: " . $e->getMessage());
}
?>