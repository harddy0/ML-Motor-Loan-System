<?php
error_reporting(0);
ini_set('display_errors', 0);

$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

if (!isset($_FILES['file']['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

try {
    $inputFileName = $_FILES['file']['tmp_name'];
    if (!is_readable($inputFileName)) {
        throw new Exception("Uploaded file cannot be read.");
    }

    $spreadsheet = IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false); 

    if (count($rows) > 0) array_shift($rows); // Remove Header Row

    $loanService = new \App\LoanService($pdo);
    $currentIdCounter = $loanService->getNextBorrowerId();

    /**
     * Normalises a Y-m-d date string to a valid semi-monthly payroll day.
     * 10 -> 15  (legacy 10/25 cycle)
     * 25 -> 30  (legacy 10/25 cycle)
     * 31 -> 30  (or last day of month if month has < 30 days)
     * 30 in Feb or short month -> last day of month
     */
    function normaliseSemiMonthlyDate(string $dateStr): string {
        if (empty($dateStr)) return $dateStr;
        $d = new DateTime($dateStr);
        $year  = (int)$d->format('Y');
        $month = (int)$d->format('m');
        $day   = (int)$d->format('d');
        $daysInMonth = (int)(new DateTime("$year-$month-01"))->format('t');

        if ($day == 10) {
            $day = 15;
        } elseif ($day == 25) {
            $day = min(30, $daysInMonth);
        } elseif ($day > $daysInMonth || $day == 31) {
            $day = $daysInMonth;
        } elseif ($day == 30 && $daysInMonth < 30) {
            $day = $daysInMonth;
        }

        $d->setDate($year, $month, $day);
        return $d->format('Y-m-d');
    }
    
    $parsedData = [];
    $nameToIdMap = []; 
    $duplicateErrors = []; 
    $validationErrors = []; 
    $pnOffset = 0; 

    foreach ($rows as $index => $row) {
        $rowNum = $index + 2;

        // F (Index 5): NAME
        $nameRaw = trim($row[5] ?? '');

        // --- NEW LOGIC: STOP AT FIRST BLANK NAME ---
        // If we hit a row with no name, we consider the Excel file DONE and stop processing further.
        if (empty($nameRaw)) {
            break; 
        }

        $nameParts = explode(' ', $nameRaw, 2);
        $fname = trim($nameParts[0]);
        $lname = isset($nameParts[1]) ? trim($nameParts[1]) : '';
        
        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper($nameRaw);

        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $duplicateErrors[] = "$displayName (Excel Row $rowNum)";
            continue; 
        }

        // COLUMN A (Index 0): ID NO.
        $providedId = trim($row[0] ?? '');
        $empId = (!empty($providedId) && is_numeric($providedId)) ? intval($providedId) : $currentIdCounter++;
        $nameToIdMap[$fullNameKey] = $empId; 

        // Extract required fields
        $amountRaw = trim($row[7] ?? '');        // H (7): AMOUNT
        $deductionRaw = trim($row[8] ?? '');     // I (8): DEDUCTIONS
        $termsRaw = trim($row[9] ?? '');         // J (9): TERMS
        $dateStr = trim($row[10] ?? '');         // K (10): DATE RELEASED
        $firstDedStr = trim($row[12] ?? '');     // M (12): FIRST DEDUCTION
        $lastDedStr = trim($row[13] ?? '');      // N (13): LAST DEDUCTION
        
        $missingFields = [];
        if (empty($amountRaw) || floatval(str_replace(',', '', $amountRaw)) <= 0) $missingFields[] = 'Loan Amount';
        if (empty($termsRaw) || intval(preg_replace('/[^0-9]/', '', $termsRaw)) <= 0) $missingFields[] = 'Terms';
        if (empty($deductionRaw) || floatval(str_replace(',', '', $deductionRaw)) <= 0) $missingFields[] = 'Deductions';
        if (empty($dateStr)) $missingFields[] = 'Date Released';
        if (empty($firstDedStr)) $missingFields[] = 'First Deduction';
        if (empty($lastDedStr)) $missingFields[] = 'Last Deduction';

        if (!empty($missingFields)) {
            $validationErrors[] = "$displayName (Row $rowNum): Missing or invalid data for " . implode(', ', $missingFields);
            continue; 
        }

        $pendingKptn = trim($row[1] ?? '');                                  // B (1): KPTN
        $kptnAmount = floatval(str_replace(',', '', $row[2] ?? '0'));        // C (2): KPTN AMOUNT
        $refNo = trim($row[6] ?? '');                                        // G (6): REFERENCE NO.
        
        // --- INFER IF KPTN IS REQUIRED ---
        $requiresKptn = (!empty($pendingKptn) || $kptnAmount > 0) ? true : false;

        $amount = floatval(str_replace(',', '', $amountRaw));
        $deduction = floatval(str_replace(',', '', $deductionRaw));
        $terms = intval(preg_replace('/[^0-9]/', '', $termsRaw));

        // Parse raw Excel date values
        $dateGranted    = is_numeric($dateStr)     ? Date::excelToDateTimeObject($dateStr)->format('Y-m-d')     : date('Y-m-d', strtotime($dateStr));
        $firstDeduction = is_numeric($firstDedStr) ? Date::excelToDateTimeObject($firstDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($firstDedStr));
        $lastDeduction  = is_numeric($lastDedStr)  ? Date::excelToDateTimeObject($lastDedStr)->format('Y-m-d')  : date('Y-m-d', strtotime($lastDedStr));

        // Normalise payroll dates to 15/30 cycle: 10->15, 25->30, 31->last valid day of month
        // date_granted is the loan release date (not a payroll date), left unchanged
        $firstDeduction = normaliseSemiMonthlyDate($firstDeduction);
        $lastDeduction  = normaliseSemiMonthlyDate($lastDeduction);

        $region = trim($row[14] ?? 'N/A');                                   // O (14): REGION
        $division = 'N/A';
        $contact = '000-000-0000'; 

        $calculation = $loanService->generatePreview($amount, $terms, $dateGranted, $deduction, $firstDeduction, $lastDeduction, $pnOffset);
        
        $parsedData[] = [
            'id' => $empId,
            'first_name' => $fname,
            'last_name' => $lname,
            'name' => $displayName,
            'contact_number' => $contact,
            'region' => $region,
            'division' => $division,
            'reference_number' => $refNo,
            'requires_kptn' => $requiresKptn, // Passed to Frontend
            'pending_kptn' => $pendingKptn,
            'kptn_amount' => $kptnAmount > 0 ? $kptnAmount : 0, 
            'loan_amount' => $amount,
            'terms' => $terms,
            'deduction' => $deduction,
            'pn_number' => $calculation['pn_number'],
            'loan_granted' => $dateGranted,
            'pn_maturity' => $calculation['maturity_date'],
            'periodic_rate' => $calculation['periodic_rate'],
            'effective_yield' => $calculation['effective_yield'],
            'add_on_rate' => $calculation['add_on_rate'],
            'add_on_rate_decimal' => $calculation['add_on_rate_decimal']
        ];
        $pnOffset++;
    }

    if (!empty($duplicateErrors)) {
        throw new Exception("IMPORT REJECTED: DUPLICATES FOUND\n" . implode("\n", array_slice($duplicateErrors, 0, 5)));
    }

    if (!empty($validationErrors)) {
        throw new Exception("IMPORT REJECTED: INCOMPLETE DATA\n" . implode("\n", array_slice($validationErrors, 0, 5)) . (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . " more." : ""));
    }

    if (empty($parsedData)) throw new Exception("No valid borrower data found.");
    echo json_encode(['success' => true, 'data' => $parsedData, 'count' => count($parsedData)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
</file>