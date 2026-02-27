<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 
?> 

<style>
    /* Absolute removal of scrollbars for the entire dashboard view */
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

<div class="h-full flex flex-col p-2">
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

    <div class="w-full flex flex-col lg:flex-row gap-6 no-scrollbar flex-1 min-h-0">
        
        <div class="flex-1 flex">
            <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm border-t-2 border-t-[#ce1126] group hover:shadow-lg transition-all w-full flex flex-col justify-between">
                
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-black">
                            Running Accounts Receivable
                        </h3>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-500 mb-1">Total Loan Amount</p>
                        <span id="valTotalLoaned" class="text-xl text-black">₱0.00</span>
                    </div>
                </div>

                <div class="space-y-4 py-4">
                    <div class="flex justify-between items-end px-1">
                        <span class="text-slate-500">Collection Progress</span>
                        <span id="valProgressTxt" class="text-[#e11d48]">0% Collected</span>
                    </div>
                    
                    <div class="relative w-full h-10 bg-slate-100 rounded-full overflow-hidden border border-slate-200 shadow-inner flex items-center">
                        <div id="barPaid" 
                            class="h-full bg-gradient-to-r from-[#e11d48] to-[#be123c] flex items-center justify-center transition-all duration-1000 ease-out relative" 
                            style="width: 0%">
                            <div class="absolute inset-0 bg-white/10 w-full h-1/2 top-0"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-100">
                    <div class="text-center">
                        <span class="text-slate-500 block mb-2">Total Payments</span>
                        <span id="valTotalCollected" class="text-xl text-black">₱0.00</span>
                    </div>
                    <div class="text-center border-x border-slate-100 px-2">
                         <span class="text-slate-500 block mb-2">Total Interest Income</span>
                        <span id="valTotalIncome" class="text-xl text-black">₱0.00</span>
                    </div>
                    <div class="text-center">
                         <span class="text-slate-500 block mb-2">Total Outstanding Balance</span>
                        <span id="valNetOutstanding" class="text-xl text-black">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:w-50 shrink-0">
            <?php 
            $cards = [
                ['id' => 'statUnits', 'title' => 'Payroll Deduction'],
                ['id' => 'statBorrowers', 'title' => 'Active Borrowers'],
                ['id' => 'statPaid', 'title' => 'Fully Paid']
            ];
            foreach ($cards as $card): 
            ?>
            <div class="flex-1 bg-white border-t-2 border-t-[#ce1126] rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all flex flex-col items-center justify-center">
                <h3 class="text-black mb-1 relative z-10"><?= $card['title'] ?></h3>
                <div class="relative z-10">
                    <span id="<?= $card['id'] ?>" class="text-5xl font-bold text-black">0</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div> 

    </div>
</div>

<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/public/assets/js/dashboard.js"></script>