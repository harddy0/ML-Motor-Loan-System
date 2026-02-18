<div id="reportPeriodModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Select <span class="text-[#ff3b30]">Report Period</span>
            </h2>
            <button onclick="closeModal('reportPeriodModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 space-y-4">
            
            <div class="space-y-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Reporting Year</label>
                <div class="relative">
                    <select id="picker-year" class="w-full border-2 border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#ff3b30] transition-colors appearance-none uppercase rounded">
                        <?php 
                        $currentYear = date('Y');
                        for($i = $currentYear; $i >= $currentYear - 5; $i--): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Month</label>
                <div class="relative">
                    <select id="picker-month" class="w-full border-2 border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#ff3b30] transition-colors appearance-none uppercase rounded">
                        <?php 
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach($months as $idx => $m): ?>
                            <option value="<?= $idx + 1 ?>" <?= ($idx + 1) == date('n') ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Report Coverage</label>
                <div class="grid grid-cols-1 gap-2">
                    
                    <label class="cursor-pointer">
                        <input type="radio" name="picker-period" value="0" class="peer sr-only" checked>
                        <div class="border-2 border-slate-200 bg-white rounded p-3 flex items-center justify-between hover:bg-slate-50 peer-checked:border-[#ff3b30] peer-checked:bg-red-50 transition-all">
                            <div>
                                <span class="block text-xs font-black text-slate-800 uppercase">Whole Month</span>
                                <span class="block text-[9px] font-bold text-slate-400">Consolidated Report</span>
                            </div>
                            <div class="h-4 w-4 rounded-full border border-slate-300 peer-checked:bg-[#ff3b30] peer-checked:border-[#ff3b30]"></div>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="picker-period" value="1" class="peer sr-only">
                            <div class="border-2 border-slate-200 bg-white rounded p-3 text-center hover:bg-slate-50 peer-checked:border-[#ff3b30] peer-checked:bg-red-50 transition-all">
                                <span class="block text-[10px] font-black text-slate-800 uppercase">1st Half</span>
                                <span class="block text-[8px] font-bold text-slate-400">1 - 15</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="picker-period" value="2" class="peer sr-only">
                            <div class="border-2 border-slate-200 bg-white rounded p-3 text-center hover:bg-slate-50 peer-checked:border-[#ff3b30] peer-checked:bg-red-50 transition-all">
                                <span class="block text-[10px] font-black text-slate-800 uppercase">2nd Half</span>
                                <span class="block text-[8px] font-bold text-slate-400">16 - End</span>
                            </div>
                        </label>
                    </div>

                </div>
            </div>

        </div>

        <div class="bg-slate-50 px-6 py-4 flex justify-end gap-2 border-t border-slate-200">
            <button onclick="closeModal('reportPeriodModal')" class="px-4 py-2 bg-white border border-slate-300 rounded text-[10px] font-black text-slate-600 uppercase hover:bg-slate-100">Cancel</button>
            <button onclick="applyReportPeriod()" class="px-6 py-2 bg-[#ff3b30] rounded text-[10px] font-black text-white uppercase hover:bg-red-700 shadow-md">
                Generate
            </button>
        </div>
    </div>
</div>

<script>
    function openReportPicker() {
        const modal = document.getElementById('reportPeriodModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function applyReportPeriod() {
        const year = document.getElementById('picker-year').value;
        const monthIndex = document.getElementById('picker-month').value - 1;
        const periodVal = document.querySelector('input[name="picker-period"]:checked').value;
        
        const months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
        const monthName = months[monthIndex];
        
        let periodName = "Whole Month";
        if(periodVal === '1') periodName = "1st Half (1-15)";
        if(periodVal === '2') periodName = "2nd Half (16-End)";

        // Update the Main Page Header
        const labelDisplay = document.getElementById('current-period-display');
        if(labelDisplay) {
            labelDisplay.innerHTML = `${monthName} ${year} <span class="text-slate-300 mx-2">|</span> <span class="text-[#ff3b30]">${periodName}</span>`;
        }

        closeModal('reportPeriodModal');
    }
</script>