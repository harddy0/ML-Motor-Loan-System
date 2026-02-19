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

    // Remove Header Row
    if (count($rows) > 0) {
        array_shift($rows);
    }

    $loanService = new \App\LoanService($pdo);
    
    // --- AUTO-INCREMENT LOGIC ---
    // Get the current highest ID from DB, then increment for each row in Excel
    $currentIdCounter = $loanService->getNextBorrowerId();
    
    $parsedData = [];

    foreach ($rows as $row) {
        // MAPPING BASED ON YOUR CSV:
        // Col 0: First Name
        // Col 1: Last Name
        // Col 2: Loan Amount
        // Col 3: Deduction Per Payday
        // Col 4: Terms (Total Payments) -> Note: Is this Months or Semi-Monthly count?
        //        * Assuming "Terms" usually means Months (e.g., 12, 24).
        //        * If your CSV "36" means "36 Payments" (18 months), change the logic below.
        // Col 5: Loan Granted
        // Col 6: Loan Maturity

        $fname = trim($row[0] ?? '');
        
        // Skip empty rows
        if (empty($fname)) continue;

        $lname = trim($row[1] ?? '');
        
        // Assign the next ID and increment the counter
        $empId = $currentIdCounter;
        $currentIdCounter++; 

        // Clean Numbers
        $amount = floatval(str_replace(',', '', $row[2] ?? '0'));
        $deduction = floatval(str_replace(',', '', $row[3] ?? '0'));
        $termsInput = intval($row[4] ?? 0);
        
        // IMPORTANT: Verify if "Terms" in Excel is Months or Payments
        // If Excel says "36" and implies Months, use as is.
        // If Excel says "36" and implies Payments (1.5 years), convert to months ($termsInput / 2).
        // DEFAULT ASSUMPTION: Excel contains MONTHS.
        $terms = $termsInput; 

        // Dates
        $dateStr = $row[5] ?? '';
        $dateGranted = !empty($dateStr) ? date('Y-m-d', strtotime($dateStr)) : date('Y-m-d');
        
        $maturityStr = $row[6] ?? '';
        $maturityDate = !empty($maturityStr) ? date('Y-m-d', strtotime($maturityStr)) : date('Y-m-d', strtotime($dateGranted . " +$terms months"));

        // Defaults for missing columns
        $contact = '000-000-0000'; 
        $region = 'HEAD OFFICE'; 
        $pnNumber = 'TBD';         

        if ($amount > 0 && $terms > 0 && $deduction > 0) {
            $calculation = $loanService->generatePreview($amount, $deduction, $terms, $dateGranted);
            
            $parsedData[] = [
                'id' => $empId,
                'first_name' => $fname,
                'last_name' => $lname,
                'name' => "$fname $lname",
                'contact_number' => $contact,
                'region' => $region,
                'loan_amount' => $amount,
                'terms' => $terms,
                'deduction' => $deduction,
                'pn_number' => $pnNumber,
                'loan_granted' => $dateGranted,
                'pn_maturity' => $maturityDate,
                
                // Attach Calculation Results
                'schedule' => $calculation['schedule'],
                'periodic_rate' => $calculation['periodic_rate'],
                'effective_yield' => $calculation['effective_yield'],
                'add_on_rate' => $calculation['add_on_rate']
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $parsedData, 'count' => count($parsedData)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>