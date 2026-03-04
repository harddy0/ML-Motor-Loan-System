<div id="importDetailModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl max-h-[90vh] flex flex-col rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden text-sm sm:text-base">
        
        <div class="relative shrink-0">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#ce1126] via-[#e11d48] to-[#ce1126]"></div>
            <div class="px-6 py-5 flex justify-between items-center bg-slate-50 border-b border-slate-100">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">Loan Summary Preview</h2>
                    <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-semibold">Pre-Import Verification</p>
                </div>
                <button onclick="closeModal('importDetailModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-8 overflow-y-auto space-y-8 bg-white">
            
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1.5 h-4 bg-slate-300 rounded-full inline-block"></span>
                    <h3 class="font-bold text-slate-600 uppercase tracking-widest text-xs">Borrower Profile</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-50 p-5 rounded-xl border border-slate-100">
                    <div class="col-span-2 md:col-span-1">
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Borrower Name</span>
                        <span id="imp-name" class="font-bold text-slate-900 text-sm uppercase">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Employee ID</span>
                        <span id="imp-id" class="font-bold text-slate-900 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Contact</span>
                        <span id="imp-contact" class="font-bold text-slate-900 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Region</span>
                        <span id="imp-region" class="font-bold text-slate-900 text-sm uppercase">--</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1.5 h-4 bg-[#ce1126] rounded-full inline-block"></span>
                    <h3 class="font-bold text-slate-800 uppercase tracking-widest text-xs">Financial Details</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-5 border border-slate-200 rounded-xl p-5 shadow-sm">
                    
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Promissory Note</span>
                        <span id="imp-pn" class="font-mono font-bold text-slate-800 text-sm bg-slate-100 px-2 py-0.5 rounded">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Reference Number</span>
                        <span id="imp-ref" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Terms & Rate</span>
                        <span class="font-bold text-slate-800 text-sm"><span id="imp-terms"></span> @ <span id="imp-rate"></span></span>
                    </div>
                    
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Date Released</span>
                        <span id="imp-granted" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-1">Maturity Date</span>
                        <span id="imp-maturity" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div class="hidden md:block"></div> <div class="bg-[#fff5f6] p-4 rounded-xl border border-red-100 mt-2 col-span-2 md:col-span-1">
                        <span class="block text-[10px] text-red-400 uppercase font-bold tracking-widest mb-1">Principal Amount</span>
                        <span id="imp-amount" class="font-black text-[#ce1126] text-xl leading-none block mt-1">--</span>
                    </div>
                    <div class="bg-[#fff5f6] p-4 rounded-xl border border-red-100 mt-2 col-span-2 md:col-span-1">
                        <span class="block text-[10px] text-red-400 uppercase font-bold tracking-widest mb-1">Semi-Monthly Deduction</span>
                        <span id="imp-deduct" class="font-black text-[#ce1126] text-xl leading-none block mt-1">--</span>
                    </div>
                </div>
            </div>
            
            <div id="imp-kptn-warning" class="hidden bg-orange-50 border border-orange-200 p-4 rounded-xl">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-orange-100 text-orange-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-orange-800 tracking-wide uppercase">Pending KPTN Deposit</p>
                        <p class="text-xs text-orange-600 mt-1 leading-relaxed">
                            This loan requires a ₱2,500 deposit. A KPTN receipt must be attached by a staff member later to activate this record.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>