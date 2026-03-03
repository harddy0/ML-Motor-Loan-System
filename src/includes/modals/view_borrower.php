<div id="viewBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl max-h-[95vh] flex flex-col rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <h2 class="text-[14px] font-black text-slate-800 uppercase tracking-widest">
                Account Information View
            </h2>
            <button onclick="closeModal('viewBorrowerModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8 overflow-y-auto">
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-[#ce1126] font-black text-[14px] tracking-widest uppercase">Personal Information</h3>
                <div class="h-px flex-1 bg-slate-100"></div>
            </div>

            <div class="overflow-x-auto mb-8">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Employee ID</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">First Name</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Last Name</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Contact Number</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Region</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <tr>
                            <td class="p-3 bg-white border border-slate-100"><p id="m-id" class="text-[14px] text-slate-900 uppercase font-bold"></p></td>
                            <td class="p-3 bg-white border border-slate-100"><p id="m-fname" class="text-[14px] text-slate-900 uppercase"></p></td>
                            <td class="p-3 bg-white border border-slate-100"><p id="m-lname" class="text-[14px] text-slate-900 uppercase"></p></td>
                            <td class="p-3 bg-white border border-slate-100"><p id="m-contact" class="text-[14px] text-slate-900 uppercase"></p></td>
                            <td class="p-3 bg-white border border-slate-100"><p id="m-region" class="text-[14px] text-slate-900 lowercase first-letter:uppercase"></p></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-[#e11d48] font-black text-[14px]  tracking-widest uppercase">Loan Information</h3>
                <div class="h-px flex-1 bg-slate-100"></div>
            </div>

            <div class="overflow-x-auto mb-8">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">PN Number</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Date Released</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">PN Maturity</th>
                            <th class="px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Terms (Months)</th>
                            <th class="text-right px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Total Loan Amount</th>
                            <th class="text-right px-5 py-1 bg-[#ce1126] border border-slate-100 text-[14px] text-white tracking-wider">Deduction Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center p-3 bg-white border border-slate-100"><p id="m-pn-no" class="text-[14px] text-slate-900"></p></td>
                            <td class="text-center p-3 bg-white border border-slate-100"><p id="m-date" class="text-[14px] text-slate-900"></p></td>
                            <td class="text-center p-3 bg-white border border-slate-100"><p id="m-pn-mat" class="text-[14px] text-slate-900"></p></td>
                            <td class="text-center p-3 bg-white border border-slate-100"><p id="m-terms" class="text-[14px] text-slate-900"></p></td>
                            <td class="text-right p-3 bg-white border border-slate-100"><p id="m-amount" class="text-[14px] text-slate-900 font-bold"></p></td>
                            <td class="text-right p-3 bg-white border border-slate-100"><p id="m-deduct" class="text-[14px] text-[#ce1126] font-bold"></p></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-slate-700 font-black text-[14px] tracking-widest uppercase">KPTN Deposit Document</h3>
                <div class="h-px flex-1 bg-slate-100"></div>
            </div>
            
            <div id="document-viewer-container" class="w-full bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-center min-h-[400px] overflow-hidden p-2">
                <span class="text-slate-400 italic font-medium tracking-wide">Loading document preview...</span>
            </div>

        </div> <div class="px-8 py-4 flex justify-end gap-3 border-t border-slate-100 shrink-0 bg-slate-50">
            <?php if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])): ?>
                <button type="button" id="btnOpenVoidModal" onclick="openVoidConfirmationModal()" class="h-8 px-5 bg-white border border-slate-200 text-slate-800 rounded-full text-[13px] tracking-widest shadow-sm hover:bg-slate-200 flex items-center gap-2 mr-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Void Loan
                </button>
            <?php endif; ?>

            <button class="h-8 px-5 bg-[#ce1126] text-white rounded-full text-[13px] font-medium tracking-widest shadow-md hover:bg-[#b20c1e] transition-all active:scale-95">
                Print PDF
            </button>
            <button onclick="closeModal('viewBorrowerModal')" class="h-8 px-5 bg-white border border-slate-200 text-slate-800 rounded-full text-[13px] tracking-widest shadow-sm hover:bg-slate-200 transition-all active:scale-95">
                Close
            </button>
        </div>
    </div>
</div>