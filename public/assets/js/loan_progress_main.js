// ==========================================
// LOAN PROGRESS MAIN: UI, Filters & Table Logic
// ==========================================

let currentLoanProgressStatus = 'ALL';
const LOAN_PROGRESS_COLUMNS = '70px 110px 220px 95px 95px 120px 120px 120px 70px';
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
            currentLoanProgressStatus = ['ALL', 'ONGOING', 'FULLY PAID', 'INACTIVE'].includes(next) ? next : 'ALL';
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
    list.innerHTML = '<tr><td colspan="9" class="px-3 py-6 text-sm font-medium text-slate-400 italic text-center">Loading...</td></tr>';

    const hasPartialDate =
        (currentLoanProgressFromDate && !currentLoanProgressToDate)
        || (!currentLoanProgressFromDate && currentLoanProgressToDate);

    if (hasPartialDate) {
        list.innerHTML = '<tr><td colspan="9" class="px-3 py-6 text-sm font-medium text-slate-400 italic text-center">Please select both From and To dates.</td></tr>';
        return;
    }

    try {
        const fromParam = currentLoanProgressFromDate ? `&from=${encodeURIComponent(currentLoanProgressFromDate)}` : '';
        const toParam = currentLoanProgressToDate ? `&to=${encodeURIComponent(currentLoanProgressToDate)}` : '';
        const url = `${BASE_URL}/public/api/get_loan_progress.php?status=${encodeURIComponent(status)}&limit=0${fromParam}${toParam}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
            const label = status === 'ONGOING'
                ? 'ongoing'
                : (status === 'FULLY PAID'
                    ? 'fully paid'
                    : (status === 'INACTIVE' ? 'inactive' : 'matching'));
            const dateLabel = (currentLoanProgressFromDate && currentLoanProgressToDate)
                ? ` within ${formatDate(currentLoanProgressFromDate)} to ${formatDate(currentLoanProgressToDate)}`
                : '';
            list.innerHTML = `<tr><td colspan="9" class="px-3 py-6 text-sm font-medium text-slate-400 italic text-center">No ${label} loans found${dateLabel}.</td></tr>`;
            return;
        }

        renderLoanProgressRows(result.data, list);
    } catch (error) {
        console.error('Loan Progress Report Load Error:', error);
        list.innerHTML = '<tr><td colspan="9" class="px-3 py-6 text-sm font-medium text-red-400 italic text-center">Failed to load progress data.</td></tr>';
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
            const groupTr = document.createElement('tr');
            groupTr.className = 'bg-slate-100';
            groupTr.innerHTML = `<td colspan="9" class="pl-3 text-[13px] font-extrabold uppercase tracking-wide text-slate-500">${escapeHtml(currentGroupKey)}</td>`;
            list.appendChild(groupTr);
            previousGroupKey = currentGroupKey;
        }

        let pctClass;
        if (r.pct_done >= 75) { pctClass = 'text-[#ce1126] font-extrabold'; } 
        else if (r.pct_done >= 50) { pctClass = 'text-[#e85568] font-extrabold'; } 
        else if (r.pct_done >= 25) { pctClass = 'text-slate-600 font-bold'; } 
        else { pctClass = 'text-slate-500 font-bold'; }

        const pctDone = Number(r.pct_done) || 0;

        const statusMeta = (function () {
            const s = String(r.status || '').toUpperCase();
            if (s === 'FULLY PAID') {
                return { label: 'FULLY PAID', cls: 'text-emerald-700' };
            }
            if (s === 'INACTIVE') {
                return { label: 'INACTIVE', cls: 'text-slate-600' };
            }
            return { label: 'ONGOING', cls: 'text-blue-700' };
        })();

        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-50';
        tr.innerHTML = `
            <td class="px-3 py-1 text-[13px] font-bold uppercase ${statusMeta.cls}">${escapeHtml(statusMeta.label)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold">${escapeHtml(r.employe_id || '--')}</td>
            <td class="px-3 py-1 text-[13px] uppercase font-bold truncate" title="${escapeHtml(r.borrower_name)}">${escapeHtml(r.borrower_name)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-center whitespace-nowrap">${formatDate(r.maturity_date)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-center whitespace-nowrap">${formatDate(r.last_paid_due_date)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-right whitespace-nowrap">${formatCurrency(r.gross_total)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-right whitespace-nowrap">${formatCurrency(r.payment_total)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-right whitespace-nowrap">${formatCurrency(r.balance_total)}</td>
            <td class="px-3 py-1 text-[13px] font-semibold text-center ${pctClass}">${pctDone}%</td>
        `;
        list.appendChild(tr);
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
    if (!raw || raw === '0000-00-00') return 'No Payment Yet';
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