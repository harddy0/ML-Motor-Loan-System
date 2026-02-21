<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 
?> 

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">
            System <span class="text-[#e11d48]">Dashboard</span>
        </h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">
                AS OF <?= date('F d, Y') ?>
            </p>
        </div>
    </div>
</div>

<div class="w-full flex-1 min-h-0 flex flex-col gap-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
        <div class="bg-white border-t-4 border-amber-500 rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-100 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">
                Payroll Deduction
            </h3>
            <div class="flex items-baseline gap-1 relative z-10">
                <span id="statUnits" class="text-5xl font-black text-slate-800 tracking-tighter">0</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 relative z-10">Units Processed</p>
        </div>

        <div class="bg-white border-t-4 border-green-500 rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-green-100 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">
                Ledgers
            </h3>
            
            <div class="flex items-baseline gap-1 relative z-10">
                <span id="statLedgers" class="text-5xl font-black text-slate-800 tracking-tighter">0</span>
            </div>
            
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 relative z-10">Active Records Found</p>
        </div>

        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">
                Active Borrowers
            </h3>
            
            <div class="flex items-baseline gap-1 relative z-10">
                <span id="statBorrowers" class="text-5xl font-black text-slate-800 tracking-tighter">0</span>
            </div>
            
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 relative z-10">Verified Accounts</p>
        </div>

        <div class="bg-white border-t-4 border-blue-500 rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            
            <h3 class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mb-1 relative z-10">
                Fully Paid
            </h3>
            
            <div class="flex items-baseline gap-1 relative z-10">
                <span id="statPaid" class="text-5xl font-black text-slate-800 tracking-tighter transition-colors group-hover:text-blue-600">0</span>
            </div>
            
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 relative z-10">Closed Portfolios</p>
        </div>
    </div> 

    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-lg border-t-4 border-t-[#e11d48]">
        <div class="flex justify-between items-start mb-5">
            <div>
                <h2 class="text-slate-800 font-black text-sm tracking-tight uppercase">
                    Running Accounts <span class="text-[#e11d48]">Receivable</span>
                </h2>
                <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest mt-1">Live Cumulative Portfolio Status</p>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Loan Amount</p>
                <span id="valTotalLoaned" class="text-xl font-black text-slate-800">₱0.00</span>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex justify-between items-end px-1">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Collection Progress</span>
                </div>
                <span id="valProgressTxt" class="text-xs font-black text-[#e11d48] bg-red-50 px-2 py-0.5 rounded">0% Collected</span>
            </div>
            
            <div class="relative w-full h-8 bg-slate-100 rounded-full overflow-hidden border border-slate-200 shadow-inner flex items-center">
                <div id="barPaid" 
                    class="h-full bg-gradient-to-r from-[#e11d48] to-[#be123c] flex items-center justify-center transition-all duration-1000 ease-out relative" 
                    style="width: 0%">
                    <div class="absolute inset-0 bg-white/10 w-full h-1/2 top-0"></div>
                </div>

                <div class="flex-1 flex items-center justify-end px-4">
                    <span id="valOutstandingTxt" class="text-[9px] text-slate-500 font-black tracking-widest uppercase">
                        Outstanding: <span class="text-slate-900 ml-1">₱0.00</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-6 mt-5 pt-4 border-t border-slate-50">
            <div class="text-center sm:text-left">
                <span class="text-[9px] font-black text-slate-400 uppercase block tracking-widest mb-1">Total Payments</span>
                <span id="valTotalCollected" class="text-lg font-black text-slate-800">₱0.00</span>
            </div>
            <div class="text-center sm:text-left border-l border-slate-100 pl-6">
                <span class="text-[9px] font-black text-green-600 uppercase block tracking-widest mb-1">Interest Income</span>
                <span id="valTotalIncome" class="text-lg font-black text-green-600">₱0.00</span>
            </div>
            <div class="text-center sm:text-left border-l border-slate-100 pl-6">
                <span class="text-[9px] font-black text-[#e11d48] uppercase block tracking-widest mb-1">Net Outstanding</span>
                <span id="valNetOutstanding" class="text-lg font-black text-slate-800">₱0.00</span>
            </div>
        </div>
    </div>
</div>

<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/public/assets/js/dashboard.js"></script>