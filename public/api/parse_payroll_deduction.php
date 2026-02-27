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
    
    // ✦ FIXED: Set formatData to TRUE. This forces PHP to read the exact visual text 
    // on your screen (e.g. "2/10/26") instead of Excel's confusing internal float numbers.
    $rows = $sheet->toArray(null, true, true, true);

    // Remove Header Row (ROW 1)
    if (count($rows) > 0) {
        array_shift($rows);
    }

    $parsedData = [];

    // Read rows top to bottom starting at ROW 2
    foreach ($rows as $row) {
        // Force array to sequential keys (0, 1, 2, 3...) to safely read columns A, B, C...
        $rowVals = array_values($row);

        $id = trim($rowVals[0] ?? ''); // Column A (IDNO)

        // STOP parsing immediately when a row has no value in column A
        if (empty($id)) {
            break; 
        }

        // Read exact on-screen text for other columns
        $dateStr = trim($rowVals[1] ?? ''); // Column B (Payroll Date)
        $lname   = trim($rowVals[2] ?? ''); // Column C (Last Name)
        $fname   = trim($rowVals[3] ?? ''); // Column D (First Name)
        $amount  = trim((string)($rowVals[4] ?? '')); // Column E (Amount)

        // ✦ ULTIMATE MONTH/DAY/YEAR PARSER ✦
        $dateStr = str_replace(['-', '.'], '/', $dateStr);
        $formattedDate = date('m/d/Y'); // Default to today as extreme fallback

        if (!empty($dateStr)) {
            // Check if Excel handed us a raw serial number unexpectedly
            if (is_numeric($dateStr)) {
                $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr);
                $formattedDate = $dateObj->format('m/d/Y');
            } else {
                // Manually force MM/DD/YYYY logic based on the text
                $parts = explode('/', $dateStr);
                if (count($parts) === 3) {
                    $m = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $d = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $y = $parts[2];
                    
                    // Expand 2-digit years ('26' -> '2026')
                    if (strlen($y) === 2) {
                        $y = '20' . $y;
                    }
                    
                    // If the "month" is greater than 12, Excel swapped M and D based on local PC settings. 
                    // Let's swap them back to enforce MM/DD!
                    if ((int)$m > 12) {
                        $temp = $m;
                        $m = $d;
                        $d = $temp;
                    }
                    
                    $formattedDate = "$m/$d/$y";
                } else {
                    // Basic string-to-time fallback
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
    }

    echo json_encode(['success' => true, 'data' => $parsedData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}