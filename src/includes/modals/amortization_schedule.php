<div id="amortizationModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center shrink-0">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Review / <span class="text-[#ff3b30]">Amortization Schedule</span>
            </h2>
            <button onclick="closeModal('amortizationModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
            
            <div class="text-center mb-6">
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Semi-Monthly Amortization Schedule</h3>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Please check before saving</p>
            </div>

            <div class="border-2 border-slate-800 mb-6 text-xs">
                <div class="flex border-b border-slate-800">
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">Account Name:</div>
                    <div class="flex-1 p-2 font-bold text-black uppercase" id="sched-name">CLARISA A. REMARIM</div>
                </div>
                <div class="flex border-b border-slate-800">
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">Contact No:</div>
                    <div class="flex-1 p-2 font-bold text-black" id="sched-contact">0995-665-1675</div>
                </div>
                <div class="flex border-b border-slate-800">
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">PN Number:</div>
                    <div class="flex-1 p-2 font-bold text-black border-r border-slate-800" id="sched-pn"></div>
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">Loan Amount:</div>
                    <div class="w-48 p-2 font-black text-right text-black" id="sched-amount">135,000.00</div>
                </div>
                <div class="flex border-b border-slate-800">
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">PN Date:</div>
                    <div class="flex-1 p-2 font-bold text-black border-r border-slate-800" id="sched-date">Dec 2, 2025</div>
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">Term (Mos):</div>
                    <div class="w-48 p-2 font-black text-right text-black" id="sched-terms">24 months</div>
                </div>
                <div class="flex border-b border-slate-800">
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">PN Maturity:</div>
                    <div class="flex-1 p-2 font-bold text-black border-r border-slate-800" id="sched-maturity">Nov 30, 2027</div>
                    <div class="w-32 bg-slate-100 p-2 font-black text-slate-700 border-r border-slate-800">Interest Rate:</div>
                    <div class="w-48 p-2 font-black text-right text-black">36 %</div>
                </div>
                <div class="flex bg-slate-50">
                    <div class="flex-1 border-r border-slate-800"></div>
                    <div class="w-48 bg-slate-200 p-2 font-black text-slate-800 border-r border-slate-800 text-[10px] uppercase">Semi-Monthly Amortization</div>
                    <div class="w-48 p-2 font-black text-right text-[#ff3b30]" id="sched-deduct">3,825.00</div>
                </div>
            </div>

            <div class="border-2 border-slate-800 overflow-hidden rounded-sm">
                <table class="w-full text-xs text-right">
                    <thead>
                        <tr class="bg-slate-100 text-slate-800 border-b-2 border-slate-800">
                            <th class="p-2 border-r border-slate-400 text-center w-12">#</th>
                            <th class="p-2 border-r border-slate-400 text-center">Date</th>
                            <th class="p-2 border-r border-slate-400">Principal</th>
                            <th class="p-2 border-r border-slate-400">Interest</th>
                            <th class="p-2 border-r border-slate-400 font-black">Total Amount</th>
                            <th class="p-2 font-black">Principal Balance</th>
                        </tr>
                        <tr class="bg-yellow-50 border-b border-slate-300">
                            <td colspan="5" class="p-2 border-r border-slate-300"></td>
                            <td class="p-2 font-bold" id="sched-initial-bal">135,000.00</td>
                        </tr>
                    </thead>
                    <tbody id="amortization-rows" class="font-mono text-slate-700">
                        <tr class="hover:bg-yellow-100 border-b border-slate-200 transition-colors">
                            <td class="p-2 border-r border-slate-200 text-center">1</td>
                            <td class="p-2 border-r border-slate-200 text-center">12/15/2025</td>
                            <td class="p-2 border-r border-slate-200">2,026.53</td>
                            <td class="p-2 border-r border-slate-200">1,798.47</td>
                            <td class="p-2 border-r border-slate-200 font-bold text-black">3,825.00</td>
                            <td class="p-2 font-bold">132,973.47</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="bg-slate-100 px-8 py-4 flex justify-end gap-3 border-t-2 border-slate-200 shrink-0">
            <button onclick="closeModal('amortizationModal')" class="bg-white border-2 border-slate-300 hover:border-slate-800 px-8 py-2 text-[10px] font-black uppercase transition-all">
                Back to Edit
            </button>
            <button onclick="submitFinalBorrower()" class="bg-[#ff3b30] hover:bg-red-700 text-white px-8 py-2 text-[10px] font-black uppercase transition-all shadow-md">
                Save Borrower
            </button>
        </div>
    </div>
</div>