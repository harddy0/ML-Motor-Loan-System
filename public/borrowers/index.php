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

<div class="flex flex-col xl:flex-row justify-between items-end mb-10 gap-6">
    <div class="w-full xl:w-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">
                Borrowers <span class="text-[#e11d48]">Information</span>
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full bg-[#e11d48]"></span>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Official Registry</p>
            </div>
        </div>
        
        <div class="relative w-full xl:w-96 group">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-[#e11d48] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" placeholder="SEARCH BY ID OR NAME..." 
                class="w-full h-12 pl-14 pr-6 bg-white border border-slate-200 rounded-full text-[11px] font-bold outline-none uppercase placeholder:text-slate-300 focus:border-[#e11d48] focus:ring-4 focus:ring-[#e11d48]/5 transition-all shadow-sm">
        </div>
    </div>

    <div class="flex flex-col items-end gap-3 w-full xl:w-auto">
       
        <div class="flex flex-none items-center gap-3">
            <button class="h-11 px-6 bg-white text-slate-500 border border-slate-200 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 hover:text-slate-800 transition-all shadow-sm">
                View All
            </button>
            
            <div class="h-11 flex items-center bg-white border border-slate-200 rounded-full overflow-hidden shadow-sm px-2">
                <div class="h-full px-4 flex items-center gap-2 border-r border-slate-100">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">From</span>
                    <input type="date" value="<?= date('Y-m-d') ?>" class="text-[10px] font-black text-slate-700 outline-none bg-transparent w-24">
                </div>
                <div class="h-full px-4 flex items-center gap-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">To</span>
                    <input type="date" value="<?= date('Y-m-d') ?>" class="text-[10px] font-black text-slate-700 outline-none bg-transparent w-24">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="openImportModal()" 
                class="h-11 px-6 bg-[#e11d48] text-white rounded-full text-[10px] 
                font-black uppercase tracking-widest shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                    Import
                </button>
                <button onclick="openAddModal()" 
                class="h-11 px-6 bg-slate-100 text-slate-800 rounded-full text-[10px] 
                font-black uppercase tracking-widest shadow-md hover:bg-slate-300 transition-all active:scale-95">
                    Add
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded border-2 border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 border-b-2 border-slate-200">
                <th class="px-6 py-4 text-[12px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50">ID</th>
                <th class="px-6 py-4 text-[12px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50">Full Name</th>
                <th class="px-6 py-4 text-[12px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50 text-center">Date Granted</th>
                <th class="px-6 py-4 text-[12px] font-black text-slate-600 uppercase text-center">Branch</th>
            </tr>
        </thead>
        <tbody class="divide-y-2 divide-slate-100">
            <?php if (empty($borrowers)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-slate-500 text-xs font-bold uppercase">
                        No borrowers found in database.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($borrowers as $borrower): 
                    $safe_data = htmlspecialchars(json_encode($borrower), ENT_QUOTES, 'UTF-8');
                ?>
                <tr onclick='openViewModal(<?= $safe_data ?>)' 
                    class="hover:bg-red-50 transition-colors cursor-pointer group border-b border-slate-100">
                    <td class="px-6 py-4 text-sm font-bold text-slate-500 border-r-2 border-slate-100"><?= $borrower['id'] ?></td>
                    <td class="px-6 py-4 text-sm font-black text-slate-800 uppercase border-r-2 border-slate-100 group-hover:text-[#ff3b30]"><?= $borrower['name'] ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-500 border-r-2 border-slate-100 text-center"><?= $borrower['date'] ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-slate-800 text-white text-sm font-black uppercase rounded">
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
            <button id="realSubmitBtn" class="w-full max-w-[180px] py-4 bg-[#e11d48] text-white rounded-full text-[11px] font-black uppercase tracking-[0.2em] shadow-lg hover:brightness-110 transition-all active:scale-95">
                Yes, Proceed
            </button>
            <button onclick="document.getElementById('confirmSaveModal').classList.replace('flex', 'hidden')" class="text-slate-400 text-[10px] font-bold uppercase tracking-widest hover:text-slate-600 transition-colors">
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