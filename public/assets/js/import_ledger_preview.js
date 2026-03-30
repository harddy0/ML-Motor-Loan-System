// ==========================================
// IMPORT LEDGER PREVIEW: Modal & DB Save Logic
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const previewModal       = document.getElementById('importLedgerPreviewModal');
    const btnConfirm         = document.getElementById('btnConfirmLedgerSave');
    const btnCancelPreview   = document.getElementById('btnCancelLedgerPreview');
    const btnCancelPreview2  = document.getElementById('btnCancelLedgerPreview2');
    const successModal       = document.getElementById('ledgerSuccessModal');
    let currentPayload       = null;

    const hideModal = el => { if(el) { el.classList.add('hidden'); el.classList.remove('flex'); } };
    const showModal = el => { if(el) { el.classList.remove('hidden'); el.classList.add('flex'); } };

    btnCancelPreview?.addEventListener('click', () => hideModal(previewModal));
    btnCancelPreview2?.addEventListener('click', () => hideModal(previewModal));

    // Math/Format helpers
    const fmt = n => parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formatLongDate = (str) => {
        if (!str) return 'N/A';
        const d = new Date(str + 'T00:00:00');
        return isNaN(d) ? str : d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };
    
    const safeSetText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
    const safeSetVal = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = '₱ ' + fmt(val); };

    // Function called by import_ledger_main.js
    window.showLedgerPreview = function(payload) {
        currentPayload = payload;
        const b = payload.borrower;
        const requiresKptn = payload.requiresKptn;

        // Populate Borrower Info
        safeSetText('previewName', `${b.first_name} ${b.last_name}`);
        safeSetText('previewId', b.employe_id || 'N/A');
        safeSetText('previewContact', b.contact_number || 'N/A');
        safeSetText('previewRegion', b.region_display || b.region_name || b.region || 'N/A');
        safeSetText('previewBranch', b.branch_display || b.branch_name || b.branch || 'N/A');
        safeSetText('previewRef', b.reference_number || 'N/A');
        safeSetText('previewGranted', formatLongDate(b.date_released));
        safeSetText('previewMaturity', formatLongDate(b.maturity_date));

        const pnElement = document.getElementById('previewPn');
        if (pnElement) {
            pnElement.textContent = (b.pn_number && b.pn_number.trim() !== '') ? b.pn_number : (b.possible_pn_number || '--');
            pnElement.parentElement.style.display = 'flex';
        }

        // Populate Loan Setup
        safeSetText('previewAmount', '₱ ' + fmt(b.loan_amount));
        safeSetText('previewDeduction', '₱ ' + fmt(b.semi_monthly_amortization));
        const semi = parseFloat(b.semi_monthly_amortization) || 0;
        safeSetText('previewMonthlyAmort', '₱ ' + fmt(semi * 2));
        safeSetText('previewTerms', `${b.terms} Months`);

        const addOnRateDecimal = parseFloat(b.add_on_rate) || 0;
        const termMonths = parseInt(b.terms) || 0;
        safeSetText('previewRate', (addOnRateDecimal * termMonths * 100).toFixed(0) + '%');

        // Calculate Gross Values
        const loanAmountNum = parseFloat(b.loan_amount) || 0;
        const grossPrincipal = loanAmountNum;
        const grossInterest = loanAmountNum * addOnRateDecimal * termMonths;
        safeSetText('preview-gross-principal', '₱ ' + fmt(grossPrincipal));
        safeSetText('preview-gross-interest', '₱ ' + fmt(grossInterest));
        safeSetText('preview-gross-total', '₱ ' + fmt(grossPrincipal + grossInterest));

        // Manage Deposit Badge
        const depositWrapper = document.getElementById('preview-security-deposit-wrapper');
        const depositAmtEl = document.getElementById('previewDepositAmount');
        if (depositWrapper) depositWrapper.style.display = requiresKptn && payload.depositAmount > 0 ? 'flex' : 'none';
        if (depositAmtEl) depositAmtEl.textContent = '₱ ' + fmt(payload.depositAmount || 0);

        const badge = document.getElementById('previewKptnBadge');
        if (badge) {
            badge.innerHTML = requiresKptn
                ? `<span class="px-3 py-1 bg-rose-50 text-[#ce1126] text-[11px] font-black rounded-full border border-rose-200 uppercase tracking-wide">With Security Deposit</span>`
                : `<span class="px-3 py-1 bg-slate-100 text-slate-500 text-[11px] font-black rounded-full border border-slate-200 uppercase tracking-wide">No Security Deposit</span>`;
        }

        // Calculate Paid/Balance summary from array
        let totalPrincipal = 0, totalInterest = 0, paidPrincipal = 0, paidInterest = 0, totalCollected = 0;
        payload.ledger.forEach(row => {
            const p = parseFloat(row.principal) || 0;
            const i = parseFloat(row.interest) || 0;
            const t = parseFloat(row.total) || 0;
            totalPrincipal += p; 
            totalInterest += i;
            if ((row.status || '').toUpperCase() === 'PAID') { 
                paidPrincipal += p; 
                paidInterest += i; 
                totalCollected += t; 
            }
        });

        safeSetVal('preview-principal-paid', paidPrincipal);
        safeSetVal('preview-principal-balance', totalPrincipal - paidPrincipal);
        safeSetVal('preview-interest-paid', paidInterest);
        safeSetVal('preview-interest-balance', totalInterest - paidInterest);
        safeSetVal('preview-total-collected', totalCollected);
        safeSetVal('preview-total-outstanding', (totalPrincipal - paidPrincipal) + (totalInterest - paidInterest));

        // Generate Amortization Rows
        const tbody = document.getElementById('previewLedgerTableBody');
        if(tbody) {
            tbody.innerHTML = '';
            payload.ledger.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-200 transition-colors border-b border-slate-100';
                
                const statusClean = (row.status || '').toUpperCase();
                const isPaid = statusClean === 'PAID';
                
                let statusBadgeCls = 'text-red-700';
                if (isPaid || statusClean === 'VOIDED' || statusClean === 'NO DEDUCTION') statusBadgeCls = 'text-slate-800';
                
                const balColor = isPaid ? '!text-slate-900' : '!text-[#e11d48]';
                const rawDate = row.date || '';
                const parsedDate = rawDate ? new Date(rawDate + 'T00:00:00') : null;
                const formattedDate = parsedDate && !isNaN(parsedDate) ? parsedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : (rawDate || '--');

                tr.innerHTML = `
                    <td class="w-[5%] px-3 py-0 text-center text-slate-600 border-r border-slate-50 font-mono">${row.installment_no ?? '--'}</td>
                    <td class="w-[16%] px-3 py-0 text-center text-slate-600 border-r border-slate-50 font-mono">${formattedDate}</td>
                    <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-4 font-mono">${fmt(row.principal)}</td>
                    <td class="w-[15%] px-3 py-0 text-right text-slate-500 border-r border-slate-50 pr-4 font-mono">${fmt(row.interest)}</td>
                    <td class="w-[15%] px-3 py-0 text-right text-slate-900 border-r border-slate-50 bg-slate-50/10 font-mono pr-4">${fmt(row.total)}</td>
                    <td class="w-[15%] px-3 py-0 text-right border-r border-slate-50 ${balColor} pr-4 font-mono">${fmt(row.balance)}</td>
                    <td class="w-[10%] px-3 py-0 text-center border-r border-slate-50">
                        <span style="font-size:11px;font-weight:600;" class="inline-block px-2 py-0.5 rounded-full ${statusBadgeCls}">${statusClean === 'VOIDED' ? 'VOID' : statusClean}</span>
                    </td>
                    <td class="px-3 py-0 text-slate-500 text-left font-mono truncate">${row.remarks || ''}</td>`;
                tbody.appendChild(tr);
            });
        }
        showModal(previewModal);
    };

    // Phase 2 API Request: Send Data to Database
    btnConfirm?.addEventListener('click', function () {
        if (!currentPayload) return;
        btnConfirm.disabled = true; 
        btnConfirm.textContent = 'Saving...';
        
        const saveData = {
            borrower: currentPayload.borrower,
            ledger: currentPayload.ledger,
            requires_kptn: currentPayload.requiresKptn,
            deposit_amount: currentPayload.depositAmount ?? 0,
            kptn_code: currentPayload.kptnCode
        };

        let fetchOptions;
        if (currentPayload.requiresKptn && currentPayload.receiptFile) {
            const fd = new FormData();
            fd.append('data', JSON.stringify(saveData));
            fd.append('kptn_receipt', currentPayload.receiptFile);
            fetchOptions = { method: 'POST', body: fd };
        } else {
            fetchOptions = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(saveData) };
        }

        const apiUrl = typeof BASE_URL !== 'undefined' ? `${BASE_URL}/public/api/save_imported_ledger.php` : '../../api/save_imported_ledger.php';

        fetch(apiUrl, fetchOptions)
            .then(async res => {
                const text = await res.text();
                try { return JSON.parse(text); } catch { throw new Error('Server error during save.'); }
            })
            .then(data => {
                btnConfirm.disabled = false; btnConfirm.textContent = 'Save';
                if (data.success) { 
                    hideModal(previewModal); 
                    showModal(successModal); 
                } else if(typeof window.showKptnWarning === 'function') { 
                    window.showKptnWarning(data.error || 'Failed to save. Please try again.'); 
                }
            })
            .catch(err => {
                btnConfirm.disabled = false; btnConfirm.textContent = 'Save';
                if(typeof window.showKptnWarning === 'function') { 
                    window.showKptnWarning('System Error: ' + err.message); 
                }
            });
    });
});
