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
 * Validates the borrower's loan status against business rules before allowing the file to process.
 * Responsibility: Enforce INACTIVE and ASSUMED payment guards.
 */
function validateLoanStatusForPayroll(PDO $pdo, int $employeId, string $isoDate, int $rowIndex): ?string {
    $stmt = $pdo->prepare("SELECT loan_id, current_status FROM Loan WHERE employe_id = ? ORDER BY loan_id DESC LIMIT 1");
    $stmt->execute([$employeId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) return null; // Defer missing loan errors to the matching engine

    // GUARD 1: INACTIVE LOAN CHECK
    if ($loan['current_status'] === 'INACTIVE') {
        return "Row {$rowIndex}: Upload Rejected. Borrower ID {$employeId} has an INACTIVE loan.";
    }

    // GUARD 2: OUTSTANDING ASSUMED PAYMENT CHECK
    if ($loan['current_status'] === 'ONGOING') {
        // Find if there are ANY assumed payments that DO NOT match the exact date of this payroll
        $stmtAssumed = $pdo->prepare("
            SELECT COUNT(*) FROM Amortization_Ledger 
            WHERE loan_id = ? AND status = 'ASSUMED' AND scheduled_date != ?
        ");
        $stmtAssumed->execute([$loan['loan_id'], $isoDate]);
        
        if ($stmtAssumed->fetchColumn() > 0) {
            return "Row {$rowIndex}: Upload Rejected. Borrower ID {$employeId} has an outstanding ASSUMED payment that must be settled first before applying new payroll deductions.";
        }
    }

    return null;
}

function resolveDate($rawValue, $cell): ?DateTime {
    $formatCode = '';
    try { $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode(); } catch (Exception $e) {}

    $isTextCell = ($formatCode === '@' || $cell->getDataType() === 's');

    if ($isTextCell || is_string($rawValue)) {
        $text = trim((string)$rawValue);
        if (empty($text)) return null;

        $text = preg_replace('/[\-\.\s]+/', '/', $text);
        $text = trim(preg_replace('/\/+/', '/', $text), '/');

        $parts = explode('/', $text);
        if (count($parts) === 3) {
            [$m, $d, $y] = $parts;
            if (strlen($y) === 2) $y = '20' . $y;

            $mi = (int)$m; $di = (int)$d; $yi = (int)$y;
            if ($mi >= 1 && $mi <= 12 && $di >= 1 && $di <= 31 && $yi >= 2000) {
                $dt = DateTime::createFromFormat('Y-n-j', "$yi-$mi-$di");
                if ($dt) return $dt;
            }
        }
        return null;
    }

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
    $rows        = $sheet->toArray(null, true, false, true);
    if (count($rows) > 0) array_shift($rows);

    $parsedData       = [];
    $validationErrors = [];
    $rowIndex         = 2;

    global $pdo; // Brought in from init.php

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

        // --- PRE-FLIGHT GUARD CHECK ---
        $domainError = validateLoanStatusForPayroll($pdo, $id, $isoDate, $rowIndex);
        if ($domainError) {
            $validationErrors[] = $domainError;
            $rowIndex++;
            continue;
        }

        $parsedData[] = [
            'id'       => $id,
            'date'     => $displayDate,
            'iso_date' => $isoDate,
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