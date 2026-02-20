<div id="reportPeriodModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        
        <div class="px-8 py-6 text-center relative border-b border-slate-100">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-[0.2em]">
                Select <span class="text-[#e11d48]">Report Period</span>
            </h2>
            <button onclick="closeModal('reportPeriodModal')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-[#e11d48] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Reporting Year</label>
                    <select id="picker-year" onchange="validateSelect(this)" class="w-full border-2 border-green-500 bg-green-50/30 px-4 py-2.5 text-xs font-black text-slate-800 outline-none transition-all appearance-none rounded-xl cursor-pointer">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--): ?>
                            <option value="<?= $i ?>" <?= ($i == $currentYear) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Month</label>
                    <select id="picker-month" onchange="validateSelect(this)" class="w-full border-2 border-green-500 bg-green-50/30 px-4 py-2.5 text-xs font-black text-slate-800 outline-none transition-all appearance-none rounded-xl cursor-pointer">
                        <?php 
                        $currentMonth = date('n'); // 1 to 12
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach($months as $idx => $m): ?>
                            <option value="<?= $idx + 1 ?>" <?= (($idx + 1) == $currentMonth) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="space-y-3">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Report Coverage</label>
                <div id="coverage-container" class="space-y-2">
                    
                    <?php 
                        // Determine current half based on today's date
                        $currentDay = (int)date('d');
                        $isFirstHalf = ($currentDay <= 15);
                    ?>

                    <label class="group cursor-pointer block">
                        <input type="radio" name="picker-period" value="0" onchange="updateCoverageStyles()" class="peer sr-only">
                        <div class="coverage-box border-2 border-slate-100 bg-white rounded-xl p-4 flex items-center justify-between transition-all duration-200">
                            <div>
                                <span class="block text-[11px] font-black text-slate-800 uppercase tracking-tight">Whole Month</span>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase">Consolidated Monthly View</span>
                            </div>
                            <div class="radio-indicator h-5 w-5 rounded-full border-2 border-slate-200 flex items-center justify-center">
                                <div class="h-2.5 w-2.5 rounded-full bg-green-500 scale-0 transition-transform duration-200"></div>
                            </div>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="1" onchange="updateCoverageStyles()" class="peer sr-only" <?= $isFirstHalf ? 'checked' : '' ?>>
                            <div class="coverage-box border-2 <?= $isFirstHalf ? 'border-green-500 bg-green-50/30' : 'border-slate-100 bg-white' ?> rounded-xl p-3 text-center transition-all">
                                <span class="block text-[10px] font-black text-slate-800 uppercase">1st Half</span>
                                <span class="block text-[8px] font-bold text-slate-400">Day 1 - 15</span>
                            </div>
                        </label>
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="2" onchange="updateCoverageStyles()" class="peer sr-only" <?= !$isFirstHalf ? 'checked' : '' ?>>
                            <div class="coverage-box border-2 <?= !$isFirstHalf ? 'border-green-500 bg-green-50/30' : 'border-slate-100 bg-white' ?> rounded-xl p-3 text-center transition-all">
                                <span class="block text-[10px] font-black text-slate-800 uppercase">2nd Half</span>
                                <span class="block text-[8px] font-bold text-slate-400">Day 16 - End</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="space-y-3 pt-4 border-t border-slate-100">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Account Status Filter</label>
                <select id="picker-status" onchange="validateSelect(this)" class="w-full border-2 border-slate-100 bg-white px-4 py-3 text-[10px] font-black text-slate-600 uppercase tracking-wide outline-none transition-all appearance-none rounded-xl cursor-pointer hover:border-[#ff3b30] focus:border-[#ff3b30]">
                    <option value="ONGOING" <?= (!isset($selectedStatus) || $selectedStatus == 'ONGOING') ? 'selected' : '' ?>>Show Ongoing Accounts</option>
                    <option value="FULLY_PAID" <?= (isset($selectedStatus) && $selectedStatus == 'FULLY_PAID') ? 'selected' : '' ?>>Show Fully Paid (Completed in Period)</option>
                    <option value="ALL" <?= (isset($selectedStatus) && $selectedStatus == 'ALL') ? 'selected' : '' ?>>Show All (Ongoing + Paid in Period)</option>
                </select>
            </div>

        </div>

        <div class="px-8 py-6 bg-slate-50 flex flex-col gap-2">
            <button id="generate-btn" onclick="applyReportPeriod()" class="w-full py-3 bg-[#e11d48] text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-500/20 hover:brightness-110 active:scale-[0.98] transition-all">
                Generate Report
            </button>
            <button onclick="closeModal('reportPeriodModal')" class="w-full py-3 text-slate-400 text-[10px] font-black uppercase tracking-widest hover:text-slate-600 transition-colors">
                Discard Changes
            </button>
        </div>
    </div>
</div>