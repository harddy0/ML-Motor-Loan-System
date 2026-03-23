// ==========================================
// DATE FORMATTERS
// ==========================================
function formatFullDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const [month, day, year] = dateStr.split('/').map(Number);
    const d = new Date(year, month - 1, day);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatFullDateTime(dateTimeStr) {
    if (!dateTimeStr || dateTimeStr === '--') return '--';
    const [datePart, timePart, meridiem] = dateTimeStr.split(' ');
    const [month, day, year] = datePart.split('/').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);
    const d = new Date(year, month - 1, day, hours, minutes);
    const dateFormatted = d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const timeFormatted = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    return `${dateFormatted} ${timeFormatted}`;
}

// ==========================================
// STATE
// ==========================================
let currentPage = 1;
const ROWS_PER_PAGE = 100;
let searchTimeout = null;

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
    if (from && to) return true;
    return false;
}

function renderEmptyState() {
    const tbody = document.querySelector('#deductionTableBody');
    const from  = document.getElementById('fromDate')?.value ?? '';
    const to    = document.getElementById('toDate')?.value ?? '';
    const partialDate = (from && !to) || (!from && to);

    if (partialDate) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-14 text-center">
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
            <td colspan="7" class="px-4 py-14 text-center">
                <div class="flex flex-col items-center gap-2 text-slate-400">
                    <svg class="w-8 h-8 mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-[13px] font-semibold text-slate-500">No records to display yet.</p>
                    <p class="text-[12px] text-slate-400">Use the search bar or a complete date range to load records.</p>
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

// ==========================================
// INIT
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    initializeFilters();
    setupExportDropdown();
    renderEmptyState();
    resetPaginationUI();
});

function setupExportDropdown() {
    const exportBtn = document.getElementById('exportMenuBtn');
    const exportMenu = document.getElementById('exportMenu');
    const excelBtn = document.getElementById('exportDeductionBtn');
    const printBtn = document.getElementById('printDeductionBtn');

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

    if (excelBtn) {
        excelBtn.addEventListener('click', () => {
            window.exportDeductionsExcel();
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.printDeductionsList();
        });
    }
}

// ==========================================
// SERVER-SIDE FETCH
// ==========================================
function fetchDeductionsPage(page) {
    const search   = document.getElementById('searchInput')?.value.trim() ?? '';
    const fromDate = document.getElementById('fromDate')?.value ?? '';
    const toDate   = document.getElementById('toDate')?.value ?? '';

    // Block fetch if no valid filter is active
    if (!hasActiveFilter()) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    // Block fetch if only one date is provided
    const partialDate = (fromDate && !toDate) || (!fromDate && toDate);
    if (partialDate) {
        renderEmptyState();
        resetPaginationUI();
        return;
    }

    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500">Loading records...</td></tr>';

    const params = new URLSearchParams({
        page,
        limit: ROWS_PER_PAGE,
        search,
        from: fromDate,
        to: toDate,
    });

    fetch(`../../../public/api/get_paginated_deductions.php?${params.toString()}`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                const {
                    data,
                    total_overall,
                    total_filtered,
                    total_pages,
                    current_page,
                    total_amount_overall,
                    total_amount_filtered,
                } = result.payload;

                const isFiltered = search !== '' || fromDate !== '' || toDate !== '';

                // Total Records card — always unfiltered
                const totalCountEl = document.getElementById('total-count');
                if (totalCountEl) totalCountEl.innerText = total_overall.toLocaleString();

                // Total Deductions card — filtered when active, overall at rest
                const totalAmountEl = document.getElementById('total-amount');
                if (totalAmountEl) {
                    const amount = isFiltered ? total_amount_filtered : total_amount_overall;
                    totalAmountEl.innerText = '₱' + amount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                }

                // Filtered label — shown only when a filter is active
                const filteredLabelEl = document.getElementById('total-amount-label');
                if (filteredLabelEl) {
                    filteredLabelEl.classList.toggle('hidden', !isFiltered);
                }

                renderTable(data);
                updatePaginationUI(total_filtered, total_pages, current_page);
            } else {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500 font-bold">Error: ${result.error}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-500 font-bold">Fatal error loading data.</td></tr>';
        });
}

// ==========================================
// RENDER TABLE
// ==========================================
function renderTable(data) {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '';

    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const amountFormatted = parseFloat(row.amount).toLocaleString('en-US', { minimumFractionDigits: 2 });

        let matchColor = 'text-[#ff3b30]';
        if (row.match_status === 'MATCHED') {
            matchColor = 'text-green-500';
        } else if (row.match_status === 'VOIDED') {
            matchColor = 'text-orange-500';
        }

        const tr = document.createElement('tr');
        tr.className = "deduction-row group hover:bg-slate-200 transition-colors cursor-pointer border-b border-slate-100";

        tr.innerHTML = `
            <td class="px-3 py-0 text-[13px] font-mono font-bold text-slate-800 text-center border-r border-slate-100 whitespace-nowrap">
                ${row.pn_number || '—'}
            </td>
            <td class="px-5 py-0 text-[14px] text-slate-500 text-center border-r border-slate-100">
                ${row.id}
            </td>
            <td class="px-1 py-0 text-[14px] text-slate-600 text-center border-r border-slate-100">
                ${formatFullDate(row.p_date)}
            </td>
            <td class="px-5 py-0 border-r border-slate-100">
                <span class="text-[14px] font-black text-slate-800 block">${row.first} ${row.last}</span>
            </td>
            <td class="px-5 py-0 text-[14px] text-slate-800 text-right border-r border-slate-100">
                ${amountFormatted}
            </td>
            <td class="px-1 py-0 text-[5px] text-slate-500 text-left border-r border-slate-100">
                ${row.region}
            </td>
            <td class="px-1 py-0 text-[5px] text-slate-400 text-center">
                ${formatFullDateTime(row.i_date)}
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

// ==========================================
// PAGINATION UI
// ==========================================
function updatePaginationUI(totalFiltered, totalPages, newCurrentPage) {
    currentPage = newCurrentPage;

    const startIndex = (currentPage - 1) * ROWS_PER_PAGE;
    const endIndex   = Math.min(startIndex + ROWS_PER_PAGE, totalFiltered);

    document.getElementById('page-start').innerText = totalFiltered === 0 ? 0 : startIndex + 1;
    document.getElementById('page-end').innerText   = endIndex;
    document.getElementById('page-total').innerText = totalFiltered;
    document.getElementById('page-info').innerText  = `Page ${currentPage} of ${totalPages || 1}`;

    document.getElementById('btn-prev-page').disabled = currentPage <= 1;
    document.getElementById('btn-next-page').disabled = currentPage >= totalPages;
}

// ==========================================
// FILTERS & SEARCH
// ==========================================
function initializeFilters() {
    const searchInput  = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchInput');
    const fromDate     = document.getElementById('fromDate');
    const toDate       = document.getElementById('toDate');

    const toggleClearSearchBtn = () => {
        if (!searchInput || !clearSearchBtn) return;
        clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
    };

    // Search — debounced
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            toggleClearSearchBtn();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchDeductionsPage(1), 500);
        });
        toggleClearSearchBtn();
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput.value.length === 0) return;
            searchInput.value = '';
            toggleClearSearchBtn();
            clearTimeout(searchTimeout);
            fetchDeductionsPage(1);
            searchInput.focus();
        });
    }

    if (fromDate) fromDate.addEventListener('change', () => fetchDeductionsPage(1));
    if (toDate)   toDate.addEventListener('change',   () => fetchDeductionsPage(1));

    // Pagination buttons
    document.getElementById('btn-prev-page')?.addEventListener('click', () => {
        if (currentPage > 1) fetchDeductionsPage(currentPage - 1);
    });

    document.getElementById('btn-next-page')?.addEventListener('click', () => {
        fetchDeductionsPage(currentPage + 1);
    });
}

window.exportDeductionsExcel = function () {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const search = document.getElementById('searchInput')?.value.trim() ?? '';
    const from = document.getElementById('fromDate')?.value ?? '';
    const to = document.getElementById('toDate')?.value ?? '';

    const queryParams = new URLSearchParams({ search, from, to });
    window.location.href = `../../../public/api/export_deductions.php?${queryParams.toString()}`;
};

window.printDeductionsList = async function () {
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) exportMenu.classList.add('hidden');

    const data = await fetchAllDeductionsForExport();
    if (!data.length) return;

    const reportInfo = getDeductionReportInfo();
    const exportHeaderHtml = buildDeductionExportHeaderHtml();
    const printWindow = window.open('', '_blank', 'width=1200,height=800');
    if (!printWindow) return;

    const esc = (v) => String(v ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const tableRowsHtml = data.map((row) => {
        const fullName = `${row.first || ''} ${row.last || ''}`.trim();
        const amount = parseFloat(row.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return `
            <tr>
                <td>${esc(row.pn_number || '---')}</td>
                <td>${esc(row.id || '')}</td>
                <td>${esc(formatFullDate(row.p_date || '--'))}</td>
                <td>${esc(fullName)}</td>
                <td>${esc(amount)}</td>
                <td>${esc(row.region || '')}</td>
                <td>${esc(formatFullDateTime(row.i_date || '--'))}</td>
            </tr>
        `;
    }).join('');

    const totalDeduction = data.reduce((sum, row) => sum + (parseFloat(row.amount || 0) || 0), 0);
    const totalDeductionFormatted = totalDeduction.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <style>
                @page { size: landscape; margin: 10mm; }
                body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; }
                * {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
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
                .sys-header-right { min-width: 56px; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td {
                    border: 1px solid #cbd5e1;
                    padding: 6px 7px;
                    font-size: 11px;
                    word-break: break-word;
                    text-align: left;
                }
                th {
                    background: #ce2216 !important;
                    color: #ffffff !important;
                    font-weight: 700;
                }
                th:nth-child(5),
                td:nth-child(5) {
                    text-align: right;
                }
                tr:nth-child(even) td { background: #f8fafc; }
                .total-row td {
                    background: #f8fafc;
                    font-weight: 700;
                }
                .total-label {
                    text-align: right;
                    color: #0f172a;
                }
                .total-amount {
                    text-align: right;
                    color: #ce2216;
                }
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
                <thead>
                    <tr>
                        <th>System Loan No.</th>
                        <th>Employee ID</th>
                        <th>Due Date</th>
                        <th>Full Name</th>
                        <th>Deduction</th>
                        <th>Region</th>
                        <th>Date Imported</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRowsHtml}
                    <tr class="total-row">
                        <td colspan="4" class="total-label">TOTAL COLLECTION:</td>
                        <td class="total-amount">${esc(totalDeductionFormatted)}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <div class="report-footer">Generated by: ${esc(reportInfo.generatedBy)} | Generated: ${esc(reportInfo.renderedAt)}</div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
};

async function fetchAllDeductionsForExport() {
    const search = document.getElementById('searchInput')?.value.trim() ?? '';
    const from = document.getElementById('fromDate')?.value ?? '';
    const to = document.getElementById('toDate')?.value ?? '';

    const params = new URLSearchParams({
        page: '1',
        limit: '50000',
        search,
        from,
        to
    });

    try {
        const response = await fetch(`../../../public/api/get_paginated_deductions.php?${params.toString()}`);
        const result = await response.json();
        if (result && result.success && result.payload && Array.isArray(result.payload.data)) {
            return result.payload.data;
        }
    } catch (error) {
        console.error('Failed to load data for print export:', error);
    }

    return [];
}

function getDeductionReportInfo() {
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

function buildDeductionExportHeaderHtml() {
    const headerData = getDeductionExportHeaderData();
    const leftLogo = headerData.leftLogoSrc ? `<img src="${headerData.leftLogoSrc}" alt="ML Diamond" />` : '';
    const centerLogo = headerData.centerLogoSrc ? `<img src="${headerData.centerLogoSrc}" alt="M Lhuillier Logo" />` : '';

    return `
        <div class="sys-header-row">
            <div class="sys-header-left">${leftLogo}</div>
            <div class="sys-header-center">
                ${centerLogo}
                <span class="brand-text">${escapeDeductionHtml(headerData.brandText)}</span>
            </div>
            <div class="sys-header-right"></div>
        </div>
    `;
}

function getDeductionExportHeaderData() {
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
        leftLogoSrc: resolveDeductionAssetUrl(leftLogoRaw),
        centerLogoSrc: resolveDeductionAssetUrl(centerLogoRaw),
        brandText
    };
}

function resolveDeductionAssetUrl(rawSrc) {
    const src = String(rawSrc || '').trim();
    if (!src) return '';
    if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:')) return src;
    if (src.startsWith('/')) return `${window.location.origin}${src}`;

    const normalizedBase = String(BASE_URL || '').replace(/\/+$/, '');
    if (normalizedBase && (src === normalizedBase || src.startsWith(`${normalizedBase}/`))) {
        return `${window.location.origin}/${src.replace(/^\/+/, '')}`;
    }

    const normalizedSrc = src.replace(/^\/+/, '');
    const basePath = normalizedBase ? `${normalizedBase}/` : '/';
    return `${window.location.origin}${basePath}${normalizedSrc}`;
}

function escapeDeductionHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}