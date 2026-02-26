<?php
$pageTitle = "DEDUCTION REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-4">
            <h1 class="text-2xl ">Deduction Reports</h1>
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
    <span class="text-[12px] text-slate-400 mr-44 hidden sm:block">Filter by Date Imported</span>

    <div class="flex flex-row items-center justify-end gap-3 w-full">
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

        <button id="exportDeductionBtn" class="h-10 flex items-center gap-1 px-4 bg-[#e11d48] text-white rounded-full 
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
</div>

<div class="flex flex-col lg:flex-row gap-8 h-full min-h-[500px]">

    <div class="flex-1 flex flex-col bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-slate-800 text-[14px] flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-[#e11d48]"></div>
                Payroll Deduction List
            </h2>
            <span class="text-[13px] text-slate-400">Live Records</span>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-800">
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-24">ID No.</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200 w-32">Payroll Date</th>
                        <th class="px-5 py-3 text-[14px] border-r border-slate-200">Full Name</th>
                        <th class="px-5 py-3 text-[14px] text-right border-r border-slate-200 w-32">Deduction</th>
                        <th class="px-5 py-3 text-[14px] text-center border-r border-slate-200">Region</th>
                        <th class="px-5 py-3 text-[14px] text-center">Date Imported</th>
                    </tr>
                </thead>
                <tbody id="deductionTableBody" class="divide-y divide-slate-100">
                </tbody>
            </table>
        </div>
        
        <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex justify-between items-center">
            <span id="showing-count" class="text-[13px] text-slate-400">Showing 0 records</span>
        </div>
    </div>

    <div class="w-full lg:w-72 flex flex-col gap-6 shrink-0">
        
        <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            <h3 class="text-slate-400 text-[14px] mb-1 relative z-10">Total Records</h3>
            <div class="flex items-baseline gap-1 relative z-10">
                <span id="total-count" class="text-5xl text-slate-800">0</span>
            </div>
        </div>

         <div class="bg-white border-t-4 border-[#e11d48] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-200 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
            
            <h3 class="text-slate-400 text-[14px] mb-1 relative z-10">Total Amount</h3>
            <div class="relative z-10">
                <span id="total-amount" class="text-3xl text-slate-800 ">₱ 0.00</span>
            </div>
        </div>

    </div>

</div>

<script src="../../../public/assets/js/deduction.js"></script>