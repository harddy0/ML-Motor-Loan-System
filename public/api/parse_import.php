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

// ─── MOVED OUTSIDE try{} ─────────────────────────────────────────────────────
/**
 * Normalises a Y-m-d date string to a valid semi-monthly payroll day.
 *  10  -> 15   (legacy 10/25 cycle)
 *  25  -> 30   (legacy 10/25 cycle, or last day if month < 30 days)
 *  31  -> last day of month
 *  30 in Feb or short month -> last day of month
 */
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

    if (count($rows) > 0) array_shift($rows); // Remove header row

    $loanService      = new \App\LoanService($pdo);
    $masterService    = new \App\MasterDataService($pdo, $pdo2);
    $currentIdCounter = $loanService->getNextBorrowerId();

    // ─── LOAD VALID REGIONS FROM MASTERDATA ──────────────────────────────────
    // Fetches para_region AND region_description columns — both are valid.
    // All comparisons are done uppercase-trimmed so casing in the Excel doesn't matter.
    $validRegions = $masterService->getValidRegions();
    
    // Create a normalized map by stripping all non-alphanumeric characters (spaces, hyphens, parentheses)
    $normalizedValidRegions = [];
    foreach ($validRegions as $vReg) {
        $cleanReg = preg_replace('/[^A-Z0-9]/', '', strtoupper($vReg));
        $normalizedValidRegions[$cleanReg] = $vReg; // Keep original for correct DB assignment
    }

    $parsedData       = [];
    $nameToIdMap      = [];
    $skippedOngoing   = [];   // Rows skipped — employee already has ONGOING loan
    $skippedKptn      = [];   // Rows skipped — KPTN code/amount mismatch
    $validationErrors = [];   // Rows skipped — missing required loan fields
    $regionErrors     = [];   // Rows with invalid/unrecognised regions — causes FULL REJECTION
    $pnOffset         = 0;

    foreach ($rows as $index => $row) {
        $rowNum = $index + 2;

        // F (Index 5): NAME
        $nameRaw = trim($row[5] ?? '');

        // Stop at first blank name row — end of data
        if (empty($nameRaw)) {
            break;
        }

        $nameParts   = explode(' ', $nameRaw, 2);
        $fname       = trim($nameParts[0]);
        $lname       = isset($nameParts[1]) ? trim($nameParts[1]) : '';
        $fullNameKey = strtoupper($fname . '|' . $lname);
        $displayName = strtoupper($nameRaw);

        // ─── REGION VALIDATION (before anything else) ─────────────────────
        // O (Index 14): REGION
        $regionRaw    = trim($row[14] ?? '');
        // Strip everything except letters and numbers for comparison
        $regionNorm   = preg_replace('/[^A-Z0-9]/', '', strtoupper($regionRaw));

        if (empty($regionRaw)) {
            // Blank region — treat as invalid so staff are forced to fill it in
            $regionErrors[] = "$displayName (Row $rowNum): Region is blank. Please fill in a valid region.";
        } elseif (!empty($normalizedValidRegions) && !isset($normalizedValidRegions[$regionNorm])) {
            // Normalized value is present but not in the masterdata list
            $regionErrors[] = "$displayName (Row $rowNum): Region \"$regionRaw\" does not match any region in the system.";
        } else if (!empty($normalizedValidRegions) && isset($normalizedValidRegions[$regionNorm])) {
            // Perfect match found: Overwrite the Excel raw string with the exact system string
            // This ensures characters like hyphens/spaces are inserted into the DB exactly as the system expects them
            $regionRaw = $normalizedValidRegions[$regionNorm];
        }

        // --- ONGOING LOAN CHECK ---
        if (isset($nameToIdMap[$fullNameKey]) || $loanService->isBorrowerExists($fname, $lname)) {
            $skippedOngoing[] = "$displayName (Row $rowNum): Already has an ONGOING loan in the system.";
            continue;
        }

        // A (Index 0): ID NO.
        $providedId = trim($row[0] ?? '');
        $empId      = (!empty($providedId) && is_numeric($providedId)) ? intval($providedId) : $currentIdCounter++;
        $nameToIdMap[$fullNameKey] = $empId;

        // Extract required fields
        $amountRaw    = trim($row[7]  ?? '');   // H (7):  AMOUNT
        $deductionRaw = trim($row[8]  ?? '');   // I (8):  DEDUCTIONS
        $termsRaw     = trim($row[9]  ?? '');   // J (9):  TERMS
        $dateStr      = trim($row[10] ?? '');   // K (10): DATE RELEASED
        $firstDedStr  = trim($row[12] ?? '');   // M (12): FIRST DEDUCTION
        $lastDedStr   = trim($row[13] ?? '');   // N (13): LAST DEDUCTION

        $missingFields = [];
        if (empty($amountRaw)    || floatval(str_replace(',', '', $amountRaw))    <= 0) $missingFields[] = 'Loan Amount';
        if (empty($termsRaw)     || intval(preg_replace('/[^0-9]/', '', $termsRaw)) <= 0) $missingFields[] = 'Terms';
        if (empty($deductionRaw) || floatval(str_replace(',', '', $deductionRaw)) <= 0) $missingFields[] = 'Deductions';
        if (empty($dateStr))      $missingFields[] = 'Date Released';
        if (empty($firstDedStr))  $missingFields[] = 'First Deduction';
        if (empty($lastDedStr))   $missingFields[] = 'Last Deduction';

        if (!empty($missingFields)) {
            $validationErrors[] = "$displayName (Row $rowNum): Missing or invalid data for " . implode(', ', $missingFields);
            continue;
        }

        // B (1): KPTN CODE    C (2): KPTN AMOUNT    G (6): REFERENCE NO.
        $pendingKptn = trim($row[1] ?? '');
        $kptnAmount  = floatval(str_replace(',', '', $row[2] ?? '0'));
        $refNo       = trim($row[6] ?? '');

        $hasKptnCode   = !empty($pendingKptn);
        $hasKptnAmount = $kptnAmount > 0;

        // --- KPTN VALIDATION ---
        if ($hasKptnCode && !$hasKptnAmount) {
            $skippedKptn[] = "$displayName (Row $rowNum): KPTN code is present but amount is missing. Either clear the KPTN code or add the deposit amount.";
            continue;
        }

        if (!$hasKptnCode && $hasKptnAmount) {
            $skippedKptn[] = "$displayName (Row $rowNum): KPTN amount is present but KPTN code is missing. Either clear the amount or add the KPTN code.";
            continue;
        }

        // Both blank = no deposit. Both filled = deposit required.
        $requiresKptn = $hasKptnCode && $hasKptnAmount;

        $amount    = floatval(str_replace(',', '', $amountRaw));
        $deduction = floatval(str_replace(',', '', $deductionRaw));
        $terms     = intval(preg_replace('/[^0-9]/', '', $termsRaw));

        // Parse raw Excel serial dates or string dates
        $dateGranted    = is_numeric($dateStr)     ? Date::excelToDateTimeObject($dateStr)->format('Y-m-d')     : date('Y-m-d', strtotime($dateStr));
        $firstDeduction = is_numeric($firstDedStr) ? Date::excelToDateTimeObject($firstDedStr)->format('Y-m-d') : date('Y-m-d', strtotime($firstDedStr));
        $lastDeduction  = is_numeric($lastDedStr)  ? Date::excelToDateTimeObject($lastDedStr)->format('Y-m-d')  : date('Y-m-d', strtotime($lastDedStr));

        // Normalise payroll dates to 15/30 cycle (10->15, 25->30)
        $firstDeduction = normaliseSemiMonthlyDate($firstDeduction);
        $lastDeduction  = normaliseSemiMonthlyDate($lastDeduction);

        $region          = $regionRaw;       // O (14): keep original casing for DB
        $division        = 'N/A';
        $contact         = '000-000-0000';

        // E (4): MONTH — label grouping from the Excel sheet (e.g. "JANUARY")
        $loanMonth       = strtoupper(trim($row[4] ?? ''));

        // L (11): MODE OF PAYMENT (e.g. "SALARY DEDUCTION")
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
            'deduction'           => $deduction,
            'pn_number'           => $calculation['pn_number'],
            'loan_granted'        => $dateGranted,
            // Excel columns M & N — explicit schedule anchors for BATCH save
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

    // ─── REGION ERRORS — HARD BLOCK ──────────────────────────────────────────
    // If ANY row has an invalid or missing region, reject the ENTIRE upload.
    // Staff must fix the Excel and re-upload. No partial imports allowed when
    // region data is corrupted, as it would produce unfilterable bad records.
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
            'region_errors' => $regionErrors,   // structured array for JS rendering
        ]);
        exit;
    }

    // --- BUILD WARNINGS ---
    // Skipped rows are warnings, not hard failures.
    // Valid rows still get imported regardless.
    $warnings = [];

    if (!empty($skippedOngoing)) {
        $warnings[] = "SKIPPED — ALREADY IN SYSTEM (" . count($skippedOngoing) . "):\n" .
                      implode("\n", $skippedOngoing);
    }

    if (!empty($skippedKptn)) {
        $warnings[] = "SKIPPED — INVALID KPTN DATA (" . count($skippedKptn) . "):\n" .
                      implode("\n", $skippedKptn);
    }

    if (!empty($validationErrors)) {
        $warnings[] = "SKIPPED — INCOMPLETE DATA (" . count($validationErrors) . "):\n" .
                      implode("\n", array_slice($validationErrors, 0, 5)) .
                      (count($validationErrors) > 5 ? "\n...and " . (count($validationErrors) - 5) . " more." : "");
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