// ============================================================
// PAYROLL UPLOAD — 3-STEP FLOW
//
// Step 1: User picks file → clicks Import
// Step 2: Date Selector Modal — pick month + 15 or 30/EOM (GROUND TRUTH)
// Step 3: File is parsed → Preview Modal
//         • Per-row badges: ✓ OK | ✗ DATE MISMATCH | ✗ INVALID DATE
//         • ANY bad row = entire batch BLOCKED (upload disabled)
//         • Staff must fix the file and re-upload
// Step 4: Process → Result Modal
//
// GROUND TRUTH RULE:
//   The date the user picks in Step 2 is the only truth.
//   Excel dates are read and shown for transparency — but Excel can
//   misread dates due to regional settings (e.g. typing 2/10/2026 on a
//   D/M/Y locale stores October 2, not February 10). That is WHY the
//   user selects the date manually first. If Excel's parsed date does
//   not match the selection, the row is flagged with a clear explanation
//   and the entire batch is blocked until the file is corrected.
// ============================================================

let parsedDeductions   = [];
let chosenPayrollDate  = null; // ISO "Y-m-d" — GROUND TRUTH
let chosenDisplayDate  = '';   // "Month DD, YYYY" for display
let previewRowStatuses = [];   // [{ status:'ok'|'mismatch'|'invalid', excelDisplay, reason }]
let batchIsClean       = false;

// ─────────────────────────────────────────
// INIT
// ─────────────────────────────────────────
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

    // Default month picker to current month
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

// ─────────────────────────────────────────────────────────────
// STEP 2 — DATE SELECTOR MODAL
// ─────────────────────────────────────────────────────────────
function openDateSelectorModal() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files.length) {
        alert('Please select or drop a file first.');
        return;
    }

    // Reset state
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

    // Ensure month has a value
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
    const systemDay = Math.min(lastDay, 30); // system uses 15/30 cycle
    lbl.innerText   = systemDay === 30 ? '30th' : `${systemDay}th (last)`;

    // Re-compute if "30" radio is already selected
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

// ─────────────────────────────────────────────────────────────
// STEP 3A — PARSE + VALIDATE
// ─────────────────────────────────────────────────────────────
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
            if (!result.success) { alert('Error reading file:\n' + result.error); return; }

            parsedDeductions   = result.data;
            previewRowStatuses = _validateAllRows(parsedDeductions);
            batchIsClean       = previewRowStatuses.every(s => s.status === 'ok');

            _renderPreviewHeader();
            _renderPreviewTable();
            openModal('importPreviewModal');
        })
        .catch(err => {
            if (dropZone) dropZone.classList.remove('opacity-60','pointer-events-none');
            console.error(err);
            alert('System error reading file.');
        });
}

// ─────────────────────────────────────────────────────────────
// STEP 3B — VALIDATION RULES
//
//  1. Blank/unparseable date → INVALID
//  2. Impossible month (>12) or day (>31) → INVALID
//     e.g. "15/30/2026" month=15 is impossible
//  3. Excel date ≠ chosen date → MISMATCH
//     Detect regional D/M vs M/D swap and explain clearly
//  ANY failure → batchIsClean=false → Upload button disabled
// ─────────────────────────────────────────────────────────────
function _validateAllRows(rows) {
    return rows.map(row => {
        const rawDisplay = row.date    || '';  // "MM/DD/YYYY" from PHP
        const isoExcel   = row.iso_date || ''; // "Y-m-d" from PHP

        // 1. Missing
        if (!isoExcel || !rawDisplay) {
            return { status:'invalid', excelDisplay: null,
                reason: 'Date is missing or could not be read from the Excel file. Ensure the date column is filled.' };
        }

        // 2. Impossible date parts
        const parts = rawDisplay.split('/').map(Number);
        if (parts.length === 3) {
            const [rawM, rawD] = parts;
            if (isNaN(rawM) || rawM < 1 || rawM > 12) {
                return { status:'invalid', excelDisplay: rawDisplay,
                    reason: `Month "${rawM}" in "${rawDisplay}" is impossible — valid months are 1–12. ` +
                            `A value like "15/30/2026" is an error. Check your Excel date column format.` };
            }
            if (isNaN(rawD) || rawD < 1 || rawD > 31) {
                return { status:'invalid', excelDisplay: rawDisplay,
                    reason: `Day "${rawD}" in "${rawDisplay}" is out of range.` };
            }
        }

        // 3. Compare Excel date vs chosen date
        if (isoExcel !== chosenPayrollDate) {
            const excelLong = _isoToLong(isoExcel);
            const [eY, eM, eD] = isoExcel.split('-').map(Number);
            const [cY, cM, cD] = chosenPayrollDate.split('-').map(Number);

            // D/M <-> M/D swap detection (regional misread)
            // Same year, digits just swapped between month and day slots.
            // ACCEPTED as OK — chosen date is the truth, Excel just read it backwards.
            if (eY === cY && eM === cD && eD === cM) {
                return {
                    status:       'ok',
                    swapDetected: true,
                    excelDisplay: excelLong,
                    reason: `Excel due date (${excelLong}) and` +
                            `your selected due date (${chosenDisplayDate}) have matched.`
                };
            }

            // Genuinely different date — real mismatch, block upload
            return {
                status:       'mismatch',
                swapDetected: false,
                excelDisplay: excelLong,
                reason: `Excel due date (${excelLong}) doesn't match the selected payroll due date (${chosenDisplayDate}). ` +
                        `Please fix and try again.`
            };
        }

        return { status:'ok', swapDetected: false, excelDisplay: _isoToLong(isoExcel), reason:'' };
    });
}

// ─────────────────────────────────────────────────────────────
// STEP 3C — PREVIEW HEADER (summary banner)
// ─────────────────────────────────────────────────────────────
function _renderPreviewHeader() {
    const okCount     = previewRowStatuses.filter(s => s.status === 'ok').length;
    const swapCount   = previewRowStatuses.filter(s => s.status === 'ok' && s.swapDetected).length;
    const mismatchCnt = previewRowStatuses.filter(s => s.status === 'mismatch').length;
    const invalidCnt  = previewRowStatuses.filter(s => s.status === 'invalid').length;
    const total       = previewRowStatuses.length;

    const dateLbl = document.getElementById('previewChosenDate');
    if (dateLbl) dateLbl.innerText = chosenDisplayDate;

    // Stats
    const statsEl = document.getElementById('previewStats');
    if (statsEl) {
        const parts = [`<span class="font-bold ${batchIsClean ? 'text-green-700' : 'text-slate-500'}">${okCount}/${total} valid</span>`];
        if (swapCount   > 0) parts.push(`<span class="font-bold text-blue-600">${swapCount} date-corrected</span>`);
        if (mismatchCnt > 0) parts.push(`<span class="font-bold text-red-600">${mismatchCnt} mismatch</span>`);
        if (invalidCnt  > 0) parts.push(`<span class="font-bold text-orange-600">${invalidCnt} invalid</span>`);
        statsEl.innerHTML = parts.join('<span class="text-slate-300 mx-1">·</span>');
    }

    // Banner
    const matchMsg = document.getElementById('previewMatchMsg');
    if (matchMsg) {
        if (batchIsClean) {
            matchMsg.className = 'flex items-center gap-2 text-[11px] font-mono text-green-700 bg-green-50 border border-green-200 rounded-sm px-3 py-1';
            let cleanMsg = `Due Date Matched Successfully.`;
            if (swapCount > 0) {
                cleanMsg += ` <span class="text-blue-600">${swapCount} row(s) had their Excel date auto-corrected due to regional D/M format.</span>`;
            }
            cleanMsg += ` Ready to upload.`;
            matchMsg.innerHTML = `
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>${cleanMsg}</span>`;
        } else {
            const detail = [];
            if (mismatchCnt > 0) detail.push(`${mismatchCnt} row(s) have a different date`);
            if (invalidCnt  > 0) detail.push(`${invalidCnt} row(s) have an impossible or unreadable date`);
            matchMsg.className = 'flex items-start gap-2 text-[11px] font-mono text-red-700 bg-red-50 border border-red-200 rounded-sm px-3 py-1';
            matchMsg.innerHTML = `
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Due Date Mismatch: ${detail.join(' and ')}.</span>`;
        }
        matchMsg.classList.remove('hidden');
    }

    // Upload button
    const btn = document.getElementById('proceedImportBtn');
    if (btn) {
        btn.disabled  = !batchIsClean;
        btn.className = batchIsClean ? _btnActiveClass() : _btnDisabledClass();
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 3D — PREVIEW TABLE ROWS
// ─────────────────────────────────────────────────────────────
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
        const amt      = Number(row.amount).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });

        // Row background
        let rowBg = 'hover:bg-slate-50';
        if (!isOk) {
            rowBg = s.status === 'mismatch' ? 'bg-red-50/60 hover:bg-red-50' : 'bg-orange-50/60 hover:bg-orange-50';
        } else if (isSwap) {
            rowBg = 'bg-blue-50/40 hover:bg-blue-50/60';
        }

        // Date cell
        let dateCell;
        if (isOk && !isSwap) {
            // Clean match
            dateCell = `<span class="text-[12px] text-slate-700">${chosenDisplayDate}</span>`;
        } else if (isSwap) {
            // Swap accepted — show strikethrough Excel date + corrected date
            const excelLabel = s.excelDisplay || row.date || '?';
            dateCell = `<div class="flex flex-col gap-0.5 leading-tight">
                <span class="text-[11px] text-slate-400 line-through">${_escHtml(String(excelLabel))}</span>
                <span class="text-[11px] text-blue-700 font-bold">→ ${chosenDisplayDate}</span>
            </div>`;
        } else {
            // Bad row — rejected
            const excelLabel = s.excelDisplay || (row.date ? `"${_escHtml(row.date)}"` : '(unreadable)');
            dateCell = `<div class="flex flex-col gap-0.5 leading-tight">
                <span class="text-[11px] text-slate-400 line-through">${excelLabel}</span>
                <span class="text-[11px] text-red-600 font-bold">✗ rejected</span>
            </div>`;
        }

        // Badge
        let badge;
        if (isSwap) {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full whitespace-nowrap">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>DATE CORRECTED
            </span>`;
        } else if (isOk) {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>OK
            </span>`;
        } else if (s.status === 'mismatch') {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-red-700 bg-red-100 px-2 py-0.5 rounded-full whitespace-nowrap">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>DATE MISMATCH
            </span>`;
        } else {
            badge = `<span class="inline-flex items-center gap-1 text-[10px] font-black text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full whitespace-nowrap">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>INVALID DATE
            </span>`;
        }

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

        // Notice row — shown for swap (info) and bad rows (error)
        if (isSwap || !isOk) {
            const rtr = document.createElement('tr');
            if (isSwap) {
                rtr.className = 'bg-blue-50/40 border-b border-blue-100';
                rtr.innerHTML = `
                    <td colspan="6" class="px-4 pb-2.5 pt-0.5">
                        <p class="text-[11px] leading-snug text-blue-600">
                            ↳ ${_escHtml(s.reason)}
                        </p>
                    </td>`;
            } else {
                rtr.className = s.status === 'mismatch'
                    ? 'bg-red-50 border-b border-red-100'
                    : 'bg-orange-50 border-b border-orange-100';
                rtr.innerHTML = `
                    <td colspan="6" class="px-4 pb-2.5 pt-0.5">
                        <p class="text-[11px] leading-snug ${s.status === 'mismatch' ? 'text-red-600' : 'text-orange-700'}">
                            ↳ ${_escHtml(s.reason)}
                        </p>
                    </td>`;
            }
            tbody.appendChild(rtr);
        }
    });
}

// ─────────────────────────────────────────────────────────────
// STEP 4 — PROCESS (only if batchIsClean)
// ─────────────────────────────────────────────────────────────
function processImport() {
    if (!batchIsClean) {
        alert('Cannot upload — one or more rows have date issues. Fix the file and re-import.');
        return;
    }

    // Stamp all rows with the ground-truth date
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
            alert('Database Error: ' + (result.error || 'Unknown error.'));
        }
    })
    .catch(err => {
        btn.innerText = orig;
        btn.disabled  = false;
        alert('Error: ' + err.message);
    });
}

// ─────────────────────────────────────────────────────────────
// RESULT MODAL
// ─────────────────────────────────────────────────────────────
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
// MODAL + UTIL HELPERS
// ─────────────────────────────────────────────────────────────
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