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
}

document.addEventListener('DOMContentLoaded', function() {
    initializeFiltersAndPagination();
    setupExportDropdown();
    renderEmptyState();
    resetPaginationUI();
    
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

function setupExportDropdown() {
    const exportBtn = document.getElementById('exportMenuBtn');
    const exportMenu = document.getElementById('exportMenu');
    if (!exportBtn || !exportMenu) return;

    exportBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        exportMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!exportBtn.contains(e.target) && !exportMenu.contains(e.target)) {
            exportMenu.classList.add('hidden');
        }
    });
}

window.exportBorrowersExcel = function() {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const exportData = getCurrentTabExportData();
    if (!exportData.rows.length) {
        return;
    }

    const reportInfo = getReportInfo();
    const title = exportData.tab === 'pending' ? 'Upload KPTN Form' : 'All Loans';

    fetch(`${BASE_URL}/public/api/export_borrowers_excel.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tab: exportData.tab,
            title,
            headers: exportData.headers,
            rows: exportData.rows,
            generatedBy: reportInfo.generatedBy,
            renderedAt: reportInfo.renderedAt
        })
    })
    .then(async (response) => {
        if (!response.ok) {
            let errorText = 'Failed to export Excel file.';
            try {
                const data = await response.json();
                if (data && data.error) errorText = data.error;
            } catch (_) {
                // Ignore JSON parse failure and keep default error.
            }
            throw new Error(errorText);
        }
        return response.blob();
    })
    .then((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `borrowers_${exportData.tab}_report_${new Date().toISOString().slice(0, 10)}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    })
    .catch((error) => {
        alert(error.message || 'Failed to export Excel file.');
    });
};

window.printBorrowersList = function() {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const exportData = getCurrentTabExportData();
    if (!exportData.rows.length) return;

    const title = exportData.tab === 'pending' ? 'Upload KPTN Form' : 'All Loans';
    const printWindow = window.open('', 's', 'width=1200,height=800');
    if (!printWindow) return;

    const reportInfo = getReportInfo();
    const exportHeaderHtml = buildExportHeaderHtml();

    const tableHeaderHtml = `<tr>${exportData.headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
    const tableRowsHtml = exportData.rows.map(row => `<tr>${row.map(c => `<td>${String(c ?? '')}</td>`).join('')}</tr>`).join('');

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <style>
                @page { size: landscape; margin: 10mm; }
                body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; }
                .sys-header {
                    border-bottom: 1px solid #cbd5e1;
                    margin-bottom: 10px;
                    padding: 8px 0 10px;
                }
                .sys-header-row {
                    min-height: 48px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                }
                .sys-header-left {
                    display: flex;
                    align-items: center;
                    min-width: 56px;
                }
                .sys-header-left img { height: 30px; width: auto; display: block; }
                .sys-header-center {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    gap: 3px;
                }
                .sys-header-center img { height: 28px; width: auto; display: block; }
                .sys-header-center .brand-text {
                    display: block;
                    color: #64748b;
                    font-size: 12px;
                    letter-spacing: 0.18em;
                    font-weight: 700;
                    text-transform: uppercase;
                }
                .sys-header-right {
                    min-width: 56px;
                }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td {
                    border: 1px solid #cbd5e1;
                    padding: 6px 7px;
                    font-size: 11px;
                    word-break: break-word;
                }
                th {
                    background: #ce1126;
                    color: #fff;
                    text-align: left;
                    font-weight: 700;
                }
                tr:nth-child(even) td { background: #f8fafc; }
                .report-footer {
                    margin-top: 10px;
                    font-size: 11px;
                    color: #475569;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <div class="sys-header">${exportHeaderHtml}</div>

            <table>
                <thead>${tableHeaderHtml}</thead>
                <tbody>${tableRowsHtml}</tbody>
            </table>

            <div class="report-footer">Generated by: ${reportInfo.generatedBy} | Generated: ${reportInfo.renderedAt}</div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
};

function getCurrentTabExportData() {
    const activeTable = document.getElementById('table-active');
    const pendingTable = document.getElementById('table-pending');
    const isActiveTab = !!activeTable && !activeTable.classList.contains('hidden');

    if (isActiveTab) {
        const headers = [
            'System Loan No.',
            'Reference Number',
            'Date Released',
            'Employee ID',
            'Full Name',
            'Region',
            'Status'
        ];

        const rows = (Array.isArray(currentBorrowersData) ? currentBorrowersData : []).map((b) => ([
            b.pn_no || '---',
            b.reference_no || '---',
            formatDate(b.raw_date),
            b.id || '',
            b.name || '',
            b.region || '',
            b.current_status || ''
        ]));

        return { tab: 'active', headers, rows };
    }

    const headers = [
        'System Loan No.',
        'Reference Number',
        'Employee ID',
        'KPTN',
        'Full Name'
    ];

    const rows = [];
    if (pendingTable) {
        const bodyRows = pendingTable.querySelectorAll('tbody tr');
        bodyRows.forEach((tr) => {
            const cells = tr.querySelectorAll('td');
            if (!cells.length || cells.length < 5) return;
            if (cells.length === 1 && cells[0].hasAttribute('colspan')) return;

            rows.push([
                cells[0].innerText.trim(),
                cells[1].innerText.trim(),
                cells[2].innerText.trim(),
                cells[3].innerText.trim(),
                cells[4].innerText.trim()
            ]);
        });
    }

    return { tab: 'pending', headers, rows };
};

function getReportInfo() {
    const renderedAt = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    let generatedBy = 'SYSTEM USER';
    if (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim()) {
        generatedBy = String(CURRENT_USER_FULLNAME).trim().toUpperCase();
    }

    return { renderedAt, generatedBy };
}

// ==========================================
// SERVER-SIDE FETCH LOGIC
// ==========================================
function fetchBorrowersPage(page) {
    const search = document.getElementById('searchInput').value.trim();
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;

    // Block fetch if no valid filter is active
    if (!hasActiveFilter()) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    // Block fetch if only one date is provided
    const partialDate = (from && !to) || (!from && to);
    if (partialDate) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

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
            statusHtml = `<span class="inline-block px-2 py-0.5 text-[12px] font-bold rounded bg-orange-100 text-orange-700 uppercase">Void</span>`;
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
            <td class="px-2 py-0 text-[13px] text-slate-600 border-r border-slate-100 uppercase font-mono truncate text-center">${borrower.reference_no || '---'}</td>
            <td class="px-2 py-0 text-[13px] text-slate-600 border-r border-slate-100 text-center truncate">${formatDate(borrower.raw_date)}</td>
            <td class="px-2 py-0 text-[13px] text-slate-700 border-r border-slate-100 text-center truncate">${borrower.id}</td>
            <td class="px-3 py-0 text-[13px] text-slate-800 border-r border-slate-100 uppercase font-semibold truncate">${borrower.name}</td>
            <td class="px-2 py-0 text-[12px] text-slate-800 border-r border-slate-100 font-mono truncate lowercase first-letter:uppercase text-center"><span>${borrower.region}</span></td>
            <td class="px-2 py-0 text-center border-r border-slate-100">${statusHtml}</td>
            <td class="px-2 py-0 text-center">${actionHtml}</td>
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

function openSecurityDepositModalByLoanId(loanId) {
    const selectedBorrower = currentBorrowersData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (!selectedBorrower) return;

    openAttachKptnModal(
        selectedBorrower.loan_id,
        selectedBorrower.name || '',
        selectedBorrower.pending_kptn || '',
        selectedBorrower.deposit_amount || 0
    );
}

function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    
    document.getElementById('m-id').innerText      = data.id || 'N/A';
    document.getElementById('m-fname').innerText   = data.first_name || 'N/A';
    document.getElementById('m-lname').innerText   = data.last_name || 'N/A';
    document.getElementById('m-date').innerText    = formatDate(data.raw_date) || 'N/A';
    document.getElementById('m-contact').innerText = data.contact || 'N/A';
    document.getElementById('m-pn').innerText      = data.pn_no || 'N/A';
    document.getElementById('m-ref-no').innerText   = data.reference_no || data.reference_number || 'N/A';
    document.getElementById('m-pn-mat').innerText  = formatDate(data.pn_maturity) || 'N/A';
    document.getElementById('m-region').innerText  = data.region || 'N/A';
    const requiresKptn = data.requires_kptn == 1 || data.requires_kptn === true;
    const kptnCandidate = String(data.pending_kptn || data.kptn || '').trim();
    const hasKptnCode = requiresKptn && kptnCandidate && !/^NR_/i.test(kptnCandidate);
    document.getElementById('m-kptn-code').innerText = hasKptnCode
        ? ('- ' + kptnCandidate.toUpperCase())
        : '';
    const kptnIndicator = document.getElementById('m-kptn-indicator');
    if (kptnIndicator) {
        kptnIndicator.classList.toggle('bg-[#ce2216]', !!hasKptnCode);
        kptnIndicator.classList.toggle('bg-slate-400', !hasKptnCode);
    }
    
    const loanAmount = parseFloat(data.loan_amount || 0);
    const semiMonthly = parseFloat(data.deduction || 0);
    const monthly = semiMonthly * 2;
    const addOnRateDecimal = parseFloat(data.add_on_rate || 0);
    const termMonths = parseInt(data.terms || 0, 10);
    const totalRatePercent = (addOnRateDecimal * termMonths * 100).toFixed(0);
    const grossPrincipal = loanAmount;
    const grossInterest = loanAmount * addOnRateDecimal * termMonths;
    const grossTotal = grossPrincipal + grossInterest;

    document.getElementById('m-amount').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + loanAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-terms').innerText  = data.terms ? data.terms + ' Months' : 'N/A';
    document.getElementById('m-rate').innerText = termMonths > 0 ? totalRatePercent + '%' : 'N/A';
    document.getElementById('m-deduct').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + semiMonthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-monthly').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + monthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-principal').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossPrincipal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-interest').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossInterest.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-total').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossTotal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';

    if (window.kptnSetTitle) window.kptnSetTitle(data.name || '');

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

function openAttachKptnModal(loanId, borrowerName, pendingKptn = '', depositAmount = 0) {
    document.getElementById('ak_loan_id').value = loanId;
    document.getElementById('ak_borrower_name').innerText = borrowerName.toUpperCase();
    document.getElementById('ak_kptn_number').value = pendingKptn || 'KPTN-';
    const depositInput = document.getElementById('ak_deposit_amount');
    if (depositInput) {
        const parsedDeposit = parseFloat(String(depositAmount).replace(/,/g, '')) || 0;
        depositInput.value = parsedDeposit > 0
            ? parsedDeposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '';
    }

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
                    // ── REGION VALIDATION FAILURE ──────────────────────────
                    // When region_errors is present the server has already
                    // structured the per-row messages — render them as a
                    // styled list instead of a raw text dump.
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

/**
 * Shows the region-validation rejection modal with a structured per-row list.
 */
function showRegionErrorModal(regionErrors) {
    const errorModal = document.getElementById('importErrorModal');
    const errorMessage = document.getElementById('importErrorMessage');

    // Start directly with the description text, omitting the duplicate icon and title
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
        // Safely extract the exact data from the PHP string
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
            // Fallback just in case a string format doesn't match the regex perfectly
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

/**
 * Generic import error modal — plain text / simple HTML.
 */
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

    // ── WARNINGS BANNER ───────────────────────────────────────────────────────
    // Render skipped-row warnings (ongoing, bad KPTN, missing fields) above the
    // import list so staff can see what was dropped before confirming the save.
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
    const branchIdInput = document.getElementById('branch_id_input'); // Get hidden input

    if (regionName.startsWith('HO') || regionName.includes('HEAD OFFICE')) {
        divContainer.classList.remove('hidden');
        branchContainer.classList.add('hidden');
        divInput.required = true;
        branchInput.required = false;
        branchInput.value = 'N/A'; 
        branchIdInput.value = 'N/A'; // Default value for Head Office
        divInput.value = '';
    } else {
        divContainer.classList.add('hidden');
        branchContainer.classList.remove('hidden');
        divInput.required = false;
        branchInput.required = true;
        divInput.value = 'N/A'; 
        branchInput.value = '';
        branchIdInput.value = ''; // Clear previous ID
        branchInput.placeholder = 'LOADING BRANCHES...';
        
        fetch(`${BASE_URL}/public/api/get_branches.php?region_code=${regionCode}`)
            .then(res => res.json())
            .then(data => {
                branchInput.placeholder = 'SELECT BRANCH...';
                if (data.success) {
                    // Pass a callback to extract the ID when a branch is selected
                    setupCustomSearchable('branch_search_input', 'branch_results', data.data, function(selectedBranch) {
                        // Support different object structures from the API
                        branchIdInput.value = selectedBranch.value || selectedBranch.branch_id || selectedBranch.id || selectedBranch;
                    });
                }
            });
    }
}

function validateAndShowSchedule() {
    const form = document.getElementById('addBorrowerForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const formData = new FormData(form);
    tempBorrowerData = Object.fromEntries(formData.entries());

    const kptnToggle = document.getElementById('requiresKptnToggle');
    if (kptnToggle) {
        tempBorrowerData['requires_kptn'] = kptnToggle.checked ? 'true' : 'false';
    }

    // Use global compatibility helper to populate fields (falls back to ledger modal ids)
    setSchedField('sched-name', (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase());
    setSchedField('sched-contact', tempBorrowerData.contact_number);
    setSchedField('sched-amount', parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
    setSchedField('sched-date', formatFullDate(tempBorrowerData.loan_granted));
    setSchedField('sched-terms', tempBorrowerData.terms + ' Months');

    setSchedField('sched-pn', "Generating PN...");
    setSchedField('sched-maturity', "Calculating..."); 
    setSchedField('sched-deduct', "Calculating...");
    const amortRowsEl = document.getElementById('amortization-rows') || document.getElementById('modal-ledger-rows');
    if (amortRowsEl) amortRowsEl.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-slate-500 italic">Calculating Schedule...</td></tr>';

    // Populate borrower identifiers into ledger modal fields if present
    const empIdEl = document.getElementById('modal-ledger-id');
    if (empIdEl) empIdEl.innerText = tempBorrowerData.employe_id || tempBorrowerData.employeId || '---';
    const refEl = document.getElementById('modal-ledger-ref');
    if (refEl) refEl.innerText = tempBorrowerData.reference_number || tempBorrowerData.reference_no || tempBorrowerData.reference || '---';
    const regionEl = document.getElementById('modal-ledger-region');
    if (regionEl) regionEl.innerText = tempBorrowerData.region_name || tempBorrowerData.region || (tempBorrowerData.region_code || '').toUpperCase() || '--';
    const branchEl = document.getElementById('modal-ledger-branch');
    if (branchEl) branchEl.innerText = tempBorrowerData.branch_name || tempBorrowerData.branch || 'N/A';

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
            setSchedField('sched-pn', result.pn_number);
            setSchedField('sched-deduct', parseFloat(result.deduction).toLocaleString('en-US', {minimumFractionDigits: 2}));
            setSchedField('sched-rate', result.add_on_rate + ' % (Add-on)');
            setSchedField('sched-initial-bal', parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
            setSchedField('sched-maturity', formatFullDate(result.maturity_date));

            renderAmortizationTable(result.schedule);

            // Compute gross totals and balances for ledger-style modal
            try {
                const rows = result.schedule || [];
                let sumTotalPrincipal = 0;
                let sumTotalInterest = 0;
                let totalPrincipalPaid = 0; // new loan -> none paid yet
                let totalInterestPaid = 0;
                let totalCollected = 0;

                rows.forEach(r => {
                    const p = parseFloat(r.principal) || 0;
                    const i = parseFloat(r.interest) || 0;
                    sumTotalPrincipal += p;
                    sumTotalInterest += i;
                });

                const loanAmount = parseFloat(tempBorrowerData.loan_amount) || 0;
                // Use the decimal rate provided by the API to avoid misinterpretation
                const addOnRateDecimal = parseFloat(result.add_on_rate_decimal) || 0;
                const termMonths = parseInt(tempBorrowerData.terms) || parseInt(tempBorrowerData.term_months) || 0;

                // Display rate without suffix text and match ledger formatting (monthly percent)
                const monthlyRatePercent = Number((addOnRateDecimal * 100).toFixed(2));
                setSchedField('sched-rate', monthlyRatePercent + ' %');

                // Semi-monthly deduction from API
                const semiAmort = parseFloat(result.deduction) || 0;
                const monthlyAmort = semiAmort * 2;

                const setMoney = (id, num) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.innerText = '₱ ' + (isNaN(num) ? '0.00' : num.toLocaleString(undefined, {minimumFractionDigits:2}));
                };

                // Prefer authoritative totals returned by the API when available
                const grossPrincipal = loanAmount;
                const grossInterest = (typeof result.total_interest !== 'undefined' && !isNaN(parseFloat(result.total_interest)))
                    ? parseFloat(result.total_interest)
                    : (loanAmount * addOnRateDecimal * termMonths);
                const grossTotal = (typeof result.gross_amount !== 'undefined' && !isNaN(parseFloat(result.gross_amount)))
                    ? parseFloat(result.gross_amount)
                    : (grossPrincipal + grossInterest);

                setMoney('modal-ledger-gross-principal', grossPrincipal);
                setMoney('modal-ledger-gross-interest', grossInterest);
                setMoney('modal-ledger-gross-total', grossTotal);

                const principalBalance = sumTotalPrincipal - totalPrincipalPaid;
                const interestBalance = ((typeof result.total_interest !== 'undefined' && !isNaN(parseFloat(result.total_interest)))
                    ? parseFloat(result.total_interest)
                    : sumTotalInterest) - totalInterestPaid;
                const totalOutstanding = principalBalance + interestBalance;

                setMoney('modal-ledger-principal-paid', totalPrincipalPaid);
                setMoney('modal-ledger-principal-balance', principalBalance);
                setMoney('modal-ledger-interest-paid', totalInterestPaid);
                setMoney('modal-ledger-interest-balance', interestBalance);
                setMoney('modal-ledger-total-payment', totalPrincipalPaid + totalInterestPaid);
                setMoney('modal-ledger-total-balance', totalOutstanding);

                // Ensure semi-monthly and monthly amortization fields are populated
                setMoney('modal-ledger-amort', semiAmort);
                setMoney('modal-ledger-monthly-amort', monthlyAmort);

                // Security deposit: if user did not apply (requires_kptn false), show zero
                const requiresKptn = tempBorrowerData.requires_kptn === 'true' || tempBorrowerData.requires_kptn === true;
                const depositAmount = requiresKptn ? (parseFloat((tempBorrowerData.deposit_amount || '').toString().replace(/,/g, '')) || 0) : 0;
                setMoney('modal-ledger-security-deposit', depositAmount);
                const depositWrapper = document.getElementById('security-deposit-wrapper');
                if (depositWrapper) depositWrapper.style.display = depositAmount > 0 ? 'flex' : 'none';

            } catch (e) {
                console.error('Error computing ledger totals', e);
            }

            tempBorrowerData.pn_number = result.pn_number;
            tempBorrowerData.pn_maturity = result.maturity_date;
            tempBorrowerData.deduction = result.deduction;
            tempBorrowerData.schedule = result.schedule;
            tempBorrowerData.periodic_rate = result.periodic_rate; 
        } else {
            showImportError("Calculation Error: " + result.error);
            closeModal('amortizationModal');
            openAddModal(); 
        }
    })
    .catch(err => {
        console.error(err);
        showImportError("System Error calling API");
        closeModal('amortizationModal');
        openAddModal();
    });
}

function formatFullDate(dateStr) {
    if (!dateStr) return '';
    const dt = new Date(dateStr);
    if (isNaN(dt)) return dateStr;
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amortization-rows') || document.getElementById('modal-ledger-rows');
    if (!tbody) return;
    tbody.innerHTML = ''; 
    rows.forEach(row => {
        const principalAmt = parseFloat(row.principal) || 0;
        const interestAmt = parseFloat(row.interest) || 0;
        const totalAmt = parseFloat(row.total) || (principalAmt + interestAmt);
        const balAmt = parseFloat(row.balance) || 0;
        const status = (row.status || row.status_code || 'UNPAID').toString().toUpperCase();
        const remarksText = row.remarks || '';

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-100 border-b border-slate-100 transition-colors`;
        tr.innerHTML = `
            <td class="w-[16%] px-8 py-0 text-center text-slate-600 border-r border-slate-50 font-medium font-mono">${formatFullDate(row.date)}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 pr-2">${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 pr-2">${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 font-medium pr-4">${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right border-r border-slate-50 text-slate-600 pr-4">${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[10%] px-3 py-0 text-center text-slate-600">${status}</td>
            <td class="px-3 py-0 text-slate-600 text-left truncate" title="${remarksText}">${remarksText}</td>
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
            showImportError((data.error || "Unknown error occurred").replace(/\n/g, '<br>'));
        }
    })
    .catch(err => {
        console.error(err);
        closeModal('amortizationModal');
        showImportError("System Error: Check console for details.");
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

function buildExportHeaderHtml() {
    const headerData = getExportHeaderData();
    const leftLogo = headerData.leftLogoSrc ? `<img src="${headerData.leftLogoSrc}" alt="ML Diamond" />` : '';
    const centerLogo = headerData.centerLogoSrc ? `<img src="${headerData.centerLogoSrc}" alt="M Lhuillier Logo" />` : '';

    return `
        <div class="sys-header-row">
            <div class="sys-header-left">${leftLogo}</div>
            <div class="sys-header-center">
                ${centerLogo}
                <span class="brand-text">${escapeHtml(headerData.brandText)}</span>
            </div>
            <div class="sys-header-right"></div>
        </div>
    `;
}

function getExportHeaderData() {
    const template = document.getElementById('exportHeaderTemplate');
    if (!template) {
        return {
            leftLogoSrc: `${window.location.origin}${BASE_URL}/public/assets/img/ml-diamond.png`,
            centerLogoSrc: `${window.location.origin}${BASE_URL}/public/assets/img/ml-logo-1.png`,
            brandText: 'ML MOTORCYCLE LOAN'
        };
    }

    const container = document.createElement('div');
    container.innerHTML = template.innerHTML.trim();
    const leftLogoRaw = container.querySelector('[name="logo"] img')?.getAttribute('src') || '';
    const centerLogoRaw = container.querySelector('[name="center"] img')?.getAttribute('src') || '';
    const brandText = container.querySelector('[name="center"] span')?.textContent?.trim() || 'ML MOTORCYCLE LOAN';

    return {
        leftLogoSrc: resolveExportAssetUrl(leftLogoRaw),
        centerLogoSrc: resolveExportAssetUrl(centerLogoRaw),
        brandText
    };
}

function resolveExportAssetUrl(rawSrc) {
    const src = String(rawSrc || '').trim();
    if (!src) return '';
    if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;

    if (src.startsWith('/')) {
        return `${window.location.origin}${src}`;
    }

    const normalizedBase = String(BASE_URL || '').replace(/\/+$/, '');
    if (normalizedBase && (src === normalizedBase || src.startsWith(`${normalizedBase}/`))) {
        return `${window.location.origin}/${src.replace(/^\/+/, '')}`;
    }

    const normalizedSrc = src.replace(/^\/+/, '');
    const basePath = normalizedBase ? `${normalizedBase}/` : '/';
    return `${window.location.origin}${basePath}${normalizedSrc}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function buildExportHeaderPhotoSrc() {
    return `${window.location.origin}${BASE_URL}/public/assets/img/header.png?t=${Date.now()}`;
}

// Helper to set amortization modal fields with ledger-modal fallbacks
function setSchedField(primaryId, value) {
    const el = document.getElementById(primaryId);
    if (el) { el.innerText = value; return; }
    const map = {
        'sched-name': 'modal-ledger-name',
        'sched-contact': 'modal-ledger-contact',
        'sched-amount': 'modal-ledger-principal',
        'sched-date': 'modal-ledger-pndate',
        'sched-terms': 'modal-ledger-terms',
        'sched-pn': 'modal-ledger-pn',
        'sched-maturity': 'modal-ledger-maturity',
        'sched-rate': 'modal-ledger-rate',
        'sched-deduct': 'modal-ledger-amort',
        'sched-initial-bal': 'modal-ledger-principal'
    };
    const alt = map[primaryId];
    if (alt) {
        const altEl = document.getElementById(alt);
        if (!altEl) return;

        // Format currency-like fields with peso sign
        const currencyFields = ['modal-ledger-principal', 'modal-ledger-amort', 'modal-ledger-gross-principal', 'modal-ledger-gross-interest', 'modal-ledger-gross-total', 'modal-ledger-monthly-amort', 'modal-ledger-security-deposit'];
        if (currencyFields.indexOf(alt) !== -1) {
            const cleaned = String(value || '').replace(/[^0-9.-]/g, '');
            const num = parseFloat(cleaned);
            if (!isNaN(num)) {
                altEl.innerText = '₱ ' + num.toLocaleString(undefined, {minimumFractionDigits:2});
            } else {
                altEl.innerText = value;
            }
        } else {
            altEl.innerText = value;
        }
    }
}