<?php
$pageTitle = "LOAN PROGRESS";
$currentPage = "reports";
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<div id="loanProgressContainer" class="w-full overflow-x-hidden">
    <div class="animate-fadeIn w-full px-0 pt-0 transition-all duration-300">
    <div class="mb-3">
        <nav class="inline-flex items-center rounded-full border border-slate-200 bg-white p-1 shadow-sm" aria-label="Ledger report navigation">
            <a href="<?= BASE_URL ?>/public/reports/ledger/" class="px-4 py-1.5 rounded-full text-slate-600 text-[13px] font-bold hover:bg-slate-100 transition-colors">Ledger Reports</a>
            <a href="<?= BASE_URL ?>/public/reports/ledger/loan_progress.php" class="px-4 py-1.5 rounded-full bg-[#ce1126] text-white text-[13px] font-bold">Loan Progress</a>
        </nav>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 border-t-[3px] border-t-[#ce1126] shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden mb-1">

        <div class="px-5 pt-1 pb-1 shrink-0 flex items-center justify-between border-b border-slate-100">
            <div>
                <h3 class="text-[13px] font-extrabold text-slate-900 tracking-widest uppercase">Loan Progress</h3>
            </div>
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center gap-1 rounded-full bg-slate-100 p-1" role="tablist" aria-label="Loan progress status filter">
                    <button type="button" id="lpFilterAll" data-status="ALL" class="lp-status-btn px-3 py-1 rounded-full text-[11px] font-bold text-slate-600">ALL</button>
                    <button type="button" id="lpFilterOngoing" data-status="ONGOING" class="lp-status-btn px-3 py-1 rounded-full text-[11px] font-bold text-slate-600">ONGOING</button>
                    <button type="button" id="lpFilterPaid" data-status="FULLY PAID" class="lp-status-btn px-3 py-1 rounded-full text-[11px] font-bold text-slate-600">FULLY PAID</button>
                </div>

                <div id="exportLoanProgressMenuWrap" class="relative">
                    <button id="exportLoanProgressMenuBtn" type="button" class="h-8 px-4 bg-[#ce1126] text-white rounded-full text-[12px] shadow-md hover:brightness-110 hover:shadow-lg transition-all duration-200 ease-in-out active:scale-[0.98] inline-flex items-center gap-1" title="Export Loan Progress">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0 0l-5-5m5 5l5-5M5 19h14" />
                        </svg>
                        Export
                    </button>

                    <div id="exportLoanProgressMenu" class="hidden absolute right-0 mt-2 w-24 origin-top-right bg-white border border-slate-200 rounded-xl shadow-xl ring-1 ring-black/5 z-50 overflow-hidden">
                        <button id="exportLoanProgressExcelBtn" type="button" class="w-full flex items-center gap-2 px-3 py-2 text-[11px] text-slate-700 hover:bg-slate-50 border-b border-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 2H8a2 2 0 00-2 2v16a2 2 0 002 2h8a2 2 0 002-2V8l-4-6zM14 2v6h4M9.5 11.5l5 5m0-5l-5 5" />
                            </svg>
                            Excel
                        </button>
                        <button id="printLoanProgressBtn" type="button" class="w-full flex items-center gap-2 px-3 py-2 text-[11px] text-slate-700 hover:bg-slate-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 14H5a2 2 0 00-2 2v3h4v-3h10v3h4v-3a2 2 0 00-2-2h-1M7 14h10" />
                            </svg>
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="w-full min-w-[980px]">
                <div class="px-5 pt-1 pb-0 bg-[#ce1126]">
                    <div class="grid gap-1 pb-1 text-[11px] font-semibold text-white" style="grid-template-columns: repeat(8, minmax(0, 1fr));">
                        <span class="pl-2">Employee ID</span>
                        <span>Full Name</span>
                        <span class="text-center">Maturity Date</span>
                        <span class="text-center">Last Paid Date</span>
                        <span class="text-right">Gross</span>
                        <span class="text-right">Payment</span>
                        <span class="text-right">Balance</span>
                        <span class="text-center pr-0">Progress</span>
                    </div>
                </div>

                <!-- Rows rendered by loadLoanProgressReport() -->
                <div class="px-5 py-2 flex flex-col gap-0" id="loanProgressList">
                    <p class="text-sm font-medium text-slate-400 italic py-6 text-center">Loading...</p>
                </div>
            </div>
        </div>

        </div>
    </div>
</div>

<script>
window.BASE_URL = "<?= BASE_URL ?>";
window.CURRENT_USER_FULL_NAME = <?= json_encode((string)($_SESSION['full_name'] ?? 'SYSTEM USER')) ?>;
</script>
<script src="<?= BASE_URL ?>/public/assets/js/loan_progress.js"></script>
