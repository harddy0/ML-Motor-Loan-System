document.addEventListener('DOMContentLoaded', function () {

    // ── Element refs ────────────────────────────────────────────────────
    const uploadForm        = document.getElementById('uploadLedgerForm');
    const fileInput         = document.getElementById('ledgerFile');
    const dropZone          = document.getElementById('ledgerDropZone');
    const displayFileName   = document.getElementById('displayFileName');
    const fileIconBox       = document.getElementById('fileIconBox');
    const fileIconCorner    = document.getElementById('fileIconCorner');
    const fileIconBar       = document.getElementById('fileIconBar');
    const fileIconExt       = document.getElementById('fileIconExt');
    const buttonContainer   = document.getElementById('buttonContainer');
    const kptnToggleSection = document.getElementById('kptnToggleSection');
    const btnUpload         = document.getElementById('btnUploadLedger');
    const btnConfirm        = document.getElementById('btnConfirmLedgerSave');
    const btnCancelPreview  = document.getElementById('btnCancelLedgerPreview');
    const previewModal      = document.getElementById('importLedgerPreviewModal');

    // KPTN
    const kptnToggle        = document.getElementById('ledgerRequiresKptnToggle');
    const kptnContainer     = document.getElementById('ledgerKptnFieldsContainer');
    const kptnToggleLabel   = document.getElementById('ledgerToggleLabelText');
    const kptnNumberInput   = document.getElementById('ledgerKptnNumber');
    const kptnReceiptInput  = document.getElementById('ledgerKptnReceipt');
    const kptnFileLabel     = document.getElementById('ledgerKptnFileLabel');

    // Modals
    const successModal      = document.getElementById('ledgerSuccessModal');
    const kptnWarningModal  = document.getElementById('ledgerKptnWarningModal');
    const kptnWarningMsg    = document.getElementById('ledgerKptnWarningMsg');
    const btnCloseKptnWarn  = document.getElementById('btnCloseKptnWarning');

    let parsedPayload = null;

    // ── Modal helpers ────────────────────────────────────────────────────
    function showModal(el)  { el.classList.remove('hidden'); el.classList.add('flex'); }
    function hideModal(el)  { el.classList.add('hidden');    el.classList.remove('flex'); }

    function showKptnWarning(msg) {
        kptnWarningMsg.textContent = msg;
        showModal(kptnWarningModal);
    }

    if (btnCloseKptnWarn) {
        btnCloseKptnWarn.addEventListener('click', () => hideModal(kptnWarningModal));
    }

    if (btnCancelPreview) {
        btnCancelPreview.addEventListener('click', () => hideModal(previewModal));
    }

    // ── KPTN receipt file label ──────────────────────────────────────────
    if (kptnReceiptInput) {
        kptnReceiptInput.addEventListener('change', function () {
            kptnFileLabel.textContent = this.files.length ? this.files[0].name : 'Choose file or drag here';
        });
    }

    // ── KPTN toggle show/hide ────────────────────────────────────────────
    function applyKptnToggle(checked) {
        if (checked) {
            kptnContainer.style.display = '';
            kptnToggleLabel.textContent = 'With KPTN Deposit (₱2,500) & Attachment';
            kptnToggleLabel.classList.replace('text-slate-400', 'text-slate-800');
        } else {
            kptnContainer.style.display  = 'none';
            kptnToggleLabel.textContent  = 'No Deposit Required';
            kptnToggleLabel.classList.replace('text-slate-800', 'text-slate-400');
            if (kptnNumberInput)  kptnNumberInput.value  = '';
            if (kptnReceiptInput) kptnReceiptInput.value = '';
            if (kptnFileLabel)    kptnFileLabel.textContent = 'Choose file or drag here';
        }
    }

    if (kptnToggle) {
        kptnToggle.addEventListener('change', function () { applyKptnToggle(this.checked); });
        applyKptnToggle(kptnToggle.checked);
    }

    // ── File icon state ──────────────────────────────────────────────────
    function setFileSelected(name) {
        const ext = (name.split('.').pop() || 'XLSX').toUpperCase();
        if (fileIconExt)    fileIconExt.textContent = ext;
        if (fileIconBox)    fileIconBox.classList.add('border-[#dc2626]', 'bg-white');
        if (fileIconCorner) fileIconCorner.classList.add('bg-[#dc2626]');
        if (fileIconBar)    { fileIconBar.classList.add('bg-[#dc2626]'); fileIconBar.classList.remove('bg-slate-100'); }
        if (fileIconExt)    fileIconExt.classList.add('text-white');

        displayFileName.textContent = name;
        displayFileName.classList.remove('text-[#dc2626]');
        displayFileName.classList.add('text-slate-800');

        kptnToggleSection.classList.remove('hidden');
        buttonContainer.classList.remove('hidden');
    }

    function resetFileState() {
        if (fileIconExt)    fileIconExt.textContent = 'XLSX';
        if (fileIconBox)    fileIconBox.classList.remove('border-[#dc2626]', 'bg-white');
        if (fileIconCorner) fileIconCorner.classList.remove('bg-[#dc2626]');
        if (fileIconBar)    { fileIconBar.classList.remove('bg-[#dc2626]'); fileIconBar.classList.add('bg-slate-100'); }
        if (fileIconExt)    fileIconExt.classList.remove('text-white');

        displayFileName.textContent = 'No file selected';
        displayFileName.classList.add('text-[#dc2626]');
        displayFileName.classList.remove('text-slate-800');

        kptnToggleSection.classList.add('hidden');
        buttonContainer.classList.add('hidden');
    }

    // ── File input change ────────────────────────────────────────────────
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) setFileSelected(this.files[0].name);
            else resetFileState();
        });
    }

    // ── Drag & drop on the whole drop zone ──────────────────────────────
    if (dropZone) {
        // Open file picker on click (entire zone, not just the icon)
        dropZone.addEventListener('click', function (e) {
            // Don't double-trigger if clicking the hidden input directly
            if (e.target !== fileInput) fileInput.click();
        });

        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.closest('form').classList.add('border-[#dc2626]', 'bg-slate-50');
            });
        });

        ['dragleave', 'dragend'].forEach(evt => {
            dropZone.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.closest('form').classList.remove('border-[#dc2626]', 'bg-slate-50');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.closest('form').classList.remove('border-[#dc2626]', 'bg-slate-50');

            const files = e.dataTransfer?.files;
            if (!files || files.length === 0) return;

            const file = files[0];
            const ext  = file.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                showKptnWarning('Invalid file type. Please upload an .XLSX, .XLS, or .CSV file.');
                return;
            }

            // Assign to the real input via DataTransfer
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            setFileSelected(file.name);
        });
    }

    // ── Process File (parse) ─────────────────────────────────────────────
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!fileInput.files.length) {
                showKptnWarning('Please select an Excel file first.');
                return;
            }

            const requiresKptn = kptnToggle ? kptnToggle.checked : false;

            if (requiresKptn) {
                const kptnCode = kptnNumberInput ? kptnNumberInput.value.trim() : '';
                const hasFile  = kptnReceiptInput && kptnReceiptInput.files.length > 0;

                if (!kptnCode) {
                    showKptnWarning('Please enter the KPTN Receipt Number before processing.');
                    kptnNumberInput.focus();
                    return;
                }
                if (!hasFile) {
                    showKptnWarning('Please attach the KPTN receipt file before processing.');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            btnUpload.disabled  = true;
            btnUpload.innerText = 'Analyzing...';

            fetch('../../api/parse_ledger_import.php', { method: 'POST', body: formData })
                .then(async res => {
                    const text = await res.text();
                    try { return JSON.parse(text); }
                    catch { console.error('RAW:', text); throw new Error('Server parse error — check console.'); }
                })
                .then(data => {
                    btnUpload.disabled  = false;
                    btnUpload.innerText = 'Process File';

                    if (data.success) {
                        parsedPayload = {
                            ...data,
                            requiresKptn: requiresKptn,
                            kptnCode:     requiresKptn ? (kptnNumberInput?.value.trim() ?? null) : null,
                            receiptFile:  requiresKptn && kptnReceiptInput?.files.length
                                              ? kptnReceiptInput.files[0] : null
                        };
                        renderPreviewModal(data, requiresKptn);
                    } else {
                        showKptnWarning(data.error || 'Failed to parse file.');
                    }
                })
                .catch(err => {
                    btnUpload.disabled  = false;
                    btnUpload.innerText = 'Process File';
                    showKptnWarning('System Error: ' + err.message);
                });
        });
    }

    // ── Render preview modal ─────────────────────────────────────────────
    function renderPreviewModal(data, requiresKptn) {
        const b = data.borrower;
        const fmt = n => parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('previewName').innerText     = `${b.first_name} ${b.last_name}`;
        document.getElementById('previewId').innerText       = b.employe_id       || 'N/A';
        document.getElementById('previewContact').innerText  = b.contact_number   || 'N/A';
        document.getElementById('previewRegion').innerText   = b.region           || 'N/A';
        document.getElementById('previewBranch').innerText   = b.branch           || 'N/A';
        document.getElementById('previewAmount').innerText   = '₱' + fmt(b.loan_amount);
        document.getElementById('previewDeduction').innerText = '₱' + fmt(b.semi_monthly_amortization);
        document.getElementById('previewRef').innerText      = b.reference_number || 'N/A';
        document.getElementById('previewPn').innerText       = b.pn_number        || 'N/A';
        document.getElementById('previewGranted').innerText  = b.date_released;
        document.getElementById('previewTerms').innerText    = `${b.terms} Mos.`;
        document.getElementById('previewMaturity').innerText = b.maturity_date;
        document.getElementById('previewRowCount').innerText = `${data.ledger.length} Payment Rows Parsed`;

        // KPTN badge
        const badge = document.getElementById('previewKptnBadge');
        if (badge) {
            badge.innerHTML = requiresKptn
                ? `<span class="px-3 py-1 bg-amber-100 text-amber-700 text-[11px] font-black rounded-full border border-amber-200 uppercase tracking-wide">
                       ₱2,500 Deposit · KPTN Receipt Attached
                   </span>`
                : `<span class="px-3 py-1 bg-slate-100 text-slate-500 text-[11px] font-black rounded-full border border-slate-200 uppercase tracking-wide">
                       No Deposit Required
                   </span>`;
        }

        // Ledger rows
        const tbody = document.getElementById('previewLedgerTableBody');
        tbody.innerHTML = '';
        data.ledger.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition-colors';

            let badge = 'bg-amber-100 text-amber-700', rowBg = 'bg-amber-50';
            if (row.status === 'PAID')         { badge = 'bg-green-100 text-green-700';  rowBg = 'bg-green-50'; }
            else if (row.status === 'NO DEDUCTION') { badge = 'bg-slate-200 text-slate-700'; rowBg = 'bg-slate-50'; }

            tr.innerHTML = `
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.installment_no}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-center">${row.date}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.principal)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.interest)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right font-black italic ${rowBg}">${fmt(row.total)}</td>
                <td class="px-4 py-2 border-r border-slate-100 text-right">${fmt(row.balance)}</td>
                <td class="px-4 py-2 text-center">
                    <span class="px-2 py-1 ${badge} text-[10px] font-black uppercase rounded-full tracking-wider">${row.status}</span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        showModal(previewModal);
    }

    // ── Confirm Save ─────────────────────────────────────────────────────
    if (btnConfirm) {
        btnConfirm.addEventListener('click', function () {
            if (!parsedPayload) return;

            btnConfirm.disabled  = true;
            btnConfirm.innerText = 'Saving...';

            const saveData = {
                borrower:      parsedPayload.borrower,
                ledger:        parsedPayload.ledger,
                requires_kptn: parsedPayload.requiresKptn,
                kptn_code:     parsedPayload.kptnCode
            };

            let fetchOptions;

            if (parsedPayload.requiresKptn && parsedPayload.receiptFile) {
                const fd = new FormData();
                fd.append('data', JSON.stringify(saveData));
                fd.append('kptn_receipt', parsedPayload.receiptFile);
                fetchOptions = { method: 'POST', body: fd };
            } else {
                fetchOptions = {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saveData)
                };
            }

            fetch('../../api/save_imported_ledger.php', fetchOptions)
                .then(async res => {
                    const text = await res.text();
                    try { return JSON.parse(text); }
                    catch { console.error('RAW:', text); throw new Error('Server error during save.'); }
                })
                .then(data => {
                    btnConfirm.disabled  = false;
                    btnConfirm.innerText = 'Confirm Save';

                    if (data.success) {
                        hideModal(previewModal);
                        showModal(successModal);
                    } else {
                        showKptnWarning(data.error || 'Failed to save. Please try again.');
                    }
                })
                .catch(err => {
                    btnConfirm.disabled  = false;
                    btnConfirm.innerText = 'Confirm Save';
                    showKptnWarning('System Error: ' + err.message);
                });
        });
    }

});