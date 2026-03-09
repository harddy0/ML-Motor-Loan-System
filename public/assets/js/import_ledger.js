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

        const safeSetText = (id, text) => { 
            const el = document.getElementById(id); 
            if (el) el.textContent = text; 
        };

        // Borrower info (left column)
        safeSetText('previewName', `${b.first_name} ${b.last_name}`);
        safeSetText('previewId', b.employe_id || 'N/A');
        safeSetText('previewContact', b.contact_number || 'N/A');
        safeSetText('previewRegion', b.region || 'N/A');
        safeSetText('previewBranch', b.branch || 'N/A');
        safeSetText('previewRef', b.reference_number || 'N/A');
        safeSetText('previewGranted', b.date_released || 'N/A');
        safeSetText('previewMaturity', b.maturity_date || 'N/A');

        // Dynamic PN Number Visibility
        const pnElement = document.getElementById('previewPn');
        if (pnElement) {
            if (b.pn_number && b.pn_number.trim() !== '') {
                // If Excel has a PN, show it
                pnElement.textContent = b.pn_number;
                pnElement.parentElement.style.display = 'block'; 
            } else {
                // If Excel was empty, hide the entire wrapper (e.g., the parent div) from the UI
                pnElement.parentElement.style.display = 'none';
            }
        }

        // Loan details (center)
        safeSetText('previewAmount', '₱ ' + fmt(b.loan_amount));
        safeSetText('previewDeduction', '₱ ' + fmt(b.semi_monthly_amortization));
        safeSetText('previewTerms', `${b.terms} Months`);

        // Add-on rate
        const addOnRateDecimal = parseFloat(b.add_on_rate) || 0;
        const termMonths       = parseInt(b.terms) || 0;
        const totalRatePct     = (addOnRateDecimal * termMonths * 100).toFixed(0);
        safeSetText('previewRate', totalRatePct + '%');

        // Security deposit row + badge
        const depositWrapper = document.getElementById('preview-security-deposit-wrapper');
        const depositAmtEl   = document.getElementById('previewDepositAmount');
        const depositAmt     = requiresKptn ? (getRawDeposit() || 0) : 0;

        if (depositWrapper) depositWrapper.style.display = requiresKptn && depositAmt > 0 ? 'flex' : 'none';
        if (depositAmtEl)   depositAmtEl.textContent     = '₱ ' + fmt(depositAmt);

        const badge = document.getElementById('previewKptnBadge');
        if (badge) {
            badge.innerHTML = requiresKptn
                ? `<span class="px-3 py-1 bg-rose-50 text-[#ce1126] text-[11px] font-black rounded-full border border-rose-200 uppercase tracking-wide">With Security Deposit</span>`
                : `<span class="px-3 py-1 bg-slate-100 text-slate-500 text-[11px] font-black rounded-full border border-slate-200 uppercase tracking-wide">No Security Deposit</span>`;
        }

        // Compute payment summary from ledger rows
        let totalPrincipal = 0, totalInterest = 0;
        let paidPrincipal  = 0, paidInterest  = 0, totalCollected = 0;

        data.ledger.forEach(row => {
            const p = parseFloat(row.principal) || 0;
            const i = parseFloat(row.interest)  || 0;
            const t = parseFloat(row.total)     || 0;
            totalPrincipal += p;
            totalInterest  += i;
            if ((row.status || '').toUpperCase() === 'PAID') {
                paidPrincipal  += p;
                paidInterest   += i;
                totalCollected += t;
            }
        });

        const principalBalance   = totalPrincipal - paidPrincipal;
        const interestBalance    = totalInterest  - paidInterest;
        const totalOutstanding   = principalBalance + interestBalance;

        const safeSetVal = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = '₱ ' + fmt(val); };
        safeSetVal('preview-principal-paid',     paidPrincipal);
        safeSetVal('preview-principal-balance',  principalBalance);
        safeSetVal('preview-interest-paid',      paidInterest);
        safeSetVal('preview-interest-balance',   interestBalance);
        safeSetVal('preview-total-collected',    totalCollected);
        safeSetVal('preview-total-outstanding',  totalOutstanding);

        // Amortization table
        const tbody = document.getElementById('previewLedgerTableBody');
        tbody.innerHTML = '';
        data.ledger.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-200 transition-colors border-b border-slate-100';

            const statusClean = (row.status || '').toUpperCase();
            const isPaid      = statusClean === 'PAID';
            const isNoDeduct  = statusClean === 'NO DEDUCTION';

            let statusBadgeCls = 'text-yellow-700 border border-yellow-200';
            if (isPaid)                                  statusBadgeCls = 'text-emerald-700 border border-emerald-200';
            else if (statusClean === 'VOIDED')           statusBadgeCls = 'text-orange-700 border border-orange-200';
            else if (isNoDeduct)                         statusBadgeCls = 'text-slate-700 border border-slate-200';
            else if (statusClean === 'UNPAID' || statusClean === 'MISSED') statusBadgeCls = 'text-red-700 border border-red-200';

            const balColor = isPaid ? '!text-slate-900' : '!text-[#e11d48]';

            const rawDate = row.date || '';
            const parsedDate = rawDate ? new Date(rawDate + 'T00:00:00') : null;
            const formattedDate = parsedDate && !isNaN(parsedDate)
                ? parsedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                : (rawDate || '--');

            tr.innerHTML = `
                <td class="w-[5%] px-3 py-0 text-center text-slate-600 border-r border-slate-50 font-mono">${row.installment_no ?? '--'}</td>
                <td class="w-[16%] px-3 py-0 text-center text-slate-600 border-r border-slate-50 font-mono">${formattedDate}</td>
                <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-4 font-mono">${fmt(row.principal)}</td>
                <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-4 font-mono">${fmt(row.interest)}</td>
                <td class="w-[15%] px-3 py-0 text-right text-slate-900 border-r border-slate-50 bg-slate-50/10 font-mono pr-4">${fmt(row.total)}</td>
                <td class="w-[15%] px-3 py-0 text-right border-r border-slate-50 ${balColor} pr-4 font-mono">${fmt(row.balance)}</td>
                <td class="w-[10%] px-3 py-0 text-center border-r border-slate-50">
                    <span style="font-size:11px;font-weight:600;" class="inline-block px-2 py-0.5 rounded-full ${statusBadgeCls}">
                        ${statusClean === 'VOIDED' ? 'VOID' : statusClean}
                    </span>
                </td>
                <td class="px-3 py-0 text-slate-500 text-left font-mono truncate">${row.remarks || ''}</td>`;
            tbody.appendChild(tr);
        });

        // Full-screen modal uses flex-col instead of flex
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');

        // Wire the footer Cancel button (the X button uses btnCancelLedgerPreview wired elsewhere)
        document.getElementById('btnCancelLedgerPreview2')?.addEventListener('click', () => hideModal(previewModal));
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