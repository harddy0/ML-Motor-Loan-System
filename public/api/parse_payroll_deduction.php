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
    
    // Set formatData to TRUE to read exact visual text
    $rows = $sheet->toArray(null, true, true, true);

    // Remove Header Row (ROW 1)
    if (count($rows) > 0) {
        array_shift($rows);
    }

    $parsedData = [];
    $validationErrors = []; 
    $rowIndex = 2; // Keep track of the actual Excel row number for error reporting


    foreach ($rows as $row) {
        $rowVals = array_values($row);
        $idStr = trim($rowVals[0] ?? ''); // Column A (IDNO)

        // STOP parsing immediately when a row has no value in column A (End of data)
        if (empty($idStr)) {
            break; 
        }

        $id = intval($idStr);
        $dateStr = trim($rowVals[1] ?? ''); // Column B (Payroll Date)
        $lname   = trim($rowVals[2] ?? ''); // Column C (Last Name)
        $fname   = trim($rowVals[3] ?? ''); // Column D (First Name)
        $amountStr = trim((string)($rowVals[4] ?? '')); // Column E (Amount)
        $amount = (float)str_replace([',', ' '], '', $amountStr);

        // 1. Validate Missing Data
        $missingFields = [];
        if (empty($dateStr)) $missingFields[] = 'Payroll Date';
        if (empty($lname) && empty($fname)) $missingFields[] = 'Borrower Name';
        if ($amount <= 0) $missingFields[] = 'Deduction Amount';

        if (!empty($missingFields)) {
            $validationErrors[] = "Row {$rowIndex}: Missing or invalid data for " . implode(', ', $missingFields);
            $rowIndex++;
            continue;
        }

       
        // 3. Format Date strictly
        $dateStr = str_replace(['-', '.'], '/', $dateStr);
        $formattedDate = date('m/d/Y'); 

        if (!empty($dateStr)) {
            if (is_numeric($dateStr)) {
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr);
                $formattedDate = $dateObj->format('m/d/Y');
            } else {
                $parts = explode('/', $dateStr);
                if (count($parts) === 3) {
                    $m = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $d = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $y = $parts[2];
                    
                    if (strlen($y) === 2) {
                        $y = '20' . $y;
                    }
                    
                    if ((int)$m > 12) {
                        $temp = $m;
                        $m = $d;
                        $d = $temp;
                    }
                    $formattedDate = "$m/$d/$y";
                } else {
                    $formattedDate = date('m/d/Y', strtotime($dateStr));
                }
            }
        }

        $parsedData[] = [
            'id'     => $id,
            'date'   => $formattedDate,
            'lname'  => $lname,
            'fname'  => $fname,
            'amount' => $amount
        ];

        $rowIndex++;
    }

    // Reject entire import if validation errors (missing data or unverified KPTNs) exist
    if (!empty($validationErrors)) {
        throw new Exception("PAYROLL IMPORT REJECTED:\n" . implode("\n", array_slice($validationErrors, 0, 5)) . (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . " more." : ""));
    }

    if (empty($parsedData)) {
        throw new Exception("No valid payroll deduction data found.");
    }

    echo json_encode(['success' => true, 'data' => $parsedData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>