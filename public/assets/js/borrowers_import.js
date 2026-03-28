document.addEventListener('DOMContentLoaded', function() {
    setupImportModalLogic();
});

function setupImportModalLogic() {
    const importForm = document.getElementById('importBorrowerForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('file-upload');
            
            if (fileInput.files.length === 0) { 
                showImportError("Please select an Excel or CSV file before submitting.");
                return; 
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Analyzing File...";
            btn.disabled = true;

            fetch(`${BASE_URL}/public/api/parse_import.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                btn.innerText = originalText;
                btn.disabled = false;

                if (result.success) {
                    importedData = result.data; 
                    closeModal('importBorrowerModal');
                    showImportPreview(importedData, result.warnings || []);
                } else {
                    if (result.region_errors && result.region_errors.length > 0) {
                        showRegionErrorModal(result.region_errors);
                    } else {
                        showImportError(result.error.replace(/\n/g, '<br>'));
                    }
                }
            })
            .catch(err => {
                btn.innerText = originalText;
                btn.disabled = false;
                showImportError("System Error during upload.");
            });
        });
    }
}

function showRegionErrorModal(regionErrors) {
    const errorModal = document.getElementById('importErrorModal');
    const errorMessage = document.getElementById('importErrorMessage');

    let html = `
        <div class="mb-4 mt-1 text-center">
            <p class="text-[13px] text-slate-600 leading-relaxed">
                <strong>Upload blocked:</strong> We found <strong>${regionErrors.length} row(s)</strong> with unrecognized regions.<br>
                Please fix the highlighted rows in your Excel file and try again.
            </p>
        </div>
        
        <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden shadow-inner text-left">
            <div class="max-h-64 overflow-y-auto p-2.5 space-y-2">
    `;

    regionErrors.forEach(errMsg => {
        const match = errMsg.match(/^(.*?)\s*\(Row (\d+)\):\s*Region\s*"([^"]+)"/i);

        if (match) {
            const name = match[1].trim();
            const row = match[2];
            const region = match[3];

            html += `
                <div class="flex items-start gap-3 bg-white p-3 rounded-md border border-red-100 shadow-sm">
                    <svg class="w-4 h-4 text-[#ce1126] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <div class="text-[12px] leading-snug">
                        <span class="font-bold text-slate-800 uppercase">${name}</span> 
                        <span class="text-slate-400 font-medium text-[11px] ml-1">(Row ${row})</span><br>
                        <span class="text-slate-500 mt-1 inline-block">Unknown region:</span> 
                        <span class="font-mono font-bold text-[#ce1126] bg-red-50 border border-red-100 px-1.5 py-0.5 rounded ml-1">"${region}"</span>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="flex items-start gap-3 bg-white p-3 rounded-md border border-red-100 shadow-sm">
                    <svg class="w-4 h-4 text-[#ce1126] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <div class="text-[12px] leading-snug text-slate-700 mt-0.5">${errMsg}</div>
                </div>
            `;
        }
    });

    html += `
            </div>
        </div>
    `;

    errorMessage.innerHTML = html;
    errorModal.style.zIndex = '9999';
    errorModal.classList.replace('hidden', 'flex');
}

function showImportError(htmlMessage) {
    document.getElementById('importErrorMessage').innerHTML = htmlMessage;
    document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
}

function openImportModal() {
    const modal = document.getElementById('importBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('importBorrowerForm').reset();
    document.getElementById('file-name-display').innerText = 'No file chosen';
}

function showImportPreview(data, warnings = []) {
    const list = document.getElementById('import-list');
    const countSpan = document.getElementById('import-count');
    list.innerHTML = '';
    countSpan.innerText = data.length;

    const warningBanner = document.getElementById('import-warnings-banner');
    if (warningBanner) {
        if (warnings.length > 0) {
            let wHtml = `
                <div class="flex items-start gap-2 mb-1">
                    <svg class="w-4 h-4 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-[12px] font-bold text-yellow-800">Some rows were skipped — review before confirming:</span>
                </div>
                <ul class="space-y-1 max-h-40 overflow-y-auto">
            `;
            warnings.forEach(w => {
                const lines = w.split('\n');
                lines.forEach((line, i) => {
                    const cls = i === 0
                        ? 'text-[12px] font-bold text-yellow-900'
                        : 'text-[11px] text-yellow-800 pl-2';
                    wHtml += `<li class="${cls}">${line.replace(/\n/g, '<br>')}</li>`;
                });
            });
            wHtml += `</ul>`;

            warningBanner.innerHTML = wHtml;
            warningBanner.className = 'mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg';
        } else {
            warningBanner.innerHTML = '';
            warningBanner.className = 'hidden';
        }
    }

    data.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = "flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded transition-colors group";
        li.innerHTML = `
            <div class="flex items-center gap-3 cursor-pointer flex-1 hover:border-[#e11d48]" onclick="viewImportDetail(${index})">
                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 group-hover:border-[#e11d48] group-hover:text-white">${index + 1}</div>
                <div>
                    <p class="text-slate-800 uppercase">${item.name}</p>
                    <p class="text-slate-400">ID: ${item.id} | Amount: ${parseFloat(item.loan_amount).toLocaleString()} | Region: ${item.region || 'N/A'}</p>
                </div>
            </div>
            <input type="checkbox" class="import-checkbox w-5 h-5 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer" value="${index}">
        `;
        list.appendChild(li);
    });

    const modal = document.getElementById('importPreviewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function viewImportDetail(index) {
    const item = importedData[index];
    const modal = document.getElementById('importDetailModal');

    const formatLongDate = (value) => {
        if (!value) return 'N/A';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    document.getElementById('imp-id').innerText = item.id ? item.id : 'AUTO-GENERATE';
    document.getElementById('imp-name').innerText = item.name;
    document.getElementById('imp-contact').innerText = item.contact_number || '000-000-0000';
    document.getElementById('imp-region').innerText = item.region || 'N/A';
    document.getElementById('imp-pn').innerText = item.pn_number || 'TBD';
    document.getElementById('imp-ref').innerText = item.reference_number || 'N/A';
    document.getElementById('imp-granted').innerText = formatLongDate(item.loan_granted);
    document.getElementById('imp-maturity').innerText = formatLongDate(item.pn_maturity);
    const amountValue = parseFloat(item.loan_amount || 0);
    const deductionValue = parseFloat(item.deduction || 0);
    const monthlyAmortValue = deductionValue * 2;
    const termMonths = parseInt(item.terms || 0, 10);
    const addOnRateDecimalRaw = parseFloat(item.add_on_rate_decimal);
    const addOnRateDecimal = Number.isFinite(addOnRateDecimalRaw)
        ? addOnRateDecimalRaw
        : ((parseFloat(item.add_on_rate || 0) / 100) / (termMonths || 1));
    const totalRatePercent = (addOnRateDecimal * termMonths * 100).toFixed(0);
    const grossPrincipal = amountValue;
    const grossInterest = amountValue * addOnRateDecimal * termMonths;
    const grossTotal = grossPrincipal + grossInterest;

    document.getElementById('imp-amount').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + amountValue.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('imp-terms').innerText = item.terms ? item.terms + ' Months' : 'N/A';
    document.getElementById('imp-deduct').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + deductionValue.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('imp-monthly-amort').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + monthlyAmortValue.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('imp-rate').innerText = termMonths > 0 ? totalRatePercent + '%' : 'N/A';
    document.getElementById('imp-gross-principal').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossPrincipal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('imp-gross-interest').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossInterest.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('imp-gross-total').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossTotal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';

    const kptnWarning = document.getElementById('imp-kptn-warning');
    if (kptnWarning) {
        const requiresKptn = item.requires_kptn === true || item.requires_kptn === 'true' || item.requires_kptn == 1;
        if (requiresKptn) kptnWarning.classList.remove('hidden');
        else kptnWarning.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function finalizeImport() {
    const checkboxes = document.querySelectorAll('.import-checkbox:checked:not(#select-all)');
    if (checkboxes.length === 0) return;

    const count = checkboxes.length;
    document.getElementById('confirmMessage').innerText = `You are about to save ${count} borrower(s).`;
    
    const confirmModal = document.getElementById('confirmSaveModal');
    confirmModal.classList.replace('hidden', 'flex');

    document.getElementById('realSubmitBtn').onclick = function() {
        confirmModal.classList.replace('flex', 'hidden'); 
        executeActualSave(checkboxes); 
    };
}

function executeActualSave(checkboxes) {
    const selectedIndices = Array.from(checkboxes).map(cb => parseInt(cb.value));
    const selectedBorrowers = selectedIndices.map(idx => importedData[idx]);

    fetch(`${BASE_URL}/public/actions/save_import.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ borrowers: selectedBorrowers })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('importPreviewModal').classList.add('hidden');
            document.getElementById('successMessage').innerText = `Successfully imported ${data.imported_count} records!`;
            document.getElementById('successAlertModal').classList.replace('hidden', 'flex');
        } else {
            document.getElementById('importPreviewModal').classList.add('hidden');
            const errorModal = document.getElementById('importErrorModal');
            errorModal.style.zIndex = '9999'; 
            document.getElementById('importErrorMessage').innerHTML = "Database Error:<br>" + data.errors.join('<br>');
            errorModal.classList.replace('hidden', 'flex');
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('importPreviewModal').classList.add('hidden');
        const errorModal = document.getElementById('importErrorModal');
        errorModal.style.zIndex = '9999';
        document.getElementById('importErrorMessage').innerHTML = "System Error: Failed to execute database queries.";
        errorModal.classList.replace('hidden', 'flex');
    });
}

function toggleSelectAll(source) {
    document.querySelectorAll('.import-checkbox').forEach(cb => cb.checked = source.checked);
}