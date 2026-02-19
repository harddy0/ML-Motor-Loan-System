<div id="viewBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl max-h-[95vh] flex flex-col rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">
                Account / <span class="text-[#e11d48]">Information View</span>
            </h2>
            <button onclick="closeModal('viewBorrowerModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 overflow-hidden">
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-[#e11d48] font-black text-[10px] tracking-widest uppercase">Personal Information</h3>
                <div class="h-px flex-1 bg-slate-100"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Employee ID</label>
                    <p id="m-id" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">First Name</label>
                    <p id="m-fname" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Last Name</label>
                    <p id="m-lname" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">PN Number</label>
                    <p id="m-pn-no" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Contact Number</label>
                    <p id="m-contact" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Region</label>
                    <p id="m-region" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-[#e11d48] font-black text-[10px] tracking-widest uppercase">Loan Particulars</h3>
                <div class="h-px flex-1 bg-slate-100"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-8">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Date Granted</label>
                    <p id="m-date" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">PN Maturity</label>
                    <p id="m-pn-mat" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Terms (Months)</label>
                    <p id="m-terms" class="text-xs font-black text-slate-900 uppercase"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-[#e11d48] rounded-2xl p-6">
                <div>
                    <label class="text-[10px] font-black text-white uppercase tracking-widest">Total Loan Amount</label>
                    <p id="m-amount" class="text-3xl font-black text-white tracking-tight"></p>
                </div>
                <div class="md:text-right">
                    <label class="text-[10px] font-black text-white uppercase tracking-widest">Deduction / Payday</label>
                    <p id="m-deduct" class="text-3xl font-black text-white tracking-tight"></p>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-8 py-5 flex justify-end gap-3 border-t border-slate-100 shrink-0">
            <button onclick="closeModal('viewBorrowerModal')" class="h-11 px-6 bg-slate-100 text-slate-800 rounded-full text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-slate-300 transition-all active:scale-95">
                Close View
            </button>
            <button class="h-11 px-6 bg-[#e11d48] text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                Print PDF
            </button>
        </div>
    </div>
</div>