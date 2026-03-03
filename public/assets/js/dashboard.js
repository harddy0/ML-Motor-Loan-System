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
            const currency = (val) => '₱ ' + parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // 1. Update Metrics (Safely check if the element exists first)
            if (document.getElementById('statUnits')) document.getElementById('statUnits').innerText = metrics.units_processed;
            if (document.getElementById('statLedgers')) document.getElementById('statLedgers').innerText = metrics.active_ledgers;
            if (document.getElementById('statBorrowers')) document.getElementById('statBorrowers').innerText = metrics.active_borrowers;
            if (document.getElementById('statPaid')) document.getElementById('statPaid').innerText = metrics.fully_paid;

            // 2. Update Financials
            if (document.getElementById('valTotalLoaned')) document.getElementById('valTotalLoaned').innerText = currency(financials.total_loaned);
            if (document.getElementById('valTotalCollected')) document.getElementById('valTotalCollected').innerText = currency(financials.total_collected);
            if (document.getElementById('valTotalIncome')) document.getElementById('valTotalIncome').innerText = currency(financials.total_income);
            if (document.getElementById('valNetOutstanding')) document.getElementById('valNetOutstanding').innerText = currency(financials.net_outstanding);

            // 3. Update Progress Bar
            if (document.getElementById('valProgressTxt')) document.getElementById('valProgressTxt').innerText = `${financials.progress_percent}% Collected`;
            if (document.getElementById('barPaid')) document.getElementById('barPaid').style.width = `${financials.progress_percent}%`;
            if (document.getElementById('valOutstandingTxt')) document.getElementById('valOutstandingTxt').innerText = `Outstanding: ${currency(financials.net_outstanding)}`;
        }
    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}

// ==========================================
// NOTIFICATION SYSTEM (TABBED MODAL VERSION)
// ==========================================

let activeModalNotifId = null;

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

                    // Build FormData explicitly to ensure file is included
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
                    formData.append('kptn_number', kptnField ? kptnField.value : '');

                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        alert('Please attach a valid KPTN receipt file.');
                        if (btn) { btn.innerText = originalText; btn.disabled = false; }
                        return;
                    }

                    formData.append('kptn_receipt', fileInput.files[0]);

                    fetch(`${BASE_URL}/public/actions/attach_kptn.php`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            closeModal('attachKptnModal');
                            const successMessage = document.getElementById('successMessage');
                            if (successMessage) successMessage.innerText = "KPTN Verification successful. Amortization schedule has been generated and the loan is now active.";
                            const successAlert = document.getElementById('successAlertModal');
                            if (successAlert) successAlert.classList.replace('hidden', 'flex');

                            // Refresh notifications and dashboard state
                            if (typeof loadNotifications === 'function') loadNotifications();
                            if (typeof loadDashboard === 'function') loadDashboard();
                        } else {
                            if (btn) { btn.innerText = originalText; btn.disabled = false; }
                            const importErrorMessage = document.getElementById('importErrorMessage');
                            if (importErrorMessage) importErrorMessage.innerHTML = "Activation Error: " + data.error;
                            const importErrorModal = document.getElementById('importErrorModal');
                            if (importErrorModal) importErrorModal.classList.replace('hidden', 'flex');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (btn) { btn.innerText = originalText; btn.disabled = false; }
                        const importErrorMessage = document.getElementById('importErrorMessage');
                        if (importErrorMessage) importErrorMessage.innerHTML = "System Error connecting to the server.";
                        const importErrorModal = document.getElementById('importErrorModal');
                        if (importErrorModal) importErrorModal.classList.replace('hidden', 'flex');
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

// Open the injected Attach KPTN modal on dashboard with notification data
window.openAttachFromDashboard = function(encodedNotif) {
    try {
        const n = JSON.parse(decodeURIComponent(encodedNotif));
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
                    if (kptnField) kptnField.value = data.pending_kptn || n.pending_kptn || '';

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
        container.innerHTML = `<p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider text-center py-8">No ${type} notifications.</p>`;
        return;
    }

    container.innerHTML = '';
    list.forEach(n => {
        const notifJson = encodeURIComponent(JSON.stringify(n));
        const opacity = type === 'read' ? 'opacity-60 bg-slate-100' : 'bg-white shadow-sm border-slate-200';
        
        // Format the uploader's name
        const uploaderName = (n.uploader_first || n.uploader_last) 
            ? `${n.uploader_first || ''} ${n.uploader_last || ''}`.trim() 
            : 'System/Unknown';

        // Show only the new borrower's name in the list (fallback to message)
        const borrowerName = (n.first_name || n.last_name) ? `${n.first_name || ''} ${n.last_name || ''}`.trim() : (n.message || '');

        let html = '';

        // ==========================================
        // STICKY / ACTION REQUIRED NOTIFICATION
        // ==========================================
        if (n.type === 'PENDING_KPTN') {
            // Open the Attach KPTN modal inline on the dashboard
            html = `
                <div onclick="openAttachFromDashboard('${notifJson}')" class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 pr-3">
                            <div class="text-[12px] font-bold text-[#ce1126] mb-1 flex items-center gap-1 tracking-wide">
                                No Security Deposit Attachment 
                            </div>
                            <p name="new-added" class="text-[12px] text-slate-700 font-medium mb-1 leading-snug">${borrowerName}</p>
                            <p name="time-added" class="text-[11px] text-slate-400 font-bold">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                        <div class="text-right shrink-0 mt-1">
                            <a href="javascript:void(0)" onclick="openAttachFromDashboard('${notifJson}')" class="inline-block bg-[#ce1126] hover:bg-[#dc2626] text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
                                ATTACH NOW
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }
        // ==========================================
        // STANDARD NOTIFICATION
        // ==========================================
        else {
            html = `
                <div class="p-3 border rounded-lg mb-2 cursor-pointer hover:border-[#dc2626] transition-all transform hover:-translate-y-0.5 ${opacity}" onclick="openNotifModal('${notifJson}', '${type}')">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 pr-3">
                            <p name="new-added" class="text-[12px] text-slate-700 font-medium mb-1 leading-snug">${borrowerName}</p>
                            <p name="time-added" class="text-[11px] text-slate-400 font-bold">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p name="loan-added" class="text-[10px] font-bold text-[#ce1126] uppercase tracking-wider mb-2">New ${n.type.replace('_', ' ')}</p>
                            <p name="added-by" class="text-[11px] font-bold text-slate-700 uppercase">By: ${uploaderName}</p>
                        </div>
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

function openNotifModal(encodedData, type) {
    const data = JSON.parse(decodeURIComponent(encodedData));
    
    // Tag this notification ID so we can mark it read when modal closes
    activeModalNotifId = (type === 'unread') ? data.notification_id : null;

    // Format Helpers
    const formatMoney = (val) => val ? '₱ ' + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
    const uploaderName = (data.uploader_first || data.uploader_last) ? `${data.uploader_first || ''} ${data.uploader_last || ''}`.trim() : 'System / Unknown';

    // Populate Modal Elements
    document.getElementById('nlm-borrower').innerText = data.first_name ? `${data.first_name} ${data.last_name}` : 'N/A';
    document.getElementById('nlm-uploader').innerText = uploaderName;
    document.getElementById('nlm-pn').innerText = data.pn_number || 'N/A';
    document.getElementById('nlm-date').innerText = data.date_granted || 'N/A';
    document.getElementById('nlm-amount').innerText = formatMoney(data.loan_amount);
    document.getElementById('nlm-deduction').innerText = formatMoney(data.semi_monthly_amt);
    document.getElementById('nlm-terms').innerText = data.term_months ? `${data.term_months} Months` : 'N/A';

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

    // If it was unread, mark as read in the database and visually move it
    if (activeModalNotifId) {
        const formData = new FormData();
        formData.append('notification_id', activeModalNotifId);

        try {
            const res = await fetch(`${BASE_URL}/public/api/mark_notification_read.php`, { method: 'POST', body: formData });
            const result = await res.json();
            if (result.success) {
                activeModalNotifId = null;
                loadNotifications(); // Reloads both Unread and Read lists seamlessly
            }
        } catch (error) {
            console.error("Error marking read", error);
        }
    }
}