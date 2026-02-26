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

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-4">
            <h1 class="text-2xl">Ledger Reports</h1>
        </div>
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by ID Number or Name" 
                class="w-full h-12 pl-14 pr-6 bg-white border border-slate-200 rounded-full 
                text-[13px] outline-none  placeholder:text-slate-300 
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
        </div>
    </div>

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
        <span class="text-[12px] text-slate-400 mr-44">Filter by Granted Date</span>
        <div class="flex items-center gap-3 w-full justify-end">
            <button id="deductionViewAllBtn" 
            class="h-10 px-4 bg-slate-100 text-slate-800 rounded-full text-[13px] 
            hover:bg-slate-300 transition-all active:scale-95">
            View All
            </button>

            <div class="h-11 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all px-1 group shrink-0">
    
                <label for="fromDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3"><span class="text-[13px] text-slate-400 mb-0.5">From</span><input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input"></div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>

                <label for="toDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3"><span class="text-[13px] text-slate-400 mb-0.5">To</span><input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input"></div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="flex flex-col lg:flex-row gap-8 h-full min-h-[500px]">
    <div class="flex-1 flex flex-col bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-slate-800 text-[14px] flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-[#e11d48]"></div>
                Master Ledger List
            </h2>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                   <tr class="bg-slate-100 text-slate-800">
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">Employee ID</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">Name</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">Granted Date</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">Maturity Date</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($borrowers)): ?>
                        <tr><td colspan="5" class="px-5 py-8 text-center text-[14px] text-slate-400">No loans found in database.</td></tr>
                    <?php else: ?>
                        <?php foreach ($borrowers as $row): ?>
                        <tr onclick="handleRowClick('<?= $row['loan_id'] ?>')" 
                            class="ledger-row hover:bg-slate-200 cursor-pointer transition-colors group"
                            data-search="<?= htmlspecialchars(strtolower($row['employe_id'] . ' ' . $row['name'])) ?>"
                            data-date="<?= htmlspecialchars($row['g_date'] ?? '') ?>"
                            data-status="<?= htmlspecialchars($row['current_status']) ?>">
                            <td class="px-5 py-4 text-[14px] text-slate-600 border-r border-slate-100"><?= htmlspecialchars($row['employe_id'] ?? '--') ?></td>
                            <td class="px-5 py-4 text-[14px] text-slate-800 font-bold border-r border-slate-100"><?= htmlspecialchars($row['name'] ?? '--') ?></td>
                            <td class="px-5 py-4 text-[14px]  text-slate-500 text-center border-r border-slate-100"><?= htmlspecialchars($row['g_date'] ?? '--') ?></td>
                            <td class="px-5 py-4 text-[14px]  text-slate-500 text-center border-r border-slate-100"><?= htmlspecialchars($row['maturity_date'] ?? '--') ?></td>
                            <td class="px-5 py-4 text-center">
                                <?php if($row['current_status'] === 'ONGOING'): ?>
                                    <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-[12px] rounded-full">Ongoing</span>
                                <?php elseif($row['current_status'] === 'VOIDED'): ?>
                                    <span class="inline-block px-3 py-1 bg-orange-100 text-orange-700 text-[12px] rounded-full">Voided</span>
                                <?php else: ?>
                                    <span class="inline-block px-3 py-1 bg-slate-200 text-slate-600 text-[12px] rounded-full"><?= htmlspecialchars($row['current_status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="w-full lg:w-72 flex flex-col gap-4 shrink-0 overflow-y-auto no-scrollbar pb-4">
        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 text-[14px] mb-1">Total Ledgers</h3>
            <span id="total-ledgers-count" class="text-5xl text-slate-800 tracking-tight"><?= $total_ledgers ?></span>
        </div>
        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 text-[14px] mb-1">Ongoing</h3>
            <span id="ongoing-count" class="text-5xl text-slate-700 tracking-tight"><?= $ongoing ?></span>
        </div>
       <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 text-[14px] mb-1">Fully Paid</h3>
            <span id="paid-count" class="text-5xl text-slate-800 tracking-tight"><?= $paid ?></span>
        </div>
        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 text-[14px] mb-1">Voided</h3>
            <span id="voided-count" class="text-5xl text-slate-800 tracking-tight"><?= $voided ?></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/ledger_detail.php'; ?>

<script>
    const ALL_BORROWERS = <?= json_encode($borrowers) ?>;
</script>
<script src="../../assets/js/ledger.js"></script>