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

        if(txn.status === 'PAID') {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
            totalPaid += totalAmt;
            finalBalance = balAmt; 
        }

        const rowClass = txn.status === 'PAID' ? 'bg-white' : 'bg-yellow-50/30';
        const statusClass = txn.status === 'PAID' 
            ? 'bg-green-100 text-green-700' 
            : 'bg-yellow-100 text-yellow-700';
            
        const datePaidText = txn.date_paid ? `<span class="font-bold text-slate-700">${txn.date_paid}</span>` : '<span class="text-slate-300 italic">--</span>';
        const notesText = txn.notes ? txn.notes : '<span class="text-slate-300 italic">--</span>';

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-50 transition-colors border-b border-slate-100 ${rowClass}`;
        
        // Match the percentage widths set in the ledger_detail.php header
        tr.innerHTML = `
            <td class="w-[10%] px-3 py-3 text-xs font-bold text-slate-600 border-r border-slate-100 text-center">${txn.scheduled_date}</td>
            <td class="w-[12%] px-3 py-3 text-xs text-center border-r border-slate-100">${datePaidText}</td>
            <td class="w-[12%] px-3 py-3 text-xs font-bold text-slate-600 text-right border-r border-slate-100">
                ${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[12%] px-3 py-3 text-xs font-bold text-slate-600 text-right border-r border-slate-100">
                 ${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[12%] px-3 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100 bg-yellow-50/50">
                 ${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[14%] px-3 py-3 text-sm font-black text-[#ff3b30] text-right border-r border-slate-100">
                ${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}
            </td>
            <td class="w-[10%] px-3 py-3 text-center border-r border-slate-100">
                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black uppercase ${statusClass}">${txn.status}</span>
            </td>
            <td class="flex-1 px-3 py-3 text-xs text-slate-500 border-r border-slate-100 text-left truncate max-w-[200px]" title="${txn.notes || ''}">
                ${notesText}
            </td>
        `;
        tbody.appendChild(tr);
    });

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