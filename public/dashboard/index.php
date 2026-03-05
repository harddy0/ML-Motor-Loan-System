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
</style>

<div class="h-full flex flex-col p-2 -mt-4" style="height:calc(90vh - 4rem); overflow:hidden;">
    <div class="flex flex-col xl:flex-row justify-between items-end mb-6 gap-6 shrink-0">
        <div>
            <h1 class="text-2xl text-slate-800">
                Dashboard
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-slate-500">
                    as of <?= date('F d, Y') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="w-full flex flex-col lg:flex-row gap-6 no-scrollbar flex-1 min-h-0 items-stretch overflow-hidden">
        
        <!-- Main column — flex-1 always; fills full width when right column is absent (USER type) -->
        <div class="flex-1 flex flex-col gap-6 pb-2">
            
            <div name="3-cards" class="grid grid-cols-1 md:grid-cols-3 gap-6 shrink-0">
                <?php 
                $cards = [
                    ['id' => 'statUnits',     'title' => 'Payroll Deduction'],
                    ['id' => 'statBorrowers', 'title' => 'Active Borrowers'],
                    ['id' => 'statPaid',      'title' => 'Fully Paid']
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

            <div name="big-card" class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm border-t-2 border-t-[#ce1126] group hover:shadow-md transition-all w-full flex flex-col flex-1 overflow-hidden min-h-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-slate-800 text-[14px] mb-1 tracking-wide">
                            Running Accounts Receivable
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-500 block text-sm mb-1">Total Loan Amount</p>
                        <span id="valTotalLoaned" class="text-1xl font-bold text-slate-800 tracking-tight">₱0.00</span>
                    </div>
                </div>

                <div class="space-y-4 py-6">
                    <div class="flex justify-between items-end px-1">
                        <span class="text-slate-500 block mb-2 text-sm">Collection Progress</span>
                        <span id="valProgressTxt" class="text-[#ce1126] font-bold">0% Collected</span>
                    </div>
                    
                    <div class="relative w-full h-10 bg-slate-100 rounded-full overflow-hidden border border-slate-200 shadow-inner flex items-center">
                        <div id="barPaid" 
                            class="h-full bg-gradient-to-r from-[#e11d48] to-[#be123c] flex items-center justify-center transition-all duration-1000 ease-out relative" 
                            style="width: 0%">
                            <div class="absolute inset-0 bg-white/10 w-full h-1/2 top-0"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100 justify-center">
                    <div class="text-center">
                        <span class="text-slate-500 block mb-2 text-sm">Payments (This month)</span>
                        <span id="valTotalCollected" class="text-1xl font-bold text-slate-800 tracking-tight">₱0.00</span>
                    </div>
                    <div class="text-center">
                        <span class="text-slate-500 block mb-2 text-sm whitespace-nowrap">Total Outstanding Balance</span>
                        <span id="valNetOutstanding" class="whitespace-nowrap text-1xl font-bold text-slate-800 tracking-tight">₱0.00</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right column: notifications panel — ADMIN and REVIEWER only, not rendered for USER -->
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

        <!-- Loan detail modal (ADMIN / REVIEWER only) -->
        <div id="notifLoanModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-95 opacity-0 duration-200" id="notifLoanModalContent">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800 uppercase tracking-wider text-sm flex items-center gap-2">
                        Loan Review Details
                    </h3>
                    <button onclick="closeNotifModal()" class="text-slate-400 font-bold hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <p class="text-[13px] text-slate-700 tracking-widest mb-1">Borrower's Name</p>
                        <p class="font-black text-xl text-slate-800" id="nlm-borrower">-</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-y-5 gap-x-4 px-2">
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Uploaded By</p>
                            <p class="font-bold text-blue-600 bg-blue-50 uppercase inline-block px-2 py-0.5 rounded text-[13px]" id="nlm-uploader">-</p>
                        </div>
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Date Released</p>
                            <p class="font-bold text-slate-700" id="nlm-date">-</p>
                        </div>
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Promisory Note Number</p>
                            <p class="font-bold text-slate-700" id="nlm-pn">-</p>
                        </div>
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Term(s)</p>
                            <p class="font-bold text-slate-700" id="nlm-terms">-</p>
                        </div>
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Loan Amount</p>
                            <p class="font-black text-[#dc2626] text-lg" id="nlm-amount">-</p>
                        </div>
                        <div>
                            <p class="text-[13px] text-slate-700 tracking-widest mb-1">Semi-Monthly Deduction</p>
                            <p class="font-bold text-slate-700 text-lg" id="nlm-deduction">-</p>
                        </div>
                    </div>
                    <div class="mt-2 pt-5 border-t border-slate-100 flex justify-end">
                        <button onclick="closeNotifModal()" class="px-7 py-2 bg-[#ce1126] text-white rounded-lg text-xs font-bold hover:bg-[#bd0217] transition-colors tracking-widest">
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