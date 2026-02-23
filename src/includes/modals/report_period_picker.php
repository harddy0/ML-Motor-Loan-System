<div id="reportPeriodModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[90vh]">
        
        <div class="px-8 py-5 flex justify-between items-center relative border-b border-slate-100 shrink-0 bg-slate-50 rounded-t-2xl">
            <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">
                Report <span class="text-[#e11d48]">Configuration</span>
            </h2>
            <button onclick="closeModal('reportPeriodModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar bg-white relative">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                
                <div class="space-y-5">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                        <div class="w-7 h-7 rounded-full bg-green-50 text-green-500 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Time Period</h3>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Reporting Year</label>
                            <select id="picker-year" onchange="validateSelect(this)" class="w-full border-2 border-green-500 bg-green-50/30 px-4 py-3 text-[11px] font-black text-slate-800 outline-none transition-all appearance-none rounded-xl cursor-pointer">
                                <?php 
                                $currentYear = date('Y');
                                for($i = $currentYear; $i >= $currentYear - 5; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($i == $currentYear) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Month</label>
                            <select id="picker-month" onchange="validateSelect(this)" class="w-full border-2 border-green-500 bg-green-50/30 px-4 py-3 text-[11px] font-black text-slate-800 outline-none transition-all appearance-none rounded-xl cursor-pointer">
                                <?php 
                                $currentMonth = date('n'); // 1 to 12
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                foreach($months as $idx => $m): ?>
                                    <option value="<?= $idx + 1 ?>" <?= (($idx + 1) == $currentMonth) ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Report Coverage</label>
                        <div id="coverage-container" class="space-y-2">
                            <?php 
                                $currentDay = (int)date('d');
                                $isFirstHalf = ($currentDay <= 15);
                            ?>
                            <label class="group cursor-pointer block">
                                <input type="radio" name="picker-period" value="0" onchange="updateCoverageStyles()" class="peer sr-only">
                                <div class="coverage-box border-2 border-slate-100 bg-white rounded-xl p-3 flex items-center justify-between transition-all duration-200 hover:border-slate-200">
                                    <div>
                                        <span class="block text-[11px] font-black text-slate-800 uppercase tracking-tight">Whole Month</span>
                                        <span class="block text-[9px] font-bold text-slate-400 uppercase mt-0.5">Consolidated View</span>
                                    </div>
                                    <div class="radio-indicator h-5 w-5 rounded-full border-2 border-slate-200 flex items-center justify-center">
                                        <div class="h-2.5 w-2.5 rounded-full bg-green-500 scale-0 transition-transform duration-200"></div>
                                    </div>
                                </div>
                            </label>

                            <div class="grid grid-cols-2 gap-2">
                                <label class="cursor-pointer block">
                                    <input type="radio" name="picker-period" value="1" onchange="updateCoverageStyles()" class="peer sr-only" <?= $isFirstHalf ? 'checked' : '' ?>>
                                    <div class="coverage-box border-2 <?= $isFirstHalf ? 'border-green-500 bg-green-50/30' : 'border-slate-100 bg-white hover:border-slate-200' ?> rounded-xl p-3 text-center transition-all">
                                        <span class="block text-[10px] font-black text-slate-800 uppercase">1st Half</span>
                                        <span class="block text-[8px] font-bold text-slate-400 mt-0.5">Day 1 - 15</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer block">
                                    <input type="radio" name="picker-period" value="2" onchange="updateCoverageStyles()" class="peer sr-only" <?= !$isFirstHalf ? 'checked' : '' ?>>
                                    <div class="coverage-box border-2 <?= !$isFirstHalf ? 'border-green-500 bg-green-50/30' : 'border-slate-100 bg-white hover:border-slate-200' ?> rounded-xl p-3 text-center transition-all">
                                        <span class="block text-[10px] font-black text-slate-800 uppercase">2nd Half</span>
                                        <span class="block text-[8px] font-bold text-slate-400 mt-0.5">Day 16 - End</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                        <div class="w-7 h-7 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        </div>
                        <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Data Filters</h3>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Account Status</label>
                        <select id="picker-status" onchange="validateSelect(this)" class="w-full border-2 border-slate-100 bg-slate-50 px-4 py-3 text-[10px] font-black text-slate-700 uppercase tracking-wide outline-none transition-all appearance-none rounded-xl cursor-pointer hover:border-[#ff3b30] hover:bg-white focus:border-[#ff3b30] focus:bg-white">
                            <option value="ONGOING" <?= (!isset($selectedStatus) || $selectedStatus == 'ONGOING') ? 'selected' : '' ?>>Ongoing Accounts Only</option>
                            <option value="FULLY_PAID" <?= (isset($selectedStatus) && $selectedStatus == 'FULLY_PAID') ? 'selected' : '' ?>>Fully Paid (In Period)</option>
                            <option value="ALL" <?= (isset($selectedStatus) && $selectedStatus == 'ALL') ? 'selected' : '' ?>>All Accounts (Ongoing + Paid)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between items-end">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Region Filter</label>
                            <button type="button" onclick="toggleInputType('region')" id="btn_toggle_region" class="text-[8px] font-black text-slate-400 hover:text-[#e11d48] transition-colors uppercase">Type Manually</button>
                        </div>
                        
                        <div class="relative" id="wrapper_region_select">
                            <select id="picker-region-select" onchange="validateSelect(this)" class="w-full border-2 border-slate-100 bg-slate-50 px-4 py-3 text-[10px] font-black text-slate-700 uppercase tracking-wide outline-none transition-all appearance-none rounded-xl cursor-pointer hover:border-[#ff3b30] hover:bg-white focus:border-[#ff3b30] focus:bg-white">
                                <option value="ALL">ALL REGIONS</option>
                                </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>

                        <input type="text" id="picker-region-input" oninput="validateSelect(this)" disabled placeholder="TYPE CUSTOM REGION..." 
                            class="hidden w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button onclick="resetReportFilters()" class="text-[9px] font-black text-slate-500 bg-slate-100 hover:bg-slate-200 hover:text-[#e11d48] px-4 py-2 rounded-lg border border-slate-200 transition-colors flex items-center gap-1.5 uppercase tracking-widest active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Reset Options to Default
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 shrink-0 rounded-b-2xl">
            <button onclick="closeModal('reportPeriodModal')" class="h-11 px-6 bg-white border border-slate-200 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors shadow-sm active:scale-95">
                Cancel
            </button>
            <button id="generate-btn" onclick="applyReportPeriod()" class="h-11 px-8 bg-[#e11d48] text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-500/20 hover:bg-[#be123c] active:scale-95 transition-all">
                Generate Report
            </button>
        </div>
    </div>
</div>