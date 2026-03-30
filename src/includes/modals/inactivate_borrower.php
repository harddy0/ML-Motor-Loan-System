<div id="inactivateModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div>
                <h3 class="text-slate-800 text-[13px]">Inactivate Borrower</h3>
                <p class="text-[16px] text-slate-800 font-bold uppercase tracking-wider" id="ivm_borrower_name">Borrower Name</p>
            </div>
        </div>
        <div class="p-6">
            <p class="text-[14px] text-slate-600 mb-4 leading-relaxed">
                This will mark the borrower's loan as <span class="text-slate-800">INACTIVE</span>.
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
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col items-center text-center gap-3">
            <div class="h-12 w-12 rounded-full bg-emerald-500/15 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.414l2.293 2.292 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
            <h3 class="text-slate-800 text-[14px] font-bold">Success</h3>
        </div>
        <div class="p-6 text-center">
            <p id="successModalMessage" class="text-[14px] text-slate-700 mb-4">Inactivated successfully.</p>
            <div class="flex justify-center">
                <button type="button" id="btnSuccessOk" onclick="handleSuccessOk()" class="px-5 py-1 bg-[#ce1126] text-white rounded-full text-[13px] tracking-wider shadow-md hover:opacity-95 transition-all">OK</button>
            </div>
        </div>
    </div>
</div>