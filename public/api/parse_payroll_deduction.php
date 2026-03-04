<?php
error_reporting(0);
ini_set('display_errors', 0);

$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}
if (!isset($_FILES['file']['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

/**
 * THE DEFINITIVE FIX — based on actual Excel file inspection:
 *
 * The column B cell stores dates as numeric serials (data_type='d'),
 * with format code 'mm-dd-yy'. The serial itself is set by Excel at
 * the moment the user types the date — and if Excel's regional setting
 * is D/M/Y, typing "10/02/2026" makes Excel store October 2, not Feb 10.
 *
 * So the serial is already wrong before our code even runs.
 *
 * The ONLY safe fix: force column B to Text format ('@') in the template
 * so Excel never interprets the date. Then the raw value is the string
 * the user typed (e.g. "02/10/2026"), and we parse it explicitly as M/D/Y.
 *
 * This function handles BOTH cases:
 *   - Text cell (@): parse string strictly as M/D/Y
 *   - Date cell (numeric serial): use the serial as-is (trust Excel only
 *     if the template column is properly formatted as mm-dd-yy on a M/D/Y system)
 */
function resolveDate($rawValue, $cell): ?DateTime
{
    $formatCode = '';
    try {
        $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();
    } catch (Exception $e) {}

    $isTextCell = ($formatCode === '@' || $cell->getDataType() === 's');

    // ── TEXT CELL: value is exactly what the user typed ──────────────────
    // Parse strictly as M/D/Y — never let PHP guess the order.
    if ($isTextCell || is_string($rawValue)) {
        $text = trim((string)$rawValue);
        if (empty($text)) return null;

        // Normalise separators to '/'
        $text = preg_replace('/[\-\.\s]+/', '/', $text);
        $text = trim(preg_replace('/\/+/', '/', $text), '/');

        $parts = explode('/', $text);
        if (count($parts) === 3) {
            [$m, $d, $y] = $parts;
            if (strlen($y) === 2) $y = '20' . $y;

            // Validate ranges before building
            $mi = (int)$m; $di = (int)$d; $yi = (int)$y;
            if ($mi >= 1 && $mi <= 12 && $di >= 1 && $di <= 31 && $yi >= 2000) {
                $dt = DateTime::createFromFormat('Y-n-j', "$yi-$mi-$di");
                if ($dt) return $dt;
            }
        }
        return null;
    }

    // ── NUMERIC SERIAL: only trustworthy if the template column is mm-dd-yy
    //    on a machine whose regional setting is M/D/Y (US format).
    //    If staff typed 02/10/2026 in D/M/Y Excel, the serial is already wrong.
    //    We use it only as a fallback when no text alternative exists.
    if (is_numeric($rawValue) && (float)$rawValue >= 40000) {
        try {
            $dt   = ExcelDate::excelToDateTimeObject((float)$rawValue);
            $year = (int)$dt->format('Y');
            if ($year >= 2000 && $year <= 2099) return $dt;
        } catch (Exception $e) {}
    }

    return null;
}

try {
    $inputFileName = $_FILES['file']['tmp_name'];
    if (!is_readable($inputFileName)) throw new Exception("Uploaded file cannot be read.");

    $spreadsheet = IOFactory::load($inputFileName);
    $sheet       = $spreadsheet->getActiveSheet();

    // formatData = FALSE → preserve numeric serials as floats
    $rows = $sheet->toArray(null, true, false, true);
    if (count($rows) > 0) array_shift($rows); // remove header

    $parsedData       = [];
    $validationErrors = [];
    $rowIndex         = 2;

    foreach ($rows as $row) {
        $rowVals = array_values($row);
        $idStr   = trim($rowVals[0] ?? '');
        if (empty($idStr)) break;

        $id      = intval($idStr);
        $rawDate = $rowVals[1] ?? '';
        $lname   = trim($rowVals[2] ?? '');
        $fname   = trim($rowVals[3] ?? '');
        $amount  = (float)str_replace([',', ' '], '', trim((string)($rowVals[4] ?? '')));

        $cell    = $sheet->getCell('B' . $rowIndex);
        $dtObj   = resolveDate($rawDate, $cell);

        $isoDate     = $dtObj ? $dtObj->format('Y-m-d') : '';
        $displayDate = $dtObj ? $dtObj->format('m/d/Y')  : '';

        $missingFields = [];
        if (empty($isoDate))                $missingFields[] = 'Payroll Date';
        if (empty($lname) && empty($fname)) $missingFields[] = 'Borrower Name';
        if ($amount <= 0)                   $missingFields[] = 'Deduction Amount';

        if (!empty($missingFields)) {
            $validationErrors[] = "Row {$rowIndex}: Missing or invalid data for " . implode(', ', $missingFields);
            $rowIndex++;
            continue;
        }

        $parsedData[] = [
            'id'       => $id,
            'date'     => $displayDate, // m/d/Y — preview only
            'iso_date' => $isoDate,     // Y-m-d — DB insert, unambiguous
            'lname'    => $lname,
            'fname'    => $fname,
            'amount'   => $amount,
        ];

        $rowIndex++;
    }

    if (!empty($validationErrors)) {
        throw new Exception(
            "PAYROLL IMPORT REJECTED:\n" .
            implode("\n", array_slice($validationErrors, 0, 5)) .
            (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . " more." : "")
        );
    }

    if (empty($parsedData)) throw new Exception("No valid payroll deduction data found.");

    echo json_encode(['success' => true, 'data' => $parsedData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>