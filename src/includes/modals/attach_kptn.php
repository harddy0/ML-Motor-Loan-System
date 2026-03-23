<div id="attachKptnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">

        <div class="border-b border-slate-400 px-4 py-2 flex justify-between items-center">
            <h2 class="text-slate-800 font-black text-sm tracking-widest">Security Deposit</h2>
            <button type="button" onclick="closeModal('attachKptnModal')" class="text-slate-600 hover:text-[#ce1126] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-8">
            <p class="text-[14px] text-slate-500 mb-6">
                Attach the KPTN form for
                <span id="ak_borrower_name" class="font-bold text-slate-900"></span>'s loan.
            </p>

            <div class="space-y-5">
                <input type="hidden" id="ak_loan_id">

                <!-- Deposit Amount -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider mb-2">
                        Security Deposit Amount <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">₱</span>
                        <input type="text" id="ak_deposit_amount" inputmode="numeric"
                            class="w-full h-11 rounded-xl border border-slate-200 pl-8 pr-3 text-[13px] font-bold text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                            placeholder="0.00" autocomplete="off">
                    </div>
                </div>

                <!-- KPTN Number -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider mb-2">
                        KPTN <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="ak_kptn_number"
                        class="w-full h-11 rounded-xl border border-slate-200 px-3 text-[13px] font-bold uppercase text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                        placeholder="KPTN-XXXX" autocomplete="off">
                </div>

                <!-- Upload Receipt -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider mb-2">
                        KPTN Form <span class="text-red-500">*</span>
                    </label>
                    <div id="akKptnDropArea" class="relative w-full bg-slate-100 text-slate-800 rounded-xl px-3 py-3 flex items-center justify-center gap-3 cursor-pointer hover:bg-[#ce1126] hover:text-white transition-colors">
                        <input type="file" id="ak_kptn_receipt" accept="image/png,image/jpeg,application/pdf"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="flex items-center gap-2 pointer-events-none">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span id="akKptnFileLabel" class="text-[13px]">Choose file or drag here</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 ml-1">JPG, PNG, PDF — Max 5MB</p>
                </div>

                <p id="ak_error_msg" class="hidden text-[12px] text-red-500 font-semibold"></p>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('attachKptnModal')"
                        class="px-5 py-2 text-[12px] font-bold text-slate-500 hover:bg-slate-200 hover:text-slate-800 rounded-full transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btnSubmitKptn" onclick="submitAttachKptn()"
                        class="px-5 py-2 bg-[#ce1126] hover:bg-[#bd0217] text-white text-[12px] font-bold tracking-widest rounded-full transition-all active:scale-95">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File label
document.getElementById('ak_kptn_receipt').addEventListener('change', function () {
    document.getElementById('akKptnFileLabel').textContent =
        this.files.length ? this.files[0].name : 'Choose file or drag it here';
});

// Drag & drop
var _akDrop = document.getElementById('akKptnDropArea');
var _akInp  = document.getElementById('ak_kptn_receipt');
['dragenter', 'dragover'].forEach(function (ev) {
    _akDrop.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); _akDrop.classList.add('ring-2'); });
});
['dragleave', 'dragend', 'drop'].forEach(function (ev) {
    _akDrop.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); _akDrop.classList.remove('ring-2'); });
});
_akDrop.addEventListener('drop', function (e) {
    if (e.dataTransfer && e.dataTransfer.files.length) {
        _akInp.files = e.dataTransfer.files;
        document.getElementById('akKptnFileLabel').textContent = e.dataTransfer.files[0].name;
    }
});

// ─── SUBMIT ───────────────────────────────────────────────────────────────
function submitAttachKptn() {
    var btn      = document.getElementById('btnSubmitKptn');
    var errEl    = document.getElementById('ak_error_msg');
    var loanId   = document.getElementById('ak_loan_id').value.trim();
    var kptnCode = document.getElementById('ak_kptn_number').value.trim();
    var depositRaw = document.getElementById('ak_deposit_amount').value.trim();
    var depositAmount = parseFloat(depositRaw.replace(/,/g, '')) || 0;
    var fileInp  = document.getElementById('ak_kptn_receipt');
    var file     = fileInp && fileInp.files.length ? fileInp.files[0] : null;

    errEl.textContent = '';
    errEl.classList.add('hidden');

    function showErr(msg) { errEl.textContent = msg; errEl.classList.remove('hidden'); }

    if (!loanId)   { showErr('Missing loan ID. Close and try again.'); return; }
    if (!(depositAmount > 0)) { showErr('Please enter a valid security deposit amount.'); return; }
    if (!kptnCode) { showErr('KPTN number is missing.'); return; }
    if (!file)     { showErr('Please attach a receipt file (JPG, PNG or PDF).'); return; }
    if (file.size > 5 * 1024 * 1024) { showErr('File exceeds 5MB limit.'); return; }

    var origText = btn ? btn.innerText : 'Save';
    if (btn) { btn.innerText = 'Saving...'; btn.disabled = true; }

    var base = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';

    var fd = new FormData();
    fd.append('loan_id',      loanId);
    fd.append('deposit_amount', depositAmount.toFixed(2));
    fd.append('kptn_number',  kptnCode);
    fd.append('kptn_receipt', file);

    fetch(base + '/public/actions/attach_kptn.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(async function (data) {
            if (data.success) {
                if (typeof activeModalNotifId !== 'undefined' && activeModalNotifId) {
                    if (typeof lastProcessedId !== 'undefined') {
                        lastProcessedId = activeModalNotifId;
                    }
                    const readData = new FormData();
                    readData.append('notification_id', activeModalNotifId);
                    await fetch(base + '/public/api/mark_notification_read.php', {
                        method: 'POST',
                        body: readData
                    });
                    activeModalNotifId = null;
                }

                closeModal('attachKptnModal');

                var sm  = document.getElementById('successMessage');
                var sma = document.getElementById('successAlertModal');
                if (sm)  sm.innerText = 'KPTN document attached successfully.';
                if (sma) { sma.classList.remove('hidden'); sma.classList.add('flex'); }

                if (typeof loadNotifications === 'function') loadNotifications();
                if (typeof loadDashboard     === 'function') loadDashboard();
            } else {
                if (btn) { btn.innerText = origText; btn.disabled = false; }
                showErr('Error: ' + (data.error || 'Unknown server error.'));
            }
        })
        .catch(function (err) {
            console.error('submitAttachKptn:', err);
            if (btn) { btn.innerText = origText; btn.disabled = false; }
            showErr('Network error. Check connection and try again.');
        });
}

function resetAttachModal() {
    const loanIdField  = document.getElementById('ak_loan_id');
    const kptnField    = document.getElementById('ak_kptn_number');
    const depositField = document.getElementById('ak_deposit_amount');
    const borrowerLabel = document.getElementById('ak_borrower_name');
    const errorMsg     = document.getElementById('ak_error_msg');

    if (loanIdField)   loanIdField.value = '';
    if (kptnField)     kptnField.value = 'KPTN-';
    if (depositField)  depositField.value = '';
    if (borrowerLabel) borrowerLabel.innerText = '...';
    if (errorMsg)      { errorMsg.innerText = ''; errorMsg.classList.add('hidden'); }

    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');
    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.textContent = 'Choose file or drag it here';

    const btn = document.getElementById('btnSubmitKptn');
    if (btn) { btn.innerText = 'Save'; btn.disabled = false; }
}

var _akKptnInput = document.getElementById('ak_kptn_number');
if (_akKptnInput) {
    _akKptnInput.addEventListener('focus', function() {
        if (this.value.trim() === '') this.value = 'KPTN-';
    });
    _akKptnInput.addEventListener('input', function() {
        var cleanVal = this.value.replace(/^KPTN-?/i, '');
        this.value = 'KPTN-' + cleanVal.toUpperCase();
    });
    _akKptnInput.addEventListener('blur', function() {
        if (this.value === 'KPTN-') this.value = '';
    });
}

var _akDepositInput = document.getElementById('ak_deposit_amount');
if (_akDepositInput) {
    _akDepositInput.addEventListener('input', function() {
        var pos = this.selectionStart;
        var prev = this.value.length;
        var clean = this.value.replace(/[^0-9.]/g, '');
        var parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        var intPart = clean.split('.')[0] || '';
        var decPart = clean.includes('.') ? '.' + (clean.split('.')[1] || '') : '';
        this.value = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + decPart;
        var diff = this.value.length - prev;
        this.setSelectionRange(pos + diff, pos + diff);
    });

    _akDepositInput.addEventListener('blur', function() {
        var raw = parseFloat(this.value.replace(/,/g, '')) || 0;
        this.value = raw > 0
            ? raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '';
    });

    _akDepositInput.addEventListener('focus', function() {
        var raw = parseFloat(this.value.replace(/,/g, '')) || 0;
        if (raw > 0) this.value = String(raw);
    });
}
</script>