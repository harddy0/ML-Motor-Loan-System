<?php
error_reporting(0);
ini_set('display_errors', 0);

$noLayout = true;

// ─── FATAL ERROR GUARD ────────────────────────────────────────────────────────
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Fatal Server Error: ' . $error['message']]);
        exit;
    }
});

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
    exit;
}

if (!isset($_FILES['file']['tmp_name'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

function normaliseSemiMonthlyDate(string $dateStr): string {
    if (empty($dateStr)) return $dateStr;
    $d = new DateTime($dateStr);
    $year        = (int)$d->format('Y');
    $month       = (int)$d->format('m');
    $day         = (int)$d->format('d');
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

try {
    $inputFileName = $_FILES['file']['tmp_name'];
    if (!is_readable($inputFileName)) {
        throw new Exception("Uploaded file cannot be read.");
    }

    $spreadsheet = IOFactory::load($inputFileName);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray(null, true, true, false);

    if (count($rows) > 0) array_shift($rows); 

    $loanService      = new \App\LoanService($pdo);
    $masterService    = new \App\MasterDataService($pdo, $pdo2);
    $currentIdCounter = $loanService->getNextBorrowerId();

    $validRegions = $masterService->getValidRegions();
    $normalizedValidRegions = [];
    foreach ($validRegions as $vReg) {
        $cleanReg = preg_replace('/[^A-Z0-9]/', '', strtoupper($vReg));
        $normalizedValidRegions[$cleanReg] = $vReg; 
    }

    $parsedData       = [];
    $nameToIdMap      = [];
    $skippedOngoing   = [];   
    $skippedKptn      = [];   
    $validationErrors = [];   
    $regionErrors     = [];   
    $pnOffset         = 0;

    foreach ($rows as $index => $row) {
        $rowNum = $index + 2;

        $nameRaw = trim($row[5] ?? '');

        if (empty($nameRaw)) {
            break;
        }

        $nameParts   = explode(' ', $nameRaw, 2);
        $fname       = trim($nameParts[0]);
        $lname       = isset($nameParts[1]) ? trim($nameParts[1]) : '';
        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper($nameRaw);

        $regionRaw    = trim($row[14] ?? '');
        $regionNorm   = preg_replace('/[^A-Z0-9]/', '', strtoupper($regionRaw));

        if (empty($regionRaw)) {
            $regionErrors[] = "$displayName (Row $rowNum): Region is blank. Please fill in a valid region.";
        } elseif (!empty($normalizedValidRegions) && !isset($normalizedValidRegions[$regionNorm])) {
            $regionErrors[] = "$displayName (Row $rowNum): Region \"$regionRaw\" does not match any region in the system.";
        } else if (!empty($normalizedValidRegions) && isset($normalizedValidRegions[$regionNorm])) {
            $regionRaw = $normalizedValidRegions[$regionNorm];
        }

        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $skippedOngoing[] = "$displayName (Row $rowNum): Already has an ONGOING loan in the system.";
            continue;
        }

        $providedId = trim($row[0] ?? '');
        $empId      = (!empty($providedId) && is_numeric($providedId)) ? intval($providedId) : $currentIdCounter++;
        $nameToIdMap[$fullNameKey] = $empId;

        $amountRaw    = trim($row[7]  ?? '');   
        $deductionRaw = trim($row[8]  ?? '');   
        $termsRaw     = trim($row[9]  ?? '');   
        $dateStr      = trim($row[10] ?? '');   
        $firstDedStr  = trim($row[12] ?? '');   
        $lastDedStr   = trim($row[13] ?? '');   

        $missingFields = [];
        if (empty($amountRaw)    || floatval(str_replace(',', '', $amountRaw))    <= 0) $missingFields[] = 'Loan Amount';
        if (empty($termsRaw)     || intval(preg_replace('/[^0-9]/', '', $termsRaw)) <= 0) $missingFields[] = 'Terms';
        // MODIFIED: Deduction is now fully optional here.
        if (empty($dateStr))      $missingFields[] = 'Date Released';
        if (empty($firstDedStr))  $missingFields[] = 'First Deduction';
        if (empty($lastDedStr))   $missingFields[] = 'Last Deduction';

        if (!empty($missingFields)) {
            $validationErrors[] = "$displayName (Row $rowNum): Missing or invalid data for " . implode(', ', $missingFields);
            continue;
        }

        $pendingKptn = trim($row[1] ?? '');
        $kptnAmount  = floatval(str_replace(',', '', $row[2] ?? '0'));
        $refNo       = trim($row[6] ?? '');

        $hasKptnCode   = !empty($pendingKptn);
        $hasKptnAmount = $kptnAmount > 0;

        if ($hasKptnCode && !$hasKptnAmount) {
            $skippedKptn[] = "$displayName (Row $rowNum): KPTN code is present but amount is missing. Either clear the KPTN code or add the deposit amount.";
            continue;
        }

        if (!$hasKptnCode && $hasKptnAmount) {
            $skippedKptn[] = "$displayName (Row $rowNum): KPTN amount is present but KPTN code is missing. Either clear the amount or add the KPTN code.";
            continue;
        }

        $requiresKptn = $hasKptnCode && $hasKptnAmount;

        $amount    = floatval(str_replace(',', '', $amountRaw));
        $deduction = floatval(str_replace(',', '', $deductionRaw)); // Will be 0 if blank
        $terms     = intval(preg_replace('/[^0-9]/', '', $termsRaw));

        $dateGranted    = is_numeric($dateStr)     ? Date::excelToDateTimeObject($dateStr)->format('Y-m-d')     : date('Y-m-d', strtotime($dateStr));
        $firstDeduction = is_numeric($firstDedStr) ? Date::excelToDateTimeObject($firstDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($firstDedStr));
        $lastDeduction  = is_numeric($lastDedStr)  ? Date::excelToDateTimeObject($lastDedStr)->format('Y-m-d')  : date('Y-m-d', strtotime($lastDedStr));

        $firstDeduction = normaliseSemiMonthlyDate($firstDeduction);
        $lastDeduction  = normaliseSemiMonthlyDate($lastDeduction);

        $region          = $regionRaw;       
        $division        = 'N/A';
        $contact         = '000-000-0000';
        $loanMonth       = strtoupper(trim($row[4] ?? ''));
        $modeOfPayment   = strtoupper(trim($row[11] ?? ''));

        $calculation = $loanService->generatePreview(
            $amount, $terms, $dateGranted,
            $deduction, $firstDeduction, $lastDeduction,
            $pnOffset
        );

        $parsedData[] = [
            'id'                  => $empId,
            'first_name'          => $fname,
            'last_name'           => $lname,
            'name'                => $displayName,
            'contact_number'      => $contact,
            'region'              => $region,
            'division'            => $division,
            'reference_number'    => $refNo,
            'requires_kptn'       => $requiresKptn,
            'pending_kptn'        => $pendingKptn,
            'kptn_amount'         => $requiresKptn ? $kptnAmount : 0,
            'loan_amount'         => $amount,
            'terms'               => $terms,
            'deduction'           => $calculation['deduction'], // Use the calculated deduction!
            'pn_number'           => $calculation['pn_number'],
            'loan_granted'        => $dateGranted,
            'first_deduction'     => $firstDeduction,
            'last_deduction'      => $lastDeduction,
            'pn_maturity'         => $calculation['maturity_date'],
            'periodic_rate'       => $calculation['periodic_rate'],
            'effective_yield'     => $calculation['effective_yield'],
            'add_on_rate'         => $calculation['add_on_rate'],
            'add_on_rate_decimal' => $calculation['add_on_rate_decimal'],
            'loan_month'          => $loanMonth,
            'mode_of_payment'     => $modeOfPayment,
        ];
        $pnOffset++;
    }

    if (!empty($regionErrors)) {
        $count   = count($regionErrors);
        $listing = implode("\n", $regionErrors);

        $errorMsg  = "UPLOAD REJECTED — INVALID REGION DATA ($count row(s) failed):\n\n";
        $errorMsg .= $listing;
        $errorMsg .= "\n\nPlease correct the Region column in your Excel file and re-upload.\n";
        $errorMsg .= "Accepted values must match the regions registered in the system.";

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success'       => false,
            'error'         => $errorMsg,
            'region_errors' => $regionErrors,   
        ]);
        exit;
    }

    $warnings = [];

    if (!empty($skippedOngoing)) {
        $warnings[] = "SKIPPED — ALREADY IN SYSTEM (" . count($skippedOngoing) . "):\n" . implode("\n", $skippedOngoing);
    }

    if (!empty($skippedKptn)) {
        $warnings[] = "SKIPPED — INVALID KPTN DATA (" . count($skippedKptn) . "):\n" . implode("\n", $skippedKptn);
    }

    if (!empty($validationErrors)) {
        $warnings[] = "SKIPPED — INCOMPLETE DATA (" . count($validationErrors) . "):\n" . implode("\n", array_slice($validationErrors, 0, 5)) . (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . " more." : "");
    }

    if (empty($parsedData) && empty($warnings)) {
        throw new Exception("No valid borrower data found in the file.");
    }

    if (empty($parsedData) && !empty($warnings)) {
        throw new Exception("No rows could be imported.\n\n" . implode("\n\n", $warnings));
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success'  => true,
        'data'     => $parsedData,
        'count'    => count($parsedData),
        'warnings' => $warnings
    ]);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>