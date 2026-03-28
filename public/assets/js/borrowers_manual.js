document.addEventListener('DOMContentLoaded', function() {
    setupAddModalLogic();

    // Auto-open KPTN modal from URL query params
    try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open_attach') === '1') {
            const loanId = params.get('loan_id');
            const borrowerName = params.get('name') ? decodeURIComponent(params.get('name')) : '';
            const kptn = params.get('kptn') || '';
            if (loanId) {
                openAttachKptnModal(loanId, borrowerName, kptn);
                history.replaceState({}, '', window.location.pathname + window.location.hash);
            }
        }
    } catch (e) {
        console.error('Failed to auto-open attach modal', e);
    }
});

function handleBorrowerRowClick(loanId) {
    const selectedBorrower = currentBorrowersData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (selectedBorrower) openViewModal(selectedBorrower);
}

function openSecurityDepositModalByLoanId(loanId) {
    const selectedBorrower = currentBorrowersData.find(b => parseInt(b.loan_id) === parseInt(loanId));
    if (!selectedBorrower) return;

    openAttachKptnModal(
        selectedBorrower.loan_id,
        selectedBorrower.name || '',
        selectedBorrower.pending_kptn || '',
        selectedBorrower.deposit_amount || 0
    );
}

function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    
    document.getElementById('m-id').innerText      = data.id || 'N/A';
    document.getElementById('m-fname').innerText   = data.first_name || 'N/A';
    document.getElementById('m-lname').innerText   = data.last_name || 'N/A';
    document.getElementById('m-date').innerText    = formatDate(data.raw_date) || 'N/A';
    document.getElementById('m-contact').innerText = data.contact || 'N/A';
    document.getElementById('m-pn').innerText      = data.pn_no || 'N/A';
    document.getElementById('m-ref-no').innerText   = data.reference_no || data.reference_number || 'N/A';
    document.getElementById('m-pn-mat').innerText  = formatDate(data.pn_maturity) || 'N/A';
    document.getElementById('m-region').innerText  = data.region || 'N/A';
    const requiresKptn = data.requires_kptn == 1 || data.requires_kptn === true;
    const kptnCandidate = String(data.pending_kptn || data.kptn || '').trim();
    const hasKptnCode = requiresKptn && kptnCandidate && !/^NR_/i.test(kptnCandidate);
    document.getElementById('m-kptn-code').innerText = hasKptnCode
        ? ('- ' + kptnCandidate.toUpperCase())
        : '';
    const kptnIndicator = document.getElementById('m-kptn-indicator');
    if (kptnIndicator) {
        kptnIndicator.classList.toggle('bg-[#ce2216]', !!hasKptnCode);
        kptnIndicator.classList.toggle('bg-slate-400', !hasKptnCode);
    }
    
    const loanAmount = parseFloat(data.loan_amount || 0);
    const semiMonthly = parseFloat(data.deduction || 0);
    const monthly = semiMonthly * 2;
    const rawRate = parseFloat(data.add_on_rate);
    const addOnRateDecimal = Number.isFinite(rawRate)
        ? (rawRate > 1 ? (rawRate / 100) : rawRate)
        : 0;
    const termMonths = parseInt(data.terms || 0, 10);
    let monthlyRatePercent;
    if (Number.isFinite(rawRate) && rawRate > 0) {
        if (rawRate <= 1) {
            monthlyRatePercent = rawRate * 100;
        } else if (rawRate <= 10) {
            monthlyRatePercent = rawRate;
        } else {
            monthlyRatePercent = termMonths > 0 ? (rawRate / termMonths) : rawRate;
        }
    } else {
        monthlyRatePercent = 1.5;
    }
    monthlyRatePercent = Number(monthlyRatePercent.toFixed(2));
    const grossPrincipal = loanAmount;
    const grossInterest = loanAmount * addOnRateDecimal * termMonths;
    const grossTotal = grossPrincipal + grossInterest;

    document.getElementById('m-amount').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + loanAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-terms').innerText  = data.terms ? data.terms + ' Months' : 'N/A';
    document.getElementById('m-rate').innerText = monthlyRatePercent + '%';
    document.getElementById('m-deduct').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + semiMonthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-monthly').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + monthly.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-principal').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossPrincipal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-interest').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossInterest.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';
    document.getElementById('m-gross-total').innerHTML = '<div class="flex justify-between items-center w-full"><span>₱</span><span>' + grossTotal.toLocaleString('en-US', { minimumFractionDigits: 2 }) + '</span></div>';

    if (window.kptnSetTitle) window.kptnSetTitle(data.name || '');

    if (window.kptnHandleState) {
        window.kptnHandleState(requiresKptn, data.file_path, data.mime_type, data.loan_id);
    }

    const btnVoid = document.getElementById('btnOpenVoidModal');
    if (btnVoid) {
        const paymentStarted = parseInt(data.paid_count || 0) > 0;
        if (data.current_status !== 'ONGOING' || paymentStarted) {
            btnVoid.classList.add('hidden');
        } else {
            btnVoid.classList.remove('hidden');
            currentVoidId   = data.id;
            currentVoidName = data.name ? data.name.toUpperCase() : "UNKNOWN BORROWER";
        }
    }

    const btnIn = document.getElementById('btnOpenInactivateModal');
    if (btnIn) {
        const paymentStarted = parseInt(data.paid_count || 0) > 0;
        if (data.current_status !== 'ONGOING' || !paymentStarted) {
            btnIn.classList.add('hidden');
        } else {
            btnIn.classList.remove('hidden');
            currentInactivateLoanId = data.loan_id;
            currentInactivateName = data.name ? data.name.toUpperCase() : "UNKNOWN BORROWER";
        }
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openAttachKptnModal(loanId, borrowerName, pendingKptn = '', depositAmount = 0) {
    document.getElementById('ak_loan_id').value = loanId;
    document.getElementById('ak_borrower_name').innerText = borrowerName.toUpperCase();
    document.getElementById('ak_kptn_number').value = pendingKptn || 'KPTN-';
    const depositInput = document.getElementById('ak_deposit_amount');
    if (depositInput) {
        const parsedDeposit = parseFloat(String(depositAmount).replace(/,/g, '')) || 0;
        depositInput.value = parsedDeposit > 0
            ? parsedDeposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '';
    }

    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');
    const errMsg = document.getElementById('ak_error_msg');
    const btn = document.getElementById('btnSubmitKptn');

    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.textContent = 'Choose file or drag it here';
    if (errMsg) { errMsg.textContent = ''; errMsg.classList.add('hidden'); }
    if (btn) { btn.innerText = 'Save'; btn.disabled = false; }

    const modal = document.getElementById('attachKptnModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openVoidConfirmationModal() {
    closeModal('viewBorrowerModal'); 
    document.getElementById('cvm_borrower_name').innerText = currentVoidName;
    document.getElementById('cvm_employe_id').value = currentVoidId;
    document.getElementById('cvm_borrower_name_input').value = currentVoidName;
    document.getElementById('cvm_reason').value = ""; 

    const modal = document.getElementById('customVoidModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function openInactivateConfirmationModal() {
    closeModal('viewBorrowerModal');
    const nameEl = document.getElementById('ivm_borrower_name');
    if (nameEl) nameEl.innerText = (currentInactivateName || 'Borrower Name');
    const modal = document.getElementById('inactivateModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

async function confirmInactivateBorrower() {
    const loanId = currentInactivateLoanId || null;
    if (!loanId) {
        alert('Missing loan information.');
        return;
    }
    const reasonEl = document.getElementById('ivm_reason');
    const reason = reasonEl ? reasonEl.value : '';
    if (!reason) { alert('Please select a reason for inactivation.'); return; }

    const btn = document.getElementById('btnConfirmInactivate');
    if (btn) { btn.disabled = true; btn.innerText = 'Processing...'; }
    try {
        const res = await fetch(`${BASE_URL}/public/api/inactivate_borrower.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ loan_id: loanId, reason: reason })
        });
        const result = await res.json();
        if (result.success) {
            closeModal('inactivateModal');
            openSuccessModal('Borrower inactivated successfully.');
        } else {
            alert(result.error || 'Failed to inactivate borrower.');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Confirm Inactivate'; }
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function openSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const msgEl = document.getElementById('successModalMessage');
    if (msgEl) msgEl.innerText = message || 'Success.';
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function handleSuccessOk() {
    ['successModal', 'inactivateModal', 'viewBorrowerModal', 'customVoidModal', 'attachKptnModal'].forEach(id => closeModal(id));
    fetchBorrowersPage(currentPage || 1);
}

function setupAddModalLogic() {
    const kptnToggle = document.getElementById('requiresKptnToggle');
    const kptnContainer = document.getElementById('kptnFieldsContainer');
    const toggleLabelText = document.getElementById('toggleLabelText');
    const depositAmountInput = document.getElementById('deposit_amount_input');
    const kptnNumberInput = document.getElementById('kptn_number_input');
    const kptnReceiptInput = document.getElementById('kptn_receipt_input');

    if (kptnToggle) {
        kptnToggle.addEventListener('change', function() {
            if (this.checked) {
                kptnContainer.style.display = 'grid'; 
                depositAmountInput.setAttribute('required', 'required');
                kptnNumberInput.setAttribute('required', 'required');
                kptnReceiptInput.setAttribute('required', 'required');
                if (toggleLabelText) {
                    toggleLabelText.textContent = "Security Deposit";
                    toggleLabelText.classList.replace('text-slate-400', 'text-slate-800');
                }
                this.value = "true";
            } else {
                kptnContainer.style.display = 'none';
                depositAmountInput.removeAttribute('required');
                kptnNumberInput.removeAttribute('required');
                kptnReceiptInput.removeAttribute('required');
                kptnNumberInput.value = '';
                kptnReceiptInput.value = ''; 
                if (toggleLabelText) {
                    toggleLabelText.textContent = "Security Deposit";
                    toggleLabelText.classList.replace('text-slate-800', 'text-slate-400');
                }
                this.value = "false";
            }
        });

        kptnContainer.style.display = 'none';
        depositAmountInput.removeAttribute('required');
        kptnNumberInput.removeAttribute('required');
        kptnReceiptInput.removeAttribute('required');
        if (toggleLabelText) {
            toggleLabelText.classList.replace('text-slate-800', 'text-slate-400');
        }
        kptnToggle.value = 'false';
    }
}

function openAddModal() {
    const modal = document.getElementById('addBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('addBorrowerForm').reset();
    
    document.getElementById('division_container').classList.add('hidden');
    document.getElementById('branch_container').classList.add('hidden');
    
    const idField = document.getElementById('employe_id');
    idField.value = "Fetching...";
    
    fetch(`${BASE_URL}/public/api/get_next_id.php`)
        .then(res => res.json())
        .then(data => {
            idField.value = data.success ? data.next_id : "Error";
        })
        .catch(() => { idField.value = "Error"; });

    if (!masterLocationsFetched) {
        fetch(`${BASE_URL}/public/api/get_master_locations.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setupCustomSearchable('region_search_input', 'region_results', data.data.regions, function(selectedRegion) {
                        handleRegionSelection(selectedRegion);
                    });
                    
                    setupCustomSearchable('division_search_input', 'division_results', data.data.divisions, function(selectedDivision) {
                        const codeInput = document.getElementById('division_code_input');
                        if(codeInput) {
                            codeInput.value = selectedDivision.value || selectedDivision.id || selectedDivision.division_code || selectedDivision;
                        }
                    });
                    
                    masterLocationsFetched = true;
                }
            })
            .catch(err => console.error("Could not fetch master locations", err));
    }
}

function handleRegionSelection(regionObj) {
    const regionName = regionObj.label.toUpperCase();
    const regionCode = regionObj.value;
    
    document.getElementById('region_code_input').value = regionCode;

    const divContainer = document.getElementById('division_container');
    const branchContainer = document.getElementById('branch_container');
    const divInput = document.getElementById('division_search_input');
    const branchInput = document.getElementById('branch_search_input');
    const branchIdInput = document.getElementById('branch_id_input'); 
    const divIdInput = document.getElementById('division_code_input'); 

    if (regionName.startsWith('HO') || regionName.includes('HEAD OFFICE')) {
        divContainer.classList.remove('hidden');
        branchContainer.classList.add('hidden');
        divInput.required = true;
        branchInput.required = false;
        branchInput.value = 'N/A'; 
        branchIdInput.value = 'N/A'; 
        divInput.value = '';
        if(divIdInput) divIdInput.value = ''; 
    } else {
        divContainer.classList.add('hidden');
        branchContainer.classList.remove('hidden');
        divInput.required = false;
        branchInput.required = true;
        divInput.value = 'N/A'; 
        branchInput.value = '';
        branchIdInput.value = ''; 
        if(divIdInput) divIdInput.value = 'N/A'; 
        branchInput.placeholder = 'LOADING BRANCHES...';
        
        fetch(`${BASE_URL}/public/api/get_branches.php?region_code=${encodeURIComponent(regionCode)}`)
            .then(res => res.json())
            .then(data => {
                branchInput.placeholder = 'SELECT BRANCH...';
                if (data.success) {
                    setupCustomSearchable('branch_search_input', 'branch_results', data.data, function(selectedBranch) {
                        branchIdInput.value = selectedBranch.value || selectedBranch.branch_id || selectedBranch.id || selectedBranch;
                    });
                }
            });
    }
}

function validateAndShowSchedule() {
    const form = document.getElementById('addBorrowerForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const formData = new FormData(form);
    tempBorrowerData = Object.fromEntries(formData.entries());

    const kptnToggle = document.getElementById('requiresKptnToggle');
    if (kptnToggle) {
        tempBorrowerData['requires_kptn'] = kptnToggle.checked ? 'true' : 'false';
    }

    setSchedField('sched-name', (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase());
    setSchedField('sched-contact', tempBorrowerData.contact_number);
    setSchedField('sched-amount', parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
    setSchedField('sched-date', formatFullDate(tempBorrowerData.loan_granted));
    setSchedField('sched-terms', tempBorrowerData.terms + ' Months');

    setSchedField('sched-pn', "Generating PN...");
    setSchedField('sched-maturity', "Calculating..."); 
    setSchedField('sched-deduct', "Calculating...");
    const amortRowsEl = document.getElementById('amortization-rows') || document.getElementById('modal-ledger-rows');
    if (amortRowsEl) amortRowsEl.innerHTML = '<tr><td colspan="7" class="p-4 text-center text-slate-500 italic">Calculating Schedule...</td></tr>';

    const empIdEl = document.getElementById('modal-ledger-id');
    if (empIdEl) empIdEl.innerText = tempBorrowerData.employe_id || tempBorrowerData.employeId || '---';
    const refEl = document.getElementById('modal-ledger-ref');
    if (refEl) refEl.innerText = tempBorrowerData.reference_number || tempBorrowerData.reference_no || tempBorrowerData.reference || '---';
    const regionEl = document.getElementById('modal-ledger-region');
    if (regionEl) regionEl.innerText = tempBorrowerData.region_name || tempBorrowerData.region || (tempBorrowerData.region_code || '').toUpperCase() || '--';
    const branchEl = document.getElementById('modal-ledger-branch');
    if (branchEl) branchEl.innerText = tempBorrowerData.branch_name || tempBorrowerData.branch || 'N/A';

    closeModal('addBorrowerModal');
    const schedModal = document.getElementById('amortizationModal');
    schedModal.classList.remove('hidden');
    schedModal.classList.add('flex');

    fetchAmortizationSchedule(tempBorrowerData);
}

function fetchAmortizationSchedule(data) {
    const payload = {
        loan_amount: data.loan_amount,
        terms: data.terms,
        date_granted: data.loan_granted
    };

    fetch(`${BASE_URL}/public/api/calculate_amortization.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            setSchedField('sched-pn', result.pn_number);
            setSchedField('sched-deduct', parseFloat(result.deduction).toLocaleString('en-US', {minimumFractionDigits: 2}));
            setSchedField('sched-rate', result.add_on_rate + ' % (Add-on)');
            setSchedField('sched-initial-bal', parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
            setSchedField('sched-maturity', formatFullDate(result.maturity_date));

            renderAmortizationTable(result.schedule);

            try {
                const rows = result.schedule || [];
                let sumTotalPrincipal = 0;
                let sumTotalInterest = 0;
                let totalPrincipalPaid = 0; 
                let totalInterestPaid = 0;
                let totalCollected = 0;

                rows.forEach(r => {
                    const p = parseFloat(r.principal) || 0;
                    const i = parseFloat(r.interest) || 0;
                    sumTotalPrincipal += p;
                    sumTotalInterest += i;
                });

                const loanAmount = parseFloat(tempBorrowerData.loan_amount) || 0;
                const addOnRateDecimal = parseFloat(result.add_on_rate_decimal) || 0;
                const termMonths = parseInt(tempBorrowerData.terms) || parseInt(tempBorrowerData.term_months) || 0;

                const monthlyRatePercent = Number((addOnRateDecimal * 100).toFixed(2));
                setSchedField('sched-rate', monthlyRatePercent + ' %');

                const semiAmort = parseFloat(result.deduction) || 0;
                const monthlyAmort = semiAmort * 2;

                const setMoney = (id, num) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.innerText = '₱ ' + (isNaN(num) ? '0.00' : num.toLocaleString(undefined, {minimumFractionDigits:2}));
                };

                const grossPrincipal = loanAmount;
                const grossInterest = (typeof result.total_interest !== 'undefined' && !isNaN(parseFloat(result.total_interest)))
                    ? parseFloat(result.total_interest)
                    : (loanAmount * addOnRateDecimal * termMonths);
                const grossTotal = (typeof result.gross_amount !== 'undefined' && !isNaN(parseFloat(result.gross_amount)))
                    ? parseFloat(result.gross_amount)
                    : (grossPrincipal + grossInterest);

                setMoney('modal-ledger-gross-principal', grossPrincipal);
                setMoney('modal-ledger-gross-interest', grossInterest);
                setMoney('modal-ledger-gross-total', grossTotal);

                const principalBalance = sumTotalPrincipal - totalPrincipalPaid;
                const interestBalance = ((typeof result.total_interest !== 'undefined' && !isNaN(parseFloat(result.total_interest)))
                    ? parseFloat(result.total_interest)
                    : sumTotalInterest) - totalInterestPaid;
                const totalOutstanding = principalBalance + interestBalance;

                setMoney('modal-ledger-principal-paid', totalPrincipalPaid);
                setMoney('modal-ledger-principal-balance', principalBalance);
                setMoney('modal-ledger-interest-paid', totalInterestPaid);
                setMoney('modal-ledger-interest-balance', interestBalance);
                setMoney('modal-ledger-total-payment', totalPrincipalPaid + totalInterestPaid);
                setMoney('modal-ledger-total-balance', totalOutstanding);

                setMoney('modal-ledger-amort', semiAmort);
                setMoney('modal-ledger-monthly-amort', monthlyAmort);

                const requiresKptn = tempBorrowerData.requires_kptn === 'true' || tempBorrowerData.requires_kptn === true;
                const depositAmount = requiresKptn ? (parseFloat((tempBorrowerData.deposit_amount || '').toString().replace(/,/g, '')) || 0) : 0;
                setMoney('modal-ledger-security-deposit', depositAmount);
                const depositWrapper = document.getElementById('security-deposit-wrapper');
                if (depositWrapper) depositWrapper.style.display = depositAmount > 0 ? 'flex' : 'none';

            } catch (e) {
                console.error('Error computing ledger totals', e);
            }

            tempBorrowerData.pn_number = result.pn_number;
            tempBorrowerData.pn_maturity = result.maturity_date;
            tempBorrowerData.deduction = result.deduction;
            tempBorrowerData.schedule = result.schedule;
            tempBorrowerData.periodic_rate = result.periodic_rate; 
        } else {
            showImportError("Calculation Error: " + result.error);
            closeModal('amortizationModal');
            openAddModal(); 
        }
    })
    .catch(err => {
        console.error(err);
        showImportError("System Error calling API");
        closeModal('amortizationModal');
        openAddModal();
    });
}

function formatFullDate(dateStr) {
    if (!dateStr) return '';
    const dt = new Date(dateStr);
    if (isNaN(dt)) return dateStr;
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amortization-rows') || document.getElementById('modal-ledger-rows');
    if (!tbody) return;
    tbody.innerHTML = ''; 
    rows.forEach(row => {
        const principalAmt = parseFloat(row.principal) || 0;
        const interestAmt = parseFloat(row.interest) || 0;
        const totalAmt = parseFloat(row.total) || (principalAmt + interestAmt);
        const balAmt = parseFloat(row.balance) || 0;
        const status = (row.status || row.status_code || 'UNPAID').toString().toUpperCase();
        const remarksText = row.remarks || '';

        const tr = document.createElement('tr');
        tr.className = `hover:bg-slate-100 border-b border-slate-100 transition-colors`;
        tr.innerHTML = `
            <td class="w-[16%] px-8 py-0 text-center text-slate-600 border-r border-slate-50 font-medium font-mono">${formatFullDate(row.date)}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 pr-2">${principalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 pr-2">${interestAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right text-slate-600 border-r border-slate-50 font-medium pr-4">${totalAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[14%] px-3 py-0 text-right border-r border-slate-50 text-slate-600 pr-4">${balAmt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td class="w-[10%] px-3 py-0 text-center text-slate-600">${status}</td>
            <td class="px-3 py-0 text-slate-600 text-left truncate" title="${remarksText}">${remarksText}</td>
        `;
        tbody.appendChild(tr);
    });
}

function submitFinalBorrower() {
    const formData = new FormData();
    for (const key in tempBorrowerData) {
        if (tempBorrowerData[key] instanceof File) {
            formData.append(key, tempBorrowerData[key]);
        } else if (typeof tempBorrowerData[key] === 'object' && tempBorrowerData[key] !== null) {
            formData.append(key, JSON.stringify(tempBorrowerData[key]));
        } else {
            formData.append(key, tempBorrowerData[key]);
        }
    }

    fetch(`${BASE_URL}/public/actions/create_borrower.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.warning) alert("Loan Saved, BUT: " + data.warning);
            location.reload();
        } else {
            closeModal('amortizationModal');
            showImportError((data.error || "Unknown error occurred").replace(/\n/g, '<br>'));
        }
    })
    .catch(err => {
        console.error(err);
        closeModal('amortizationModal');
        showImportError("System Error: Check console for details.");
    });
}

function setupCustomSearchable(inputId, resultsId, dataArray, onSelectCallback = null) {
    const input = document.getElementById(inputId);
    const results = document.getElementById(resultsId);

    if (!input || !results) return;

    input.searchData = dataArray;
    input.onSelectCallback = onSelectCallback;

    if (input.dataset.searchInitialized === "true") return; 
    input.dataset.searchInitialized = "true";

    input.addEventListener('click', function() {
        if (this.value === '') renderList(this);
    });

    input.addEventListener('input', function() {
        const val = this.value.toUpperCase();
        const filtered = this.searchData.filter(item => {
            let text = typeof item === 'object' ? item.label : item;
            return text && text.toUpperCase().includes(val);
        });
        renderList(this, filtered);
    });

    function renderList(targetInput, listToRender = null) {
        const dataToUse = listToRender || targetInput.searchData;
        results.innerHTML = '';
        
        if (dataToUse.length > 0) {
            results.classList.remove('hidden');
            dataToUse.forEach(item => {
                let text = typeof item === 'object' ? item.label : item;
                const div = document.createElement('div');
                div.className = "px-3 py-2 text-[12px] cursor-pointer hover:bg-slate-100 border-b border-slate-50 last:border-none uppercase text-slate-700 transition-colors";
                div.innerText = text;
                div.onclick = function(e) {
                    e.stopPropagation(); 
                    targetInput.value = text;
                    results.classList.add('hidden');
                    if (targetInput.onSelectCallback) targetInput.onSelectCallback(item);
                };
                results.appendChild(div);
            });
        } else {
            results.classList.add('hidden');
        }
    }

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('hidden');
        }
    });
}

function setSchedField(primaryId, value) {
    const el = document.getElementById(primaryId);
    if (el) { el.innerText = value; return; }
    const map = {
        'sched-name': 'modal-ledger-name',
        'sched-contact': 'modal-ledger-contact',
        'sched-amount': 'modal-ledger-principal',
        'sched-date': 'modal-ledger-pndate',
        'sched-terms': 'modal-ledger-terms',
        'sched-pn': 'modal-ledger-pn',
        'sched-maturity': 'modal-ledger-maturity',
        'sched-rate': 'modal-ledger-rate',
        'sched-deduct': 'modal-ledger-amort',
        'sched-initial-bal': 'modal-ledger-principal'
    };
    const alt = map[primaryId];
    if (alt) {
        const altEl = document.getElementById(alt);
        if (!altEl) return;

        const currencyFields = ['modal-ledger-principal', 'modal-ledger-amort', 'modal-ledger-gross-principal', 'modal-ledger-gross-interest', 'modal-ledger-gross-total', 'modal-ledger-monthly-amort', 'modal-ledger-security-deposit'];
        if (currencyFields.indexOf(alt) !== -1) {
            const cleaned = String(value || '').replace(/[^0-9.-]/g, '');
            const num = parseFloat(cleaned);
            if (!isNaN(num)) {
                altEl.innerText = '₱ ' + num.toLocaleString(undefined, {minimumFractionDigits:2});
            } else {
                altEl.innerText = value;
            }
        } else {
            altEl.innerText = value;
        }
    }
}