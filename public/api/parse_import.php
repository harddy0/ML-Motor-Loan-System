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
    
    $parsedData = [];
    $nameToIdMap = []; 
    $duplicateErrors = []; 
    $pnOffset = 0; 

    foreach ($rows as $index => $row) {
        // COLUMN F (Index 5): NAME
        $nameRaw = trim($row[5] ?? '');
        if (empty($nameRaw)) continue; 

        $nameParts = explode(' ', $nameRaw, 2);
        $fname = trim($nameParts[0]);
        $lname = isset($nameParts[1]) ? trim($nameParts[1]) : '';
        
        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper($nameRaw);

        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $duplicateErrors[] = "$displayName (Excel Row " . ($index + 2) . ")";
            continue; 
        }

        // COLUMN A (Index 0): ID NO.
        $providedId = trim($row[0] ?? '');
        $empId = (!empty($providedId) && is_numeric($providedId)) ? intval($providedId) : $currentIdCounter++;
        $nameToIdMap[$fullNameKey] = $empId; 

        // Map NEW layout columns
        $pendingKptn = trim($row[1] ?? '');                                  // B (1): KPTN
        $kptnAmount = floatval(str_replace(',', '', $row[2] ?? '0'));        // C (2): KPTN AMOUNT
        
        $refNo = trim($row[6] ?? '');                                        // G (6): REFERENCE NO.
        $amount = floatval(str_replace(',', '', $row[7] ?? '0'));            // H (7): AMOUNT
        $deduction = floatval(str_replace(',', '', $row[8] ?? '0'));         // I (8): DEDUCTIONS PER PAY DAY
        
        $termsRaw = trim($row[9] ?? '0');                                    // J (9): TERMS
        $terms = intval(preg_replace('/[^0-9]/', '', $termsRaw)); 

        $dateStr = $row[10] ?? '';                                           // K (10): DATE RELEASED
        $dateGranted = date('Y-m-d');
        if (!empty($dateStr)) {
            $dateGranted = is_numeric($dateStr) ? Date::excelToDateTimeObject($dateStr)->format('Y-m-d') : date('Y-m-d', strtotime($dateStr));
        }

        $firstDedStr = $row[12] ?? '';                                       // M (12): FIRST DEDUCTION
        $firstDeduction = !empty($firstDedStr) ? (is_numeric($firstDedStr) ? Date::excelToDateTimeObject($firstDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($firstDedStr))) : null;

        $lastDedStr = $row[13] ?? '';                                        // N (13): LAST DEDUCTION
        $lastDeduction = !empty($lastDedStr) ? (is_numeric($lastDedStr) ? Date::excelToDateTimeObject($lastDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($lastDedStr))) : null;

        $region = trim($row[14] ?? 'N/A');                                   // O (14): REGION
        $division = 'N/A';
        $contact = '000-000-0000'; 

        if ($amount > 0 && $terms > 0 && $deduction > 0) {
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
                'pending_kptn' => $pendingKptn,
                'kptn_amount' => $kptnAmount > 0 ? $kptnAmount : 2500.00, // Default if blank
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
    }

    if (!empty($duplicateErrors)) {
        throw new Exception("IMPORT REJECTED: DUPLICATES FOUND\n" . implode("\n", array_slice($duplicateErrors, 0, 3)));
    }

    if (empty($parsedData)) throw new Exception("No valid borrower data found.");
    echo json_encode(['success' => true, 'data' => $parsedData, 'count' => count($parsedData)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>