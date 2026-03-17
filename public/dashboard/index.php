 <?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 
$isAdminOrReviewer = in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER']);
?> 

<div class="h-[500px] flex flex-col -mt-4">

    <div class="flex items-end justify-between mb-4 shrink-0 ">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Dashboard</h1>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>
                <span class="text-slate-500 font-mono text-xs font-medium">as of <?= date('F d, Y') ?></span>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-5 items-start min-h-0 pb-2">

        <div class="flex flex-col gap-4 flex-1 min-w-0 overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

            <div class="grid grid-cols-3 gap-4 shrink-0">
                <?php
                $cards = [
                    ['id' => 'statUnits',     'title' => 'Unpaid Due Date'],
                    ['id' => 'statBorrowers', 'title' => 'Active Borrowers'],
                    ['id' => 'statPaid',      'title' => 'Fully Paid'],
                ];
                foreach ($cards as $card):
                ?>
                <div class="bg-white border-t-[3px] border-t-[#ce1126] rounded-xl shadow-[0_1px_3px_rgba(0,0,0,.06)] py-1 px-4 text-center transition-shadow duration-150 hover:shadow-[0_4px_12px_rgba(0,0,0,.1)] flex flex-col items-center gap-0.5">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.07em] text-slate-500"><?= $card['title'] ?></span>
                    <span id="<?= $card['id'] ?>" class="text-[28px] font-extrabold text-slate-900 tracking-[-0.03em] leading-[1.1] tabular-nums">0</span>
                    <?php if ($card['id'] === 'statUnits'): ?>
                    <span id="statUnitsCutoffLabel" class="text-[10px] text-slate-400 font-mono mt-0.5">—</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 border-t-[3px] border-t-[#ce1126] shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden mb-1">

                <div name="monthly-div" class="px-5 w-full pt-4 shrink-0 min-h-0">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-bold text-slate-900 tracking-widest uppercase">
                        Monthly Report
                        </h3>
                    </div>
            
                    <div class="mt-4 w-full">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Collection Progress</span>
                            <span id="valProgressTxt" class="text-[13px] font-bold text-slate-900">0%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-5">
                            <div id="barPaid" class="h-full bg-gradient-to-r from-[#ce2233] to-[#ce2216] rounded-full transition-[width] duration-1000 ease-out" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="px-5 pt-3 pb-3">
                    <div class="grid grid-cols-2 gap-0">

                        <div class="p-0 border-r border-slate-200 pr-6">
                            <span class="text-[10px] font-bold tracking-[0.1em] uppercase border-b-2 border-slate-200 pb-2 block text-slate-500">Gross</span>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0 pt-1">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Principal</span>
                                <span class="text-[13px] font-semibold text-slate-900 tabular-nums" id="valExpectedPrincipal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Interest</span>
                                <span class="text-[13px] font-semibold text-slate-900 tabular-nums" id="valExpectedInterest">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.06em]">Total</span>
                                <span class="text-[15px] font-bold text-slate-900 tracking-[-0.02em] tabular-nums" id="valExpectedTotal">₱0.00</span>
                            </div>
                        </div>

                        <div class="p-0 pl-6">
                            <span class="text-[10px] font-bold tracking-[0.1em] uppercase border-b-2 border-slate-200 pb-2 block text-slate-500">Payment</span>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0 pt-1">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Principal</span>
                                <span class="text-[13px] font-bold text-slate-900 tabular-nums" id="valCollectedPrincipal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Interest</span>
                                <span class="text-[13px] font-bold text-slate-900 tabular-nums" id="valCollectedInterest">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.06em]">Total</span>
                                <span class="text-[15px] font-extrabold text-slate-900 tracking-[-0.02em] tabular-nums" id="valCollectedTotal">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 border-t-[3px] border-t-[#ce2216] shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden mb-4">
                <div class="px-5 pb-3 pt-5 shrink-0">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-bold text-slate-900 tracking-widest uppercase">
                       Running Outstanding Balance
                        </h3>
                    </div>
                    <div class="mt-1 pt-2 shrink-0">
                        <div class="grid grid-cols-3 gap-0">
                            <div class="flex flex-col gap-1 py-2 px-3 border-r border-slate-200 rounded-l-lg">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.06em]">Principal Balance</span>
                                <span class="text-[15px] font-bold text-slate-800 tracking-[-0.02em] tabular-nums" id="valOutstandingPrincipal">₱0.00</span>
                            </div>
                            <div class="flex flex-col gap-1 py-2 px-3 border-r border-slate-200">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.06em]">Interest Balance</span>
                                <span class="text-[15px] font-bold text-slate-800 tracking-[-0.02em] tabular-nums" id="valOutstandingInterest">₱0.00</span>
                            </div>
                            <div class="flex flex-col gap-1 py-2 px-3 rounded-r-lg">
                                <span class="text-[10px] font-bold text-slate-700 uppercase tracking-[0.06em]">Total Balance</span>
                                <span class="text-[17px] font-extrabold text-slate-900 tracking-[-0.02em] tabular-nums" id="valNetOutstanding">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><?php if ($isAdminOrReviewer): ?>

        <div class="flex flex-col lg:w-[360px] xl:w-[400px] shrink-0 lg:h-[478px] h-[360px] min-h-0">
            <div class="flex flex-col flex-1 min-h-0 bg-white border-t-[3px] border-t-[#dc2626] border border-slate-200 border-b border-b-slate-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">

                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <h3 class="text-[13px] font-bold text-slate-900 flex items-center gap-2 tracking-wide uppercase">
                        <svg class="w-4 h-4 text-[#dc2626] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        New Loans
                    </h3>
                    <span id="notifBadge" class="bg-[#dc2626] text-white text-[9px] font-bold px-2 py-0.5 rounded-full hidden tracking-wider">0 NEW</span>
                </div>

                <div class="flex border-b border-slate-100 shrink-0">
                    <button id="tabBtnUnread" onclick="switchNotifTab('unread')"
                        class="flex-1 py-2.5 text-[11px] font-bold text-[#dc2626] border-b-2 border-[#dc2626] tracking-wider uppercase transition-colors">
                        Unread
                    </button>
                    <button id="tabBtnRead" onclick="switchNotifTab('read')"
                        class="flex-1 py-2.5 text-[11px] font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-700 tracking-wider uppercase transition-colors">
                        Read
                    </button>
                </div>

                <div class="flex-1 min-h-0 overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div id="notifUnreadList" class="space-y-2 p-3 block">
                        <p class="text-xs text-slate-400 italic text-center py-8">Loading...</p>
                    </div>
                    <div id="notifReadList" class="space-y-2 p-3 hidden"></div>
                </div>
            </div>
        </div>

        <div id="notifLoanModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-[96vw] max-w-[1500px] overflow-hidden transform transition-all scale-95 opacity-0 duration-200 flex flex-col max-h-[92vh]" id="notifLoanModalContent">

                <div class="px-5 py-3 flex justify-between items-center bg-slate-50 border-b border-slate-200">
                    <h3 class="font-bold text-slate-900 uppercase tracking-widest text-[12px]">Loan Details</h3>
                    <button onclick="closeNotifModal()" class="text-slate-400 hover:text-red-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto flex-1">
                    <div class="flex items-start gap-5 w-full">

                        <div class="flex flex-col gap-0 min-w-[301px] bg-white shadow-md rounded-md pb-1 shrink-0">
                            <div class="bg-[#ce2216] text-white rounded-t-md py-0.5 px-3 relative">
                                <h2 class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[13px] text-white uppercase font-bold tracking-widest whitespace-nowrap">Borrower's Information</h2>
                                <div class="h-8"></div>
                            </div>
                            <div class="px-3 pt-2 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Borrower's Name:</span>
                                <h2 class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-name">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Employee ID:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-id">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Reference Number:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-ref">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">System Loan Number:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-pn">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Region:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-region">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Branch:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-branch">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Contact Number:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-contact">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Date Released:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-pndate">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2">
                                <span class="text-[12px] text-slate-400 font-mono uppercase w-36">Maturity Date:</span>
                                <h2 class="text-[13px] text-slate-800 font-mono uppercase whitespace-nowrap" id="modal-ledger-maturity">--</h2>
                            </div>
                        </div>

                        <div class="flex-grow min-w-0">
                            <div class="mb-0 flex items-center justify-end gap-2" name="btn"></div>

                            <div class="mt-0 bg-white rounded-md shadow-md overflow-hidden border border-slate-200">
                                <div class="bg-[#ce2216] text-white rounded-t-md py-0.5 px-3 relative">
                                    <h2 class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[13px] text-white uppercase font-bold tracking-widest whitespace-nowrap">Motorcycle Loan Report</h2>
                                    <div class="h-8"></div>
                                </div>

                                <div class="px-3 py-2 border-b border-slate-100">
                                    <div class="text-[12px] font-bold uppercase text-slate-900 pt-0">Loan Details</div>
                                    <div class="grid grid-cols-3 gap-6 pt-1">
                                        <div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Loan Amount</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-principal">₱ 0.00</div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Interest Rate</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-rate">0.00%</div>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Semi-monthly Amortization</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-amort">₱ 0.00</div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Monthly Amortization</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-monthly-amort">₱ 0.00</div>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Term(s)</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-terms">--</div>
                                            </div>
                                            <div class="flex items-center justify-between" id="security-deposit-wrapper">
                                                <div class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Security Deposit</div>
                                                <div class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-security-deposit">₱ 2,500.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-1">
                                    <div class="p-3 pr-0">
                                        <div class="text-[12px] font-bold uppercase text-slate-900">Gross</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Gross Principal:</span>
                                                <span class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-gross-principal">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Gross Interest:</span>
                                                <span class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-gross-interest">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-900 font-mono uppercase whitespace-nowrap">Total Gross:</span>
                                                <span class="text-[13px] text-slate-900 font-mono whitespace-nowrap" id="modal-ledger-gross-total">₱ 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3 border-l border-slate-100 border-r border-slate-100">
                                        <div class="text-[12px] font-bold uppercase text-slate-900">Payment</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Principal Paid:</span>
                                                <span class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-principal-paid">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Interest Paid:</span>
                                                <span class="text-[13px] text-slate-800 font-mono whitespace-nowrap" id="modal-ledger-interest-paid">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-900 font-mono uppercase whitespace-nowrap">Total Payment:</span>
                                                <span class="text-[13px] text-slate-900 font-mono whitespace-nowrap" id="modal-ledger-total-payment">₱ 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <div class="text-[12px] text-slate-900 font-bold uppercase whitespace-nowrap">Balance</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Principal Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-mono whitespace-nowrap" id="modal-ledger-principal-balance">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 font-mono uppercase whitespace-nowrap">Interest Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-mono whitespace-nowrap" id="modal-ledger-interest-balance">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-rose-600 font-mono uppercase whitespace-nowrap">Outstanding Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-mono whitespace-nowrap" id="modal-ledger-total-balance">₱ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50">
                    <p class="text-[11px] text-slate-500 font-semibold tracking-wide">
                        Uploaded by: <span id="notif-uploaded-by" class="font-mono text-[14px] uppercase text-slate-500 tracking-normal">-</span>
                    </p>
                    <button onclick="closeNotifModal()"
                        class="px-5 py-2 bg-[#ce1126] hover:bg-[#bd0217] text-white rounded-lg text-[11px] font-bold tracking-widest uppercase transition-colors shadow-sm">
                        Close
                    </button>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/public/assets/js/dashboard.js"></script>