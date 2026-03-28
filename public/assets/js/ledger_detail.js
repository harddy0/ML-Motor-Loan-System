// ==========================================
// LEDGER DETAIL: Modal, Transactions & Exports
// ==========================================

document.addEventListener("DOMContentLoaded", function() {
    setupLedgerExportDropdown();
});

function setupLedgerExportDropdown() {
    const menuBtn = document.getElementById('ledgerExportMenuBtn');
    const menu = document.getElementById('ledgerExportMenu');
    if (!menuBtn || !menu) return;

    document.addEventListener('click', (e) => {
        if (!menuBtn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
}

// Ensure formatting functions exist if loaded standalone
function _detailFormatDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
    return dateStr;
}

function openLedgerModal(borrowerData) {
    const modal = document.getElementById('ledgerDetailModal');
    const loader = document.getElementById('ledger-loading');
    
    if(!modal) return; // Failsafe if modal not on page
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if(loader) loader.classList.remove('hidden'); 
    
    populateLedgerFields(borrowerData);

    fetchLedgerData(borrowerData.loan_id)
        .then(transactions => {
            renderLedgerTable(transactions, borrowerData); 
            if(loader) loader.classList.add('hidden');
        })
        .catch(err => {
            console.error("Error loading ledger:", err);
            if(loader) loader.classList.add('hidden');
            const rowsEl = document.getElementById('modal-ledger-rows');
            if (rowsEl) rowsEl.innerHTML = '<tr><td colspan="8" class="text-center text-red-500 py-4 font-bold">Failed to load schedule.</td></tr>';
        });
}

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
    setText('modal-ledger-pndate', _detailFormatDate(borrowerData.g_date || borrowerData.date_granted));
    setText('modal-ledger-maturity', _detailFormatDate(borrowerData.maturity_date));
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
    
    const monthlyRatePercent = Number((addOnRateDecimal * 100).toFixed(2));
    setText('modal-ledger-rate', monthlyRatePercent + '%');
    
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
    const modal = document.getElementById('ledgerDetailModal');
    if(modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
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
    
    let totalPrincipalPaid = 0, totalInterestPaid = 0, totalCollected = 0;
    let sumTotalPrincipal = 0, sumTotalInterest = 0;

    transactions.forEach(txn => {
        const principalAmt = parseFloat(txn.principal_amt || txn.principal) || 0;
        const interestAmt = parseFloat(txn.interest_amt || txn.interest) || 0;
        const totalAmt = parseFloat(txn.total_payment || txn.total) || 0;
        const balAmt = parseFloat(txn.remaining_bal || txn.balance) || 0;

        sumTotalPrincipal += principalAmt;
        sumTotalInterest += interestAmt;

        const statusClean = (txn.status || "").toUpperCase();
        const statusNormalized = statusClean.replace(/[\s_-]/g, '');
        const isPaid = statusClean === 'PAID';
        const isUnpaid = statusClean === 'UNPAID';
        const isVoid = statusClean === 'VOIDED' || statusClean === 'VOID';
        const isNoDeduction = statusNormalized === 'NODEDUCTION';

        if(isPaid) {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
            totalCollected += totalAmt;
        }

        let rowTextClass = 'text-slate-900', rowBgClass = '', rowHoverClass = 'hover:bg-slate-200';
        let statusBadgeClass = 'text-slate-900', statusBadgeBaseClass = 'inline-block rounded-full text-[11px]';

        if (isVoid) {
            rowTextClass = 'text-slate-500'; statusBadgeClass = 'text-slate-500';
        } else if (isNoDeduction) {
            statusBadgeClass = 'text-red-600';     
        } else if (isPaid) {
            statusBadgeClass = 'text-[#1A924B]';   
        }

        const displayScheduledDate = _detailFormatDate(txn.scheduled_date);
        const remarksText = txn.remarks || '';

        const tr = document.createElement('tr');
        tr.className = `${rowBgClass} ${rowHoverClass} transition-colors border-b border-slate-100`;
        tr.innerHTML = `
            <td class="w-[16%] px-8 py-0 text-center ${rowTextClass} border-r border-slate-50 font-medium font-mono">${displayScheduledDate}</td>
            <td class="w-[15%] px-3 py-0 text-right ${rowTextClass} border-r border-slate-50 pr-2">${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[15%] px-3 py-0 text-right ${rowTextClass} border-r border-slate-50 pr-2">${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[15%] px-3 py-0 text-right ${rowTextClass} border-r border-slate-50 font-medium pr-4">${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[15%] px-3 py-0 text-right border-r border-slate-50 ${rowTextClass} pr-4">${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[10%] px-3 py-0 text-center ${rowTextClass}">
                <span style="font-size: 11px !important; font-weight: 700 !important;" class="${statusBadgeBaseClass} ${statusBadgeClass}">
                    ${statusClean === 'VOIDED' ? 'VOID' : statusClean}
                </span>
            </td>
            <td class="px-3 py-0 ${rowTextClass} text-left truncate" title="${remarksText}">${remarksText}</td>
        `;
        tbody.appendChild(tr);
    });

    const safeSetText = (id, val) => {
        const el = document.getElementById(id);
        if(el) el.innerText = '₱ ' + val.toLocaleString(undefined, {minimumFractionDigits:2});
    };

    const loanAmount = parseFloat(borrowerData.loan_amount) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months) || 0;
    const grossPrincipal = loanAmount;
    const grossInterest = loanAmount * addOnRateDecimal * termMonths; 
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
    safeSetText('modal-ledger-total-payment', totalPrincipalPaid + totalInterestPaid);
    safeSetText('modal-ledger-total-collected', totalCollected);
    safeSetText('modal-ledger-total-balance', totalOutstanding);
}

// ---------------------------------------------------------
// EXPORT & PRINT LOGIC
// ---------------------------------------------------------

function exportLedgerExcel() {
    const menu = document.getElementById('ledgerExportMenu');
    if (menu) menu.classList.add('hidden');

    const btn = document.getElementById('btn-export-ledger');
    const loanId = btn.getAttribute('data-loan-id');
    if (!loanId) return;
    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/export_ledger.php?loan_id=${loanId}`
        : `../../api/export_ledger.php?loan_id=${loanId}`;
    window.location.href = url;
}

function printLedgerReport() {
    const menu = document.getElementById('ledgerExportMenu');
    if (menu) menu.classList.add('hidden');

    const getText = (id, fallback = '--') => {
        const el = document.getElementById(id);
        if (!el) return fallback;
        const text = (el.innerText || '').trim();
        return text || fallback;
    };

    const parseAmount = (text) => {
        const cleaned = String(text || '').replace(/[^0-9.-]/g, '');
        const num = parseFloat(cleaned);
        return Number.isFinite(num) ? num : 0;
    };

    const formatAmount = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const rowsEl = document.getElementById('modal-ledger-rows');
    if (!rowsEl) return;

    const rowData = Array.from(rowsEl.querySelectorAll('tr')).map((tr) => {
        const cells = Array.from(tr.querySelectorAll('td'));
        if (cells.length < 7) return null;
        return {
            dueDate: (cells[0].innerText || '').trim(),
            principal: parseAmount(cells[1].innerText),
            interest: parseAmount(cells[2].innerText),
            total: parseAmount(cells[3].innerText),
            balance: parseAmount(cells[4].innerText),
            status: (cells[5].innerText || '').trim().toUpperCase(),
            remarks: (cells[6].innerText || '').trim()
        };
    }).filter(Boolean);

    if (!rowData.length) return;

    const subtotalPrincipal = rowData.reduce((sum, r) => sum + r.principal, 0);
    const subtotalInterest = rowData.reduce((sum, r) => sum + r.interest, 0);
    const subtotalTotal = rowData.reduce((sum, r) => sum + r.total, 0);

    const paidRows = rowData.filter((r) => r.status === 'PAID');
    const collectedPrincipal = paidRows.reduce((sum, r) => sum + r.principal, 0);
    const collectedInterest = paidRows.reduce((sum, r) => sum + r.interest, 0);
    const totalCollected = paidRows.reduce((sum, r) => sum + r.total, 0);

    const grossPrincipal = parseAmount(getText('modal-ledger-gross-principal', '0'));
    const grossInterest = parseAmount(getText('modal-ledger-gross-interest', '0'));
    const grossTotal = parseAmount(getText('modal-ledger-gross-total', '0'));
    const balancePrincipal = parseAmount(getText('modal-ledger-principal-balance', '0'));
    const balanceInterest = parseAmount(getText('modal-ledger-interest-balance', '0'));
    const balanceTotal = parseAmount(getText('modal-ledger-total-balance', '0'));

    const generatedBy = (typeof CURRENT_USER_FULLNAME !== 'undefined' && String(CURRENT_USER_FULLNAME).trim())
        ? String(CURRENT_USER_FULLNAME).trim().toUpperCase()
        : 'SYSTEM USER';
    const generatedAt = new Date().toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });

    const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const scheduleRows = rowData.map((r, idx) => {
        const rowClass = r.status === 'PAID' ? 'paid-row' : (r.status === 'NO DEDUCTION' ? 'no-deduction' : '');
        return `
        <tr class="${rowClass}">
            <td class="c-center">${idx + 1}</td>
            <td class="c-center">${esc(r.dueDate)}</td>
            <td class="c-right">${esc(formatAmount(r.principal))}</td>
            <td class="c-right">${esc(formatAmount(r.interest))}</td>
            <td class="c-right">${esc(formatAmount(r.total))}</td>
            <td class="c-right">${esc(formatAmount(r.balance))}</td>
            <td class="c-center"><strong>${esc(r.status)}</strong></td>
        </tr>`;
    }).join('');

    const printWindow = window.open('', '_blank', 'width=1100,height=900');
    if (!printWindow) return;

    printWindow.document.open();
    printWindow.document.write(`
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8" />
            <title>ML Motorcycle Loan</title>
            <style>
                @page { size: portrait; margin: 10mm; }
                * { -webkit-print-color-adjust: exact; print-color-adjust: exact; box-sizing: border-box; }
                body { margin: 0; font-family: Arial, sans-serif; color: #0f172a; font-size: 12px; }
                .report { width: 100%; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                td, th { border: 1px solid #0f172a; padding: 4px 6px; font-size: 11px; }
                .no-border { border: 0 !important; }
                .title { text-align: center; font-weight: 700; font-size: 13px; }
                .label { font-weight: 700; }
                .right { text-align: right; }
                .center { text-align: center; }
                .header-top { margin-bottom: 2px; }
                .section-gap { margin-top: 6px; }
                .app-head { font-weight: 700; text-align: center; }
                .sched-head th { font-weight: 700; text-align: center; }
                .c-right { text-align: right; }
                .c-center { text-align: center; }
                .paid-row td { background: #fecaca; }
                .no-deduction td { background: #fca5a5; }
                .totals td { font-weight: 700; }
                .collected { color: #15803d; font-weight: 700; }
            </style>
        </head>
        <body>
            <div class="report">
                <table class="header-top">
                    <tr><td colspan="7" class="title">SEMI - MONTHLY AMORTIZATION SCHEDULE</td></tr>
                    <tr><td colspan="2" class="label">Account Name :</td><td colspan="5"><strong>${esc(getText('modal-ledger-name'))}</strong></td></tr>
                    <tr><td colspan="2" class="label">ID Number:</td><td>${esc(getText('modal-ledger-id'))}</td><td colspan="4" class="no-border"></td></tr>
                    <tr><td colspan="2" class="label">Reference Number:</td><td>${esc(getText('modal-ledger-ref'))}</td><td colspan="4" class="no-border"></td></tr>
                    <tr><td colspan="2" class="label">PN Number:</td><td>${esc(getText('modal-ledger-pn'))}</td><td colspan="4" class="no-border"></td></tr>
                    <tr><td colspan="2" class="label">Region:</td><td>${esc(getText('modal-ledger-region'))}</td><td colspan="4" class="no-border"></td></tr>
                    <tr>
                        <td colspan="2" class="label">Branch:</td><td>${esc(getText('modal-ledger-branch', ''))}</td>
                        <td class="label">Loan Amount :</td><td class="right">${esc(formatAmount(parseAmount(getText('modal-ledger-principal', '0'))))}</td><td colspan="2" class="no-border"></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label">Contact Number:</td><td>${esc(getText('modal-ledger-contact'))}</td>
                        <td class="label">Interest/mo :</td><td class="right">${esc(getText('modal-ledger-rate').replace('%', '').trim())}</td><td>%</td><td></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label">Date Released:</td><td>${esc(getText('modal-ledger-pndate'))}</td>
                        <td class="label">Terms:</td><td class="right">${esc(getText('modal-ledger-terms').replace(/\s*months?\s*/i, '').trim())}</td><td>months</td><td></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="label">Maturity Date:</td><td>${esc(getText('modal-ledger-maturity'))}</td>
                        <td colspan="2" class="label right">Semi-Monthly Amortization</td><td class="right">${esc(formatAmount(parseAmount(getText('modal-ledger-amort', '0'))))}</td><td></td>
                    </tr>
                </table>

                <table class="section-gap">
                    <tr>
                        <td colspan="2" class="no-border"></td><td colspan="2" class="app-head">APPLICATION</td><td rowspan="2" class="app-head">TOTAL AMOUNT</td><td rowspan="2" class="app-head">PRINCIPAL BALANCE</td><td rowspan="2" class="app-head">STATUS</td>
                    </tr>
                    <tr><td class="app-head">#</td><td class="app-head">DATE</td><td class="app-head">PRINCIPAL</td><td class="app-head">INTEREST</td></tr>
                    ${scheduleRows}
                    <tr class="totals"><td colspan="2" class="right">SUBTOTALS:</td><td class="c-right">${esc(formatAmount(subtotalPrincipal))}</td><td class="c-right">${esc(formatAmount(subtotalInterest))}</td><td class="c-right">${esc(formatAmount(subtotalTotal))}</td><td></td><td></td></tr>
                </table>

                <table class="section-gap">
                    <tr><td colspan="2" class="app-head">GROSS</td><td colspan="2" class="app-head">PAYMENT</td><td colspan="2" class="app-head">BALANCE</td></tr>
                    <tr>
                        <td class="label">Principal Gross</td><td class="right">${esc(formatAmount(grossPrincipal))}</td>
                        <td class="label">Principal Paid</td><td class="right collected">${esc(formatAmount(collectedPrincipal))}</td>
                        <td class="label">Principal Balance</td><td class="right">${esc(formatAmount(balancePrincipal))}</td>
                    </tr>
                    <tr>
                        <td class="label">Interest Gross</td><td class="right">${esc(formatAmount(grossInterest))}</td>
                        <td class="label">Interest Paid</td><td class="right collected">${esc(formatAmount(collectedInterest))}</td>
                        <td class="label">Interest Balance</td><td class="right">${esc(formatAmount(balanceInterest))}</td>
                    </tr>
                    <tr class="totals">
                        <td class="label">Total Gross</td><td class="right">${esc(formatAmount(grossTotal))}</td>
                        <td class="label">Total Payment</td><td class="right collected">${esc(formatAmount(totalCollected))}</td>
                        <td class="label">Outstanding Balance</td><td class="right">${esc(formatAmount(balanceTotal))}</td>
                    </tr>
                </table>

                <table class="section-gap" style="width: 60%;">
                    <tr><td class="label">Generated By:</td><td>${esc(generatedBy)}</td></tr>
                    <tr><td class="label">Date Generated:</td><td>${esc(generatedAt)}</td></tr>
                </table>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}