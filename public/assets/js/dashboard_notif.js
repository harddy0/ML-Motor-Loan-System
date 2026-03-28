// ==========================================
// DASHBOARD NOTIFICATIONS: Alerts & Modals
// ==========================================

let activeModalNotifId   = null;
let activeModalNotifType = null;
let lastProcessedId      = null;

document.addEventListener('DOMContentLoaded', function () {
    loadNotifications();
});

// ── Attach KPTN Injection ──
function ensureAttachModalLoaded(callback) {
    if (document.getElementById('attachKptnModal')) {
        return callback && callback();
    }

    fetch(`${BASE_URL}/public/borrowers/index.php`)
        .then(res => res.text())
        .then(html => {
            const start = html.indexOf('<div id="attachKptnModal"');
            if (start === -1) return callback && callback();

            const scriptIdx = html.indexOf('<script', start);
            let modalHtml = '';
            let scriptContent = '';

            if (scriptIdx !== -1) {
                modalHtml = html.substring(start, scriptIdx);
                const scriptEnd = html.indexOf('</script>', scriptIdx);
                if (scriptEnd !== -1) {
                    scriptContent = html.substring(scriptIdx, scriptEnd + 9);
                }
            } else {
                const endDiv = html.indexOf('</div>', start);
                modalHtml = html.substring(start, endDiv + 6);
            }

            const container = document.createElement('div');
            container.innerHTML = modalHtml;
            document.body.appendChild(container);

            if (scriptContent) {
                const innerStart = scriptContent.indexOf('>') + 1;
                const innerEnd   = scriptContent.lastIndexOf('</script>');
                const js = scriptContent.substring(innerStart, innerEnd);
                const s  = document.createElement('script');
                s.textContent = js;
                document.body.appendChild(s);
            }

            if (typeof window.closeModal !== 'function') {
                window.closeModal = function (id) {
                    const modal = document.getElementById(id);
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        if (id === 'attachKptnModal') resetAttachModal();
                    }
                };
            }

            const attachForm = document.getElementById('attachKptnForm');
            if (attachForm && !attachForm._dashboardHandlerAttached) {
                attachForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const btn          = document.getElementById('btnSubmitKptn');
                    const originalText = btn ? btn.innerText : 'Save';
                    if (btn) { btn.innerText = 'Activating...'; btn.disabled = true; }

                    const formData    = new FormData();
                    const loanIdField = document.getElementById('ak_loan_id');
                    const kptnField   = document.getElementById('ak_kptn_number');
                    const fileInput   = document.getElementById('ak_kptn_receipt');

                    if (!loanIdField || !loanIdField.value) {
                        alert('Missing loan ID.');
                        if (btn) { btn.innerText = originalText; btn.disabled = false; }
                        return;
                    }

                    formData.append('loan_id',     loanIdField.value);
                    formData.append('kptn_number', kptnField ? kptnField.textContent.trim() : '');
                    if (fileInput && fileInput.files[0]) {
                        formData.append('kptn_receipt', fileInput.files[0]);
                    }

                    fetch(`${BASE_URL}/public/actions/attach_kptn.php`, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(async (data) => {
                            if (data.success) {
                                if (activeModalNotifId) {
                                    const readData = new FormData();
                                    readData.append('notification_id', activeModalNotifId);
                                    await fetch(`${BASE_URL}/public/api/mark_notification_read.php`, { method: 'POST', body: readData });
                                    activeModalNotifId = null;
                                }
                                closeModal('attachKptnModal');
                                loadNotifications();
                                if (typeof loadDashboard    === 'function') loadDashboard();
                                if (typeof loadLoanProgress === 'function') loadLoanProgress();
                                const successAlert = document.getElementById('successAlertModal');
                                if (successAlert) successAlert.classList.replace('hidden', 'flex');
                            } else {
                                alert('Error: ' + data.error);
                            }
                        })
                        .catch(err => console.error(err))
                        .finally(() => { if (btn) { btn.innerText = originalText; btn.disabled = false; } });
                });
                attachForm._dashboardHandlerAttached = true;
            }

            callback && callback();
        })
        .catch(err => {
            console.error('Failed to load attach modal', err);
            callback && callback();
        });
}

function resetAttachModal() {
    const loanIdField   = document.getElementById('ak_loan_id');
    const kptnField     = document.getElementById('ak_kptn_number');
    const borrowerLabel = document.getElementById('ak_borrower_name');
    const errorMsg      = document.getElementById('ak_error_msg');

    if (loanIdField)   loanIdField.value       = '';
    if (kptnField)     kptnField.textContent    = '';
    if (borrowerLabel) borrowerLabel.innerText  = '...';
    if (errorMsg)      { errorMsg.innerText = ''; errorMsg.classList.add('hidden'); }

    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');
    if (fileInput) fileInput.value = '';
    if (fileLabel) fileLabel.textContent = 'Choose file or drag it here';

    const btn = document.getElementById('btnSubmitKptn');
    if (btn) { btn.innerText = 'Save'; btn.disabled = false; }
}

window.openAttachFromDashboard = function (encodedNotif) {
    if (typeof resetAttachModal === 'function') resetAttachModal();
    try {
        const n = JSON.parse(decodeURIComponent(encodedNotif));
        activeModalNotifId = n.notification_id;

        ensureAttachModalLoaded(() => {
            const loanId = n.loan_id || n.loanId || n.id || '';
            if (!loanId) { console.error('No loan_id in notification'); return; }

            fetch(`${BASE_URL}/public/api/get_loan_details.php?loan_id=${encodeURIComponent(loanId)}`)
                .then(res => res.json())
                .then(resp => {
                    if (!resp.success) console.warn('Could not fetch loan details', resp.error);
                    const data = resp.success ? resp.data : n;

                    const loanIdField   = document.getElementById('ak_loan_id');
                    const borrowerLabel = document.getElementById('ak_borrower_name');
                    const kptnField     = document.getElementById('ak_kptn_number');

                    if (loanIdField) loanIdField.value = data.loan_id || loanId;
                    const nameVal = data.first_name
                        ? (data.first_name + ' ' + (data.last_name || ''))
                        : (n.first_name ? (n.first_name + ' ' + (n.last_name || '')) : n.name || '');
                    if (borrowerLabel) borrowerLabel.innerText  = nameVal.toUpperCase();
                    if (kptnField)     kptnField.textContent    = data.pending_kptn || n.pending_kptn || '';

                    const modal = document.getElementById('attachKptnModal');
                    if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
                })
                .catch(err => console.error('Error fetching loan details', err));
        });
    } catch (e) {
        console.error('openAttachFromDashboard error', e);
    }
};

// ── Notifications Data & UI ──
async function loadNotifications() {
    const unreadList = document.getElementById('notifUnreadList');
    const readList   = document.getElementById('notifReadList');
    if (!unreadList) return;

    try {
        const response = await fetch(`${BASE_URL}/public/api/get_notifications.php`);
        const result   = await response.json();

        if (result.success) {
            const { unread, read, unread_count } = result.data;

            const badge = document.getElementById('notifBadge');
            if (unread_count > 0) {
                badge.innerText = `${unread_count} NEW`;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }

            renderNotifList('unread', unread, unreadList);
            renderNotifList('read',   read,   readList);
        }
    } catch (error) {
        console.error('Failed to load notifications', error);
        unreadList.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Failed to load data.</p>';
    }
}

function renderNotifList(type, list, container) {
    if (list.length === 0) {
        container.innerHTML = `<p class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No ${type} notifications.</p>`;
        return;
    }

    const sorted = [...list].sort((a, b) => {
        if (String(a.notification_id) === String(lastProcessedId)) return -1;
        if (String(b.notification_id) === String(lastProcessedId)) return  1;

        const aSticky = (a.type === 'PENDING_KPTN' && !parseInt(a.is_read));
        const bSticky = (b.type === 'PENDING_KPTN' && !parseInt(b.is_read));
        if (aSticky && !bSticky) return -1;
        if (!aSticky && bSticky) return  1;

        return new Date(b.created_at) - new Date(a.created_at);
    });

    container.innerHTML = '';
    sorted.forEach(n => {
        const notifJson    = encodeURIComponent(JSON.stringify(n));
        const opacity      = type === 'read' ? 'opacity-60 bg-slate-100' : 'bg-white shadow-sm border-slate-200';
        const uploaderName = (n.uploader_first || n.uploader_last)
            ? `${n.uploader_first || ''} ${n.uploader_last || ''}`.trim()
            : 'System/Unknown';
        const borrowerName = (n.first_name || n.last_name)
            ? `${n.first_name || ''} ${n.last_name || ''}`.trim()
            : (n.message || '');
        const createdDate = new Date(n.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        let html = '';

        if (n.type === 'PENDING_KPTN') {
            if (type === 'unread') {
                html = `
                    <div class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="flex-1 pr-3">
                                <div class="text-[8px] font-bold text-[#ce1126] uppercase tracking-wider">New Loan Added</div>
                                <div class="text-[8px] font-bold text-[#ce1126] uppercase tracking-wider">KPTN Form is Missing</div>
                                <p class="text-[14px] uppercase text-slate-700 font-medium mb-1 leading-snug">${borrowerName}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[10px] text-slate-400 uppercase font-bold">By: ${uploaderName}</p>
                                <p class="text-[11px] text-slate-400 font-bold">${createdDate}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <button onclick="openNotifModal('${notifJson}', '${type}')" class="inline-block bg-[#ce1126] hover:bg-[#dc2626] text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
                                VIEW DETAILS
                            </button>
                            <button onclick="event.stopPropagation(); openAttachFromDashboard('${notifJson}')" class="inline-block bg-[#ce1126] hover:bg-[#dc2626] text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
                                ATTACH KPTN FORM
                            </button>
                        </div>
                    </div>
                `;
            } else {
                html = `
                    <div class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="flex-1 pr-3">
                                <div class="text-[8px] font-bold text-[#ce1126] uppercase tracking-wider">New Loan Added</div>
                                <div class="text-[8px] font-bold text-green-600 uppercase tracking-wider">KPTN form Attached</div>
                                <p class="text-[14px] uppercase text-slate-700 font-medium mb-1 leading-snug">${borrowerName}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[10px] text-slate-400 uppercase font-bold">By: ${uploaderName}</p>
                                <p class="text-[11px] text-slate-400 font-bold">${createdDate}</p>
                            </div>
                        </div>
                        <div class="flex justify-start items-center mt-2">
                            <button onclick="openNotifModal('${notifJson}', '${type}')" class="inline-block bg-[#ce1126] hover:bg-[#dc2626] text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
                                VIEW DETAILS
                            </button>
                        </div>
                    </div>
                `;
            }
        } else {
            html = `
                <div class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 pr-3">
                            <p class="text-[9px] font-bold text-[#ce1126] uppercase tracking-wider">New ${n.type ? n.type.replace('_', ' ') : 'System Update'}</p>
                            <p class="text-[14px] text-slate-700 uppercase font-medium leading-snug">${borrowerName}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] text-slate-400 uppercase font-bold">By: ${uploaderName}</p>
                            <p class="text-[11px] text-slate-400 font-bold">${createdDate}</p>
                        </div>
                    </div>
                    <div class="flex justify-start items-center mt-2">
                        <button onclick="openNotifModal('${notifJson}', '${type}')" class="inline-block bg-[#ce1126] hover:bg-[#dc2626] text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
                            VIEW DETAILS
                        </button>
                    </div>
                </div>
            `;
        }

        container.insertAdjacentHTML('beforeend', html);
    });
}

window.switchNotifTab = function(tab) {
    const btnUnread  = document.getElementById('tabBtnUnread');
    const btnRead    = document.getElementById('tabBtnRead');
    const listUnread = document.getElementById('notifUnreadList');
    const listRead   = document.getElementById('notifReadList');

    if (tab === 'unread') {
        btnUnread.className = 'flex-1 py-3 text-xs font-bold text-[#dc2626] border-b-2 border-[#dc2626] transition-colors bg-white';
        btnRead.className   = 'flex-1 py-3 text-xs font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors bg-slate-50';
        listUnread.classList.remove('hidden');
        listRead.classList.add('hidden');
    } else {
        btnRead.className   = 'flex-1 py-3 text-xs font-bold text-[#dc2626] border-b-2 border-[#dc2626] transition-colors bg-white';
        btnUnread.className = 'flex-1 py-3 text-xs font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors bg-slate-50';
        listRead.classList.remove('hidden');
        listUnread.classList.add('hidden');
    }
}

// ── Notification Ledger Pop-up Data Handlers ──
function formatDashboardDisplayDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    let d = new Date(String(dateStr) + 'T00:00:00');
    if (isNaN(d.getTime())) d = new Date(dateStr);
    if (!isNaN(d.getTime())) return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    return dateStr;
}

function fetchBorrowerDetailsFromBorrowersApi(payload) {
    const searchKey = payload.employe_id || payload.employee_id || payload.id || payload.name || '';
    if (!searchKey) return Promise.resolve(null);
    return fetch(`${BASE_URL}/public/api/get_paginated_borrowers.php?page=1&limit=50&search=${encodeURIComponent(searchKey)}`)
        .then(r => r.json())
        .then(result => {
            if (!result.success || !result.payload || !Array.isArray(result.payload.data)) return null;
            const rows = result.payload.data;
            const wantedLoanId = String(payload.loan_id || payload.loanId || '');
            const exact = rows.find(row => (wantedLoanId && String(row.loan_id || '') === wantedLoanId));
            return exact || rows[0] || null;
        }).catch(() => null);
}

function fetchBorrowerDetailsFromLedgerApi(payload) {
    const searchKey = payload.pn_number || payload.pn_no || payload.employe_id || payload.employee_id || payload.name || '';
    if (!searchKey) return Promise.resolve(null);
    return fetch(`${BASE_URL}/public/api/get_paginated_ledger.php?page=1&limit=50&search=${encodeURIComponent(searchKey)}`)
        .then(r => r.json())
        .then(result => {
            if (!result.success || !result.payload || !Array.isArray(result.payload.data)) return null;
            const rows = result.payload.data;
            const wantedLoanId = String(payload.loan_id || payload.loanId || '');
            const exact = rows.find(row => (wantedLoanId && String(row.loan_id || '') === wantedLoanId));
            return exact || rows[0] || null;
        }).catch(() => null);
}

function fetchDashboardLedgerData(loanId) {
    return fetch(`${BASE_URL}/public/api/get_ledger_transactions.php?loan_id=${encodeURIComponent(loanId)}`)
        .then(r => r.json())
        .then(result => {
            if (result.success) return result.data;
            throw new Error(result.error || 'Failed to fetch ledger transactions.');
        });
}

function populateDashboardLedgerFields(borrowerData, fallbackData = {}) {
    const setText = (id, text) => { const el = document.getElementById(id); if (el) el.innerText = text; };

    const borrowerLabel = borrowerData.name
        || ((borrowerData.first_name || fallbackData.first_name)
            ? `${borrowerData.first_name || fallbackData.first_name || ''} ${borrowerData.last_name || fallbackData.last_name || ''}`.trim()
            : '')
        || fallbackData.name || 'N/A';

    setText('modal-ledger-name',    borrowerLabel);
    setText('modal-ledger-id',      borrowerData.employe_id  || borrowerData.employee_id  || fallbackData.id  || 'N/A');
    setText('modal-ledger-pn',      borrowerData.pn_number   || borrowerData.pn_no        || fallbackData.pn_number || '--');
    setText('modal-ledger-pndate',  formatDashboardDisplayDate(borrowerData.g_date || borrowerData.date_granted || fallbackData.date_granted));
    setText('modal-ledger-maturity',formatDashboardDisplayDate(borrowerData.maturity_date || fallbackData.maturity_date));

    const termsValue = borrowerData.term_months || borrowerData.terms || fallbackData.term_months || fallbackData.terms;
    setText('modal-ledger-terms',   termsValue ? (termsValue + ' Months') : '--');
    setText('modal-ledger-ref',     borrowerData.loan_ref_no || borrowerData.reference_number || '--');
    setText('modal-ledger-region',  borrowerData.region  || fallbackData.region  || '--');

    const branchRaw = borrowerData.branch || fallbackData.branch || '--';
    setText('modal-ledger-branch',  String(branchRaw).trim().toUpperCase() === 'N/A' ? '' : branchRaw);
    setText('modal-ledger-contact', borrowerData.contact_number || borrowerData.contact || '--');

    const principal        = parseFloat(borrowerData.loan_amount      || fallbackData.loan_amount)      || 0;
    const semiAmort        = parseFloat(borrowerData.semi_monthly_amt  || borrowerData.deduction || fallbackData.semi_monthly_amt) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate       || fallbackData.add_on_rate)      || 0;
    const termMonths       = parseInt(borrowerData.term_months         || borrowerData.terms || fallbackData.terms) || 0;

    setText('modal-ledger-rate',          (addOnRateDecimal * termMonths * 100).toFixed(0) + '%');
    setText('modal-ledger-principal',     '₱ ' + principal.toLocaleString(undefined, { minimumFractionDigits: 2 }));
    setText('modal-ledger-amort',         '₱ ' + semiAmort.toLocaleString(undefined, { minimumFractionDigits: 2 }));
    setText('modal-ledger-monthly-amort', '₱ ' + (semiAmort * 2).toLocaleString(undefined, { minimumFractionDigits: 2 }));

    const depositAmount = parseFloat(borrowerData.deposit_amount || fallbackData.deposit_amount) || 0;
    setText('modal-ledger-security-deposit', '₱ ' + depositAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }));
}

function populateDashboardLedgerSummary(transactions, borrowerData) {
    let totalPrincipalPaid = 0, totalInterestPaid = 0, sumTotalPrincipal = 0, sumTotalInterest = 0;

    transactions.forEach(txn => {
        const principalAmt = parseFloat(txn.principal_amt || txn.principal) || 0;
        const interestAmt  = parseFloat(txn.interest_amt  || txn.interest)  || 0;
        sumTotalPrincipal += principalAmt; sumTotalInterest  += interestAmt;
        if ((txn.status || '').toUpperCase() === 'PAID') {
            totalPrincipalPaid += principalAmt; totalInterestPaid  += interestAmt;
        }
    });

    const setMoney = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = '₱ ' + val.toLocaleString(undefined, { minimumFractionDigits: 2 }); };

    const loanAmount       = parseFloat(borrowerData.loan_amount)  || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate)  || 0;
    const termMonths       = parseInt(borrowerData.term_months)    || 0;
    const grossInterest    = loanAmount * addOnRateDecimal * termMonths;

    setMoney('modal-ledger-gross-principal', loanAmount);
    setMoney('modal-ledger-gross-interest',  grossInterest);
    setMoney('modal-ledger-gross-total',     loanAmount + grossInterest);
    setMoney('modal-ledger-principal-paid',  totalPrincipalPaid);
    setMoney('modal-ledger-interest-paid',   totalInterestPaid);
    setMoney('modal-ledger-total-payment',   totalPrincipalPaid + totalInterestPaid);
    setMoney('modal-ledger-principal-balance', sumTotalPrincipal - totalPrincipalPaid);
    setMoney('modal-ledger-interest-balance',  sumTotalInterest  - totalInterestPaid);
    setMoney('modal-ledger-total-balance',     (sumTotalPrincipal - totalPrincipalPaid) + (sumTotalInterest - totalInterestPaid));
}

window.openNotifModal = function(encodedData, type) {
    const data = JSON.parse(decodeURIComponent(encodedData));
    activeModalNotifId   = (type === 'unread') ? data.notification_id : null;
    activeModalNotifType = (type === 'unread') ? data.type            : null;

    const uploaderName = (data.uploader_first || data.uploader_last) ? `${data.uploader_first || ''} ${data.uploader_last || ''}`.trim() : 'System / Unknown';
    const safeSet = (id, value) => { const el = document.getElementById(id); if (el) el.innerText = value; };

    populateDashboardLedgerFields(data, data);
    safeSet('notif-uploaded-by', uploaderName);

    const loanId = data.loan_id || data.loanId || data.id;
    if (loanId) {
        const detailsUrl = `${BASE_URL}/public/api/get_loan_details.php?loan_id=${encodeURIComponent(loanId)}`;

        Promise.all([
            fetch(detailsUrl).then(r => r.json()).then(resp => (resp && resp.success && resp.data) ? resp.data : null).catch(() => null),
            fetchBorrowerDetailsFromBorrowersApi(data),
            fetchBorrowerDetailsFromLedgerApi(data),
        ]).then(([loanDetails, borrowerFallback, ledgerFallback]) => {
            const borrowerData = { ...(data || {}), ...(borrowerFallback || {}), ...(ledgerFallback || {}), ...(loanDetails || {}) };
            populateDashboardLedgerFields(borrowerData, data);
            fetchDashboardLedgerData(loanId)
                .then(transactions => populateDashboardLedgerSummary(transactions, borrowerData))
                .catch(err => console.warn('Failed to fetch ledger transactions for notif modal', err));
        }).catch(() => {
            populateDashboardLedgerFields(data, data);
            fetchDashboardLedgerData(loanId)
                .then(transactions => populateDashboardLedgerSummary(transactions, data))
                .catch(e => console.warn('fetchDashboardLedgerData fallback failed', e));
        });
    }

    const modal   = document.getElementById('notifLoanModal');
    const content = document.getElementById('notifLoanModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
};

window.closeNotifModal = async function() {
    const modal   = document.getElementById('notifLoanModal');
    const content = document.getElementById('notifLoanModalContent');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 200);

    if (activeModalNotifId) {
        if (activeModalNotifType === 'PENDING_KPTN') {
            lastProcessedId      = activeModalNotifId;
            activeModalNotifId   = null;
            activeModalNotifType = null;
            loadNotifications();
        } else {
            const formData = new FormData();
            formData.append('notification_id', activeModalNotifId);
            try {
                const res    = await fetch(`${BASE_URL}/public/api/mark_notification_read.php`, { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) {
                    lastProcessedId      = activeModalNotifId;
                    activeModalNotifId   = null;
                    activeModalNotifType = null;
                    loadNotifications();
                }
            } catch (error) { console.error('Error marking read', error); }
        }
    }
};