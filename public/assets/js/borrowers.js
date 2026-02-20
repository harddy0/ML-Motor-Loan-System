// Global variables
let tempBorrowerData = {};
let importedData = [];

// --- VIEW LOGIC ---
function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    document.getElementById('m-id').innerText = data.id;
    document.getElementById('m-fname').innerText = data.first_name;
    document.getElementById('m-lname').innerText = data.last_name;
    document.getElementById('m-date').innerText = data.date;
    document.getElementById('m-contact').innerText = data.contact;
    document.getElementById('m-pn-no').innerText = data.pn_no;
    document.getElementById('m-pn-mat').innerText = data.pn_maturity;
    document.getElementById('m-region').innerText = data.region;
    document.getElementById('m-amount').innerText = '₱ ' + parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('m-terms').innerText = data.terms;
    document.getElementById('m-deduct').innerText = '₱ ' + parseFloat(data.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// --- ADD / CREATE LOGIC ---
function openAddModal() {
    const modal = document.getElementById('addBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('addBorrowerForm').reset();
    
    // Auto-Fetch Next ID
    const idField = document.getElementById('employe_id');
    idField.value = "Fetching...";
    
    // Use the global BASE_URL variable defined in index.php
    fetch(`${BASE_URL}/public/api/get_next_id.php`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                idField.value = data.next_id;
            } else {
                idField.value = "Error";
            }
        })
        .catch(err => {
            console.error(err);
            idField.value = "Error";
        });
}

// --- VALIDATE & CALL API ---
function validateAndShowSchedule() {
    const form = document.getElementById('addBorrowerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    tempBorrowerData = Object.fromEntries(formData.entries());

    // Populate Modal Header
    document.getElementById('sched-name').innerText = (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase();
    document.getElementById('sched-contact').innerText = tempBorrowerData.contact_number;
    document.getElementById('sched-pn').innerText = tempBorrowerData.pn_number;
    document.getElementById('sched-amount').innerText = parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sched-date').innerText = tempBorrowerData.loan_granted;
    document.getElementById('sched-terms').innerText = tempBorrowerData.terms + ' Months';
    document.getElementById('sched-maturity').innerText = tempBorrowerData.pn_maturity;
    
    // Show Loading State
    document.getElementById('amortization-rows').innerHTML = '<tr><td colspan="6" class="p-4 text-center text-slate-500 italic">Calculating effective yield...</td></tr>';

    closeModal('addBorrowerModal');
    const schedModal = document.getElementById('amortizationModal');
    schedModal.classList.remove('hidden');
    schedModal.classList.add('flex');

    // Call the Backend API
    fetchAmortizationSchedule(tempBorrowerData);
}

// --- FETCH FROM API ---
function fetchAmortizationSchedule(data) {
    const payload = {
        loan_amount: data.loan_amount,
        terms: data.terms,
        deduction: data.deduction,
        date_granted: data.loan_granted
    };

    fetch(`${BASE_URL}/public/api/calculate_amortization.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            // Update Financial Summary
            document.getElementById('sched-deduct').innerText = parseFloat(data.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('sched-rate').innerText = result.add_on_rate + ' % (Add-on)'; 
            document.getElementById('sched-initial-bal').innerText = parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});

            // Render Table Rows
            renderAmortizationTable(result.schedule);
            
            // Save calculated data
            tempBorrowerData.schedule = result.schedule;
            tempBorrowerData.periodic_rate = result.periodic_rate; 
        } else {
            alert("Calculation Error: " + result.error);
            closeModal('amortizationModal');
            openAddModal(); 
        }
    })
    .catch(err => {
        console.error(err);
        alert("System Error calling API");
    });
}

// --- RENDER TABLE ---
function renderAmortizationTable(rows) {
    const tbody = document.getElementById('amortization-rows');
    tbody.innerHTML = ''; 

    rows.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-yellow-50 border-b border-slate-200 transition-colors";
        tr.innerHTML = `
            <td class="p-2 border-r border-slate-200 text-center">${row.installment_no}</td>
            <td class="p-2 border-r border-slate-200 text-center">${row.date}</td>
            <td class="p-2 border-r border-slate-200 text-right text-slate-500">${parseFloat(row.principal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-2 border-r border-slate-200 text-right text-slate-500">${parseFloat(row.interest).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-2 border-r border-slate-200 font-bold text-black text-right">${parseFloat(row.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td class="p-2 font-bold text-right text-[#ff3b30]">${parseFloat(row.balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
    });
}

// --- FINAL SUBMIT ---
function submitFinalBorrower() {
    const formData = new FormData();
    for (const key in tempBorrowerData) {
        if (typeof tempBorrowerData[key] === 'object') {
            formData.append(key, JSON.stringify(tempBorrowerData[key]));
        } else {
            formData.append(key, tempBorrowerData[key]);
        }
    }

    fetch(`${BASE_URL}/public/actions/create_borrower.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert("Borrower & Amortization Schedule Saved Successfully!");
            location.reload();
        } else {
            alert("Error: " + (data.error || "Unknown error occurred"));
        }
    })
    .catch(err => {
        console.error(err);
        alert("System Error: Check console for details.");
    });
}

// --- IMPORT LOGIC ---
function openImportModal() {
    const modal = document.getElementById('importBorrowerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('importBorrowerForm').reset();
    document.getElementById('file-name-display').innerText = 'No file chosen';
}

// Initialize Import Listener
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importBorrowerForm');
    if(importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('file-upload');
            if(fileInput.files.length === 0) { alert("Please select a file."); return; }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Analyzing File...";
            btn.disabled = true;

            fetch(`${BASE_URL}/public/api/parse_import.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                btn.innerText = originalText;
                btn.disabled = false;

                if(result.success) {
                    importedData = result.data; 
                    closeModal('importBorrowerModal');
                    showImportPreview(importedData);
                } else {
                    alert("Import Error: " + result.error);
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerText = originalText;
                btn.disabled = false;
                alert("System Error during upload.");
            });
        });
    }
});

function showImportPreview(data) {
    const list = document.getElementById('import-list');
    const countSpan = document.getElementById('import-count');
    list.innerHTML = '';
    countSpan.innerText = data.length;

    data.forEach((item, index) => {
    const li = document.createElement('li');

    li.className = "flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded transition-colors group";
    
    li.innerHTML = `
        <div class="flex items-center gap-3 cursor-pointer flex-1 hover:border-[#e11d48]" onclick="viewImportDetail(${index})">
            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-black text-slate-600 group-hover:border-[#e11d48] group-hover:text-white">
                ${index + 1}
            </div>
            <div>
                <p class="text-xs font-black text-slate-800 uppercase">${item.name}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase">ID: ${item.id} | Amount: ${parseFloat(item.loan_amount).toLocaleString()}</p>
            </div>
        </div>
        <input type="checkbox" class="import-checkbox w-5 h-5 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer" value="${index}" checked>
    `;
    list.appendChild(li);
});

    const modal = document.getElementById('importPreviewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function viewImportDetail(index) {
    const item = importedData[index];
    const modal = document.getElementById('importDetailModal');

    document.getElementById('imp-id').innerText = item.id;
    document.getElementById('imp-name').innerText = item.name;
    document.getElementById('imp-contact').innerText = item.contact_number;
    document.getElementById('imp-region').innerText = item.region;
    document.getElementById('imp-amount').innerText = '₱ ' + parseFloat(item.loan_amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('imp-terms').innerText = item.terms + ' Months';

    const tbody = document.getElementById('imp-amort-rows');
    tbody.innerHTML = '';
    
    if (item.schedule && item.schedule.length > 0) {
        item.schedule.forEach(row => {
                tbody.innerHTML += `
                <tr class="border-b border-slate-200">
                    <td class="p-2 border-r border-slate-200 text-center">${row.installment_no}</td>
                    <td class="p-2 border-r border-slate-200 text-center">${row.date}</td>
                    <td class="p-2 border-r border-slate-200 text-right text-slate-500">${parseFloat(row.principal).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="p-2 border-r border-slate-200 text-right text-slate-500">${parseFloat(row.interest).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="p-2 border-r border-slate-200 font-bold text-black text-right">${parseFloat(row.total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="p-2 font-bold text-right text-[#ff3b30]">${parseFloat(row.balance).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });
    } else {
            tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center">No schedule available</td></tr>';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function finalizeImport() {
    const checkboxes = document.querySelectorAll('.import-checkbox:checked');
    if(checkboxes.length === 0) { alert("No records selected."); return; }

    const selectedIndices = Array.from(checkboxes).map(cb => parseInt(cb.value));
    const selectedBorrowers = selectedIndices.map(idx => importedData[idx]);

    if(!confirm(`Are you sure you want to save ${selectedBorrowers.length} borrowers to the database?`)) return;

    fetch(`${BASE_URL}/public/actions/save_import.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ borrowers: selectedBorrowers })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert(`Successfully imported ${data.imported_count} records!`);
            if(data.errors.length > 0) {
                alert("Some records failed:\n" + data.errors.join("\n"));
            }
            location.reload();
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("System Error during save.");
    });
}

function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.import-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}