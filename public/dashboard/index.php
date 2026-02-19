<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 

// Mock Data
$totalLoanAmount = 850000;
$paymentAmount = 56950; 
$outstandingAmount = 793050; 
$incomeAmount = 6950; 

// Percentages for the Bar
$paymentWidth = ($paymentAmount / $totalLoanAmount) * 100;
$outstandingWidth = ($outstandingAmount / $totalLoanAmount) * 100;
?> 

    <div class="flex flex-col xl:flex-row justify-between items-end mb-6 gap-6">
        <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Dashboard</h1>
        <div class="h-11 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden">
                <div class="h-full pl-5 pr-3 flex items-center gap-2 border-r border-slate-200">
                    <span class="text-[9px] font-black text-slate-400 uppercase">From</span>
                    <input type="date" value="<?= date('Y-m-d') ?>" class="text-xs font-bold text-slate-700 outline-none bg-transparent w-28 cursor-pointer">
                </div>
                <div class="h-full px-4 flex items-center gap-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase">To</span>
                    <input type="date" value="<?= date('Y-m-d') ?>" class="text-xs font-bold text-slate-700 outline-none bg-transparent w-28 cursor-pointer">
                </div>
            </div>
    </div>

    <div class="w-full flex-1 min-h-0 flex flex-col gap-6 overflow-hidden">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
            
            <div class="group p-5 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-xl transition-all duration-500 flex flex-col justify-between shadow-sm hover:shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="min-w-0">
                        <h2 class="text-[#b04b4b] font-bold text-[10px] tracking-widest uppercase mb-1 truncate">Payroll Deduction</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-green-100 text-green-700">↑ +12.5%</span>
                    </div>
                   
                </div>
                <div class="mt-2 text-center">
                    <div class="text-3xl xl:text-4xl font-black text-[#8a3333] tracking-tighter group-hover:scale-105 transition-transform">12,450</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Processed</div>
                </div>
            </div>

            <div class="group p-5 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-xl transition-all duration-500 flex flex-col justify-between shadow-sm hover:shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="min-w-0">
                        <h2 class="text-[#b04b4b] font-bold text-[10px] tracking-widest uppercase mb-1 truncate">Ledgers</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700">↓ -2.1%</span>
                    </div>
                    
                </div>
                <div class="mt-2 text-center">
                    <div class="text-3xl xl:text-4xl font-black text-[#8a3333] tracking-tighter group-hover:scale-105 transition-transform">8,932</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total Records</div>
                </div>
            </div>

            <div class="group p-5 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-xl transition-all duration-500 flex flex-col justify-between shadow-sm hover:shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="min-w-0">
                        <h2 class="text-[#b04b4b] font-bold text-[10px] tracking-widest uppercase mb-1 truncate">Active Borrowers</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-orange-100 text-orange-700 uppercase">Live Status</span>
                    </div>
                    
                </div>
                <div class="mt-2 text-center">
                    <div class="text-3xl xl:text-4xl font-black text-[#8a3333] tracking-tighter group-hover:scale-110 transition-transform">1,048</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Current Accounts</div>
                </div>
            </div>

            <div class="group p-5 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-xl transition-all duration-500 flex flex-col justify-between shadow-sm hover:shadow-lg">
                <div class="flex justify-between items-start">
                    <div class="min-w-0">
                        <h2 class="text-[#b04b4b] font-bold text-[10px] tracking-widest uppercase mb-1 truncate">Fully Paid</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700 uppercase">Completed</span>
                    </div>
                    
                </div>
                <div class="mt-2 text-center">
                    <div class="text-3xl xl:text-4xl font-black text-[#8a3333] tracking-tighter group-hover:scale-110 transition-transform">735</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Closed Accounts</div>
                </div>
            </div>
        </div> 

        <div class="group p-6 bg-[#eeeeee]/40 border-2 border-transparent hover:border-[#b04b4b]/20 rounded-xl transition-all duration-500 relative flex flex-col shadow-md flex-1 min-h-0 overflow-hidden">
            <div class="flex justify-between items-center mb-4 shrink-0">
                <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase">Running Accounts Receivable</h2>
                
            </div>

            <div class="flex flex-col flex-1 justify-around">
                <div class="space-y-4">
                    <div class="flex flex-wrap justify-between items-end gap-2 shrink-0">
                        <span class="text-[10px] font-black text-[#b04b4b] uppercase tracking-widest">Collection Progress</span>
                        <span class="text-xs font-bold text-gray-600">Goal: ₱850k</span>
                    </div>
                    
                    <div class="relative w-full h-10 lg:h-14 bg-gray-200 rounded-lg overflow-hidden flex shadow-inner shrink-0">
                        <div class="h-full bg-gray-600 flex items-center justify-center transition-all duration-1000 min-w-[30px]" style="width: 6.7%">
                            <span class="text-[9px] text-white font-bold opacity-0 sm:opacity-100">PAID</span>
                        </div>
                        <div class="h-full bg-[#d7845f] flex items-center justify-center transition-all duration-1000 min-w-0" style="width: 93.3%">
                            <span class="text-[10px] sm:text-[12px] text-white font-bold truncate px-4">OUTSTANDING: ₱793k</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200 shrink-0">
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] font-bold text-gray-400 uppercase truncate">Total Amount</span>
                        <span class="text-md md:text-lg font-black text-black">₱850,000</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] font-bold text-gray-400 uppercase truncate">Total Payments</span>
                        <span class="text-md md:text-lg font-black text-gray-600">₱56,950</span>
                    </div>
                    <div class="flex flex-col min-w-0 md:border-l md:pl-4 border-[#8a3333]/20">
                        <span class="text-[10px] font-bold text-[#b04b4b] uppercase italic truncate">Monthly Income</span>
                        <span class="text-md md:text-lg font-black text-[#8a3333]">₱6,950</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
