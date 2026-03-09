<?php
$pageTitle = "RUNNING RECEIVABLES";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';

// --- LIVE DATA FETCHING ---
$receivables = [];
$selectedPeriod = $_GET['period'] ?? date('Y-m'); 
$selectedHalf = $_GET['half'] ?? 'ALL'; // Can be 'ALL', '1ST', or '2ND'
$selectedStatus = $_GET['status'] ?? 'ONGOING'; // Default to ongoing
$selectedRegion = $_GET['region'] ?? 'ALL'; // Added Region Filter

try {
    if (class_exists('\App\RunningReceivablesService')) {
        $rrService = new \App\RunningReceivablesService($pdo);
        $receivables = $rrService->getReportData($selectedPeriod, $selectedHalf === 'ALL' ? null : $selectedHalf, $selectedStatus, $selectedRegion);
    }
} catch (Exception $e) {
    $receivables = [];
}

// Display strings for the UI header
$displayHalf = "Whole Month";
if ($selectedHalf === '1ST') $displayHalf = "1st Half (Day 1-15)";
if ($selectedHalf === '2ND') $displayHalf = "2nd Half (Day 16-End)";

$displayStatus = "Ongoing Accounts";
if ($selectedStatus === 'FULLY_PAID') $displayStatus = "Fully Paid Accounts";
if ($selectedStatus === 'ALL') $displayStatus = "All Accounts";

// Derive selected year/month for inline pickers
[$_selY, $_selM] = explode('-', $selectedPeriod);
$selectedYearInt = (int)$_selY;
$selectedMonthInt = (int)$_selM;

// Aggregates matching Excel Totals Row
$total_loaned = array_sum(array_column($receivables, 'loan_amount'));
$total_interest = array_sum(array_column($receivables, 'interest_amount'));
$total_gross = array_sum(array_column($receivables, 'gross_amount'));
$total_principal_paid = array_sum(array_column($receivables, 'principal_paid'));
$total_interest_paid = array_sum(array_column($receivables, 'interest_paid'));
$total_ar_principal = array_sum(array_map(fn($r) => round((float)$r['running_ar_principal'], 2), $receivables));
?>

<style>
/* Table wrapper now perfectly fills its Flexbox parent */
.table-fixed-wrapper {
    height: 100%; 
    overflow-y: auto;
    overflow-x: auto;
    /* Firefox Support for custom scrollbars */
    scrollbar-width: thin !important;
    
}

/* Chrome, Edge, Safari Support for custom scrollbars - Added !important to override your style.css */
.table-fixed-wrapper::-webkit-scrollbar {
    height: 12px !important; 
    width: 12px !important;  
}
.table-fixed-wrapper::-webkit-scrollbar-track {
    background: #f8fafc !important; 
    border-top: 1px solid #e2e8f0 !important; 
}
.table-fixed-wrapper::-webkit-scrollbar-thumb {
    background-color: #ef4444 !important; /* A softer, lighter red */
    border-radius: 8px !important;
    border: 2px solid #f8fafc !important; /* Padding around the thumb */
}
.table-fixed-wrapper::-webkit-scrollbar-thumb:hover {
    background-color: #f87171 !important; /* Even lighter/softer on hover */
}

/* Fix the transparent background issue on scrolling headers */
#receivablesTable thead th {
    background-color: #ce1126 !important;
    color: white;
    z-index: 20;
    box-shadow: inset 0 -1px 0 #e2e8f0, inset 1px 0 0 #e2e8f0; 
    background-clip: padding-box; 
}

/* Ensure second row of the header sticks exactly under the first row */
#receivablesTable thead tr:nth-child(2) th {
    top: 30px !important; 
    z-index: 21;
    box-shadow: 0 -2px 0 #ce1126, inset 0 -1px 0 #e2e8f0, inset 1px 0 0 #e2e8f0 !important;
}

#receivablesTable tfoot td,
#receivablesTable tfoot th {
    position: sticky;
    bottom: 0;
    z-index: 30;
    background-color: #f1f5f9 !important; 
    box-shadow: inset 0 2px 0 #cbd5e1; 
}
</style>

<div class="mb-3 w-full -mt-4 shrink-0">
    <h1 class="text-2xl text-slate-800">
        Motorcycle Loan Running Accounts Receivable
    </h1>
    
    <div class="flex items-center gap-2 mt-2 flex-wrap">
        <span class="text-[14px] text-slate-800">As of </span>
        <h2 id="current-period-display" class="text-[14px] text-slate-700 flex items-center gap-2 flex-wrap">
            <?php
                $currentYear = date('Y');
                $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            ?>
            <div class="flex items-center gap-2">
                <select id="picker-month" onchange="quickChangePeriod()" class="border border-slate-50 px-3 py-1 text-sm text-slate-700 rounded-md">
                        <?php foreach($months as $idx => $m): ?>
                            <option value="<?= $idx + 1 ?>" <?= (($idx + 1) == $selectedMonthInt) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>    
                <select id="picker-year" onchange="quickChangePeriod()" class="border border-slate-50 px-3 py-1 text-sm text-slate-700 rounded-md">
                        <?php for($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= ($y == $selectedYearInt) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                        </select>
            </div>
            <span class="text-slate-700">|</span> 
            <select id="picker-half" onchange="quickChangeHalf()" class="border border-slate-50 px-3 py-1 text-sm text-slate-700 rounded-md">
                <option value="ALL" <?= ($selectedHalf === 'ALL') ? 'selected' : '' ?>>Whole Month</option>
                <option value="1ST" <?= ($selectedHalf === '1ST') ? 'selected' : '' ?>>1st Half (Day 1-15)</option>
                <option value="2ND" <?= ($selectedHalf === '2ND') ? 'selected' : '' ?>>2nd Half (Day 16-End)</option>
            </select>
            <span class="text-slate-700">|</span> 
            <select id="picker-status-inline" onchange="quickChangeStatus()" class="border border-slate-50 px-3 py-1 text-sm text-slate-700 rounded-md">
                <option value="ONGOING" <?= ($selectedStatus === 'ONGOING') ? 'selected' : '' ?>>Ongoing</option>
                <option value="FULLY_PAID" <?= ($selectedStatus === 'FULLY_PAID') ? 'selected' : '' ?>>Fully Paid</option>
                <option value="ALL" <?= ($selectedStatus === 'ALL') ? 'selected' : '' ?>>All</option>
            </select>

            <span class="text-slate-700">|</span> 
            <div class="relative">
                <select id="picker-region-inline" onchange="quickChangeRegion()" class="w-40 border border-slate-50 px-3 py-1 text-sm text-slate-700 rounded-md">
                    <?php if ($selectedRegion !== 'ALL'): ?>
                        <option value="<?= htmlspecialchars(strtoupper($selectedRegion)) ?>" selected><?= htmlspecialchars($selectedRegion) ?></option>
                    <?php endif; ?>
                    <option value="ALL" <?= ($selectedRegion === 'ALL') ? 'selected' : '' ?>>All Regions</option>
                </select>
                <div id="region-suggestions" class="absolute left-0 mt-1 w-full bg-white border border-slate-200 rounded shadow-sm hidden max-h-40 overflow-auto z-50"></div>
            </div>
        </h2>
    </div>
</div>

<div class="flex flex-col md:flex-row justify-between items-center gap-3 mb-2 w-full shrink-0">
    
    <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by Name" 
                class="w-full h-8 pl-14 pr-6 bg-white border border-slate-200 rounded-full 
                text-[15px] outline-none  placeholder:text-slate-300 placeholder:text-[13px]
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <button onclick="downloadExcelReport()" class="h-9 flex items-center gap-1 px-4 bg-[#e11d48] text-white rounded-full 
            text-[13px] 
            shadow-md hover:brightness-110 hover:shadow-lg
            transition-all duration-200 ease-in-out active:scale-[0.98]" 
            title="Download Report">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download
        </button>
    </div>
</div>

<div class="flex flex-col gap-2" style="height: calc(100vh - 150px); min-height: 500px;">
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 shrink-0">
        <div class="bg-white border-t-2 border-red-500 rounded-md shadow-sm py-1.5 px-2 relative overflow-hidden group hover:shadow-md transition-all text-center">
            <h3 class="text-slate-500 text-[11px] font-semibold mb-0 tracking-wide uppercase">Total Loan Amount</h3>
            <span class="text-base font-bold text-slate-800 tracking-tight">₱ <?= number_format($total_loaned, 2) ?></span>
        </div>

        <div class="bg-white border-t-2 border-red-500 rounded-md shadow-sm py-1.5 px-2 relative overflow-hidden group hover:shadow-md transition-all text-center">
            <h3 class="text-slate-500 text-[11px] font-semibold mb-0 tracking-wide uppercase">Total Expected Interest</h3>
            <span class="text-base font-bold text-slate-800 tracking-tight">₱ <?= number_format($total_interest, 2) ?></span>
        </div>

        <div class="bg-white border-t-2 border-red-500 rounded-md shadow-sm py-1.5 px-2 relative overflow-hidden group hover:shadow-md transition-all text-center">
            <h3 class="text-slate-500 text-[11px] font-semibold mb-0 tracking-wide uppercase">Total Principal Paid</h3>
            <span class="text-base font-bold text-slate-800 tracking-tight">₱ <?= number_format($total_principal_paid, 2) ?></span>
        </div>

       <div class="bg-white border-t-2 border-red-500 rounded-md shadow-sm py-1.5 px-2 relative overflow-hidden group hover:shadow-md transition-all text-center">
            <h3 class="text-slate-500 text-[11px] font-semibold mb-0 tracking-wide uppercase">Running AR (Principal)</h3>
            <span class="text-base font-bold text-[#ce1126] tracking-tight">₱ <?= number_format($total_ar_principal, 2) ?></span>
        </div>
    </div>

    <div class="flex-1 min-h-0 bg-white border border-slate-100 shadow-sm custom-scrollbar table-fixed-wrapper">
        <table class="w-full text-left border-collapse whitespace-nowrap" id="receivablesTable">
            <thead>
                <tr class="bg-[#ce1126] text-white relative z-20">
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-center sticky top-0">Date Released</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 min-w-[200px] sticky top-0">Borrower</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-center sticky top-0">Region / Division</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-center sticky top-0">Term(s)</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-right sticky top-0">Loan Amount</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-right sticky top-0">Interest Amount</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-right sticky top-0">Gross Amount</th>
                    <th colspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-center sticky top-0">Total Payment Received</th>
                    <th rowspan="2" class="px-4 py-1 text-[14px] border border-slate-200 text-right sticky top-0">Running Accounts Receivable<br><span class="text-[11px] font-normal">(PRINCIPAL)</span></th>
                </tr>
                <tr class="bg-[#ce1126] text-white relative z-10">
                    <th class="px-4 py-1 text-[13px] border border-slate-200 text-right sticky">Principal</th>
                    <th class="px-4 py-1 text-[13px] border border-slate-200 text-right sticky">Interest</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if(empty($receivables)): ?>
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">
                            No records found for this period. 
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($receivables as $row): ?>
                    <tr class="hover:bg-slate-50 transition-colors duration-150 cursor-default">
                        
                        <td class="px-4 py-1 text-[14px] text-slate-800 border border-slate-100 text-center">
                            <?= ($row['loan_granted'] === 'No Date') ? 'No Date' : date('F j, Y', strtotime($row['loan_granted'])) ?>
                        </td>

                        <td class="px-4 py-1 text-slate-800 border uppercase border-slate-100">
                            <div class="flex flex-col">
                                <span class="text-[14px]"><?= htmlspecialchars($row['name']) ?></span>
                            </div>
                        </td>

                        <td class="px-4 py-1 text-[14px] text-slate-600 border border-slate-100 text-center">
                            <?= htmlspecialchars($row['region_division'] ?? 'N/A') ?>
                        </td>

                        <td class="px-4 py-1 text-[13px] text-slate-800 border border-slate-100 text-center font-bold">
                            <?= htmlspecialchars($row['term_months']) ?>
                        </td>
                        
                        <td class="px-4 py-1 text-[13px] text-slate-800 text-right border border-slate-100">
                            <?= number_format($row['loan_amount'], 2) ?>
                        </td>
                        
                        <td class="px-4 py-1 text-[13px] text-slate-800 text-right border border-slate-100">
                            <?= number_format($row['interest_amount'], 2) ?>
                        </td>

                        <td class="px-4 py-1 text-[13px] text-slate-800 text-right border border-slate-100 bg-slate-50/50">
                            <?= number_format($row['gross_amount'], 2) ?>
                        </td>
                        
                        <td class="px-4 py-1 text-[13px] text-green-700 text-right border border-slate-100">
                            <?= number_format($row['principal_paid'], 2) ?>
                        </td>
                        
                        <td class="px-4 py-1 text-[13px] text-green-700 text-right border border-slate-100">
                            <?= number_format($row['interest_paid'], 2) ?>
                        </td>

                        <td class="px-4 py-1 text-[13px] font-bold text-red-700 text-right border border-slate-100 bg-red-50/30">
                            <?= number_format($row['running_ar_principal'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if(!empty($receivables)): ?>
            <tfoot>
                <tr class="bg-slate-100 font-bold border-t-2 border-slate-300">
                    <td colspan="4" class="px-4 py-1 text-[14px] text-slate-800 text-right border border-slate-200">GRAND TOTALS:</td>
                    <td class="px-4 py-3 text-[13px] text-slate-800 text-right border border-slate-200"><?= number_format($total_loaned, 2) ?></td>
                    <td class="px-4 py-3 text-[13px] text-slate-800 text-right border border-slate-200"><?= number_format($total_interest, 2) ?></td>
                    <td class="px-4 py-3 text-[13px] text-slate-800 text-right border border-slate-200"><?= number_format($total_gross, 2) ?></td>
                    <td class="px-4 py-3 text-[13px] text-slate-800 text-right border border-slate-200"><?= number_format($total_principal_paid, 2) ?></td>
                    <td class="px-4 py-3 text-[13px] text-slate-800 text-right border border-slate-200"><?= number_format($total_interest_paid, 2) ?></td>
                    <td class="px-4 py-3 text-[13px] text-red-700 text-right border border-slate-200"><?= number_format($total_ar_principal, 2) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/report_period_picker.php'; ?>

<script>
    const BASE_URL = "<?= defined('BASE_URL') ? BASE_URL : '' ?>";
</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/public/assets/js/running_receivables.js"></script>