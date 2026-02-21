<?php
error_reporting(0);
ini_set('display_errors', 0);

$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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
    $rows = $sheet->toArray();

    if (count($rows) > 0) {
        array_shift($rows); // Remove Header Row
    }

    $loanService = new \App\LoanService($pdo);
    $currentIdCounter = $loanService->getNextBorrowerId();
    
    $parsedData = [];
    $nameToIdMap = []; 
    $duplicateErrors = []; // Track duplicates to violently reject the file

    foreach ($rows as $index => $row) {
        // COLUMNS 0 & 1: Names
        $fname = trim($row[0] ?? '');
        $lname = trim($row[1] ?? '');
        
        if (empty($fname) || empty($lname)) continue;

        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper("$fname $lname");

        // --- STRICT DUPLICATE REJECTION ---
        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $duplicateErrors[] = "$displayName (Excel Row " . ($index + 2) . ")";
            continue; 
        }

        // Mark as seen for this file
        $empId = $currentIdCounter;
        $nameToIdMap[$fullNameKey] = $empId; 
        $currentIdCounter++; 

        // COLUMNS 2, 3, 4: Financials
        $amount = floatval(str_replace(',', '', $row[2] ?? '0'));
        $deduction = floatval(str_replace(',', '', $row[3] ?? '0'));
        $termsInput = intval($row[4] ?? 0);
        $terms = $termsInput; 

        // COLUMNS 5 & 6: Dates
        $dateStr = $row[5] ?? '';
        $dateGranted = !empty($dateStr) ? date('Y-m-d', strtotime($dateStr)) : date('Y-m-d');
        
        $maturityStr = $row[6] ?? '';
        $maturityDate = !empty($maturityStr) ? date('Y-m-d', strtotime($maturityStr)) : date('Y-m-d', strtotime($dateGranted . " +$terms months"));

        // COLUMNS 7 & 8: Region and Division (NEWLY ADDED)
        // If the column doesn't exist in the excel or is blank, default to 'N/A'
        $regionInput = trim($row[7] ?? '');
        $divisionInput = trim($row[8] ?? '');
        
        $region = !empty($regionInput) ? strtoupper($regionInput) : 'N/A';
        $division = !empty($divisionInput) ? strtoupper($divisionInput) : 'N/A';

        // Defaults for missing internal columns
        $contact = '000-000-0000'; 
        $pnNumber = 'TBD';         

        if ($amount > 0 && $terms > 0 && $deduction > 0) {
            $calculation = $loanService->generatePreview($amount, $deduction, $terms, $dateGranted);
            
            $parsedData[] = [
                'id' => $empId,
                'first_name' => $fname,
                'last_name' => $lname,
                'name' => $displayName,
                'contact_number' => $contact,
                'region' => $region,
                'division' => $division,
                'loan_amount' => $amount,
                'terms' => $terms,
                'deduction' => $deduction,
                'pn_number' => $pnNumber,
                'loan_granted' => $dateGranted,
                'pn_maturity' => $maturityDate,
                'schedule' => $calculation['schedule'],
                'periodic_rate' => $calculation['periodic_rate'],
                'effective_yield' => $calculation['effective_yield'],
                'add_on_rate' => $calculation['add_on_rate']
            ];
        }
    }

    // --- BLOCK UPLOAD IF ANY DUPLICATES EXIST ---
    if (!empty($duplicateErrors)) {
        $errorMsg = "IMPORT REJECTED: DUPLICATES FOUND\n\nThe following borrowers already exist:\n" . implode("\n", array_slice($duplicateErrors, 0, 3));
        if (count($duplicateErrors) > 3) {
            $errorMsg .= "\n...and " . (count($duplicateErrors) - 3) . " more.";
        }
        throw new Exception($errorMsg);
    }

    if (empty($parsedData)) {
        throw new Exception("No valid borrower data found in the Excel file. Please check the format.");
    }

    echo json_encode(['success' => true, 'data' => $parsedData, 'count' => count($parsedData)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>