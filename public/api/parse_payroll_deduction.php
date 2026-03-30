<?php
error_reporting(0);
ini_set('display_errors', 0);

$noLayout = true;
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}
if (!isset($_FILES['file']['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
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
    global $pdo; 

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
        } else {
            $parsedData[] = [
                'id'       => $id,
                'date'     => $displayDate,
                'iso_date' => $isoDate,
                'lname'    => $lname,
                'fname'    => $fname,
                'amount'   => $amount,
                'rowIndex' => $rowIndex
            ];
        }
        $rowIndex++;
    }

    if (!empty($validationErrors)) {
        throw new Exception("PAYROLL IMPORT REJECTED:\n" . implode("\n", array_slice($validationErrors, 0, 5)));
    }
    if (empty($parsedData)) throw new Exception("No valid payroll deduction data found.");

    // BULK MAP FETCH 
    $domainMap = [];
    $empIds = array_unique(array_filter(array_column($parsedData, 'id')));
    
    if (!empty($empIds)) {
        $inClause = implode(',', array_fill(0, count($empIds), '?'));
        
        $stmtLoans = $pdo->prepare("
            SELECT employe_id, loan_id, current_status
            FROM Loan l1
            WHERE loan_id = (SELECT MAX(loan_id) FROM Loan l2 WHERE l2.employe_id = l1.employe_id)
              AND employe_id IN ($inClause)
        ");
        $stmtLoans->execute(array_values($empIds));
        $loans = $stmtLoans->fetchAll(PDO::FETCH_ASSOC);
        
        $activeLoans = [];
        foreach ($loans as $l) {
            $domainMap[$l['employe_id']] = [
                'status' => $l['current_status'],
                'has_assumed' => false,
                'assumed_amount' => 0.00,
                'assumed_date' => null
            ];
            if ($l['current_status'] === 'ONGOING') {
                $activeLoans[] = $l['loan_id'];
            }
        }
        
        if (!empty($activeLoans)) {
            $inLoans = implode(',', array_fill(0, count($activeLoans), '?'));
            $stmtAssumed = $pdo->prepare("
                SELECT l.employe_id, al.total_payment, al.scheduled_date
                FROM Amortization_Ledger al
                JOIN Loan l ON al.loan_id = l.loan_id
                WHERE al.status = 'ASSUMED' AND l.loan_id IN ($inLoans)
                ORDER BY al.scheduled_date ASC
            ");
            $stmtAssumed->execute(array_values($activeLoans));
            $assumedList = $stmtAssumed->fetchAll(PDO::FETCH_ASSOC);
            
            $seenAssumed = [];
            foreach ($assumedList as $a) {
                $eid = $a['employe_id'];
                if (!isset($seenAssumed[$eid])) {
                    $seenAssumed[$eid] = true;
                    if (isset($domainMap[$eid])) {
                        $domainMap[$eid]['has_assumed'] = true;
                        $domainMap[$eid]['assumed_amount'] = (float)$a['total_payment'];
                        $domainMap[$eid]['assumed_date'] = $a['scheduled_date']; // Push exact date to JS
                    }
                }
            }
        }
    }

    foreach ($parsedData as &$dataRow) {
        $eid = $dataRow['id'];
        if (isset($domainMap[$eid])) {
            $dataRow['is_inactive'] = ($domainMap[$eid]['status'] === 'INACTIVE');
            $dataRow['has_assumed'] = $domainMap[$eid]['has_assumed'];
            $dataRow['assumed_amount'] = $domainMap[$eid]['assumed_amount'];
            $dataRow['assumed_date'] = $domainMap[$eid]['assumed_date'];
        } else {
            $dataRow['is_inactive'] = false;
            $dataRow['has_assumed'] = false;
        }
        unset($dataRow['rowIndex']); 
    }

    echo json_encode(['success' => true, 'data' => $parsedData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>