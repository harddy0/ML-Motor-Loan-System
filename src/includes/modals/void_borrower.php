<div id="customVoidModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="bg-orange-50 px-6 py-4 border-b border-orange-100 flex items-center gap-3">
            <div class="p-2 bg-orange-100 text-orange-600 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-slate-800 font-bold text-lg">Void Loan Record</h3>
                <p class="text-xs text-orange-600 font-bold uppercase tracking-wider" id="cvm_borrower_name">Borrower Name</p>
            </div>
        </div>
        
        <form action="/ML-MOTOR-LOAN-SYSTEM/public/actions/delete_borrower.php" method="POST" class="p-6">
            <input type="hidden" name="action" value="void">
            <input type="hidden" name="employe_id" id="cvm_employe_id" value="">
            <input type="hidden" name="borrower_name" id="cvm_borrower_name_input" value="">

            <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                You are about to <span class="font-bold text-orange-600">VOID</span> all active loans, ledgers, and deduction records for this borrower. This action will be logged.
            </p>

            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Reason for Voiding <span class="text-red-500">*</span></label>
            <textarea name="void_reason" id="cvm_reason" required rows="3" class="w-full border border-slate-300 rounded-xl p-3 text-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition-all shadow-inner" placeholder="E.g., Incorrect amount encoded, Duplicate entry, etc."></textarea>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeModal('customVoidModal')" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm hover:bg-slate-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 py-3 bg-orange-500 text-white rounded-full text-xs font-bold uppercase tracking-wider shadow-md hover:bg-orange-600 transition-all">Confirm Void</button>
            </div>
        </form>
    </div>
</div>