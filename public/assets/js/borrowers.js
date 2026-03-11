let tempBorrowerData = {};
let importedData = [];
let masterLocationsFetched = false;
let currentVoidId = "";
let currentVoidName = "";   

// Pagination Globals
let currentPage = 1;
const rowsPerPage = 50;
let currentBorrowersData = [];
let searchTimeout = null;
let currentStatusFilter = "";

// ==========================================
// DATE FORMATTER — "January 30, 2026"
// ==========================================
function formatDate(dateStr) {
    if (!dateStr || dateStr === 'N/A') return 'N/A';
    // Handles both "Y-m-d" (raw_date) and "mm / dd / yyyy" (pn_maturity)
    const cleaned = dateStr.toString().replace(/\s/g, '');
    // Try as-is first (works for Y-m-d)
    let d = new Date(dateStr + 'T00:00:00');
    // If that fails, try cleaned slash format (mm/dd/yyyy)
    if (isNaN(d.getTime())) {
        d = new Date(cleaned);
    }
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeFiltersAndPagination();
    fetchBorrowersPage(1);
    
    // Auto-open KPTN modal from URL query params
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

    setupImportModalLogic();
    setupAddModalLogic();
});

// ==========================================
// SERVER-SIDE FETCH LOGIC
// ==========================================
function fetchBorrowersPage(page) {
    const search = document.getElementById('searchInput').value.trim();
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;
    const loader = document.getElementById('table-loader');

    loader.classList.remove('hidden');

    const url = `${BASE_URL}/public/api/get_paginated_borrowers.php?page=${page}&limit=${rowsPerPage}&search=${encodeURIComponent(search)}&from=${from}&to=${to}&status=${encodeURIComponent(currentStatusFilter)}`;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentBorrowersData = result.payload.data;
                document.getElementById('tab-all-count').innerText = result.payload.total_overall;
                renderBorrowersTable(currentBorrowersData);
                updatePaginationUI(result.payload.total_filtered, result.payload.total_pages, result.payload.current_page);
            } else {
                console.error('Error fetching data:', result.error);
            }
        })
        .catch(error => console.error('Fetch error:', error))
        .finally(() => {
            loader.classList.add('hidden');
        });
}

function renderBorrowersTable(data) {
    const tbody = document.getElementById('borrowersTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>`;
        return;
    }

    data.forEach(borrower => {
        let statusHtml = '';
        if(borrower.current_status === 'ONGOING') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-blue-100 text-blue-700 uppercase">Ongoing</span>`;
        } else if(borrower.current_status === 'FULLY PAID') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-green-100 text-green-700 uppercase">Fully Paid</span>`;
        } else if(borrower.current_status === 'VOIDED') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Void</span>`;
        } else {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">${borrower.current_status || 'N/A'}</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-100 transition-colors cursor-pointer border-b border-slate-200 last:border-0';
        tr.onclick = () => handleBorrowerRowClick(borrower.loan_id);
        
        tr.innerHTML = `
            <td class="px-3 py-0 text-[14px] text-slate-600 border-r border-slate-100 font-mono truncate">${borrower.reference_no || '---'}</td>
            <td class="px-3 py-0 text-[14px] text-slate-600 border-r border-slate-100 text-left truncate">${formatDate(borrower.raw_date)}</td>
            <td class="px-3 py-0 text-[14px] text-slate-700 border-r border-slate-100 truncate">${borrower.id}</td>
            <td class="px-3 py-0 text-[14px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate">${borrower.name}</td>
            <td class="px-3 py-0 text-[12px] text-slate-800 border-r border-slate-100 font-mono truncate lowercase first-letter:uppercase"><span>${borrower.region}</span></td>
            <td class="px-3 py-0 text-center border-r border-slate-100">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

// ==========================================
// FILTERS & PAGINATION
// ==========================================
function initializeFiltersAndPagination() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');

    const toggleClearSearchBtn = () => {
        if (!searchInput || !clearSearchBtn) return;
        clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
    };
    
    // Debounced search — waits 500ms after the user stops typing before querying
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            toggleClearSearchBtn();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchBorrowersPage(1); }, 500);
        });
        toggleClearSearchBtn();
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput.value.length === 0) return;
            searchInput.value = '';
            toggleClearSearchBtn();
            clearTimeout(searchTimeout);
            fetchBorrowersPage(1);
            searchInput.focus();
        });
    }
    if (fromDate) fromDate.addEventListener('change', () => fetchBorrowersPage(1));
    if (toDate) toDate.addEventListener('change', () => fetchBorrowersPage(1));

    // Status Dropdown
    const filterBtn = document.getElementById('borrowerFilterBtn');
    const filterMenu = document.getElementById('borrowerFilterMenu');
    const statusText = document.getElementById('selectedStatusText');
    const statusOptions = document.querySelectorAll('.status-opt');

    if (filterBtn) {
        filterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterMenu.classList.toggle('hidden');
        });
        
        statusOptions.forEach(option => {
            option.addEventListener('click', () => {
                const apiStatus = option.getAttribute('data-status');
                const labelStatus = option.getAttribute('data-label');
                
                statusText.textContent = labelStatus;
                currentStatusFilter = apiStatus;
                filterMenu.classList.add('hidden');
                
                fetchBorrowersPage(1);
            });
        });

        document.addEventListener('click', (e) => {
            if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
                filterMenu.classList.add('hidden');
            }
        });
    }

    // Pagination Buttons
    document.getElementById('btn-prev-page').addEventListener('click', () => {
        if (currentPage > 1) fetchBorrowersPage(currentPage - 1);
    });
    
    document.getElementById('btn-next-page').addEventListener('click', () => {
        fetchBorrowersPage(currentPage + 1);
    });
}

function updatePaginationUI(totalFilteredItems, totalPages, newCurrentPage) {
    currentPage = newCurrentPage;
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, totalFilteredItems);

    document.getElementById('page-start').innerText = totalFilteredItems === 0 ? 0 : startIndex + 1;
    document.getElementById('page-end').innerText = endIndex;
    document.getElementById('page-total').innerText = totalFilteredItems;
    document.getElementById('page-info').innerText = `Page ${currentPage} of ${totalPages || 1}`;

    document.getElementById('btn-prev-page').disabled = currentPage <= 1;
    document.getElementById('btn-next-page').disabled = currentPage >= totalPages;
}

// ==========================================
// MODAL INTERACTIONS
// ==========================================
function handleBorrowerRowClick(loanId) {
    const selectedBorrower = currentBorrowersData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower) openViewModal(selectedBorrower);
}

function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    
    document.getElementById('m-id').innerText      = data.id || 'N/A';
    document.getElementById('m-fname').innerText   = data.first_name || 'N/A';
    document.getElementById('m-lname').innerText   = data.last_name || 'N/A';
    document.getElementById('m-date').innerText    = formatDate(data.raw_date) || 'N/A';
    document.getElementById('m-contact').innerText = data.contact || 'N/A';
    document.getElementById('m-ref-no').innerText   = data.reference_no || data.reference_number || 'N/A';
    document.getElementById('m-pn-mat').innerText  = formatDate(data.pn_maturity) || 'N/A';
    document.getElementById('m-region').innerText  = data.region || 'N/A';
    
    const loanAmount = parseFloat(data.loan_amount || 0);
    const semiMonthly = parseFloat(data.deduction || 0);
    const monthly = semiMonthly * 2;

    document.getElementById('m-amount').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + loanAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-terms').innerText  = data.terms;
    document.getElementById('m-deduct').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + semiMonthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-monthly').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + monthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';

    if (window.kptnSetTitle) window.kptnSetTitle(data.name || '');

    const requiresKptn = data.requires_kptn == 1 || data.requires_kptn === true;
    if (window.kptnHandleState) {
        window.kptnHandleState(requiresKptn, data.file_path, data.mime_type, data.loan_id);
    }

    const btnVoid = document.getElementById('btnOpenVoidModal');
    if (btnVoid) {
        const paymentStarted = parseInt(data.paid_count || 0) > 0;
        if (data.current_status === 'VOIDED' || data.current_status === 'FULLY PAID' || paymentStarted) {
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

// ==========================================
// OTHER UI & LOGIC
// ==========================================
window.switchTab = function(tab) {
    const activeTabBtn  = document.getElementById('tab-active');
    const pendingTabBtn = document.getElementById('tab-pending');
    const activeTable   = document.getElementById('table-active');
    const pendingTable  = document.getElementById('table-pending');

    if (!activeTabBtn || !pendingTabBtn || !activeTable || !pendingTable) return;

    if (tab === 'active') {
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('hidden', 'flex');
        pendingTable.classList.replace('block', 'hidden');
    } else if (tab === 'pending') {
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('flex', 'hidden');
        pendingTable.classList.replace('hidden', 'block');
    }
};

function openAttachKptnModal(loanId, borrowerName, pendingKptn = '') {
    document.getElementById('ak_loan_id').value = loanId;
    document.getElementById('ak_borrower_name').innerText = borrowerName.toUpperCase();
    document.getElementById('ak_kptn_number').textContent = pendingKptn;

    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');
    const errMsg = document.getElementById('ak_error_msg');
    const btn = document.getElementById('btnSubmitKptn');

    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.textContent = 'Choose file or drag it here';
    if (errMsg) { errMsg.textContent = ''; errMsg.classList.add('hidden'); }
    if (btn) { btn.innerText = 'Save'; btn.disabled = false; }

    const modal = document.getElementById('attachKptnModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openVoidConfirmationModal() {
    closeModal('viewBorrowerModal'); 
    document.getElementById('cvm_borrower_name').innerText = currentVoidName;
    document.getElementById('cvm_employe_id').value = currentVoidId;
    document.getElementById('cvm_borrower_name_input').value = currentVoidName;
    document.getElementById('cvm_reason').value = ""; 

    const modal = document.getElementById('customVoidModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// ------------------------------------------
// Import Handling
// ------------------------------------------
function setupImportModalLogic() {
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
                    showImportPreview(importedData);
                } else {
                    document.getElementById('importErrorMessage').innerHTML = result.error.replace(/\n/g, '<br>');
                    document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
                }
            })
            .catch(err => {
                btn.innerText = originalText;
                btn.disabled = false;
                document.getElementById('importErrorMessage').innerHTML = "System Error during upload.";
                document.getElementById('importErrorModal').classList.replace('hidden', 'flex');
            });
        });
    }
}

function openImportModal() {
    const modal = document.getElementById('importBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('importBorrowerForm').reset();
    document.getElementById('file-name-display').innerText = 'No file chosen';
}

function showImportPreview(data) {
    const list = document.getElementById('import-list');
    const countSpan = document.getElementById('import-count');
    list.innerHTML = '';
    countSpan.innerText = data.length;

    data.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = "flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded transition-colors group";
        li.innerHTML = `
            <div class="flex items-center gap-3 cursor-pointer flex-1 hover:border-[#e11d48]" onclick="viewImportDetail(${index})">
                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 group-hover:border-[#e11d48] group-hover:text-white">${index + 1}</div>
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
    const amountValue = parseFloat(item.loan_amount);
    const formattedAmount = Number.isFinite(amountValue)
        ? amountValue.toLocaleString(undefined, { minimumFractionDigits: 2 })
        : 'N/A';
    document.getElementById('imp-amount').innerText = formattedAmount;
    document.getElementById('imp-terms').innerText = item.terms + ' Months';
    const deductionValue = parseFloat(item.deduction);
    const formattedDeduction = Number.isFinite(deductionValue)
        ? deductionValue.toLocaleString(undefined, { minimumFractionDigits: 2 })
        : 'N/A';
    const formattedMonthlyAmort = Number.isFinite(deductionValue)
        ? (deductionValue * 2).toLocaleString(undefined, { minimumFractionDigits: 2 })
        : 'N/A';

    document.getElementById('imp-deduct').innerText = formattedDeduction;
    document.getElementById('imp-monthly-amort').innerText = formattedMonthlyAmort;
    document.getElementById('imp-rate').innerText = item.add_on_rate ? item.add_on_rate + '%' : 'N/A';

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
    document.getElementById('confirmMessage').innerText = `Are you sure you want to save ${count} borrowers to the database?`;
    
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

// ------------------------------------------
// Add/Schedule Logic
// ------------------------------------------
function setupAddModalLogic() {
    const kptnToggle = document.getElementById('requiresKptnToggle');
    const kptnContainer = document.getElementById('kptnFieldsContainer');
    const toggleLabelText = document.getElementById('toggleLabelText');
    const depositAmountInput = document.getElementById('deposit_amount_input');
    const kptnNumberInput = document.getElementById('kptn_number_input');
    const kptnReceiptInput = document.getElementById('kptn_receipt_input');

    if (kptnToggle) {
        kptnToggle.addEventListener('change', function() {
            if (this.checked) {
                kptnContainer.style.display = 'grid'; 
                depositAmountInput.setAttribute('required', 'required');
                kptnNumberInput.setAttribute('required', 'required');
                kptnReceiptInput.setAttribute('required', 'required');
                if (toggleLabelText) {
                    toggleLabelText.textContent = "Security Deposit";
                    toggleLabelText.classList.replace('text-slate-400', 'text-slate-800');
                }
                this.value = "true";
            } else {
                kptnContainer.style.display = 'none';
                depositAmountInput.removeAttribute('required');
                kptnNumberInput.removeAttribute('required');
                kptnReceiptInput.removeAttribute('required');
                kptnNumberInput.value = '';
                kptnReceiptInput.value = ''; 
                if (toggleLabelText) {
                    toggleLabelText.textContent = "Security Deposit";
                    toggleLabelText.classList.replace('text-slate-800', 'text-slate-400');
                }
                this.value = "false";
            }
        });

        kptnContainer.style.display = 'none';
        depositAmountInput.removeAttribute('required');
        kptnNumberInput.removeAttribute('required');
        kptnReceiptInput.removeAttribute('required');
        if (toggleLabelText) {
            toggleLabelText.classList.replace('text-slate-800', 'text-slate-400');
        }
        kptnToggle.value = 'false';

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

    const divContainer = document.getElementById('division_container');
    const branchContainer = document.getElementById('branch_container');
    const divInput = document.getElementById('division_search_input');
    const branchInput = document.getElementById('branch_search_input');

    if (regionName.startsWith('HO') || regionName.includes('HEAD OFFICE')) {
        divContainer.classList.remove('hidden');
        branchContainer.classList.add('hidden');
        divInput.required = true;
        branchInput.required = false;
        branchInput.value = 'N/A'; 
        divInput.value = '';
    } else {
        divContainer.classList.add('hidden');
        branchContainer.classList.remove('hidden');
        divInput.required = false;
        branchInput.required = true;
        divInput.value = 'N/A'; 
        branchInput.value = '';
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

    document.getElementById('sched-name').innerText = (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase();
    document.getElementById('sched-contact').innerText = tempBorrowerData.contact_number;
    document.getElementById('sched-amount').innerText = parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sched-date').innerText = formatFullDate(tempBorrowerData.loan_granted);
    document.getElementById('sched-terms').innerText = tempBorrowerData.terms + ' Months';
    
    document.getElementById('sched-pn').innerText = "Generating PN...";
    document.getElementById('sched-maturity').innerText = "Calculating..."; 
    document.getElementById('sched-deduct').innerText = "Calculating...";
    document.getElementById('amortization-rows').innerHTML = '<tr><td colspan="6" class="p-4 text-center text-slate-500 italic">Calculating Schedule...</td></tr>';

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
            document.getElementById('sched-pn').innerText = result.pn_number; 
            document.getElementById('sched-deduct').innerText = parseFloat(result.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('sched-rate').innerText = result.add_on_rate + ' % (Add-on)'; 
            document.getElementById('sched-initial-bal').innerText = parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('sched-maturity').innerText = formatFullDate(result.maturity_date);

            renderAmortizationTable(result.schedule);
            
            tempBorrowerData.pn_number = result.pn_number;
            tempBorrowerData.pn_maturity = result.maturity_date;
            tempBorrowerData.deduction = result.deduction;
            tempBorrowerData.schedule = result.schedule;
            tempBorrowerData.periodic_rate = result.periodic_rate; 
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

function formatFullDate(dateStr) {
    if (!dateStr) return '';
    // Backend sends PHP 'M d, Y' format e.g. "Mar 15, 2026"
    const dt = new Date(dateStr);
    if (isNaN(dt)) return dateStr; // fallback: show as-is
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amortization-rows');
    tbody.innerHTML = ''; 

    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-red-50 border-b border-slate-200 transition-colors";
        tr.innerHTML = `
            <td class="p-1 text-[13px] border-r border-slate-200 text-center">${row.installment_no}</td>
            <td class="p-1 text-[13px] border-r border-slate-200 text-center">${formatFullDate(row.date)}</td>
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

function setupCustomSearchable(inputId, resultsId, dataArray, onSelectCallback = null) {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);

    if (!input || !results) return;

    input.searchData = dataArray;
    input.onSelectCallback = onSelectCallback;

    if (input.dataset.searchInitialized === "true") return; 
    input.dataset.searchInitialized = "true";

    input.addEventListener('click', function() {
        if (this.value === '') renderList(this);
    });

    input.addEventListener('input', function() {
        const val = this.value.toUpperCase();
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