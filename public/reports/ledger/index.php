<?php
$pageTitle = "LEDGER REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<div class="max-w-full overflow-x-hidden">

<!-- Title + Search (left) | Filters (right, bottom-aligned with search bar) -->
<div class="flex flex-col xl:flex-row justify-between items-end mb-4 gap-3">

    <!-- LEFT: title stacked above search bar -->
    <div class="flex-shrink-0">
        <h1 class="text-2xl text-slate-700 mb-2">Ledger Reports</h1>
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
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

    <!-- RIGHT: filters row, aligned to bottom -->
    <div class="flex flex-row items-center gap-2 flex-shrink-0">

        <!-- Status Filter Dropdown -->
        <div class="relative inline-block text-left">
            <button id="ledgerFilterBtn" class="flex items-center gap-2 h-8 px-3 bg-slate-100 text-slate-600 rounded-full hover:bg-slate-200 transition-all whitespace-nowrap">
                <span id="selectedStatusText" class="text-[13px]">View All</span>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="ledgerFilterMenu" class="hidden absolute left-0 mt-2 w-40 origin-top-left bg-white border border-slate-100 rounded-xl shadow-xl ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                <div class="py-1">
                    <button class="ledger-status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50 border-b border-slate-50" data-status="" data-label="All Statuses">View All</button>
                    <button class="ledger-status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50 border-b border-slate-50" data-status="ONGOING" data-label="Ongoing">Ongoing</button>
                    <button class="ledger-status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50 border-b border-slate-50" data-status="FULLY PAID" data-label="Fully Paid">Fully Paid</button>
                    <button class="ledger-status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50" data-status="VOIDED" data-label="Void">Void</button>
                </div>
            </div>
            <input type="hidden" id="statusFilter" value="">
        </div>

        <!-- From date — standalone input, no wrapper label -->
        <div class="relative flex items-center h-8">
            <span class="absolute left-3 text-[11px] font-semibold text-slate-400 pointer-events-none select-none z-10 leading-none" style="top:50%;transform:translateY(-50%)">From</span>
            <input type="date" id="fromDate"
                class="h-8 pl-12 pr-3 bg-white border border-slate-200 rounded-full text-[13px] font-bold text-slate-700 outline-none shadow-sm hover:border-slate-300 hover:shadow-md focus:border-slate-400 transition-all cursor-pointer custom-date-input"
                style="min-width:160px;">
        </div>

        <!-- To date — standalone input, no wrapper label -->
        <div class="relative flex items-center h-8">
            <span class="absolute left-3 text-[11px] font-semibold text-slate-400 pointer-events-none select-none z-10 leading-none" style="top:50%;transform:translateY(-50%)">To</span>
            <input type="date" id="toDate"
                class="h-8 pl-8 pr-3 bg-white border border-slate-200 rounded-full text-[13px] font-bold text-slate-700 outline-none shadow-sm hover:border-slate-300 hover:shadow-md focus:border-slate-400 transition-all cursor-pointer custom-date-input"
                style="min-width:155px;">
        </div>

    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 mt-2 transition-all duration-300">
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-1 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Total Ledgers</h3>
        <span id="stat-total" class="text-1xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-1 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Ongoing</h3>
        <span id="stat-ongoing" class="text-1xl font-bold text-slate-700 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-1 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Fully Paid</h3>
        <span id="stat-paid" class="text-1xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-1 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Void</h3>
        <span id="stat-voided" class="text-1xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
</div>

<div class="bg-white border border-slate-100 rounded-lg shadow-sm overflow-hidden transition-all duration-300">
    <div class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden flex flex-col relative min-h-[300px]">
        
        <div id="table-loader" class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#ce1126] mb-2"></div>
                <span class="text-[13px] text-slate-500 font-medium">Loading data...</span>
            </div>
        </div>

        <table class="w-full text-left border-collapse table-auto">
            <thead>
                <tr class="bg-[#ce1126] border-b border-slate-300">
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest border-r border-slate-100 text-center">System Loan Number</th>
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest border-r border-slate-100 text-center">Employee ID</th>
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest border-r border-slate-100 text-center">Date Released</th>
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest border-r border-slate-100 text-center">Maturity Date</th>
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest border-r border-slate-100 text-center">Full Name</th>
                    <th class="w-auto px-1 py-1 text-[14px] text-white tracking-widest text-center">Status</th>
                </tr>
            </thead>
            <tbody id="borrowersTableBody" class="divide-y divide-slate-100">
            </tbody>
        </table>

        <div class="flex justify-between items-center p-2 bg-slate-50 border-t border-slate-200 mt-auto" id="pagination-container">
            <div class="text-[13px] text-slate-500">
                Showing <span id="page-start" class="font-bold text-slate-700 text-[13px]">0</span> to <span id="page-end" class="font-bold text-slate-700 text-[13px]">0</span> of <span id="page-total" class="font-bold text-[13px] text-slate-700">0</span> entries
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

</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/ledger_detail.php'; ?>
<script src="../../assets/js/ledger.js"></script>