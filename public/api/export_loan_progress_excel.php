<?php
$noLayout = true;
require_once __DIR__ . '/../../src/includes/init.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function normalizeDateParam($value): ?string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return null;
    }

    $d = \DateTime::createFromFormat('Y-m-d', $raw);
    if (!$d || $d->format('Y-m-d') !== $raw) {
        return null;
    }

    return $raw;
}

try {
    $status = strtoupper(trim((string)($_GET['status'] ?? 'ALL')));
    $status = str_replace('_', ' ', $status);
    if (!in_array($status, ['ALL', 'ONGOING', 'FULLY PAID'], true)) {
        $status = 'ALL';
    }

    $fromDate = normalizeDateParam($_GET['from'] ?? '');
    $toDate = normalizeDateParam($_GET['to'] ?? '');

    if (($fromDate && !$toDate) || (!$fromDate && $toDate)) {
        http_response_code(422);
        echo 'Both from and to dates are required for date filtering.';
        exit;
    }

    if ($fromDate && $toDate && strcmp($fromDate, $toDate) > 0) {
        [$fromDate, $toDate] = [$toDate, $fromDate];
    }

    $service = new \App\DashboardService($pdo);
    $rows = $service->getLoanProgress($status, null, $fromDate, $toDate);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Loan Progress');

    $headerImagePath = __DIR__ . '/../assets/img/header.png';
    if (is_file($headerImagePath)) {
        $sheet->mergeCells('A1:H1');
        $sheet->getRowDimension(1)->setRowHeight(36);

        $drawing = new Drawing();
        $drawing->setName('Loan Progress Header');
        $drawing->setDescription('Loan Progress Header');
        $drawing->setPath($headerImagePath);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(6);
        $drawing->setOffsetY(2);
        $drawing->setHeight(30);
        $drawing->setWorksheet($sheet);

        $sheet->getStyle('A1:H1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
    }

    $headerRow = is_file($headerImagePath) ? 2 : 1;
    $dataStartRow = $headerRow + 1;

    $headers = [
        'A' . $headerRow => 'Employee ID',
        'B' . $headerRow => 'Full Name',
        'C' . $headerRow => 'Maturity Date',
        'D' . $headerRow => 'Last Paid Date',
        'E' . $headerRow => 'Gross',
        'F' . $headerRow => 'Payment',
        'G' . $headerRow => 'Balance',
        'H' . $headerRow => 'Progress',
    ];

    foreach ($headers as $cell => $label) {
        $sheet->setCellValue($cell, $label);
    }

    $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['argb' => 'FFFFFFFF'],
            'size' => 10,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFCE1126'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
        ],
    ]);

    // Keep amount headers aligned with amount columns
    $sheet->getStyle("E{$headerRow}:G{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->getColumnDimension('A')->setWidth(14);
    $sheet->getColumnDimension('B')->setWidth(28);
    $sheet->getColumnDimension('C')->setWidth(16);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(16);
    $sheet->getColumnDimension('F')->setWidth(16);
    $sheet->getColumnDimension('G')->setWidth(16);
    $sheet->getColumnDimension('H')->setWidth(12);

    $rowNum = $dataStartRow;
    $grossTotal = 0.0;
    $paymentTotal = 0.0;
    $balanceTotal = 0.0;
    $progressTotal = 0.0;
    $rowCount = 0;
    foreach ($rows as $row) {
        $sheet->setCellValueExplicit('A' . $rowNum, (string)($row['employe_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $rowNum, (string)($row['borrower_name'] ?? ''));

        $maturity = !empty($row['maturity_date']) && $row['maturity_date'] !== '0000-00-00'
            ? date('d-M-y', strtotime((string)$row['maturity_date']))
            : '--';
        $lastPaid = !empty($row['last_paid_due_date']) && $row['last_paid_due_date'] !== '0000-00-00'
            ? date('d-M-y', strtotime((string)$row['last_paid_due_date']))
            : '--';

        $sheet->setCellValue('C' . $rowNum, $maturity);
        $sheet->setCellValue('D' . $rowNum, $lastPaid);

        $sheet->setCellValue('E' . $rowNum, (float)($row['gross_total'] ?? 0));
        $sheet->setCellValue('F' . $rowNum, (float)($row['payment_total'] ?? 0));
        $sheet->setCellValue('G' . $rowNum, (float)($row['balance_total'] ?? 0));
        $sheet->setCellValue('H' . $rowNum, (int)($row['pct_done'] ?? 0) . '%');

        $grossTotal += (float)($row['gross_total'] ?? 0);
        $paymentTotal += (float)($row['payment_total'] ?? 0);
        $balanceTotal += (float)($row['balance_total'] ?? 0);
        $progressTotal += (float)($row['pct_done'] ?? 0);
        $rowCount++;

        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$rowNum}:B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getNumberFormat()->setFormatCode('_([$₱-340A]* #,##0.00_);_([$₱-340A]* (#,##0.00);_([$₱-340A]* "-"??_);_(@_)');

        $rowNum++;
    }

    if ($rowNum === $dataStartRow) {
        $sheet->mergeCells("A{$dataStartRow}:H{$dataStartRow}");
        $sheet->setCellValue("A{$dataStartRow}", 'No rows found for the selected status.');
        $sheet->getStyle("A{$dataStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$dataStartRow}")->getFont()->getColor()->setArgb('FF94A3B8');
        $sheet->getStyle("A{$dataStartRow}:H{$dataStartRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);
        $footerStartRow = $dataStartRow + 1;
    } else {
        $averageProgress = $rowCount > 0 ? ($progressTotal / $rowCount) : 0;

        $sheet->setCellValue('D' . $rowNum, 'TOTAL / AVG');
        $sheet->setCellValue('E' . $rowNum, $grossTotal);
        $sheet->setCellValue('F' . $rowNum, $paymentTotal);
        $sheet->setCellValue('G' . $rowNum, $balanceTotal);
        $sheet->setCellValue('H' . $rowNum, $averageProgress / 100);

        $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF8FAFC'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getNumberFormat()->setFormatCode('_([$₱-340A]* #,##0.00_);_([$₱-340A]* (#,##0.00);_([$₱-340A]* "-"??_);_(@_)');
        $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('0.00%');

        $footerStartRow = $rowNum + 1;
    }

    $generatedBy = strtoupper((string)($_SESSION['full_name'] ?? 'SYSTEM USER'));

    $tzName = $_ENV['APP_TIMEZONE'] ?? ($_ENV['TIMEZONE'] ?? 'Asia/Manila');
    try {
        $tz = new \DateTimeZone((string)$tzName);
    } catch (\Throwable $tzError) {
        $tz = new \DateTimeZone('Asia/Manila');
    }
    $now = new \DateTime('now', $tz);
    $generatedAt = $now->format('F d, Y h:i A');

    $sheet->mergeCells("A{$footerStartRow}:H{$footerStartRow}");
    $sheet->setCellValue("A{$footerStartRow}", "Generated By: {$generatedBy}");
    $sheet->getStyle("A{$footerStartRow}")->applyFromArray([
        'font' => ['size' => 10, 'color' => ['argb' => 'FF475569']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $footerDateRow = $footerStartRow + 1;
    $sheet->mergeCells("A{$footerDateRow}:H{$footerDateRow}");
    $sheet->setCellValue("A{$footerDateRow}", "Generated Date and Time: {$generatedAt}");
    $sheet->getStyle("A{$footerDateRow}")->applyFromArray([
        'font' => ['size' => 10, 'color' => ['argb' => 'FF475569']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $statusLabel = strtolower(str_replace(' ', '_', $status));
    $filename = 'loan_progress_' . $statusLabel . '_' . $now->format('Y_m_d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error generating loan progress export: ' . $e->getMessage();
}
