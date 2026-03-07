document.addEventListener('DOMContentLoaded', function () {

    // ── Refs ─────────────────────────────────────────────────────────────
    const uploadForm         = document.getElementById('uploadLedgerForm');
    const fileInput          = document.getElementById('ledgerFile');
    const dropZone           = document.getElementById('ledgerDropZone');
    const displayFileName    = document.getElementById('displayFileName');
    const fileChip           = document.getElementById('fileChip');
    const btnClearFile       = document.getElementById('btnClearFile');
    const fileIconCorner     = document.getElementById('fileIconCorner');
    const fileIconBar        = document.getElementById('fileIconBar');
    const fileIconExt        = document.getElementById('fileIconExt');
    const btnUpload          = document.getElementById('btnUploadLedger');
    const btnConfirm         = document.getElementById('btnConfirmLedgerSave');
    const btnCancelPreview   = document.getElementById('btnCancelLedgerPreview');
    const previewModal       = document.getElementById('importLedgerPreviewModal');
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
    const successModal       = document.getElementById('ledgerSuccessModal');
    const kptnWarningModal   = document.getElementById('ledgerKptnWarningModal');
    const kptnWarningMsg     = document.getElementById('ledgerKptnWarningMsg');
    const btnCloseKptnWarn   = document.getElementById('btnCloseKptnWarning');

    let parsedPayload = null;

    // ── Modal helpers ─────────────────────────────────────────────────────
    const showModal = el => { el.classList.remove('hidden'); el.classList.add('flex'); };
    const hideModal = el => { el.classList.add('hidden');    el.classList.remove('flex'); };

    function showKptnWarning(msg) {
        kptnWarningMsg.textContent = msg;
        showModal(kptnWarningModal);
    }

    btnCloseKptnWarn?.addEventListener('click', () => hideModal(kptnWarningModal));
    btnCancelPreview?.addEventListener('click', () => hideModal(previewModal));

    // ── Deposit — comma formatting ────────────────────────────────────────
    function formatDeposit(raw) {
        let clean = raw.replace(/[^0-9.]/g, '');
        const parts = clean.split('.');
        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
        const int  = clean.split('.')[0] || '';
        const dec  = clean.includes('.') ? '.' + (clean.split('.')[1] ?? '') : '';
        return int.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + dec;
    }

    function getRawDeposit() {
        return parseFloat((depositAmountInput?.value || '').replace(/,/g, '')) || 0;
    }

    if (depositAmountInput) {
        depositAmountInput.addEventListener('input', function () {
            const pos  = this.selectionStart;
            const prev = this.value.length;
            this.value = formatDeposit(this.value);
            const diff = this.value.length - prev;
            this.setSelectionRange(pos + diff, pos + diff);
        });
        depositAmountInput.addEventListener('blur', function () {
            const raw = getRawDeposit();
            this.value = raw > 0
                ? raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '';
        });
        depositAmountInput.addEventListener('focus', function () {
            const raw = getRawDeposit();
            if (raw > 0) this.value = String(raw);
        });
    }

    // ── Receipt file label ────────────────────────────────────────────────
    kptnReceiptInput?.addEventListener('change', function () {
        if (this.files.length) {
            kptnFileLabel.textContent = this.files[0].name;
            kptnDropArea?.classList.add('has-file');
        } else {
            kptnFileLabel.textContent = 'Choose file';
            kptnDropArea?.classList.remove('has-file');
        }
    });

    // ── KPTN toggle ───────────────────────────────────────────────────────
    function applyKptnToggle(checked) {
        if (checked) {
            kptnContainer.classList.add('open');
            kptnToggleLabel.textContent = 'With Security Deposit';
            kptnToggleLabel.classList.replace('text-slate-500', 'text-slate-800');
            kptnTogglePill?.classList.add('active');
            if (kptnSubText) kptnSubText.textContent = 'Enter the deposit amount collected and attach the KPTN receipt and file proof.';
        } else {
            kptnContainer.classList.remove('open');
            kptnToggleLabel.textContent = 'No Security Deposit';
            kptnToggleLabel.classList.replace('text-slate-800', 'text-slate-500');
            kptnTogglePill?.classList.remove('active');
            if (kptnSubText) kptnSubText.textContent = 'No deposit required. Toggle on to attach a security deposit and KPTN receipt.';
            if (depositAmountInput) depositAmountInput.value = '';
            if (kptnNumberInput)    kptnNumberInput.value    = '';
            if (kptnReceiptInput)   kptnReceiptInput.value   = '';
            if (kptnFileLabel)      kptnFileLabel.textContent = 'Choose file';
            kptnDropArea?.classList.remove('has-file');
        }
    }

    kptnToggle?.addEventListener('change', function () { applyKptnToggle(this.checked); });
    applyKptnToggle(kptnToggle?.checked ?? false);

    // ── File selected / reset ─────────────────────────────────────────────
    function setFileSelected(name) {
        const ext = (name.split('.').pop() || 'xlsx').toUpperCase();
        if (fileIconExt) fileIconExt.textContent = ext;
        dropZone.classList.remove('drag-over');
        dropZone.classList.add('file-selected');

        if (displayFileName) displayFileName.textContent = name;
        fileChip?.classList.remove('hidden');

        // Unlock panel + pulse the toggle pill
        kptnPanel?.classList.remove('locked');
        kptnPanel?.classList.add('file-ready');
        kptnTogglePill?.classList.remove('glow');
        void kptnTogglePill?.offsetWidth;
        kptnTogglePill?.classList.add('glow');

        if (btnUpload) btnUpload.disabled = false;
    }

    function resetFileState() {
        if (fileIconExt) fileIconExt.textContent = 'XLSX';
        dropZone.classList.remove('file-selected', 'drag-over');
        fileChip?.classList.add('hidden');

        kptnPanel?.classList.add('locked');
        kptnPanel?.classList.remove('file-ready');
        kptnTogglePill?.classList.remove('glow');

        if (btnUpload) btnUpload.disabled = true;
        if (fileInput) fileInput.value = '';
    }

    btnClearFile?.addEventListener('click', e => { e.stopPropagation(); resetFileState(); });

    // ── File input ────────────────────────────────────────────────────────
    fileInput?.addEventListener('change', function () {
        this.files.length ? setFileSelected(this.files[0].name) : resetFileState();
    });

    // ── Drag & drop ───────────────────────────────────────────────────────
    if (dropZone) {
        dropZone.addEventListener('click', e => {
            if (e.target !== fileInput && !btnClearFile?.contains(e.target)) fileInput.click();
        });

        ['dragenter', 'dragover'].forEach(evt =>
            dropZone.addEventListener(evt, e => {
                e.preventDefault(); e.stopPropagation();
                if (!dropZone.classList.contains('file-selected')) dropZone.classList.add('drag-over');
            })
        );

        ['dragleave', 'dragend'].forEach(evt =>
            dropZone.addEventListener(evt, e => {
                e.preventDefault(); e.stopPropagation();
                dropZone.classList.remove('drag-over');
            })
        );

        dropZone.addEventListener('drop', e => {
            e.preventDefault(); e.stopPropagation();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            if (!['xlsx','xls','csv'].includes(file.name.split('.').pop().toLowerCase())) {
                showKptnWarning('Invalid file type. Please upload .XLSX, .XLS, or .CSV.');
                return;
            }
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            setFileSelected(file.name);
        });
    }

    // ── Submit / parse ────────────────────────────────────────────────────
    uploadForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!fileInput.files.length) { showKptnWarning('Please select a file first.'); return; }

        const requiresKptn = kptnToggle?.checked ?? false;
        if (requiresKptn) {
            if (!getRawDeposit() || getRawDeposit() <= 0) {
                showKptnWarning('Please enter the security deposit amount.');
                depositAmountInput?.focus(); return;
            }
            if (!kptnNumberInput?.value.trim()) {
                showKptnWarning('Please enter the KPTN Receipt Number.');
                kptnNumberInput?.focus(); return;
            }
            if (!kptnReceiptInput?.files.length) {
                showKptnWarning('Please attach the KPTN receipt file.'); return;
            }
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        btnUpload.disabled  = true;
        btnUpload.textContent = 'Analyzing...';

        fetch('../../api/parse_ledger_import.php', { method: 'POST', body: formData })
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); }
                catch { console.error('RAW:', text); throw new Error('Server parse error.'); }
            })
            .then(data => {
                btnUpload.disabled   = false;
                btnUpload.textContent = 'Process File';
                if (data.success) {
                    parsedPayload = {
                        ...data,
                        requiresKptn:  requiresKptn,
                        depositAmount: requiresKptn ? getRawDeposit() : 0,
                        kptnCode:      requiresKptn ? (kptnNumberInput?.value.trim() ?? null) : null,
                        receiptFile:   requiresKptn && kptnReceiptInput?.files.length ? kptnReceiptInput.files[0] : null
                    };
                    renderPreviewModal(data, requiresKptn);
                } else {
                    showKptnWarning(data.error || 'Failed to parse file.');
                }
            })
            .catch(err => {
                btnUpload.disabled   = false;
                btnUpload.textContent = 'Process File';
                showKptnWarning('System Error: ' + err.message);
            });
    });

    // ── Preview modal ─────────────────────────────────────────────────────
    function renderPreviewModal(data, requiresKptn) {
        const b   = data.borrower;
        const fmt = n => parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('previewName').textContent      = `${b.first_name} ${b.last_name}`;
        document.getElementById('previewId').textContent        = b.employe_id           || 'N/A';
        document.getElementById('previewContact').textContent   = b.contact_number       || 'N/A';
        document.getElementById('previewRegion').textContent    = b.region               || 'N/A';
        document.getElementById('previewBranch').textContent    = b.branch               || 'N/A';
        document.getElementById('previewAmount').textContent    = '₱' + fmt(b.loan_amount);
        document.getElementById('previewDeduction').textContent = '₱' + fmt(b.semi_monthly_amortization);
        document.getElementById('previewRef').textContent       = b.reference_number     || 'N/A';
        document.getElementById('previewPn').textContent        = b.pn_number            || 'N/A';
        document.getElementById('previewGranted').textContent   = b.date_released;
        document.getElementById('previewTerms').textContent     = `${b.terms} Mos.`;
        document.getElementById('previewMaturity').textContent  = b.maturity_date;
        document.getElementById('previewRowCount').textContent  = `${data.ledger.length} Payment Rows Parsed`;

        const badge = document.getElementById('previewKptnBadge');
        if (badge) {
            badge.innerHTML = requiresKptn
                ? `<span class="px-3 py-1 bg-amber-100 text-amber-700 text-[11px] font-black rounded-full border border-amber-200 uppercase tracking-wide">· KPTN Receipt Attached</span>`
                : `<span class="px-3 py-1 bg-slate-100 text-slate-500 text-[11px] font-black rounded-full border border-slate-200 uppercase tracking-wide">No Deposit Required</span>`;
        }

        const tbody = document.getElementById('previewLedgerTableBody');
        tbody.innerHTML = '';
        data.ledger.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition-colors';
            let badgeCls = 'bg-amber-100 text-amber-700', rowBg = '';
            if (row.status === 'PAID')             { badgeCls = 'bg-green-100 text-green-700';  rowBg = 'bg-green-50'; }
            else if (row.status === 'NO DEDUCTION') { badgeCls = 'bg-slate-200 text-slate-700'; rowBg = 'bg-slate-50'; }
            tr.innerHTML = `
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.installment_no}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.date}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.principal)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.interest)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right font-black italic ${rowBg}">${fmt(row.total)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.balance)}</td>
                <td class="px-4 py-2 text-center">
                    <span class="px-2 py-1 ${badgeCls} text-[10px] font-black uppercase rounded-full tracking-wider">${row.status}</span>
                </td>`;
            tbody.appendChild(tr);
        });

        showModal(previewModal);
    }

    // ── Confirm save ──────────────────────────────────────────────────────
    btnConfirm?.addEventListener('click', function () {
        if (!parsedPayload) return;
        btnConfirm.disabled     = true;
        btnConfirm.textContent  = 'Saving...';

        const saveData = {
            borrower:       parsedPayload.borrower,
            ledger:         parsedPayload.ledger,
            requires_kptn:  parsedPayload.requiresKptn,
            deposit_amount: parsedPayload.depositAmount ?? 0,
            kptn_code:      parsedPayload.kptnCode
        };

        let fetchOptions;
        if (parsedPayload.requiresKptn && parsedPayload.receiptFile) {
            const fd = new FormData();
            fd.append('data', JSON.stringify(saveData));
            fd.append('kptn_receipt', parsedPayload.receiptFile);
            fetchOptions = { method: 'POST', body: fd };
        } else {
            fetchOptions = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(saveData) };
        }

        fetch('../../api/save_imported_ledger.php', fetchOptions)
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); }
                catch { console.error('RAW:', text); throw new Error('Server error during save.'); }
            })
            .then(data => {
                btnConfirm.disabled    = false;
                btnConfirm.textContent = 'Confirm Save';
                if (data.success) { hideModal(previewModal); showModal(successModal); }
                else              { showKptnWarning(data.error || 'Failed to save. Please try again.'); }
            })
            .catch(err => {
                btnConfirm.disabled    = false;
                btnConfirm.textContent = 'Confirm Save';
                showKptnWarning('System Error: ' + err.message);
            });
    });

});