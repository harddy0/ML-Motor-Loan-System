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

function getMonthYearGroupLabel($value): string {
    $raw = trim((string)$value);
    if ($raw === '' || $raw === '0000-00-00') {
        return 'No Last Paid Date';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return 'No Last Paid Date';
    }

    return date('F Y', $ts);
}

function getReportLabelByStatus(string $status): string {
    $normalized = strtoupper(trim($status));
    if ($normalized === 'ONGOING') return 'Ongoing Loan Report';
    if ($normalized === 'FULLY PAID') return 'Fully Paid Loan Report';
    if ($normalized === 'INACTIVE') return 'Inactive Loan Report';
    return 'All Loan Report';
}

try {
    $status = strtoupper(trim((string)($_GET['status'] ?? 'ALL')));
    $status = str_replace('_', ' ', $status);
    if (!in_array($status, ['ALL', 'ONGOING', 'FULLY PAID', 'INACTIVE'], true)) {
        $status = 'ALL';
    }

    $reportLabel = getReportLabelByStatus($status);

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
    usort($rows, static function (array $a, array $b): int {
        $aTs = strtotime((string)($a['last_paid_due_date'] ?? '')) ?: 0;
        $bTs = strtotime((string)($b['last_paid_due_date'] ?? '')) ?: 0;
        return $bTs <=> $aTs;
    });

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Loan Progress');

    $headerImagePath = __DIR__ . '/../assets/img/header.png';
    if (is_file($headerImagePath)) {
        $sheet->mergeCells('A1:I1');
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

        $sheet->getStyle('A1:I1')->applyFromArray([
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

    $reportLabelRow = is_file($headerImagePath) ? 2 : 1;
    $sheet->mergeCells("A{$reportLabelRow}:I{$reportLabelRow}");
    $sheet->setCellValue("A{$reportLabelRow}", $reportLabel);
    $sheet->getStyle("A{$reportLabelRow}:I{$reportLabelRow}")->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['argb' => 'FF0F172A'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFFFFFFF'],
        ],
    ]);
    $sheet->getRowDimension($reportLabelRow)->setRowHeight(22);

    $headerRow = $reportLabelRow + 1;
    $dataStartRow = $headerRow + 1;

    $headers = [
        'A' . $headerRow => 'Status',
        'B' . $headerRow => 'Employee ID',
        'C' . $headerRow => 'Full Name',
        'D' . $headerRow => 'Maturity Date',
        'E' . $headerRow => 'Last Paid Date',
        'F' . $headerRow => 'Gross',
        'G' . $headerRow => 'Payment',
        'H' . $headerRow => 'Balance',
        'I' . $headerRow => 'Progress',
    ];

    foreach ($headers as $cell => $label) {
        $sheet->setCellValue($cell, $label);
    }

    $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
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
    $sheet->getStyle("F{$headerRow}:H{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $sheet->getColumnDimension('A')->setWidth(14);
    $sheet->getColumnDimension('B')->setWidth(14);
    $sheet->getColumnDimension('C')->setWidth(28);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(16);
    $sheet->getColumnDimension('F')->setWidth(16);
    $sheet->getColumnDimension('G')->setWidth(16);
    $sheet->getColumnDimension('H')->setWidth(16);
    $sheet->getColumnDimension('I')->setWidth(12);

    $rowNum = $dataStartRow;
    $grossTotal = 0.0;
    $paymentTotal = 0.0;
    $balanceTotal = 0.0;
    $progressTotal = 0.0;
    $rowCount = 0;
    $previousGroupLabel = '';
    foreach ($rows as $row) {
        $groupLabel = getMonthYearGroupLabel($row['last_paid_due_date'] ?? null);
        if ($groupLabel !== $previousGroupLabel) {
            $sheet->mergeCells("A{$rowNum}:I{$rowNum}");
            $sheet->setCellValue("A{$rowNum}", strtoupper($groupLabel));
            $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FF475569'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF8FAFC'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCBD5E1'],
                    ],
                ],
            ]);
            $rowNum++;
            $previousGroupLabel = $groupLabel;
        }

        $rawStatus = strtoupper(trim((string)($row['status'] ?? 'ONGOING')));
        $statusLabel = $rawStatus === 'FULLY PAID' ? 'Fully Paid' : ($rawStatus === 'INACTIVE' ? 'Inactive' : 'Ongoing');

        $sheet->setCellValue('A' . $rowNum, $statusLabel);
        $sheet->setCellValueExplicit('B' . $rowNum, (string)($row['employe_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C' . $rowNum, (string)($row['borrower_name'] ?? ''));

        $maturity = !empty($row['maturity_date']) && $row['maturity_date'] !== '0000-00-00'
            ? date('d-M-y', strtotime((string)$row['maturity_date']))
            : '--';
        $lastPaid = !empty($row['last_paid_due_date']) && $row['last_paid_due_date'] !== '0000-00-00'
            ? date('d-M-y', strtotime((string)$row['last_paid_due_date']))
            : '--';

        $sheet->setCellValue('D' . $rowNum, $maturity);
        $sheet->setCellValue('E' . $rowNum, $lastPaid);

        $sheet->setCellValue('F' . $rowNum, (float)($row['gross_total'] ?? 0));
        $sheet->setCellValue('G' . $rowNum, (float)($row['payment_total'] ?? 0));
        $sheet->setCellValue('H' . $rowNum, (float)($row['balance_total'] ?? 0));
        $sheet->setCellValue('I' . $rowNum, (int)($row['pct_done'] ?? 0) . '%');

        $grossTotal += (float)($row['gross_total'] ?? 0);
        $paymentTotal += (float)($row['payment_total'] ?? 0);
        $balanceTotal += (float)($row['balance_total'] ?? 0);
        $progressTotal += (float)($row['pct_done'] ?? 0);
        $rowCount++;

        $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D{$rowNum}:E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$rowNum}:H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("F{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('_([$₱-340A]* #,##0.00_);_([$₱-340A]* (#,##0.00);_([$₱-340A]* "-"??_);_(@_)');

        $rowNum++;
    }

    if ($rowNum === $dataStartRow) {
        $sheet->mergeCells("A{$dataStartRow}:I{$dataStartRow}");
        $sheet->setCellValue("A{$dataStartRow}", 'No rows found for the selected status.');
        $sheet->getStyle("A{$dataStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$dataStartRow}")->getFont()->getColor()->setArgb('FF94A3B8');
        $sheet->getStyle("A{$dataStartRow}:I{$dataStartRow}")->applyFromArray([
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

        $sheet->setCellValue('E' . $rowNum, 'TOTAL / AVG');
        $sheet->setCellValue('F' . $rowNum, $grossTotal);
        $sheet->setCellValue('G' . $rowNum, $paymentTotal);
        $sheet->setCellValue('H' . $rowNum, $balanceTotal);
        $sheet->setCellValue('I' . $rowNum, $averageProgress / 100);

        $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray([
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

        $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$rowNum}:H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("F{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('_([$₱-340A]* #,##0.00_);_([$₱-340A]* (#,##0.00);_([$₱-340A]* "-"??_);_(@_)');
        $sheet->getStyle("I{$rowNum}")->getNumberFormat()->setFormatCode('0.00%');

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

    $sheet->mergeCells("A{$footerStartRow}:I{$footerStartRow}");
    $sheet->setCellValue("A{$footerStartRow}", "Generated By: {$generatedBy}");
    $sheet->getStyle("A{$footerStartRow}")->applyFromArray([
        'font' => ['size' => 10, 'color' => ['argb' => 'FF475569']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $footerDateRow = $footerStartRow + 1;
    $sheet->mergeCells("A{$footerDateRow}:I{$footerDateRow}");
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
