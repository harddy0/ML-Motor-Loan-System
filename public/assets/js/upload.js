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

    // --- NEW: Date Picker Listener ---
    const dateInput = document.getElementById('confirmedPayrollDate');
    if (dateInput) {
        dateInput.addEventListener('change', (e) => {
            updateVisibleDateText(e.target.value);
            
            // Force re-confirmation if date changes
            dateConfirmed = false;
            const confirmBtn = document.getElementById('confirmDateBtn');
            const proceedBtn = document.getElementById('proceedImportBtn');
            const statusBox = document.getElementById('dateConfirmStatus');
            
            if(confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerText = "✓ Confirm";
                confirmBtn.className = "px-4 py-1 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-md hover:brightness-110 transition-all duration-200 ease-in-out active:scale-95 active:shadow-inner";
            }
            if(proceedBtn) {
                proceedBtn.disabled = true;
                proceedBtn.className = "px-4 py-1 bg-slate-300 text-slate-500 cursor-not-allowed rounded-full font-black shadow-sm transition-all duration-200";
            }
            if(statusBox) statusBox.classList.replace('flex', 'hidden');
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
            const firstRow   = parsedDeductions[0];
            const isoDate    = firstRow ? firstRow.iso_date : '';   
            const dateInput  = document.getElementById('confirmedPayrollDate');

            if (dateInput) {
                dateInput.value = isoDate;
                updateVisibleDateText(isoDate); // NEW: Format text on load
            }
            // Reset confirmation state
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
    const confirmBtn = document.getElementById('confirmDateBtn'); // Added
    const proceedBtn = document.getElementById('proceedImportBtn'); // Added
    const val        = dateInput ? dateInput.value : '';

    if (!val) {
        statusBox.className = 'flex items-center gap-1.5 text-sm font-bold text-red-600'; // Made text red for error
        statusBox.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg> No date selected.`;
        statusBox.classList.remove('hidden');
        statusBox.classList.add('flex');
        dateConfirmed = false;
        return;
    }

    const [y, m, d]  = val.split('-');
    const display    = `${m}/${d}/${y}`; 
    const dtObj      = new Date(`${y}-${m}-${d}T00:00:00`);
    const longFormat = dtObj.toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric'
    });

    // 1. Show green confirmed status
    statusBox.className = 'flex items-center gap-1.5 text-sm font-bold text-green-600'; // Changed to green
    statusBox.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> ${longFormat}`;
    statusBox.classList.remove('hidden');
    statusBox.classList.add('flex');

    // 2. Update preview table
    const tbody = document.getElementById('preview-body');
    if (tbody) {
        tbody.querySelectorAll('tr').forEach(tr => {
            const dateTd = tr.querySelector('td:nth-child(2)');
            if (dateTd) dateTd.innerText = longFormat;
        });
    }

    // 3. Update Data Array
    parsedDeductions = parsedDeductions.map(row => ({
        ...row,
        date:     display,
        iso_date: val
    }));

    dateConfirmed = true;

    // --- NEW COLOR SWAP LOGIC ---
    
    // A. Disable Confirm Button and make it Gray
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerText = "✓ Confirmed";
        // Remove red/hover, add gray/muted
        confirmBtn.classList.remove('bg-[#ce1126]', 'hover:brightness-110', 'hover:shadow-md');
        confirmBtn.classList.add('bg-slate-100', 'text-slate-400', 'border', 'border-slate-200', 'cursor-not-allowed');
    }

    // B. Enable Proceed Button and make it Red
    if (proceedBtn) {
        proceedBtn.disabled = false;
        // Remove gray/muted, add red/active
        proceedBtn.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
        proceedBtn.classList.add('bg-[#ce1126]', 'text-white', 'hover:brightness-110', 'hover:shadow-md', 'animate-pulse-once');
    }
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
            <td class="px-4 py-1 border-x text-[13px] text-center">${row.id}</td>
            <td class="px-4 py-1 border-x text-[13px] text-center">${fmtLong(row.date)}</td>
            <td class="px-4 py-1 border-x text-[13px] uppercase text-center">${row.fname}</td>
            <td class="px-4 py-1 border-x text-[13px] uppercase text-center">${row.lname}</td>
            <td class="px-4 py-1 border-x text-[13px] text-center font-black">${row.amount}</td>
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
    // 1. Hide the modal
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');

    // 2. Reset the Confirm Button to original Red state
    const confirmBtn = document.getElementById('confirmDateBtn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerText = "✓ Confirm";
        confirmBtn.className = "px-4 py-1 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-md hover:brightness-110 transition-all duration-200 ease-in-out active:scale-95 active:shadow-inner";
    }

    // 3. Reset the Proceed Button to the "Disabled/Gray" state
    const proceedBtn = document.getElementById('proceedImportBtn');
    if (proceedBtn) {
        proceedBtn.disabled = true;
        proceedBtn.className = "px-4 py-1 bg-slate-300 text-slate-500 cursor-not-allowed rounded-full font-black shadow-sm transition-all duration-200";
    }

    // 4. Reset the Status Box and Date Input
    const statusBox = document.getElementById('dateConfirmStatus');
    const dateInput = document.getElementById('confirmedPayrollDate');
    if (statusBox) statusBox.classList.replace('flex', 'hidden');
    if (dateInput) {
        dateInput.value = '';
        dateInput.classList.remove('border-red-500');
        updateVisibleDateText(''); // NEW: Reset the visible word text
    }
    
    dateConfirmed = false;
}

function closeAllModals() {
    window.location.reload();
}

function updateVisibleDateText(isoDate) {
    const span = document.getElementById('visibleDateText');
    if (!span) return;
    
    if (!isoDate) {
        span.innerText = "Select Date";
        return;
    }
    
    const [y, m, d] = isoDate.split('-');
    const dtObj = new Date(`${y}-${m}-${d}T00:00:00`);
    span.innerText = dtObj.toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
}