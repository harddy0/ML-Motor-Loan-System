<div id="ledgerDetailModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[60] hidden items-center justify-center p-2 md:p-4">
    <div class="bg-white w-full max-w-7xl h-[95vh] rounded-lg shadow-2xl border border-slate-200 overflow-hidden flex flex-col font-sans">
        
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 shrink-0 shadow-sm z-10">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight" id="modal-ledger-name">--</h2>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 bg-slate-200 text-slate-600 text-l font-black uppercase rounded" id="modal-ledger-id">--</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:inline-block">Motorcycle Loan Account</span>
                    </div>
                </div>
                <button onclick="closeLedgerModal()" class="group bg-white border border-slate-200 text-slate-400 hover:text-[#e11d48] hover:border-[#e11d48] p-2 rounded-full transition-all">
                    <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 pt-2 mt-2">
                <div class="relative pl-4 border-l-2 border-[#e11d48]/20 group hover:border-[#e11d48] transition-colors duration-300">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.15em] mb-4">Reference</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">PN Number</span>
                            <span class="text-sm font-black text-slate-900 font-mono" id="modal-ledger-pn">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Granted</span>
                            <span class="text-xs font-bold text-slate-600" id="modal-ledger-pndate">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Maturity</span>
                            <span class="text-xs font-bold text-slate-600" id="modal-ledger-maturity">--</span>
                        </div>
                    </div>
                </div>

                <div class="relative pl-4 border-l-2 border-slate-100 group hover:border-[#e11d48]/50 transition-colors duration-300">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.15em] mb-4">Terms</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Duration</span>
                            <span class="text-sm font-black text-slate-900" id="modal-ledger-terms">-- Months</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Add-on Rate</span>
                            <div class="px-2 py-0.5 bg-slate-100 rounded text-[11px] font-black text-slate-700" id="modal-ledger-rate">--</div>
                        </div>
                    </div>
                </div>

                <div class="relative pl-4 border-l-2 border-slate-100 group hover:border-[#e11d48]/50 transition-colors duration-300">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.15em] mb-4">Financials</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">Principal</span>
                            <span class="text-sm font-black text-slate-900" id="modal-ledger-principal">--</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-[#e11d48]/5 rounded-xl mt-2">
                            <span class="text-[9px] font-black text-[#e11d48] uppercase tracking-tighter">Amortization</span>
                            <span class="text-sm font-black text-[#e11d48]" id="modal-ledger-amort">--</span>
                        </div>
                    </div>
                </div>

            <div class="relative pl-4 border-l-2 border-slate-100 group hover:border-[#e11d48]/50 transition-colors duration-300">
                    <span class="text-[10px] font-black text-slate-800 uppercase tracking-[0.15em] mb-4">Account Status</span>
                    <div class="relative">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase rounded-full border border-emerald-100" id="modal-ledger-status">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Ongoing
                        </span>
                    </div>
                </div>

                <div class="flex flex-col justify-center items-end text-right bg-slate-50 p-4 rounded-2xl">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Principal Balance</span>
                    <div class="flex items-baseline gap-1">
                        
                        <span class="text-4xl font-black text-[#e11d48] tracking-tighter leading-none" id="modal-ledger-balance">--</span>
                    </div>
                </div>

            </div>
        </div>

        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden bg-white h-0">
            <div class="flex-1 flex flex-col h-full overflow-hidden border-r border-slate-200 relative">
                <div class="bg-slate-900 text-white flex text-xs font-black uppercase tracking-wider sticky top-0 z-20 shadow-md">
                    <div class="w-[10%] p-4 text-center border-r border-white/10">Due Date</div>
                    <div class="w-[12%] p-4 text-center border-r border-white/10 bg-slate-800 text-slate-300">Date Paid</div>
                    <div class="w-[12%] p-4 text-right border-r border-white/10">Principal</div>
                    <div class="w-[12%] p-4 text-right border-r border-white/10">Interest</div>
                    <div class="w-[12%] p-4 text-right border-r border-white/10 text-yellow-400">Total Due</div>
                    <div class="w-[14%] p-4 text-right border-r border-white/10 bg-[#ff3b30]">Balance</div>
                    <div class="w-[10%] p-4 text-center border-r border-white/10">Status</div>
                    <div class="flex-1 p-4 text-left">Notes</div>
                </div>

                <div class="overflow-y-auto custom-scrollbar flex-1 relative">
                    <div id="ledger-loading" class="absolute inset-0 bg-white/90 z-30 flex items-center justify-center hidden backdrop-blur-sm">
                        <div class="flex flex-col items-center">
                            <svg class="animate-spin h-10 w-10 text-[#e11d48] mb-3" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Calculating Ledger...</span>
                        </div>
                    </div>
                    
                    <table class="w-full text-left border-collapse table-fixed">
                        <tbody id="modal-ledger-rows" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
<!--end here-->

            <div class="w-full lg:w-72 bg-slate-50 flex flex-col shrink-0 border-t lg:border-t-0 z-10 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.05)]">
                <div class="p-6 flex flex-col h-full">
                    <h3 class="text-[#e11d48] font-black text-xs uppercase tracking-widest border-b border-slate-200 pb-3 mb-4">Payment Summary</h3>
                    <div class="space-y-4 flex-1">
                        <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-200 border-dashed">
                            <span class="font-bold text-slate-500 uppercase">Principal Paid</span>
                            <span class="font-black text-slate-800" id="sum-principal">0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-200 border-dashed">
                            <span class="font-bold text-slate-500 uppercase">Interest Paid</span>
                            <span class="font-black text-slate-800" id="sum-interest">0.00</span>
                        </div>
                        <div class="bg-white p-4 rounded shadow-sm mt-2">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Total Collected</span>
                            <span class="block text-2xl font-black text-green-600 leading-none" id="sum-paid">0.00</span>
                        </div>
                    </div>

                    <div class="pt-6 mt-auto space-y-3">
                        <button 
                        class="w-full py-3 bg-[#e11d48] hover:bg-[#be123c] text-white text-xs font-black uppercase rounded shadow-lg transition-all flex justify-center items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Ledger
                        </button>
                        <button id="btn-export-ledger" onclick="exportLedgerExcel()" class="w-full py-3 bg-white border border-slate-300 hover:border-[#ff3b30] hover:text-[#ff3b30] text-slate-500 text-xs font-black uppercase rounded transition-all flex justify-center items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>