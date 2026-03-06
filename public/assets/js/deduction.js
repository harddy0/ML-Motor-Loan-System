function formatFullDate(dateStr) {
    if (!dateStr || dateStr === '--') return '--';
    const [month, day, year] = dateStr.split('/').map(Number);
    const d = new Date(year, month - 1, day);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatFullDateTime(dateTimeStr) {
    if (!dateTimeStr || dateTimeStr === '--') return '--';
    const [datePart, timePart, meridiem] = dateTimeStr.split(' ');
    const [month, day, year] = datePart.split('/').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);
    const d = new Date(year, month - 1, day, hours, minutes);
    const dateFormatted = d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const timeFormatted = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    return `${dateFormatted} ${timeFormatted}`;
}

document.addEventListener("DOMContentLoaded", function() {
    fetchDeductions();
    initializeFilters();
});

function fetchDeductions() {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-slate-500 ">Loading records...</td></tr>';

    fetch('../../../public/api/get_deductions.php')
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                renderTable(result.data);
                // Set total record count once on load — never changes with search/filter
                const totalCount = document.getElementById('total-count');
                if (totalCount) totalCount.innerText = result.data.length;
                applyFilters(); 
            } else {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-red-500 font-bold">Error: ${result.error}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500 font-bold">Fatal error loading data.</td></tr>';
        });
}

function renderTable(data) {
    const tableBody = document.querySelector('#deductionTableBody');
    tableBody.innerHTML = '';

    if(data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-12 text-center text-[13px] text-slate-400 italic">No records found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const amountFormatted = parseFloat(row.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
        
        let matchColor = 'text-[#ff3b30]'; // default UNMATCHED / EXCEPTION
        if (row.match_status === 'MATCHED') {
            matchColor = 'text-green-500';
        } else if (row.match_status === 'VOIDED') {
            matchColor = 'text-orange-500'; // NEW Voided UI color
        }
        
        const searchableText = `${row.id} ${row.first} ${row.last}`.toLowerCase();

        const tr = document.createElement('tr');
        tr.className = "deduction-row group hover:bg-slate-200 transition-colors cursor-pointer border-b border-slate-100";
        tr.setAttribute('data-search', searchableText);
        tr.setAttribute('data-date', row.raw_i_date); 
        tr.setAttribute('data-amount', row.amount); 
        tr.setAttribute('data-status', row.match_status); // Added status tracker

        tr.innerHTML = `
            <td class="px-5 py-2 text-[14px] text-slate-500 text-center border-r border-slate-100">
                ${row.id}
            </td>
            <td class="px-5 py-2 text-[14px] text-slate-600 text-center border-r border-slate-100">
                ${formatFullDate(row.p_date)}
            </td>
            <td class="px-5 py-2 border-r border-slate-100">
                <span class="text-[14px] font-black text-slate-800 block">${row.first} ${row.last}</span>
            </td>
            <td class="px-5 py-2 text-[14px] text-slate-800 text-right border-r border-slate-100">
                ${amountFormatted}
            </td>
            <td class="px-1 py-1 text-[5px] text-slate-500 text-left border-r border-slate-100">
                ${row.region}
            </td>
            <td class="px-1 py-1 text-[5px] text-slate-400 text-center">
                ${formatFullDateTime(row.i_date)}
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

// ==========================================
// SEARCH & DATE FILTER LOGIC
// ==========================================
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const viewAllBtn = document.getElementById('deductionViewAllBtn');
    const exportBtn = document.getElementById('exportDeductionBtn');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const search = searchInput ? searchInput.value.trim() : '';
            const from = fromDate ? fromDate.value : '';
            const to = toDate ? toDate.value : '';

            const queryParams = new URLSearchParams({ search, from, to });
            window.location.href = `../../../public/api/export_deductions.php?${queryParams.toString()}`;
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (fromDate) fromDate.addEventListener('change', applyFilters);
    if (toDate) toDate.addEventListener('change', applyFilters);

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
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
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
        const status = row.getAttribute('data-status');

        const matchesSearch = searchableText.includes(searchTerm);
        
        let matchesDate = true;
        if (from && rowDate < from) matchesDate = false;
        if (to && rowDate > to) matchesDate = false;

        if (matchesSearch && matchesDate) {
            row.style.display = ''; 
            visibleCount++;
            
            // EXCLUDE VOIDED AMOUNTS FROM THE TOTAL TRACKER
            if (status !== 'VOIDED') {
                visibleAmount += amount;
            }
        } else {
            row.style.display = 'none'; 
        }
    });

    const showingCount = document.getElementById('showing-count');
    const totalAmount = document.getElementById('total-amount');

    if (showingCount) showingCount.innerText = `Showing ${visibleCount} records`;
    if (totalAmount) totalAmount.innerText = '₱ ' + visibleAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
}