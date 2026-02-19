<?php
$pageTitle = "DEDUCTION REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';

// --- MOCK DATA ---
$mock_data = [
    ['id' => '20150428', 'p_date' => '01/30/2026', 'last' => 'REMARIM', 'first' => 'CLARISA', 'amount' => 3825, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20190617', 'p_date' => '01/30/2026', 'last' => 'GOZON JR', 'first' => 'FRANCIS', 'amount' => 1585, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20230445', 'p_date' => '01/30/2026', 'last' => 'DE GUZMAN', 'first' => 'ROMEO', 'amount' => 3570, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20240031', 'p_date' => '01/30/2026', 'last' => 'AMPIS', 'first' => 'MIKAELA', 'amount' => 2463, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20240158', 'p_date' => '01/30/2026', 'last' => 'SUPAN', 'first' => 'JENELY', 'amount' => 2958, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20240242', 'p_date' => '01/30/2026', 'last' => 'GENESE', 'first' => 'MARITES', 'amount' => 4175, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
    ['id' => '20240675', 'p_date' => '01/30/2026', 'last' => 'QUIAMBAO', 'first' => 'ERWIN', 'amount' => 2758, 'region' => 'Head Office', 'i_date' => '1/31/2026'],
];

// Calculate Totals
$total_count = count($mock_data);
$total_amount = array_sum(array_column($mock_data, 'amount'));
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-4">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Deduction <span class="text-[#ff3b30]">Reports</span></h1>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Financial Collection Summary</p>
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
    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mr-44">Filter by Date Imported</span>

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
                Payroll Deduction List
            </h2>
            <span class="text-[10px] font-bold text-slate-400 uppercase">Live Records</span>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-24">ID No.</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700 w-32">Payroll Date</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider border-r border-slate-700">Full Name</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-right border-r border-slate-700 w-32">Deduction</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center border-r border-slate-700">Region</th>
                        <th class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-center w-32">Date Imported</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($mock_data as $row): ?>
                    <tr class="hover:bg-slate-50 transition-colors group cursor-default">
                        <td class="px-5 py-3 text-xs font-bold text-slate-500 text-center border-r border-slate-100 bg-slate-50/50">
                            <?= $row['id'] ?>
                        </td>
                        <td class="px-5 py-3 text-xs font-bold text-slate-600 text-center border-r border-slate-100">
                            <?= $row['p_date'] ?>
                        </td>
                        <td class="px-5 py-3 border-r border-slate-100">
                            <span class="text-xs font-black text-slate-800 uppercase block"><?= $row['last'] ?>, <?= $row['first'] ?></span>
                        </td>
                        <td class="px-5 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100 bg-[#fff5f5]/50 group-hover:bg-[#fff5f5]">
                            <?= number_format($row['amount'], 2) ?>
                        </td>
                        <td class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase text-center border-r border-slate-100">
                            <?= $row['region'] ?>
                        </td>
                        <td class="px-5 py-3 text-[10px] font-bold text-slate-400 text-center">
                            <?= $row['i_date'] ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex justify-between items-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Showing <?= count($mock_data) ?> records</span>
        </div>
    </div>

    <div class="w-full lg:w-72 flex flex-col gap-6 shrink-0">
        
       <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50/50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
        
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">Total Records</h3>
            <div class="flex items-baseline gap-1 relative z-10">
                <span class="text-5xl font-black text-slate-800 tracking-tighter"><?= $total_count ?></span>
                <span class="text-xs font-bold text-slate-400 uppercase">Items</span>
            </div>
        </div>

        <div class="bg-white border-t-4 border-slate-700 rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-slate-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">Total Amount</h3>
            <div class="relative z-10">
                <span class="text-3xl font-black text-[#ff3b30] tracking-tight">₱ <?= number_format($total_amount, 2) ?></span>
            </div>
        </div>

    </div>

</div>