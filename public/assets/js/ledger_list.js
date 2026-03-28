// ==========================================
// LEDGER LIST: Master Table & Pagination
// ==========================================

let currentPage = 1;
const rowsPerPage = 50;
let currentData = []; 
let searchTimeout = null;
let currentStatusFilter = "";

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

function fetchLedgerPage(page) {
    const search = document.getElementById('searchInput').value.trim();
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;
    const loader = document.getElementById('table-loader');

    if (!hasActiveFilter() || ((from && !to) || (!from && to))) {
        if(loader) loader.classList.add('hidden');
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

function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');

    const toggleClearSearchBtn = () => {
        if (!searchInput || !clearSearchBtn) return;
        clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
    };

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
    if (btnPrev) btnPrev.addEventListener('click', () => { if (currentPage > 1) fetchLedgerPage(currentPage - 1); });
    if (btnNext) btnNext.addEventListener('click', () => fetchLedgerPage(currentPage + 1));
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
        } else if(row.current_status === 'INACTIVE') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">Inactive</span>`;
        } else {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">${row.current_status}</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 cursor-pointer transition-colors border-b border-slate-100 last:border-0';
        tr.onclick = () => handleRowClick(row.loan_id);
        
        tr.innerHTML = `
            <td class="px-4 py-0 text-[14px] text-slate-600 border-r uppercase border-slate-50 text-center font-mono font-bold">${row.pn_number || '--'}</td>
            <td class="px-4 py-0 text-[14px] text-slate-600 border-r border-slate-50 text-center font-mono">${row.employe_id || '--'}</td>
            <td class="px-1 py-0 text-[14px] text-slate-500 text-center border-r border-slate-50 font-mono">${display_g_date}</td>
            <td class="px-1 py-0 text-[14px] text-slate-500 text-center border-r border-slate-50 font-mono">${display_maturity}</td>
            <td class="px-4 py-0 text-[14px] text-slate-800 font-bold border-r border-slate-50 truncate uppercase">${row.name || '--'}</td>
            <td class="px-4 py-0 text-center font-mono">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateStats(stats) {
    const elTotal = document.getElementById('stat-total');
    const elOngoing = document.getElementById('stat-ongoing');
    const elPaid = document.getElementById('stat-paid');
    const elVoided = document.getElementById('stat-voided');
    const elInactive = document.getElementById('stat-inactive');
    
    if(elTotal) elTotal.innerText = stats.total;
    if(elOngoing) elOngoing.innerText = stats.ongoing;
    if(elPaid) elPaid.innerText = stats.paid;
    if(elVoided) elVoided.innerText = stats.voided;
    if(elInactive) elInactive.innerText = stats.inactive ?? 0;
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

function handleRowClick(loanId) {
    const selectedBorrower = currentData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower && typeof openLedgerModal === 'function') {
        openLedgerModal(selectedBorrower);
    }
}