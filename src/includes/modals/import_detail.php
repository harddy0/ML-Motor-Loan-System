<div id="importDetailModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[95vh]">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center shrink-0">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Import / <span class="text-[#e11d48]">Record Details</span>
            </h2>
            <button onclick="closeModal('importDetailModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="overflow-y-auto custom-scrollbar flex-1 p-8">
            
            <div class="mb-8 border-b-2 border-dashed border-slate-200 pb-8">
                <h3 class="text-[#e11d48] font-bold text-xs tracking-widest uppercase mb-4">Borrower Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Employee ID</label>
                        <p id="imp-id" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Full Name</label>
                        <p id="imp-name" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Contact</label>
                        <p id="imp-contact" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Region</label>
                        <p id="imp-region" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Loan Amount</label>
                        <p id="imp-amount" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase">Terms</label>
                        <p id="imp-terms" class="text-sm font-bold text-black uppercase"></p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <h3 class="text-[#e11d48] font-black text-xs tracking-widest uppercase">Projected Amortization</h3>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition-all">
                    <table class="w-full text-sm text-right border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <th class="p-4 text-center w-12 font-black uppercase tracking-tighter">#</th>
                                <th class="p-4 text-center font-black uppercase tracking-tighter">Due Date</th>
                                <th class="p-4 font-black uppercase tracking-tighter">Principal</th>
                                <th class="p-4 font-black uppercase tracking-tighter">Interest</th>
                                <th class="p-4 font-black uppercase tracking-tighter text-[#e11d48]">Total Amort</th>
                                <th class="p-4 font-black uppercase tracking-tighter text-slate-800">Remaining Balance</th>
                            </tr>
                        </thead>
                        
                        <tbody id="imp-amort-rows" class="font-bold text-slate-700 divide-y divide-slate-50">
                            </tbody>
                    </table>
                </div>
                
                <div class="mt-3 flex justify-end">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        * All figures are projected based on current terms
                    </p>
                </div>
            </div>

        </div>

        <div class="bg-slate-100 px-8 py-4 flex justify-end border-t-2 border-slate-200 shrink-0">
            <button onclick="closeModal('importDetailModal')" 
            class="h-11 px-6 bg-[#e11d48] text-white rounded-full text-[10px] 
                font-black uppercase tracking-widest shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                Close Details
            </button>
        </div>
    </div>
</div>