<div id="attachKptnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="border-b border-slate-400 px-8 py-5 flex justify-between items-center">
            <h2 class="text-slate-800 font-black text-sm tracking-widest">Verify & Activate Loan</h2>
            <button onclick="closeModal('attachKptnModal')" class="text-slate-600 hover:text-[#ce1126] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8">
            <p class="text-[14px] text-slate-500 mb-6">You are validating <span id="ak_borrower_name" class="font-bold text-slate-900"></span>'s loan. Attaching the KPTN receipt will generate the amortization schedule.</p>
            
            <form id="attachKptnForm" class="space-y-5">
                <input type="hidden" id="ak_loan_id" name="loan_id">
                
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">KPTN Number</label>
                    <input type="text" id="ak_kptn_number" name="kptn_number" required readonly 
                        class="w-full bg-slate-100 border border-slate-200 text-slate-600 font-bold text-sm rounded-xl px-4 py-3 cursor-not-allowed outline-none focus:ring-0">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Upload Deposit Receipt <span class="text-red-500">*</span></label>
                    <div id="akKptnDropArea" class="relative w-full bg-slate-100 text-slate-800 rounded-xl px-3 py-3 flex items-center justify-center gap-3 cursor-pointer hover:bg-[#ce1126] hover:text-white transition-colors">
                        <input type="file" id="ak_kptn_receipt" name="kptn_receipt" required accept="image/png, image/jpeg, application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="flex items-center gap-2 pointer-events-none">
                            <span id="akKptnFileLabel" class="text-[13px]">Choose file or drag it here</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 ml-1">Accepted formats: JPG, PNG, PDF (Max 5MB)</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('attachKptnModal')" class="px-5 py-2 text-[12px] font-bold text-slate-500 hover:bg-slate-300 hover:text-slate-800  rounded-full transition-colors">Cancel</button>
                    <button type="submit" id="btnSubmitKptn" class="px-5 py-2 bg-[#ce1126] hover:bg-[#bd0217] text-white text-[12px] font-bold tracking-widest rounded-full transition-all active:scale-95">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Drag & drop + file selection for Attach KPTN
;(function(){
    const dropArea = document.getElementById('akKptnDropArea');
    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');

    if (!dropArea || !fileInput || !fileLabel) return;

    function updateLabel(files) {
        if (!files || files.length === 0) {
            fileLabel.textContent = 'Choose file or drag it here';
        } else if (files.length === 1) {
            fileLabel.textContent = files[0].name;
        } else {
            fileLabel.textContent = `${files.length} files selected`;
        }
    }

    fileInput.addEventListener('change', (e) => updateLabel(e.target.files));

    ['dragenter', 'dragover'].forEach(evt => {
        dropArea.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.add('ring', 'ring-2', 'ring-[#ce1126]');
        });
    });

    ['dragleave', 'dragend', 'drop'].forEach(evt => {
        dropArea.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.remove('ring', 'ring-2', 'ring-[#ce1126]');
        });
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (!dt || !dt.files) return;
        fileInput.files = dt.files;
        updateLabel(dt.files);
    });
})();
</script>