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
            html = `
                <div class="p-3 border-l-4 border-l-orange-500 bg-orange-50 rounded-r-lg mb-2 shadow-sm cursor-default">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 pr-3">
                            <div class="text-[12px] font-bold text-orange-700 mb-1 flex items-center gap-1 uppercase tracking-wide">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Action Required
                            </div>
                            <p class="text-[11px] text-slate-700 leading-snug mb-2 font-medium">${n.message}</p>
                            <p class="text-[10px] text-slate-400 font-bold">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                        <div class="text-right shrink-0 mt-1">
                            <a href="${BASE_URL}/public/borrowers/index.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white text-[10px] font-bold py-1.5 px-3 rounded shadow-sm transition-colors">
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