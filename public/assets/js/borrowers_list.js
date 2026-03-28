let tempBorrowerData = {};
let importedData = [];
let masterLocationsFetched = false;
let currentVoidId = "";
let currentVoidName = "";   
let currentInactivateLoanId = "";
let currentInactivateName = "";

// Pagination Globals
let currentPage = 1;
const rowsPerPage = 50;
let currentBorrowersData = [];
let searchTimeout = null;
let currentStatusFilter = "";
let currentVoidedView = "inactive";

// ==========================================
// DATE FORMATTER — "January 30, 2026"
// ==========================================
function formatDate(dateStr) {
    if (!dateStr || dateStr === 'N/A') return 'N/A';
    const cleaned = dateStr.toString().replace(/\s/g, '');
    let d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d.getTime())) {
        d = new Date(cleaned);
    }
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

// ==========================================
// FILTER GUARD
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
    const tbodyId = currentStatusFilter === 'FULLY PAID' ? 'fullyPaidTableBody' : 'borrowersTableBody';
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const from  = document.getElementById('fromDate')?.value ?? '';
    const to    = document.getElementById('toDate')?.value ?? '';
    const partialDate = (from && !to) || (!from && to);

    if (partialDate) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-14 text-center">
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
            <td colspan="8" class="px-4 py-14 text-center">
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

    const fpStart = document.getElementById('fully-paid-page-start');
    const fpEnd = document.getElementById('fully-paid-page-end');
    const fpTotal = document.getElementById('fully-paid-page-total');
    const fpInfo = document.getElementById('fully-paid-page-info');
    const fpPrev = document.getElementById('btn-prev-page-fully-paid');
    const fpNext = document.getElementById('btn-next-page-fully-paid');
    if (fpStart) fpStart.innerText = 0;
    if (fpEnd) fpEnd.innerText = 0;
    if (fpTotal) fpTotal.innerText = 0;
    if (fpInfo) fpInfo.innerText = 'Page 1 of 1';
    if (fpPrev) fpPrev.disabled = true;
    if (fpNext) fpNext.disabled = true;
}

function getVoidedCategory(borrower) {
    const reason = (borrower?.inactivate_reason || borrower?.void_reason || '').toString().trim().toUpperCase();
    if (reason === 'AWOL' || reason === 'RESIGNED') return 'inactive';
    return 'void';
}

document.addEventListener('DOMContentLoaded', function() {
    initializeFiltersAndPagination();
    renderEmptyState();
    resetPaginationUI();
});

// ==========================================
// SERVER-SIDE FETCH LOGIC
// ==========================================
function fetchBorrowersPage(page) {
    const search = document.getElementById('searchInput').value.trim();
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;

    if (!hasActiveFilter()) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    const partialDate = (from && !to) || (!from && to);
    if (partialDate) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    const loaderId = currentStatusFilter === 'FULLY PAID' ? 'table-loader-fully-paid' : 'table-loader';
    const loader = document.getElementById(loaderId);
    if (!loader) return;
    loader.classList.remove('hidden');

    const url = `${BASE_URL}/public/api/get_paginated_borrowers.php?page=${page}&limit=${rowsPerPage}&search=${encodeURIComponent(search)}&from=${from}&to=${to}&status=${encodeURIComponent(currentStatusFilter)}`;

    fetch(url)
        .then(response => response.json())
        .then(result => {
                if (result.success) {
                currentBorrowersData = result.payload.data;
                if (currentStatusFilter !== 'VOIDED') {
                    if (currentStatusFilter === 'FULLY PAID') {
                        const fullyPaidCountEl = document.getElementById('tab-fully-paid-count');
                        if (fullyPaidCountEl) fullyPaidCountEl.innerText = result.payload.total_filtered || 0;
                    } else {
                        const allCountEl = document.getElementById('tab-all-count');
                        if (allCountEl) allCountEl.innerText = result.payload.total_filtered || 0;
                    }
                }
                const activeTable = document.getElementById('table-active');
                const fullyPaidTable = document.getElementById('table-fully-paid');
                const inactiveTable = document.getElementById('table-inactive');
                const voidTable = document.getElementById('table-void');
                if (currentStatusFilter === 'VOIDED') {
                    if (activeTable) activeTable.classList.add('hidden');
                    if (fullyPaidTable) fullyPaidTable.classList.add('hidden');
                    if (currentVoidedView === 'void') {
                        if (inactiveTable) inactiveTable.classList.add('hidden');
                        if (voidTable) voidTable.classList.remove('hidden');
                    } else {
                        if (voidTable) voidTable.classList.add('hidden');
                        if (inactiveTable) inactiveTable.classList.remove('hidden');
                    }
                    renderVoidedTable(currentBorrowersData, currentVoidedView);
                } else if (currentStatusFilter === 'FULLY PAID') {
                    if (inactiveTable) inactiveTable.classList.add('hidden');
                    if (voidTable) voidTable.classList.add('hidden');
                    if (activeTable) activeTable.classList.add('hidden');
                    if (fullyPaidTable) fullyPaidTable.classList.remove('hidden');
                    renderBorrowersTable(currentBorrowersData, 'fullyPaidTableBody');
                } else {
                    if (inactiveTable) inactiveTable.classList.add('hidden');
                    if (voidTable) voidTable.classList.add('hidden');
                    if (fullyPaidTable) fullyPaidTable.classList.add('hidden');
                    if (activeTable) activeTable.classList.remove('hidden');
                    renderBorrowersTable(currentBorrowersData);
                }
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

function renderBorrowersTable(data, tbodyId = 'borrowersTableBody') {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>`;
        return;
    }

    data.forEach(borrower => {
        let statusHtml = '';
        if(borrower.current_status === 'ONGOING') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-blue-100 text-blue-700 uppercase">Ongoing</span>`;
        } else if(borrower.current_status === 'FULLY PAID') {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-green-100 text-green-700 uppercase">Fully Paid</span>`;
        } else if(borrower.current_status === 'VOIDED') {
            const inactReason = (borrower.inactivate_reason || borrower.void_reason || '').toString().trim();
            if (inactReason.length > 0) {
                statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Inactive</span>`;
            } else {
                statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Void</span>`;
            }
        } else {
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">${borrower.current_status || 'N/A'}</span>`;
        }

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-100 transition-colors cursor-pointer border-b border-slate-200 last:border-0';
        tr.onclick = () => handleBorrowerRowClick(borrower.loan_id);

        const hasAmount = parseFloat(String(borrower.deposit_amount ?? 0).replace(/,/g, '')) > 0;
        const rawKptn = String(borrower.kptn || '').trim();
        const hasKptnNumber = !!rawKptn && !/^NR_/i.test(rawKptn);
        const hasKptnForm = !!(borrower.file_path && String(borrower.file_path).trim() && borrower.mime_type && String(borrower.mime_type).trim());
        const isCompleteKptn = hasAmount && hasKptnNumber && hasKptnForm;
        const disabledAttrs = isCompleteKptn ? 'disabled aria-disabled="true"' : '';
        const disabledClass = isCompleteKptn
            ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
            : 'bg-red-50 text-[#ce1126] hover:bg-[#ce1126] hover:text-white';
        const actionHtml = `<button type="button" ${disabledAttrs} class="inline-flex items-center gap-1 px-2 py-1 font-mono rounded-full transition-colors leading-none ${disabledClass}" style="font-size:9px;" onclick="event.stopPropagation(); if (!this.disabled) openSecurityDepositModalByLoanId(${borrower.loan_id})"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg><span style="font-size:9px;line-height:1;">KPTN</span></button>`;
        
            tr.innerHTML = `
            <td class="px-2 py-0 text-[13px] text-slate-800 border-r border-slate-100 uppercase font-mono truncate text-center">${borrower.pn_no || '---'}</td>
            <td class="px-2 py-0 text-[13px] text-slate-600 border-r border-slate-100 uppercase font-mono truncate text-center whitespace-nowrap">${borrower.reference_no || '---'}</td>
            <td class="px-2 py-0 text-[13px] text-slate-600 border-r border-slate-100 text-center truncate">${formatDate(borrower.raw_date)}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center truncate">${borrower.id}</td>
            <td class="px-3 py-0 text-[13px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate whitespace-nowrap">${borrower.name}</td>
                <td class="px-2 py-0 text-[12px] text-slate-800 border-r border-slate-100 font-mono truncate lowercase first-letter:uppercase text-center whitespace-nowrap"><span>${borrower.region}</span></td>
            <td class="px-2 py-0 text-center border-r border-slate-100">${statusHtml}</td>
            <td class="px-2 py-0 text-center">${actionHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderVoidedTable(data, mode = 'inactive') {
    const inactiveTbody = document.getElementById('inactiveBorrowersTableBody');
    const voidTbody = document.getElementById('voidBorrowersTableBody');
    const tbody = mode === 'void' ? voidTbody : inactiveTbody;
    if (!tbody) return;
    tbody.innerHTML = '';

    const inactiveCountEl = document.getElementById('tab-inactive-count');
    const voidCountEl = document.getElementById('tab-void-count');

    const colSpan = mode === 'void' ? 7 : 8;

    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>`;
        if (inactiveCountEl) inactiveCountEl.innerText = '0';
        if (voidCountEl) voidCountEl.innerText = '0';
        return;
    }

    const inactiveRows = data.filter(b => getVoidedCategory(b) === 'inactive');
    const voidRows = data.filter(b => getVoidedCategory(b) === 'void');
    const filtered = mode === 'void' ? voidRows : inactiveRows;

    if (inactiveCountEl) inactiveCountEl.innerText = String(inactiveRows.length || 0);
    if (voidCountEl) voidCountEl.innerText = String(voidRows.length || 0);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>`;
        return;
    }

    filtered.forEach(b => {
        const isVoidMode = mode === 'void';
        const statusHtml = isVoidMode
            ? `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Void</span>`
            : `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-slate-100 text-slate-700 uppercase">Inactive</span>`;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-100 transition-colors border-b border-slate-200 last:border-0 cursor-pointer';
        tr.onclick = () => handleBorrowerRowClick(b.loan_id);
        const dateValue = mode === 'void'
            ? (b.voided_at || b.inactivated_at)
            : (b.inactivated_at || b.voided_at);
        const byValue = mode === 'void'
            ? (b.voided_by || b.inactivated_by || '')
            : (b.inactivated_by || b.voided_by || '');
        tr.innerHTML = mode === 'void'
            ? `
            <td class="px-2 py-0 text-[13px] text-slate-800 border-r border-slate-100 text-center">${statusHtml}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center">${b.id || ''}</td>
            <td class="px-3 py-0 text-[13px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate whitespace-nowrap">${b.name || ''}</td>
            <td class="px-2 py-0 text-[12px] text-slate-800 border-r border-slate-100 font-mono text-center whitespace-nowrap">${b.region || ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 whitespace-nowrap">${b.inactivate_reason || b.void_reason || ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center whitespace-nowrap">${dateValue ? formatDate(dateValue) : ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 text-center whitespace-nowrap uppercase">${String(byValue).toUpperCase()}</td>
        `
            : `
            <td class="px-2 py-0 text-[13px] text-slate-800 border-r border-slate-100 text-center">${statusHtml}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center">${b.id || ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-600 border-r border-slate-100 text-center truncate">${b.reference_no || b.reference_number || ''}</td>
            <td class="px-3 py-0 text-[13px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate whitespace-nowrap">${b.name || ''}</td>
            <td class="px-2 py-0 text-[12px] text-slate-800 border-r border-slate-100 font-mono text-center whitespace-nowrap">${b.region || ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 whitespace-nowrap">${b.inactivate_reason || b.void_reason || ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center whitespace-nowrap">${dateValue ? formatDate(dateValue) : ''}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 text-center whitespace-nowrap uppercase">${String(byValue).toUpperCase()}</td>
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

    const filterBtn = document.getElementById('borrowerFilterBtn');
    const filterMenu = document.getElementById('borrowerFilterMenu');
    const statusText = document.getElementById('selectedStatusText');
    const statusOptions = document.querySelectorAll('.status-opt');

    if (filterBtn) {
        const positionFilterMenu = () => {
            if (!filterMenu || !filterBtn) return;
            filterMenu.style.left = '';
            filterMenu.style.right = '';
            filterMenu.style.minWidth = filterBtn.offsetWidth + 'px';

            const btnRect = filterBtn.getBoundingClientRect();
            const menuRect = filterMenu.getBoundingClientRect();
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const overflowRight = btnRect.left + menuRect.width > viewportWidth;

            if (overflowRight) {
                filterMenu.style.left = 'auto';
                filterMenu.style.right = '0';
            } else {
                filterMenu.style.left = '0';
                filterMenu.style.right = '';
            }
        };

        filterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterMenu.classList.toggle('hidden');
            if (!filterMenu.classList.contains('hidden')) {
                setTimeout(positionFilterMenu, 0);
            }
        });
        
        statusOptions.forEach(option => {
            option.addEventListener('click', () => {
                const apiStatus = option.getAttribute('data-status');
                const labelStatus = option.getAttribute('data-label');

                statusText.textContent = labelStatus;
                filterMenu.classList.add('hidden');

                if (apiStatus === 'VOIDED') {
                    const nextTab = labelStatus === 'Voided' ? 'void' : 'inactive';
                    switchTab(nextTab);
                    return;
                }

                if (apiStatus === 'FULLY PAID') {
                    switchTab('fully-paid');
                    return;
                }

                currentStatusFilter = apiStatus;
                switchTab('active');
            });
        });

        document.addEventListener('click', (e) => {
            if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
                filterMenu.classList.add('hidden');
            }
        });
    }

    document.getElementById('btn-prev-page').addEventListener('click', () => {
        if (currentPage > 1) fetchBorrowersPage(currentPage - 1);
    });
    
    document.getElementById('btn-next-page').addEventListener('click', () => {
        fetchBorrowersPage(currentPage + 1);
    });

    const fullyPaidPrev = document.getElementById('btn-prev-page-fully-paid');
    const fullyPaidNext = document.getElementById('btn-next-page-fully-paid');
    if (fullyPaidPrev) {
        fullyPaidPrev.addEventListener('click', () => {
            if (currentPage > 1) fetchBorrowersPage(currentPage - 1);
        });
    }
    if (fullyPaidNext) {
        fullyPaidNext.addEventListener('click', () => {
            fetchBorrowersPage(currentPage + 1);
        });
    }
}

function updatePaginationUI(totalFilteredItems, totalPages, newCurrentPage) {
    currentPage = newCurrentPage;
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, totalFilteredItems);

    const isFullyPaidView = currentStatusFilter === 'FULLY PAID';
    const startEl = document.getElementById(isFullyPaidView ? 'fully-paid-page-start' : 'page-start');
    const endEl = document.getElementById(isFullyPaidView ? 'fully-paid-page-end' : 'page-end');
    const totalEl = document.getElementById(isFullyPaidView ? 'fully-paid-page-total' : 'page-total');
    const infoEl = document.getElementById(isFullyPaidView ? 'fully-paid-page-info' : 'page-info');
    const prevBtn = document.getElementById(isFullyPaidView ? 'btn-prev-page-fully-paid' : 'btn-prev-page');
    const nextBtn = document.getElementById(isFullyPaidView ? 'btn-next-page-fully-paid' : 'btn-next-page');

    if (startEl) startEl.innerText = totalFilteredItems === 0 ? 0 : startIndex + 1;
    if (endEl) endEl.innerText = endIndex;
    if (totalEl) totalEl.innerText = totalFilteredItems;
    if (infoEl) infoEl.innerText = `Page ${currentPage} of ${totalPages || 1}`;

    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
}

window.switchTab = function(tab) {
    const activeTabBtn  = document.getElementById('tab-active');
    const fullyPaidTabBtn = document.getElementById('tab-fully-paid');
    const pendingTabBtn = document.getElementById('tab-pending');
    const inactiveTabBtn = document.getElementById('tab-inactive');
    const voidTabBtn = document.getElementById('tab-void');
    const activeTable   = document.getElementById('table-active');
    const fullyPaidTable = document.getElementById('table-fully-paid');
    const pendingTable  = document.getElementById('table-pending');
    const inactiveTable = document.getElementById('table-inactive');
    const voidTable = document.getElementById('table-void');

    if (!activeTabBtn || !pendingTabBtn || !activeTable || !pendingTable) return;

    if (tab === 'active') {
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        if (fullyPaidTabBtn) fullyPaidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (inactiveTabBtn) inactiveTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (voidTabBtn) voidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('hidden', 'flex');
        if (fullyPaidTable) fullyPaidTable.classList.replace('flex', 'hidden');
        pendingTable.classList.replace('block', 'hidden');
        if (inactiveTable) inactiveTable.classList.replace('flex', 'hidden');
        if (voidTable) voidTable.classList.replace('flex', 'hidden');
        document.querySelectorAll('.inactive-col').forEach(el => el.classList.add('hidden'));
        currentStatusFilter = 'ONGOING';
        const statusText = document.getElementById('selectedStatusText');
        if (statusText) statusText.textContent = 'Ongoing';

        fetchBorrowersPage(1);
    } else if (tab === 'fully-paid') {
        if (fullyPaidTabBtn) fullyPaidTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (inactiveTabBtn) inactiveTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (voidTabBtn) voidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (activeTable) activeTable.classList.replace('flex', 'hidden');
        if (fullyPaidTable) fullyPaidTable.classList.replace('hidden', 'flex');
        if (pendingTable) pendingTable.classList.replace('block', 'hidden');
        if (inactiveTable) inactiveTable.classList.replace('flex', 'hidden');
        if (voidTable) voidTable.classList.replace('flex', 'hidden');
        currentStatusFilter = 'FULLY PAID';
        const statusText = document.getElementById('selectedStatusText');
        if (statusText) statusText.textContent = 'Fully Paid';
        fetchBorrowersPage(1);
    } else if (tab === 'pending') {
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (fullyPaidTabBtn) fullyPaidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (inactiveTabBtn) inactiveTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (voidTabBtn) voidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTable.classList.replace('flex', 'hidden');
        if (fullyPaidTable) fullyPaidTable.classList.replace('flex', 'hidden');
        pendingTable.classList.replace('hidden', 'block');
        if (inactiveTable) inactiveTable.classList.replace('flex', 'hidden');
        if (voidTable) voidTable.classList.replace('flex', 'hidden');
        document.querySelectorAll('.inactive-col').forEach(el => el.classList.add('hidden'));
    } else if (tab === 'inactive') {
        if (inactiveTabBtn) inactiveTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        if (voidTabBtn) voidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (fullyPaidTabBtn) fullyPaidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (activeTable) activeTable.classList.replace('flex', 'hidden');
        if (fullyPaidTable) fullyPaidTable.classList.replace('flex', 'hidden');
        if (pendingTable) pendingTable.classList.replace('block', 'hidden');
        if (inactiveTable) inactiveTable.classList.replace('hidden', 'flex');
        if (voidTable) voidTable.classList.replace('flex', 'hidden');
        document.querySelectorAll('.inactive-col').forEach(el => el.classList.add('hidden'));
        currentVoidedView = 'inactive';
        currentStatusFilter = 'VOIDED';
        const statusText = document.getElementById('selectedStatusText');
        if (statusText) statusText.textContent = 'Inactive';
        fetchBorrowersPage(1);
    } else if (tab === 'void') {
        if (voidTabBtn) voidTabBtn.className = "px-6 py-3 border-b-2 border-[#e11d48] text-[#e11d48] font-bold text-[13px] tracking-wide transition-colors";
        if (inactiveTabBtn) inactiveTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (fullyPaidTabBtn) fullyPaidTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        activeTabBtn.className  = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        pendingTabBtn.className = "px-6 py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-bold text-[13px] tracking-wide transition-colors";
        if (activeTable) activeTable.classList.replace('flex', 'hidden');
        if (fullyPaidTable) fullyPaidTable.classList.replace('flex', 'hidden');
        if (pendingTable) pendingTable.classList.replace('block', 'hidden');
        if (inactiveTable) inactiveTable.classList.replace('flex', 'hidden');
        if (voidTable) voidTable.classList.replace('hidden', 'flex');
        document.querySelectorAll('.inactive-col').forEach(el => el.classList.add('hidden'));
        currentVoidedView = 'void';
        currentStatusFilter = 'VOIDED';
        const statusText = document.getElementById('selectedStatusText');
        if (statusText) statusText.textContent = 'Voided';
        fetchBorrowersPage(1);
    }
};