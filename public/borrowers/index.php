<?php
$pageTitle = "BORROWERS INFORMATION";
$currentPage = "borrowers";
require_once __DIR__ . '/../../src/includes/init.php'; 

try {
    $loanService = new \App\LoanService($pdo);
    $pendingLoans = $loanService->getPendingKptnLoans(); 
} catch (Exception $e) {
    $pendingLoans = [];
}
?>

<div class="max-w-full overflow-x-hidden">

<!-- ROW 1: Title + Search (left) | Filters (right, bottom-aligned with search bar) -->
<div class="flex flex-col xl:flex-row justify-between items-end mb-0 gap-3">

    <!-- LEFT: title stacked above search bar -->
    <div class="flex-shrink-0">
        <h1 class="text-2xl mb-3">Borrowers Information</h1>
        
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

    <div class="flex flex-col items-end gap-1 w-full xl:w-auto">
    <span class="text-[12px] text-slate-400 mr-44 hidden sm:block">Filter by Date Released</span>
    <!-- RIGHT: all filters in one row, aligned to bottom (same h-8 height as search bar) -->
    <div class="flex flex-row items-center gap-2 flex-shrink-0">

        <!-- Status Filter Dropdown -->
        <div class="relative inline-block text-left">
            <button id="borrowerFilterBtn" class="flex items-center gap-2 h-8 px-3 bg-slate-100 text-slate-600 rounded-full hover:bg-slate-200 transition-all whitespace-nowrap">
                <span id="selectedStatusText" class="text-[13px]">Select Status</span>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="borrowerFilterMenu" class="hidden absolute left-0 mt-2 w-38 origin-top-left bg-white border border-slate-100 rounded-xl shadow-xl ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                <div class="py-1">
                    <button class="status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50 border-b border-slate-50" data-status="ONGOING" data-label="Ongoing">Ongoing</button>
                    <button class="status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50 border-b border-slate-50" data-status="FULLY PAID" data-label="Fully Paid">Fully Paid</button>
                    <button class="status-opt block w-full text-left px-4 py-2.5 text-[13px] text-slate-700 hover:bg-slate-50" data-status="VOIDED" data-label="Inactive">Inactive</button>
                </div>
            </div>
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
</div>

<!-- ROW 2: Tabs + Import/Add buttons on the same line -->
<div class="flex items-center justify-between mb-2">
    <div class="flex gap-2">
        <button onclick="switchTab('active')" id="tab-active" class="px-6 py-3 border-b-2 border-[#ce1126] text-[#ce1126] font-bold text-[13px] tracking-wide transition-colors">
            Active Loans (<span id="tab-all-count" class="text-sm"></span>)
        </button>
        <button onclick="switchTab('pending')" id="tab-pending" class="px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors">
            Upload KPTN Form (<?= count($pendingLoans) ?>)
        </button>
        <button onclick="switchTab('inactive')" id="tab-inactive" class="px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors">
            Inactive Loans (<span id="tab-inactive-count" class="text-sm"></span>)
        </button>
    </div>
    <div class="flex items-center gap-2">
        <div class="relative inline-block text-left">
            <button id="exportMenuBtn" type="button" class="h-8 px-4 bg-slate-100 text-slate-700 rounded-full text-[13px] shadow-md hover:bg-slate-200 transition-all active:scale-95 inline-flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 17v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
                </svg>
                Export
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-2 w-24 origin-top-right bg-white border border-slate-200 rounded-xl shadow-xl ring-1 ring-black/5 z-50 overflow-hidden">
                <button type="button" onclick="exportBorrowersExcel()" class="w-full flex items-center gap-2 px-3 py-2 text-[11px] text-slate-700 hover:bg-slate-50 border-b border-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H8a2 2 0 00-2 2v16a2 2 0 002 2h8a2 2 0 002-2V8l-4-6zM14 2v6h4M9.5 11.5l5 5m0-5l-5 5" />
                    </svg>
                    Excel
                </button>
                <button type="button" onclick="printBorrowersList()" class="w-full flex items-center gap-2 px-3 py-2 text-[11px] text-slate-700 hover:bg-slate-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 14H5a2 2 0 00-2 2v3h4v-3h10v3h4v-3a2 2 0 00-2-2h-1M7 14h10" />
                    </svg>
                    Print
                </button>
            </div>
        </div>
        <button onclick="openImportModal()" class="h-8 px-4 bg-[#ce1126] hover:bg-[#bd0217] text-[13px] text-white rounded-full transition-colors shadow-lg shadow-red-900/10">
            Import
        </button>
        <button onclick="openAddModal()" class="h-8 px-6 bg-slate-100 text-slate-800 rounded-full text-[13px] shadow-md hover:bg-slate-300 transition-all active:scale-95">
            Add
        </button>
    </div>
</div>

<?php if(isset($_SESSION['success_msg'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative shadow-sm">
        <span class="block sm:inline font-bold"><?= htmlspecialchars($_SESSION['success_msg']); ?></span>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error_msg'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative shadow-sm">
        <span class="block sm:inline font-bold"><?= htmlspecialchars($_SESSION['error_msg']); ?></span>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>



<div id="table-active" class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden block relative min-h-[300px] flex flex-col">
    
    <div id="table-loader" class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-10 hidden">
        <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#ce1126] mb-2"></div>
            <span class="text-[13px] text-slate-500 font-medium">Loading borrowers...</span>
        </div>
    </div>

    <table class="w-full text-left border-collapse table-fixed">
        <thead>
            <tr class="bg-[#ce1126] border-b border-slate-300">
                <th class="w-[10%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">System Loan No.</th>
                <th class="w-[15%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Reference Number</th>
                <th class="w-[10%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center">Date Released</th>
                <th class="w-[8%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center">Employee ID</th>
                <th class="w-[14%] px-3 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Full Name</th>
                <th class="w-[16%] px-2 py-1 text-[13px] font-bold text-white tracking-wider text-center border-r border-slate-200 whitespace-nowrap">Region</th>
                <th class="w-[6%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center">Status</th>
                <th class="w-[6%] px-2 py-1 text-[13px] font-bold text-white tracking-wider text-center">Action</th>
            </tr>
        </thead>
        <tbody id="borrowersTableBody">
        </tbody>
    </table>

    <div class="flex justify-between items-center p-2 py-2 bg-slate-50 border-t border-slate-200 mt-auto">
        <div class="text-[11px] text-slate-500">
            Showing <span id="page-start" class="font-bold text-slate-700 text-[12px]">0</span> to <span id="page-end" class="font-bold text-slate-700 text-[12px]">0</span> of <span id="page-total" class="font-bold text-[12px] text-slate-700">0</span> entries
        </div>
        <div class="flex items-center gap-2">
            <button id="btn-prev-page" class="px-3 py-1.5 text-[11px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">Previous</button>
            <span id="page-info" class="px-3 py-1.5 text-[11px] text-slate-600 font-medium">Page 1 of 1</span>
            <button id="btn-next-page" class="px-3 py-1.5 text-[11px] font-medium bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">Next</button>
        </div>
    </div>
</div>

<div id="table-pending" class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden hidden">
    <table class="w-full text-left border-collapse table-fixed">
        <thead>
            <tr class="bg-[#ce1126] border-b border-red-200">
                <th class="w-[15%] px-2 py-1 text-[14px] font-bold text-white tracking-wider border-r border-red-200 text-center">System Loan No.</th>
                <th class="w-[150px] px-2 py-1 text-[14px] font-bold text-white tracking-wider border-r border-red-200 text-center whitespace-nowrap">Reference Number</th>
                <th class="w-[120px] px-3 py-1 text-[14px] font-bold text-white tracking-wider border-r border-red-200 text-center whitespace-nowrap">Employee ID</th>
                <th class="w-[12%] px-3 py-1 text-[14px] font-bold text-white tracking-wider border-r border-red-200 text-center">KPTN</th>
                <th class="w-[16%] px-3 py-1 text-[14px] font-bold text-white tracking-wider border-r border-red-200 text-center">Full Name</th>
                <th class="w-[15%] px-3 py-1 text-[14px] font-bold text-white tracking-wider text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pendingLoans)): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($pendingLoans as $pending): ?>
                <tr class="hover:bg-slate-50 transition-colors border-b border-slate-200 last:border-0">
                    <td class="px-3 py-0 text-slate-800 border-r border-slate-100 text-center">
                        <span class="block truncate text-[13px] uppercase font-mono" title="<?= htmlspecialchars($pending['pn_no'] ?? '') ?>"><?= htmlspecialchars($pending['pn_no'] ?? '---') ?></span>
                    </td>
                    <td class="px-3 py-0 text-slate-500 border-r border-slate-100 overflow-hidden">
                        <span class="block truncate text-[14px] uppercase font-mono" title="<?= htmlspecialchars($pending['reference_no']) ?>"><?= $pending['reference_no'] ?></span>
                    </td>
                                    <td class="w-[120px] px-3 py-0 text-[14px] text-slate-700 border-r border-slate-100 text-center whitespace-nowrap"><?= $pending['id'] ?></td>
                                    <td class="px-3 py-0 text-slate-500 border-r border-slate-100 overflow-hidden">
                    <span class="block truncate text-[14px] uppercase font-mono" title="<?= htmlspecialchars($pending['pending_kptn'] ?? '-') ?>"><?php echo htmlspecialchars($pending['pending_kptn'] ?? '-'); ?></span>
                </td>
                    <td class="px-3 py-0 text-[14px] text-slate-800 uppercase font-bold border-r border-slate-100"><?= $pending['name'] ?></td>
                    <td class="px-3 py-0 text-center">
                        <button onclick="openAttachKptnModal(<?= $pending['loan_id'] ?>, '<?= htmlspecialchars(addslashes($pending['name'])) ?>', '<?= addslashes($pending['pending_kptn'] ?? '') ?>', '<?= number_format((float)($pending['deposit_amount'] ?? 0), 2, '.', '') ?>')" 
                            class="px-4 py-1 bg-red-100 text-red-700 hover:bg-[#ce1126] hover:text-white rounded-full text-xs font-bold uppercase tracking-wider transition-colors">
                            Upload
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="table-inactive" class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden block relative min-h-[360px] flex flex-col hidden">
    <table class="w-full text-left border-collapse table-fixed">
        <thead>
            <tr class="bg-[#ce1126] border-b border-slate-300">
                <th class="w-[8%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center">Status</th>
                <th class="w-[8%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center">ID Number</th>
                <th class="w-[14%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center truncate">Reference Number</th>
                <th class="w-[14%] px-3 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Full Name</th>
                <th class="w-[14%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Region</th>
                <th class="w-[12%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Reason</th>
                <th class="w-[12%] px-2 py-1 text-[13px] font-bold text-white tracking-wider border-r border-slate-200 text-center whitespace-nowrap">Date Inactivated</th>
                <th class="w-[14%] px-2 py-1 text-[13px] font-bold text-white tracking-wider text-center whitespace-nowrap">Inactivated By</th>
            </tr>
        </thead>
        <tbody id="inactiveBorrowersTableBody">
            <tr><td colspan="8" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>
        </tbody>
    </table>
</div>

<div id="confirmSaveModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-12 text-center border border-slate-100">
        <div class="flex justify-center mb-8">
            <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center text-blue-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
        <h3 class="text-slate-800 font-bold text-2xl mb-3">Confirm Import</h3>
        <p id="confirmMessage" class="text-slate-600 text-sm mb-10 leading-relaxed px-4"></p>
        <div class="flex flex-col gap-3 items-center">
            <button id="realSubmitBtn" class="w-full max-w-[180px] py-4 bg-[#e11d48] text-white rounded-full text-[13px] shadow-lg hover:brightness-110 transition-all active:scale-95">Save</button>
            <button onclick="document.getElementById('confirmSaveModal').classList.replace('flex', 'hidden')" class="text-slate-400 text-[13px] hover:text-slate-600 transition-colors">Cancel</button>
        </div>
    </div>
</div>

<div id="successAlertModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-12 text-center border border-slate-100">
        <div class="bg-[#e8fbf3] w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-8">
            <svg class="w-10 h-10 text-[#22c55e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <h3 class="text-slate-800 font-bold text-2xl mb-3">Success</h3>
        <p id="successMessage" class="text-slate-600 text-sm mb-10 leading-relaxed"></p>
        <button onclick="window.location.href='/ML-MOTOR-LOAN-SYSTEM/public/borrowers/'" class="w-full max-w-[180px] py-4 bg-[#e11d48] text-white rounded-full text-md font-black uppercase tracking-[0.2em] shadow-lg hover:brightness-110 transition-all active:scale-95">OK</button>
    </div>
</div>

</div>

<?php include dirname(__DIR__) . '/../src/includes/modals/view_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/add_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/amortization_schedule.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_borrowers.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_preview.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_detail.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/void_borrower.php'; ?> 
<?php include dirname(__DIR__) . '/../src/includes/modals/inactivate_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/attach_kptn.php'; ?> 

<div id="exportHeaderTemplate" class="hidden">
    <?php include dirname(__DIR__) . '/../src/includes/export_header.php'; ?>
</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
const CURRENT_USER_FULLNAME = <?= json_encode($_SESSION['full_name'] ?? 'System User') ?>;
</script>

<script src="<?= BASE_URL ?>/public/assets/js/borrowers_list.js"></script>

<script src="<?= BASE_URL ?>/public/assets/js/borrowers_export.js"></script>
<script src="<?= BASE_URL ?>/public/assets/js/borrowers_manual.js"></script>
<script src="<?= BASE_URL ?>/public/assets/js/borrowers_import.js"></script>