<?php
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<style>
    .no-scrollbar { -ms-overflow-style:none; scrollbar-width:none; }
    .no-scrollbar::-webkit-scrollbar { display:none; }

    /* Hide the native month picker button — the whole row is clickable */
    input[type="month"] { color-scheme: light; }
    input[type="month"]::-webkit-calendar-picker-indicator {
        opacity: 0; position: absolute; inset: 0; width: 100%; cursor: pointer;
    }
</style>

<div class="mb-3 pb-2 shrink-0 -mt-4">
    <h1 class="text-2xl text-slate-800">Upload Payroll Deduction</h1>
</div>

<!-- Two-column layout: 40% provision panel (left) | 60% file dropzone (right) -->
<div class="flex flex-col lg:flex-row gap-6 mb-10">
    
    <!-- Left Column: Provision Assumed Payments (40% width) -->
    <?php if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])): ?>
    <div class="lg:w-[40%] bg-white rounded-2xl border-2 border-slate-200 shadow-sm flex flex-col p-8 min-h-[430px]">
        <div class="flex items-center gap-3 mb-4">
            <svg class="w-6 h-6 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <h2 class="text-xl font-black text-slate-800">Provision Assumed Payments</h2>
        </div>
        
        <div class="flex-1 mb-6">
            <p class="text-slate-600 text-sm leading-relaxed mb-4">
                Create expected payment entries for forecasted Accounts Receivable reporting before the official payroll file arrives.
            </p>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <p class="text-xs text-slate-500 leading-relaxed">
                    <span class="font-bold text-slate-700">Note:</span> Eligible loans for the selected period will be marked as <span class="font-black text-[#ce1126]">ASSUMED</span>.
                </p>
            </div>
        </div>
        
        <button type="button" onclick="openAssumeModal()" 
            class="w-full px-6 py-3 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-xl font-black text-sm uppercase tracking-wider shadow-md hover:shadow-lg transition-all duration-300 active:scale-95 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Provision Payments
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Right Column: File Dropzone (60% width) -->
    <div id="dropZone"
         class="<?php echo (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])) ? 'lg:w-[60%]' : 'w-full'; ?> bg-white rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center
                transition-all hover:border-slate-500 hover:bg-slate-50/50
                shadow-sm flex-1 min-h-[430px] overflow-hidden no-scrollbar">

    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" class="hidden" onchange="updateName(this)">

    <div onclick="document.getElementById('fileInput').click()" class="relative mb-6 cursor-pointer group">
        <div class="w-20 h-24 bg-slate-50 rounded-xl relative border-2 border-slate-200 overflow-hidden group-hover:border-green-500 group-hover:bg-white transition-all duration-300">
            <div class="absolute top-0 right-0 w-6 h-6 bg-slate-200 rounded-bl-lg group-hover:bg-green-500 transition-colors"></div>
            <div class="absolute bottom-0 left-0 right-0 h-8 bg-slate-100 flex items-center justify-center group-hover:bg-green-500 transition-colors">
                <span class="font-black text-slate-400 group-hover:text-white text-sm">XLSX</span>
            </div>
            <div class="mt-8 px-4 space-y-2">
                <div class="h-1 bg-slate-200 rounded w-full"></div>
                <div class="h-1 bg-slate-200 rounded w-3/4"></div>
                <div class="h-1 bg-slate-100 rounded w-full"></div>
            </div>
        </div>
    </div>

    <div class="mb-2 shrink-0">
        <label for="fileInput" class="cursor-pointer">
            <h2 class="text-black text-center">
                Drag & Drop file here or <span class="text-[#1d7fe1] hover:underline">Choose File</span>
            </h2>
        </label>
        <p class="text-slate-400 text-center text-[10px] mt-1">.XLSX, .XLS, .CSV</p>
    </div>

    <div class="mb-8 text-center shrink-0">
        <div class="inline-flex items-center bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
            <span class="text-slate-400 text-[12px] mr-2">File:</span>
            <span id="displayFileName" class="text-[#ce1126] text-[13px]">No file selected</span>
        </div>
    </div>

    <div id="buttonContainer" class="hidden flex items-center gap-4 shrink-0">
        <button onclick="window.location.reload()"
            class="px-4 py-1 bg-white text-slate-400 border border-slate-200 rounded-full hover:bg-slate-50 hover:text-slate-600 hover:shadow-sm transition-all duration-200 active:scale-95">
            cancel
        </button>
        <button onclick="openDateSelectorModal()"
            class="px-4 py-1 bg-[#ce1126] text-white rounded-full shadow-sm hover:shadow-lg hover:brightness-110 transition-all duration-200 active:scale-95">
            Import
        </button>
    </div>
</div>


<div id="dateSelectorModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col"
         style="max-height: min(680px, 92vh);">

        <div class="bg-[#ce1126] px-6 py-4 flex items-start justify-between rounded-t-2xl shrink-0">
            <div>
                <p class="text-white font-black text-base tracking-wide">Select Payroll Date</p>
            </div>
            <button onclick="closeModal('dateSelectorModal')" class="text-white/60 hover:text-white transition-colors mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 p-6 space-y-5">

            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Payroll Month</label>
                <div class="relative"
                     onclick="const p=document.getElementById('dsMonthPicker'); try{p.showPicker();}catch(e){p.click();}">
                    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 hover:border-slate-400 rounded-xl px-4 py-3 cursor-pointer transition-all">
                        <svg class="w-5 h-5 text-slate-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input type="month" id="dsMonthPicker"
                               class="flex-1 bg-transparent outline-none text-slate-800 font-bold text-sm cursor-pointer relative z-10"
                               onchange="updateEomLabel(); const c=document.querySelector('input[name=dsPayrollHalf]:checked'); if(c) onHalfSelected(c.value);">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Payroll Due Date</label>
                <div class="grid grid-cols-2 gap-3">

                    <label class="relative cursor-pointer">
                        <input type="radio" name="dsPayrollHalf" value="15" class="sr-only peer"
                               onchange="onHalfSelected('15')">
                        <div class="flex flex-col items-center justify-center gap-1 border-2 border-slate-200 rounded-xl px-4 py-4
                                    peer-checked:border-[#ce1126] peer-checked:bg-red-50
                                    hover:border-slate-400 transition-all duration-150 select-none">
                            <span class="text-3xl font-black text-slate-700 leading-none peer-checked:text-[#ce1126]">15th</span>
                    
                        </div>
                        <span class="absolute top-2 right-2 w-3.5 h-3.5 rounded-full border-2 border-slate-300
                                     peer-checked:border-[#ce1126] peer-checked:bg-[#ce1126] transition-all
                                     flex items-center justify-center">
                        </span>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="dsPayrollHalf" value="30" class="sr-only peer"
                               onchange="onHalfSelected('30')">
                        <div class="flex flex-col items-center justify-center gap-1 border-2 border-slate-200 rounded-xl px-4 py-4
                                    peer-checked:border-[#ce1126] peer-checked:bg-red-50
                                    hover:border-slate-400 transition-all duration-150 select-none">
                            <span id="dsEomLabel" class="text-3xl font-black text-slate-700 leading-none">30th</span>
                           
                        </div>
                        <span class="absolute top-2 right-2 w-3.5 h-3.5 rounded-full border-2 border-slate-300
                                     peer-checked:border-[#ce1126] peer-checked:bg-[#ce1126] transition-all
                                     flex items-center justify-center">
                        </span>
                    </label>

                </div>
            </div>

            <div id="dsChosenSummary" class="hidden items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <div>
                    <p class="text-[10px] text-green-600 font-black uppercase tracking-widest">Selected Payroll Due Date</p>
                    <p id="dsChosenDateText" class="text-sm text-green-800 font-black mt-0.5"></p>
                </div>
            </div>

            <p id="dsError" class="hidden text-[12px] text-red-600 font-bold bg-red-50 border border-red-200 rounded-lg px-3 py-2"></p>

        </div>

        <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 shrink-0">
            <button onclick="closeModal('dateSelectorModal')"
                class="px-5 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-full font-black
                    hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-95 text-sm">
                Cancel
            </button>
            <button id="dsProceedBtn" onclick="proceedToPreview()" disabled
                class="px-5 py-1.5 bg-slate-200 text-slate-400 rounded-full font-black cursor-not-allowed transition-all duration-200 text-sm">
                Upload
            </button>
        </div>

    </div>
</div>


<div id="importPreviewModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-[#f1f1f1] w-full max-w-8xl rounded-xl shadow-2xl flex flex-col"
         style="max-height: min(88vh, 860px);">

        <div class="bg-white px-6 py-4 rounded-t-xl shrink-0 border-b border-slate-200 space-y-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-slate-800 font-black text-base tracking-wide">Preview</p>
                    <p class="text-slate-500 text-sm mt-0.5">
                        Selected Payroll Due Date:
                        <span id="previewChosenDate" class="text-slate-800 font-black ml-1"></span>
                    </p>
                </div>
                <p id="previewStats" class="text-[13px] text-right shrink-0 mt-0.5"></p>
            </div>
            <div id="previewMatchMsg" class="hidden text-[12px]"></div>
        </div>

        <div class="overflow-auto flex-1 p-4">
            <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-[#ce1126] text-white">
                        <th class="px-3 py-1 text-[16px] font-black text-center border-r border-red-700 whitespace-nowrap">Employee ID</th>
                        <th class="px-3 py-1 text-[16px] font-black text-center border-r border-red-700 whitespace-nowrap">Due Date</th>
                        <th class="px-3 py-1 text-[16px] font-black border-r border-red-700">First Name</th>
                        <th class="px-3 py-1 text-[16px] font-black border-r border-red-700">Last Name</th>
                        <th class="px-3 py-1 text-[16px] font-black text-right border-r border-red-700">Amount</th>
                        <th class="px-3 py-1 text-[16px] font-black text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody id="preview-body" class="text-slate-800 divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <div class="bg-white/80 px-6 py-4 rounded-b-xl border-t border-slate-200 flex justify-end gap-3 shrink-0">
            <button onclick="closeImportModal()"
                class="px-5 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-full font-black
                       hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-95 text-sm">
               Cancel
            </button>
            <button id="proceedImportBtn" onclick="processImport()" disabled
                class="px-5 py-1.5 bg-slate-200 text-slate-400 rounded-full font-black cursor-not-allowed transition-all duration-200 text-sm">
                Save
            </button>
        </div>

    </div>
</div>


<div id="importResultsModal"
     class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-white w-1/2 max-w-1xl rounded-2xl shadow-2xl flex flex-col"
         style="max-height: min(80vh, 700px);">

        <div class="overflow-y-auto flex-1 p-8">

            <div class="text-center mb-2">
                <div id="result-icon-container" class="inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 id="result-title" class="text-slate-800 font-black text-xl">Upload Complete</h3>
                <p id="result-subtitle" class="text-slate-500 text-xs font-bold mt-1">Successfully processed 0 records.</p>
            </div>

            <div id="result-details-container" class="hidden bg-slate-50 rounded-xl p-5 border border-slate-200">
                <h4 class="font-black text-slate-400 mb-3 text-[11px] uppercase tracking-widest">Notices & Errors</h4>
                <ul id="result-issues-list" class="space-y-2"></ul>
            </div>

        </div>

        <div class="px-8 pb-8 shrink-0 flex justify-center">
            <button onclick="window.location.reload()"
                class="px-10 py-3 bg-[#ce1126] hover:bg-[#be123c] text-white rounded-full font-black shadow-md transition-all duration-200">
                Close
            </button>
        </div>

    </div>
</div>

<?php if (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['ADMIN', 'REVIEWER'])): ?>
<div id="assumePaymentsModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col"
         style="max-height: min(680px, 92vh);">

        <div class="bg-white border-b border-slate-100 px-6 py-4 flex items-start justify-between rounded-t-2xl shrink-0">
            <div>
                <p class="text-slate-800 font-black text-base tracking-wide flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Provision Payments
                </p>
            </div>
            <button onclick="closeModal('assumePaymentsModal')" class="text-slate-400 hover:text-slate-800 transition-colors mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto flex-1 p-6 space-y-5">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                <p class="text-[12.5px] text-slate-600 leading-relaxed font-medium">
                    This action provisions expected payments for the selected cutoff. All eligible active loans with an unpaid status for this period will be temporarily designated as <span class="font-black text-[#ce1126]">ASSUMED</span>. This enables accurate, forecasted Accounts Receivable reporting prior to the receipt of the official payroll deduction file.
                </p>
            </div>

            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Select Provisioning Period</label>
                <div id="assumePeriodCards" class="grid grid-cols-1 gap-3">
                    </div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3 shrink-0 rounded-b-2xl">
            <button onclick="closeModal('assumePaymentsModal')"
                class="px-5 py-2 bg-white text-slate-500 border border-slate-200 rounded-full font-black text-sm
                    hover:bg-slate-100 hover:text-slate-700 transition-all duration-200 active:scale-95">
                Cancel
            </button>
            <button id="btnSubmitAssume" onclick="submitAssumePayments()"
                class="px-5 py-2 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-full font-black text-sm shadow-md transition-all duration-200 active:scale-95">
                Proceed & Provision
            </button>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- Error Modal -->
<div id="errorModal" role="dialog" aria-modal="true" aria-labelledby="errorModalTitle"
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col transform scale-95 transition-transform duration-300">
        <div class="p-8 text-center">
            <div class="inline-flex bg-red-100 p-4 rounded-full mb-4 shadow-sm" aria-hidden="true">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h3 id="errorModalTitle" class="text-slate-800 font-black text-xl mb-2">Error</h3>
            <p id="errorModalMessage" class="text-slate-600 text-sm leading-relaxed"></p>
        </div>
        <div class="px-8 pb-8 flex justify-center">
            <button onclick="closeErrorModal()" aria-label="Close error dialog"
                class="px-10 py-3 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-full font-black shadow-md transition-all duration-200 active:scale-95">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Warning Modal -->
<div id="warningModal" role="dialog" aria-modal="true" aria-labelledby="warningModalTitle"
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col transform scale-95 transition-transform duration-300">
        <div class="p-8 text-center">
            <div class="inline-flex bg-amber-100 p-4 rounded-full mb-4 shadow-sm" aria-hidden="true">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 id="warningModalTitle" class="text-slate-800 font-black text-xl mb-2">Warning</h3>
            <p id="warningModalMessage" class="text-slate-600 text-sm leading-relaxed"></p>
        </div>
        <div class="px-8 pb-8 flex justify-center">
            <button onclick="closeWarningModal()" aria-label="Close warning dialog"
                class="px-10 py-3 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-full font-black shadow-md transition-all duration-200 active:scale-95">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" role="dialog" aria-modal="true" aria-labelledby="successModalTitle"
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col transform scale-95 transition-transform duration-300">
        <div class="p-8 text-center">
            <div class="inline-flex bg-green-100 p-4 rounded-full mb-4 shadow-sm" aria-hidden="true">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 id="successModalTitle" class="text-slate-800 font-black text-xl mb-2">Success</h3>
            <p id="successModalMessage" class="text-slate-600 text-sm leading-relaxed"></p>
        </div>
        <div class="px-8 pb-8 flex justify-center">
            <button onclick="closeSuccessModal()" aria-label="Close success dialog"
                class="px-10 py-3 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-full font-black shadow-md transition-all duration-200 active:scale-95">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle"
     class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col transform scale-95 transition-transform duration-300">
        <div class="p-8 text-center">
            <div class="inline-flex bg-blue-100 p-4 rounded-full mb-4 shadow-sm" aria-hidden="true">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 id="confirmModalTitle" class="text-slate-800 font-black text-xl mb-2">Confirm Action</h3>
            <p id="confirmModalMessage" class="text-slate-600 text-sm leading-relaxed"></p>
        </div>
        <div class="px-8 pb-8 flex justify-center gap-3">
            <button onclick="closeConfirmModal(false)" aria-label="Cancel action"
                class="px-8 py-3 bg-white text-slate-500 border border-slate-200 rounded-full font-black hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-95">
                No
            </button>
            <button onclick="closeConfirmModal(true)" aria-label="Confirm action"
                class="px-8 py-3 bg-[#ce1126] hover:bg-[#b00e20] text-white rounded-full font-black shadow-md transition-all duration-200 active:scale-95">
                Yes
            </button>
        </div>
    </div>
</div>

<script src="../../assets/js/upload.js?v=<?php echo time(); ?>"></script>