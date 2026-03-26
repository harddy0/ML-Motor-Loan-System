<div id="amortizationModal" class="fixed inset-0 bg-white z-[60] hidden flex-col overflow-hidden text-[14px]">
    
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
                        <span class="text-[12px] text-slate-400 uppercase w-36">System Loan Number :</span>
                        <h2 class="text-[13px] text-slate-800 uppercase  whitespace-nowrap" id="modal-ledger-pn">--</h2>
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
                                <div class="col-span-4 row-span-1 bg-[#ce2216] text-white rounded-t-md py-2">
                                    <div class="px-3 py-0 relative">
                                        <div class="flex items-center justify-between mt-2 mb-2 pb-0">
                                            <h2 class="absolute left-1/2 top-1/2 transform -pt-1 pb-0 -translate-x-1/2 -translate-y-1/2 text-[18px] text-white uppercase font-bold tracking-widest ">MOTORCYCLE LOAN REPORT</h2>
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

            <div name="ledger-sched" class="w-full bg-white border-t border-slate-200 pb-6">
                <table class="w-full text-left border-collapse table-fixed bg-white">
                    <thead class="sticky top-0">
                        <tr class="bg-[#ce1126] border-b border-slate-300">
                            <th class="py-1 w-[16%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Due Date</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Principal</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Interest</th>
                            <th class="py-1 w-[14%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Paid Amount</th>
                            <th class="py-1 w-[14%] pr-4 text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right">Principal Balance</th>
                            <th class="py-1 w-[10%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Status</th>
                            <th class="py-1 px-6 text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="modal-ledger-rows" class="divide-y bg-white divide-slate-50 text-slate-600 text-[13px]"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Added Edit/Save buttons from amortization modal -->
    <div class="bg-slate-100 px-8 py-3 flex justify-end gap-3 border-t-2 border-slate-200 shrink-0">
        <button onclick="goBackToEdit()"  class="h-8 px-6 bg-slate-100 text-slate-800 rounded-lg shadow-md hover:bg-slate-300 transition-all active:scale-95">
            Edit
        </button>
        <button onclick="submitFinalBorrower()"  class="h-8 px-6 bg-[#ce1126] text-white rounded-lg shadow-md hover:bg-[#be123c] transition-all active:scale-95">
            Save
        </button>
    </div>
</div>

<script>
function toggleLedgerExportMenu(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('ledgerExportMenu');
    if (!menu) return;
    menu.classList.toggle('hidden');
}

(function initLedgerExportMenuHandlers() {
    if (window.__ledgerExportMenuInitialized) return;
    window.__ledgerExportMenuInitialized = true;

    document.addEventListener('click', function (event) {
        const menu = document.getElementById('ledgerExportMenu');
        const btn = document.getElementById('ledgerExportMenuBtn');
        if (!menu || !btn) return;
        if (menu.classList.contains('hidden')) return;
        if (!menu.contains(event.target) && !btn.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
})();

// Minimal goBackToEdit handler copied from amortization modal
function goBackToEdit() {
    if (typeof closeModal === 'function') {
        closeModal('amortizationModal');
    } else {
        const am = document.getElementById('amortizationModal');
        if (am) am.classList.add('hidden');
    }

    setTimeout(() => {
        if (typeof openModal === 'function') {
            openModal('addBorrowerModal');
        } else {
            const add = document.getElementById('addBorrowerModal');
            if (add) {
                add.classList.remove('hidden');
                add.classList.add('flex');
            }
        }

        const firstInput = document.querySelector('#addBorrowerForm input, #addBorrowerForm select');
        if (firstInput) firstInput.focus();
    }, 200);
}
</script>
