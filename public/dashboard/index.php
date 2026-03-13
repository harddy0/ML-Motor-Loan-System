<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 
$isAdminOrReviewer = in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER']);
?> 

<style>
    html, body {
        overflow: hidden !important;
        height: 100%;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* ── Two-column breakdown panel ─────────────────────────── */
    .bd-panel {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }
    .bd-col { padding: 0 4px; }
    .bd-col-left  { border-right: 1px solid #e2e8f0; padding-right: 20px; }
    .bd-col-right { padding-left: 20px; }

    /* Column headings — both muted, differentiated only by weight */
    .bd-col-heading {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding-bottom: 7px;
        margin-bottom: 6px;
        border-bottom: 2px solid #cbd5e1;
        display: block;
        color: #64748b;
    }
    /* Collected heading gets a darker underline so it reads as "active" vs "pending" */
    .bd-col-right .bd-col-heading {
        border-bottom-color: #334155;
        color: #334155;
    }

    .bd-line {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding: 5px 0;
    }
    .bd-line-label {
        font-size: 11px;
        font-family: ui-monospace, monospace;
        color: #94a3b8;
        letter-spacing: 0.03em;
    }
    /* Expected values — lighter, clearly "not yet" */
    .bd-col-left  .bd-line-val {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        letter-spacing: -0.01em;
    }
    /* Collected values — full dark, this is real money */
    .bd-col-right .bd-line-val {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
    }

    .bd-divider { border-top: 1px solid #e2e8f0; margin: 7px 0; }

    .bd-total-label {
        font-size: 11px;
        font-family: ui-monospace, monospace;
        font-weight: 700;
        color: #475569;
    }
    /* Expected total — still muted */
    .bd-col-left  .bd-total-val {
        font-size: 14px;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: -0.02em;
    }
    /* Collected total — heaviest weight, near-black, this is the number that matters */
    .bd-col-right .bd-total-val {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    /* Outstanding — slate, no red, no green */
    .bd-outstanding-section {
        border-top: 2px solid #f1f5f9;
        margin-top: 14px;
        padding-top: 12px;
    }
    .bd-outstanding-heading {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
        padding-bottom: 6px;
        margin-bottom: 10px;
        display: block;
        border-bottom: 2px solid #e2e8f0;
    }
    .bd-outstanding-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0 12px;
    }
    .bd-out-block {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .bd-out-label {
        font-size: 10px;
        font-family: ui-monospace, monospace;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    /* Principal + Interest — medium slate */
    .bd-out-val {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        letter-spacing: -0.02em;
    }
    /* Total outstanding — largest, darkest, most prominent */
    .bd-out-block:last-child .bd-out-label { color: #475569; font-weight: 700; }
    .bd-out-block:last-child .bd-out-val   { font-size: 15px; font-weight: 800; color: #0f172a; }
</style>

<div class="h-full flex flex-col p-2 -mt-4" style="height:calc(90vh - 4rem); overflow:hidden;">
    <div class="flex flex-col xl:flex-row justify-between items-end mb-3 gap-4 shrink-0">
        <div>
            <h1 class="text-2xl text-slate-800">
                Dashboard
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-slate-500 font-mono text-sm">
                    as of <?= date('F d, Y') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="w-full flex flex-col lg:flex-row gap-6 no-scrollbar flex-1 min-h-0 items-stretch overflow-hidden">
        
        <!-- Main column -->
        <div class="flex-1 flex flex-col gap-3 pb-2">
            
            <!-- 3 top cards -->
            <div name="3-cards" class="grid grid-cols-1 md:grid-cols-3 gap-6 shrink-0">
                <?php 
                $cards = [
                    ['id' => 'statUnits',     'title' => 'Due This Month'],
                    ['id' => 'statBorrowers', 'title' => 'Active Borrowers'],
                    ['id' => 'statPaid',      'title' => 'Fully Paid'],
                ];
                foreach ($cards as $card): 
                ?>
                <div class="bg-white border-t-2 border-red-500 rounded-xl shadow-sm p-3 relative overflow-hidden group hover:shadow-md transition-all text-center">
                    <h3 class="text-slate-800 text-[14px] mb-1 tracking-wide"><?= $card['title'] ?></h3>
                    <div class="relative z-10">
                        <span id="<?= $card['id'] ?>" class="text-1xl font-bold text-slate-800 tracking-tight">0</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Big card: Running AR + Monthly Breakdown -->
            <div name="big-card" class="bg-white px-6 pt-5 pb-5 rounded-2xl border border-slate-100 shadow-sm border-t-2 border-t-[#ce1126] hover:shadow-md transition-all w-full flex flex-col flex-1 min-h-0" style="overflow: visible;">

                <!-- Card header -->
                <div class="flex justify-between items-start shrink-0">
                    <div>
                        <h3 class="text-slate-700 text-[13px] font-semibold mb-0 tracking-wide uppercase">
                            Running Accounts Receivable
                        </h3>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5"><?= date('F Y') ?> — Monthly Collection</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="space-y-1.5 py-3 shrink-0">
                    <div class="flex justify-between items-center px-0.5">
                        <span class="text-[11px] text-slate-400 font-mono uppercase tracking-wide">Collection Progress</span>
                        <span id="valProgressTxt" class="text-[12px] text-slate-700 font-bold">0%</span>
                    </div>
                    <div class="relative w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                        <div id="barPaid"
                            class="h-full bg-slate-700 rounded-full transition-all duration-1000 ease-out"
                            style="width: 0%">
                        </div>
                    </div>
                </div>

                <!-- Two-column breakdown: Expected (left) | Collected (right) -->
                <div class="flex-1 overflow-auto">
                    <div class="bd-panel">

                        <!-- LEFT: Expected -->
                        <div class="bd-col bd-col-left">
                            <span class="bd-col-heading">Expected</span>
                            <div class="bd-line">
                                <span class="bd-line-label">Principal</span>
                                <span class="bd-line-val" id="valExpectedPrincipal">₱0.00</span>
                            </div>
                            <div class="bd-line">
                                <span class="bd-line-label">Interest</span>
                                <span class="bd-line-val" id="valExpectedInterest">₱0.00</span>
                            </div>
                            <div class="bd-divider"></div>
                            <div class="bd-line">
                                <span class="bd-total-label">Total</span>
                                <span class="bd-total-val" id="valExpectedTotal">₱0.00</span>
                            </div>
                        </div>

                        <!-- RIGHT: Collected -->
                        <div class="bd-col bd-col-right">
                            <span class="bd-col-heading">Collected</span>
                            <div class="bd-line">
                                <span class="bd-line-label">Principal</span>
                                <span class="bd-line-val" id="valCollectedPrincipal">₱0.00</span>
                            </div>
                            <div class="bd-line">
                                <span class="bd-line-label">Interest</span>
                                <span class="bd-line-val" id="valCollectedInterest">₱0.00</span>
                            </div>
                            <div class="bd-divider"></div>
                            <div class="bd-line">
                                <span class="bd-total-label">Total</span>
                                <span class="bd-total-val" id="valCollectedTotal">₱0.00</span>
                            </div>
                        </div>

                    </div>

                    <!-- OUTSTANDING: full-width below both columns -->
                    <div class="bd-outstanding-section">
                        <span class="bd-outstanding-heading">Outstanding Balance</span>
                        <div class="bd-outstanding-grid">
                            <div class="bd-out-block">
                                <span class="bd-out-label">Principal</span>
                                <span class="bd-out-val" id="valOutstandingPrincipal">₱0.00</span>
                            </div>
                            <div class="bd-out-block">
                                <span class="bd-out-label">Interest</span>
                                <span class="bd-out-val" id="valOutstandingInterest">₱0.00</span>
                            </div>
                            <div class="bd-out-block">
                                <span class="bd-out-label">Total</span>
                                <span class="bd-out-val" id="valNetOutstanding">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div><!-- /big-card -->

        </div><!-- /main column -->

        <!-- Right column: notifications panel — ADMIN and REVIEWER only -->
        <?php if ($isAdminOrReviewer): ?>
        <div name="new-card" class="flex flex-col lg:w-[380px] xl:w-[420px] shrink-0 min-h-0 pb-2">

            <div class="flex-1 bg-white border-t-2 border-t-[#dc2626] rounded-xl shadow-sm p-0 flex flex-col min-h-0 max-h-full overflow-hidden hover:shadow-md">
                
                <div class="p-4 border-b border-slate-100 flex justify-between items-center shrink-0 bg-white">
                    <h3 class="text-slate-800 flex items-center gap-2 text-[14px] tracking-wider">
                        <svg class="w-4 h-4 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        New Loans
                    </h3>
                    <span id="notifBadge" class="bg-[#dc2626] text-white text-[9px] font-bold px-2 py-0.5 rounded-full hidden">0 NEW</span>
                </div>
                
                <div class="flex border-b border-slate-100 bg-white shrink-0">
                    <button id="tabBtnUnread" onclick="switchNotifTab('unread')" class="flex-1 py-3 text-xs font-bold text-[#dc2626] border-b-2 border-[#dc2626] transition-colors">Unread</button>
                    <button id="tabBtnRead"   onclick="switchNotifTab('read')"   class="flex-1 py-3 text-xs font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors">Read</button>
                </div>

                <div class="flex-1 overflow-y-auto min-h-0">
                    <div id="notifUnreadList" class="space-y-2 p-3 block">
                        <p class="text-xs text-slate-400 italic text-center py-6">Loading...</p>
                    </div>
                    <div id="notifReadList" class="space-y-2 p-3 hidden"></div>
                </div>
                
            </div>

        </div>

        <!-- Loan detail modal (ADMIN / REVIEWER only) — unchanged -->
        <div id="notifLoanModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-3 lg:p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-[96vw] max-w-[1500px] overflow-hidden transform transition-all scale-95 opacity-0 duration-200 flex flex-col max-h-[92vh]" id="notifLoanModalContent">
                <div class="px-5 py-3 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 uppercase tracking-wider text-sm flex items-center gap-2">
                        Details
                    </h3>
                    <button onclick="closeNotifModal()" class="text-slate-400 font-bold hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-4 overflow-y-auto flex-1">
                    <div class="flex items-start justify-between p-2 gap-2 w-full">
                        <div class="flex flex-col border border-slate-200 gap-0 min-w-[301px] bg-white shadow-md rounded-md ml-1 -pr-1 pb-1 ">
                            <div class="px-3 pt-2 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Borrower's Name:</span>
                                <h2 class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-name">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Employee ID:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-id">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Reference Number:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-ref">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Region:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap " id="modal-ledger-region">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Branch:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-branch">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Contact Number:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-contact">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">System Loan Number :</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-pn">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Date Released:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-pndate">--</h2>
                            </div>
                            <div class="px-3 flex items-center gap-2 space-y-1">
                                <span class="text-[12px] text-slate-400 uppercase w-36">Maturity Date:</span>
                                <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-maturity">--</h2>
                            </div>
                        </div>

                        <div name="bigcard" class="flex-grow -mt-2">
                            <div class="mb-0">
                                <div class="flex items-center justify-between mb-0">
                                    <div class="flex-1"></div>
                                    <div name="btn" class="flex items-center gap-2"></div>
                                </div>
                            </div>
                                <div name="loanInfo" class="mt-2">
                                    <div class="grid grid-cols-4 grid-rows-1 bg-white rounded-md shadow-md items-start">
                                        <div class="col-span-4 row-span-1 bg-[#ce2216] text-white rounded-t-md py-3">
                                            <div class="px-3 py-0 relative">
                                                <div class="flex items-center justify-between mt-2 pb-0">
                                                    <h2 class="absolute left-1/2 top-1/2 transform -pt-1 pb-1 -translate-x-1/2 -translate-y-1/2 text-[18px] text-white uppercase font-bold tracking-widest ">MOTORCYCLE LOAN REPORT</h2>
                                                </div>
                                            </div>
                                        </div>
                                    
                                        <div class="col-span-4 row-span-1">
                                            <div name="row1" class="px-3 py-0 border-b border-slate-100">
                                                <div class="text-[12px] font-bold justify-between pt-3 uppercase text-slate-900 flex w-full">Loan Details
                                                </div>
                                                <div class="grid grid-cols-3 gap-6 pb-2 items-start space-y-1">
                                                    <div class="col-span-1">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Loan Amount</div>
                                                            <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-principal">₱ 0.00</div>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Rate</div>
                                                            <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-rate">0.00%</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-span-1">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Semi-monthly Amortization</div>
                                                            <div class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-amort">₱ 0.00</div>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Monthly Amortization</div>
                                                            <div class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-monthly-amort">₱ 0.00</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-span-1">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Term(s)</div>
                                                            <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-terms">--</div>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Security Deposit</div>
                                                            <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-security-deposit">₱ 2,500.00</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div name="row2" class="col-span-4 row-span-1 grid grid-cols-3 gap-1">
                                                <div name="col2" class="col-span-1">
                                                    <div class="p-3 pr-0">
                                                        <div class="text-[12px] font-bold uppercase text-slate-900">Gross</div>
                                                    <div class="space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Gross Principal:</span>
                                                            <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-gross-principal">₱ 0.00</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 justify-between">
                                                            <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Gross Interest:</span>
                                                            <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-gross-interest">₱ 0.00</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 justify-between">
                                                            <span class="text-[12px] text-slate-900 font-bold uppercase whitespace-nowrap">Total Gross:</span>
                                                            <span class="text-[13px] text-slate-900 font-bold whitespace-nowrap" id="modal-ledger-gross-total">₱ 0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>

                                                <div name="col3" class="col-span-1">
                                                    <div class="p-3">
                                                        <div class="text-[12px] font-bold uppercase text-slate-900">Payment</div>
                                                            <div class="space-y-1">
                                                                <div class="flex items-center gap-2 justify-between">
                                                                    <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Principal Paid:</span>
                                                                    <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-principal-paid">₱ 0.00</span>
                                                                </div>
                                                                <div class="flex items-center gap-2 justify-between">
                                                                    <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Paid:</span>
                                                                    <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-interest-paid">₱ 0.00</span>
                                                                </div>
                                                                <div class="flex items-center gap-2 justify-between">
                                                                    <span class="text-[12px] text-slate-900 font-bold uppercase whitespace-nowrap">Total Payment:</span>
                                                                    <span class="text-[13px] text-slate-900 font-bold whitespace-nowrap" id="modal-ledger-total-payment">₱ 0.00</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                </div>

                                                <div name="col4" class="col-span-1">
                                                    <div class="p-3">
                                                        <div class="text-[12px] font-bold uppercase text-slate-900">Balance</div>
                                                    <div class="space-y-1">
                                                        <div class="flex items-center gap-2 justify-between">
                                                            <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Principal Balance:</span>
                                                            <span class="text-[13px] text-rose-600 font-semibold whitespace-nowrap" id="modal-ledger-principal-balance">₱ 0.00</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 justify-between">
                                                            <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Balance:</span>
                                                            <span class="text-[13px] text-rose-600 font-semibold whitespace-nowrap" id="modal-ledger-interest-balance">₱ 0.00</span>
                                                        </div>
                                                        <div class="flex items-center gap-2 justify-between">
                                                            <span class="text-[12px] text-rose-600 font-bold uppercase whitespace-nowrap">Outstanding Balance:</span>
                                                            <span class="text-[13px] text-rose-600 font-bold whitespace-nowrap" id="modal-ledger-total-balance">₱ 0.00</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-1 flex justify-between">
                    <div class="flex w-full justify-between gap-4">
                        <div class="text-sm text-slate-600 pt-4">Uploaded By: <span id="notif-uploaded-by" class="font-mono text-sm uppercase">-</span></div>
                        <button onclick="closeNotifModal()" class="px-5 py-2 bg-[#ce1126] text-white rounded-lg text-xs font-bold hover:bg-[#bd0217] transition-colors tracking-widest">
                            Close
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/public/assets/js/dashboard.js"></script>