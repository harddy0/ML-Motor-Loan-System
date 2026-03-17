// --- GLOBAL VARIABLES ---
let currentPage = 1;
const rowsPerPage = 50;
let currentData = []; 
let searchTimeout = null;
let currentStatusFilter = "";

// ==========================================
// FILTER GUARD
// Returns true only when at least one valid
// filter is active. Date filter requires BOTH
// from AND to to count as active.
// ==========================================
function hasActiveFilter() {
    const search = document.getElementById('searchInput')?.value.trim() ?? '';
    const from   = document.getElementById('fromDate')?.value ?? '';
    const to     = document.getElementById('toDate')?.value ?? '';
    if (search.length > 0) return true;
    if (currentStatusFilter !== '') return true;
    if (from && to) return true;
    return false;
}

function renderEmptyState() {
    const tbody = document.getElementById('borrowersTableBody');
    const from  = document.getElementById('fromDate')?.value ?? '';
    const to    = document.getElementById('toDate')?.value ?? '';
    const partialDate = (from && !to) || (!from && to);

    if (partialDate) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-14 text-center">
                    <div class="flex flex-col items-center gap-2 text-slate-400">
                        <svg class="w-8 h-8 mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-[13px] font-semibold text-slate-500">Please select both a start and end date.</p>
                        <p class="text-[12px] text-slate-400">A complete date range is required to filter records.</p>
                    </div>
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-4 py-14 text-center">
                <div class="flex flex-col items-center gap-2 text-slate-400">
                    <svg class="w-8 h-8 mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-[13px] font-semibold text-slate-500">No records to display yet.</p>
                    <p class="text-[12px] text-slate-400">Use the search bar, status filter, or a complete date range to load records.</p>
                </div>
            </td>
        </tr>`;
}

function resetPaginationUI() {
    document.getElementById('page-start').innerText = 0;
    document.getElementById('page-end').innerText   = 0;
    document.getElementById('page-total').innerText = 0;
    document.getElementById('page-info').innerText  = 'Page 1 of 1';
    document.getElementById('btn-prev-page').disabled = true;
    document.getElementById('btn-next-page').disabled = true;
}

document.addEventListener("DOMContentLoaded", function() {
    const loader = document.getElementById('table-loader');
    if (loader) loader.classList.add('hidden');
    initializeFilters();
    initializePagination();
    renderEmptyState();
    resetPaginationUI();
});

function formatDisplayDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    return dateStr;
}

function formatToMMDDYYYY(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    return dateStr;
}

// ==========================================
// API FETCH LOGIC
// ==========================================
function fetchLedgerPage(page) {
    const search = document.getElementById('searchInput').value.trim();
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;

    const loader = document.getElementById('table-loader');

    // Block fetch if no valid filter is active
    if (!hasActiveFilter()) {
        loader.classList.add('hidden');
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    // Block fetch if only one date is provided
    const partialDate = (from && !to) || (!from && to);
    if (partialDate) {
        loader.classList.add('hidden');
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    loader.classList.remove('hidden');

    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/get_paginated_ledger.php?page=${page}&limit=${rowsPerPage}&search=${encodeURIComponent(search)}&from=${from}&to=${to}&status=${encodeURIComponent(currentStatusFilter)}`
        : `../../api/get_paginated_ledger.php?page=${page}&limit=${rowsPerPage}&search=${encodeURIComponent(search)}&from=${from}&to=${to}&status=${encodeURIComponent(currentStatusFilter)}`;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentData = result.payload.data;
                renderTable(currentData);
                updateStats(result.payload.stats);
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

// ==========================================
// SEARCH, FILTER, AND PAGINATION LOGIC
// ==========================================
function initializeFilters() {
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
            searchTimeout = setTimeout(() => { fetchLedgerPage(1); }, 500);
        });
        toggleClearSearchBtn();
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput.value.length === 0) return;
            searchInput.value = '';
            toggleClearSearchBtn();
            clearTimeout(searchTimeout);
            fetchLedgerPage(1);
            searchInput.focus();
        });
    }
    if (fromDate) fromDate.addEventListener('change', () => fetchLedgerPage(1));
    if (toDate) toDate.addEventListener('change', () => fetchLedgerPage(1));

    // Status Dropdown (pill-style, matches borrower page)
    const filterBtn = document.getElementById('ledgerFilterBtn');
    const filterMenu = document.getElementById('ledgerFilterMenu');
    const statusText = document.getElementById('selectedStatusText');
    const statusOptions = document.querySelectorAll('.ledger-status-opt');

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

                // Keep hidden input in sync (in case any other code reads it)
                const hiddenInput = document.getElementById('statusFilter');
                if (hiddenInput) hiddenInput.value = apiStatus;

                filterMenu.classList.add('hidden');
                fetchLedgerPage(1);
            });
        });

        document.addEventListener('click', (e) => {
            if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
                filterMenu.classList.add('hidden');
            }
        });
    }
}

function initializePagination() {
    const btnPrev = document.getElementById('btn-prev-page');
    const btnNext = document.getElementById('btn-next-page');

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) fetchLedgerPage(currentPage - 1);
        });
    }
    if (btnNext) {
        btnNext.addEventListener('click', () => fetchLedgerPage(currentPage + 1));
    }
}

function renderTable(data) {
    const tbody = document.getElementById('borrowersTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>`;
        return;
    }

    data.forEach(row => {
        const display_g_date = formatDisplayDate(row.g_date);
        const display_maturity = formatDisplayDate(row.maturity_date);
        
        let statusHtml = '';
        if(row.current_status === 'ONGOING') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-blue-100 text-blue-700 uppercase">Ongoing</span>`;
        } else if(row.current_status === 'FULLY PAID') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-green-100 text-green-700 uppercase">Fully Paid</span>`;
        } else if(row.current_status === 'VOIDED') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Void</span>`;
        } else {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">${row.current_status}</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 cursor-pointer transition-colors border-b border-slate-100 last:border-0';
        tr.onclick = () => handleRowClick(row.loan_id);
        
        tr.innerHTML = `
            <td class="px-4 py-0 text-[14px] text-slate-600 border-r uppercase border-slate-50 text-center font-mono font-bold">${row.pn_number || '--'}</td>
            <td class="px-4 py-0 text-[14px] text-slate-600 border-r border-slate-50 text-center font-mono">${row.employe_id || '--'}</td>
            <td class="px-1 py-0 text-[14px] text-slate-500 text-left border-r border-slate-50 font-mono">${display_g_date}</td>
            <td class="px-1 py-0 text-[14px] text-slate-500 text-left border-r border-slate-50 font-mono">${display_maturity}</td>
            <td class="px-4 py-0 text-[14px] text-slate-800 font-bold border-r border-slate-50 truncate uppercase">${row.name || '--'}</td>
            <td class="px-4 py-0 text-center font-mono">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateStats(stats) {
    document.getElementById('stat-total').innerText = stats.total;
    document.getElementById('stat-ongoing').innerText = stats.ongoing;
    document.getElementById('stat-paid').innerText = stats.paid;
    document.getElementById('stat-voided').innerText = stats.voided;
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
// MAIN PAGE INTERACTION & MODAL LOGIC
// ==========================================
function handleRowClick(loanId) {
    const selectedBorrower = currentData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower) {
        openLedgerModal(selectedBorrower);
    }
}

function openLedgerModal(borrowerData) {
    const modal = document.getElementById('ledgerDetailModal');
    const loader = document.getElementById('ledger-loading');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loader.classList.remove('hidden'); 
    // Populate fields (shared helper will set modal DOM values)
    if (typeof populateLedgerFields === 'function') {
        try { populateLedgerFields(borrowerData); } catch (e) { console.error('populateLedgerFields error', e); }
    }

    // Load transactions and render table
    fetchLedgerData(borrowerData.loan_id)
        .then(transactions => {
            renderLedgerTable(transactions, borrowerData); 
            loader.classList.add('hidden');
        })
        .catch(err => {
            console.error("Error loading ledger:", err);
            loader.classList.add('hidden');
            const rowsEl = document.getElementById('modal-ledger-rows');
            if (rowsEl) rowsEl.innerHTML = '<tr><td colspan="8" class="text-center text-red-500 py-4 font-bold">Failed to load schedule.</td></tr>';
        });
}

// Shared field population so other pages (dashboard notif modal) can reuse the logic
function populateLedgerFields(borrowerData) {
    const rowsEl = document.getElementById('modal-ledger-rows');
    if (rowsEl) rowsEl.innerHTML = '';

    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerText = text;
    };

    setText('modal-ledger-name', borrowerData.name || '--');
    setText('modal-ledger-id', borrowerData.employe_id || borrowerData.employee_id || 'N/A');
    setText('modal-ledger-pn', borrowerData.pn_number || '--');
    setText('modal-ledger-pndate', formatDisplayDate(borrowerData.g_date || borrowerData.date_granted));
    setText('modal-ledger-maturity', formatDisplayDate(borrowerData.maturity_date));
    setText('modal-ledger-terms', borrowerData.term_months ? (borrowerData.term_months + ' Months') : '--');
    setText('modal-ledger-ref', borrowerData.loan_ref_no || '--');
    setText('modal-ledger-region', borrowerData.region || '--');
    setText('modal-ledger-branch', (!borrowerData.branch || borrowerData.branch.trim().toUpperCase() === 'N/A') ? '' : borrowerData.branch);
    setText('modal-ledger-contact', borrowerData.contact_number || '--');

    const btn = document.getElementById('btn-export-ledger');
    if (btn && borrowerData.loan_id) btn.setAttribute('data-loan-id', borrowerData.loan_id);

    const statusBadge = document.getElementById('modal-ledger-status');
    if (statusBadge) {
        const statusText = borrowerData.current_status === 'VOIDED' ? 'VOID' : (borrowerData.current_status || '--');
        statusBadge.innerText = statusText;
        if (borrowerData.current_status === 'FULLY PAID') {
            statusBadge.className = "inline-block px-4 py-0 bg-green-100 text-green-700 text-[13px] font-black uppercase rounded-full";
        } else if (borrowerData.current_status === 'VOIDED') {
            statusBadge.className = "inline-block px-4 py-0 bg-orange-100 text-orange-700 text-[13px] font-black uppercase rounded-full";
        } else {
            statusBadge.className = "inline-block px-4 py-0 bg-blue-100 text-blue-700 text-[13px] font-black uppercase rounded-full";
        }
    }

    const principal = parseFloat(borrowerData.loan_amount) || 0;
    const semiAmort = parseFloat(borrowerData.semi_monthly_amt) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months) || 0;
    const totalRatePercent = (addOnRateDecimal * termMonths * 100).toFixed(0);

    setText('modal-ledger-rate', totalRatePercent + '%');
    if (document.getElementById('modal-ledger-principal')) document.getElementById('modal-ledger-principal').innerText = '₱ ' + principal.toLocaleString(undefined, {minimumFractionDigits:2});
    if (document.getElementById('modal-ledger-amort')) document.getElementById('modal-ledger-amort').innerText = '₱ ' + semiAmort.toLocaleString(undefined, {minimumFractionDigits:2});

    const monthlyAmort = semiAmort * 2;
    const monthlyElem = document.getElementById('modal-ledger-monthly-amort');
    if (monthlyElem) monthlyElem.innerText = '₱ ' + monthlyAmort.toLocaleString(undefined, {minimumFractionDigits:2});

    const depositAmount = parseFloat(borrowerData.deposit_amount) || 0;
    const depositWrapper = document.getElementById('security-deposit-wrapper');
    const depositText = document.getElementById('modal-ledger-security-deposit');
    if (depositText) depositText.innerText = '₱ ' + depositAmount.toLocaleString(undefined, {minimumFractionDigits:2});
    if (depositWrapper) depositWrapper.style.display = depositAmount > 0 ? 'flex' : 'none';
}

function closeLedgerModal() {
    document.getElementById('ledgerDetailModal').classList.remove('flex');
    document.getElementById('ledgerDetailModal').classList.add('hidden');
}

function fetchLedgerData(loanId) {
    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/get_ledger_transactions.php?loan_id=${loanId}`
        : `../../api/get_ledger_transactions.php?loan_id=${loanId}`;

    return fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) return result.data;
            throw new Error(result.error);
        });
}

function renderLedgerTable(transactions, borrowerData) {
    const tbody = document.getElementById('modal-ledger-rows');
    tbody.innerHTML = '';
    
    let totalPrincipalPaid = 0;
    let totalInterestPaid = 0;
    let totalCollected = 0;
    
    let sumTotalPrincipal = 0;
    let sumTotalInterest = 0;

    transactions.forEach(txn => {
        const principalAmt = parseFloat(txn.principal_amt || txn.principal) || 0;
        const interestAmt = parseFloat(txn.interest_amt || txn.interest) || 0;
        const totalAmt = parseFloat(txn.total_payment || txn.total) || 0;
        const balAmt = parseFloat(txn.remaining_bal || txn.balance) || 0;

        sumTotalPrincipal += principalAmt;
        sumTotalInterest += interestAmt;

        const statusClean = (txn.status || "").toUpperCase();
        const isPaid = statusClean === 'PAID';

        if(isPaid) {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
            totalCollected += totalAmt;
        }

        const balanceTextColor = isPaid ? '!text-slate-900' : '!text-[#e11d48]';
        
        let statusBadgeClass = 'bg-yellow-100 text-yellow-700 border border-yellow-200'; 
        if (isPaid) {
            statusBadgeClass = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
        } else if (statusClean === 'VOIDED') {
            statusBadgeClass = 'bg-orange-100 text-orange-700 border border-orange-200';
        } else if (statusClean === 'MISSED' || statusClean === 'UNPAID') {
            statusBadgeClass = 'bg-red-100 text-red-700 border border-red-200';
        }

        const displayScheduledDate = formatToMMDDYYYY(txn.scheduled_date);
        const remarksText = txn.remarks || '';

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-200 transition-colors border-b border-slate-100`;
        
        tr.innerHTML = `
            <td class="w-[16%] px-8 py-0 text-left text-slate-600 border-r border-slate-50 font-medium font-mono">
                ${displayScheduledDate}
            </td>
            <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-2">
                ${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-2">
                ${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[15%] px-3 py-0 text-right text-slate-900 border-r border-slate-50 bg-slate-50/10 font-medium pr-4">
                ${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[15%] px-3 py-0 text-right border-r border-slate-50 ${balanceTextColor} pr-4">
                ${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[10%] px-3 py-0 text-center">
                <span style="font-size: 11px !important; font-weight: 400 !important;" 
                        class="inline-block px-2 py-0.5 rounded-full text-[3px] ${statusBadgeClass}">
                    ${statusClean === 'VOIDED' ? 'VOID' : statusClean}
                </span>
            </td>
            <td class="px-3 py-0 text-slate-500 text-left truncate" title="${remarksText}">
                ${remarksText}
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Gross (uneducted) totals — these represent the original scheduled amounts
    const safeSetText = (id, val) => {
        const el = document.getElementById(id);
        if(el) el.innerText = '₱ ' + val.toLocaleString(undefined, {minimumFractionDigits:2});
    };

    // Gross values based on loan terms (original/undeducted)
    const loanAmount = parseFloat(borrowerData.loan_amount) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months) || 0;
    const grossPrincipal = loanAmount;
    const grossInterest = loanAmount * addOnRateDecimal * termMonths; // add-on interest over term
    const grossTotal = grossPrincipal + grossInterest;

    safeSetText('modal-ledger-gross-principal', grossPrincipal);
    safeSetText('modal-ledger-gross-interest', grossInterest);
    safeSetText('modal-ledger-gross-total', grossTotal);

    const principalBalance = sumTotalPrincipal - totalPrincipalPaid;
    const interestBalance = sumTotalInterest - totalInterestPaid;
    const totalOutstanding = principalBalance + interestBalance;

    safeSetText('modal-ledger-principal-paid', totalPrincipalPaid);
    safeSetText('modal-ledger-principal-balance', principalBalance);
    safeSetText('modal-ledger-interest-paid', totalInterestPaid);
    safeSetText('modal-ledger-interest-balance', interestBalance);
    // Total Payment = Principal Paid + Interest Paid (user-requested)
    safeSetText('modal-ledger-total-payment', totalPrincipalPaid + totalInterestPaid);
    safeSetText('modal-ledger-total-collected', totalCollected);
    safeSetText('modal-ledger-total-balance', totalOutstanding);
}

function exportLedgerExcel() {
    const btn = document.getElementById('btn-export-ledger');
    const loanId = btn.getAttribute('data-loan-id');
    if (!loanId) return;
    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/export_ledger.php?loan_id=${loanId}`
        : `../../api/export_ledger.php?loan_id=${loanId}`;
    window.location.href = url;
}