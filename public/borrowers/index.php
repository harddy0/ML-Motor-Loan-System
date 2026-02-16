<?php
$pageTitle = "BORROWERS INFORMATION";
$currentPage = "borrowers";
require_once __DIR__ . '/../../src/includes/init.php'; 
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
        <input type="text" placeholder="SEARCH NAME OR ID..." class="w-full pl-11 pr-4 py-3 bg-white border-2 border-slate-200 rounded text-xs font-bold outline-none uppercase placeholder:text-slate-300">
    </div>
    
    <div class="flex items-center gap-3">
        <button class="px-5 py-3 bg-white border-2 border-slate-200 text-slate-700 rounded text-[10px] font-black uppercase hover:bg-slate-50">Import File</button>
        <button class="px-6 py-3 bg-[#ff3b30] text-white rounded text-[10px] font-black uppercase shadow-md hover:bg-red-700 transition-all">Add Borrower</button>
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
            // This is your MOCK DATA. Later, this will be your "foreach ($database_results as $row)"
            $mock_borrowers = [
                [
                    'id' => 'ML1234567', 'name' => 'CLARISA REMARIM', 'date' => '12 / 15 / 2025', 'region' => 'HEAD OFFICE',
                    'first_name' => 'CLARISA', 'last_name' => 'REMARIM', 'contact' => '0917-123-4567', 'pn_no' => 'PN-88901',
                    'pn_maturity' => '12 / 15 / 2028', 'loan_amount' => '250,000.00', 'terms' => '36', 'deduction' => '3,185.00'
                ],
                [
                    'id' => 'ML7654321', 'name' => 'JUAN DELA CRUZ', 'date' => '01 / 20 / 2026', 'region' => 'DAVAO CITY',
                    'first_name' => 'JUAN', 'last_name' => 'DELA CRUZ', 'contact' => '0918-999-0000', 'pn_no' => 'PN-77210',
                    'pn_maturity' => '01 / 20 / 2029', 'loan_amount' => '120,000.00', 'terms' => '24', 'deduction' => '5,000.00'
                ],
            ];

            foreach ($mock_borrowers as $borrower): 
                // We encode the whole borrower array into a safe format for JavaScript
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

<script>
/**
 * DYNAMIC MODAL HANDLER
 * This script takes the row data and pushes it into the modal IDs
 */
function openViewModal(data) {
    const modal = document.getElementById('viewBorrowerModal');
    
    // Personal & Contact Info
    document.getElementById('m-id').innerText = data.id;
    document.getElementById('m-fname').innerText = data.first_name;
    document.getElementById('m-lname').innerText = data.last_name;
    document.getElementById('m-date').innerText = data.date;
    document.getElementById('m-contact').innerText = data.contact;
    document.getElementById('m-pn-no').innerText = data.pn_no;
    document.getElementById('m-pn-mat').innerText = data.pn_maturity;
    document.getElementById('m-region').innerText = data.region;
    
    // Financials
    document.getElementById('m-amount').innerText = '₱ ' + data.loan_amount;
    document.getElementById('m-terms').innerText = data.terms;
    document.getElementById('m-deduct').innerText = '₱ ' + data.deduction;

    // Show the modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>