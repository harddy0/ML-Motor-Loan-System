<?php
$pageTitle = "LEDGER REPORTS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-4 gap-6 -mt-4">
    <div class="w-full xl:w-auto">
        <div class="mb-2">
            <h1 class="text-2xl text-slate-700 font-medium">Ledger Reports</h1>
        </div>
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by PN, ID Number, or Name" 
                class="w-full h-8 pl-14 pr-6 bg-white border border-slate-200 rounded-full 
                text-[16px] outline-none placeholder:text-slate-300 placeholder:text-[13px]
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
        </div>
    </div>

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
        <span class="text-[12px] font-medium text-slate-500 mr-4">Filter by Status & Date</span>
        <div class="flex items-center gap-3 w-full justify-end">
            
            <div class="relative">
                <select id="statusFilter" class="h-8 pl-4 pr-8 bg-white border border-slate-200 rounded-full text-[13px] font-medium text-slate-700 outline-none focus:border-slate-300 hover:border-slate-300 hover:shadow-md transition-all shadow-sm cursor-pointer appearance-none">
                    <option value="">All Statuses</option>
                    <option value="ONGOING">Ongoing</option>
                    <option value="FULLY PAID">Fully Paid</option>
                    <option value="VOIDED">Voided</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <div class="h-8 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all px-1 group shrink-0">
                <label for="fromDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">From</span>
                        <input type="date" id="fromDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                </label>
                <label for="toDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">To</span>
                        <input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 transition-all duration-300">
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Total Ledgers</h3>
        <span id="stat-total" class="text-3xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Ongoing</h3>
        <span id="stat-ongoing" class="text-3xl font-bold text-slate-700 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Fully Paid</h3>
        <span id="stat-paid" class="text-3xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
    <div class="bg-white border-t-2 border-[#e11d48] rounded-xl shadow-sm p-2 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
        <h3 class="text-slate-700 text-[14px] tracking-wider mb-1">Voided</h3>
        <span id="stat-voided" class="text-3xl font-bold text-slate-800 tracking-tight">0</span>
    </div>
</div>

<div class="bg-white border border-slate-100 rounded-lg shadow-sm overflow-hidden transition-all duration-300">
    <div class="bg-white rounded border border-slate-300 shadow-sm overflow-x-auto flex flex-col relative min-h-[300px]">
        
        <div id="table-loader" class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#ce1126] mb-2"></div>
                <span class="text-[13px] text-slate-500 font-medium">Loading data...</span>
            </div>
        </div>

        <table class="w-full text-left border-collapse table-fixed">
            <thead>
                <tr class="bg-[#ce1126] border-b border-slate-300">
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest border-r border-slate-100 text-center">System Loan No.</th>
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest border-r border-slate-100 text-center">Employee ID</th>
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest border-r border-slate-100 text-center">Date Release</th>
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest border-r border-slate-100 text-center">Maturity Date</th>
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest border-r border-slate-100">Full Name</th>
                    <th class="w-1/6 px-4 py-2 text-[14px] text-white uppercase tracking-widest text-center">Status</th>
                </tr>
            </thead>
            <tbody id="borrowersTableBody" class="divide-y divide-slate-100">
                </tbody>
        </table>

        <div class="flex justify-between items-center p-4 bg-slate-50 border-t border-slate-200 mt-auto" id="pagination-container">
            <div class="text-[13px] text-slate-500">
                Showing <span id="page-start" class="font-bold text-slate-700">0</span> to <span id="page-end" class="font-bold text-slate-700">0</span> of <span id="page-total" class="font-bold text-slate-700">0</span> entries
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-prev-page" class="px-3 py-1.5 text-[13px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                    Previous
                </button>
                <span id="page-info" class="px-3 py-1.5 text-[13px] text-slate-600 font-medium">Page 1 of 1</span>
                <button id="btn-next-page" class="px-3 py-1.5 text-[13px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../src/includes/modals/ledger_detail.php'; ?>
<script src="../../assets/js/ledger.js"></script>