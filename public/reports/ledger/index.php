<?php
$pageTitle = "LEDGER REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';

// --- MOCK DATA (Adjusted for Add-on Rate Logic) ---
$borrowers = [
    [
        'employe_id' => 'ML1234567',        
        'name' => 'CLARISA REMARIM',        
        'pn_number' => 'PN-88901',          
        'g_date' => '2025-12-02',          
        'maturity_date' => '2027-11-30',    
        'current_status' => 'ONGOING',      
        'loan_amount' => 135000,            
        'term_months' => 24,
        // Logic: 135k Principal. 1.5% Add-on Rate/Mo. 
        // Interest = 135k * 0.015 * 24 = 48,600. Total = 183,600. 
        // Payments (48): 3,825.00
        'semi_monthly_amt' => 3825.00       
    ],
    [
        'employe_id' => 'ML7772211',
        'name' => 'JUAN DELA CRUZ',
        'pn_number' => 'PN-77210',
        'g_date' => '2025-01-10',
        'maturity_date' => '2026-01-10',
        'current_status' => 'ONGOING',
        'loan_amount' => 50000,
        'term_months' => 12,
        // Logic: 50k Principal. 2.0% Add-on Rate/Mo.
        // Interest = 50k * 0.02 * 12 = 12,000. Total = 62,000.
        // Payments (24): 2,583.33
        'semi_monthly_amt' => 2583.33
    ],
    [
        'employe_id' => 'ML5554433',
        'name' => 'MARIA CLARA',
        'pn_number' => 'PN-66123',
        'g_date' => '2024-06-20',
        'maturity_date' => '2025-06-20',
        'current_status' => 'FULLY PAID',
        'loan_amount' => 20000,
        'term_months' => 12,
        // Logic: 20k Principal. 1.5% Add-on.
        // Interest = 20k * 0.015 * 12 = 3,600. Total = 23,600.
        // Payments (24): 983.33
        'semi_monthly_amt' => 983.33
    ]
];

// Stats
$total_ledgers = count($borrowers);
$ongoing = count(array_filter($borrowers, fn($b) => $b['current_status'] === 'ONGOING'));
$paid = count(array_filter($borrowers, fn($b) => $b['current_status'] === 'FULLY PAID'));
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-4">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Ledger <span class="text-[#ff3b30]">Reports</span></h1>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Loan Account History</p>
        </div>
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" placeholder="SEARCH ID OR NAME..." 
                class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-full text-xs font-bold outline-none uppercase placeholder:text-slate-300 focus:border-[#ff3b30] transition-all shadow-sm">
        </div>
    </div>

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mr-44">Filter by Granted Date</span>

    <div class="flex items-center gap-3 w-full justify-end">
        <button class="h-11 px-6 bg-white text-slate-500 border border-slate-200 rounded-full text-[10px] font-black uppercase flex items-center justify-center transition-all duration-200 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 active:scale-95 shadow-sm">
            View All
        </button>

        <div class="h-11 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm">
            <div class="h-full pl-5 pr-3 flex items-center gap-2 border-r border-slate-100 hover:bg-slate-50 transition-colors">
                <span class="text-[9px] font-black text-slate-400 uppercase">From</span>
                <input type="date" value="<?= date('Y-m-d') ?>" class="text-[11px] font-bold text-slate-700 outline-none bg-transparent w-24 cursor-pointer appearance-none">
            </div>
            <div class="h-full px-4 flex items-center gap-2 hover:bg-slate-50 transition-colors">
                <span class="text-[9px] font-black text-slate-400 uppercase">To</span>
                <input type="date" value="<?= date('Y-m-d') ?>" class="text-[11px] font-bold text-slate-700 outline-none bg-transparent w-24 cursor-pointer appearance-none">
            </div>
        </div>

        <button class="h-11 flex items-center gap-2 px-6 bg-[#e11d48] text-white rounded-full 
            text-[10px] font-black uppercase tracking-wider
            shadow-md hover:brightness-110 hover:shadow-lg
            transition-all duration-200 ease-in-out active:scale-[0.98]" 
            title="Download Report">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Download Report</span>
        </button>
    </div>
</div>
</div>

<div class="flex flex-col lg:flex-row gap-8 h-full min-h-[500px]">

    <div class="flex-1 flex flex-col bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-slate-800 font-bold text-xs tracking-widest uppercase flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-[#ff3b30]"></div>
                Master Ledger List
            </h2>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                   <tr class="bg-slate-900 text-white">
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">Employee ID</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">Name</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">Granted Date</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">Maturity Date</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($borrowers as $row): ?>
                    <tr onclick="handleRowClick('<?= $row['employe_id'] ?>')" 
                        class="hover:bg-red-50 cursor-pointer transition-colors group">
                        <td class="px-5 py-4 text-xs font-bold text-slate-600 border-r border-slate-100"><?= $row['employe_id'] ?></td>
                        <td class="px-5 py-4 text-xs font-black text-slate-800 uppercase border-r border-slate-100 group-hover:text-[#ff3b30]"><?= $row['name'] ?></td>
                        <td class="px-5 py-4 text-xs font-bold text-slate-500 text-center border-r border-slate-100"><?= $row['g_date'] ?></td>
                        <td class="px-5 py-4 text-xs font-bold text-slate-500 text-center border-r border-slate-100"><?= $row['maturity_date'] ?></td>
                        <td class="px-5 py-4 text-center">
                            <?php if($row['current_status'] === 'ONGOING'): ?>
                                <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-[9px] font-black uppercase rounded-full">Ongoing</span>
                            <?php else: ?>
                                <span class="inline-block px-3 py-1 bg-slate-200 text-slate-600 text-[9px] font-black uppercase rounded-full">Fully Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
        <div class="w-full lg:w-72 flex flex-col gap-4 shrink-0">
            <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    
                <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Total Ledgers</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-black text-slate-800 tracking-tight"><?= $total_ledgers ?></span>
                    <span class="text-slate-400 text-[10px] font-bold">UNITS</span>
                </div>
            </div>

            <div class="bg-white border-t-4 border-slate-700 rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-slate-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                
                <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Ongoing</h3>
                <span class="text-5xl font-black text-slate-700 tracking-tight"><?= $ongoing ?></span>
            </div>

            <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                
                <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Fully Paid</h3>
                <span class="text-5xl font-black text-slate-800 tracking-tight"><?= $paid ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/ledger_detail.php'; ?>

<script>
    const ALL_BORROWERS = <?= json_encode($borrowers) ?>;

    function handleRowClick(id) {
        const selectedBorrower = ALL_BORROWERS.find(b => b.employe_id === id);
        if (selectedBorrower && typeof openLedgerModal === 'function') {
            openLedgerModal(selectedBorrower);
        }
    }
</script>