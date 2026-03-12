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

<!-- PAGE HEADER -->
<div class="flex flex-col lg:flex-row justify-between items-end mb-3 pb-2 shrink-0 -mt-4">
    <h1 class="text-2xl text-slate-800">Upload Payroll Deduction</h1>
</div>

<!-- DROP ZONE -->
<div id="dropZone"
     class="bg-white rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center
            transition-all hover:border-slate-500 hover:bg-slate-50/50
            mx-10 mb-10 shadow-sm flex-1 min-h-[430px] overflow-hidden no-scrollbar">

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


<!-- ══════════════════════════════════════════════
     MODAL 1 — PAYROLL DATE SELECTOR
     Fixed height: compact card, never overflows screen
══════════════════════════════════════════════ -->
<div id="dateSelectorModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <!-- max-h prevents overflow; no inner scroll needed — content is compact -->
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col"
         style="max-height: min(680px, 92vh);">

        <!-- Header (fixed) -->
        <div class="bg-[#ce1126] px-6 py-4 flex items-start justify-between rounded-t-2xl shrink-0">
            <div>
                <p class="text-white font-black text-base tracking-wide">Select Payroll Date</p>
                <p class="text-white/70 text-xs mt-0.5">Choose the month and cutoff before previewing the file.</p>
            </div>
            <button onclick="closeModal('dateSelectorModal')" class="text-white/60 hover:text-white transition-colors mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Scrollable body -->
        <div class="overflow-y-auto flex-1 p-6 space-y-5">

            <!-- Month picker -->
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Payroll Month</label>
                <div class="relative"
                     onclick="const p=document.getElementById('dsMonthPicker'); try{p.showPicker();}catch(e){p.click();}">
                    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 hover:border-slate-400 rounded-xl px-4 py-3 cursor-pointer transition-all">
                        <svg class="w-5 h-5 text-[#ce1126] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input type="month" id="dsMonthPicker"
                               class="flex-1 bg-transparent outline-none text-slate-800 font-bold text-sm cursor-pointer relative z-10"
                               onchange="updateEomLabel(); const c=document.querySelector('input[name=dsPayrollHalf]:checked'); if(c) onHalfSelected(c.value);">
                    </div>
                </div>
            </div>

            <!-- 15 / 30 radio -->
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Payroll Cutoff</label>
                <div class="grid grid-cols-2 gap-3">

                    <!-- 15th -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="dsPayrollHalf" value="15" class="sr-only peer"
                               onchange="onHalfSelected('15')">
                        <div class="flex flex-col items-center justify-center gap-1 border-2 border-slate-200 rounded-xl px-4 py-4
                                    peer-checked:border-[#ce1126] peer-checked:bg-red-50
                                    hover:border-slate-400 transition-all duration-150 select-none">
                            <span class="text-3xl font-black text-slate-700 leading-none peer-checked:text-[#ce1126]">15</span>
                            <span class="text-[11px] text-slate-400 tracking-wide">th of month</span>
                        </div>
                        <!-- check dot -->
                        <span class="absolute top-2 right-2 w-3.5 h-3.5 rounded-full border-2 border-slate-300
                                     peer-checked:border-[#ce1126] peer-checked:bg-[#ce1126] transition-all
                                     flex items-center justify-center">
                        </span>
                    </label>

                    <!-- 30th / last day -->
                    <label class="relative cursor-pointer">
                        <input type="radio" name="dsPayrollHalf" value="30" class="sr-only peer"
                               onchange="onHalfSelected('30')">
                        <div class="flex flex-col items-center justify-center gap-1 border-2 border-slate-200 rounded-xl px-4 py-4
                                    peer-checked:border-[#ce1126] peer-checked:bg-red-50
                                    hover:border-slate-400 transition-all duration-150 select-none">
                            <span id="dsEomLabel" class="text-3xl font-black text-slate-700 leading-none">30th</span>
                            <span class="text-[11px] text-slate-400 tracking-wide">end of month</span>
                        </div>
                        <span class="absolute top-2 right-2 w-3.5 h-3.5 rounded-full border-2 border-slate-300
                                     peer-checked:border-[#ce1126] peer-checked:bg-[#ce1126] transition-all
                                     flex items-center justify-center">
                        </span>
                    </label>

                </div>
            </div>

            <!-- Chosen date summary (shown after radio selection) -->
            <div id="dsChosenSummary" class="hidden items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <div>
                    <p class="text-[10px] text-green-600 font-black uppercase tracking-widest">Payroll Date Set</p>
                    <p id="dsChosenDateText" class="text-sm text-green-800 font-black mt-0.5"></p>
                </div>
            </div>

            <!-- Error -->
            <p id="dsError" class="hidden text-[12px] text-red-600 font-bold bg-red-50 border border-red-200 rounded-lg px-3 py-2"></p>

            <!-- Why this step note -->
            <div class="text-[11px] text-slate-400 leading-relaxed bg-slate-50 border border-slate-100 rounded-xl p-3">
                <p class="font-bold text-slate-500 mb-1">Why select the date manually?</p>
                <p>Excel's date cells can be misread depending on the computer's regional settings.
                For example, <code class="bg-white border border-slate-200 px-1 rounded">2/10/2026</code> may be stored
                as <strong>October 2</strong> instead of <strong>February 10</strong> if the system uses D/M/Y order.
                Your selection here is the <strong>ground truth</strong> — any row in the file that doesn't match
                will be clearly shown and the upload will be blocked until it's fixed.</p>
            </div>

        </div>

        <!-- Footer (fixed) -->
        <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 shrink-0">
            <button onclick="closeModal('dateSelectorModal')"
                class="px-5 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-full font-black
                       hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-95 text-sm">
                Cancel
            </button>
            <button id="dsProceedBtn" onclick="proceedToPreview()" disabled
                class="px-5 py-1.5 bg-slate-200 text-slate-400 rounded-full font-black cursor-not-allowed transition-all duration-200 text-sm">
                Preview File →
            </button>
        </div>

    </div>
</div>


<!-- ══════════════════════════════════════════════
     MODAL 2 — IMPORT PREVIEW
     Date is already locked — no date picker here.
     Table scrolls independently; header + footer are fixed.
══════════════════════════════════════════════ -->
<div id="importPreviewModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-[#f1f1f1] w-full max-w-5xl rounded-xl shadow-2xl flex flex-col"
         style="max-height: min(88vh, 860px);">

        <!-- Fixed header -->
        <div class="bg-white px-6 py-4 rounded-t-xl shrink-0 border-b border-slate-200 space-y-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-slate-800 font-black text-base tracking-wide">Preview Import</p>
                    <p class="text-slate-500 text-sm mt-0.5">
                        Payroll date locked to:
                        <span id="previewChosenDate" class="text-[#ce1126] font-black ml-1"></span>
                    </p>
                </div>
                <p id="previewStats" class="text-[13px] text-right shrink-0 mt-0.5"></p>
            </div>
            <!-- Match/mismatch banner -->
            <div id="previewMatchMsg" class="hidden text-[12px]"></div>
        </div>

        <!-- Scrollable table area -->
        <div class="overflow-auto flex-1 p-4">
            <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-[#ce1126] text-white">
                        <th class="px-3 py-2 text-[12px] font-black text-center border-r border-red-700 whitespace-nowrap">Employee ID</th>
                        <th class="px-3 py-2 text-[12px] font-black text-center border-r border-red-700 whitespace-nowrap">Due Date</th>
                        <th class="px-3 py-2 text-[12px] font-black border-r border-red-700">First Name</th>
                        <th class="px-3 py-2 text-[12px] font-black border-r border-red-700">Last Name</th>
                        <th class="px-3 py-2 text-[12px] font-black border-r border-red-700">Amount</th>
                        <th class="px-3 py-2 text-[12px] font-black text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="preview-body" class="text-slate-800 divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <!-- Fixed footer -->
        <div class="bg-white/80 px-6 py-4 rounded-b-xl border-t border-slate-200 flex justify-end gap-3 shrink-0">
            <button onclick="closeImportModal()"
                class="px-5 py-1.5 bg-white text-slate-500 border border-slate-200 rounded-full font-black
                       hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 active:scale-95 text-sm">
                ← Back
            </button>
            <button id="proceedImportBtn" onclick="processImport()" disabled
                class="px-5 py-1.5 bg-slate-200 text-slate-400 rounded-full font-black cursor-not-allowed transition-all duration-200 text-sm">
                Upload
            </button>
        </div>

    </div>
</div>


<!-- ══════════════════════════════════════════════
     MODAL 3 — RESULT
     Fixed size; error list scrolls if long
══════════════════════════════════════════════ -->
<div id="importResultsModal"
     class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">

    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col"
         style="max-height: min(80vh, 700px);">

        <!-- Scrollable content -->
        <div class="overflow-y-auto flex-1 p-8">

            <div class="text-center mb-6">
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

        <!-- Fixed footer button -->
        <div class="px-8 pb-8 shrink-0 flex justify-center">
            <button onclick="window.location.reload()"
                class="px-10 py-3 bg-[#ce1126] hover:bg-[#be123c] text-white rounded-full font-black shadow-md transition-all duration-200">
                Close
            </button>
        </div>

    </div>
</div>

<script src="../../assets/js/upload.js?v=<?php echo time(); ?>"></script>