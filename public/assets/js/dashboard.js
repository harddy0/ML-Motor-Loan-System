// Change this line near the top
let activeModalNotifId   = null;
let activeModalNotifType = null; // Track type to guard PENDING_KPTN from mark-as-read
let lastProcessedId      = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    loadNotifications();

    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerText = "REFRESHING...";
            loadDashboard().finally(() => {
                btn.disabled = false;
                btn.innerText = "REFRESH DATA";
            });
        });
    }
});

async function loadDashboard() {
    try {
        const response = await fetch(`${BASE_URL}/public/api/get_dashboard_stats.php`);
        const result = await response.json();

        if (result.success) {
            const { metrics, financials } = result.data;

            // Helper: format peso amount (no leading ₱ symbol in the span — 
            // the table already has the ₱ column header or prefix)
            const peso = (val) => parseFloat(val).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.innerText = val;
            };

            // ── Top 3 cards ─────────────────────────────────────────────
            set('statUnits',     metrics.due_this_month);   // count of unpaid schedules this month
            set('statBorrowers', metrics.active_borrowers);
            set('statPaid',      metrics.fully_paid);

            // ── Progress bar (this month's collected vs expected) ────────
            const pct   = parseFloat(financials.progress_percent) || 0;
            const label = financials.progress_label || `₱0.00 collected of ₱0.00 expected`;

            set('valProgressTxt',   `${pct}% Collected`);
            set('valProgressLabel', label);

            const bar = document.getElementById('barPaid');
            if (bar) bar.style.width = `${pct}%`;

            // ── Monthly breakdown table ──────────────────────────────────
            // Expected (scheduled this month)
            set('valExpectedPrincipal', '₱ ' + peso(financials.month_expected_principal));
            set('valExpectedInterest',  '₱ ' + peso(financials.month_expected_interest));
            set('valExpectedTotal',     '₱ ' + peso(financials.month_expected_total));

            // Collected (paid this month)
            set('valCollectedPrincipal', '₱ ' + peso(financials.month_collected_principal));
            set('valCollectedInterest',  '₱ ' + peso(financials.month_collected_interest));
            set('valCollectedTotal',     '₱ ' + peso(financials.month_collected_total));

            // Outstanding split
            set('valOutstandingPrincipal', '₱ ' + peso(financials.outstanding_principal));
            set('valOutstandingInterest',  '₱ ' + peso(financials.outstanding_interest));
            set('valNetOutstanding',       '₱ ' + peso(financials.net_outstanding));
        }
    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}

// ==========================================
// NOTIFICATION SYSTEM (TABBED MODAL VERSION)
// — Everything below this line is UNCHANGED —
// ==========================================


// Load & inject the Attach KPTN modal from the borrowers page when needed
function ensureAttachModalLoaded(callback) {
    if (document.getElementById('attachKptnModal')) {
        return callback && callback();
    }

    fetch(`${BASE_URL}/public/borrowers/index.php`) 
        .then(res => res.text())
        .then(html => {
            const start = html.indexOf('<div id="attachKptnModal"');
            if (start === -1) return callback && callback();

            // Find the script tag after the modal so we can extract both
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
                // Fallback: try to find closing div (best-effort)
                const endDiv = html.indexOf('</div>', start);
                modalHtml = html.substring(start, endDiv + 6);
            }

            const container = document.createElement('div');
            container.innerHTML = modalHtml;
            document.body.appendChild(container);

            if (scriptContent) {
                // Extract inner JS and execute it so drag/drop setup runs
                const innerStart = scriptContent.indexOf('>') + 1;
                const innerEnd = scriptContent.lastIndexOf('</script>');
                const js = scriptContent.substring(innerStart, innerEnd);
                const s = document.createElement('script');
                s.textContent = js;
                document.body.appendChild(s);
            }

            // Attach a local submit handler (borrowers.js is not loaded on dashboard)
            // Define closeModal if it's not available on the dashboard
            if (typeof window.closeModal !== 'function') {
                window.closeModal = function(id) {
                    const modal = document.getElementById(id);
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        // If we are closing the attach modal, reset it
                        if (id === 'attachKptnModal') {
                            resetAttachModal();
                        }
                    }
                };
            }

            const attachForm = document.getElementById('attachKptnForm');
            if (attachForm && !attachForm._dashboardHandlerAttached) {
                attachForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitKptn');
    const originalText = btn ? btn.innerText : 'Save';
    if (btn) { btn.innerText = "Activating..."; btn.disabled = true; }

    const formData = new FormData();
    const loanIdField = document.getElementById('ak_loan_id');
    const kptnField = document.getElementById('ak_kptn_number');
    const fileInput = document.getElementById('ak_kptn_receipt');

    if (!loanIdField || !loanIdField.value) {
        alert('Missing loan ID.');
        if (btn) { btn.innerText = originalText; btn.disabled = false; }
        return;
    }

    formData.append('loan_id', loanIdField.value);
     formData.append('kptn_number', kptnField ? kptnField.textContent.trim() : '');
    if (fileInput && fileInput.files[0]) {
        formData.append('kptn_receipt', fileInput.files[0]);
    }

    // 1. Upload the File
    fetch(`${BASE_URL}/public/actions/attach_kptn.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(async (data) => {
        if (data.success) {
            // 2. If we have an active notification ID, mark it as READ immediately
            if (activeModalNotifId) {
                const readData = new FormData();
                readData.append('notification_id', activeModalNotifId);
                
                await fetch(`${BASE_URL}/public/api/mark_notification_read.php`, { 
                    method: 'POST', 
                    body: readData 
                });
                activeModalNotifId = null; 
            }

            // 3. Close the upload modal
            closeModal('attachKptnModal');

            // 4. Refresh everything (This moves the item to the Read list with the green text)
            loadNotifications(); 
            if (typeof loadDashboard === 'function') loadDashboard();

            // 5. Show Success Message
            const successAlert = document.getElementById('successAlertModal');
            if (successAlert) successAlert.classList.replace('hidden', 'flex');
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        if (btn) { btn.innerText = originalText; btn.disabled = false; }
    });
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
    // 1. Reset the hidden and text inputs
    const loanIdField = document.getElementById('ak_loan_id');
    const kptnField = document.getElementById('ak_kptn_number');
    const borrowerLabel = document.getElementById('ak_borrower_name');
    const errorMsg = document.getElementById('ak_error_msg');
    
    if (loanIdField) loanIdField.value = '';
    if (kptnField) kptnField.textContent = '';
    if (borrowerLabel) borrowerLabel.innerText = '...';
    if (errorMsg) {
        errorMsg.innerText = '';
        errorMsg.classList.add('hidden');
    }

    // 2. Reset the File Input and Label
    const fileInput = document.getElementById('ak_kptn_receipt');
    const fileLabel = document.getElementById('akKptnFileLabel');
    if (fileInput) fileInput.value = ''; // This clears the actual selected file
    if (fileLabel) fileLabel.textContent = 'Choose file or drag it here';

    // 3. Reset the Button
    const btn = document.getElementById('btnSubmitKptn');
    if (btn) {
        btn.innerText = 'Save';
        btn.disabled = false;
    }
}

// Open the injected Attach KPTN modal on dashboard with notification data
window.openAttachFromDashboard = function(encodedNotif) {
   // Check if the function exists before calling it
    if (typeof resetAttachModal === 'function') {
        resetAttachModal();
    }
    try {
        const n = JSON.parse(decodeURIComponent(encodedNotif));

        activeModalNotifId = n.notification_id;
        // First ensure modal HTML + helpers are injected
        ensureAttachModalLoaded(() => {
            const loanId = n.loan_id || n.loanId || n.id || '';
            if (!loanId) {
                console.error('No loan_id in notification');
                return;
            }

            // Fetch loan details to get the pending_kptn and borrower name
            fetch(`${BASE_URL}/public/api/get_loan_details.php?loan_id=${encodeURIComponent(loanId)}`)
                .then(res => res.json())
                .then(resp => {
                    if (!resp.success) {
                        console.warn('Could not fetch loan details', resp.error);
                    }

                    const data = resp.success ? resp.data : n;

                    const loanIdField = document.getElementById('ak_loan_id');
                    const borrowerLabel = document.getElementById('ak_borrower_name');
                    const kptnField = document.getElementById('ak_kptn_number');

                    if (loanIdField) loanIdField.value = data.loan_id || loanId;
                    const nameVal = data.first_name ? (data.first_name + ' ' + (data.last_name||'')) : (n.first_name ? (n.first_name + ' ' + (n.last_name||'')) : n.name || '');
                    if (borrowerLabel) borrowerLabel.innerText = nameVal.toUpperCase();
                    if (kptnField) kptnField.textContent = data.pending_kptn || n.pending_kptn || '';

                    const modal = document.getElementById('attachKptnModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                })
                .catch(err => {
                    console.error('Error fetching loan details', err);
                });
        });
    } catch (e) {
        console.error('openAttachFromDashboard error', e);
    }
};

async function loadNotifications() {
    const unreadList = document.getElementById('notifUnreadList');
    const readList = document.getElementById('notifReadList');
    if (!unreadList) return;

    try {
        const response = await fetch(`${BASE_URL}/public/api/get_notifications.php`);
        const result = await response.json();

        if (result.success) {
            const { unread, read, unread_count } = result.data;
            
            // Manage Notification Badge
            const badge = document.getElementById('notifBadge');
            if (unread_count > 0) {
                badge.innerText = `${unread_count} NEW`;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }

            renderNotifList('unread', unread, unreadList);
            renderNotifList('read', read, readList);
        }
    } catch (error) {
        console.error("Failed to load notifications", error);
        unreadList.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Failed to load data.</p>';
    }
}

function renderNotifList(type, list, container) {
    if (list.length === 0) {
        container.innerHTML = `<p class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No ${type} notifications.</p>`;
        return;
    }

    // This sorts the list so that the one you just clicked is ALWAYS #1
    const sorted = [...list].sort((a, b) => {
        // 1. Last-processed item always stays on top
        if (String(a.notification_id) === String(lastProcessedId)) return -1;
        if (String(b.notification_id) === String(lastProcessedId)) return 1;

        // 2. Unread PENDING_KPTN always before everything else
        const aSticky = (a.type === 'PENDING_KPTN' && !parseInt(a.is_read));
        const bSticky = (b.type === 'PENDING_KPTN' && !parseInt(b.is_read));
        if (aSticky && !bSticky) return -1;
        if (!aSticky && bSticky) return  1;

        // 3. Newest first within same priority group
        return new Date(b.created_at) - new Date(a.created_at);
    });

    container.innerHTML = '';
    sorted.forEach(n => {
        const notifJson = encodeURIComponent(JSON.stringify(n));
        const opacity = type === 'read' ? 'opacity-60 bg-slate-100' : 'bg-white shadow-sm border-slate-200';
        
        const uploaderName = (n.uploader_first || n.uploader_last) 
            ? `${n.uploader_first || ''} ${n.uploader_last || ''}`.trim() 
            : 'System/Unknown';

        const borrowerName = (n.first_name || n.last_name) ? `${n.first_name || ''} ${n.last_name || ''}`.trim() : (n.message || '');

        // ✏️ CHANGED: full long-form date instead of toLocaleString()
        const createdDate = new Date(n.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        let html = '';

        // --- CASE 1: BORROWERS REQUIRING ATTACHMENT ---
        if (n.type === 'PENDING_KPTN') {
            if (type === 'unread') {
                // DISPLAY IN: notifUnreadList
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
                // DISPLAY IN: notifReadList (Confirmation state)
                html = `
                    <div onclick="openNotifModal('${notifJson}', '${type}')" class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}">
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
                    </div>
                `;
            }
        } 
        // --- CASE 2: STANDARD NOTIFICATIONS (No Attachment Required) ---
        else {
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

function switchNotifTab(tab) {
    const btnUnread = document.getElementById('tabBtnUnread');
    const btnRead = document.getElementById('tabBtnRead');
    const listUnread = document.getElementById('notifUnreadList');
    const listRead = document.getElementById('notifReadList');

    if (tab === 'unread') {
        btnUnread.className = 'flex-1 py-3 text-xs font-bold text-[#dc2626] border-b-2 border-[#dc2626] transition-colors bg-white';
        btnRead.className = 'flex-1 py-3 text-xs font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors bg-slate-50';
        listUnread.classList.remove('hidden');
        listRead.classList.add('hidden');
    } else {
        btnRead.className = 'flex-1 py-3 text-xs font-bold text-[#dc2626] border-b-2 border-[#dc2626] transition-colors bg-white';
        btnUnread.className = 'flex-1 py-3 text-xs font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-600 transition-colors bg-slate-50';
        listRead.classList.remove('hidden');
        listUnread.classList.add('hidden');
    }
}

function formatDashboardDisplayDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const cleaned = String(dateStr).replace(/\s/g, '');
    let d = new Date(String(dateStr) + 'T00:00:00');
    if (isNaN(d.getTime())) d = new Date(cleaned);
    if (!isNaN(d.getTime())) return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    return dateStr;
}

function fetchBorrowerDetailsFromBorrowersApi(payload) {
    const searchKey = payload.employe_id || payload.employee_id || payload.id || payload.name || '';
    if (!searchKey) return Promise.resolve(null);

    const url = `${BASE_URL}/public/api/get_paginated_borrowers.php?page=1&limit=50&search=${encodeURIComponent(searchKey)}`;
    return fetch(url)
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.payload || !Array.isArray(result.payload.data)) return null;

            const rows = result.payload.data;
            const wantedLoanId = String(payload.loan_id || payload.loanId || '');
            const wantedEmpId = String(payload.employe_id || payload.employee_id || payload.id || '');
            const wantedName = String(payload.name || `${payload.first_name || ''} ${payload.last_name || ''}`.trim()).toLowerCase();

            const exact = rows.find(row => {
                const rowLoanId = String(row.loan_id || '');
                const rowEmpId = String(row.id || row.employe_id || '');
                const rowName = String(row.name || '').toLowerCase();
                return (wantedLoanId && rowLoanId === wantedLoanId)
                    || (wantedEmpId && rowEmpId === wantedEmpId)
                    || (wantedName && rowName === wantedName);
            });

            return exact || rows[0] || null;
        })
        .catch(() => null);
}

function fetchBorrowerDetailsFromLedgerApi(payload) {
    const searchKey = payload.pn_number || payload.pn_no || payload.employe_id || payload.employee_id || payload.name || '';
    if (!searchKey) return Promise.resolve(null);

    const url = `${BASE_URL}/public/api/get_paginated_ledger.php?page=1&limit=50&search=${encodeURIComponent(searchKey)}`;
    return fetch(url)
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.payload || !Array.isArray(result.payload.data)) return null;

            const rows = result.payload.data;
            const wantedLoanId = String(payload.loan_id || payload.loanId || '');
            const wantedEmpId = String(payload.employe_id || payload.employee_id || payload.id || '');
            const wantedPn = String(payload.pn_number || payload.pn_no || '');
            const wantedName = String(payload.name || `${payload.first_name || ''} ${payload.last_name || ''}`.trim()).toLowerCase();

            const exact = rows.find(row => {
                const rowLoanId = String(row.loan_id || '');
                const rowEmpId = String(row.employe_id || row.employee_id || '');
                const rowPn = String(row.pn_number || row.pn_no || '');
                const rowName = String(row.name || '').toLowerCase();

                return (wantedLoanId && rowLoanId === wantedLoanId)
                    || (wantedEmpId && rowEmpId === wantedEmpId)
                    || (wantedPn && rowPn === wantedPn)
                    || (wantedName && rowName === wantedName);
            });

            return exact || rows[0] || null;
        })
        .catch(() => null);
}

function populateDashboardLedgerFields(borrowerData, fallbackData = {}) {
    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerText = text;
    };

    const borrowerLabel = borrowerData.name
        || ((borrowerData.first_name || fallbackData.first_name) ? `${borrowerData.first_name || fallbackData.first_name || ''} ${borrowerData.last_name || fallbackData.last_name || ''}`.trim() : '')
        || fallbackData.name
        || 'N/A';

    setText('modal-ledger-name', borrowerLabel);
    setText('modal-ledger-id', borrowerData.employe_id || borrowerData.employee_id || borrowerData.id || fallbackData.employe_id || fallbackData.employee_id || fallbackData.id || 'N/A');
    setText('modal-ledger-pn', borrowerData.pn_number || borrowerData.pn_no || fallbackData.pn_number || fallbackData.pn_no || '--');
    setText('modal-ledger-pndate', formatDashboardDisplayDate(borrowerData.g_date || borrowerData.date_granted || borrowerData.raw_date || borrowerData.date || fallbackData.g_date || fallbackData.date_granted || fallbackData.raw_date || fallbackData.date));
    setText('modal-ledger-maturity', formatDashboardDisplayDate(borrowerData.maturity_date || borrowerData.pn_maturity || fallbackData.maturity_date || fallbackData.pn_maturity));
    const termsValue = borrowerData.term_months || borrowerData.terms || fallbackData.term_months || fallbackData.terms;
    setText('modal-ledger-terms', termsValue ? (termsValue + ' Months') : '--');
    setText('modal-ledger-ref', borrowerData.loan_ref_no || borrowerData.reference_no || borrowerData.reference_number || fallbackData.loan_ref_no || fallbackData.reference_no || fallbackData.reference_number || '--');
    setText('modal-ledger-region', borrowerData.region || fallbackData.region || '--');
    setText('modal-ledger-branch', borrowerData.branch || fallbackData.branch || '--');
    setText('modal-ledger-contact', borrowerData.contact_number || borrowerData.contact || fallbackData.contact_number || fallbackData.contact || '--');

    const statusBadge = document.getElementById('modal-ledger-status');
    if (statusBadge) {
        const status = borrowerData.current_status || fallbackData.current_status || '--';
        const statusText = status === 'VOIDED' ? 'VOID' : status;
        statusBadge.innerText = statusText;
        if (status === 'FULLY PAID') {
            statusBadge.className = 'inline-block px-4 py-0 bg-green-100 text-green-700 text-[13px] font-black uppercase rounded-full';
        } else if (status === 'VOIDED') {
            statusBadge.className = 'inline-block px-4 py-0 bg-orange-100 text-orange-700 text-[13px] font-black uppercase rounded-full';
        } else {
            statusBadge.className = 'inline-block px-4 py-0 bg-blue-100 text-blue-700 text-[13px] font-black uppercase rounded-full';
        }
    }

    const principal = parseFloat(borrowerData.loan_amount || fallbackData.loan_amount) || 0;
    const semiAmort = parseFloat(borrowerData.semi_monthly_amt || borrowerData.deduction || fallbackData.semi_monthly_amt || fallbackData.deduction) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate || fallbackData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months || borrowerData.terms || fallbackData.term_months || fallbackData.terms) || 0;
    const totalRatePercent = (addOnRateDecimal * termMonths * 100).toFixed(0);

    setText('modal-ledger-rate', totalRatePercent + '%');
    setText('modal-ledger-principal', '₱ ' + principal.toLocaleString(undefined, { minimumFractionDigits: 2 }));
    setText('modal-ledger-amort', '₱ ' + semiAmort.toLocaleString(undefined, { minimumFractionDigits: 2 }));
    setText('modal-ledger-monthly-amort', '₱ ' + (semiAmort * 2).toLocaleString(undefined, { minimumFractionDigits: 2 }));

    const depositAmount = parseFloat(borrowerData.deposit_amount || fallbackData.deposit_amount) || 0;
    setText('modal-ledger-security-deposit', '₱ ' + depositAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }));
}

function fetchDashboardLedgerData(loanId) {
    const url = `${BASE_URL}/public/api/get_ledger_transactions.php?loan_id=${encodeURIComponent(loanId)}`;
    return fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) return result.data;
            throw new Error(result.error || 'Failed to fetch ledger transactions.');
        });
}

function populateDashboardLedgerSummary(transactions, borrowerData) {
    let totalPrincipalPaid = 0;
    let totalInterestPaid = 0;
    let sumTotalPrincipal = 0;
    let sumTotalInterest = 0;

    transactions.forEach(txn => {
        const principalAmt = parseFloat(txn.principal_amt || txn.principal) || 0;
        const interestAmt = parseFloat(txn.interest_amt || txn.interest) || 0;
        const statusClean = (txn.status || '').toUpperCase();
        const isPaid = statusClean === 'PAID';

        sumTotalPrincipal += principalAmt;
        sumTotalInterest += interestAmt;

        if (isPaid) {
            totalPrincipalPaid += principalAmt;
            totalInterestPaid += interestAmt;
        }
    });

    const setMoney = (id, val) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerText = '₱ ' + val.toLocaleString(undefined, { minimumFractionDigits: 2 });
    };

    const loanAmount = parseFloat(borrowerData.loan_amount) || 0;
    const addOnRateDecimal = parseFloat(borrowerData.add_on_rate) || 0;
    const termMonths = parseInt(borrowerData.term_months) || 0;
    const grossPrincipal = loanAmount;
    const grossInterest = loanAmount * addOnRateDecimal * termMonths;
    const grossTotal = grossPrincipal + grossInterest;

    setMoney('modal-ledger-gross-principal', grossPrincipal);
    setMoney('modal-ledger-gross-interest', grossInterest);
    setMoney('modal-ledger-gross-total', grossTotal);

    const principalBalance = sumTotalPrincipal - totalPrincipalPaid;
    const interestBalance = sumTotalInterest - totalInterestPaid;
    const totalOutstanding = principalBalance + interestBalance;

    setMoney('modal-ledger-principal-paid', totalPrincipalPaid);
    setMoney('modal-ledger-principal-balance', principalBalance);
    setMoney('modal-ledger-interest-paid', totalInterestPaid);
    setMoney('modal-ledger-interest-balance', interestBalance);
    setMoney('modal-ledger-total-payment', totalPrincipalPaid + totalInterestPaid);
    setMoney('modal-ledger-total-balance', totalOutstanding);
}

function openNotifModal(encodedData, type) {
    const data = JSON.parse(decodeURIComponent(encodedData));
    
    // Tag this notification ID so we can mark it read when modal closes
    // PENDING_KPTN is sticky — never mark-as-read via modal close, only via ATTACH NOW
    activeModalNotifId   = (type === 'unread') ? data.notification_id : null;
    activeModalNotifType = (type === 'unread') ? data.type : null;

    // Format Helpers
    const formatMoney = (val) => val ? '₱ ' + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
    const uploaderName = (data.uploader_first || data.uploader_last) ? `${data.uploader_first || ''} ${data.uploader_last || ''}`.trim() : 'System / Unknown';
    // ✏️ CHANGED: parse and format date_granted to full long-form date
    const formatLongDate = (str) => {
        if (!str) return 'N/A';
        const d = new Date(str);
        return isNaN(d) ? str : d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    // Safe setter to avoid errors when template changed
    const safeSet = (id, value) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerText = value;
    };

    const borrowerLabel = data.first_name ? `${data.first_name} ${data.last_name}` : (data.name || 'N/A');
    safeSet('nlm-borrower', borrowerLabel);
    safeSet('nlm-uploader', uploaderName);
    safeSet('nlm-pn', data.pn_number || 'N/A');
    safeSet('nlm-date', formatLongDate(data.date_granted));
    safeSet('nlm-amount', formatMoney(data.loan_amount));
    safeSet('nlm-deduction', formatMoney(data.semi_monthly_amt));
    safeSet('nlm-terms', data.term_months ? `${data.term_months} Months` : 'N/A');

    // Populate the embedded ledger card immediately using notification payload.
    populateDashboardLedgerFields(data, data);
    safeSet('notif-uploaded-by', uploaderName);

    // Fetch full loan details and ledger transactions when possible
    const loanId = data.loan_id || data.loanId || data.id || data.loanId;
    if (loanId) {
        const detailsUrl = (typeof BASE_URL !== 'undefined')
            ? `${BASE_URL}/public/api/get_loan_details.php?loan_id=${encodeURIComponent(loanId)}`
            : `../../api/get_loan_details.php?loan_id=${encodeURIComponent(loanId)}`;

        const loanDetailsPromise = fetch(detailsUrl)
            .then(res => res.json())
            .then(resp => (resp && resp.success && resp.data) ? resp.data : null)
            .catch(() => null);

        const borrowersFallbackPromise = fetchBorrowerDetailsFromBorrowersApi(data);
        const ledgerFallbackPromise = fetchBorrowerDetailsFromLedgerApi(data);

        Promise.all([loanDetailsPromise, borrowersFallbackPromise, ledgerFallbackPromise])
            .then(([loanDetails, borrowerFallback, ledgerFallback]) => {
                const borrowerData = {
                    ...(data || {}),
                    ...(borrowerFallback || {}),
                    ...(ledgerFallback || {}),
                    ...(loanDetails || {})
                };
                populateDashboardLedgerFields(borrowerData, data);

                fetchDashboardLedgerData(loanId)
                    .then(transactions => {
                        populateDashboardLedgerSummary(transactions, borrowerData);
                    })
                    .catch(err => console.warn('Failed to fetch ledger transactions for notif modal', err));
            })
            .catch(() => {
                populateDashboardLedgerFields(data, data);
                fetchDashboardLedgerData(loanId)
                    .then(transactions => {
                        populateDashboardLedgerSummary(transactions, data);
                    })
                    .catch(e => console.warn('fetchDashboardLedgerData fallback failed', e));
            });
    }

    // Show Modal Animation
    const modal = document.getElementById('notifLoanModal');
    const content = document.getElementById('notifLoanModalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

async function closeNotifModal() {
    const modal = document.getElementById('notifLoanModal');
    const content = document.getElementById('notifLoanModalContent');
    
    // Hide Modal Animation
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); }, 200);

    // If it was unread, mark as read — but NEVER for PENDING_KPTN (sticky, only resolved via ATTACH NOW)
    if (activeModalNotifId) {
        if (activeModalNotifType === 'PENDING_KPTN') {
            // Sticky notification — just clear the tracking vars and refresh
            lastProcessedId    = activeModalNotifId;
            activeModalNotifId = null;
            activeModalNotifType = null;
            loadNotifications();
        } else {
            const formData = new FormData();
            formData.append('notification_id', activeModalNotifId);

            try {
                const res = await fetch(`${BASE_URL}/public/api/mark_notification_read.php`, { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) {
                    lastProcessedId      = activeModalNotifId;
                    activeModalNotifId   = null;
                    activeModalNotifType = null;
                    loadNotifications();
                }
            } catch (error) {
                console.error("Error marking read", error);
            }
        }
    }
}