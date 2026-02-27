<div id="ledgerDetailModal" class="fixed inset-0 bg-white z-[60] hidden flex-col overflow-hidden text-[14px]">
    
    <a href="javascript:void(0);" onclick="closeLedgerModal()" class="fixed top-4 right-6 group bg-red-500 text-white hover:bg-red-600 p-2 rounded-full transition-all shadow-md z-[70] flex items-center justify-center">
        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" name="close-button">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </a>

    <div id="ledger-loading" class="fixed inset-0 bg-white/90 z-[65] hidden items-center justify-center">
        <div class="w-10 h-10 border-4 border-slate-200 border-t-rose-500 rounded-full animate-spin"></div>
    </div>

    <div class="flex-1 overflow-y-auto relative w-full scroll-smooth">
        
        <div class="flex flex-col w-full p-5 pb-3">
            <div class="flex items-start justify-between gap-10 w-full">
                
                <div class="flex flex-col gap-0.5 min-w-[300px] border-r border-slate-100 pr-10">
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Borrower's Name:</span>
                        <h2 class="text-[13px] text-slate-800 font-bold uppercase" id="modal-ledger-name">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">ID Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-id">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Reference Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-ref">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Region:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-region">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Branch:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-branch">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Contact Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-contact">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">PN Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-pn">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Date Released:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-pndate">--</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Maturity Date:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase" id="modal-ledger-maturity">--</h2>
                    </div>
                </div>

                <div class="flex-grow px-2">
                    <div class="border-b border-slate-100 mb-1">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-[14px] text-slate-800 uppercase font-bold tracking-widest">MOTORCYCLE LOAN REPORT</h2>
                            <div class="flex items-center gap-1">
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100 text-[10px] font-bold uppercase" id="modal-ledger-status">--</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-x-8 gap-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-32">Loan Amount:</span>
                            <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="modal-ledger-principal">₱ 0.00</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-32">Interest Rate (AOR):</span>
                            <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="modal-ledger-rate">0.00%</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-32">Terms:</span>
                            <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="modal-ledger-terms">--</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-32">Amortization:</span>
                            <h2 class="text-[13px] text-rose-600 font-bold uppercase" id="modal-ledger-amort">₱ 0.00</h2>
                        </div>
                    </div>

                    <div class="mt-2">
                        <span class="text-[12px] text-slate-900 font-bold uppercase">Payment Summary</span>
                        <div class="grid grid-cols-2 gap-x-12 gap-y-1">
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-[12px] text-slate-500 uppercase">Principal Paid:</span>
                                <span class="text-[12px] text-slate-800 font-medium" id="modal-ledger-principal-paid">₱ 0.00</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-[12px] text-slate-500 uppercase">Principal Balance:</span>
                                <span class="text-[12px] text-slate-800 font-medium" id="modal-ledger-principal-balance">₱ 0.00</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-[12px] text-slate-500 uppercase">Interest Paid:</span>
                                <span class="text-[12px] text-slate-800 font-medium" id="modal-ledger-interest-paid">₱ 0.00</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1">
                                <span class="text-[12px] text-slate-500 uppercase">Interest Balance:</span>
                                <span class="text-[12px] text-slate-800 font-medium" id="modal-ledger-interest-balance">₱ 0.00</span>
                            </div>
                            <div class="flex justify-between pt-1">
                                <span class="text-[12px] text-slate-900 font-bold uppercase">Total Collected:</span>
                                <span class="text-[12px] text-slate-900 font-bold" id="modal-ledger-total-collected">₱ 0.00</span>
                            </div>
                            <div class="flex justify-between pt-1">
                                <span class="text-[12px] text-rose-600 font-bold uppercase">Total Outstanding:</span>
                                <span class="text-[12px] text-rose-600 font-bold" id="modal-ledger-total-balance">₱ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-4 min-w-[180px] mt-12">
                    <div class="flex flex-col gap-2 w-full max-w-[160px] mt-20">
                        <button class="w-full py-2 bg-rose-500 hover:bg-rose-600 text-white text-[11px] font-bold uppercase rounded-lg transition-all shadow-sm flex justify-center items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Ledger
                        </button>
                        <button id="btn-export-ledger" onclick="exportLedgerExcel()" class="w-full py-2 bg-white border border-slate-200 text-slate-600 text-[11px] font-bold uppercase rounded-lg hover:bg-slate-50 transition-all flex justify-center items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full border-t border-slate-200">
            <table class="w-full text-left border-collapse table-fixed">
                <thead class="sticky top-0">
                    <tr class="bg-slate-50 border-b border-slate-300">
                        <th class="py-1 w-[14%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Payroll Date</th>
                        <th class="py-1 w-[14%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Date Paid</th>
                        <th class="py-1 w-[12%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Principal</th>
                        <th class="py-1 w-[12%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Interest</th>
                        <th class="py-1 w-[12%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Total Amount</th>
                        <th class="py-1 w-[12%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Balance</th>
                        <th class="py-1 w-[10%] text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Status</th>
                        <th class="py-1 px-6 text-[14px] font-black text-slate-600 uppercase tracking-widest border-r border-slate-100 text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody id="modal-ledger-rows" class="divide-y divide-slate-50 text-slate-600 text-[13px]"></tbody>
            </table>
        </div>

    </div>
</div>