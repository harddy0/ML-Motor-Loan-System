<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../src/includes/init.php';

// Mock Data - In a real app, these would come from your Database
$totalLoanAmount = 850000;
$paymentAmount = 56950; // roughly 6.7%
$outstandingAmount = 793050; // The remainder
$incomeAmount = 6950; // Assuming income matches payments for this view

// Percentages for the Bar
$paymentWidth = ($paymentAmount / $totalLoanAmount) * 100;
$outstandingWidth = ($outstandingAmount / $totalLoanAmount) * 100;
?> 

<div class="flex justify-between items-center mb-6 shrink-0">
    <h1 class="text-xl font-bold text-[#b04b4b] tracking-tight uppercase">DASHBOARD</h1>
    <div class="flex items-center bg-white border-2 border-slate-200/40 rounded shadow-sm overflow-hidden">
        <div class="px-4 py-2 border-r border-slate-100 flex items-center gap-3">
            <span class="text-[10px] font-black text-slate-400 uppercase">From</span>
            <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
        </div>
        <div class="px-4 py-2 flex items-center gap-3 border-r border-slate-100">
            <span class="text-[10px] font-black text-slate-400 uppercase">To</span>
            <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
        </div>
        <button class="bg-[#ff3b30] hover:bg-red-700 text-white px-6 py-2 text-[10px] font-black uppercase transition-all">
            Filter
        </button>
    </div>
</div>

<div class="min-h-[calc(65vh-140px)] flex flex-col overflow-hidden">
    <div class="flex flex-col gap-6 flex-1">
        
        <div class="grid grid-cols-4 gap-6 shrink-0">
            <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-1xl transition-all duration-500 relative flex flex-col justify-between shadow-sm hover:shadow-xl">
                <div class="relative z-20 flex justify-between items-start">
                    <div>
                        <h2 class="text-[#b04b4b] font-bold text-xs tracking-widest uppercase mb-1">Payroll Deduction</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">↑ +12.5%</span>
                    </div>
                    <span class="text-[#b04b4b] text-xl cursor-pointer hover:scale-125 transition-transform">↗</span>
                </div>
                <div class="mt-4 text-center relative z-10">
                    <div class="text-5xl font-black text-[#8a3333] tracking-tighter group-hover:scale-105 transition-transform">12,450</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Processed</div>
                </div>
            </div>

            <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-1xl transition-all duration-500 relative flex flex-col justify-between shadow-sm hover:shadow-xl">
                <div class="relative z-20 flex justify-between items-start">
                    <div>
                        <h2 class="text-[#b04b4b] font-bold text-xs tracking-widest uppercase mb-1">Ledgers</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">↓ -2.1%</span>
                    </div>
                    <span class="text-[#b04b4b] text-xl cursor-pointer hover:scale-125 transition-transform">↗</span>
                </div>
                <div class="mt-4 text-center relative z-10">
                    <div class="text-5xl font-black text-[#8a3333] tracking-tighter group-hover:scale-105 transition-transform">8,932</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total Records</div>
                </div>
            </div>

            <?php
                $active = 1048; 
                $paid = 735; 
            ?>

            <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-1xl transition-all duration-500 relative flex flex-col justify-between shadow-sm hover:shadow-xl">
                <div class="relative z-20 flex justify-between items-start">
                    <div>
                        <h2 class="text-[#b04b4b] font-bold text-xs tracking-widest uppercase mb-1">Active Borrowers</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 uppercase">Live Status</span>
                    </div>
                    <span class="text-[#b04b4b] text-xl cursor-pointer hover:scale-125 transition-transform">↗</span>
                </div>
                <div class="mt-4 text-center relative z-10">
                    <div class="text-5xl font-black text-[#8a3333] tracking-tighter group-hover:scale-110 transition-transform">
                        <?php echo number_format($active); ?>
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Current Accounts</div>
                </div>
            </div>

            <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-1xl transition-all duration-500 relative flex flex-col justify-between shadow-sm hover:shadow-xl">
                <div class="relative z-20 flex justify-between items-start">
                    <div>
                        <h2 class="text-[#b04b4b] font-bold text-xs tracking-widest uppercase mb-1">Fully Paid</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 uppercase">Completed</span>
                    </div>
                    <span class="text-[#b04b4b] text-xl cursor-pointer hover:scale-125 transition-transform">↗</span>
                </div>
                <div class="mt-4 text-center relative z-10">
                    <div class="text-5xl font-black text-[#8a3333] tracking-tighter group-hover:scale-110 transition-transform">
                        <?php echo number_format($paid); ?>
                    </div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Closed Accounts</div>
                </div>
            </div>
        </div> 

        <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-1xl transition-all duration-500 relative flex flex-col justify-between shadow-md hover:shadow-md">
            <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase mb-8">Running Accounts Receivable</h2>
            <span class="absolute top-6 right-8 text-[#b04b4b] text-xl cursor-pointer hover:scale-125 transition-transform">↗</span>

            <div class="flex flex-col gap-8">
                <div class="space-y-2">
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-black text-[#b04b4b] uppercase tracking-widest">Collection Progress vs Total Loan</span>
                        <span class="text-xs font-bold text-gray-600">Goal: ₱<?php echo number_format($totalLoanAmount/1000); ?>k</span>
                    </div>
                    
                    <div class="relative w-full h-12 bg-gray-200 rounded-lg overflow-hidden flex shadow-inner">
                        <div class="h-full bg-gray-600 flex items-center justify-center transition-all duration-1000" style="width: <?php echo $paymentWidth; ?>%">
                            <span class="text-[9px] text-white font-bold opacity-0 hover:opacity-100 transition-opacity">PAID</span>
                        </div>
                        <div class="h-full bg-[#d7845f] flex items-center justify-center transition-all duration-1000" style="width: <?php echo $outstandingWidth; ?>%">
                            <span class="text-[12px] text-white font-bold">OUTSTANDING: ₱<?php echo number_format($outstandingAmount/1000); ?>k</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Total Amount Loan</span>
                        <span class="text-lg font-black text-black">₱<?php echo number_format($totalLoanAmount); ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Total Payments</span>
                        <span class="text-lg font-black text-gray-600">₱<?php echo number_format($paymentAmount); ?></span>
                    </div>
                    <div class="flex flex-col border-l pl-4 border-[#8a3333]/20">
                        <span class="text-[10px] font-bold text-[#b04b4b] uppercase italic">Monthly Income</span>
                        <span class="text-lg font-black text-[#8a3333]">₱<?php echo number_format($incomeAmount); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #b04b4b; }
</style>