<?php
// FILE: public/api/export_deductions.php
$noLayout = true; // Prevents HTML wrapper
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

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
        $rowDate = $row['raw_p_date']; 
        
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

    // --- 5. Header Image + Title ---
    $sheet->mergeCells('A1:E2');
    $sheet->getRowDimension(1)->setRowHeight(42);
    $sheet->getRowDimension(2)->setRowHeight(42);

    $headerImagePath = __DIR__ . '/../assets/img/header.png';
    if (is_file($headerImagePath)) {
        $drawing = new Drawing();
        $drawing->setName('Deductions Header');
        $drawing->setDescription('Deductions Header');
        $drawing->setPath($headerImagePath);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(6);
        $drawing->setOffsetY(4);
        $drawing->setHeight(78);
        $drawing->setWorksheet($sheet);
    }

    $sheet->getStyle('A1:E2')->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFFFFFFF']
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFCBD5E1']
            ]
        ]
    ]);

    $sheet->mergeCells('A3:E3');
    $sheet->setCellValue('A3', 'Deduction Reports');
    $sheet->getStyle('A3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF0F172A']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => [
            'outline' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFCBD5E1']
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFFFFFFF']
        ]
    ]);
    $sheet->getRowDimension(3)->setRowHeight(24);

    // --- 6. Table Headers ---
    $startRow = 4;
    $headers = [
        'A' => 'ID NO.',
        'B' => 'PAYROLL DATE',
        'C' => 'FULL NAME',
        'D' => 'DEDUCTION AMOUNT',
        'E' => 'REGION',
    ];

    foreach ($headers as $col => $value) {
        $sheet->setCellValue($col . $startRow, $value);
    }

    // Header Styling
    $sheet->getStyle("A{$startRow}:E{$startRow}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCE2216']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]]
    ]);

    // Set Column Widths
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(15);

    // --- 7. Populate Data ---
    $row = $startRow + 1;
    $totalAmount = 0;

    if (empty($filteredData)) {
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", 'No records found matching current filters.');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setArgb('FF94A3B8');
        $row++;
    } else {
        foreach ($filteredData as $dataRow) {
            $amount = (float)$dataRow['amount'];
            $totalAmount += $amount;

            // Force ID to be text so it doesn't drop leading zeros if they exist
            $sheet->setCellValueExplicit('A' . $row, $dataRow['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            
            $sheet->setCellValue('B' . $row, $dataRow['p_date']);
            $sheet->setCellValue('C' . $row, strtoupper($dataRow['last'] . ', ' . $dataRow['first']));
            $sheet->setCellValue('D' . $row, $amount);
            $sheet->setCellValue('E' . $row, $dataRow['region']);

            // Row alignment and borders
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
            ]);
            
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
    $sheet->getStyle("D{$row}")->getFont()->setBold(true)->getColor()->setArgb('FFE11D48');
    
    // Background for total row
    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]]
    ]);

    // --- 9. Footer metadata ---
    $footerRow = $row + 1;
    $generatedBy = strtoupper((string)($_SESSION['full_name'] ?? 'SYSTEM USER'));
    $generatedAt = date('F d, Y h:i A');
    $sheet->mergeCells("A{$footerRow}:E{$footerRow}");
    $sheet->setCellValue("A{$footerRow}", "Generated by: {$generatedBy} | Generated: {$generatedAt}");
    $sheet->getStyle("A{$footerRow}:E{$footerRow}")->applyFromArray([
        'font' => ['size' => 10, 'color' => ['argb' => 'FF475569']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);

    // --- 10. Output to Browser ---
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