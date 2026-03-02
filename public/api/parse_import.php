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
    $rows = $sheet->toArray(null, true, true, false); // 0-indexed array (A=0, B=1, etc.)

    if (count($rows) > 0) {
        array_shift($rows); // Remove Header Row
    }

    $loanService = new \App\LoanService($pdo);
    $currentIdCounter = $loanService->getNextBorrowerId();
    
    $parsedData = [];
    $nameToIdMap = []; 
    $duplicateErrors = []; 
    
    // Counter to ensure unique PN numbers are generated during the batch preview
    $pnOffset = 0; 

    foreach ($rows as $index => $row) {
        // COLUMN D (Index 3): NAME
        $nameRaw = trim($row[3] ?? '');
        if (empty($nameRaw)) continue; 

        $nameParts = explode(' ', $nameRaw, 2);
        $fname = trim($nameParts[0]);
        $lname = isset($nameParts[1]) ? trim($nameParts[1]) : '';
        
        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper($nameRaw);

        // Check for duplicates by name
        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $duplicateErrors[] = "$displayName (Excel Row " . ($index + 2) . ")";
            continue; 
        }

        // COLUMN A (Index 0): ID NO.
        $providedId = trim($row[0] ?? '');
        if (!empty($providedId) && is_numeric($providedId)) {
            $empId = intval($providedId);
        } else {
            $empId = $currentIdCounter;
            $currentIdCounter++; 
        }

        $nameToIdMap[$fullNameKey] = $empId; 

        // Map the rest of the columns based on the new layout
        $refNo = trim($row[4] ?? '');                                    // E (4): REFERENCE NO.
        $amount = floatval(str_replace(',', '', $row[5] ?? '0'));        // F (5): AMOUNT
        $deduction = floatval(str_replace(',', '', $row[6] ?? '0'));     // G (6): DEDUCTIONS PER PAY DAY
        
        $termsRaw = trim($row[7] ?? '0');                                // H (7): TERMS
        $terms = intval(preg_replace('/[^0-9]/', '', $termsRaw)); 

        $dateStr = $row[8] ?? '';                                        // I (8): DATE RELEASED
        $dateGranted = date('Y-m-d');
        if (!empty($dateStr)) {
            $dateGranted = is_numeric($dateStr) ? Date::excelToDateTimeObject($dateStr)->format('Y-m-d') : date('Y-m-d', strtotime($dateStr));
        }

        $firstDedStr = $row[10] ?? '';                                   // K (10): FIRST DEDUCTION
        $firstDeduction = null;
        if (!empty($firstDedStr)) {
            $firstDeduction = is_numeric($firstDedStr) ? Date::excelToDateTimeObject($firstDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($firstDedStr));
        }

        $lastDedStr = $row[11] ?? '';                                    // L (11): LAST DEDUCTION
        $lastDeduction = null;
        if (!empty($lastDedStr)) {
            $lastDeduction = is_numeric($lastDedStr) ? Date::excelToDateTimeObject($lastDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($lastDedStr));
        }

        $region = trim($row[12] ?? 'N/A');                               // M (12): REGION
        $division = 'N/A';
        $contact = '000-000-0000'; 

        if ($amount > 0 && $terms > 0 && $deduction > 0) {
            
            // Pass the $pnOffset so the preview knows to increment the PN Number
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
                'loan_amount' => $amount,
                'terms' => $terms,
                'deduction' => $deduction,
                'pn_number' => $calculation['pn_number'],
                'loan_granted' => $dateGranted,
                'pn_maturity' => $calculation['maturity_date'],
                //'schedule' => $calculation['schedule'],
                'periodic_rate' => $calculation['periodic_rate'],
                'effective_yield' => $calculation['effective_yield'],
                'add_on_rate' => $calculation['add_on_rate'],
                'add_on_rate_decimal' => $calculation['add_on_rate_decimal']
            ];
            
            // Increment offset for the next row
            $pnOffset++;
        }
    }

    if (!empty($duplicateErrors)) {
        $errorMsg = "IMPORT REJECTED: DUPLICATES FOUND\n\nThe following borrowers already exist:\n" . implode("\n", array_slice($duplicateErrors, 0, 3));
        if (count($duplicateErrors) > 3) $errorMsg .= "\n...and " . (count($duplicateErrors) - 3) . " more.";
        throw new Exception($errorMsg);
    }

    if (empty($parsedData)) throw new Exception("No valid borrower data found in the Excel file.");
    echo json_encode(['success' => true, 'data' => $parsedData, 'count' => count($parsedData)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>