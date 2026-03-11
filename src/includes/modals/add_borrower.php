<div id="addBorrowerModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-md shadow-2xl border border-slate-300 overflow-hidden transform transition-all flex flex-col max-h-[95vh]">
        
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50 shrink-0">
            <h2 class="text-[14px] font-bold text-slate-700">New Borrower Entry</h2>
            <button type="button" onclick="closeModal('addBorrowerModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="addBorrowerForm" class="flex flex-col flex-1 overflow-hidden" onsubmit="event.preventDefault(); validateAndShowSchedule();" enctype="multipart/form-data">
            
            <div class="p-6 overflow-y-auto space-y-5">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Employee ID *</label>
                        <input type="text" name="employe_id" id="employe_id" placeholder="12345" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] text-slate-800 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">First Name *</label>
                        <input type="text" name="first_name" placeholder="JUAN" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Last Name *</label>
                        <input type="text" name="last_name" placeholder="DELA CRUZ" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Reference Number *</label>
                        <input type="text" name="reference_number" placeholder="REF-0000" class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase text-slate-800 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                    <div class="relative space-y-1">
                        <label class="text-[13px] text-slate-500">Region *</label>
                        <div class="relative">
                            <input type="hidden" name="region_code" id="region_code_input">
                            <input type="text" name="region" id="region_search_input" autocomplete="off" placeholder="SELECT REGION..." required
                                class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none pr-10">
                        </div>
                        <div id="region_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div id="division_container" class="relative space-y-1 hidden">
                        <label class="text-[13px] text-slate-500">Division *</label>
                        <input type="text" name="division" id="division_search_input" autocomplete="off" placeholder="SELECT DIVISION..." 
                               class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none">
                        <div id="division_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div id="branch_container" class="relative space-y-1 hidden">
                        <label class="text-[13px] text-slate-500">Branch *</label>
                        <div class="relative">
                            <input type="text" name="branch" id="branch_search_input" autocomplete="off" placeholder="WAITING FOR REGION..." 
                                class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none pr-10">
                        </div>
                        <div id="branch_results" class="hidden absolute left-0 right-0 z-50 bg-white border border-slate-300 shadow-2xl max-h-60 overflow-y-auto rounded-sm mt-1"></div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Contact Number *</label>
                        <input type="text" name="contact_number" placeholder="0900..." required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                </div>

                <div class="flex items-center mt-2 mb-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="requiresKptnToggle" name="requires_kptn" value="true" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#ce1126]"></div>
                        <span id="toggleLabelText" class="ml-3 text-[13px] font-bold text-slate-800 select-none transition-colors">Security Deposit</span>
                    </label>
                </div>

                <div id="kptnFieldsContainer" class="grid grid-cols-1 md:grid-cols-3 gap-4" style="display:none;">
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Deposit Amount*</label>
                        
                        <div class="flex items-center w-full bg-white border border-slate-300 rounded-sm px-3 focus-within:ring-1 focus-within:ring-slate-400">
                            
                            <span class="text-[13px] font-bold text-slate-500 pr-1">₱</span>
                            
                            <input type="text" 
                                step="0.01" 
                                name="deposit_amount" 
                                id="deposit_amount_input" 
                                value="2,500.00" 
                                required 
                                class="w-full bg-transparent py-2 text-[13px] font-bold text-slate-800 outline-none text-right [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">KPTN *</label>
                        <input type="text" name="kptn" id="kptn_number_input" placeholder="Enter KPTN" required class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Upload KPTN form*</label>
                        <div id="kptnDropArea" class="relative w-full bg-slate-100 text-slate-800 rounded-sm px-3 py-2 flex items-center justify-center gap-3 cursor-pointer hover:bg-[#ce1126] hover:text-white transition-colors">
                            <input type="file" name="kptn_receipt" id="kptn_receipt_input" accept="image/jpeg, image/png, application/pdf" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="flex items-center gap-2 pointer-events-none">
                                <span id="kptnFileLabel" class="text-[12px]">Choose file or drag here</span>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">JPEG, PNG, PDF</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Loan Amount *</label>
                        
                        <div class="flex items-center w-full bg-white border border-slate-300 rounded-sm px-3 focus-within:border-black">
                            
                            <span class="text-[13px] font-bold text-slate-500 pr-1">₱</span>
                            
                            <input type="number" 
                                name="loan_amount"
                                step="0.01" 
                                placeholder="0.00" 
                                required 
                                class="w-full bg-transparent py-2 text-[13px] font-semibold text-slate-700 outline-none text-right [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Date Released *</label>
                        <input type="date" name="loan_granted" required class="w-full bg-white border border-slate-300 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[13px] text-slate-500">Term(s) *</label>
                        <input type="number" name="terms" placeholder="36" required class="w-full bg-white border border-slate-300 rounded-sm px-3 py-2 text-[13px] outline-none">
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-4 bg-white shrink-0">
                <button type="button" onclick="closeModal('addBorrowerModal')" class="px-6 py-2 text-[12px] font-bold text-slate-500 hover:text-slate-800">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-[#ce1126] text-white text-[12px] font-bold rounded-lg hover:bg-[#b80c1f] transition-colors shadow-sm">Next</button>
            </div>
        </form>
    </div>
</div>

<script>
// Drag & drop + file selection for KPTN proof
;(function(){
    const dropArea = document.getElementById('kptnDropArea');
    const fileInput = document.getElementById('kptn_receipt_input');
    const fileLabel = document.getElementById('kptnFileLabel');

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

    // Click is handled by the native file input (opacity 0 overlay)
    // Handle selection via file dialog
    fileInput.addEventListener('change', (e) => updateLabel(e.target.files));

    // Drag events
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

// Deposit amount: auto-comma formatting, resets to 2,500.00 if cleared
(function() {
    const depositInput = document.getElementById('deposit_amount_input');
    if (!depositInput) return;

    depositInput.addEventListener('input', function() {
        let raw = this.value.replace(/[^\d.]/g, '');
        let [integer, decimal] = raw.split('.');
        if (!integer) integer = '';
        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        this.value = raw.includes('.') ? `${integer}.${(decimal || '').substring(0, 2)}` : integer;
    });

    depositInput.addEventListener('blur', function() {
        let raw = this.value.replace(/,/g, '');
        if (raw === '' || isNaN(raw)) {
            this.value = '2,500.00';
        } else {
            this.value = parseFloat(raw).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
})();
</script>