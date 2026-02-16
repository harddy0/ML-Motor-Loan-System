<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../src/includes/init.php';
?>

<div class="min-h-[calc(100vh-140px)] flex flex-col overflow-y-auto px-4 py-2 custom-scrollbar">
    
    <div class="flex justify-between items-center mb-6 shrink-0">
        <h1 class="text-xl font-bold text-[#b04b4b] tracking-tight uppercase">DASHBOARD</h1>
        
        <div class="flex gap-2">
            <div class="flex items-center gap-2 bg-gray-100 rounded-full px-4 py-1 border border-gray-200">
                <span class="text-[10px] font-bold text-gray-400 uppercase">FROM</span>
                <select class="bg-transparent text-xs font-bold outline-none"><option>--</option></select>
            </div>
            <div class="flex items-center gap-2 bg-gray-100 rounded-full px-4 py-1 border border-gray-200">
                <span class="text-[10px] font-bold text-gray-400 uppercase">TO</span>
                <select class="bg-transparent text-xs font-bold outline-none"><option>--</option></select>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 flex-1 min-h-0">
        
        <div class="grid grid-cols-4 gap-6 h-[32%] shrink-0">
            <div class="col-span-1 p-6 bg-[#eeeeee] border-2 border-transparent hover:border-gray-400 rounded-2xl transition-all duration-300 relative">
                <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase">Payroll Deduction</h2>
                <!-- Count -->
                <div class="mt-6 flex flex-col items-center justify-center text-center">
                    <div class="text-5xl font-extrabold text-[#b04b4b] leading-none">
                        50
                    </div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">
                        borrower
                    </div>
                </div>
                <span class="absolute top-4 right-4 p-2 text-[#b04b4b] text-xl cursor-pointer transition-all duration-300 hover:scale-125 hover:-translate-y-1 hover:translate-x-1">↗</span>
            </div>

            <div class="col-span-1 p-6 bg-[#eeeeee] border-2 border-transparent hover:border-gray-400 rounded-2xl transition-all duration-300 relative">
                <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase">Ledgers</h2>
                <!-- Count -->
                <div class="mt-6 flex flex-col items-center justify-center text-center">
                    <div class="text-5xl font-extrabold text-[#b04b4b] leading-none">
                        50
                    </div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">
                        borrower
                    </div>
                </div>
                <span class="absolute top-4 right-4 p-2 text-[#b04b4b] text-xl cursor-pointer transition-all duration-300 hover:scale-125 hover:-translate-y-1 hover:translate-x-1">↗</span>
            </div>

            <div class="col-span-2 p-6 bg-[#eeeeee] border-2 border-transparent hover:border-gray-400 rounded-2xl transition-all duration-300 relative flex flex-col">
                <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase mb-6">Borrowers</h2>
                <span class="absolute top-4 right-4 p-2 text-[#b04b4b] text-xl cursor-pointer transition-all duration-300 hover:scale-125 hover:-translate-y-1 hover:translate-x-1">↗</span>

                <div class="flex flex-col gap-4 pr-10">
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-bold text-[#b04b4b] w-16 text-right uppercase">Active</span>
                        <div class="flex-1 h-8 bg-transparent flex items-center">
                            <div class="h-full bg-[#8a3333] rounded-sm" style="width: 90%;"></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-bold text-[#b04b4b] w-16 text-right uppercase leading-tight">Fully Paid</span>
                        <div class="flex-1 h-8 bg-transparent flex items-center">
                            <div class="h-full bg-[#8a3333] rounded-sm" style="width: 8%;"></div>
                        </div>
                    </div>
                </div>

                <div class="flex ml-[80px] mt-2 justify-between pr-10">
                    <span class="text-[10px] font-bold text-gray-400">0</span>
                    <span class="text-[10px] font-bold text-gray-400">10</span>
                    <span class="text-[10px] font-bold text-gray-400">20</span>
                    <span class="text-[10px] font-bold text-gray-400">30</span>
                    <span class="text-[10px] font-bold text-gray-400">40</span>
                    <span class="text-[10px] font-bold text-gray-400">50</span>
                </div>
            </div>
        </div>

        <div class="flex-1 p-3 pb-5 mt-0 bg-[#eeeeee] border-2 border-transparent hover:border-gray-400 rounded-2xl transition-all duration-300 relative flex flex-col">
            <!-- Header -->
            <h2 class="text-[#b04b4b] font-bold text-sm tracking-tight uppercase mb-6">
                Running Accounts Receivable
            </h2>

            <span class="absolute top-4 right-4 p-2 text-[#b04b4b] text-xl cursor-pointer transition-all duration-300 hover:scale-125 hover:-translate-y-1 hover:translate-x-1">
                ↗
            </span>

            <!-- Bars -->
            <div class="flex flex-col gap-4 pr-10">

                <!-- Amount Loan -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-[#b04b4b] w-24 text-right uppercase">
                        Amount Loan
                    </span>
                    <div class="flex-1 h-8 bg-transparent flex items-center">
                        <div class="h-full bg-black rounded-sm" style="width: 100%;"></div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-[#b04b4b] w-24 text-right uppercase">
                        Payment
                    </span>
                    <div class="flex-1 h-8 bg-transparent flex items-center">
                        <div class="h-full bg-gray-500 rounded-sm" style="width: 6.7%;"></div>
                    </div>
                </div>

                <!-- Outstanding -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-[#b04b4b] w-24 text-right uppercase">
                        Outstanding
                    </span>
                    <div class="flex-1 h-8 bg-transparent flex items-center">
                        <div class="h-full bg-[#d7845f] rounded-sm" style="width: 100%;"></div>
                    </div>
                </div>

                <!-- Income -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-[#b04b4b] w-24 text-right uppercase">
                        Income
                    </span>
                    <div class="flex-1 h-8 bg-transparent flex items-center">
                        <div class="h-full bg-[#8a3333] rounded-sm" style="width: 6.7%;"></div>
                    </div>
                </div>

            </div>

            <!-- Axis -->
            <div class="flex ml-[112px] mt-2 justify-between pr-10">
                <span class="text-[10px] font-bold text-gray-400">0</span>
                <span class="text-[10px] font-bold text-gray-400">200k</span>
                <span class="text-[10px] font-bold text-gray-400">400k</span>
                <span class="text-[10px] font-bold text-gray-400">600k</span>
                <span class="text-[10px] font-bold text-gray-400">800k</span>
            </div>

        </div> <!-- end of RR -->

       
    </div>
</div>

<style>
/* Custom thin scrollbar to keep the UI clean */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #b04b4b; }
</style>