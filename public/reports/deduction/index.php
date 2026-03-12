<?php
$pageTitle = "DEDUCTION REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-4 gap-6 -mt-4">
    <div class="w-full xl:w-auto">
        <div class="mb-2">
            <h1 class="text-2xl text-slate-800">Deduction Reports</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-slate-500 font-mono text-sm">
                    as of <?= date('F d, Y') ?>
                </p>
            </div>
        </div>
        
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by Employee ID or Name" 
                class="w-full h-8 pl-14 pr-10 bg-white border border-slate-200 rounded-full 
                text-[16px] outline-none  placeholder:text-slate-300 placeholder:text-[13px]
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
            <button type="button" id="clearSearchInput"
                class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors"
                aria-label="Clear search">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
    <span class="text-[12px] text-slate-400 mr-44 hidden sm:block">Filter by Date Imported</span>

    <div class="flex flex-row items-center justify-end gap-3 w-full">

        <div class="h-8 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all px-1 group shrink-0">
    
                <label for="fromDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3"><span class="text-[13px] text-slate-400 mb-0.5">From</span><input type="date" id="fromDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input"></div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>

                <label for="toDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3"><span class="text-[13px] text-slate-400 mb-0.5">To</span><input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input"></div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>
            </div>

        <button id="exportDeductionBtn" class="h-8 flex items-center gap-1 px-4 bg-[#ce1126] text-white rounded-full 
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

<div class="flex flex-col gap-6 h-full w-full">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-[50%]">

    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-800 text-[14px] mb-1 uppercase tracking-wide">Total Records</h3>
        <div class="flex items-baseline gap-2">
            <span id="total-count" class="text-1xl font-bold text-slate-800 tracking-tight">0</span>
        </div>
    </div>

    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-800 text-[14px] mb-1 uppercase tracking-wide">Total Deductions</h3>
        <div class="flex items-baseline gap-2">
            <span id="total-amount" class="text-1xl font-bold text-slate-800 tracking-tight">₱0.00</span>
        </div>
        <span id="total-amount-label" class="hidden text-[10px] text-[#ce1126] font-bold mt-0.5 tracking-wide">FILTERED</span>
    </div>

</div>

    <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col">

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse table-fixed">
                <thead>
                    <tr class="bg-[#ce1126] border-b border-slate-300">
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 text-center w-20">Employee ID</th>
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 text-center w-20">Due Date</th>
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 w-24 text-center">Full Name</th>
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 text-right w-20">Deduction</th>
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 text-center w-24">Region</th>
                        <th class="px-4 py-1 text-[14px] font-black text-white tracking-widest border-r border-slate-100 text-center w-24">Date Imported</th>
                    </tr>
                </thead>
                <tbody id="deductionTableBody" class="divide-y divide-slate-100">
                </tbody>
            </table>
        </div>

        <!-- PAGINATION CONTROLS -->
        <div class="flex justify-between items-center p-2 bg-slate-50 border-t border-slate-200 mt-auto" id="pagination-container">
            <div class="text-[13px] text-slate-500">
                Showing <span id="page-start" class="font-bold text-slate-700 text-[13px]">0</span>
                to <span id="page-end" class="font-bold text-slate-700 text-[13px]">0</span>
                of <span id="page-total" class="font-bold text-[13px] text-slate-700">0</span> entries
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-prev-page" class="px-3 py-1.5 text-[11px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                    Previous
                </button>
                <span id="page-info" class="px-3 py-1.5 text-[11px] text-slate-600 font-medium">Page 1 of 1</span>
                <button id="btn-next-page" class="px-3 py-1.5 text-[11px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                    Next
                </button>
            </div>
        </div>

    </div>
</div>

<script src="../../../public/assets/js/deduction.js"></script>