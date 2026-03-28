// ==========================================
// IMPORT LEDGER MAIN: Upload UI & File Parsing
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const uploadForm         = document.getElementById('uploadLedgerForm');
    const fileInput          = document.getElementById('ledgerFile');
    const dropZone           = document.getElementById('ledgerDropZone');
    const displayFileName    = document.getElementById('displayFileName');
    const fileChip           = document.getElementById('fileChip');
    const btnClearFile       = document.getElementById('btnClearFile');
    const fileIconExt        = document.getElementById('fileIconExt');
    const btnUpload          = document.getElementById('btnUploadLedger');
    const kptnPanel          = document.getElementById('kptnPanel');
    const kptnToggle         = document.getElementById('ledgerRequiresKptnToggle');
    const kptnTogglePill     = document.getElementById('kptnTogglePill');
    const kptnContainer      = document.getElementById('ledgerKptnFieldsContainer');
    const kptnToggleLabel    = document.getElementById('ledgerToggleLabelText');
    const kptnSubText        = document.getElementById('kptnSubText');
    const kptnNumberInput    = document.getElementById('ledgerKptnNumber');
    const kptnReceiptInput   = document.getElementById('ledgerKptnReceipt');
    const kptnFileLabel      = document.getElementById('ledgerKptnFileLabel');
    const kptnDropArea       = document.getElementById('ledgerKptnDropArea');
    const depositAmountInput = document.getElementById('ledgerDepositAmount');
    const kptnWarningModal   = document.getElementById('ledgerKptnWarningModal');
    const kptnWarningMsg     = document.getElementById('ledgerKptnWarningMsg');
    const btnCloseKptnWarn   = document.getElementById('btnCloseKptnWarning');

    // Modals & Warnings
    const showModal = el => { if(el) { el.classList.remove('hidden'); el.classList.add('flex'); } };
    const hideModal = el => { if(el) { el.classList.add('hidden');    el.classList.remove('flex'); } };
    
    // Make warning globally accessible for the preview script
    window.showKptnWarning = function(msg) {
        if(kptnWarningMsg) kptnWarningMsg.textContent = msg;
        showModal(kptnWarningModal);
    };
    btnCloseKptnWarn?.addEventListener('click', () => hideModal(kptnWarningModal));

    // Auto-prefix KPTN input
    if (kptnNumberInput) {
        kptnNumberInput.addEventListener('focus', function() { if (this.value.trim() === '') this.value = 'KPTN-'; });
        kptnNumberInput.addEventListener('input', function() { this.value = 'KPTN-' + this.value.replace(/^KPTN-?/i, '').toUpperCase(); });
        kptnNumberInput.addEventListener('blur', function() { if (this.value === 'KPTN-') this.value = ''; });
    }

    // Deposit amount formatting
    function formatDeposit(raw) {
        let clean = raw.replace(/[^0-9.]/g, '');
        const parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        const int  = clean.split('.')[0] || '';
        const dec  = clean.includes('.') ? '.' + (clean.split('.')[1] ?? '') : '';
        return int.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + dec;
    }
    function getRawDeposit() { return parseFloat((depositAmountInput?.value || '').replace(/,/g, '')) || 0; }

    if (depositAmountInput) {
        depositAmountInput.addEventListener('input', function () {
            const pos = this.selectionStart, prev = this.value.length;
            this.value = formatDeposit(this.value);
            const diff = this.value.length - prev;
            this.setSelectionRange(pos + diff, pos + diff);
        });
        depositAmountInput.addEventListener('blur', function () {
            const raw = getRawDeposit();
            this.value = raw > 0 ? raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
        });
        depositAmountInput.addEventListener('focus', function () {
            const raw = getRawDeposit();
            if (raw > 0) this.value = String(raw);
        });
    }

    // Receipt file label binding
    kptnReceiptInput?.addEventListener('change', function () {
        if (this.files.length) { 
            kptnFileLabel.textContent = this.files[0].name; 
            kptnDropArea?.classList.add('has-file'); 
        } else { 
            kptnFileLabel.textContent = 'Choose file'; 
            kptnDropArea?.classList.remove('has-file'); 
        }
    });

    // KPTN toggle logic
    function applyKptnToggle(checked) {
        if (checked) {
            kptnContainer?.classList.add('open');
            if(kptnToggleLabel) { kptnToggleLabel.textContent = 'With Security Deposit'; kptnToggleLabel.classList.replace('text-slate-500', 'text-slate-800'); }
            kptnTogglePill?.classList.add('active');
            if (kptnSubText) kptnSubText.textContent = 'Enter the deposit amount collected and attach the KPTN receipt and file proof.';
        } else {
            kptnContainer?.classList.remove('open');
            if(kptnToggleLabel) { kptnToggleLabel.textContent = 'No Security Deposit'; kptnToggleLabel.classList.replace('text-slate-800', 'text-slate-500'); }
            kptnTogglePill?.classList.remove('active');
            if (kptnSubText) kptnSubText.textContent = 'No deposit required. Toggle on to attach a security deposit and KPTN receipt.';
            if (depositAmountInput) depositAmountInput.value = '';
            if (kptnNumberInput) kptnNumberInput.value = '';
            if (kptnReceiptInput) kptnReceiptInput.value = '';
            if (kptnFileLabel) kptnFileLabel.textContent = 'Choose file';
            kptnDropArea?.classList.remove('has-file');
        }
    }
    kptnToggle?.addEventListener('change', function () { applyKptnToggle(this.checked); });
    applyKptnToggle(kptnToggle?.checked ?? false);

    // File selection UI updates
    function setFileSelected(name) {
        const ext = (name.split('.').pop() || 'xlsx').toUpperCase();
        if (fileIconExt) fileIconExt.textContent = ext;
        dropZone?.classList.remove('drag-over');
        dropZone?.classList.add('file-selected');
        if (displayFileName) displayFileName.textContent = name;
        fileChip?.classList.remove('hidden');
        kptnPanel?.classList.remove('locked');
        kptnPanel?.classList.add('file-ready');
        kptnTogglePill?.classList.remove('glow');
        void kptnTogglePill?.offsetWidth; // Trigger reflow
        kptnTogglePill?.classList.add('glow');
        if (btnUpload) btnUpload.disabled = false;
    }

    function resetFileState() {
        if (fileIconExt) fileIconExt.textContent = 'XLSX';
        dropZone?.classList.remove('file-selected', 'drag-over');
        fileChip?.classList.add('hidden');
        kptnPanel?.classList.add('locked');
        kptnPanel?.classList.remove('file-ready');
        kptnTogglePill?.classList.remove('glow');
        if (btnUpload) btnUpload.disabled = true;
        if (fileInput) fileInput.value = '';
    }

    btnClearFile?.addEventListener('click', e => { e.stopPropagation(); resetFileState(); });
    fileInput?.addEventListener('change', function () { this.files.length ? setFileSelected(this.files[0].name) : resetFileState(); });

    // Drag and drop mechanics
    if (dropZone) {
        dropZone.addEventListener('click', e => { if (e.target !== fileInput && !btnClearFile?.contains(e.target)) fileInput.click(); });
        ['dragenter', 'dragover'].forEach(evt => dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); if (!dropZone.classList.contains('file-selected')) dropZone.classList.add('drag-over'); }));
        ['dragleave', 'dragend'].forEach(evt => dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.remove('drag-over'); }));
        dropZone.addEventListener('drop', e => {
            e.preventDefault(); e.stopPropagation();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            if (!['xlsx','xls','csv'].includes(file.name.split('.').pop().toLowerCase())) {
                window.showKptnWarning('Invalid file type. Please upload .XLSX, .XLS, or .CSV.'); return;
            }
            const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files;
            setFileSelected(file.name);
        });
    }

    // Phase 1 API Request: Parse the file
    uploadForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!fileInput.files.length) { window.showKptnWarning('Please select a file first.'); return; }
        
        const requiresKptn = kptnToggle?.checked ?? false;
        if (requiresKptn) {
            if (!getRawDeposit() || getRawDeposit() <= 0) { window.showKptnWarning('Please enter the security deposit amount.'); depositAmountInput?.focus(); return; }
            if (!kptnNumberInput?.value.trim()) { window.showKptnWarning('Please enter the KPTN Receipt Number.'); kptnNumberInput?.focus(); return; }
            if (!kptnReceiptInput?.files.length) { window.showKptnWarning('Please attach the KPTN receipt file.'); return; }
        }

        const formData = new FormData(); 
        formData.append('file', fileInput.files[0]);
        btnUpload.disabled = true; 
        btnUpload.textContent = 'Uploading...';
        
        const apiUrl = typeof BASE_URL !== 'undefined' ? `${BASE_URL}/public/api/parse_ledger_import.php` : '../../api/parse_ledger_import.php';

        fetch(apiUrl, { method: 'POST', body: formData })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); } catch { throw new Error('Server parse error.'); }
            })
            .then(data => {
                btnUpload.disabled = false; btnUpload.textContent = 'Upload';
                if (data.success) {
                    const parsedPayload = {
                        ...data,
                        requiresKptn: requiresKptn,
                        depositAmount: requiresKptn ? getRawDeposit() : 0,
                        kptnCode: requiresKptn ? (kptnNumberInput?.value.trim() ?? null) : null,
                        receiptFile: requiresKptn && kptnReceiptInput?.files.length ? kptnReceiptInput.files[0] : null
                    };
                    // Hand off to the preview script!
                    if(typeof window.showLedgerPreview === 'function') {
                        window.showLedgerPreview(parsedPayload);
                    }
                } else {
                    window.showKptnWarning(data.error || 'Failed to parse file.');
                }
            })
            .catch(err => {
                btnUpload.disabled = false; btnUpload.textContent = 'Upload';
                window.showKptnWarning('System Error: ' + err.message);
            });
    });
});