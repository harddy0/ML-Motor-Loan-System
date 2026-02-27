<?php
$pageTitle = "BORROWERS INFORMATION";
$currentPage = "borrowers";
require_once __DIR__ . '/../../src/includes/init.php'; 

// --- FETCH REAL DATA ---
try {
    $loanService = new \App\LoanService($pdo);
    $borrowers = $loanService->getAllBorrowers();
} catch (Exception $e) {
    $borrowers = []; 
}
?>

<div class="flex flex-col xl:flex-row justify-between items-end mb-5 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-3">
            <h1 class="text-2xl">
                Borrowers Information
            </h1>
        </div>
        
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-slate-800 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" id="searchInput" placeholder="Search by ID or Name" 
                class="w-full h-8 pl-14 pr-6 bg-white border border-slate-200 rounded-full 
                placeholder:text-slate-300 text-[13px]
                focus:border-slate-300 focus:ring-1 focus:ring-slate-500/5 focus:shadow-md transition-all shadow-sm">
        </div>
    </div>

        <div class="flex flex-row items-center justify-end gap-3 w-full">
            
            <button id="viewAllBtn" 
                class="h-8 px-6 bg-slate-100 text-slate-800 rounded-full text-[13px] 
                shadow-md hover:bg-slate-300 transition-all active:scale-95 shrink-0">
                View All
            </button>

            <div class="h-8 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm hover:shadow-md hover:border-slate-300 transition-all px-1 group shrink-0">
    
                <label for="fromDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">From</span>
                        <input type="date" id="fromDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>

                <label for="toDate" class="h-full px-3 flex items-center cursor-pointer hover:bg-slate-50 rounded-r-full transition-colors group/item2 relative">
                    <div class="flex flex-row relative gap-3">
                        <span class="text-[13px] text-slate-400 mb-0.5">To</span>
                        <input type="date" id="toDate" class="text-[13px] font-bold text-slate-700 outline-none bg-transparent w-[105px] cursor-pointer custom-date-input">
                    </div>
                    <svg class="w-5 h-5 text-slate-300 group-hover/item2:text-slate-800 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </label>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button onclick="openImportModal()" 
                    class="h-8 px-4 bg-[#e11d48] hover:bg-[#be123c] text-[13px] text-white rounded-full transition-colors shadow-lg shadow-red-900/10">
                        Import
                </button>
                <button onclick="openAddModal()" 
                    class="h-8 px-6 bg-slate-100 text-slate-800 rounded-full text-[13px] 
                    shadow-md hover:bg-slate-300 transition-all active:scale-95">
                        Add
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded border border-slate-300 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse table-fixed">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-300">
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200">ID</th>
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200">Full Name</th>
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200">Reference No.</th>
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200">PN Number</th>
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200 text-center">Date Released</th>
                <th class="w-1/6 px-3 py-2 text-[14px] font-bold text-slate-600 uppercase tracking-wider text-center">Region</th>
            </tr>
        </thead>
        <tbody id="borrowersTableBody">
            <?php if (empty($borrowers)): ?>
                <?php else: ?>
                <?php foreach ($borrowers as $borrower): 
                    $safe_data = htmlspecialchars(json_encode($borrower), ENT_QUOTES, 'UTF-8');
                ?>
                <tr onclick='openViewModal(<?= $safe_data ?>)' 
                    class="borrower-row hover:bg-slate-100 transition-colors cursor-pointer border-b border-slate-200 last:border-0"
                    data-id="<?= htmlspecialchars($borrower['id']) ?>"
                    data-name="<?= htmlspecialchars(strtolower($borrower['name'])) ?>"
                    data-date="<?= htmlspecialchars($borrower['raw_date'] ?? '') ?>">
                    
                    <td class="px-3 py-1.5 text-[14px] text-slate-700 border-r border-slate-100 truncate"><?= $borrower['id'] ?></td>
                    <td class="px-3 py-1.5 text-[14px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate"><?= $borrower['name'] ?></td>
                    <td class="px-3 py-1.5 text-[14px] text-slate-600 border-r border-slate-100 uppercase truncate"><?= $borrower['reference_no'] ?? '---' ?></td>
                    <td class="px-3 py-1.5 text-[14px] text-slate-600 border-r border-slate-100 font-mono truncate"><?= $borrower['pn_no'] ?? '---' ?></td>
                    <td class="px-3 py-1.5 text-[14px] text-slate-600 border-r border-slate-100 text-center truncate"><?= $borrower['date'] ?></td>
                    <td class="px-3 py-1.5 text-[13px] text-slate-600 text-center">
                        <span>
                            <?= $borrower['region'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="confirmSaveModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-12 text-center border border-slate-100">
        <div class="flex justify-center mb-8">
            <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center text-blue-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
        <h3 class="text-slate-800 font-bold text-2xl mb-3">Confirm Import</h3>
        <p id="confirmMessage" class="text-slate-400 text-sm mb-10 leading-relaxed px-4"></p>
        <div class="flex flex-col gap-3 items-center">
            <button id="realSubmitBtn" class="w-full max-w-[180px] py-4 bg-[#e11d48] text-white rounded-full text-[13px] shadow-lg hover:brightness-110 transition-all active:scale-95">
                Yes, Proceed
            </button>
            <button onclick="document.getElementById('confirmSaveModal').classList.replace('flex', 'hidden')" class="text-slate-400 text-[13px] hover:text-slate-600 transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<div id="successAlertModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-12 text-center border border-slate-100">
        <div class="bg-[#e8fbf3] w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-8">
            <svg class="w-10 h-10 text-[#22c55e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-slate-800 font-bold text-2xl mb-3">Import Successful</h3>
        <p id="successMessage" class="text-slate-400 text-sm mb-10 leading-relaxed"></p>
        <button onclick="window.location.href='/ML-MOTOR-LOAN-SYSTEM/public/borrowers/'" 
            class="w-full max-w-[180px] py-4 bg-[#e11d48] text-white rounded-full text-[11px] font-black uppercase tracking-[0.2em] shadow-lg hover:brightness-110 transition-all active:scale-95">
            OK
        </button>
    </div>
</div>

<?php include dirname(__DIR__) . '/../src/includes/modals/view_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/add_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/amortization_schedule.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_borrowers.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_preview.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_detail.php'; ?>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>/public/assets/js/borrowers.js"></script>