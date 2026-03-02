<div id="importDetailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-5 flex justify-between items-center">
            <h2 class="text-slate-800 uppercase font-bold">Loan Summary Preview</h2>
            <button onclick="closeModal('importDetailModal')" class="text-slate-400 hover:text-slate-800 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-8 space-y-6 bg-white">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Borrower Details</h3>
                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div>
                        <span class="block text-[11px] text-slate-400 uppercase">Borrower Name</span>
                        <span id="imp-name" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-slate-400 uppercase">Employee ID</span>
                        <span id="imp-id" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-slate-400 uppercase">Contact / Region</span>
                        <span class="font-bold text-slate-800 text-sm"><span id="imp-contact"></span> | <span id="imp-region"></span></span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-slate-400 uppercase">Ref No.</span>
                        <span id="imp-ref" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loan Details</h3>
                    <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-200">PENDING KPTN</span>
                </div>
                <div class="grid grid-cols-2 gap-4 bg-red-50 p-4 rounded-xl border border-red-100">
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">PN Number</span>
                        <span id="imp-pn" class="font-bold text-red-900 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">Principal Amount</span>
                        <span id="imp-amount" class="font-black text-red-600 text-lg">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">Date Released</span>
                        <span id="imp-granted" class="font-bold text-red-900 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">Maturity Date</span>
                        <span id="imp-maturity" class="font-bold text-red-900 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">Terms & Rate</span>
                        <span class="font-bold text-red-900 text-sm"><span id="imp-terms"></span> @ <span id="imp-rate"></span></span>
                    </div>
                    <div>
                        <span class="block text-[11px] text-red-400 uppercase">Semi-Monthly Deduction</span>
                        <span id="imp-deduct" class="font-black text-slate-900 text-sm">--</span>
                    </div>
                </div>
            </div>
            
            <p class="text-[11px] text-slate-500 italic text-center">
                * Amortization schedule will be generated automatically once KPTN receipt is verified by staff.
            </p>
        </div>
    </div>
</div>