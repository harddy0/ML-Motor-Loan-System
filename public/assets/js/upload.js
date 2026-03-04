let parsedDeductions = [];

/**
 * INITIALIZE ALL LISTENERS
 */
function initUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const display = document.getElementById('displayFileName');
    const buttonContainer = document.getElementById('buttonContainer');

    if (dropZone && fileInput) {
        fileInput.value = "";
        if (display) display.innerText = "No file selected";
        if (buttonContainer) buttonContainer.classList.add('hidden');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('bg-[#eeeeee]/90', 'border-[#8a3333]');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('bg-[#eeeeee]/90', 'border-[#8a3333]');
            }, false);
        });

        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                updateName(fileInput);
            }
        });
    }
}

function updateName(input) {
    const fileNameDisplay = document.getElementById('displayFileName');
    const buttonContainer = document.getElementById('buttonContainer');
    if (input.files && input.files[0]) {
        fileNameDisplay.innerText = input.files[0].name;
        if (buttonContainer) buttonContainer.classList.remove('hidden');
    } else {
        fileNameDisplay.innerText = 'No file selected';
        if (buttonContainer) buttonContainer.classList.add('hidden');
    }
}

document.addEventListener("DOMContentLoaded", initUpload);
window.addEventListener("pageshow", () => initUpload());

/**
 * MODAL & API LOGIC
 */
function openImportModal() {
    const fileInput = document.getElementById('fileInput');
    if (!fileInput || !fileInput.files.length) {
        alert("Please select or drop a file first.");
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    fetch('../../api/parse_payroll_deduction.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            parsedDeductions = result.data;
            populatePreviewTable(parsedDeductions);

            // ── DATE CONFIRMATION BANNER ──────────────────────────────────
            // All rows share the same payroll date. Show it prominently and
            // let staff correct it before anything hits the database.
            // The date input is pre-filled from the file but fully editable.
            const firstRow   = parsedDeductions[0];
            const isoDate    = firstRow ? firstRow.iso_date : '';   // Y-m-d for the input
            const dateInput  = document.getElementById('confirmedPayrollDate');
            const dateStatus = document.getElementById('dateConfirmStatus');

            if (dateInput) {
                dateInput.value = isoDate;
            }
            // Reset confirmation state — staff must explicitly click Confirm Date
            dateConfirmed = false;
            const statusBox = document.getElementById('dateConfirmStatus');
            if (statusBox) {
                statusBox.className = 'hidden';
                statusBox.innerHTML = '';
            }
            // ──────────────────────────────────────────────────────────────

            document.getElementById('importPreviewModal').classList.replace('hidden', 'flex');
        } else {
            alert("Error reading file: " + result.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("System Error reading file.");
    });
}

// Tracks whether staff has explicitly confirmed the date
let dateConfirmed = false;

/**
 * Called when staff clicks "✓ Confirm Date".
 * Only then do we show feedback and stamp all rows.
 */
function confirmPayrollDate() {
    const dateInput  = document.getElementById('confirmedPayrollDate');
    const statusBox  = document.getElementById('dateConfirmStatus');
    const val        = dateInput ? dateInput.value : '';

    if (!val) {
        // Show error — no green, no proceed
        statusBox.className = 'flex items-center gap-1.5 text-sm font-bold text-white/80';
        statusBox.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg> No date selected.`;
        statusBox.classList.replace('hidden', 'flex');
        dateConfirmed = false;
        return;
    }

    const [y, m, d]  = val.split('-');
    const display    = `${m}/${d}/${y}`;                           // 02/10/2026 — for DB
    const dtObj      = new Date(`${y}-${m}-${d}T00:00:00`);
    const longFormat = dtObj.toLocaleDateString('en-US', {         // February 10, 2026 — for UI
        year: 'numeric', month: 'long', day: 'numeric'
    });

    // Show green confirmed status
    statusBox.className = 'flex items-center gap-1.5 text-sm font-bold text-white';
    statusBox.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> ${longFormat}`;
    statusBox.classList.replace('hidden', 'flex');

    // Update preview table Due Date column to long format
    const tbody = document.getElementById('preview-body');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(tr => {
            const dateTd = tr.querySelector('td:nth-child(2)');
            if (dateTd) dateTd.innerText = longFormat;
        });
    }

    // Stamp all rows with the confirmed date
    parsedDeductions = parsedDeductions.map(row => ({
        ...row,
        date:     display,
        iso_date: val
    }));

    dateConfirmed = true;
}

// Kept for backward compatibility — does nothing visual on its own now
function updateDateStatus() {}

function populatePreviewTable(data) {
    const tbody = document.getElementById('preview-body');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-xs font-bold text-slate-500 uppercase">No valid data found in the file.</td></tr>';
        return;
    }

    // Convert m/d/Y → "February 10, 2026" for display in the preview table
    const fmtLong = (mdY) => {
        if (!mdY) return '';
        const [m, d, y] = mdY.split('/');
        const dt = new Date(`${y}-${m}-${d}T00:00:00`);
        return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "border-b border-slate-100 hover:bg-slate-50 transition-colors";
        tr.innerHTML = `
            <td class="px-4 py-1 border-x text-center">${row.id}</td>
            <td class="px-4 py-1 border-x text-center">${fmtLong(row.date)}</td>
            <td class="px-4 py-1 border-x uppercase text-center">${row.fname}</td>
            <td class="px-4 py-1 border-x uppercase text-center">${row.lname}</td>
            <td class="px-4 py-1 border-x text-center font-black">${row.amount}</td>
        `;
        tbody.appendChild(tr);
    });
}

function processImport() {
    if (parsedDeductions.length === 0) {
        alert("No data to process.");
        return;
    }

    // ── GATE: staff must click "✓ Confirm Date" before proceeding ──────────
    if (!dateConfirmed) {
        const statusBox   = document.getElementById('dateConfirmStatus');
        const dateInput2  = document.getElementById('confirmedPayrollDate');
        const confirmBtn  = document.getElementById('confirmDateBtn');

        // Flash the status message in bright yellow so it pops on the red bar
        statusBox.className = 'flex items-center gap-1.5 text-sm font-black bg-yellow-400 text-[#7a0000] px-3 py-1 rounded-lg';
        statusBox.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Confirm the date first!`;
        statusBox.classList.replace('hidden', 'flex');

        // Shake the date picker
        dateInput2.classList.add('animate-shake');
        dateInput2.addEventListener('animationend', () => dateInput2.classList.remove('animate-shake'), { once: true });

        // Pulse the confirm button
        if (confirmBtn) {
            confirmBtn.classList.add('animate-pulse-once');
            confirmBtn.addEventListener('animationend', () => confirmBtn.classList.remove('animate-pulse-once'), { once: true });
        }

        dateInput2.focus();
        return;
    }

    const dateInput        = document.getElementById('confirmedPayrollDate');
    const confirmedIso     = dateInput.value;
    const [y, m, d]        = confirmedIso.split('-');
    const confirmedDisplay = `${m}/${d}/${y}`;

    parsedDeductions = parsedDeductions.map(row => ({
        ...row,
        date:     confirmedDisplay,
        iso_date: confirmedIso
    }));
    // ──────────────────────────────────────────────────────────────────────

    const proceedBtn   = document.getElementById('proceedImportBtn');
    const originalText = proceedBtn.innerText;
    proceedBtn.innerText = "PROCESSING...";
    proceedBtn.disabled  = true;

    fetch('../../api/process_payroll_deduction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deductions: parsedDeductions })
    })
    .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (e) { throw new Error("Server returned invalid JSON."); }
    })
    .then(result => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled  = false;
        if (result.success === true) { showImportResults(result); }
        else { alert("Database Error: " + (result.error || "Unknown error.")); }
    })
    .catch(err => {
        proceedBtn.innerText = originalText;
        proceedBtn.disabled  = false;
        alert("JavaScript Error: " + err.message);
    });
}

function showImportResults(result) {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
    const modal          = document.getElementById('importResultsModal');
    const title          = document.getElementById('result-title');
    const subtitle       = document.getElementById('result-subtitle');
    const detailsContainer = document.getElementById('result-details-container');
    const issuesList     = document.getElementById('result-issues-list');
    const iconContainer  = document.getElementById('result-icon-container');

    subtitle.innerText = `Successfully recorded ${result.success_count} payment(s).`;
    issuesList.innerHTML = '';

    const allIssues = [...(result.discrepancies || []), ...(result.errors || [])];
    if (allIssues.length > 0) {
        iconContainer.className = "inline-flex bg-yellow-100 p-4 rounded-full mb-4 shadow-sm";
        iconContainer.innerHTML = `<svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
        title.innerText = "Processed with Notices";
        allIssues.forEach(issue => {
            const li = document.createElement('li');
            li.className = "flex items-start gap-2 bg-white p-3 rounded-lg shadow-sm";
            li.innerHTML = `<span class="text-[#e11d48] mt-0.5">•</span> <span>${issue}</span>`;
            issuesList.appendChild(li);
        });
        detailsContainer.classList.replace('hidden', 'block');
    } else {
        iconContainer.className = "inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm";
        iconContainer.innerHTML = `<svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>`;
        title.innerText = "Upload Complete";
        detailsContainer.classList.replace('block', 'hidden');
    }
    modal.classList.replace('hidden', 'flex');
}

function closeImportModal() {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
}

function closeAllModals() {
    window.location.reload();
}