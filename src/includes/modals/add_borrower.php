<div id="addBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-5 flex justify-between items-center">
            <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">
                Borrower / <span class="text-[#e11d48]">New Application</span>
            </h2>
            <button onclick="closeModal('addBorrowerModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="addBorrowerForm" class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6 mb-10">
                
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider ml-1">Employee ID (Auto)</label>
                    <input type="text" name="employe_id" id="employe_id" readonly 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">First Name *</label>
                    <input type="text" name="first_name" placeholder="CLARISA" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Last Name *</label>
                    <input type="text" name="last_name" placeholder="REMARIM" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Contact Number *</label>
                    <input type="text" name="contact_number" placeholder="09XX-XXX-XXXX" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <div class="flex justify-between items-end">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Region *</label>
                        <button type="button" onclick="toggleInputType('region')" id="btn_toggle_region" class="text-[8px] font-black text-slate-400 hover:text-[#e11d48] transition-colors uppercase">Type Manually</button>
                    </div>
                    
                    <div class="relative" id="wrapper_region_select">
                        <select name="region" id="region_select" required class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none appearance-none transition-all uppercase">
                            <option value="">LOADING REGIONS...</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </div>

                    <input type="text" name="region" id="region_input" disabled placeholder="TYPE CUSTOM REGION..." 
                        class="hidden w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <div class="flex justify-between items-end">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Division</label>
                        <button type="button" onclick="toggleInputType('division')" id="btn_toggle_division" class="text-[8px] font-black text-slate-400 hover:text-[#e11d48] transition-colors uppercase">Type Manually</button>
                    </div>

                    <div class="relative" id="wrapper_division_select">
                        <select name="division" id="division_select" class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none appearance-none transition-all uppercase">
                            <option value="">LOADING DIVISIONS...</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                    </div>

                    <input type="text" name="division" id="division_input" disabled placeholder="TYPE CUSTOM DIVISION..." 
                        class="hidden w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">PN Number *</label>
                    <input type="text" name="pn_number" placeholder="PN-00001" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all uppercase placeholder:text-slate-300">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Loan Granted *</label>
                    <input type="date" name="loan_granted" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">PN Maturity Date *</label>
                    <input type="date" name="pn_maturity" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Loan Amount (Principal) *</label>
                    <input type="number" name="loan_amount" step="0.01" placeholder="0.00" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider ml-1">Terms (Months) *</label>
                    <input type="number" name="terms" placeholder="36" required 
                        class="w-full bg-slate-50 border border-slate-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-bold text-slate-800 outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-[#e11d48] uppercase tracking-wider ml-1">Deduction Per Payday *</label>
                    <input type="number" name="deduction" step="0.01" placeholder="0.00" required 
                        class="w-full bg-red-50/30 border border-red-100 focus:border-[#e11d48]/30 focus:bg-white focus:ring-4 focus:ring-red-50 rounded-xl px-4 py-3 text-xs font-black text-[#e11d48] outline-none transition-all">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('addBorrowerModal')" 
                class="h-11 px-6 bg-slate-100 text-slate-800 rounded-full text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-slate-300 transition-all active:scale-95">
                    Cancel
                </button>
                <button type="button" onclick="validateAndShowSchedule()" 
                class="h-11 px-6 bg-[#e11d48] text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                    Next Step
                </button>
            </div>
        </form>
    </div>
</div>