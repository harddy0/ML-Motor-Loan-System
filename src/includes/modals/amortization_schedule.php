<div id="amortizationModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-2 flex justify-between items-center shrink-0">
            <h2 class="text-[14px] text-slate-800">
                Review Amortization Schedule
            </h2>
            <button onclick="closeModal('amortizationModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-4 overflow-y-auto custom-scrollbar flex-1">
            
            <div class="text-center mb-3">
                <h3 class="text-md font-black text-slate-800 tracking-tight">Semi-Monthly Amortization Schedule</h3>
                <p class="text-md font-bold text-slate-500 tracking-widest mt-1">Please check before saving</p>
            </div>

            <div class="border-2 border-slate-500 mb-6">
                <div class="flex border-b border-slate-400">
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">Account Name:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-name">CLARISA A. REMARIM</div>
                </div>
                <div class="flex border-b border-slate-400">
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">Contact No:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-contact">0995-665-1675</div>
                </div>
                <div class="flex border-b border-slate-400">
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">PN Number:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-pn"></div>
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">Loan Amount:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-amount">135,000.00</div>
                </div>
                <div class="flex border-b border-slate-400">
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">PN Date:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-date">Dec 2, 2025</div>
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">Term (Mos):</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-terms">24 months</div>
                </div>
                <div class="flex border-b border-slate-400">
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">PN Maturity:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-maturity">Nov 30, 2027</div>
                    <div class="w-32 p-1 text-[14px] text-slate-700 border-r border-slate-800">Interest Rate:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-rate">0.00 %</div>
                </div>
                <div class="flex border-b border-slate-400">
                    <div class="w-27 p-1 pr-5 text-[14px] text-slate-700 border-r border-slate-50"></div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase"></div>
                    <div class="w-50 pl-30 pr-12 p-1 text-[14px] font-bold text-slate-700 border-r border-slate-800">Semi-Monthly Amortization:</div>
                    <div class="flex-1 p-1 font-bold text-black text-[14px] uppercase" id="sched-deduct">3,825.00</div>
                </div>
            </div>

            <div class="border-2 border-slate-500 overflow-hidden rounded-sm">
                <table class="w-full text-right">
                    <thead>
                        <tr class=" text-slate-700 border-b-2 border-slate-500">
                            <th class="text-[14px] p-1 border-r border-slate-400 text-center w-12">#</th>
                            <th class="text-[14px] p-1 border-r border-slate-400 text-center">Date</th>
                            <th class="text-[14px] p-1 border-r border-slate-400 text-center">Principal</th>
                            <th class="text-[14px] p-1 border-r border-slate-400 text-center">Interest</th>
                            <th class="text-[14px] p-1 border-r border-slate-400 font-black text-center">Total Amount</th>
                            <th class="text-[14px] p-1 font-black text-center">Principal Balance</th>
                        </tr>
                        <tr class="border-b border-slate-300">
                            <td colspan="5" class="p-1 border-r border-slate-300"></td>
                            <td class="p-1 font-bold" id="sched-initial-bal">135,000.00</td>
                        </tr>
                    </thead>
                    <tbody id="amortization-rows" class="font-mono text-slate-700">
                        <tr class="hover:bg-red-100 border-b border-slate-200 transition-colors">
                            <td class="p-1 border-r border-slate-200 text-center">1</td>
                            <td class="p-1 border-r border-slate-200 text-center">12/15/2025</td>
                            <td class="p-1 border-r border-slate-200">2,026.53</td>
                            <td class="p-1 border-r border-slate-200">1,798.47</td>
                            <td class="p-1 border-r border-slate-200 font-bold text-black">3,825.00</td>
                            <td class="p-1 font-bold">132,973.47</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="bg-slate-100 px-8 py-3 flex justify-end gap-3 border-t-2 border-slate-200 shrink-0">
            <button onclick="goBackToEdit()"  class="h-8 px-6 bg-slate-100 text-slate-800 rounded-sm shadow-md hover:bg-slate-300 transition-all active:scale-95">
                Edit
            </button>
            <button onclick="submitFinalBorrower()"  class="h-8 px-6 bg-[#ce1126] text-white rounded-sm shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                Save
            </button>
        </div>
    </div>
</div>

<script>
function goBackToEdit() {
    // Close amortization modal (uses existing closeModal if available)
    if (typeof closeModal === 'function') {
        closeModal('amortizationModal');
    } else {
        const am = document.getElementById('amortizationModal');
        if (am) am.classList.add('hidden');
    }

    // Re-open add borrower modal after a short delay to allow animation to finish
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

        // Focus first input in the add form if present
        const firstInput = document.querySelector('#addBorrowerForm input, #addBorrowerForm select');
        if (firstInput) firstInput.focus();
    }, 200);
}
</script>