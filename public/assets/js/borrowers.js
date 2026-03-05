let tempBorrowerData = {};
let importedData = [];
let masterLocationsFetched = false;
let currentVoidId = "";
let currentVoidName = "";   

// --- TAB SWITCHING LOGIC ---
window.switchTab = function(tab) {
    const activeTabBtn  = document.getElementById('tab-active');
    const pendingTabBtn = document.getElementById('tab-pending');
    const activeTable   = document.getElementById('table-active');
    const pendingTable  = document.getElementById('table-pending');

    if (!activeTabBtn || !pendingTabBtn || !activeTable || !pendingTable) return;

    if (tab === 'active') {
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('hidden', 'block');
        pendingTable.classList.replace('block', 'hidden');
    } else if (tab === 'pending') {
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('block', 'hidden');
        pendingTable.classList.replace('hidden', 'block');
    }
};

// --- ATTACH KPTN MODAL ---
// FIX: removed attachKptnForm.reset() — it wiped the KPTN number that was
//      just set, and cleared the file input, making the upload always fail.
//      We reset fields individually so we control exactly what gets cleared.
function openAttachKptnModal(loanId, borrowerName, pendingKptn = '') {
    document.getElementById('ak_loan_id').value         = loanId;
    document.getElementById('ak_borrower_name').innerText = borrowerName.toUpperCase();
    document.getElementById('ak_kptn_number').value     = pendingKptn;

    // Reset only the file input and error message — not the whole form
    const fileInput  = document.getElementById('ak_kptn_receipt');
    const fileLabel  = document.getElementById('akKptnFileLabel');
    const errMsg     = document.getElementById('ak_error_msg');
    const btn        = document.getElementById('btnSubmitKptn');

    if (fileInput)  fileInput.value  = '';
    if (fileLabel)  fileLabel.textContent = 'Choose file or drag it here';
    if (errMsg)     { errMsg.textContent = ''; errMsg.classList.add('hidden'); }
    if (btn)        { btn.innerText = 'Save'; btn.disabled = false; }

    const modal = document.getElementById('attachKptnModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Auto-open from URL query params (e.g. redirect from dashboard notification)
document.addEventListener('DOMContentLoaded', function() {
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open_attach') === '1') {
            const loanId = params.get('loan_id');
            const borrowerName = params.get('name') ? decodeURIComponent(params.get('name')) : '';
            const kptn = params.get('kptn') || '';
            if (loanId) {
                openAttachKptnModal(loanId, borrowerName, kptn);
                history.replaceState({}, '', window.location.pathname + window.location.hash);
            }
        }
    } catch (e) {
        console.error('Failed to auto-open attach modal', e);
    }
});

function toggleInputType(field) {
    const selectWrapper = document.getElementById(`wrapper_${field}_select`);
    const select        = document.getElementById(`${field}_select`);
    const input         = document.getElementById(`${field}_input`);
    const btn           = document.getElementById(`btn_toggle_${field}`);

    if (selectWrapper.classList.contains('hidden')) {
        selectWrapper.classList.remove('hidden');
        select.disabled = false;
        input.classList.add('hidden');
        input.disabled = true;
        btn.innerText = "Type Manually";
    } else {
        selectWrapper.classList.add('hidden');
        select.disabled = true;
        input.classList.remove('hidden');
        input.disabled = false;
        btn.innerText = "Select from List";
    }
}

function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    
    document.getElementById('m-id').innerText      = data.id || 'N/A';
    document.getElementById('m-fname').innerText   = data.first_name || 'N/A';
    document.getElementById('m-lname').innerText   = data.last_name || 'N/A';
    document.getElementById('m-date').innerText    = data.date || 'N/A';
    document.getElementById('m-contact').innerText = data.contact || 'N/A';
    document.getElementById('m-pn-no').innerText   = data.pn_no || 'N/A';
    document.getElementById('m-pn-mat').innerText  = data.pn_maturity || 'N/A';
    document.getElementById('m-region').innerText  = data.region || 'N/A';
    
    document.getElementById('m-amount').innerText = '₱ ' + parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('m-terms').innerText  = data.terms;
    document.getElementById('m-deduct').innerText = '₱ ' + parseFloat(data.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});

    if (window.kptnSetTitle) window.kptnSetTitle(data.name || '');

    // FIX: Branch on requires_kptn — delegate all viewer logic to kptnHandleState()
    // which lives in view_borrower.php and owns both states cleanly.
    const requiresKptn = data.requires_kptn == 1 || data.requires_kptn === true;
    if (window.kptnHandleState) {
        window.kptnHandleState(requiresKptn, data.file_path, data.mime_type, data.loan_id);
    }

    const btnVoid = document.getElementById('btnOpenVoidModal');
    if (btnVoid) {
        if (data.current_status === 'VOIDED' || data.current_status === 'FULLY PAID') {
            btnVoid.classList.add('hidden'); 
        } else {
            btnVoid.classList.remove('hidden');
            currentVoidId   = data.id;
            currentVoidName = data.name ? data.name.toUpperCase() : "UNKNOWN BORROWER"; 
        }
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openVoidConfirmationModal() {
    closeModal('viewBorrowerModal'); 
    document.getElementById('cvm_borrower_name').innerText       = currentVoidName;
    document.getElementById('cvm_employe_id').value              = currentVoidId;
    document.getElementById('cvm_borrower_name_input').value     = currentVoidName;
    document.getElementById('cvm_reason').value                  = ""; 

    const modal = document.getElementById('customVoidModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function openAddModal() {
    const modal = document.getElementById('addBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('addBorrowerForm').reset();
    
    document.getElementById('division_container').classList.add('hidden');
    document.getElementById('branch_container').classList.add('hidden');
    
    const idField = document.getElementById('employe_id');
    idField.value = "Fetching...";
    
    fetch(`${BASE_URL}/public/api/get_next_id.php`)
        .then(res => res.json())
        .then(data => {
            idField.value = data.success ? data.next_id : "Error";
        })
        .catch(() => { idField.value = "Error"; });

    if (!masterLocationsFetched) {
        fetch(`${BASE_URL}/public/api/get_master_locations.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setupCustomSearchable('region_search_input', 'region_results', data.data.regions, function(selectedRegion) {
                        handleRegionSelection(selectedRegion);
                    });
                    setupCustomSearchable('division_search_input', 'division_results', data.data.divisions);
                    masterLocationsFetched = true;
                }
            })
            .catch(err => console.error("Could not fetch master locations", err));
    }
}

function handleRegionSelection(regionObj) {
    const regionName = regionObj.label.toUpperCase();
    const regionCode = regionObj.value;
    
    document.getElementById('region_code_input').value = regionCode;

    const divContainer   = document.getElementById('division_container');
    const branchContainer= document.getElementById('branch_container');
    const divInput       = document.getElementById('division_search_input');
    const branchInput    = document.getElementById('branch_search_input');

    if (regionName.startsWith('HO') || regionName.includes('HEAD OFFICE')) {
        divContainer.classList.remove('hidden');
        branchContainer.classList.add('hidden');
        divInput.required    = true;
        branchInput.required = false;
        branchInput.value    = 'N/A'; 
        divInput.value       = '';
    } else {
        divContainer.classList.add('hidden');
        branchContainer.classList.remove('hidden');
        divInput.required    = false;
        branchInput.required = true;
        divInput.value       = 'N/A'; 
        branchInput.value    = '';
        branchInput.placeholder = 'LOADING BRANCHES...';
        
        fetch(`${BASE_URL}/public/api/get_branches.php?region_code=${regionCode}`)
            .then(res => res.json())
            .then(data => {
                branchInput.placeholder = 'SELECT BRANCH...';
                if (data.success) setupCustomSearchable('branch_search_input', 'branch_results', data.data);
            });
    }
}

function validateAndShowSchedule() {
    const form = document.getElementById('addBorrowerForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const formData = new FormData(form);
    tempBorrowerData = Object.fromEntries(formData.entries());

    document.getElementById('sched-name').innerText    = (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase();
    document.getElementById('sched-contact').innerText = tempBorrowerData.contact_number;
    document.getElementById('sched-amount').innerText  = parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sched-date').innerText    = tempBorrowerData.loan_granted;
    document.getElementById('sched-terms').innerText   = tempBorrowerData.terms + ' Months';
    
    document.getElementById('sched-pn').innerText            = "Generating PN...";
    document.getElementById('sched-maturity').innerText      = "Calculating..."; 
    document.getElementById('sched-deduct').innerText        = "Calculating...";
    document.getElementById('amortization-rows').innerHTML   = '<tr><td colspan="6" class="p-4 text-center text-slate-500 italic">Calculating Schedule...</td></tr>';

    closeModal('addBorrowerModal');
    const schedModal = document.getElementById('amortizationModal');
    schedModal.classList.remove('hidden');
    schedModal.classList.add('flex');

    fetchAmortizationSchedule(tempBorrowerData);
}

function fetchAmortizationSchedule(data) {
    const payload = {
        loan_amount: data.loan_amount,
        terms: data.terms,
        date_granted: data.loan_granted
    };

    fetch(`${BASE_URL}/public/api/calculate_amortization.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('sched-pn').innerText          = result.pn_number; 
            document.getElementById('sched-deduct').innerText      = parseFloat(result.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('sched-rate').innerText        = result.add_on_rate + ' % (Add-on)'; 
            document.getElementById('sched-initial-bal').innerText = parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('sched-maturity').innerText    = result.maturity_date;

            renderAmortizationTable(result.schedule);
            
            tempBorrowerData.pn_number    = result.pn_number;
            tempBorrowerData.pn_maturity  = result.maturity_date;
            tempBorrowerData.deduction    = result.deduction;
            tempBorrowerData.schedule     = result.schedule;
            tempBorrowerData.periodic_rate= result.periodic_rate; 
        } else {
            document.getElementById('importErrorMessage').innerHTML = "Calculation Error: " + result.error;
            document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
            closeModal('amortizationModal');
            openAddModal(); 
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('importErrorMessage').innerHTML = "System Error calling API";
        document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
        closeModal('amortizationModal');
        openAddModal();
    });
}

function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amortization-rows');
    tbody.innerHTML = ''; 

    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-red-50 border-b border-slate-200 transition-colors";
        tr.innerHTML = `
            <td class="p-1 text-[13px] border-r border-slate-200 text-center">${row.installment_no}</td>
            <td class="p-1 text-[13px] border-r border-slate-200 text-center">${row.date}</td>
            <td class="p-1 text-[13px] border-r border-slate-200 text-right text-slate-500">${parseFloat(row.principal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-1 text-[13px] border-r border-slate-200 text-right text-slate-500">${parseFloat(row.interest).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-1 text-[13px] border-r border-slate-200 font-bold text-black text-right">${parseFloat(row.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-1 text-[13px] font-bold text-right text-[#ce1126]">${parseFloat(row.balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
    });
}

function submitFinalBorrower() {
    const formData = new FormData();
    for (const key in tempBorrowerData) {
        if (tempBorrowerData[key] instanceof File) {
            formData.append(key, tempBorrowerData[key]);
        } else if (typeof tempBorrowerData[key] === 'object' && tempBorrowerData[key] !== null) {
            formData.append(key, JSON.stringify(tempBorrowerData[key]));
        } else {
            formData.append(key, tempBorrowerData[key]);
        }
    }

    fetch(`${BASE_URL}/public/actions/create_borrower.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.warning) alert("Loan Saved, BUT: " + data.warning);
            location.reload();
        } else {
            closeModal('amortizationModal');
            document.getElementById('importErrorMessage').innerHTML = (data.error || "Unknown error occurred").replace(/\n/g, '<br>');
            document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
        }
    })
    .catch(err => {
        console.error(err);
        closeModal('amortizationModal');
        document.getElementById('importErrorMessage').innerHTML = "System Error: Check console for details.";
        document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
    });
}

function openImportModal() {
    const modal = document.getElementById('importBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('importBorrowerForm').reset();
    document.getElementById('file-name-display').innerText = 'No file chosen';
}

function showImportPreview(data) {
    const list      = document.getElementById('import-list');
    const countSpan = document.getElementById('import-count');
    list.innerHTML  = '';
    countSpan.innerText = data.length;

    data.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = "flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded transition-colors group";
        li.innerHTML = `
            <div class="flex items-center gap-3 cursor-pointer flex-1 hover:border-[#e11d48]" onclick="viewImportDetail(${index})">
                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 group-hover:border-[#e11d48] group-hover:text-white">
                    ${index + 1}
                </div>
                <div>
                    <p class="text-slate-800 uppercase">${item.name}</p>
                    <p class="text-slate-400">ID: ${item.id} | Amount: ${parseFloat(item.loan_amount).toLocaleString()}</p>
                </div>
            </div>
            <input type="checkbox" class="import-checkbox w-5 h-5 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer" value="${index}" checked>
        `;
        list.appendChild(li);
    });

    const modal = document.getElementById('importPreviewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function viewImportDetail(index) {
    const item  = importedData[index];
    const modal = document.getElementById('importDetailModal');

    document.getElementById('imp-id').innerText      = item.id ? item.id : 'AUTO-GENERATE';
    document.getElementById('imp-name').innerText    = item.name;
    document.getElementById('imp-contact').innerText = item.contact_number || '000-000-0000';
    document.getElementById('imp-region').innerText  = item.region || 'N/A';
    document.getElementById('imp-pn').innerText      = item.pn_number || 'TBD';
    document.getElementById('imp-ref').innerText     = item.reference_number || 'N/A';
    document.getElementById('imp-granted').innerText = item.loan_granted || 'N/A';
    document.getElementById('imp-maturity').innerText= item.pn_maturity || 'N/A';
    document.getElementById('imp-amount').innerText  = '₱ ' + parseFloat(item.loan_amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('imp-terms').innerText   = item.terms + ' Months';
    document.getElementById('imp-deduct').innerText  = '₱ ' + parseFloat(item.deduction).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('imp-rate').innerText    = item.add_on_rate ? item.add_on_rate + '%' : 'N/A';

    // Conditionally show/hide the KPTN warning based on logic
    const kptnWarning = document.getElementById('imp-kptn-warning');
    if (kptnWarning) {
        // Evaluate the flag safely, whether it's a boolean, string, or number
        const requiresKptn = item.requires_kptn === true || item.requires_kptn === 'true' || item.requires_kptn == 1;
        
        if (requiresKptn) {
            kptnWarning.classList.remove('hidden'); // Show warning
        } else {
            kptnWarning.classList.add('hidden'); // Hide warning
        }
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function finalizeImport() {
    const checkboxes = document.querySelectorAll('.import-checkbox:checked:not(#select-all)');
    if (checkboxes.length === 0) return;

    const count = checkboxes.length;
    document.getElementById('confirmMessage').innerText = `Are you sure you want to save ${count} borrowers to the database?`;
    
    const confirmModal = document.getElementById('confirmSaveModal');
    confirmModal.classList.replace('hidden', 'flex');

    document.getElementById('realSubmitBtn').onclick = function() {
        confirmModal.classList.replace('flex', 'hidden'); 
        executeActualSave(checkboxes); 
    };
}

function executeActualSave(checkboxes) {
    const selectedIndices   = Array.from(checkboxes).map(cb => parseInt(cb.value));
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

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function setupCustomSearchable(inputId, resultsId, dataArray, onSelectCallback = null) {
    const input   = document.getElementById(inputId);
    const results = document.getElementById(resultsId);

    if (!input || !results) return;

    input.searchData        = dataArray;
    input.onSelectCallback  = onSelectCallback;

    if (input.dataset.searchInitialized === "true") return; 
    input.dataset.searchInitialized = "true";

    input.addEventListener('click', function() {
        if (this.value === '') renderList(this);
    });

    input.addEventListener('input', function() {
        const val      = this.value.toUpperCase();
        const filtered = this.searchData.filter(item => {
            let text = typeof item === 'object' ? item.label : item;
            return text && text.toUpperCase().includes(val);
        });
        renderList(this, filtered);
    });

    function renderList(targetInput, listToRender = null) {
        const dataToUse = listToRender || targetInput.searchData;
        results.innerHTML = '';
        
        if (dataToUse.length > 0) {
            results.classList.remove('hidden');
            dataToUse.forEach(item => {
                let text = typeof item === 'object' ? item.label : item;
                const div = document.createElement('div');
                div.className = "px-3 py-2 text-[12px] cursor-pointer hover:bg-slate-100 border-b border-slate-50 last:border-none uppercase text-slate-700 transition-colors";
                div.innerText = text;
                div.onclick = function(e) {
                    e.stopPropagation(); 
                    targetInput.value = text;
                    results.classList.add('hidden');
                    if (targetInput.onSelectCallback) targetInput.onSelectCallback(item);
                };
                results.appendChild(div);
            });
        } else {
            results.classList.add('hidden');
        }
    }

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('hidden');
        }
    });
}

// ── DOM READY ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    
    // Import Form
    const importForm = document.getElementById('importBorrowerForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('file-upload');
            
            if (fileInput.files.length === 0) { 
                document.getElementById('importErrorMessage').innerHTML = "Please select an Excel or CSV file before submitting.";
                document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
                return; 
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const btn          = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Analyzing File...";
            btn.disabled  = true;

            fetch(`${BASE_URL}/public/api/parse_import.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                btn.innerText = originalText;
                btn.disabled  = false;

                if (result.success) {
                    importedData = result.data; 
                    closeModal('importBorrowerModal');
                    showImportPreview(importedData);
                } else {
                    document.getElementById('importErrorMessage').innerHTML = result.error.replace(/\n/g, '<br>');
                    document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerText = originalText;
                btn.disabled  = false;
                document.getElementById('importErrorMessage').innerHTML = "System Error during upload. The file format may be invalid or corrupted.";
                document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
            });
        });
    }

    // KPTN Toggle (Add Borrower modal)
    const kptnToggle      = document.getElementById('requiresKptnToggle');
    const kptnContainer   = document.getElementById('kptnFieldsContainer');
    const toggleLabelText = document.getElementById('toggleLabelText');
    const depositAmountInput = document.getElementById('deposit_amount_input');
    const kptnNumberInput    = document.getElementById('kptn_number_input');
    const kptnReceiptInput   = document.getElementById('kptn_receipt_input');

    if (kptnToggle) {
        kptnToggle.addEventListener('change', function() {
            if (this.checked) {
                kptnContainer.style.display = 'grid'; 
                depositAmountInput.setAttribute('required', 'required');
                kptnNumberInput.setAttribute('required', 'required');
                kptnReceiptInput.setAttribute('required', 'required');
                if (toggleLabelText) {
                    toggleLabelText.textContent = "With KPTN Deposit & Attachment";
                    toggleLabelText.classList.replace('text-slate-400', 'text-slate-800');
                }
                this.value = "true";
            } else {
                kptnContainer.style.display = 'none';
                depositAmountInput.removeAttribute('required');
                kptnNumberInput.removeAttribute('required');
                kptnReceiptInput.removeAttribute('required');
                kptnNumberInput.value  = '';
                kptnReceiptInput.value = ''; 
                if (toggleLabelText) {
                    toggleLabelText.textContent = "No Deposit Required";
                    toggleLabelText.classList.replace('text-slate-800', 'text-slate-400');
                }
                this.value = "false";
            }
        });
    }

    // Search and Date Filter
    const searchInput = document.getElementById('searchInput');
    const fromDate    = document.getElementById('fromDate');
    const toDate      = document.getElementById('toDate');
    const viewAllBtn  = document.getElementById('viewAllBtn');
    const tableRows   = document.querySelectorAll('.borrower-row');

    function filterTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const from = fromDate ? fromDate.value : ''; 
        const to   = toDate   ? toDate.value   : '';     

        tableRows.forEach(row => {
            const id   = row.getAttribute('data-id').toLowerCase();
            const name = row.getAttribute('data-name');
            const date = row.getAttribute('data-date'); 

            const matchesSearch = id.includes(searchTerm) || name.includes(searchTerm);
            
            let matchesDate = true;
            if (from && date < from) matchesDate = false;
            if (to   && date > to)   matchesDate = false;

            row.style.display = (matchesSearch && matchesDate) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (fromDate)    fromDate.addEventListener('change', filterTable);
    if (toDate)      toDate.addEventListener('change', filterTable);
    
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (fromDate)    fromDate.value    = '';
            if (toDate)      toDate.value      = '';
            filterTable(); 
        });
    }
});