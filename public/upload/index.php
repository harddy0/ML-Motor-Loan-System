<?php
require_once __DIR__ . '/../../src/includes/init.php'; 
$current_page = basename($_SERVER['PHP_SELF']);
  
  // Define the "Active" and "Inactive" styles as variables for cleaner code
  $active_style = "bg-[#e11d48] text-white border-transparent shadow-sm"; 
  $inactive_style = "bg-white text-slate-500 border-slate-200 hover:bg-slate-50 hover:border-slate-300";

?>

    <div class="flex flex-col lg:flex-row justify-between items-end mb-3 pb-2 shrink-0">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">
                UPLOAD <span class="text-[#ff3b30]">PAYROLL DEDUCTION</span>
            </h1>
        </div>
        <div class="flex items-center bg-white border border-slate-300 rounded-full shadow-sm overflow-hidden">
            <div class="px-4 py-2 border-r border-slate-100 flex items-center gap-3">
                <span class="text-[10px] font-black text-slate-400 uppercase">From</span>
                <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
            </div>
            <div class="px-4 py-2 flex items-center gap-3 border-r border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">To</span>
                <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 shrink-0">
        <div class="relative w-full md:w-1/2">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" placeholder="SEARCH NAME OR ID..." class="w-full pl-12 pr-4 py-3 bg-white border-2 border-slate-200 rounded-full text-xs font-bold outline-none uppercase placeholder:text-slate-300 transition-colors focus:border-[#ff3b30]">
        </div>
        <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-full border border-slate-100">
            <a href="index.php" 
            class="px-6 py-2 rounded-full text-[10px] font-bold uppercase transition-all duration-200 
            <?php echo ($current_page == 'index.php') ? $active_style : $inactive_style; ?>">
                Import
            </a>

            <a href="history.php" 
            class="px-6 py-2 rounded-full text-[10px] font-bold uppercase transition-all duration-200 
            <?php echo ($current_page == 'history.php') ? $active_style : $inactive_style; ?>">
                History
            </a>
        </div>
    </div>

    <div 
    id="dropZone"
    class="bg-[#eeeeee]/60 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center transition-all hover:bg-[#eeeeee]/80 flex-1 min-h-0 overflow-hidden py-4"
>
    <input 
        type="file" 
        id="fileInput" 
        accept=".xlsx, .xls, .csv" 
        class="hidden" 
        onchange="updateName(this)"
    >

    <div class="mb-4 shrink-0">
        <label for="fileInput" class="cursor-pointer">
            <h2 class="text-[#8a3333] font-black text-sm tracking-widest uppercase hover:underline text-center">
                DRAG&DROP FILE HERE OR <span class="underline">CHOOSE FILE</span>
            </h2>
        </label>
    </div>

    <div 
        onclick="document.getElementById('fileInput').click()"
        class="relative mb-4 scale-90 shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
    >
        <div class="w-24 h-32 bg-[#57b65f] rounded-lg relative shadow-md border-b-4 border-black/10 overflow-hidden">
            <div class="absolute top-0 right-0 w-8 h-8 bg-black/20 rounded-bl-lg"></div>
            <div class="mt-8 px-4 space-y-2">
                <div class="h-1 bg-white/40 rounded"></div>
                <div class="h-1 bg-white/40 rounded w-3/4"></div>
                <div class="h-1 bg-white/40 rounded"></div>
                <div class="h-1 bg-white/40 rounded w-1/2"></div>
            </div>
            <div class="absolute bottom-4 left-0 right-0 px-2">
                <div class="bg-[#f37a22] text-white font-black text-center py-1 rounded text-sm shadow-sm border border-black/5">XLS</div>
            </div>
        </div>
    </div>

    <div class="mb-6 text-center shrink-0">
        <p class="text-[#8a3333] text-sm font-black tracking-tight uppercase">
            Filename: 
            <span id="displayFileName" class="text-slate-800 font-black italic ml-1">
                No file selected
            </span>
        </p>
    </div>

    <div class="flex items-center gap-8 shrink-0">
        <button onclick="openImportModal()"   
       class="px-8 py-3 bg-[#e11d48] text-white rounded-full text-[10px] 
           font-black uppercase tracking-wider
           /* Focus on shadow and brightness rather than movement */
           shadow-sm hover:shadow-md hover:brightness-110
           /* Smooth out the scaling and remove the translate-y */
           transition-all duration-200 ease-in-out active:scale-95 active:shadow-inner">
        IMPORT</button>

        <button onclick="window.location.reload()"  
        class="px-8 py-3 bg-white/20 text-slate-500 border border-slate-200 rounded-full
        text-[10px] font-black uppercase hover:bg-slate-50 hover:border-slate-300 
        hover:text-slate-800 hover:shadow-sm transition-all duration-200 active:scale-95">
        CANCEL</button>
    </div>
</div>

    <div id="importPreviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-[#eeeeee] w-full max-w-5xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
        
        <div class="p-6 overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
                <thead>
                    <tr class="text-[#8a3333] font-black text-[10px] uppercase tracking-wider">
                        <th class="px-4 py-3 text-center border-b">IDNO</th>
                        <th class="px-4 py-3 text-center border-b">PAYROLL DATE</th>
                        <th class="px-4 py-3 text-center border-b">FIRST NAME</th>
                        <th class="px-4 py-3 text-center border-b">LAST NAME</th>
                        <th class="px-4 py-3 text-center border-b">AMOUNT PAID</th>
                        <th class="px-4 py-3 text-center border-b">REGION</th>
                    </tr>
                </thead>
                <tbody id="preview-body" class="text-[11px] font-bold text-slate-700">
                    <?php 
                    $preview_data = [
                        ['id' => '20150428', 'date' => '01/30/2026', 'fname' => 'REMARIM', 'lname' => 'CLARISA', 'amount' => '3825', 'region' => 'Head Office'],
                        ['id' => '20190617', 'date' => '01/30/2026', 'fname' => 'GOZON JR', 'lname' => 'FRANCIS', 'amount' => '1585', 'region' => 'Head Office'],
                        ['id' => '20230445', 'date' => '01/30/2026', 'fname' => 'DE GUZMAN', 'lname' => 'EA', 'amount' => '3570', 'region' => 'Head Office'],
                        ['id' => '20240031', 'date' => '01/30/2026', 'fname' => 'AMPIS', 'lname' => 'MIKAELA', 'amount' => '2463', 'region' => 'Head Office'],
                        ['id' => '20240158', 'date' => '01/30/2026', 'fname' => 'SUPAN', 'lname' => 'JENELY', 'amount' => '2958', 'region' => 'Head Office'],
                        ['id' => '20240242', 'date' => '01/30/2026', 'fname' => 'GENESE', 'lname' => 'MARITES', 'amount' => '4175', 'region' => 'Head Office'],
                        ['id' => '20240675', 'date' => '01/30/2026', 'fname' => 'QUIAMBAO', 'lname' => 'ERWIN', 'amount' => '2758', 'region' => 'Head Office'],
                    ];
                    foreach($preview_data as $row): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2 border-x text-center bg-yellow-100/50"><?= $row['id'] ?></td>
                        <td class="px-4 py-2 border-x text-center"><?= $row['date'] ?></td>
                        <td class="px-4 py-2 border-x bg-yellow-100/50 uppercase"><?= $row['fname'] ?></td>
                        <td class="px-4 py-2 border-x uppercase"><?= $row['lname'] ?></td>
                        <td class="px-4 py-2 border-x text-center bg-yellow-100/50 font-black italic"><?= $row['amount'] ?></td>
                        <td class="px-4 py-2 border-x text-center"><?= $row['region'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-6 pt-0 flex justify-end gap-4">
            <button 
                onclick="showSuccessAlert()" 
               class="px-8 py-3 bg-[#e11d48] text-white rounded-full text-[10px] 
                font-black uppercase tracking-wider
                /* Focus on shadow and brightness rather than movement */
                shadow-sm hover:shadow-md hover:brightness-110
                /* Smooth out the scaling and remove the translate-y */
                transition-all duration-200 ease-in-out active:scale-95 active:shadow-inner">
                PROCEED
            </button>

            <button onclick="closeImportModal()" 
            class="px-8 py-3 bg-white/20 text-slate-500 border border-slate-200 rounded-full
            text-[10px] font-black uppercase hover:bg-slate-50 hover:border-slate-300 
            hover:text-slate-800 hover:shadow-sm transition-all duration-200 active:scale-95">
            Cancel</button>
        </div>
    </div>
    </div>

    <div id="successAlertModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-10 relative overflow-hidden border border-slate-100">
        
        <div class="flex justify-center mb-6">
            <div class="bg-green-100 p-4 rounded-full">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <div class="text-center mb-8">
            <h3 class="text-slate-800 font-bold text-lg mb-2">Validation Successful</h3>
            <p class="text-slate-500 text-sm leading-relaxed">
                The deduction report has been validated against the ledger and reflected in the running accounts receivable.
            </p>
        </div>

        <div class="flex justify-center">
            <button onclick="closeAllModals()" 
            class="w-full max-w-[120px] py-3 bg-[#e11d48] text-white rounded-full text-[10px] 
            font-black uppercase tracking-widest
            shadow-sm hover:brightness-110
            transition-all duration-200 ease-in-out active:scale-95">
                OK
            </button>
        </div>
    </div>
    </div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileNameDisplay = document.getElementById('displayFileName');

// 1. Prevent browser from downloading file on drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

// 2. Visual feedback when dragging over
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.add('bg-[#eeeeee]/90', 'border-[#8a3333]'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.remove('bg-[#eeeeee]/90', 'border-[#8a3333]'), false);
});

// 3. Handle dropped files
dropZone.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files.length) {
        fileInput.files = files; // Sync drop with the hidden input
        updateName(fileInput);
    }
});

// 4. Standard update function
function updateName(input) {
    if (input.files && input.files[0]) {
        fileNameDisplay.innerText = input.files[0].name;
    } else {
        fileNameDisplay.innerText = 'No file selected';
    }
}

function openImportModal() {
    if (!fileInput.files.length) {
        alert("Please select or drop a file first.");
        return;
    }
    const modal = document.getElementById('importPreviewModal');
    modal.classList.replace('hidden', 'flex');
}

function closeImportModal() {
    document.getElementById('importPreviewModal').classList.replace('flex', 'hidden');
}

function showSuccessAlert() {
    // Hide the first preview modal
    const previewModal = document.getElementById('importPreviewModal');
    previewModal.classList.replace('flex', 'hidden');

    // Show the success alert modal
    const alertModal = document.getElementById('successAlertModal');
    alertModal.classList.remove('hidden');
    alertModal.classList.add('flex');
}

function closeAllModals() {
    // Close the success alert
    document.getElementById('successAlertModal').classList.replace('flex', 'hidden');
    
    // Reset the upload area
    document.getElementById('fileInput').value = "";
    document.getElementById('displayFileName').innerText = "No file selected";
}
</script>