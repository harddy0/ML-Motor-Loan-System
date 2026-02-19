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

    // Remove Header Row (Assuming row 1 is headers: IDNO, PAYROLL DATE, FIRST NAME, LAST NAME, AMOUNT PAID, REGION)
    if (count($rows) > 0) {
        array_shift($rows);
    }

    $parsedData = [];

    foreach ($rows as $row) {
        // Skip completely empty rows
        if (empty(array_filter($row))) continue;

        $id = trim($row[0] ?? '');
        $dateStr = trim($row[1] ?? '');
        $fname = trim($row[2] ?? '');
        $lname = trim($row[3] ?? '');
        $amount = trim($row[4] ?? '');
        $region = trim($row[5] ?? '');

        // Handle Excel Date serial numbers
        if (is_numeric($dateStr)) {
            $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr);
            $formattedDate = $dateObj->format('m/d/Y');
        } else {
            $formattedDate = !empty($dateStr) ? date('m/d/Y', strtotime($dateStr)) : date('m/d/Y');
        }

        $parsedData[] = [
            'id' => $id,
            'date' => $formattedDate,
            'fname' => $fname,
            'lname' => $lname,
            'amount' => $amount,
            'region' => $region
        ];
    }

    echo json_encode(['success' => true, 'data' => $parsedData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}