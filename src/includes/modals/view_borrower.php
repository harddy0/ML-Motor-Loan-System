<div id="viewBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-4xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Account / <span class="text-[#ff3b30]">Information View</span>
            </h2>
            <button onclick="closeModal('viewBorrowerModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-8">
                
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Employee ID</label>
                    <p id="m-id" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">First Name</label>
                    <p id="m-fname" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Last Name</label>
                    <p id="m-lname" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Date Granted</label>
                    <p id="m-date" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Contact Number</label>
                    <p id="m-contact" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">PN Number</label>
                    <p id="m-pn-no" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">PN Maturity</label>
                    <p id="m-pn-mat" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Branch</label>
                    <p id="m-region" class="text-sm font-bold text-slate-900 border-b-2 border-slate-100 pb-1 uppercase"></p>
                </div>

                <div class="md:col-span-4 grid grid-cols-3 gap-6 bg-slate-50 p-6 rounded border-2 border-slate-100 mt-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Loan Amount</label>
                        <p id="m-amount" class="text-xl font-black text-slate-900"></p>
                    </div>
                    <div class="text-center">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Terms (Months)</label>
                        <p id="m-terms" class="text-xl font-black text-slate-900"></p>
                    </div>
                    <div class="text-right">
                        <label class="text-[10px] font-black text-[#ff3b30] uppercase tracking-wider">Deduction / Payday</label>
                        <p id="m-deduct" class="text-xl font-black text-[#ff3b30]"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-100 px-8 py-4 flex justify-end gap-3 border-t-2 border-slate-200">
            <button onclick="closeModal('viewBorrowerModal')" class="bg-white border-2 border-slate-300 hover:border-slate-800 px-8 py-2 text-[10px] font-black uppercase transition-all">
                Close
            </button>
            <button class="bg-slate-800 hover:bg-black text-white px-8 py-2 text-[10px] font-black uppercase transition-all shadow-md">
                Print PDF
            </button>
        </div>
    </div>
</div>