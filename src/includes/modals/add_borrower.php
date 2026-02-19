<div id="addBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-6xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Borrower / <span class="text-[#ff3b30]">New Application</span>
            </h2>
            <button onclick="closeModal('addBorrowerModal')" class="text-slate-500 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="addBorrowerForm" class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                
                <div class="space-y-1">
    <label for="employe_id" class="text-[10px] font-black text-black uppercase tracking-wider">Employee ID (Auto)</label>
    <input type="text" name="employe_id" id="employe_id" readonly 
           class="w-full border-b-2 border-slate-200 bg-slate-100 text-slate-500 px-3 py-2 text-sm font-bold outline-none cursor-not-allowed">
</div>

                <div class="space-y-1">
                    <label for="first_name" class="text-[10px] font-black text-black uppercase tracking-wider">First Name *</label>
                    <input type="text" name="first_name" id="first_name" placeholder="CLARISA" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1">
                    <label for="last_name" class="text-[10px] font-black text-black uppercase tracking-wider">Last Name *</label>
                    <input type="text" name="last_name" id="last_name" placeholder="REMARIM" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1">
                    <label for="loan_granted" class="text-[10px] font-black text-black uppercase tracking-wider">Loan Granted *</label>
                    <input type="date" name="loan_granted" id="loan_granted" value="<?= date('Y-m-d') ?>" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors">
                </div>

                <div class="space-y-1">
                    <label for="contact_number" class="text-[10px] font-black text-black uppercase tracking-wider">Contact Number *</label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="09XX-XXX-XXXX" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1">
                    <label for="pn_number" class="text-[10px] font-black text-black uppercase tracking-wider">PN Number *</label>
                    <input type="text" name="pn_number" id="pn_number" placeholder="12345" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1">
                    <label for="pn_maturity" class="text-[10px] font-black text-black uppercase tracking-wider">PN Maturity *</label>
                    <input type="date" name="pn_maturity" id="pn_maturity" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors">
                </div>

                <div class="space-y-1">
                    <label for="region" class="text-[10px] font-black text-black uppercase tracking-wider">Region *</label>
                    <div class="relative">
                        <select name="region" id="region" class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors appearance-none uppercase">
                            <option value="DAVAO">DAVAO</option>
                            <option value="CEBU">CEBU</option>
                            <option value="MANILA">MANILA</option>
                            <option value="HEAD OFFICE">HEAD OFFICE</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-black">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="loan_amount" class="text-[10px] font-black text-black uppercase tracking-wider">Loan Amount (Principal) *</label>
                    <input type="number" step="0.01" name="loan_amount" id="loan_amount" placeholder="0.00" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors placeholder:text-slate-300">
                </div>

                <div class="space-y-1">
                    <label for="terms" class="text-[10px] font-black text-black uppercase tracking-wider">Terms (Months) *</label>
                    <input type="number" name="terms" id="terms" placeholder="36" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-black outline-none focus:border-[#ff3b30] transition-colors placeholder:text-slate-300">
                </div>


                <div class="space-y-1">
                    <label for="deduction" class="text-[10px] font-black text-black uppercase tracking-wider">Deduction Per Payday *</label>
                    <input type="number" step="0.01" name="deduction" id="deduction" placeholder="0.00" required 
                           class="w-full border-b-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-[#ff3b30] outline-none focus:border-[#ff3b30] transition-colors placeholder:text-slate-300">
                </div>
            </div>

            <div class="bg-slate-100 px-8 py-4 flex justify-end gap-3 border-t-2 border-slate-200 -mx-8 -mb-8">
                <button type="button" onclick="closeModal('addBorrowerModal')" class="bg-white border-2 border-slate-300 hover:border-slate-800 px-8 py-2 text-[10px] font-black uppercase transition-all">
                    Cancel
                </button>
                <button type="button" onclick="validateAndShowSchedule()" class="bg-[#ff3b30] hover:bg-red-700 text-white px-8 py-2 text-[10px] font-black uppercase transition-all shadow-md">
                    Next >
                </button>
            </div>
        </form>
    </div>
</div>