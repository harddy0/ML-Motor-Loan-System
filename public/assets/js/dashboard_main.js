// ==========================================
// DASHBOARD MAIN: Top-Level Metrics & Progress
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    loadDashboard();
    loadLoanProgress();

    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            const btn = this;
            btn.disabled  = true;
            btn.innerText = 'REFRESHING...';
            
            // Wait for all dashboard elements to refresh
            const tasks = [loadDashboard(), loadLoanProgress()];
            if (typeof loadNotifications === 'function') {
                tasks.push(loadNotifications());
            }

            Promise.all(tasks).finally(() => {
                btn.disabled  = false;
                btn.innerText = 'REFRESH DATA';
            });
        });
    }
});

// ── Top strip + outstanding balance ───────────────────────────────────────
async function loadDashboard() {
    try {
        const response = await fetch(`${BASE_URL}/public/api/get_dashboard_stats.php`);
        const result   = await response.json();

        if (result.success) {
            const { metrics, financials } = result.data;

            const peso = (val) => parseFloat(val).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.innerText = val;
            };

            set('statBorrowers', metrics.active_borrowers);
            set('statPaid',      metrics.fully_paid);

            set('valOutstandingPrincipal', '₱ ' + peso(financials.outstanding_principal));
            set('valOutstandingInterest',  '₱ ' + peso(financials.outstanding_interest));
            set('valNetOutstanding',       '₱ ' + peso(financials.net_outstanding));
        }
    } catch (error) {
        console.error('Dashboard Load Error:', error);
    }
}

// ── Loan Progress ─────────────────────────────────────────────────────────
async function loadLoanProgress() {
    const list = document.getElementById('loanProgressList');
    if (!list) return;
 
    try {
        const response = await fetch(`${BASE_URL}/public/api/get_loan_progress.php`);
        const result   = await response.json();
 
        if (!result.success || !result.data.length) {
            list.innerHTML = '<p class="text-sm font-medium text-slate-400 italic py-6 text-center">No active loans found.</p>';
            return;
        }
 
        const rows = result.data;
 
        const setEl = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        list.innerHTML = '';
 
        rows.forEach(r => {
            let barColor, pctClass;
            if      (r.pct_done >= 75) { barColor = '#ce1126'; pctClass = 'text-[#ce1126] font-extrabold'; }
            else if (r.pct_done >= 50) { barColor = '#e85568'; pctClass = 'text-[#e85568] font-extrabold'; }
            else if (r.pct_done >= 25) { barColor = '#94a3b8'; pctClass = 'text-slate-600 font-bold';      }
            else                       { barColor = '#cbd5e1'; pctClass = 'text-slate-500 font-bold';      }
 
            const fillPct = Math.max(r.pct_done, 2);
 
            const item = document.createElement('div');
            item.className = 'grid items-center gap-3 py-0.5 border-b border-slate-50 last:border-0';
            item.style.gridTemplateColumns = '1fr 150px 110px 52px';
 
            item.innerHTML = `
                <span class="text-[13px] font-bold text-slate-800 truncate" title="${r.borrower_name}">
                    ${r.borrower_name}
                </span>
                <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full transition-all duration-700"
                         style="width:${fillPct}%; background:${barColor};"></div>
                </div>
                <span class="text-right whitespace-nowrap tabular-nums leading-tight">
                    <span class="block text-[14px] font-extrabold text-slate-700">${r.remaining_periods}</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-400">salary deductions left</span>
                </span>
                <span class="text-[14px] tabular-nums text-right ${pctClass}">
                    ${r.pct_done}%
                </span>
            `;
            list.appendChild(item);
        });
 
    } catch (error) {
        console.error('Loan Progress Load Error:', error);
        if (list) list.innerHTML = '<p class="text-sm font-medium text-red-400 italic py-6 text-center">Failed to load progress data.</p>';
    }
}