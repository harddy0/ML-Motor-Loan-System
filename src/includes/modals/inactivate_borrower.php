<div id="inactivateModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="p-2 bg-slate-100 text-slate-600 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 6v.01"></path></svg>
            </div>
            <div>
                <h3 class="text-slate-800 text-[13px]">Inactivate Borrower</h3>
                <p class="text-[16px] text-slate-800 font-bold uppercase tracking-wider" id="ivm_borrower_name">Borrower Name</p>
            </div>
        </div>
        <div class="p-6">
            <p class="text-[14px] text-slate-600 mb-4 leading-relaxed">
                This will mark the borrower's loan as <span class="text-slate-800">INACTIVE</span>. This action can be reversed by an administrator if needed.
            </p>

            <label class="block text-[14px] text-slate-700 tracking-wider mb-2">Reason for Inactivation <span class="text-red-500">*</span></label>
            <select id="ivm_reason" class="w-full border border-[#ce1126] rounded-xl p-3 text-sm mb-4" required>
                <option value="">-- Select reason --</option>
                <option value="AWOL">AWOL or Absent Without Leave</option>
                <option value="RESIGNED">Resigned</option>
            </select>

            <div class="flex justify-between gap-3 mt-6">
                <button type="button" onclick="closeModal('inactivateModal')" class="px-5 py-1 bg-slate-100 text-slate-700 rounded-full text-[13px] tracking-wider shadow-sm hover:bg-slate-200 transition-all">Cancel</button>
                <button type="button" id="btnConfirmInactivate" onclick="confirmInactivateBorrower()" class="px-5 py-1 bg-[#ce1126] text-white rounded-full text-[13px] tracking-wider shadow-md hover:bg-[#b20c1e] transition-all">Confirm Inactivate</button>
            </div>
        </div>
    </div>

            <!-- Success Modal -->
            <div id="successModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-slate-800 text-[14px] font-bold">Success</h3>
                    </div>
                    <div class="p-6">
                        <p id="successModalMessage" class="text-[14px] text-slate-700 mb-4">Operation completed successfully.</p>
                        <div class="flex justify-end">
                            <button type="button" id="btnSuccessOk" onclick="handleSuccessOk()" class="px-5 py-1 bg-[#0f1724] text-white rounded-full text-[13px] tracking-wider shadow-md hover:opacity-95 transition-all">OK</button>
                        </div>
                    </div>
                </div>
            </div>
</div>