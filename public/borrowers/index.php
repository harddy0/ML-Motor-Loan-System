<?php
$pageTitle = "BORROWERS INFORMATION";
$currentPage = "borrowers";
require_once __DIR__ . '/../../src/includes/init.php'; 

// --- MOCK DATA (Restored Full Array) ---
$mock_borrowers = [
    [
        'id' => 'ML1234567', 
        'name' => 'CLARISA REMARIM', 
        'date' => '12 / 15 / 2025', 
        'region' => 'HEAD OFFICE',
        'first_name' => 'CLARISA', 
        'last_name' => 'REMARIM', 
        'contact' => '0917-123-4567', 
        'pn_no' => 'PN-88901',
        'pn_maturity' => '12 / 15 / 2028', 
        'loan_amount' => '250,000.00', 
        'terms' => '36', 
        'deduction' => '3,185.00'
    ],
    [
        'id' => 'ML7654321', 
        'name' => 'JUAN DELA CRUZ', 
        'date' => '01 / 20 / 2026', 
        'region' => 'DAVAO CITY',
        'first_name' => 'JUAN', 
        'last_name' => 'DELA CRUZ', 
        'contact' => '0918-999-0000', 
        'pn_no' => 'PN-77210',
        'pn_maturity' => '01 / 20 / 2029', 
        'loan_amount' => '120,000.00', 
        'terms' => '24', 
        'deduction' => '5,000.00'
    ],
];
?>

<div class="flex flex-col lg:flex-row justify-between items-end mb-8 pb-4 border-b-2 border-slate-200">
    <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">
            Borrower <span class="text-[#ff3b30]">Information</span>
        </h1>
        <p class="text-slate-500 text-[11px] font-bold uppercase tracking-widest mt-1">Official Registry</p>
    </div>
    
    <div class="flex items-center bg-white border-2 border-slate-200 rounded shadow-sm overflow-hidden">
        <div class="px-4 py-2 border-r border-slate-100 flex items-center gap-3">
            <span class="text-[10px] font-black text-slate-400 uppercase">From</span>
            <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
        </div>
        <div class="px-4 py-2 flex items-center gap-3 border-r border-slate-100">
            <span class="text-[10px] font-black text-slate-400 uppercase">To</span>
            <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
        </div>
        <button class="bg-[#ff3b30] hover:bg-red-700 text-white px-6 py-2 text-[10px] font-black uppercase transition-all">
            Filter
        </button>
    </div>
</div>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div class="relative w-full md:w-1/2">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" placeholder="SEARCH NAME OR ID..." class="w-full pl-12 pr-4 py-3 bg-white border-2 border-slate-200 rounded-full text-xs font-bold outline-none uppercase placeholder:text-slate-300 transition-colors focus:border-[#ff3b30]">
    </div>
    
    <div class="flex items-center gap-3">
        <button onclick="openImportModal()" class="px-6 py-3 bg-[#ff3b30] text-white rounded text-[10px] font-black uppercase shadow-md hover:bg-red-700 transition-all">
            Import File
        </button>
        <button onclick="openAddModal()" class="px-6 py-3 bg-[#ff3b30] text-white rounded text-[10px] font-black uppercase shadow-md hover:bg-red-700 transition-all">
            Add Borrower
        </button>
    </div>
</div>

<div class="bg-white rounded border-2 border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 border-b-2 border-slate-200">
                <th class="px-6 py-4 text-[11px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50">ID</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50">Full Name</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-600 uppercase border-r-2 border-slate-200/50 text-center">Date Granted</th>
                <th class="px-6 py-4 text-[11px] font-black text-slate-600 uppercase text-center">Branch</th>
            </tr>
        </thead>
        <tbody class="divide-y-2 divide-slate-100">
            <?php 
            foreach ($mock_borrowers as $borrower): 
                $safe_data = htmlspecialchars(json_encode($borrower), ENT_QUOTES, 'UTF-8');
            ?>
            <tr onclick='openViewModal(<?= $safe_data ?>)' 
                class="hover:bg-red-50 transition-colors cursor-pointer group border-b border-slate-100">
                <td class="px-6 py-4 text-xs font-bold text-slate-500 border-r-2 border-slate-100"><?= $borrower['id'] ?></td>
                <td class="px-6 py-4 text-xs font-black text-slate-800 uppercase border-r-2 border-slate-100 group-hover:text-[#ff3b30]"><?= $borrower['name'] ?></td>
                <td class="px-6 py-4 text-xs font-bold text-slate-500 border-r-2 border-slate-100 text-center"><?= $borrower['date'] ?></td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-block px-3 py-1 bg-slate-800 text-white text-[9px] font-black uppercase rounded">
                        <?= $borrower['region'] ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include dirname(__DIR__) . '/../src/includes/modals/view_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/add_borrower.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/amortization_schedule.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_borrowers.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_preview.php'; ?>
<?php include dirname(__DIR__) . '/../src/includes/modals/import_detail.php'; ?>

<script>
    // Global variable to hold temporary data between modals
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
        document.getElementById('m-amount').innerText = '₱ ' + data.loan_amount;
        document.getElementById('m-terms').innerText = data.terms;
        document.getElementById('m-deduct').innerText = '₱ ' + data.deduction;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // --- ADD / CREATE LOGIC ---
    function openAddModal() {
        const modal = document.getElementById('addBorrowerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('addBorrowerForm').reset();
    }

    function validateAndShowSchedule() {
        const form = document.getElementById('addBorrowerForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        tempBorrowerData = Object.fromEntries(formData.entries());

        // Populate Amortization Summary
        document.getElementById('sched-name').innerText = (tempBorrowerData.first_name + ' ' + tempBorrowerData.last_name).toUpperCase();
        document.getElementById('sched-contact').innerText = tempBorrowerData.contact_number;
        document.getElementById('sched-pn').innerText = tempBorrowerData.pn_number;
        document.getElementById('sched-amount').innerText = parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('sched-date').innerText = tempBorrowerData.loan_granted;
        document.getElementById('sched-terms').innerText = tempBorrowerData.terms + ' Months';
        document.getElementById('sched-maturity').innerText = tempBorrowerData.pn_maturity;
        document.getElementById('sched-deduct').innerText = parseFloat(tempBorrowerData.deduction).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('sched-initial-bal').innerText = parseFloat(tempBorrowerData.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});

        generateMockAmortizationRows(tempBorrowerData.loan_amount, tempBorrowerData.terms, tempBorrowerData.deduction);

        closeModal('addBorrowerModal');
        const schedModal = document.getElementById('amortizationModal');
        schedModal.classList.remove('hidden');
        schedModal.classList.add('flex');
    }

    function generateMockAmortizationRows(principal, terms, deduction) {
        const tbody = document.getElementById('amortization-rows');
        tbody.innerHTML = ''; 

        let balance = parseFloat(principal);
        const termCount = parseInt(terms) * 2; // Semi-monthly payments
        // Logic check: The user input 'Terms' is usually MONTHS. But payments are semi-monthly.
        // If Terms = 36 months, then Total Payments = 72.
        
        // Simple interest split for display
        const totalPrincipal = parseFloat(principal);
        const monthlyPrincipal = totalPrincipal / parseInt(terms);
        const semiPrincipal = monthlyPrincipal / 2;
        
        // Deduction is "per payday" (semi-monthly)
        // Interest = Deduction - Principal
        const semiInterest = parseFloat(deduction) - semiPrincipal;
        
        // Limit display rows to 24 for the preview
        const limit = Math.min(termCount, 24); 
        
        let currentDate = new Date(tempBorrowerData.loan_granted);

        for(let i=1; i<=limit; i++) {
            balance = balance - semiPrincipal;
            if (balance < 0) balance = 0;

            // Simple date increment logic (just adding 15 days for mock preview)
            currentDate.setDate(currentDate.getDate() + 15);
            let dateStr = currentDate.toISOString().split('T')[0];

            const tr = document.createElement('tr');
            tr.className = "hover:bg-yellow-50 border-b border-slate-200 transition-colors";
            tr.innerHTML = `
                <td class="p-2 border-r border-slate-200 text-center">${i}</td>
                <td class="p-2 border-r border-slate-200 text-center">${dateStr}</td>
                <td class="p-2 border-r border-slate-200 text-right">${semiPrincipal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="p-2 border-r border-slate-200 text-right">${semiInterest.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="p-2 border-r border-slate-200 font-bold text-black text-right">${parseFloat(deduction).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="p-2 font-bold text-right">${balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            tbody.appendChild(tr);
        }
    }

    function submitFinalBorrower() {
        console.log("FINAL SUBMISSION PAYLOAD:", tempBorrowerData);
        
        // Prepare FormData
        const formData = new FormData();
        for (const key in tempBorrowerData) {
            formData.append(key, tempBorrowerData[key]);
        }

        // Send to Backend
        fetch('<?= BASE_URL ?>/public/actions/create_borrower.php', {
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

    // 1. Submit File -> Parse -> Show Preview
    document.getElementById('importBorrowerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('file-upload');
        if(fileInput.files.length === 0) { alert("Please select a file."); return; }
        
        // MOCK: Simulate parsing 3 records
        importedData = [
            { id: 'ML-IMP-001', name: 'MARIA CLARA', contact: '09170000001', region: 'CEBU', amount: 50000, terms: 12, deduction: 4500 },
            { id: 'ML-IMP-002', name: 'JOSE RIZAL', contact: '09170000002', region: 'MANILA', amount: 100000, terms: 24, deduction: 4800 },
            { id: 'ML-IMP-003', name: 'ANDRES BONIFACIO', contact: '09170000003', region: 'DAVAO', amount: 75000, terms: 18, deduction: 4600 }
        ];

        closeModal('importBorrowerModal');
        showImportPreview(importedData);
    });

    // 2. Render Preview List
    function showImportPreview(data) {
        const list = document.getElementById('import-list');
        const countSpan = document.getElementById('import-count');
        list.innerHTML = '';
        countSpan.innerText = data.length;

        data.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = "flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded hover:border-[#ff3b30] transition-colors group";
            li.innerHTML = `
                <div class="flex items-center gap-3 cursor-pointer flex-1" onclick="viewImportDetail(${index})">
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-black text-slate-600 group-hover:bg-[#ff3b30] group-hover:text-white">
                        ${index + 1}
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-800 uppercase">${item.name}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">ID: ${item.id} | Amount: ${item.amount.toLocaleString()}</p>
                    </div>
                </div>
                <input type="checkbox" class="import-checkbox w-5 h-5 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer" checked>
            `;
            list.appendChild(li);
        });

        const modal = document.getElementById('importPreviewModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // 3. View Detail of Imported Item
    function viewImportDetail(index) {
        const item = importedData[index];
        const modal = document.getElementById('importDetailModal');

        // Populate Info
        document.getElementById('imp-id').innerText = item.id;
        document.getElementById('imp-name').innerText = item.name;
        document.getElementById('imp-contact').innerText = item.contact;
        document.getElementById('imp-region').innerText = item.region;
        document.getElementById('imp-amount').innerText = '₱ ' + item.amount.toLocaleString();
        document.getElementById('imp-terms').innerText = item.terms + ' Months';

        // Generate Mock Amortization Table for Import Detail
        const tbody = document.getElementById('imp-amort-rows');
        tbody.innerHTML = '';
        let balance = item.amount;
        const principalPart = (balance / item.terms).toFixed(2);
        
        for(let i=1; i<=Math.min(item.terms, 6); i++) { // Show max 6 rows for preview
            balance -= principalPart;
            const interest = (item.deduction - principalPart).toFixed(2);
            tbody.innerHTML += `
                <tr class="border-b border-slate-200">
                    <td class="p-2 border-r border-slate-200 text-center">${i}</td>
                    <td class="p-2 border-r border-slate-200 text-center">--/--</td>
                    <td class="p-2 border-r border-slate-200">${parseFloat(principalPart).toLocaleString()}</td>
                    <td class="p-2 border-r border-slate-200">${parseFloat(interest).toLocaleString()}</td>
                    <td class="p-2 border-r border-slate-200 font-bold">${item.deduction.toLocaleString()}</td>
                    <td class="p-2 font-bold">${balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // 4. Finalize Import
    function finalizeImport() {
        const checkboxes = document.querySelectorAll('.import-checkbox:checked');
        const count = checkboxes.length;
        if(count === 0) { alert("No records selected."); return; }

        alert(`Successfully imported ${count} records into the database!`);
        closeModal('importPreviewModal');
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
</script>