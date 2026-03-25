let currentLoanProgressStatus = 'ALL';
const LOAN_PROGRESS_COLUMNS = 'repeat(8, minmax(0, 1fr))';
let currentLoanProgressRows = [];

document.addEventListener('DOMContentLoaded', function () {
    bindLoanProgressFilters();
    bindLoanProgressExportMenu();
    loadLoanProgressReport(currentLoanProgressStatus);
});

function bindLoanProgressExportMenu() {
    const menuWrap = document.getElementById('exportLoanProgressMenuWrap');
    const menuBtn = document.getElementById('exportLoanProgressMenuBtn');
    const menu = document.getElementById('exportLoanProgressMenu');
    const excelBtn = document.getElementById('exportLoanProgressExcelBtn');
    const printBtn = document.getElementById('printLoanProgressBtn');

    if (!menuWrap || !menuBtn || !menu || !excelBtn || !printBtn) return;

    let hoverCloseTimer = null;

    function openMenu() {
        if (hoverCloseTimer) {
            clearTimeout(hoverCloseTimer);
            hoverCloseTimer = null;
        }
        menu.classList.remove('hidden');
    }

    function closeMenuWithDelay() {
        if (hoverCloseTimer) clearTimeout(hoverCloseTimer);
        hoverCloseTimer = setTimeout(() => {
            menu.classList.add('hidden');
        }, 120);
    }

    menuWrap.addEventListener('mouseenter', openMenu);
    menuWrap.addEventListener('mouseleave', closeMenuWithDelay);

    menuBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target) && event.target !== menuBtn && !menuBtn.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });

    excelBtn.addEventListener('click', function () {
        exportLoanProgressToExcel();
        menu.classList.add('hidden');
    });

    printBtn.addEventListener('click', function () {
        printLoanProgress();
        menu.classList.add('hidden');
    });
}

function bindLoanProgressFilters() {
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

    try {
        const url = `${BASE_URL}/public/api/get_loan_progress.php?status=${encodeURIComponent(status)}&limit=0`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
            const label = status === 'ONGOING' ? 'ongoing' : (status === 'FULLY PAID' ? 'fully paid' : 'matching');
            list.innerHTML = `<p class="text-sm font-medium text-slate-400 italic py-6 text-center">No ${label} loans found.</p>`;
            return;
        }

        renderLoanProgressRows(result.data, list);
    } catch (error) {
        console.error('Loan Progress Report Load Error:', error);
        list.innerHTML = '<p class="text-sm font-medium text-red-400 italic py-6 text-center">Failed to load progress data.</p>';
    }
}

function renderLoanProgressRows(rows, list) {
    currentLoanProgressRows = Array.isArray(rows) ? rows : [];
    list.innerHTML = '';

    rows.forEach((r) => {
        let barColor;
        let pctClass;

        if (r.pct_done >= 75) {
            barColor = '#ce1126';
            pctClass = 'text-[#ce1126] font-extrabold';
        } else if (r.pct_done >= 50) {
            barColor = '#e85568';
            pctClass = 'text-[#e85568] font-extrabold';
        } else if (r.pct_done >= 25) {
            barColor = '#94a3b8';
            pctClass = 'text-slate-600 font-bold';
        } else {
            barColor = '#cbd5e1';
            pctClass = 'text-slate-500 font-bold';
        }

        const pctDone = Number(r.pct_done) || 0;

        const item = document.createElement('div');
        item.className = 'grid items-center gap-1 py-2 border-b border-slate-50 last:border-0';
        item.style.gridTemplateColumns = LOAN_PROGRESS_COLUMNS;

        const employeeId = escapeHtml(r.employe_id || '--');
        const maturityDate = formatDate(r.maturity_date);
        const lastPaidDueDate = formatDate(r.last_paid_due_date);
        const grossTotal = formatCurrency(r.gross_total);
        const paymentTotal = formatCurrency(r.payment_total);
        const balanceTotal = formatCurrency(r.balance_total);

        item.innerHTML = `
            <span class="text-[13px] font-semibold text-slate-700 tabular-nums pl-2">${employeeId}</span>
            <span class="text-[13px] font-bold text-slate-800 truncate" title="${escapeHtml(r.borrower_name)}">
                ${escapeHtml(r.borrower_name)}
            </span>
            <span class="text-[12px] text-slate-600 text-center">${maturityDate}</span>
            <span class="text-[12px] text-slate-600 text-center">${lastPaidDueDate}</span>
            <span class="text-[12px] font-semibold text-slate-700 text-right tabular-nums">${grossTotal}</span>
            <span class="text-[12px] font-semibold text-emerald-700 text-right tabular-nums">${paymentTotal}</span>
            <span class="text-[12px] font-semibold text-rose-600 text-right tabular-nums">${balanceTotal}</span>
            <span class="text-[12px] tabular-nums text-center pr-0 ${pctClass}">${pctDone}%</span>
        `;

        list.appendChild(item);
    });
}

function getLoanProgressExportRows() {
    return currentLoanProgressRows.map((r) => ({
        employeeId: String(r.employe_id || '--'),
        fullName: String(r.borrower_name || '--'),
        maturityDate: formatDate(r.maturity_date),
        lastPaidDate: formatDate(r.last_paid_due_date),
        gross: formatCurrency(r.gross_total),
        payment: formatCurrency(r.payment_total),
        balance: formatCurrency(r.balance_total),
        progress: `${Number(r.pct_done) || 0}%`
    }));
}

function exportLoanProgressToExcel() {
    const statusParam = encodeURIComponent(currentLoanProgressStatus);
    const exportUrl = `${BASE_URL}/public/api/export_loan_progress_excel.php?status=${statusParam}`;
    window.location.href = exportUrl;
}

function printLoanProgress() {
    const rows = getLoanProgressExportRows();
    if (!rows.length) {
        alert('No rows to print.');
        return;
    }

    const statusLabel = currentLoanProgressStatus === 'ALL' ? 'All' : currentLoanProgressStatus;
    const printedAt = new Date().toLocaleString('en-US');
    const generatedBy = String(window.CURRENT_USER_FULL_NAME || 'SYSTEM USER').toUpperCase();

    let grossTotal = 0;
    let paymentTotal = 0;
    let balanceTotal = 0;
    let progressTotal = 0;

    rows.forEach((row) => {
        grossTotal += parseCurrencyNumber(row.gross);
        paymentTotal += parseCurrencyNumber(row.payment);
        balanceTotal += parseCurrencyNumber(row.balance);
        progressTotal += parseProgressPercent(row.progress);
    });

    const avgProgress = rows.length > 0 ? (progressTotal / rows.length) : 0;

    const tableRows = rows.map((row) => `
        <tr>
            <td>${escapeHtml(row.employeeId)}</td>
            <td>${escapeHtml(row.fullName)}</td>
            <td style="text-align:center;">${escapeHtml(row.maturityDate)}</td>
            <td style="text-align:center;">${escapeHtml(row.lastPaidDate)}</td>
            ${renderPrintMoneyCell(row.gross)}
            ${renderPrintMoneyCell(row.payment)}
            ${renderPrintMoneyCell(row.balance)}
            <td style="text-align:center;">${escapeHtml(row.progress)}</td>
        </tr>
    `).join('') + `
        <tr class="summary-row">
            <td></td>
            <td></td>
            <td></td>
            <td class="summary-label">TOTAL / AVG</td>
            ${renderPrintMoneyCell(formatCurrency(grossTotal), true)}
            ${renderPrintMoneyCell(formatCurrency(paymentTotal), true)}
            ${renderPrintMoneyCell(formatCurrency(balanceTotal), true)}
            <td class="summary-progress">${escapeHtml(avgProgress.toFixed(2))}%</td>
        </tr>
        <tr class="generated-row">
            <td colspan="8">Generated By: ${escapeHtml(generatedBy)}</td>
        </tr>
        <tr class="generated-row generated-row-last">
            <td colspan="8">Generated Date and Time: ${escapeHtml(printedAt)}</td>
        </tr>
    `;

    const printWindow = window.open('', '_blank', 'width=1100,height=700');
    if (!printWindow) return;

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Loan Progress Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #1f2937; }
                h1 { margin: 0 0 8px; font-size: 20px; }
                .meta { margin-bottom: 12px; font-size: 12px; color: #475569; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; }
                th { background: #ce1126; color: #fff; text-align: left; }
                th:nth-child(3), th:nth-child(4), th:nth-child(8) { text-align: center; }
                th:nth-child(5), th:nth-child(6), th:nth-child(7) { text-align: right; }
                td.money-cell { padding: 0; }
                td.money-cell .money-wrap {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    width: 100%;
                    padding: 8px;
                    box-sizing: border-box;
                }
                td.money-cell .peso { min-width: 14px; text-align: left; }
                td.money-cell .amount { flex: 1; text-align: right; }
                tr.summary-row td { font-weight: 700; background: #f8fafc; }
                td.summary-label { text-align: right; }
                td.summary-progress { text-align: center; }
                tr.generated-row td {
                    font-size: 11px;
                    color: #475569;
                    text-align: left;
                    background: #ffffff;
                    border-top: 0;
                }
                tr.generated-row-last td {
                    border-bottom: 1px solid #d1d5db;
                }
            </style>
        </head>
        <body>
            <h1>Loan Progress Report</h1>
            <div class="meta">Status: ${escapeHtml(statusLabel)} | Printed: ${escapeHtml(printedAt)}</div>
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Maturity Date</th>
                        <th>Last Paid Date</th>
                        <th>Gross</th>
                        <th>Payment</th>
                        <th>Balance</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

function renderPrintMoneyCell(value, isSummary = false) {
    const raw = String(value ?? '').trim();
    const amountOnly = raw.replace(/^₱\s*/, '');
    const tdClass = isSummary ? 'money-cell summary-money' : 'money-cell';
    return `<td class="${tdClass}"><div class="money-wrap"><span class="peso">₱</span><span class="amount">${escapeHtml(amountOnly)}</span></div></td>`;
}

function parseCurrencyNumber(value) {
    const normalized = String(value ?? '').replace(/[^0-9.-]/g, '');
    const num = Number(normalized);
    return Number.isFinite(num) ? num : 0;
}

function parseProgressPercent(value) {
    const normalized = String(value ?? '').replace('%', '').trim();
    const num = Number(normalized);
    return Number.isFinite(num) ? num : 0;
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
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
