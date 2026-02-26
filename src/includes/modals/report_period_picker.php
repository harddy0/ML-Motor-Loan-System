<div id="reportPeriodModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-[70] hidden items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[90vh] overflow-hidden">
        
        <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
            <h2 class="text-base text-slate-800">
                Select Report Period
            </h2>
            <button onclick="closeModal('reportPeriodModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-sm text-slate-500 ml-1">Reporting Year</label>
                    <select id="picker-year" onchange="checkFormReady()" class="w-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 outline-none rounded-lg focus:ring-2 focus:ring-slate-100 transition-all cursor-pointer">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--): ?>
                            <option value="<?= $i ?>" <?= ($i == $currentYear) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-sm text-slate-500 ml-1">Month</label>
                    <select id="picker-month" onchange="checkFormReady()" class="w-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 outline-none rounded-lg focus:ring-2 focus:ring-slate-100 transition-all cursor-pointer">
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
                <label class="text-sm text-slate-500 ml-1">Report Coverage</label>
                <div id="coverage-container" class="space-y-3">
                    <?php 
                        $currentDay = (int)date('d');
                        $isFirstHalf = ($currentDay <= 15);
                    ?>
                    <label class="group cursor-pointer block">
                        <input type="radio" name="picker-period" value="0" onchange="updateCoverageStyles()" class="peer sr-only">
                        <div class="border border-slate-200 bg-white rounded-lg p-3 flex items-center justify-between peer-checked:border-slate-400 peer-checked:bg-slate-50 transition-all">
                            <span class="text-sm text-slate-700">Whole month view</span>
                            <div class="radio-indicator h-4 w-4 rounded-full border border-slate-300 flex items-center justify-center bg-white peer-checked:border-slate-500">
                                <div class="h-2 w-2 rounded-full bg-slate-500 scale-0 transition-transform duration-200"></div>
                            </div>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="1" onchange="updateCoverageStyles()" class="peer sr-only" <?= $isFirstHalf ? 'checked' : '' ?>>
                            <div class="border border-slate-200 bg-white rounded-lg p-3 text-center transition-all peer-checked:border-slate-400 peer-checked:bg-slate-50">
                                <span class="block text-sm text-slate-700">1st Half</span>
                                <span class="block text-xs text-slate-400">Day 1 - 15</span>
                            </div>
                        </label>
                        <label class="cursor-pointer block">
                            <input type="radio" name="picker-period" value="2" onchange="updateCoverageStyles()" class="peer sr-only" <?= !$isFirstHalf ? 'checked' : '' ?>>
                            <div class="border border-slate-200 bg-white rounded-lg p-3 text-center transition-all peer-checked:border-slate-400 peer-checked:bg-slate-50">
                                <span class="block text-sm text-slate-700">2nd Half</span>
                                <span class="block text-xs text-slate-400">Day 16 - End</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[11px] text-slate-400 uppercase tracking-wider ml-1">Account status</label>
                    <select id="picker-status" onchange="validateSelect(this)" class="w-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600 outline-none rounded-lg focus:border-slate-300 transition-all">
                        <option value="ONGOING">Ongoing accounts</option>
                        <option value="FULLY_PAID">Fully paid</option>
                        <option value="ALL">All accounts</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="text-[11px] text-slate-400 uppercase tracking-wider ml-1">Region filter</label>
                        <button type="button" id="btn_toggle_region" onclick="toggleInputType('region')" class="text-[10px] text-slate-400 hover:text-slate-600 transition-colors">
                            Type manually
                        </button>
                    </div>
                    
                    <div id="wrapper_region_select">
                        <select id="picker-region-select" onchange="validateSelect(this)" class="w-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600 outline-none rounded-lg focus:border-slate-300 transition-all">
                            <option value="ALL">Loading regions...</option>
                        </select>
                    </div>
                    
                    <input type="text" id="picker-region-input" placeholder="Enter custom region" oninput="validateSelect(this)" class="w-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none rounded-lg focus:border-slate-300 transition-all hidden" disabled>
                </div>
            </div>

            <div class="flex justify-center">
                <button onclick="resetReportFilters()" class="text-xs text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Reset to default
                </button>
            </div>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col gap-3 shrink-0">
            <button id="generate-btn" onclick="applyReportPeriod()" class="w-full py-3 bg-slate-800 text-white rounded-xl text-sm hover:bg-slate-900 active:scale-[0.99] transition-all">
                Generate Report
            </button>
            <button onclick="closeModal('reportPeriodModal')" class="w-full text-slate-500 text-sm hover:text-slate-700 transition-colors">
                Discard changes
            </button>
        </div>
    </div>
</div>