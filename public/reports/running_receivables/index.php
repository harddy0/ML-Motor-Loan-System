<?php
$pageTitle = "RUNNING RECEIVABLES";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';

// --- MOCK DATA (Full Month Values) ---
$receivables = [
    [
        'id' => 'ML-001',
        'emp_id' => '20150428', 
        'name' => 'CLARISA REMARIM',
        'loan_granted' => '2025-12-02',
        
        'loan_amount' => 135000.00,       
        'monthly_principal' => 5625.00,   // Full Month (2 payments)
        'prior_principal' => 19688.00,    // Paid before this month
        'total_payment' => 25313.00,      // Prior + Current
        'outstanding_balance' => 109687.00, 
        'monthly_income' => 2025.00,      // Interest for full month
        
        'status' => 'ONGOING'
    ],
    [
        'id' => 'ML-002',
        'emp_id' => '20190617',
        'name' => 'JUAN DELA CRUZ',
        'loan_granted' => '2026-01-15',
        
        'loan_amount' => 50000.00,
        'monthly_principal' => 10000.00, 
        'prior_principal' => 40000.00,
        'total_payment' => 50000.00,
        'outstanding_balance' => 0.00,
        'monthly_income' => 900.00,
        
        'status' => 'FULLY PAID'
    ],
    [
        'id' => 'ML-003',
        'emp_id' => '20240012',
        'name' => 'PEDRO PENDUKO',
        'loan_granted' => '2025-06-20',
        
        'loan_amount' => 75000.00,
        'monthly_principal' => 4166.00,
        'prior_principal' => 10417.00,
        'total_payment' => 14583.00,
        'outstanding_balance' => 60417.00,
        'monthly_income' => 1700.00,
        
        'status' => 'ONGOING'
    ]
];

// Aggregates
$total_loaned = array_sum(array_column($receivables, 'loan_amount'));
$total_outstanding = array_sum(array_column($receivables, 'outstanding_balance'));
$total_income = array_sum(array_column($receivables, 'monthly_income'));
$total_collected = array_sum(array_column($receivables, 'total_payment'));
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div class="w-full xl:w-auto">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Running <span class="text-[#ff3b30]">Receivables</span></h1>
        
        <div class="flex items-center gap-2 mt-2">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest bg-slate-100 px-3 py-1 rounded-full">Report Period</span>
            <h2 id="current-period-display" class="text-sm font-black text-slate-700 uppercase tracking-wide">
                <?= date('F Y') ?> <span class="text-slate-300 mx-2">|</span> <span class="text-[#ff3b30]">Whole Month</span>
            </h2>
        </div>
        
        <div class="relative w-full xl:w-96 mt-4 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" placeholder="SEARCH NAME OR ID..." 
                class="h-11 w-full pl-12 pr-4 bg-white border border-slate-200 rounded-full text-xs font-bold outline-none uppercase placeholder:text-slate-300 focus:border-[#ff3b30] transition-all">
        </div>
    </div>

    <div class="flex flex-col items-end gap-2 w-full xl:w-auto">
        <div class="flex items-center gap-3 w-full justify-end">
            <button onclick="openReportPicker()" 
                class="h-11 px-6 bg-white border border-slate-200 text-slate-500 rounded-full text-[10px] font-black uppercase flex items-center gap-2 transition-all duration-200 hover:bg-slate-100 hover:border-slate-300 hover:text-slate-800 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Select Period
            </button>

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

<div class="flex flex-col gap-6 h-full min-h-[600px]">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border-t-4 border-yellow-500 rounded-xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Total Loaned</h3>
            <span class="text-2xl font-black text-yellow-500 tracking-tight">₱ <?= number_format($total_loaned) ?></span>
        </div>

        <div class="bg-white border-t-4 border-green-500 rounded-xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-green-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Payment</h3>
            <span class="text-2xl font-black text-green-600 tracking-tight">₱ <?= number_format($total_collected) ?></span>
        </div>

        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-slate-50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Total Outstanding</h3>
            <span class="text-2xl font-black text-[#e11d48] tracking-tight">₱ <?= number_format($total_outstanding) ?></span>
        </div>

        <div class="bg-white border-t-4 border-[#1d7fe1] rounded-xl shadow-sm p-5 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-yellow-50/50 rounded-full group-hover:scale-110 transition-transform duration-500 flex items-center justify-center">
                <svg class="w-8 h-8 text-yellow-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-1">Income (This Month)</h3>
            <span class="text-2xl font-black text-[#1d7fe1] tracking-tight">₱ <?= number_format($total_income) ?></span>
        </div>
    </div>

    <div class="flex-1 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden flex flex-col">
        
        <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex justify-between items-center shrink-0">
            <h2 class="text-slate-800 font-bold text-xs tracking-widest uppercase flex items-center gap-2">
                Active Portfolio Records
            </h2>
            <span class="text-[10px] font-black text-slate-400 uppercase">Showing <?= count($receivables) ?> Accounts</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar flex-1 rounded-b-xl border border-slate-100">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-900 text-white sticky top-0 z-10">
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider border-r border-white/10 text-center bg-slate-800 w-24">Employee ID</th>
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider border-r border-white/10 min-w-[180px]">Borrower</th>
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider border-r border-white/10 text-center w-28">Loan Granted</th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 border-t-4 border-yellow-500">
                            Amount Loan<br><span class="text-[8px] text-slate-400 font-normal">(Base Principal)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 text-slate-300">
                            Monthly Principal<br><span class="text-[8px] text-white/70 font-normal">(This Period)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 text-slate-300">
                            Prior Principal<br><span class="text-[8px] text-slate-500 font-normal">(Excl. This Period)</span>
                        </th>
                        
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 border-t-4 border-green-500">
                            Payment<br><span class="text-[8px] text-slate-500 font-normal">(Total Principal)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 text-slate-300 border-t-4 border-[#e11d48] ">
                            Outstanding Bal<br><span class="text-[8px] text--[#e11d48] font-normal">(Remaining)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-right border-r border-white/10 text-slate-300 border-t-4 border-[#1d7fe1]">
                            Monthly Income<br><span class="text-[8px] text-slate-900 font-normal">(Interest)</span>
                        </th>
                        
                        <th class="px-4 py-4 text-[9px] font-black uppercase tracking-wider text-center w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($receivables as $row): ?>
                    <tr class="hover:bg-slate-100/80 transition-all duration-200 group cursor-default">
                        
                        <td class="px-4 py-3 text-[10px] font-medium text-slate-400 border-r border-slate-50 text-center italic">
                            <?= $row['emp_id'] ?>
                        </td>

                        <td class="px-4 py-3 text-xs font-black text-slate-800 border-r border-slate-100 uppercase">
                            <?= $row['name'] ?>
                        </td>

                        <td class="px-4 py-3 text-[10px] font-bold text-slate-800 border-r border-slate-100 text-center">
                            <?= $row['loan_granted'] ?>
                        </td>
                        
                        <td class="px-4 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-slate-400 font-bold">₱</span>
                                <span><?= number_format($row['loan_amount'], 2) ?></span>
                            </div>
                        </td>
                        
                        <td class="px-4 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-slate-400 font-bold">₱</span>
                                <span><?= number_format($row['monthly_principal'], 2) ?></span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100">
                            <?= number_format($row['prior_principal'], 2) ?>
                        </td>
                        
                        <td class="px-4 py-3 text-xs font-black text-green-600 text-right border-r border-slate-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] opacity-60 font-bold">₱</span>
                                <span><?= number_format($row['total_payment'], 2) ?></span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-xs font-black text-red-800 text-right border-r border-slate-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-slate-400 font-bold">₱</span>
                                <span><?= number_format($row['outstanding_balance'], 2) ?></span>
                            </div>
                        </td>
                        
                        <td class="px-4 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] opacity-60 font-bold">₱</span>
                                <span><?= number_format($row['monthly_income'], 2) ?></span>
                            </div>
                        </td>
                        
                        <td class="px-4 py-3 text-center">
                            <?php if($row['status'] === 'ONGOING'): ?>
                                <span class="inline-block px-2 py-1 rounded-full text-[8px] font-black uppercase bg-red-100 text-red-600 border border-red-200">Ongoing</span>
                            <?php else: ?>
                                <span class="inline-block px-2 py-1 rounded-full text-[8px] font-black uppercase bg-slate-100 text-slate-500 border border-slate-200">Fully Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Page 1 of 1</span>
            <div class="flex gap-2">
                <button class="px-3 py-1 bg-white border border-slate-300 rounded text-[10px] font-bold text-slate-500 disabled:opacity-50" disabled>Prev</button>
                <button class="px-3 py-1 bg-white border border-slate-300 rounded text-[10px] font-bold text-slate-500">Next</button>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/report_period_picker.php'; ?>