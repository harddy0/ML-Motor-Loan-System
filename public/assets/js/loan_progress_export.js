// ==========================================
// LOAN PROGRESS EXPORT: Print & Excel Logic
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    bindLoanProgressExportMenu();
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
        if (hoverCloseTimer) { clearTimeout(hoverCloseTimer); hoverCloseTimer = null; }
        menu.classList.remove('hidden');
    }

    function closeMenuWithDelay() {
        if (hoverCloseTimer) clearTimeout(hoverCloseTimer);
        hoverCloseTimer = setTimeout(() => { menu.classList.add('hidden'); }, 120);
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

function getLoanProgressExportRows() {
    // Relies on global currentLoanProgressRows from main script
    return currentLoanProgressRows.map((r) => ({
        status: (function () {
            const s = String(r.status || '').toUpperCase();
            if (s === 'FULLY PAID') return 'Fully Paid';
            if (s === 'INACTIVE') return 'Inactive';
            return 'Ongoing';
        })(),
        employeeId: String(r.employe_id || '--'),
        fullName: String(r.borrower_name || '--'),
        maturityDate: formatDate(r.maturity_date),
        lastPaidDate: formatDate(r.last_paid_due_date),
        rawLastPaidDate: String(r.last_paid_due_date || ''),
        gross: formatCurrency(r.gross_total),
        payment: formatCurrency(r.payment_total),
        balance: formatCurrency(r.balance_total),
        progress: `${Number(r.pct_done) || 0}%`
    }));
}

function exportLoanProgressToExcel() {
    const hasPartialDate = (currentLoanProgressFromDate && !currentLoanProgressToDate) || (!currentLoanProgressFromDate && currentLoanProgressToDate);
    if (hasPartialDate) {
        alert('Please select both From and To dates before exporting.');
        return;
    }

    const statusParam = encodeURIComponent(currentLoanProgressStatus);
    const fromParam = currentLoanProgressFromDate ? `&from=${encodeURIComponent(currentLoanProgressFromDate)}` : '';
    const toParam = currentLoanProgressToDate ? `&to=${encodeURIComponent(currentLoanProgressToDate)}` : '';
    const exportUrl = `${BASE_URL}/public/api/export_loan_progress_excel.php?status=${statusParam}${fromParam}${toParam}`;
    window.location.href = exportUrl;
}

function printLoanProgress() {
    const rows = getLoanProgressExportRows();
    if (!rows.length) {
        alert('No data available to print for the selected period.');
        return;
    }

    const statusLabel = currentLoanProgressStatus === 'ALL' ? 'All' : currentLoanProgressStatus;
    const printedAt = new Date().toLocaleString('en-US');
    const generatedBy = String(window.CURRENT_USER_FULL_NAME || 'SYSTEM USER').toUpperCase();

    let grossTotal = 0, paymentTotal = 0, balanceTotal = 0, progressTotal = 0;

    rows.forEach((row) => {
        grossTotal += parseCurrencyNumber(row.gross);
        paymentTotal += parseCurrencyNumber(row.payment);
        balanceTotal += parseCurrencyNumber(row.balance);
        progressTotal += parseProgressPercent(row.progress);
    });

    const avgProgress = rows.length > 0 ? (progressTotal / rows.length) : 0;
    const sortedRows = [...rows].sort((a, b) => getLastPaidDateSortValue(b.rawLastPaidDate) - getLastPaidDateSortValue(a.rawLastPaidDate));

    let previousGroupKey = '';
    const groupedTableRows = [];
    sortedRows.forEach((row) => {
        const groupKey = getMonthYearGroupKey(row.rawLastPaidDate);
        if (groupKey !== previousGroupKey) {
            groupedTableRows.push(`<tr class="group-row"><td colspan="9">${escapeHtml(groupKey)}</td></tr>`);
            previousGroupKey = groupKey;
        }

        groupedTableRows.push(`
            <tr>
                <td>${escapeHtml(row.status)}</td>
                <td>${escapeHtml(row.employeeId)}</td>
                <td>${escapeHtml(row.fullName)}</td>
                <td style="text-align:center;">${escapeHtml(row.maturityDate)}</td>
                <td style="text-align:center;">${escapeHtml(row.lastPaidDate)}</td>
                ${renderPrintMoneyCell(row.gross)}
                ${renderPrintMoneyCell(row.payment)}
                ${renderPrintMoneyCell(row.balance)}
                <td style="text-align:center;">${escapeHtml(row.progress)}</td>
            </tr>
        `);
    });

    const tableRows = groupedTableRows.join('') + `
        <tr class="summary-row">
            <td></td><td></td><td></td><td></td><td class="summary-label">TOTAL / AVG</td>
            ${renderPrintMoneyCell(formatCurrency(grossTotal), true)}
            ${renderPrintMoneyCell(formatCurrency(paymentTotal), true)}
            ${renderPrintMoneyCell(formatCurrency(balanceTotal), true)}
            <td class="summary-progress">${escapeHtml(avgProgress.toFixed(2))}%</td>
        </tr>
        <tr class="generated-row"><td colspan="9">Generated By: ${escapeHtml(generatedBy)}</td></tr>
        <tr class="generated-row generated-row-last"><td colspan="9">Generated Date and Time: ${escapeHtml(printedAt)}</td></tr>
    `;

    const printWindow = window.open('', '_blank', 'width=1100,height=700');
    if (!printWindow) return;

    const exportHeaderHtml = buildExportHeaderHtml();

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Loan Progress Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #1f2937; }
                @media print { .sys-header { display: block; position: static; } thead { display: table-row-group; } }
                .sys-header { border-bottom: 1px solid #cbd5e1; margin-bottom: 10px; padding: 8px 0 10px; }
                .sys-header-row { min-height: 48px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
                .sys-header-left { display: flex; align-items: center; min-width: 56px; }
                .sys-header-left img { height: 30px; width: auto; display: block; }
                .sys-header-center { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 3px; }
                .sys-header-center img { height: 28px; width: auto; display: block; }
                .sys-header-center .brand-text { display: block; color: #64748b; font-size: 12px; letter-spacing: 0.18em; font-weight: 700; text-transform: uppercase; }
                .sys-header-right { min-width: 56px; }
                h1 { margin: 0 0 8px; font-size: 20px; }
                .meta { margin-bottom: 12px; font-size: 12px; color: #475569; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; }
                th { background: #ce1126; color: #fff; text-align: left; }
                th:nth-child(3), th:nth-child(4), th:nth-child(8) { text-align: center; }
                th:nth-child(5), th:nth-child(6), th:nth-child(7) { text-align: right; }
                td.money-cell { padding: 0; }
                td.money-cell .money-wrap { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 8px; box-sizing: border-box; }
                td.money-cell .peso { min-width: 14px; text-align: left; }
                td.money-cell .amount { flex: 1; text-align: right; }
                tr.group-row td { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; background: #f8fafc; }
                tr.summary-row td { font-weight: 700; background: #f8fafc; }
                td.summary-label { text-align: right; }
                td.summary-progress { text-align: center; }
                tr.generated-row td { font-size: 11px; color: #475569; text-align: left; background: #ffffff; border-top: 0; }
                tr.generated-row-last td { border-bottom: 1px solid #d1d5db; }
            </style>
        </head>
        <body>
            <div class="sys-header">${exportHeaderHtml}</div>
            <h1>Loan Progress Report</h1>
            <div class="meta">Status: ${escapeHtml(statusLabel)} | Printed: ${escapeHtml(printedAt)}</div>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
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

function buildExportHeaderHtml() {
    const templateHtml = getEmbeddedExportHeaderTemplate();
    if (!templateHtml) return '';
    const parser = new DOMParser();
    const doc = parser.parseFromString(templateHtml, 'text/html');
    const leftLogo = doc.querySelector('[name="logo"]')?.innerHTML || '';
    const centerLogo = doc.querySelector('[name="center"]')?.querySelector('img')?.outerHTML || '';
    const brandText = doc.querySelector('[name="center"]')?.querySelector('span')?.outerHTML || '';
    return `<div class="sys-header-row"><div class="sys-header-left">${leftLogo}</div><div class="sys-header-center">${centerLogo}${brandText}</div><div class="sys-header-right"></div></div>`;
}

function getEmbeddedExportHeaderTemplate() {
    const template = document.getElementById('exportHeaderTemplate');
    if (!template) return '';
    return template.innerHTML || '';
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