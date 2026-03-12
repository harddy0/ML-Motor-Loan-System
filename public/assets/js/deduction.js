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
// INIT
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    initializeFilters();
    fetchDeductionsPage(1);
});

// ==========================================
// SERVER-SIDE FETCH
// ==========================================
function fetchDeductionsPage(page) {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500">Loading records...</td></tr>';

    const search   = document.getElementById('searchInput')?.value.trim() ?? '';
    const fromDate = document.getElementById('fromDate')?.value ?? '';
    const toDate   = document.getElementById('toDate')?.value ?? '';

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
    const exportBtn    = document.getElementById('exportDeductionBtn');

    const toggleClearSearchBtn = () => {
        if (!searchInput || !clearSearchBtn) return;
        clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
    };

    // Export — always exports ALL records, ignores pagination
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const search = searchInput?.value.trim() ?? '';
            const from   = fromDate?.value ?? '';
            const to     = toDate?.value ?? '';

            const queryParams = new URLSearchParams({ search, from, to });
            window.location.href = `../../../public/api/export_deductions.php?${queryParams.toString()}`;
        });
    }

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