document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();

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