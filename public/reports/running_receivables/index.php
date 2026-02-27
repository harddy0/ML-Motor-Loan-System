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

// Aggregates
$total_loaned = array_sum(array_column($receivables, 'loan_amount'));
$total_outstanding = array_sum(array_column($receivables, 'outstanding_balance'));
$total_income = array_sum(array_column($receivables, 'period_income'));
$total_collected = array_sum(array_column($receivables, 'accumulated_payments')); 
?>

<div class="mb-3 w-full">
    <h1 class="text-2xl text-slate-800">
        Running Receivables
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

<div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4 w-full">
    
    <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by ID Number or Name" 
                class="w-full h-8 pl-14 pr-6 bg-white border border-slate-200 rounded-full 
                text-[16px] outline-none  placeholder:text-slate-300 placeholder:text-[13px]
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <button onclick="downloadExcelReport()" class="h-10 flex items-center gap-1 px-4 bg-[#e11d48] text-white rounded-full 
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

<div class="flex flex-col gap-6 h-full min-h-[550px]">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border-t-2 border-red-500 rounded-xl shadow-sm p-3 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
            </div>
            
            <h3 class="text-slate-800 text-[15px] mb-1">Total Loan</h3>
            <span class="text-2xl text-slate-800 tracking-tight">₱ <?= number_format($total_loaned, 2) ?></span>
        </div>

        <div class="bg-white border-t-2 border-red-500 rounded-xl shadow-sm p-3 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
            </div>
            
            <h3 class="text-slate-800 text-[15px] mb-1">Total Outstanding</h3>
            <span class="text-2xl text-slate-800 tracking-tight">₱ <?= number_format($total_outstanding, 2) ?></span>
        </div>

        <div class="bg-white border-t-2 border-red-500 rounded-xl shadow-sm p-3 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
            </div>
            
            <h3 class="text-slate-800 text-[15px] mb-1">Payment (This Month)</h3>
            <span class="text-2xl text-slate-800 tracking-tight">₱ <?= number_format($total_collected, 2) ?></span>
        </div>

       <div class="bg-white border-t-2 border-red-500 rounded-xl shadow-sm p-3 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
            </div>
            
            <h3 class="text-slate-800 text-[15px] mb-1">Income (This Month)</h3>
            <span class="text-2xl text-slate-800 tracking-tight">₱ <?= number_format($total_income, 2) ?></span>
        </div>
    </div>

    <div class="flex-1 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden flex flex-col">

        <div class="overflow-x-auto custom-scrollbar flex-1 rounded-b-xl border border-slate-100">
            <table class="w-full text-left border-collapse whitespace-nowrap" id="receivablesTable">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 sticky top-0 z-10">
                        <th class="px-4 py-4 text-[14px] border-r border-white/10 text-center w-24">Employee ID</th>
                        <th class="px-4 py-4 text-[14px] border-r border-white/10 min-w-[180px]">Borrower</th>
                        <th class="px-4 py-4 text-[14px] border-r border-white/10 text-center w-28">Region</th>
                        <th class="px-4 py-4 text-[14px] border-r border-white/10 text-center w-28">Loan Granted</th>
                        
                        <th class="px-4 py-4 text-[14px] text-right border-r border-white/10">
                            Amount Loan<br><span class="text-[12px] text-slate-800">(Base Principal)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-right border-r border-white/10 ">
                            Monthly Principal<br><span class="text-[12px] text-slate-800">(This Period)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-right border-r border-white/10 ">
                            Prior Principal<br><span class="text-[12px] text-slate-800">(Excl. This Period)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-right border-r border-white/10">
                            Payment<br><span class="text-[12px] text-slate-800 ">(Total Principal)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-right border-r border-white/10">
                            Outstanding Bal<br><span class="text-[12px] text-slate-800">(Remaining)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-right border-r border-white/10 ">
                            Monthly Income<br><span class="text-[12px] text-slate-800 ">(Interest)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[13px] text-center w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if(empty($receivables)): ?>
                        <tr>
                            <td colspan="11" class="px-6 py-8 text-center text-slate-800 text-[13px]">
                                No records found for this period. 
                                <?php if (!class_exists('\App\RunningReceivablesService')) echo "<br><span class='text-red-500'>Error: Service class not found. Please run 'composer dump-autoload'.</span>"; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receivables as $row): ?>
                        <tr class="hover:bg-slate-200 transition-all duration-200 group cursor-default">
                            
                            <td class="px-4 py-3 text-[14px] text-slate-800 border-r border-slate-50 text-center">
                                <?= htmlspecialchars($row['employe_id']) ?>
                            </td>

                            <td class="px-4 py-3 text-[14px] text-slate-800 border-r border-slate-100">
                                <?= htmlspecialchars($row['name']) ?>
                            </td>

                            <td class="px-4 py-3 text-[14px] text-slate-500 border-r border-slate-100 text-center">
                                <?= htmlspecialchars($row['region'] ?? 'N/A') ?>
                            </td>

                            <td class="px-4 py-3 text-[14px] text-slate-800 border-r border-slate-100 text-center">
                                <?= ($row['loan_granted'] === 'No Date') ? 'No Date' : date('Y-m-d', strtotime($row['loan_granted'])) ?>
                            </td>
                            
                            <td class="px-4 py-3 text-[14px] text-slate-800 text-right border-r border-slate-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] text-slate-400 font-bold">₱</span>
                                    <span><?= number_format($row['loan_amount'], 2) ?></span>
                                </div>
                            </td>
                            
                            <td class="px-4 py-3 text-[14px] text-slate-800 text-right border-r border-slate-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] text-slate-400 font-bold">₱</span>
                                    <span><?= number_format($row['period_principal'], 2) ?></span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-[14px] text-slate-800 text-right border-r border-slate-100">
                                <?= number_format($row['prior_payments'], 2) ?>
                            </td>
                            
                            <td class="px-4 py-3 text-[14px] text-green-600 text-right border-r border-slate-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] opacity-60 font-bold">₱</span>
                                    <span><?= number_format($row['accumulated_payments'], 2) ?></span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-[14px]  text-red-800 text-right border-r border-slate-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] text-slate-400 font-bold">₱</span>
                                    <span><?= number_format($row['outstanding_balance'], 2) ?></span>
                                </div>
                            </td>
                            
                            <td class="px-4 py-3 text-[14px]  text-slate-800 text-right border-r border-slate-100">
                                <div class="flex justify-between items-center">
                                    <span class="text-[14px] opacity-60 font-bold">₱</span>
                                    <span><?= number_format($row['period_income'], 2) ?></span>
                                </div>
                            </td>
                            
                            <td class="px-4 py-3 text-center">
                                <?php if($row['loan_status'] === 'ONGOING'): ?>
                                    <span class="inline-block px-2 py-1 rounded-full text-[13px] font-black uppercase bg-red-100 text-red-600 border border-red-200">Ongoing</span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 rounded-full text-[13px] font-black uppercase bg-slate-100 text-slate-500 border border-slate-200">Fully Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex justify-between items-center shrink-0">
            <span class="text-[12px] font-bold text-slate-400 uppercase">Page 1 of 1</span>
            <div class="flex gap-2">
                <button class="px-3 py-1 bg-white border border-slate-300 rounded text-[13px] font-bold text-slate-500 disabled:opacity-50" disabled>Prev</button>
                <button class="px-3 py-1 bg-white border border-slate-300 rounded text-[13px] font-bold text-slate-500">Next</button>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/report_period_picker.php'; ?>

<script>
    const BASE_URL = "<?= defined('BASE_URL') ? BASE_URL : '' ?>";
</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/public/assets/js/running_receivables.js"></script>