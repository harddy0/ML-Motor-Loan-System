 <?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 
$isAdminOrReviewer = in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER']);
?> 

<div class="h-[500px] flex flex-col">

    <div class="flex items-end justify-between mb-4 shrink-0">
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
                    ['id' => 'statUnits',     'title' => 'Due This Month'],
                    ['id' => 'statBorrowers', 'title' => 'Active Borrowers'],
                    ['id' => 'statPaid',      'title' => 'Fully Paid'],
                ];
                foreach ($cards as $card):
                ?>
                <div class="bg-white border-t-[3px] border-t-[#ce1126] rounded-xl shadow-[0_1px_3px_rgba(0,0,0,.06)] py-3.5 px-4 text-center transition-shadow duration-150 hover:shadow-[0_4px_12px_rgba(0,0,0,.1)] flex flex-col items-center gap-0.5">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.07em] text-slate-500"><?= $card['title'] ?></span>
                    <span id="<?= $card['id'] ?>" class="text-[28px] font-extrabold text-slate-900 tracking-[-0.03em] leading-[1.1] tabular-nums">0</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 border-t-[3px] border-t-[#ce1126] shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden mb-4">

                <div class="px-5 pt-4 pb-0 shrink-0">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-[13px] font-bold text-slate-900 tracking-widest uppercase">
                           Monthly Collection Overview
                        </h3>
                        <span class="text-[11px] text-slate-400 font-mono font-medium"><?= date('F Y') ?></span>
                    </div>
                    <p class="text-[13px] text-slate-500 mt-0.5 mb-3">Overview of monthly collection</p>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Collection Progress</span>
                            <span id="valProgressTxt" class="text-[13px] font-bold text-slate-900">0%</span>
                        </div>
                        <div class="bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div id="barPaid" class="h-full bg-gradient-to-r from-slate-700 to-slate-800 rounded-full transition-[width] duration-1000 ease-out" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="px-5 pb-0">
                    <div class="grid grid-cols-2 gap-0">

                        <div class="p-0 border-r border-slate-200 pr-6">
                            <span class="text-[10px] font-bold tracking-[0.1em] uppercase pb-2 mb-1 border-b-2 border-slate-200 block text-slate-500">Expected</span>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Principal</span>
                                <span class="text-[13px] font-semibold text-slate-700 tabular-nums" id="valExpectedPrincipal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Interest</span>
                                <span class="text-[13px] font-semibold text-slate-700 tabular-nums" id="valExpectedInterest">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1.5 mt-0.5 border-t-2 border-slate-200">
                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.06em]">Total</span>
                                <span class="text-[15px] font-bold text-slate-700 tracking-[-0.02em] tabular-nums" id="valExpectedTotal">₱0.00</span>
                            </div>
                        </div>

                        <div class="p-0 pl-6">
                            <span class="text-[10px] font-bold tracking-[0.1em] uppercase pb-2 mb-1 border-b-2 border-slate-700 block text-slate-900">Collected</span>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Principal</span>
                                <span class="text-[13px] font-bold text-slate-900 tabular-nums" id="valCollectedPrincipal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline py-1 border-b border-slate-100 last:border-b-0">
                                <span class="text-[12px] font-medium text-slate-500 tracking-[0.02em]">Interest</span>
                                <span class="text-[13px] font-bold text-slate-900 tabular-nums" id="valCollectedInterest">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1.5 mt-0.5 border-t-2 border-slate-200">
                                <span class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.06em]">Total</span>
                                <span class="text-[15px] font-extrabold text-slate-900 tracking-[-0.02em] tabular-nums" id="valCollectedTotal">₱0.00</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="px-5 pb-3 shrink-0">
                    <div class="border-t-2 border-slate-200 mt-1 pt-2 shrink-0">
                        <span class="text-[10px] font-bold tracking-[0.1em] uppercase text-slate-600 pb-2 mb-2.5 block border-b-2 border-slate-200">Outstanding Balance</span>
                        <div class="grid grid-cols-3 gap-0">
                            <div class="flex flex-col gap-1 py-2 px-3 bg-slate-50 border-r border-slate-200 rounded-l-lg">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.06em]">Principal</span>
                                <span class="text-[15px] font-bold text-slate-800 tracking-[-0.02em] tabular-nums" id="valOutstandingPrincipal">₱0.00</span>
                            </div>
                            <div class="flex flex-col gap-1 py-2 px-3 bg-slate-50 border-r border-slate-200">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.06em]">Interest</span>
                                <span class="text-[15px] font-bold text-slate-800 tracking-[-0.02em] tabular-nums" id="valOutstandingInterest">₱0.00</span>
                            </div>
                            <div class="flex flex-col gap-1 py-2 px-3 bg-slate-100 rounded-r-lg">
                                <span class="text-[10px] font-bold text-slate-700 uppercase tracking-[0.06em]">Total</span>
                                <span class="text-[17px] font-extrabold text-slate-900 tracking-[-0.02em] tabular-nums" id="valNetOutstanding">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div><?php if ($isAdminOrReviewer): ?>

        <div class="flex flex-col lg:w-[360px] xl:w-[400px] shrink-0 lg:h-[460px] h-[360px] min-h-0">
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

                        <div class="flex flex-col border border-slate-200 rounded-xl bg-slate-50 min-w-[280px] overflow-hidden shrink-0">
                            <div class="bg-[#ce1126] px-4 py-2.5">
                                <span class="text-[10px] font-bold text-red-100 uppercase tracking-widest">Borrower Info</span>
                            </div>
                            <?php
                            $infoRows = [
                                ['label' => "Borrower's Name",   'id' => 'modal-ledger-name'],
                                ['label' => 'Employee ID',       'id' => 'modal-ledger-id'],
                                ['label' => 'Reference Number',  'id' => 'modal-ledger-ref'],
                                ['label' => 'Region',            'id' => 'modal-ledger-region'],
                                ['label' => 'Branch',            'id' => 'modal-ledger-branch'],
                                ['label' => 'Contact Number',    'id' => 'modal-ledger-contact'],
                                ['label' => 'System Loan No.',   'id' => 'modal-ledger-pn'],
                                ['label' => 'Date Released',     'id' => 'modal-ledger-pndate'],
                                ['label' => 'Maturity Date',     'id' => 'modal-ledger-maturity'],
                            ];
                            foreach ($infoRows as $i => $row): ?>
                            <div class="px-4 py-2 flex items-center justify-between gap-3 <?= $i !== array_key_last($infoRows) ? 'border-b border-slate-200' : '' ?>">
                                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wide whitespace-nowrap shrink-0"><?= $row['label'] ?></span>
                                <span class="text-[12px] text-slate-900 font-bold uppercase text-right" id="<?= $row['id'] ?>">--</span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex-grow min-w-0">
                            <div name="btn" class="flex items-center justify-end gap-2 mb-3"></div>

                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="bg-[#ce2216] py-3 px-5 text-center">
                                    <h2 class="text-[15px] text-white font-bold uppercase tracking-widest">Motorcycle Loan Report</h2>
                                </div>

                                <div class="px-5 py-4 border-b border-slate-100">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-3">Loan Details</div>
                                    <div class="grid grid-cols-3 gap-6">

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Loan Amount</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-principal">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Interest Rate</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-rate">0.00%</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Semi-mo. Amort.</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-amort">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Monthly Amort.</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-monthly-amort">₱0.00</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Term(s)</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-terms">--</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Security Deposit</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-security-deposit">₱2,500.00</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="grid grid-cols-3 divide-x divide-slate-100">

                                    <div class="px-5 py-4">
                                        <div class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-3">Gross</div>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Principal</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-gross-principal">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Interest</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-gross-interest">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                                <span class="text-[11px] text-slate-700 font-bold uppercase">Total Gross</span>
                                                <span class="text-[13px] text-slate-900 font-bold" id="modal-ledger-gross-total">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-5 py-4">
                                        <div class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-3">Payment</div>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Principal Paid</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-principal-paid">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Interest Paid</span>
                                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-interest-paid">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                                <span class="text-[11px] text-slate-700 font-bold uppercase">Total Payment</span>
                                                <span class="text-[13px] text-slate-900 font-bold" id="modal-ledger-total-payment">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-5 py-4 bg-rose-50/40">
                                        <div class="text-[10px] font-bold uppercase text-rose-400 tracking-widest mb-3">Balance</div>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Principal</span>
                                                <span class="text-[12px] text-rose-600 font-bold" id="modal-ledger-principal-balance">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] text-slate-500 font-semibold uppercase">Interest</span>
                                                <span class="text-[12px] text-rose-600 font-bold" id="modal-ledger-interest-balance">₱0.00</span>
                                            </div>
                                            <div class="flex items-center justify-between pt-2 border-t border-rose-100">
                                                <span class="text-[11px] text-rose-600 font-bold uppercase">Outstanding</span>
                                                <span class="text-[13px] text-rose-600 font-bold" id="modal-ledger-total-balance">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50">
                    <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wide">
                        Uploaded by: <span id="notif-uploaded-by" class="font-mono text-slate-900 normal-case tracking-normal">-</span>
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