// ============================================================
// MODAL UTILITIES
// ============================================================
let confirmModalCallback = null;

function showErrorModal(message) {
    const modal = document.getElementById('errorModal');
    const messageEl = document.getElementById('errorModalMessage');
    if (!modal || !messageEl) return;
    
    messageEl.textContent = message;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const content = modal.querySelector('div > div');
        if (content) content.classList.remove('scale-95');
    }, 10);
    
    const closeBtn = modal.querySelector('button');
    if (closeBtn) closeBtn.focus();
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    const content = modal.querySelector('div > div');
    if (content) content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

function showWarningModal(message) {
    const modal = document.getElementById('warningModal');
    const messageEl = document.getElementById('warningModalMessage');
    if (!modal || !messageEl) return;
    
    messageEl.textContent = message;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const content = modal.querySelector('div > div');
        if (content) content.classList.remove('scale-95');
    }, 10);
    
    const closeBtn = modal.querySelector('button');
    if (closeBtn) closeBtn.focus();
}

function closeWarningModal() {
    const modal = document.getElementById('warningModal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    const content = modal.querySelector('div > div');
    if (content) content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const messageEl = document.getElementById('successModalMessage');
    if (!modal || !messageEl) return;
    
    messageEl.textContent = message;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const content = modal.querySelector('div > div');
        if (content) content.classList.remove('scale-95');
    }, 10);
    
    const closeBtn = modal.querySelector('button');
    if (closeBtn) closeBtn.focus();
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    const content = modal.querySelector('div > div');
    if (content) content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

function showConfirmModal(message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    const messageEl = document.getElementById('confirmModalMessage');
    if (!modal || !messageEl) return;
    
    messageEl.textContent = message;
    confirmModalCallback = onConfirm;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const content = modal.querySelector('div > div');
        if (content) content.classList.remove('scale-95');
    }, 10);
    
    const yesBtn = modal.querySelector('button:last-child');
    if (yesBtn) yesBtn.focus();
}

function closeConfirmModal(confirmed) {
    const modal = document.getElementById('confirmModal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    const content = modal.querySelector('div > div');
    if (content) content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        
        if (confirmed && typeof confirmModalCallback === 'function') {
            confirmModalCallback();
        }
        confirmModalCallback = null;
    }, 300);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const errorModal = document.getElementById('errorModal');
        const warningModal = document.getElementById('warningModal');
        const successModal = document.getElementById('successModal');
        const confirmModal = document.getElementById('confirmModal');
        
        if (errorModal && !errorModal.classList.contains('hidden')) closeErrorModal();
        else if (warningModal && !warningModal.classList.contains('hidden')) closeWarningModal();
        else if (successModal && !successModal.classList.contains('hidden')) closeSuccessModal();
        else if (confirmModal && !confirmModal.classList.contains('hidden')) closeConfirmModal(false);
    }
});

document.addEventListener('click', (e) => {
    if (e.target.id === 'errorModal') closeErrorModal();
    if (e.target.id === 'warningModal') closeWarningModal();
    if (e.target.id === 'successModal') closeSuccessModal();
    if (e.target.id === 'confirmModal') closeConfirmModal(false);
});

// ============================================================
// PAYROLL UPLOAD FLOW
// ============================================================
let parsedDeductions   = [];
let chosenPayrollDate  = null; 
let chosenDisplayDate  = '';   
let previewRowStatuses = [];   
let batchIsClean       = false;

function initUpload() {
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const display   = document.getElementById('displayFileName');
    const btnCont   = document.getElementById('buttonContainer');

    if (!dropZone || !fileInput) return;

    fileInput.value = '';
    if (display) display.innerText = 'No file selected';
    if (btnCont) btnCont.classList.add('hidden');

    ['dragenter','dragover','dragleave','drop'].forEach(ev =>
        dropZone.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }, false)
    );
    ['dragenter','dragover'].forEach(ev =>
        dropZone.addEventListener(ev, () => dropZone.classList.add('border-[#ce1126]','bg-slate-50'), false)
    );
    ['dragleave','drop'].forEach(ev =>
        dropZone.addEventListener(ev, () => dropZone.classList.remove('border-[#ce1126]','bg-slate-50'), false)
    );
    dropZone.addEventListener('drop', e => {
        const f = e.dataTransfer.files;
        if (f.length) { fileInput.files = f; updateName(fileInput); }
    });

    const mp = document.getElementById('dsMonthPicker');
    if (mp) {
        const now = new Date();
        mp.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
        updateEomLabel();
    }
}

document.addEventListener('DOMContentLoaded', initUpload);
window.addEventListener('pageshow', initUpload);

function updateName(input) {
    const display = document.getElementById('displayFileName');
    const btnCont = document.getElementById('buttonContainer');
    if (input.files && input.files[0]) {
        if (display) display.innerText = input.files[0].name;
        if (btnCont) btnCont.classList.remove('hidden');
    } else {
        if (display) display.innerText = 'No file selected';
        if (btnCont) btnCont.classList.add('hidden');
    }
}

function openDateSelectorModal() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files.length) {
        showErrorModal('Please select or drop a file first.');
        return;
    }

    chosenPayrollDate = null;
    chosenDisplayDate = '';
    batchIsClean      = false;
    document.querySelectorAll('input[name="dsPayrollHalf"]').forEach(r => r.checked = false);

    const proceedBtn = document.getElementById('dsProceedBtn');
    if (proceedBtn) { proceedBtn.disabled = true; proceedBtn.className = _btnDisabledClass(); }

    const chooseSummary = document.getElementById('dsChosenSummary');
    if (chooseSummary) chooseSummary.classList.add('hidden');

    const dsError = document.getElementById('dsError');
    if (dsError) dsError.classList.add('hidden');

    const mp = document.getElementById('dsMonthPicker');
    if (mp && !mp.value) {
        const now = new Date();
        mp.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
    }
    updateEomLabel();
    openModal('dateSelectorModal');
}

function updateEomLabel() {
    const mp  = document.getElementById('dsMonthPicker');
    const lbl = document.getElementById('dsEomLabel');
    if (!mp || !lbl) return;

    if (!mp.value) { lbl.innerText = '30th / last day'; return; }

    const [y, m] = mp.value.split('-').map(Number);
    const lastDay   = new Date(y, m, 0).getDate();
    const systemDay = Math.min(lastDay, 30); 
    lbl.innerText   = systemDay === 30 ? '30th' : `${systemDay}th`;

    const checked = document.querySelector('input[name="dsPayrollHalf"]:checked');
    if (checked) onHalfSelected(checked.value);
}

function onHalfSelected(half) {
    const mp      = document.getElementById('dsMonthPicker');
    const errBox  = document.getElementById('dsError');
    if (errBox) errBox.classList.add('hidden');

    if (!mp || !mp.value) {
        _showDsError('Please select a month first.');
        return;
    }

    const [y, m] = mp.value.split('-').map(Number);
    const day    = (half === '15') ? 15 : Math.min(new Date(y, m, 0).getDate(), 30);
    const iso    = `${y}-${String(m).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    const long   = new Date(y, m-1, day)
        .toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });

    chosenPayrollDate = iso;
    chosenDisplayDate = long;

    const txt = document.getElementById('dsChosenDateText');
    if (txt) txt.innerText = long;

    const summary = document.getElementById('dsChosenSummary');
    if (summary) { summary.classList.remove('hidden'); summary.classList.add('flex'); }

    const btn = document.getElementById('dsProceedBtn');
    if (btn) { btn.disabled = false; btn.className = _btnActiveClass(); }
}

function _showDsError(msg) {
    const box = document.getElementById('dsError');
    if (!box) return;
    box.innerText = msg;
    box.classList.remove('hidden');
}

function proceedToPreview() {
    if (!chosenPayrollDate) {
        _showDsError('Please select a payroll cutoff (15th or 30th/last day).');
        return;
    }
    closeModal('dateSelectorModal');
    _parseAndOpenPreview();
}

function _parseAndOpenPreview() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files.length) return;

    const dropZone = document.getElementById('dropZone');
    if (dropZone) dropZone.classList.add('opacity-60', 'pointer-events-none');

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('../../api/parse_payroll_deduction.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(result => {
            if (dropZone) dropZone.classList.remove('opacity-60','pointer-events-none');
            if (!result.success) { 
                showErrorModal('Error reading file:\n' + result.error); 
                return; 
            }

            parsedDeductions   = result.data;
            previewRowStatuses = _validateAllRows(parsedDeductions);
            
            // Clean batch permits ok, and matched assumed priorities
            batchIsClean = previewRowStatuses.every(s => 
                ['ok', 'assumed_exact', 'assumed_excess'].includes(s.status)
            );

            _renderPreviewHeader();
            _renderPreviewTable();
            openModal('importPreviewModal');
        })
        .catch(err => {
            if (dropZone) dropZone.classList.remove('opacity-60','pointer-events-none');
            console.error(err);
            showErrorModal('System error reading file.');
        });
}

// ─────────────────────────────────────────────────────────────
// VALIDATION RULES (Includes ASSUMED checks)
// ─────────────────────────────────────────────────────────────
function _validateAllRows(rows) {
    return rows.map(row => {
        const rawDisplay = row.date    || '';  
        const isoExcel   = row.iso_date || ''; 

        if (row.is_inactive) {
            return { status: 'invalid', excelDisplay: null, reason: `Upload Rejected. Borrower has an INACTIVE loan.` };
        }
        
        if (!isoExcel || !rawDisplay) {
            return { status:'invalid', excelDisplay: null, reason: 'Date is missing or could not be read from the Excel file.' };
        }

        // RULE 1: EXCEL DATE MUST MATCH UI DATE
        if (isoExcel !== chosenPayrollDate) {
            const excelLong = _isoToLong(isoExcel);
            const [eY, eM, eD] = isoExcel.split('-').map(Number);
            const [cY, cM, cD] = chosenPayrollDate.split('-').map(Number);

            // Correct Day/Month swaps
            if (eY === cY && eM === cD && eD === cM) {
                // If the dates swapped but mean the same thing, allow it. Let the ASSUMED block handle it below.
            } else {
                return {
                    status:       'mismatch',
                    swapDetected: false,
                    excelDisplay: excelLong,
                    reason: `Excel due date (${excelLong}) doesn't match the selected UI payroll due date (${chosenDisplayDate}).`
                };
            }
        }

        // RULE 2: ASSUMED DATE STRICT ENFORCEMENT
        if (row.has_assumed) {
            // The chosen/Excel date MUST match the earliest pending ASSUMED date.
            if (row.assumed_date !== chosenPayrollDate) {
                return {
                    status: 'assumed_date_block',
                    swapDetected: false,
                    excelDisplay: _isoToLong(isoExcel),
                    reason: `Blocked: Borrower has a prior pending ASSUMED payment for ${_isoToDisplay(row.assumed_date)}. You must upload a payroll matching that exact date first.`
                };
            }

            // Dates matched exactly. Check the amount.
            if (row.amount < row.assumed_amount) {
                return { 
                    status: 'assumed_short', 
                    swapDetected: false, excelDisplay: _isoToLong(isoExcel),
                    reason: `Blocked: Insufficient funds (₱${row.amount.toFixed(2)}) to clear priority ASSUMED balance (₱${row.assumed_amount.toFixed(2)}).` 
                };
            } else if (row.amount === row.assumed_amount) {
                return { 
                    status: 'assumed_exact', 
                    swapDetected: false, excelDisplay: _isoToLong(isoExcel),
                    reason: `Exact match for priority ASSUMED balance.` 
                };
            } else {
                return { 
                    status: 'assumed_excess', 
                    swapDetected: false, excelDisplay: _isoToLong(isoExcel),
                    reason: `Clearing priority ASSUMED balance. Excess will float.` 
                };
            }
        }

        // Standard Match
        return { 
            status:'ok', 
            swapDetected: false, 
            excelDisplay: _isoToLong(isoExcel), 
            reason:'' 
        };
    });
}

function _renderPreviewHeader() {
    const okCount       = previewRowStatuses.filter(s => s.status === 'ok').length;
    const assumedCnt    = previewRowStatuses.filter(s => ['assumed_exact', 'assumed_excess'].includes(s.status)).length;
    const invalidCnt    = previewRowStatuses.filter(s => s.status === 'invalid').length;
    const shortCnt      = previewRowStatuses.filter(s => s.status === 'assumed_short').length;
    const dateBlockCnt  = previewRowStatuses.filter(s => s.status === 'assumed_date_block').length;
    const mismatchCnt   = previewRowStatuses.filter(s => s.status === 'mismatch').length;
    const total         = previewRowStatuses.length;

    const validTotal  = okCount + assumedCnt;

    const dateLbl = document.getElementById('previewChosenDate');
    if (dateLbl) dateLbl.innerText = chosenDisplayDate;

    const statsEl = document.getElementById('previewStats');
    if (statsEl) {
        const parts = [`<span class="font-bold ${batchIsClean ? 'text-green-700' : 'text-slate-500'}">${validTotal}/${total} valid</span>`];
        if (assumedCnt   > 0) parts.push(`<span class="font-bold text-purple-700">${assumedCnt} priority handled</span>`);
        if (dateBlockCnt > 0) parts.push(`<span class="font-bold text-red-600">${dateBlockCnt} pending assumed block</span>`);
        if (shortCnt     > 0) parts.push(`<span class="font-bold text-red-600">${shortCnt} insufficient</span>`);
        if (mismatchCnt  > 0) parts.push(`<span class="font-bold text-red-600">${mismatchCnt} mismatch</span>`);
        if (invalidCnt   > 0) parts.push(`<span class="font-bold text-orange-600">${invalidCnt} invalid</span>`);
        statsEl.innerHTML = parts.join('<span class="text-slate-300 mx-1">·</span>');
    }

    const matchMsg = document.getElementById('previewMatchMsg');
    if (matchMsg) {
        if (batchIsClean) {
            matchMsg.className = 'flex items-center gap-2 text-[11px] font-mono text-green-700 bg-green-50 border border-green-200 rounded-sm px-3 py-1';
            let cleanMsg = `Dates matched perfectly. Ready for Processing.`;
            if (assumedCnt > 0) {
                cleanMsg += ` <span class="text-purple-700 font-bold">${assumedCnt} row(s) resolving ASSUMED priorities.</span>`;
            }
            matchMsg.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>${cleanMsg}</span>`;
        } else {
            const detail = [];
            if (dateBlockCnt > 0) detail.push(`${dateBlockCnt} pending ASSUMED date violations`);
            if (shortCnt    > 0) detail.push(`${shortCnt} lacking ASSUMED funds`);
            if (mismatchCnt > 0) detail.push(`${mismatchCnt} date mismatches`);
            if (invalidCnt  > 0) detail.push(`${invalidCnt} invalid records`);
            matchMsg.className = 'flex items-start gap-2 text-[11px] font-mono text-red-700 bg-red-50 border border-red-200 rounded-sm px-3 py-1';
            matchMsg.innerHTML = `<svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg><span>Validation Block: ${detail.join(', ')}.</span>`;
        }
        matchMsg.classList.remove('hidden');
    }

    const btn = document.getElementById('proceedImportBtn');
    if (btn) {
        btn.disabled  = !batchIsClean;
        btn.className = batchIsClean ? _btnActiveClass() : _btnDisabledClass();
    }
}

function _renderPreviewTable() {
    const tbody = document.getElementById('preview-body');
    tbody.innerHTML = '';

    if (!parsedDeductions.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 text-xs italic">No data found in the file.</td></tr>`;
        return;
    }

    parsedDeductions.forEach((row, i) => {
        const s        = previewRowStatuses[i];
        const isOk     = s.status === 'ok';
        const isSwap   = isOk && s.swapDetected;
        const isAssumed = ['assumed_exact', 'assumed_excess'].includes(s.status);
        const isHardError = ['mismatch', 'assumed_short', 'assumed_date_block'].includes(s.status);
        const amt      = Number(row.amount).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });

        let rowBg = 'hover:bg-slate-50';
        if (isHardError) {
            rowBg = 'bg-red-50/60 hover:bg-red-50';
        } else if (!isOk && !isAssumed) {
            rowBg = 'bg-orange-50/60 hover:bg-orange-50';
        } else if (isSwap) {
            rowBg = 'bg-blue-50/40 hover:bg-blue-50/60';
        } else if (isAssumed) {
            rowBg = 'bg-purple-50/40 hover:bg-purple-50/60';
        }

        // ... Existing badge logic ...
        let badge;
        if (isAssumed) {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full whitespace-nowrap">PRIORITY ASSUMED</span>`;
        } else if (s.status === 'assumed_date_block') {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-red-700 bg-red-100 px-2 py-0.5 rounded-full whitespace-nowrap">DATE VIOLATION</span>`;
        } else if (s.status === 'assumed_short') {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-red-700 bg-red-100 px-2 py-0.5 rounded-full whitespace-nowrap">ASSUMED SHORT</span>`;
        } else if (isOk) {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-green-700 bg-green-100 px-2 py-0.5 rounded-full">OK</span>`;
        } else if (s.status === 'mismatch') {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-red-700 bg-red-100 px-2 py-0.5 rounded-full whitespace-nowrap">MISMATCHED</span>`;
        } else {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full whitespace-nowrap">INVALID DATE</span>`;
        }

        const dateCell = `<span class="text-[12px] text-slate-700">${chosenDisplayDate}</span>`;

        const tr = document.createElement('tr');
        tr.className = `border-b border-slate-100 transition-colors ${rowBg}`;
        tr.innerHTML = `
            <td class="px-3 py-1.5 text-[12px] text-slate-500 text-center border-r border-slate-100">${_escHtml(String(row.id || '—'))}</td>
            <td class="px-3 py-1.5 text-center border-r border-slate-100">${dateCell}</td>
            <td class="px-3 py-1.5 text-[12px] uppercase border-r border-slate-100">${_escHtml(row.fname || '—')}</td>
            <td class="px-3 py-1.5 text-[12px] uppercase border-r border-slate-100">${_escHtml(row.lname || '—')}</td>
            <td class="px-3 py-1.5 border-r border-slate-100">
                <div class="flex justify-between font-mono text-[12px] text-slate-700">
                    <span>₱</span><span>${amt}</span>
                </div>
            </td>
            <td class="px-3 py-1.5 text-center">${badge}</td>
        `;
        tbody.appendChild(tr);

        if (isAssumed || !isOk) {
            const rtr = document.createElement('tr');
            if (isAssumed) {
                rtr.className = 'bg-purple-50/40 border-b border-purple-100';
                rtr.innerHTML = `<td colspan="6" class="px-4 pb-2.5 pt-0.5"><p class="text-[11px] leading-snug text-purple-700 font-medium">↳ ${_escHtml(s.reason)}</p></td>`;
            } else {
                rtr.className = isHardError ? 'bg-red-50 border-b border-red-100' : 'bg-orange-50 border-b border-orange-100';
                rtr.innerHTML = `<td colspan="6" class="px-4 pb-2.5 pt-0.5"><p class="text-[11px] leading-snug ${isHardError ? 'text-red-600 font-bold' : 'text-orange-700'}">↳ ${_escHtml(s.reason)}</p></td>`;
            }
            tbody.appendChild(rtr);
        }
    });
}
function processImport() {
    if (!batchIsClean) {
        showWarningModal('Cannot upload — resolve blocking issues first.');
        return;
    }

    const payload = parsedDeductions.map(row => ({
        ...row,
        date:     _isoToDisplay(chosenPayrollDate),
        iso_date: chosenPayrollDate
    }));

    const btn = document.getElementById('proceedImportBtn');
    const orig = btn.innerText;
    btn.innerText = 'Processing...';
    btn.disabled  = true;

    fetch('../../api/process_payroll_deduction.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ deductions: payload })
    })
    .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { throw new Error('Server returned invalid JSON.'); }
    })
    .then(result => {
        btn.innerText = orig;
        btn.disabled  = false;
        if (result.success === true) {
            showImportResults(result);
        } else {
            showErrorModal('Database Error: ' + (result.error || 'Unknown error.'));
        }
    })
    .catch(err => {
        btn.innerText = orig;
        btn.disabled  = false;
        showErrorModal('Error: ' + err.message);
    });
}

function showImportResults(result) {
    closeModal('importPreviewModal');

    const title       = document.getElementById('result-title');
    const subtitle    = document.getElementById('result-subtitle');
    const detailsCont = document.getElementById('result-details-container');
    const issuesList  = document.getElementById('result-issues-list');
    const iconCont    = document.getElementById('result-icon-container');

    issuesList.innerHTML = '';
    detailsCont.classList.add('hidden');

    if (result.errors && result.errors.length > 0) {
        iconCont.className = 'inline-flex bg-red-100 p-4 rounded-full mb-4 shadow-sm';
        iconCont.innerHTML = `<svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`;
        title.innerText   = 'Upload Rejected';
        subtitle.innerHTML = `<span class="text-red-600 font-bold">0 records saved.</span> Resolve the issues below and try again.`;
        detailsCont.classList.remove('hidden');
        result.errors.forEach(err => {
            const li = document.createElement('li');
            li.className = 'flex items-start gap-2 bg-red-50 p-3 rounded-lg border border-red-100 text-red-800';
            li.innerHTML  = `<span class="text-[#ce1126] font-bold shrink-0 mt-0.5">✗</span><span class="text-[13px] leading-tight">${_escHtml(err)}</span>`;
            issuesList.appendChild(li);
        });
    } else if (result.discrepancies && result.discrepancies.length > 0) {
        iconCont.className = 'inline-flex bg-yellow-100 p-4 rounded-full mb-4 shadow-sm';
        iconCont.innerHTML = `<svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
        title.innerText   = 'Processed with Notices';
        subtitle.innerHTML = `<span class="text-green-600 font-bold">Successfully recorded ${result.success_count} payment(s).</span>`;
        detailsCont.classList.remove('hidden');
        result.discrepancies.forEach(issue => {
            const li = document.createElement('li');
            li.className = 'flex items-start gap-2 bg-yellow-50 p-3 rounded-lg border border-yellow-100 text-yellow-800';
            li.innerHTML  = `<span class="text-yellow-600 font-bold shrink-0 mt-0.5">!</span><span class="text-[13px] leading-tight">${_escHtml(issue)}</span>`;
            issuesList.appendChild(li);
        });
    } else {
        iconCont.className = 'inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm';
        iconCont.innerHTML = `<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>`;
        title.innerText    = 'Upload Complete';
        subtitle.innerHTML = `<span class="text-green-600 font-bold">Successfully recorded ${result.success_count} payment(s).</span>`;
    }

    openModal('importResultsModal');
}

// ─────────────────────────────────────────────────────────────
// ASSUME PAYMENTS LOGIC
// ─────────────────────────────────────────────────────────────
function openAssumeModal() {
    const cardsContainer = document.getElementById('assumePeriodCards');
    if (cardsContainer) {
        cardsContainer.innerHTML = ''; 

        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth(); 
        const day = today.getDate();

        function getFormattedDate(y, m, d) {
            return new Date(y, m, d).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }

        let periods = [];
        if (day <= 15) {
            periods.push({
                label: 'Current Cutoff',
                dateRange: `${getFormattedDate(year, month, 1)} to ${getFormattedDate(year, month, 15)}`,
                start: `${year}-${String(month+1).padStart(2,'0')}-01`,
                end: `${year}-${String(month+1).padStart(2,'0')}-15`
            });
            const prevMonth = month === 0 ? 11 : month - 1;
            const prevYear = month === 0 ? year - 1 : year;
            const lastDayPrevMonth = new Date(prevYear, prevMonth + 1, 0).getDate();
            periods.push({
                label: 'Previous Cutoff',
                dateRange: `${getFormattedDate(prevYear, prevMonth, 16)} to ${getFormattedDate(prevYear, prevMonth, lastDayPrevMonth)}`,
                start: `${prevYear}-${String(prevMonth+1).padStart(2,'0')}-16`,
                end: `${prevYear}-${String(prevMonth+1).padStart(2,'0')}-${lastDayPrevMonth}`
            });
        } else {
            const lastDay = new Date(year, month + 1, 0).getDate();
            periods.push({
                label: 'Current Cutoff',
                dateRange: `${getFormattedDate(year, month, 16)} to ${getFormattedDate(year, month, lastDay)}`,
                start: `${year}-${String(month+1).padStart(2,'0')}-16`,
                end: `${year}-${String(month+1).padStart(2,'0')}-${lastDay}`
            });
            periods.push({
                label: 'Previous Cutoff',
                dateRange: `${getFormattedDate(year, month, 1)} to ${getFormattedDate(year, month, 15)}`,
                start: `${year}-${String(month+1).padStart(2,'0')}-01`,
                end: `${year}-${String(month+1).padStart(2,'0')}-15`
            });
        }

        periods.forEach((p, idx) => {
            const val = `${p.start}|${p.end}`;
            const isChecked = idx === 0 ? 'checked' : '';
            
            cardsContainer.innerHTML += `
                <label class="relative cursor-pointer">
                    <input type="radio" name="assumePeriod" value="${val}" class="sr-only peer" ${isChecked}>
                    <div class="flex flex-col border-2 border-slate-200 rounded-xl p-4 peer-checked:border-[#ce1126] peer-checked:bg-red-50 hover:border-slate-300 transition-all bg-white shadow-sm">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">${p.label}</span>
                        <span class="text-[14px] font-bold text-slate-800 tracking-tight">${p.dateRange}</span>
                    </div>
                    <span class="absolute top-4 right-4 w-4 h-4 rounded-full border-2 border-slate-300 peer-checked:border-[#ce1126] peer-checked:bg-[#ce1126] flex items-center justify-center transition-all"></span>
                </label>
            `;
        });
    }

    openModal('assumePaymentsModal');
}

function submitAssumePayments() {
    const checkedRadio = document.querySelector('input[name="assumePeriod"]:checked');
    if (!checkedRadio || !checkedRadio.value) return;

    const selected = checkedRadio.value.split('|');
    if (selected.length !== 2) return;

    showConfirmModal('Are you absolutely sure you want to provision assumed payments for this specific cutoff period?', () => {
        const btnSubmit = document.getElementById('btnSubmitAssume');
        const origText = btnSubmit.innerText;
        btnSubmit.disabled = true;
        btnSubmit.innerText = 'Processing...';
        btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');

        const formData = new FormData();
        formData.append('start_date', selected[0]);
        formData.append('end_date', selected[1]);

        fetch('../../api/assume_payroll_period.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerText = origText;
            btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');

            if (data.success) {
                showSuccessModal(data.message);
                closeModal('assumePaymentsModal');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showErrorModal('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showErrorModal('A network error occurred.');
            btnSubmit.disabled = false;
            btnSubmit.innerText = origText;
            btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
        });
    });
}

function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
}
function closeImportModal() { closeModal('importPreviewModal'); }
function closeAllModals()   { window.location.reload(); }

function _isoToLong(iso) {
    if (!iso) return 'unknown';
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m-1, d).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
}
function _isoToDisplay(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${m}/${d}/${y}`;
}
function _escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function _btnActiveClass() {
    return 'px-5 py-1.5 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:brightness-110 hover:shadow-md transition-all duration-200 active:scale-95';
}
function _btnDisabledClass() {
    return 'px-5 py-1.5 bg-slate-200 text-slate-400 rounded-full font-black cursor-not-allowed transition-all duration-200';
}