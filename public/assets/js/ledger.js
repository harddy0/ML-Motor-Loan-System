// --- GLOBAL VARIABLES ---
document.addEventListener("DOMContentLoaded", function() {
    initializeFilters();
});

// ==========================================
// SEARCH & DATE FILTER LOGIC
// ==========================================
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const viewAllBtn = document.getElementById('viewAllBtn');

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (fromDate) fromDate.addEventListener('change', applyFilters);
    if (toDate) toDate.addEventListener('change', applyFilters);

    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (fromDate) fromDate.value = '';
            if (toDate) toDate.value = '';
            applyFilters();
        });
    }
}

function applyFilters() {
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const from = fromDate ? fromDate.value : '';
    const to = toDate ? toDate.value : '';

    const rows = document.querySelectorAll('.ledger-row');
    
    let totalCount = 0;
    let ongoingCount = 0;
    let paidCount = 0;
    let voidedCount = 0; // NEW

    rows.forEach(row => {
        const searchableText = row.getAttribute('data-search') || '';
        const rowDate = row.getAttribute('data-date') || '';
        const status = row.getAttribute('data-status') || '';

        const matchesSearch = searchableText.includes(searchTerm);
        
        let matchesDate = true;
        if (from && rowDate < from) matchesDate = false;
        if (to && rowDate > to) matchesDate = false;

        if (matchesSearch && matchesDate) {
            row.style.display = ''; 
            totalCount++;
            if (status === 'ONGOING') {
                ongoingCount++;
            } else if (status === 'FULLY PAID') {
                paidCount++;
            } else if (status === 'VOIDED') {
                voidedCount++;
            }
        } else {
            row.style.display = 'none'; 
        }
    });

    const totalEl = document.getElementById('total-ledgers-count');
    const ongoingEl = document.getElementById('ongoing-count');
    const paidEl = document.getElementById('paid-count');
    const voidedEl = document.getElementById('voided-count');

    if (totalEl) totalEl.innerText = totalCount;
    if (ongoingEl) ongoingEl.innerText = ongoingCount;
    if (paidEl) paidEl.innerText = paidCount;
    if (voidedEl) voidedEl.innerText = voidedCount;
}

// --- MAIN PAGE INTERACTION ---
function handleRowClick(loanId) {
    if (typeof ALL_BORROWERS === 'undefined') {
        console.error("Borrower data not loaded.");
        return;
    }

    const selectedBorrower = ALL_BORROWERS.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower) {
        openLedgerModal(selectedBorrower);
    }
}

// --- MODAL LOGIC ---
function openLedgerModal(borrowerData) {
    const modal = document.getElementById('ledgerDetailModal');
    const loader = document.getElementById('ledger-loading');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loader.classList.remove('hidden'); 
    
    document.getElementById('modal-ledger-rows').innerHTML = '';

    document.getElementById('modal-ledger-name').innerText = borrowerData.name;
    document.getElementById('modal-ledger-id').innerText = borrowerData.employe_id;
    document.getElementById('modal-ledger-pn').innerText = borrowerData.pn_number || '--';
    document.getElementById('modal-ledger-pndate').innerText = borrowerData.g_date || '--'; 
    document.getElementById('modal-ledger-maturity').innerText = borrowerData.maturity_date || '--';
    document.getElementById('modal-ledger-terms').innerText = borrowerData.term_months + ' Months';
    
    // FIX: Map missing fields
    document.getElementById('modal-ledger-ref').innerText = borrowerData.loan_ref_no || '--';
    document.getElementById('modal-ledger-region').innerText = borrowerData.region || '--';
    document.getElementById('modal-ledger-branch').innerText = borrowerData.branch || '--';
    document.getElementById('modal-ledger-contact').innerText = borrowerData.contact_number || '--';
    
    document.getElementById('btn-export-ledger').setAttribute('data-loan-id', borrowerData.loan_id);

    // Status Badge Logic
    const statusBadge = document.getElementById('modal-ledger-status');
    statusBadge.innerText = borrowerData.current_status;
    
    if(borrowerData.current_status === 'FULLY PAID') {
        statusBadge.className = "inline-block px-4 py-1.5 bg-slate-200 text-slate-600 text-[13px] font-black uppercase rounded-full";
    } else if (borrowerData.current_status === 'VOIDED') {
        statusBadge.className = "inline-block px-4 py-1.5 bg-orange-100 text-orange-700 text-[13px] font-black uppercase rounded-full";
    } else {
        statusBadge.className = "inline-block px-4 py-1.5 bg-green-100 text-green-700 text-[13px] font-black uppercase rounded-full";
    }

    const principal = parseFloat(borrowerData.loan_amount) || 0;
    const semiAmort = parseFloat(borrowerData.semi_monthly_amt) || 0;
    
    // FIX: Calculate total rate percentage (e.g., 0.015 * 24 months * 100 = 36%)
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months) || 0;
    const totalRatePercent = (addOnRateDecimal * termMonths * 100).toFixed(0);
    
    document.getElementById('modal-ledger-rate').innerText = totalRatePercent + '%';
    document.getElementById('modal-ledger-principal').innerText = '₱ ' + principal.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('modal-ledger-amort').innerText = '₱ ' + semiAmort.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('modal-ledger-principal').innerText = '₱ ' + principal.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('modal-ledger-amort').innerText = '₱ ' + semiAmort.toLocaleString(undefined, {minimumFractionDigits:2});

    fetchLedgerData(borrowerData.loan_id)
        .then(transactions => {
            renderLedgerTable(transactions, borrowerData); // FIX: Send entire borrowerData
            loader.classList.add('hidden');
        })
        .catch(err => {
            console.error("Error loading ledger:", err);
            loader.classList.add('hidden');
            document.getElementById('modal-ledger-rows').innerHTML = '<tr><td colspan="8" class="text-center text-red-500 py-4 font-bold">Failed to load schedule.</td></tr>';
        });
}

function closeLedgerModal() {
    document.getElementById('ledgerDetailModal').classList.remove('flex');
    document.getElementById('ledgerDetailModal').classList.add('hidden');
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
    
    let totalPrincipalPaid = 0;
    let totalInterestPaid = 0;
    let totalCollected = 0;
    
    let sumTotalPrincipal = 0;
    let sumTotalInterest = 0;

    transactions.forEach(txn => {
        // Safe parsing for both DB column structures
        const principalAmt = parseFloat(txn.principal_amt || txn.principal) || 0;
        const interestAmt = parseFloat(txn.interest_amt || txn.interest) || 0;
        const totalAmt = parseFloat(txn.total_payment || txn.total) || 0;
        const balAmt = parseFloat(txn.remaining_bal || txn.balance) || 0;

        sumTotalPrincipal += principalAmt;
        sumTotalInterest += interestAmt;

        const statusClean = (txn.status || "").toUpperCase();
        const isPaid = statusClean === 'PAID';

        if(isPaid) {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
            totalCollected += totalAmt;
        }

        const balanceTextColor = isPaid ? '!text-slate-900' : '!text-[#e11d48]';
        
        let statusBadgeClass = 'bg-yellow-100 text-yellow-700 border border-yellow-200'; // Default
        if (isPaid) {
            statusBadgeClass = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
        } else if (statusClean === 'VOIDED') {
            statusBadgeClass = 'bg-orange-100 text-orange-700 border border-orange-200';
        } else if (statusClean === 'MISSED' || statusClean === 'UNPAID') {
            statusBadgeClass = 'bg-red-100 text-red-700 border border-red-200';
        }

        const datePaidText = txn.date_paid 
            ? `<span class="text-emerald-600 font-medium">${txn.date_paid}</span>` 
            : `<span class="text-slate-300 italic">--</span>`;

        const remarksText = txn.remarks || '';

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-200 transition-colors border-b border-slate-100`;
        
        // FIX: Match widths with the thead AND reduced vertical padding (py-1.5 instead of py-3) for tighter rows
        tr.innerHTML = `
            <td class="w-[14%] px-3 py-1.5 text-center text-slate-600 border-r border-slate-50 font-medium">
                ${txn.scheduled_date || '--'}
            </td>
            <td class="w-[14%] px-3 py-1.5 text-center border-r border-slate-50 ${isPaid ? 'bg-emerald-50/20' : ''}">
                ${datePaidText}
            </td>
            <td class="w-[12%] px-3 py-1.5 text-right text-slate-500 border-r border-slate-50 pr-2">
                ${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[12%] px-3 py-1.5 text-right text-slate-500 border-r border-slate-50 pr-2">
                ${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[12%] px-3 py-1.5 text-right text-slate-900 border-r border-slate-50 bg-slate-50/10 font-medium pr-4">
                ${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[12%] px-3 py-1.5 text-right border-r border-slate-50 ${balanceTextColor} pr-4">
                ${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[10%] px-3 py-1.5 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold ${statusBadgeClass}">
                    ${statusClean}
                </span>
            </td>
            <td class="px-3 py-1.5 text-slate-500 text-left truncate" title="${remarksText}">
                ${remarksText}
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Calculate dynamic balances for Payment Summary
    const principalBalance = sumTotalPrincipal - totalPrincipalPaid;
    const interestBalance = sumTotalInterest - totalInterestPaid;
    const totalOutstanding = principalBalance + interestBalance;

    // FIX: Ensure elements exist before injecting into the UI to prevent JS crashing
    const safeSetText = (id, val) => {
        const el = document.getElementById(id);
        if(el) el.innerText = '₱ ' + val.toLocaleString(undefined, {minimumFractionDigits:2});
    };

    safeSetText('modal-ledger-principal-paid', totalPrincipalPaid);
    safeSetText('modal-ledger-principal-balance', principalBalance);
    safeSetText('modal-ledger-interest-paid', totalInterestPaid);
    safeSetText('modal-ledger-interest-balance', interestBalance);
    safeSetText('modal-ledger-total-collected', totalCollected);
    safeSetText('modal-ledger-total-balance', totalOutstanding);
}

function exportLedgerExcel() {
    const btn = document.getElementById('btn-export-ledger');
    const loanId = btn.getAttribute('data-loan-id');
    
    if (!loanId) return;

    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/export_ledger.php?loan_id=${loanId}`
        : `../../api/export_ledger.php?loan_id=${loanId}`;

    window.location.href = url;
}