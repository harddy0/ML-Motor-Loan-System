document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();

    document.getElementById('refreshDashboard').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerText = "REFRESHING...";
        loadDashboard().finally(() => {
            btn.disabled = false;
            btn.innerText = "REFRESH DATA";
        });
    });
});

async function loadDashboard() {
    try {
        const response = await fetch(`${BASE_URL}/public/api/get_dashboard_stats.php`);
        const result = await response.json();

        if (result.success) {
            const { metrics, financials } = result.data;
            const currency = (val) => '₱ ' + parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // 1. Update Metrics
            document.getElementById('statUnits').innerText = metrics.units_processed;
            document.getElementById('statLedgers').innerText = metrics.active_ledgers;
            document.getElementById('statBorrowers').innerText = metrics.active_borrowers;
            document.getElementById('statPaid').innerText = metrics.fully_paid;

            // 2. Update Financials
            document.getElementById('valTotalLoaned').innerText = currency(financials.total_loaned);
            document.getElementById('valTotalCollected').innerText = currency(financials.total_collected);
            document.getElementById('valTotalIncome').innerText = currency(financials.total_income);
            document.getElementById('valNetOutstanding').innerText = currency(financials.net_outstanding);

            // 3. Update Progress Bar
            document.getElementById('valProgressTxt').innerText = `${financials.progress_percent}% Collected`;
            document.getElementById('barPaid').style.width = `${financials.progress_percent}%`;
            document.getElementById('valOutstandingTxt').innerText = `Outstanding: ${currency(financials.net_outstanding)}`;
        }
    } catch (error) {
        console.error("Dashboard Load Error:", error);
    }
}