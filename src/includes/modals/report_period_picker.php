<div id="reportPeriodModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[90vh] overflow-hidden">
        
        <div class="px-8 py-6 text-center relative border-b border-slate-100 shrink-0">
            <h2 class="text-[14px] font-black text-slate-800 uppercase tracking-[0.2em]">
                Select <span class="text-[#e11d48]">Report Period</span>
            </h2>
            <button onclick="closeModal('reportPeriodModal')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-[#e11d48] transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 space-y-6 overflow-y-auto">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-[12px] font-black text-slate-400 uppercase tracking-widest ml-1">Reporting Year</label>
                    <select id="picker-year" class="w-full border-2 border-slate-100 bg-slate-50 px-4 py-2.5 text-[14px] font-black text-slate-800 outline-none rounded-xl cursor-pointer focus:border-green-500 transition-all">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--): ?>
                            <option value="<?= $i ?>" <?= ($i == $currentYear) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[12px] font-black text-slate-400 uppercase tracking-widest ml-1">Month</label>
                    <select id="picker-month" class="w-full border-2 border-slate-100 bg-slate-50 px-4 py-2.5 text-[14px] font-black text-slate-800 outline-none rounded-xl cursor-pointer focus:border-green-500 transition-all">
                        <?php 
                        $currentMonth = date('n');
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach($months as $idx => $m): ?>
                            <option value="<?= $idx + 1 ?>" <?= (($idx + 1) == $currentMonth) ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="space-y-3">
                <label class="text-[12px] font-black text-slate-400 uppercase tracking-widest ml-1">Report Coverage</label>
                <div id="coverage-container" class="space-y-2">
                    <?php 
                        $currentDay = (int)date('d');
                        $isFirstHalf = ($currentDay <= 15);
                    ?>
                    <label class="group cursor-pointer block">
                        <input type="radio" name="picker-period" value="0" class="peer sr-only">
                        <div class="border-2 border-slate-100 bg-white rounded-xl p-4 flex items-center justify-between peer-checked:border-green-500 peer-checked:bg-green-50/30 transition-all">
                            <div>
                                <span class="block text-[14px] font-black text-slate-800 uppercase">Whole Month</span>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase">Consolidated View</span>
                            </div>
                            <div class="h-5 w-5 rounded-full border-2 border-slate-200 flex items-center justify-center group-hover:border-green-500">
                                <div class="h-2.5 w-2.5 rounded-full bg-green-500 scale-0 peer-checked:scale-100 transition-transform"></div>
                            </div>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="1" class="peer sr-only" <?= $isFirstHalf ? 'checked' : '' ?>>
                            <div class="border-2 border-slate-100 bg-white peer-checked:border-green-500 peer-checked:bg-green-50/30 rounded-xl p-3 text-center transition-all">
                                <span class="block text-[14px] font-black text-slate-800 uppercase">1st Half</span>
                                <span class="block text-[12px] font-bold text-slate-400 uppercase">Day 1 - 15</span>
                            </div>
                        </label>
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="2" class="peer sr-only" <?= !$isFirstHalf ? 'checked' : '' ?>>
                            <div class="border-2 border-slate-100 bg-white peer-checked:border-green-500 peer-checked:bg-green-50/30 rounded-xl p-3 text-center transition-all">
                                <span class="block text-[14px] font-black text-slate-800 uppercase">2nd Half</span>
                                <span class="block text-[12px] font-bold text-slate-400 uppercase">Day 16 - End</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="space-y-3 pt-4 border-t border-slate-100">
                <label class="text-[12px] font-black text-slate-400 uppercase tracking-widest ml-1">Account Status Filter</label>
                <select id="picker-status" class="w-full border-2 border-slate-100 bg-slate-50 px-4 py-3 text-[13px] font-black text-slate-600 uppercase outline-none rounded-xl focus:border-[#e11d48] transition-all">
                    <option value="ONGOING">Show Ongoing Accounts</option>
                    <option value="FULLY_PAID">Show Fully Paid</option>
                    <option value="ALL">Show All Accounts</option>
                </select>
            </div>

            <div class="flex justify-end">
                <button onclick="resetReportFilters()" class="text-[10px] font-black text-slate-400 hover:text-[#e11d48] transition-colors uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset to Default
                </button>
            </div>
        </div>

        <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex flex-col gap-2 shrink-0">
            <button id="generate-btn" onclick="applyReportPeriod()" class="w-full py-4 bg-[#e11d48] text-white rounded-full text-[13px] font-black uppercase tracking-[0.15em] shadow-lg shadow-red-500/20 hover:brightness-110 active:scale-[0.98] transition-all">
                Generate Report
            </button>
            <button onclick="closeModal('reportPeriodModal')" class="w-full py-2 text-slate-400 text-[11px] font-black uppercase tracking-widest hover:text-slate-600 transition-colors">
                Discard Changes
            </button>
        </div>
    </div>
</div>