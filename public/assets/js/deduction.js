document.addEventListener("DOMContentLoaded", function() {
    fetchDeductions();
});

function fetchDeductions() {
    const tableBody = document.querySelector('tbody');
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500 font-bold uppercase tracking-widest">Loading records...</td></tr>';

    fetch('../../../public/api/get_deductions.php')
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                renderTable(result.data);
                updateStats(result.data);
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
    const tableBody = document.querySelector('tbody');
    tableBody.innerHTML = '';

    if(data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500 font-bold uppercase tracking-widest">No records found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const amountFormatted = parseFloat(row.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Add a color indicator if the match was successful or not
        const matchColor = row.match_status === 'MATCHED' ? 'text-green-500' : 'text-[#ff3b30]';
        
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 transition-colors group cursor-default";
        tr.innerHTML = `
            <td class="px-5 py-3 text-xs font-bold text-slate-500 text-center border-r border-slate-100 bg-slate-50/50">
                ${row.id}
            </td>
            <td class="px-5 py-3 text-xs font-bold text-slate-600 text-center border-r border-slate-100">
                ${row.p_date}
            </td>
            <td class="px-5 py-3 border-r border-slate-100">
                <span class="text-xs font-black text-slate-800 uppercase block">${row.last}, ${row.first}</span>
            </td>
            <td class="px-5 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100 bg-[#fff5f5]/50 group-hover:bg-[#fff5f5]">
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

function updateStats(data) {
    const totalCount = data.length;
    const totalAmount = data.reduce((sum, row) => sum + parseFloat(row.amount), 0);
    
    document.getElementById('total-count').innerText = totalCount;
    document.getElementById('total-amount').innerText = '₱ ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('showing-count').innerText = `Showing ${totalCount} records`;
}