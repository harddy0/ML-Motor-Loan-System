<div id="ledgerDetailModal" class="fixed inset-0 bg-white z-[60] hidden flex-col overflow-hidden text-[14px]">
    
    <a href="javascript:void(0);" onclick="closeLedgerModal()" class="fixed top-2.5 right-6 group bg-red-500 text-white hover:bg-red-600 p-1 rounded-full transition-all shadow-md z-[70] flex items-center justify-center">
        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" name="close-button">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </a>

    <div id="ledger-floating-actions" class="fixed top-2 right-14 z-[70] flex items-center gap-2">
        <button id="btn-export-ledger" onclick="exportLedgerExcel()"
            class="px-2 py-1.5 bg-white border border-slate-200 text-slate-600 text-[12px] font-bold uppercase rounded-lg hover:bg-slate-50 transition-all flex items-center gap-2 shadow-md">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export
        </button>
    </div>

    <div id="ledger-loading" class="fixed inset-0 bg-white/90 z-[65] hidden items-center justify-center">
        <div class="w-10 h-10 border-4 border-slate-200 border-t-rose-500 rounded-full animate-spin"></div>
    </div>

    <div class="flex-1 overflow-y-auto relative w-full scroll-smooth">
        
        <div class="flex flex-col bg-slate-200 w-full">
            <div class="flex items-start justify-between p-2 gap-2 w-full">
                <div class="flex flex-col gap-0 min-w-[301px] bg-white shadow-md rounded-md ml-1 -pr-1 pb-1 ">
                    <div class="px-3 pt-2 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Borrower's Name:</span>
                        <h2 class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-name">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Employee ID:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-id">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Reference Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-ref">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Region:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap " id="modal-ledger-region">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Branch:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-branch">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Contact Number:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-contact">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">System Loan Number :</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-pn">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Date Released:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-pndate">--</h2>
                    </div>
                    <div class="px-3 flex items-center gap-2 space-y-1">
                        <span class="text-[12px] text-slate-400 uppercase w-36">Maturity Date:</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-maturity">--</h2>
                    </div>
                </div>

                <div name="bigcard" class="flex-grow -mt-2">
                    <div class="mb-0">
                        <div class="flex items-center justify-between mb-0">
                            <div class="flex-1"></div>
                            <div name="btn" class="flex items-center gap-2"></div>
                        </div>
                    </div>
                    <div name="loanInfo" class="mt-2">
                        <div class="grid grid-cols-4 grid-rows-1 bg-white rounded-md shadow-sm items-start">
                            <div class="col-span-4 row-span-1 bg-[#ce2216] text-white rounded-t-md py-0.4">
                                <div class="px-3 py-0 relative">
                                    <div class="flex items-center mt-1.5">
                                        <span class="px-2 py-2.5 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100 text-[6px] uppercase" id="modal-ledger-status">--</span>
                                    </div>
                                    <div class="flex items-center justify-between mt-2 pb-0">
                                        <h2 class="absolute left-1/2 top-1/2 transform -pt-1 pb-1 -translate-x-1/2 -translate-y-1/2 text-[18px] text-white uppercase font-bold tracking-widest ">MOTORCYCLE LOAN REPORT</h2>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-span-4 row-span-1">
                                <div name="row1" class="px-3 py-0 border-b border-slate-100">
                                    <div class="text-[12px] font-bold justify-between pt-3 uppercase text-slate-900 flex w-full">Loan Details
                                    </div>
                                    <div class="grid grid-cols-3 gap-6 pb-2 items-start space-y-1">
                                        <div class="col-span-1">
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Loan Amount</div>
                                                <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-principal">₱ 0.00</div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Rate</div>
                                                <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-rate">0.00%</div>
                                            </div>
                                        </div>

                                        <div class="col-span-1">
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Semi-monthly Amortization</div>
                                                <div class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-amort">₱ 0.00</div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Monthly Amortization</div>
                                                <div class="text-[13px] text-slate-800 font-bold uppercase whitespace-nowrap" id="modal-ledger-monthly-amort">₱ 0.00</div>
                                            </div>
                                        </div>

                                        <div class="col-span-1">
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Term(s)</div>
                                                <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-terms">--</div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Security Deposit</div>
                                                <div class="text-[13px] text-slate-800 font-semibold uppercase whitespace-nowrap" id="modal-ledger-security-deposit">₱ 2,500.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div name="row2" class="col-span-4 row-span-1 grid grid-cols-3 gap-1">
                                    <div name="col2" class="col-span-1">
                                        <div class="p-3 pr-0">
                                            <div class="text-[12px] font-bold uppercase text-slate-900">Gross</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Gross Principal:</span>
                                                <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-gross-principal">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center gap-2 justify-between">
                                                <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Gross Interest:</span>
                                                <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-gross-interest">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center gap-2 justify-between">
                                                <span class="text-[12px] text-slate-900 font-bold uppercase whitespace-nowrap">Total Gross:</span>
                                                <span class="text-[13px] text-slate-900 font-bold whitespace-nowrap" id="modal-ledger-gross-total">₱ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    </div>

                                    <div name="col3" class="col-span-1">
                                        <div class="p-3">
                                            <div class="text-[12px] font-bold uppercase text-slate-900">Payment</div>
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 justify-between">
                                                        <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Principal Paid:</span>
                                                        <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-principal-paid">₱ 0.00</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 justify-between">
                                                        <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Paid:</span>
                                                        <span class="text-[13px] text-slate-800 font-semibold whitespace-nowrap" id="modal-ledger-interest-paid">₱ 0.00</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 justify-between">
                                                        <span class="text-[12px] text-slate-900 font-bold uppercase whitespace-nowrap">Total Payment:</span>
                                                        <span class="text-[13px] text-slate-900 font-bold whitespace-nowrap" id="modal-ledger-total-payment">₱ 0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>

                                    <div name="col4" class="col-span-1">
                                        <div class="p-3">
                                            <div class="text-[12px] font-bold uppercase text-slate-900">Balance</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 justify-between">
                                                <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Principal Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-semibold whitespace-nowrap" id="modal-ledger-principal-balance">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center gap-2 justify-between">
                                                <span class="text-[12px] text-slate-400 uppercase whitespace-nowrap">Interest Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-semibold whitespace-nowrap" id="modal-ledger-interest-balance">₱ 0.00</span>
                                            </div>
                                            <div class="flex items-center gap-2 justify-between">
                                                <span class="text-[12px] text-rose-600 font-bold uppercase whitespace-nowrap">Outstanding Balance:</span>
                                                <span class="text-[13px] text-rose-600 font-bold whitespace-nowrap" id="modal-ledger-total-balance">₱ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <div class="w-full bg-white border-t border-slate-200 pb-6">
                <table class="w-full text-left border-collapse table-fixed bg-white">
                    <thead class="sticky top-0">
                        <tr class="bg-[#ce1126] border-b border-slate-300">
                            <th class="py-1 w-[16%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Due Date</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Principal</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Interest</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Total Amount</th>
                            <th class="py-1 w-[14%] pr-4 text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Balance</th>
                            <th class="py-1 w-[10%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Status</th>
                            <th class="py-1 px-6 text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="modal-ledger-rows" class="divide-y bg-white divide-slate-50 text-slate-600 text-[13px]"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>