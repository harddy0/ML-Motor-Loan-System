document.addEventListener("DOMContentLoaded", function() {
    fetchDeductions();
    initializeFilters();
});

function fetchDeductions() {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500 font-bold uppercase tracking-widest">Loading records...</td></tr>';

    fetch('../../../public/api/get_deductions.php')
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                renderTable(result.data);
                // Call filter to initialize stats on load
                applyFilters(); 
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500 font-bold">Error: ${result.error}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500 font-bold">Fatal error loading data.</td></tr>';
        });
}

function renderTable(data) {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '';

    if(data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500 font-bold uppercase tracking-widest">No records found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const amountFormatted = parseFloat(row.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
        const matchColor = row.match_status === 'MATCHED' ? 'text-green-500' : 'text-[#ff3b30]';
        
        // Searchable string
        const searchableText = `${row.id} ${row.first} ${row.last}`.toLowerCase();

        const tr = document.createElement('tr');
        // Add class and data attributes for filtering and calculating totals dynamically
        tr.className = "deduction-row group hover:bg-slate-200 transition-colors cursor-pointer group border-b border-slate-100";
        tr.setAttribute('data-search', searchableText);
        tr.setAttribute('data-date', row.raw_i_date); // The YYYY-MM-DD date we added in the PHP class
        tr.setAttribute('data-amount', row.amount); // Used to calculate live total amount

        tr.innerHTML = `
            <td class="px-5 py-3 text-xs font-bold text-slate-500 text-center border-r border-slate-100">
                ${row.id}
            </td>
            <td class="px-5 py-3 text-xs font-bold text-slate-600 text-center border-r border-slate-100">
                ${row.p_date}
            </td>
            <td class="px-5 py-3 border-r border-slate-100">
                <span class="text-xs font-black text-slate-800 uppercase block">${row.last}, ${row.first}</span>
            </td>
            <td class="px-5 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100">
                ${amountFormatted}
            </td>
            <td class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase text-center border-r border-slate-100">
                ${row.region}
            </td>
            <td class="px-5 py-3 text-[10px] font-bold text-slate-400 text-center">
                ${row.i_date}
                <br><span class="text-[8px] uppercase font-black tracking-widest ${matchColor}">${row.match_status}</span>
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

// ==========================================
// SEARCH & DATE FILTER LOGIC
// ==========================================
function initializeFilters() {
    const searchInput = document.getElementById('deductionSearchInput');
    const fromDate = document.getElementById('deductionFromDate');
    const toDate = document.getElementById('deductionToDate');
    const viewAllBtn = document.getElementById('deductionViewAllBtn');

    const exportBtn = document.getElementById('exportDeductionBtn');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Grab the current values from the inputs
            const search = searchInput ? searchInput.value.trim() : '';
            const from = fromDate ? fromDate.value : '';
            const to = toDate ? toDate.value : '';

            // Build the URL parameters
            const queryParams = new URLSearchParams({
                search: search,
                from: from,
                to: to
            });

            // Redirect to the API endpoint (forces file download)
            window.location.href = `../../../public/api/export_deductions.php?${queryParams.toString()}`;
        });
    }

    // Attach event listeners
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (fromDate) fromDate.addEventListener('change', applyFilters);
    if (toDate) toDate.addEventListener('change', applyFilters);

    // View All resets the filters
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (fromDate) fromDate.value = '';
            if (toDate) toDate.value = '';
            applyFilters();
        });
    }
}

function applyFilters() {
    const searchInput = document.getElementById('deductionSearchInput');
    const fromDate = document.getElementById('deductionFromDate');
    const toDate = document.getElementById('deductionToDate');
    const tableBody = document.getElementById('deductionTableBody');

    if (!tableBody) return;

    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const from = fromDate ? fromDate.value : '';
    const to = toDate ? toDate.value : '';

    const tableRows = tableBody.querySelectorAll('.deduction-row');
    
    let visibleCount = 0;
    let visibleAmount = 0;

    tableRows.forEach(row => {
        const searchableText = row.getAttribute('data-search');
        const rowDate = row.getAttribute('data-date'); 
        const amount = parseFloat(row.getAttribute('data-amount')) || 0;

        // 1. Check Search
        const matchesSearch = searchableText.includes(searchTerm);
        
        // 2. Check Dates
        let matchesDate = true;
        if (from && rowDate < from) matchesDate = false;
        if (to && rowDate > to) matchesDate = false;

        // 3. Apply Visibility
        if (matchesSearch && matchesDate) {
            row.style.display = ''; // Show
            visibleCount++;
            visibleAmount += amount;
        } else {
            row.style.display = 'none'; // Hide
        }
    });

    // Dynamically update the widgets based on visible rows
    document.getElementById('showing-count').innerText = `Showing ${visibleCount} records`;
    document.getElementById('total-count').innerText = visibleCount;
    document.getElementById('total-amount').innerText = '₱ ' + visibleAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
}