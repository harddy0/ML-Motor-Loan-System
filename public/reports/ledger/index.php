<?php
$pageTitle = "LEDGER REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';

// Instantiate the service using the $pdo from init.php
$loanService = new \App\LoanService($pdo);

// --- REAL DATA FETCH ---
$borrowers = $loanService->getAllLedgerLoans();

// Stats
$total_ledgers = count($borrowers);
$ongoing = count(array_filter($borrowers, fn($b) => $b['current_status'] === 'ONGOING'));
$paid = count(array_filter($borrowers, fn($b) => $b['current_status'] === 'FULLY PAID'));
$voided = count(array_filter($borrowers, fn($b) => $b['current_status'] === 'VOIDED')); // NEW
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-4 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-2">
            <h1 class="text-2xl text-slate-700 font-medium">Ledger Reports</h1>
        </div>
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
    </div>

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
        <span class="text-[12px] text-slate-400 mr-4">Filter by Granted Date</span>
        <div class="flex items-center gap-3 w-full justify-end">
            <button id="ledgerViewAllBtn" class="h-9 px-6 bg-slate-100 text-slate-800 rounded-full text-[13px] hover:bg-slate-200 transition-all active:scale-95">
                View All
            </button>

            <div class="h-8 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all px-1 group shrink-0">
    
                <label for="fromDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">From</span>
                        <input type="date" id="fromDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>

                <label for="toDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">To</span>
                        <input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 transition-all duration-300">
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Total Ledgers</h3>
        <span name="total-ledgers-count-span" id="total-ledgers-count" class="text-3xl font-bold text-slate-800 tracking-tight"><?= $total_ledgers ?></span>
    </div>

    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Ongoing</h3>
        <span  name="ongoing-count-span" id="ongoing-count" class="text-3xl font-bold text-slate-700 tracking-tight"><?= $ongoing ?></span>
    </div>

    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Fully Paid</h3>
        <span name="paid-count-span" id="paid-count" class="text-3xl font-bold text-slate-800 tracking-tight"><?= $paid ?></span>
    </div>

    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Voided</h3>
        <span name="voided-count-span" id="voided-count" class="text-3xl font-bold text-slate-800 tracking-tight"><?= $voided ?></span>
    </div>
</div>

<div class="bg-white border border-slate-100 rounded-lg shadow-sm overflow-hidden transition-all duration-300">
    
    <div class="bg-white rounded border border-slate-300 shadow-smoverflow-x-auto">
        <table class="w-full text-left border-collapse table-fixed">
            <thead>
                <tr class="bg-[#ce1126] border-b border-slate-300">
                    <th class="w-1/5 px-4 py-2 text-[14px]  text-white uppercase tracking-widest border-r border-slate-100 text-center">Employee ID</th>
                    <th class="w-1/5 px-4 py-2 text-[14px]  text-white uppercase tracking-widest border-r border-slate-100">Name</th>
                    <th class="w-1/5 px-4 py-2 text-[14px]  text-white uppercase tracking-widest border-r border-slate-100 text-center">Date Released</th>
                    <th class="w-1/5 px-4 py-2 text-[14px]  text-white uppercase tracking-widest border-r border-slate-100 text-center">Maturity Date</th>
                    <th class="w-1/5 px-4 py-2 text-[14px]  text-white uppercase tracking-widest text-center">Status</th>
                </tr>
            </thead>
            <tbody id="borrowersTableBody" class="divide-y divide-slate-100">
                <?php if (empty($borrowers)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No loans found in database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($borrowers as $row): 
                        // Format dates to Month Day, Year for display purposes
                        $display_g_date = !empty($row['g_date']) ? date('M d, Y', strtotime($row['g_date'])) : '--';
                        $display_maturity = !empty($row['maturity_date']) ? date('M d, Y', strtotime($row['maturity_date'])) : '--';
                    ?>
                    <tr onclick="handleRowClick('<?= $row['loan_id'] ?>')" 
                        class="ledger-row hover:bg-slate-50 cursor-pointer transition-colors border-b border-slate-100 last:border-0"
                        data-search="<?= htmlspecialchars(strtolower($row['employe_id'] . ' ' . $row['name'])) ?>"
                        data-date="<?= htmlspecialchars($row['g_date'] ?? '') ?>"
                        data-status="<?= htmlspecialchars($row['current_status']) ?>">
                        
                        <td class="px-4 py-2 text-[14px] text-slate-600 border-r border-slate-50 text-center font-mono"><?= htmlspecialchars($row['employe_id'] ?? '--') ?></td>
                        <td class="px-4 py-2 text-[14px] text-slate-800 font-bold border-r border-slate-50 truncate uppercase"><?= htmlspecialchars($row['name'] ?? '--') ?></td>
                        <td class="px-4 py-2 text-[14px] text-slate-500 text-center border-r border-slate-50 font-medium"><?= htmlspecialchars($display_g_date) ?></td>
                        <td class="px-4 py-2 text-[14px] text-slate-500 text-center border-r border-slate-50 font-medium"><?= htmlspecialchars($display_maturity) ?></td>
                        <td class="px-4 py-2 text-center">
                            <?php if($row['current_status'] === 'ONGOING'): ?>
                                <span class="text-[#ce1126] font-bold text-[14px]">Ongoing</span>
                            <?php elseif($row['current_status'] === 'VOIDED'): ?>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[14px] font-bold rounded uppercase">Voided</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 text-green-600 text-[14px] font-bold"><?= htmlspecialchars($row['current_status']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../../../src/includes/modals/ledger_detail.php'; ?>

<script>
    const ALL_BORROWERS = <?= json_encode($borrowers) ?>;
</script>
<script src="../../assets/js/ledger.js"></script>