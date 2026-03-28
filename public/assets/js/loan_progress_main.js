// ==========================================
// LOAN PROGRESS MAIN: UI, Filters & Table Logic
// ==========================================

let currentLoanProgressStatus = 'ALL';
const LOAN_PROGRESS_COLUMNS = 'repeat(8, minmax(0, 1fr))';
let currentLoanProgressRows = [];
let currentLoanProgressFromDate = '';
let currentLoanProgressToDate = '';

document.addEventListener('DOMContentLoaded', function () {
    bindLoanProgressFilters();
    loadLoanProgressReport(currentLoanProgressStatus);
});

function bindLoanProgressFilters() {
    const fromDateInput = document.getElementById('loanProgressFromDate');
    const toDateInput = document.getElementById('loanProgressToDate');

    if (fromDateInput && toDateInput) {
        const applyDateFilter = () => {
            currentLoanProgressFromDate = String(fromDateInput.value || '').trim();
            currentLoanProgressToDate = String(toDateInput.value || '').trim();
            loadLoanProgressReport(currentLoanProgressStatus);
        };

        fromDateInput.addEventListener('change', applyDateFilter);
        toDateInput.addEventListener('change', applyDateFilter);
    }

    const buttons = document.querySelectorAll('.lp-status-btn');
    buttons.forEach((btn) => {
        btn.addEventListener('click', function () {
            const next = String(this.dataset.status || 'ALL').toUpperCase();
            currentLoanProgressStatus = ['ALL', 'ONGOING', 'FULLY PAID'].includes(next) ? next : 'ALL';
            setLoanProgressActiveFilter(currentLoanProgressStatus);
            loadLoanProgressReport(currentLoanProgressStatus);
        });
    });

    setLoanProgressActiveFilter(currentLoanProgressStatus);
}

function setLoanProgressActiveFilter(status) {
    const buttons = document.querySelectorAll('.lp-status-btn');
    buttons.forEach((btn) => {
        const isActive = String(btn.dataset.status || '').toUpperCase() === status;
        if (isActive) {
            btn.classList.add('bg-[#ce1126]', 'text-white', 'shadow-sm');
            btn.classList.remove('text-slate-600');
        } else {
            btn.classList.remove('bg-[#ce1126]', 'text-white', 'shadow-sm');
            btn.classList.add('text-slate-600');
        }
    });
}

async function loadLoanProgressReport(status) {
    const list = document.getElementById('loanProgressList');
    if (!list) return;

    currentLoanProgressRows = [];
    list.innerHTML = '<p class="text-sm font-medium text-slate-400 italic py-6 text-center">Loading...</p>';

    const hasPartialDate =
        (currentLoanProgressFromDate && !currentLoanProgressToDate)
        || (!currentLoanProgressFromDate && currentLoanProgressToDate);

    if (hasPartialDate) {
        list.innerHTML = '<p class="text-sm font-medium text-slate-400 italic py-6 text-center">Please select both From and To dates.</p>';
        return;
    }

    try {
        const fromParam = currentLoanProgressFromDate ? `&from=${encodeURIComponent(currentLoanProgressFromDate)}` : '';
        const toParam = currentLoanProgressToDate ? `&to=${encodeURIComponent(currentLoanProgressToDate)}` : '';
        const url = `${BASE_URL}/public/api/get_loan_progress.php?status=${encodeURIComponent(status)}&limit=0${fromParam}${toParam}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
            const label = status === 'ONGOING' ? 'ongoing' : (status === 'FULLY PAID' ? 'fully paid' : 'matching');
            const dateLabel = (currentLoanProgressFromDate && currentLoanProgressToDate)
                ? ` within ${formatDate(currentLoanProgressFromDate)} to ${formatDate(currentLoanProgressToDate)}`
                : '';
            list.innerHTML = `<p class="text-sm font-medium text-slate-400 italic py-6 text-center">No ${label} loans found${dateLabel}.</p>`;
            return;
        }

        renderLoanProgressRows(result.data, list);
    } catch (error) {
        console.error('Loan Progress Report Load Error:', error);
        list.innerHTML = '<p class="text-sm font-medium text-red-400 italic py-6 text-center">Failed to load progress data.</p>';
    }
}

function renderLoanProgressRows(rows, list) {
    const normalizedRows = Array.isArray(rows) ? [...rows] : [];
    normalizedRows.sort((a, b) => getLastPaidDateSortValue(b.last_paid_due_date) - getLastPaidDateSortValue(a.last_paid_due_date));
    currentLoanProgressRows = normalizedRows;
    list.innerHTML = '';

    let previousGroupKey = '';

    normalizedRows.forEach((r) => {
        const currentGroupKey = getMonthYearGroupKey(r.last_paid_due_date);
        if (currentGroupKey !== previousGroupKey) {
            const groupRow = document.createElement('div');
            groupRow.className = 'grid items-center py-1.5 border-b border-slate-100 bg-slate-50';
            groupRow.style.gridTemplateColumns = LOAN_PROGRESS_COLUMNS;
            groupRow.innerHTML = `<span class="col-span-8 pl-2 text-[11px] font-extrabold uppercase tracking-wide text-slate-500">${escapeHtml(currentGroupKey)}</span>`;
            list.appendChild(groupRow);
            previousGroupKey = currentGroupKey;
        }

        let barColor, pctClass;
        if (r.pct_done >= 75) { barColor = '#ce1126'; pctClass = 'text-[#ce1126] font-extrabold'; } 
        else if (r.pct_done >= 50) { barColor = '#e85568'; pctClass = 'text-[#e85568] font-extrabold'; } 
        else if (r.pct_done >= 25) { barColor = '#94a3b8'; pctClass = 'text-slate-600 font-bold'; } 
        else { barColor = '#cbd5e1'; pctClass = 'text-slate-500 font-bold'; }

        const pctDone = Number(r.pct_done) || 0;
        const item = document.createElement('div');
        item.className = 'grid items-center gap-1 py-2 border-b border-slate-50 last:border-0';
        item.style.gridTemplateColumns = LOAN_PROGRESS_COLUMNS;

        item.innerHTML = `
            <span class="text-[13px] font-semibold text-slate-700 tabular-nums pl-2">${escapeHtml(r.employe_id || '--')}</span>
            <span class="text-[13px] font-bold text-slate-800 truncate" title="${escapeHtml(r.borrower_name)}">${escapeHtml(r.borrower_name)}</span>
            <span class="text-[12px] text-slate-600 text-center">${formatDate(r.maturity_date)}</span>
            <span class="text-[12px] text-slate-600 text-center">${formatDate(r.last_paid_due_date)}</span>
            <span class="text-[12px] font-semibold text-slate-700 text-right tabular-nums">${formatCurrency(r.gross_total)}</span>
            <span class="text-[12px] font-semibold text-emerald-700 text-right tabular-nums">${formatCurrency(r.payment_total)}</span>
            <span class="text-[12px] font-semibold text-rose-600 text-right tabular-nums">${formatCurrency(r.balance_total)}</span>
            <span class="text-[12px] tabular-nums text-center pr-0 ${pctClass}">${pctDone}%</span>
        `;
        list.appendChild(item);
    });
}

// --- Shared Helper Functions (Used by Main & Export) ---
function getLastPaidDateSortValue(raw) {
    if (!raw || raw === '0000-00-00') return 0;
    const parsed = new Date(String(raw) + 'T00:00:00');
    if (isNaN(parsed.getTime())) return 0;
    return parsed.getTime();
}

function getMonthYearGroupKey(raw) {
    if (!raw || raw === '0000-00-00') return 'No Last Paid Date';
    const parsed = new Date(String(raw) + 'T00:00:00');
    if (isNaN(parsed.getTime())) return 'No Last Paid Date';
    return parsed.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function formatDate(raw) {
    if (!raw || raw === '0000-00-00') return '--';
    const d = new Date(String(raw) + 'T00:00:00');
    if (isNaN(d.getTime())) return '--';
    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function formatCurrency(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) return '₱ 0.00';
    return '₱ ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#39;');
}