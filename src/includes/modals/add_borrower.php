<div id="addBorrowerModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-md shadow-2xl border border-slate-300 overflow-hidden transform transition-all">
        
        <div class="px-8 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
            <h2 class="text-[14px] font-bold text-slate-700">New Borrower Entry</h2>
            <button type="button" onclick="closeModal('addBorrowerModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="addBorrowerForm" class="p-8" onsubmit="event.preventDefault(); validateAndShowSchedule();">
            <div class="space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">First Name *</label>
                        <input type="text" name="first_name" placeholder="JUAN" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Last Name *</label>
                        <input type="text" name="last_name" placeholder="DELA CRUZ" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>
                    
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Employee ID *</label>
                        <input type="text" name="employe_id" id="employe_id" placeholder="12345" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] text-slate-800 outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Reference Number</label>
                        <input type="text" name="reference_number" placeholder="REF-0000" class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                    <div class="relative space-y-1">
                        <label class="text-[13px] text-slate-500">Division</label>
                        <input type="text" name="division" id="division_search_input" autocomplete="off" placeholder="SELECT..." 
                               class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none">
                        <div id="division_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div class="relative space-y-1">
                        <label class="text-[13px] text-slate-500">Region *</label>
                        <div class="relative">
                            <input type="text" name="region" id="region_search_input" autocomplete="off" placeholder="SELECT..." required
                                class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none pr-10">
                        </div>
                        <div id="region_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div class="relative space-y-1">
                        <label class="text-[13px] text-slate-500">Branch *</label>
                        <div class="relative">
                            <input type="text" name="branch" id="branch_search_input" autocomplete="off" placeholder="SELECT..." required
                                class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none pr-10">
                        </div>
                        <div id="branch_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Contact Number *</label>
                        <input type="text" name="contact_number" placeholder="0900..." required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50/50 p-4 rounded-md border border-slate-100">
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">PN Number</label>
                        <input type="text" name="pn_number" value="AUTO-GENERATED" readonly class="w-full bg-slate-100 border border-slate-300 rounded-sm px-3 py-2 text-[13px] font-bold text-slate-500 outline-none cursor-not-allowed">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Loan Amount *</label>
                        <input type="number" name="loan_amount" step="0.01" placeholder="0.00" required class="w-full bg-white border border-slate-300 rounded-sm px-3 py-2 text-[13px] font-semibold outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Date Released *</label>
                        <input type="date" name="loan_granted" required class="w-full bg-white border border-slate-300 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Terms (Months) *</label>
                        <input type="number" name="terms" placeholder="36" required class="w-full bg-white border border-slate-300 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-10 pt-6 border-t border-slate-100">
                <button type="button" onclick="closeModal('addBorrowerModal')" class="px-6 py-2 text-[12px] font-bold text-slate-500 hover:text-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white text-[12px] font-bold rounded-sm hover:bg-red-800 transition-colors">Calculate Amortization</button>
            </div>
        </form>
    </div>
</div>