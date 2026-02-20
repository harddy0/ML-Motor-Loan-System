// --- GLOBAL VARIABLES ---
// Ensure ALL_BORROWERS is defined in the PHP file before this script runs
// window.ALL_BORROWERS should be populated in index.php

// --- MAIN PAGE INTERACTION ---

function handleRowClick(loanId) {
    if (typeof ALL_BORROWERS === 'undefined') {
        console.error("Borrower data not loaded.");
        return;
    }

    // Match by loan_id instead of employe_id
    const selectedBorrower = ALL_BORROWERS.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower) {
        openLedgerModal(selectedBorrower);
    }
}

// --- MODAL LOGIC (Formerly in ledger_detail.php) ---

function openLedgerModal(borrowerData) {
    const modal = document.getElementById('ledgerDetailModal');
    const loader = document.getElementById('ledger-loading');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loader.classList.remove('hidden'); 
    
    // Clear Table
    document.getElementById('modal-ledger-rows').innerHTML = '';

    // --- 1. POPULATE HEADER ---
    document.getElementById('modal-ledger-name').innerText = borrowerData.name;
    document.getElementById('modal-ledger-id').innerText = borrowerData.employe_id;
    document.getElementById('modal-ledger-pn').innerText = borrowerData.pn_number || '--';
    document.getElementById('modal-ledger-pndate').innerText = borrowerData.g_date || '--'; 
    document.getElementById('modal-ledger-maturity').innerText = borrowerData.maturity_date || '--';
    document.getElementById('modal-ledger-terms').innerText = borrowerData.term_months + ' Months';
    
    // Bind the loan ID to the export button so we know which one to download
    document.getElementById('btn-export-ledger').setAttribute('data-loan-id', borrowerData.loan_id);

    // Status Badge Logic
    const statusBadge = document.getElementById('modal-ledger-status');
    statusBadge.innerText = borrowerData.current_status;
    
    if(borrowerData.current_status === 'FULLY PAID') {
        statusBadge.className = "inline-block px-4 py-1.5 bg-slate-200 text-slate-600 text-xs font-black uppercase rounded-full";
    } else {
        statusBadge.className = "inline-block px-4 py-1.5 bg-green-100 text-green-700 text-xs font-black uppercase rounded-full";
    }

    // --- ADD-ON RATE CALCULATION ---
    const principal = parseFloat(borrowerData.loan_amount);
    const semiAmort = parseFloat(borrowerData.semi_monthly_amt);
    
    const ratePercent = (parseFloat(borrowerData.add_on_rate) || 0).toFixed(2);
    
    document.getElementById('modal-ledger-rate').innerText = ratePercent + '%';
    document.getElementById('modal-ledger-principal').innerText = '₱ ' + principal.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('modal-ledger-amort').innerText = '₱ ' + semiAmort.toLocaleString(undefined, {minimumFractionDigits:2});

    // --- 2. FETCH & RENDER LEDGER ---
    fetchLedgerData(borrowerData.loan_id)
        .then(transactions => {
            renderLedgerTable(transactions, principal);
            loader.classList.add('hidden');
        })
        .catch(err => {
            console.error("Error loading ledger:", err);
            loader.classList.add('hidden');
            document.getElementById('modal-ledger-rows').innerHTML = '<tr><td colspan="7" class="text-center text-red-500 py-4 font-bold">Failed to load schedule.</td></tr>';
        });
}

function closeLedgerModal() {
    document.getElementById('ledgerDetailModal').classList.remove('flex');
    document.getElementById('ledgerDetailModal').classList.add('hidden');
}

// --- REAL FETCH CALL to API Endpoint ---
function fetchLedgerData(loanId) {
    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/get_ledger_transactions.php?loan_id=${loanId}`
        : `../../../public/api/get_ledger_transactions.php?loan_id=${loanId}`;

    return fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.error);
            }
        });
}

function renderLedgerTable(transactions, initialPrincipal) {
    const tbody = document.getElementById('modal-ledger-rows');
    tbody.innerHTML = '';
    
    let totalPrincipalPaid = 0;
    let totalInterestPaid = 0;
    let totalPaid = 0;
    let finalBalance = initialPrincipal; 

    transactions.forEach(txn => {
        const principalAmt = parseFloat(txn.principal);
        const interestAmt = parseFloat(txn.interest);
        const totalAmt = parseFloat(txn.total);
        const balAmt = parseFloat(txn.balance);

        // 1. STYRIC STATUS CHECK: Ensure we catch 'PAID' regardless of casing
        const statusClean = (txn.status || "").toUpperCase();
        const isPaid = statusClean === 'PAID';

        if(isPaid) {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
            totalPaid += totalAmt;
            finalBalance = balAmt; 
        }

        // 2. DYNAMIC COLOR LOGIC: 
        // Turns BLACK (!text-slate-900) if Paid, ORANGE (!text-[#e11d48]) if Pending
        const balanceTextColor = isPaid ? '!text-slate-900' : '!text-[#e11d48]';
        
        const statusBadgeClass = isPaid 
            ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' 
            : 'bg-yellow-100 text-yellow-700 border border-yellow-200';

        const datePaidText = txn.date_paid 
            ? `<span class="font-bold text-emerald-600">${txn.date_paid}</span>` 
            : `<span class="text-slate-300 italic">--</span>`;

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-50 transition-colors border-b border-slate-100`;
        
        // 3. TABLE ROW GENERATION
        // All widths (w-32, w-40, w-24) now match your ledger_detail.php header exactly
        tr.innerHTML = `
            <td class="w-32 p-4 text-center text-xs font-bold text-slate-600 border-r border-slate-50">
                ${txn.scheduled_date}
            </td>
            <td class="w-32 p-4 text-center text-xs border-r border-slate-50 ${isPaid ? 'bg-emerald-50/20' : ''}">
                ${datePaidText}
            </td>
            <td class="p-4 text-right font-mono text-xs text-slate-500 border-r border-slate-50">
                ${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="p-4 text-right font-mono text-xs text-slate-500 border-r border-slate-50">
                ${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="p-4 text-right font-black text-xs text-slate-900 border-r border-slate-50 bg-slate-50/10">
                ${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-40 p-4 text-right font-black text-xs border-r border-slate-50 ${balanceTextColor}">
                ${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-24 p-4 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter ${statusBadgeClass}">
                    ${statusClean}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // 4. UPDATE SUMMARY TOTALS
    document.getElementById('modal-ledger-balance').innerText = '₱ ' + finalBalance.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('sum-principal').innerText = '₱ ' + totalPrincipalPaid.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('sum-interest').innerText = '₱ ' + totalInterestPaid.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('sum-paid').innerText = '₱ ' + totalPaid.toLocaleString(undefined, {minimumFractionDigits:2});
}

// --- EXPORT LOGIC ---
function exportLedgerExcel() {
    const btn = document.getElementById('btn-export-ledger');
    const loanId = btn.getAttribute('data-loan-id');
    
    if (!loanId) return;

    // Open the export endpoint, which triggers the download
    const url = typeof BASE_URL !== 'undefined' 
        ? `${BASE_URL}/public/api/export_ledger.php?loan_id=${loanId}`
        : `../../../public/api/export_ledger.php?loan_id=${loanId}`;

    window.location.href = url;
}