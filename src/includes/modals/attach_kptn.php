<div id="attachKptnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-red-50 border-b border-red-100 px-8 py-5 flex justify-between items-center">
            <h2 class="text-red-900 uppercase font-black text-sm tracking-widest">Verify & Activate Loan</h2>
            <button onclick="closeModal('attachKptnModal')" class="text-red-400 hover:text-red-800 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8">
            <p class="text-[13px] text-slate-500 mb-6">You are validating <span id="ak_borrower_name" class="font-bold text-slate-900"></span>'s loan. Attaching the KPTN receipt will generate the amortization schedule.</p>
            
            <form id="attachKptnForm" class="space-y-5">
                <input type="hidden" id="ak_loan_id" name="loan_id">
                
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">KPTN Number <span class="text-red-500">*</span></label>
                    <input type="text" id="ak_kptn_number" name="kptn_number" required placeholder="e.g. KPTN-2026-88192" 
                        class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all placeholder:text-slate-300">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Upload Deposit Receipt <span class="text-red-500">*</span></label>
                    <input type="file" id="ak_kptn_receipt" name="kptn_receipt" required accept="image/png, image/jpeg, application/pdf"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-[11px] file:font-bold file:uppercase file:tracking-wider file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition-all cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50">
                    <p class="text-[10px] text-slate-400 mt-2 ml-1">Accepted formats: JPG, PNG, PDF (Max 5MB)</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('attachKptnModal')" class="px-6 py-3 text-[12px] font-bold text-slate-500 hover:bg-slate-50 rounded-full transition-colors">Cancel</button>
                    <button type="submit" id="btnSubmitKptn" class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white text-[12px] font-bold uppercase tracking-widest rounded-full shadow-lg shadow-red-500/30 transition-all active:scale-95">Verify & Activate</button>
                </div>
            </form>
        </div>
    </div>
</div>