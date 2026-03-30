<?php
session_start();
require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function failExport(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);
if (!is_array($payload)) {
    failExport('Invalid export payload.');
}

if (empty($_SESSION['user_id'])) {
    failExport('Unauthorized.', 401);
}

$headers = $payload['headers'] ?? [];
$rows = $payload['rows'] ?? [];
$title = trim((string)($payload['title'] ?? 'All Loans'));
$generatedBy = trim((string)($payload['generatedBy'] ?? 'SYSTEM USER'));
$renderedAt = trim((string)($payload['renderedAt'] ?? ''));
$tab = preg_replace('/[^a-z_\-]/i', '', (string)($payload['tab'] ?? 'active'));
$reportLabel = trim((string)($payload['reportLabel'] ?? $title));

if (!is_array($headers) || count($headers) === 0) {
    failExport('Missing column headers.');
}
if (!is_array($rows)) {
    failExport('Invalid rows payload.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Borrowers');

$colCount = count($headers);
$lastCol = Coordinate::stringFromColumnIndex($colCount);

$sheet->mergeCells("A1:{$lastCol}2");
$sheet->getRowDimension(1)->setRowHeight(42);
$sheet->getRowDimension(2)->setRowHeight(42);

$headerImagePath = __DIR__ . '/../assets/img/header.png';
if (is_file($headerImagePath)) {
    $drawing = new Drawing();
    $drawing->setName('Borrowers Header');
    $drawing->setDescription('Borrowers Header');
    $drawing->setPath($headerImagePath);
    $drawing->setCoordinates('A1');
    $drawing->setOffsetX(6);
    $drawing->setOffsetY(4);
    $drawing->setHeight(78);
    $drawing->setWorksheet($sheet);
}

$sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'borders' => [
        'bottom' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
]);

$sheet->mergeCells("A3:{$lastCol}3");
// Place the report label in row 3 (remove the old 'Borrowers Information' title)
$sheet->setCellValue('A3', $reportLabel);
$sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'name' => 'Arial',
        'color' => ['rgb' => '0F172A']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'borders' => [
        'outline' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
]);
$sheet->getRowDimension(3)->setRowHeight(20);

// Headers should start at row 4 and data at row 5 so content begins at row 3 (the report label)
$headerRow = 4;
for ($i = 0; $i < $colCount; $i++) {
    $column = Coordinate::stringFromColumnIndex($i + 1);
    $sheet->setCellValue($column . $headerRow, (string)$headers[$i]);
}

$sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'name' => 'Arial',
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => 'CE1126']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
]);

$dataStartRow = $headerRow + 1;
$currentRow = $dataStartRow;
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }

    for ($i = 0; $i < $colCount; $i++) {
        $column = Coordinate::stringFromColumnIndex($i + 1);
        $value = isset($row[$i]) ? (string)$row[$i] : '';
        $sheet->setCellValueExplicit($column . $currentRow, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }

    $currentRow++;
}

$lastDataRow = max($dataStartRow, $currentRow - 1);
$sheet->getStyle("A{$dataStartRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
    'font' => [
        'name' => 'Arial',
        'size' => 10,
        'color' => ['rgb' => '0F172A']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CBD5E1']
        ]
    ]
]);

for ($r = $dataStartRow; $r <= $lastDataRow; $r++) {
    if (($r - $dataStartRow) % 2 === 1) {
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
    }
}

$footerRow = $lastDataRow + 1;
$sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
$sheet->setCellValue("A{$footerRow}", "Generated By: " . $generatedBy . "\nGenerated Date and Time: " . $renderedAt);
$sheet->getStyle("A{$footerRow}:{$lastCol}{$footerRow}")->applyFromArray([
    'font' => [
        'name' => 'Arial',
        'size' => 10,
        'color' => ['rgb' => '475569']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ]
]);
$sheet->getRowDimension($footerRow)->setRowHeight(36);

for ($i = 1; $i <= $colCount; $i++) {
    $column = Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->freezePane('A' . ($dataStartRow));

$safeTab = $tab !== '' ? $tab : 'active';
$fileName = sprintf('borrowers_%s_report_%s.xlsx', $safeTab, date('Y-m-d'));

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
