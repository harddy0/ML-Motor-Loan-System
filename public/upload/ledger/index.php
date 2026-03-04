<?php
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<style>
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
</style>

<div class="flex flex-col lg:flex-row justify-between items-end mb-4 pb-2 shrink-0 -mt-4">
    <div>
        <h1 class="text-2xl text-slate-800">Upload Existing Ledger</h1>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     UPLOAD FORM — drag & drop zone + KPTN toggle
═══════════════════════════════════════════════════════ -->
<form id="uploadLedgerForm" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center transition-all mx-10 mb-10 shadow-sm flex-1 min-h-[430px] overflow-hidden no-scrollbar relative"
      id="uploadLedgerForm">

    <!-- Hidden file input -->
    <input type="file" id="ledgerFile" name="file" accept=".xlsx,.xls,.csv" class="hidden">

    <!-- Drop zone visual -->
    <div id="ledgerDropZone" class="flex flex-col items-center justify-center w-full flex-1 px-6 pt-10 pb-4 cursor-pointer">

        <div class="relative mb-6 group pointer-events-none">
            <div class="w-20 h-24 bg-slate-50 rounded-xl relative border-2 border-slate-200 overflow-hidden transition-all duration-300" id="fileIconBox">
                <div class="absolute top-0 right-0 w-6 h-6 bg-slate-200 rounded-bl-lg transition-colors" id="fileIconCorner"></div>
                <div class="absolute bottom-0 left-0 right-0 h-8 bg-slate-100 flex items-center justify-center transition-colors" id="fileIconBar">
                    <span class="font-black text-slate-400 text-[11px]" id="fileIconExt">XLSX</span>
                </div>
                <div class="mt-8 px-4 space-y-2">
                    <div class="h-1 bg-slate-200 rounded w-full"></div>
                    <div class="h-1 bg-slate-200 rounded w-3/4"></div>
                    <div class="h-1 bg-slate-100 rounded w-full"></div>
                </div>
            </div>
        </div>

        <h2 class="text-black text-center">
            Drag &amp; Drop file here or <span class="text-[#dc2626] hover:underline">Choose File</span>
        </h2>
        <p class="text-slate-400 text-center mt-1 text-sm">Supported formats: .XLSX, .XLS, .CSV</p>

        <div class="mt-3 inline-flex items-center bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
            <span class="text-slate-400 mr-2 text-sm">File:</span>
            <span id="displayFileName" class="text-[#ce1126] text-sm">No file selected</span>
        </div>
    </div>

    <!-- KPTN toggle — only visible once a file is chosen -->
    <div id="kptnToggleSection" class="hidden w-full px-10 pb-4 flex flex-col items-center gap-3">

        <div class="flex items-center">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="ledgerRequiresKptnToggle" class="sr-only peer" checked>
                <div class="w-11 h-6 bg-slate-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#ce1126]"></div>
                <span id="ledgerToggleLabelText" class="ml-3 text-[13px] font-bold text-slate-800 select-none">With KPTN Deposit (₱2,500) &amp; Attachment</span>
            </label>
        </div>

        <!-- KPTN fields — shown when toggle is ON -->
        <div id="ledgerKptnFieldsContainer" class="w-full max-w-xl flex flex-col sm:flex-row gap-3 bg-slate-50 px-5 py-4 rounded-xl border border-slate-200">
            <div class="flex flex-col gap-1 w-full sm:w-48 shrink-0">
                <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">KPTN Receipt No. *</label>
                <input type="text" id="ledgerKptnNumber" placeholder="ENTER KPTN..."
                       class="w-full bg-white border border-slate-300 focus:border-slate-900 rounded-sm px-3 py-2 text-[13px] uppercase outline-none">
            </div>
            <div class="flex flex-col gap-1 flex-1 w-full">
                <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">KPTN Receipt File *</label>
                <div id="ledgerKptnDropArea"
                     class="relative w-full bg-slate-100 text-slate-700 rounded-sm px-3 py-2 flex items-center gap-2 cursor-pointer hover:bg-[#ce1126] hover:text-white transition-colors">
                    <input type="file" id="ledgerKptnReceipt" accept="image/jpeg,image/png,application/pdf"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <svg class="w-4 h-4 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0l-3 3m3-3l3 3"/>
                    </svg>
                    <span id="ledgerKptnFileLabel" class="text-[13px] pointer-events-none truncate">Choose file or drag here</span>
                </div>
                <p class="text-[11px] text-slate-400">Accepted: JPEG, PNG, PDF</p>
            </div>
        </div>

    </div>

    <!-- Action buttons -->
    <div id="buttonContainer" class="hidden flex items-center gap-4 shrink-0 pb-8">
        <button type="button" onclick="window.location.reload()"
                class="px-6 py-2 bg-white text-slate-400 border border-slate-200 rounded-full font-black hover:bg-slate-50 hover:text-slate-600 hover:shadow-sm transition-all duration-200 active:scale-95">
            Cancel
        </button>
        <button type="submit" id="btnUploadLedger"
                class="px-6 py-2 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-lg hover:bg-red-700 transition-all duration-200 active:scale-95">
            Process File
        </button>
    </div>

</form>

<!-- ═══════════════════════════════════════════════════════
     PREVIEW MODAL
═══════════════════════════════════════════════════════ -->
<div id="importLedgerPreviewModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-[#eeeeee] w-full max-w-6xl rounded-2xl shadow-2xl flex flex-col h-[90vh] overflow-hidden">

        <div class="px-6 py-4 bg-white border-b border-slate-200 flex justify-between items-center shrink-0">
            <h2 class="text-lg font-black text-slate-800">Review Import Data</h2>
            <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full border border-slate-200">Preview</span>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                        <h3 class="text-sm font-black text-slate-700"><i class="bi bi-person-badge me-2 text-[#dc2626]"></i>Borrower Profile</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Account Name</p>
                            <p class="font-black text-base text-slate-800" id="previewName">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">ID Number</p>
                            <p class="font-black text-sm text-slate-800" id="previewId">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Contact Number</p>
                            <p class="font-black text-sm text-slate-800" id="previewContact">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Region</p>
                            <p class="font-black text-sm text-slate-800" id="previewRegion">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Branch</p>
                            <p class="font-black text-sm text-slate-800" id="previewBranch">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                        <h3 class="text-sm font-black text-slate-700"><i class="bi bi-file-earmark-text me-2 text-[#dc2626]"></i>Loan Details</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Loan Amount</p>
                            <p class="font-black text-lg text-[#dc2626]" id="previewAmount">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Amortization</p>
                            <p class="font-black text-base text-slate-800" id="previewDeduction">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Ref Number</p>
                            <p class="font-black text-sm text-slate-800" id="previewRef">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Promissory Note</p>
                            <p class="font-black text-sm text-slate-800" id="previewPn">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Date Released</p>
                            <p class="font-black text-sm text-slate-800" id="previewGranted">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Terms / Maturity</p>
                            <p class="font-black text-sm text-slate-800">
                                <span id="previewTerms">-</span>
                                <span class="text-slate-400 mx-1">|</span>
                                <span id="previewMaturity">-</span>
                            </p>
                        </div>
                        <!-- KPTN badge -->
                        <div class="col-span-2" id="previewKptnBadge"></div>
                    </div>
                </div>

            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-sm font-black text-slate-700"><i class="bi bi-calendar3 me-2 text-[#dc2626]"></i>Amortization Schedule</h3>
                    <span class="text-xs font-bold text-slate-500" id="previewRowCount">0 records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-white">
                            <tr class="text-slate-700 font-black border-b border-slate-200">
                                <th class="px-4 py-3 text-center border-r border-slate-100">No</th>
                                <th class="px-4 py-3 text-center border-r border-slate-100">Payment Date</th>
                                <th class="px-4 py-3 text-right border-r border-slate-100">Principal</th>
                                <th class="px-4 py-3 text-right border-r border-slate-100">Interest</th>
                                <th class="px-4 py-3 text-right border-r border-slate-100 bg-slate-50">Total Amount</th>
                                <th class="px-4 py-3 text-right border-r border-slate-100">Principal Balance</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="previewLedgerTableBody" class="text-slate-800"></tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="p-4 flex justify-end gap-4 shrink-0 border-t border-slate-200 bg-white">
            <button type="button" id="btnCancelLedgerPreview"
                    class="px-8 py-2.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-full font-black hover:bg-slate-200 hover:text-slate-800 transition-all duration-200 active:scale-95">
                Cancel
            </button>
            <button type="button" id="btnConfirmLedgerSave"
                    class="px-8 py-2.5 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-md hover:bg-red-700 transition-all duration-200 ease-in-out active:scale-95">
                Confirm Save
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SUCCESS MODAL
═══════════════════════════════════════════════════════ -->
<div id="ledgerSuccessModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden transform transition-all">
        <div class="bg-emerald-50 px-6 pt-8 pb-6 flex flex-col items-center text-center border-b border-emerald-100">
            <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wide">Ledger Imported</h3>
            <p class="text-[13px] text-slate-500 mt-1">The loan record has been saved successfully.</p>
        </div>
        <div class="px-6 py-5 flex flex-col gap-2">
            <button onclick="window.location.href='../../reports/ledger/index.php'"
                    class="w-full py-2.5 bg-[#ce1126] text-white rounded-full font-black text-sm hover:bg-red-700 transition-colors active:scale-95">
                View Ledger
            </button>
            <button onclick="window.location.reload()"
                    class="w-full py-2.5 bg-slate-100 text-slate-600 rounded-full font-black text-sm hover:bg-slate-200 transition-colors active:scale-95">
                Import Another
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     KPTN REQUIRED WARNING MODAL
═══════════════════════════════════════════════════════ -->
<div id="ledgerKptnWarningModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-amber-50 px-6 pt-8 pb-6 flex flex-col items-center text-center border-b border-amber-100">
            <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wide">KPTN Required</h3>
            <p id="ledgerKptnWarningMsg" class="text-[13px] text-slate-500 mt-1">Please complete the KPTN details before processing.</p>
        </div>
        <div class="px-6 py-5">
            <button id="btnCloseKptnWarning"
                    class="w-full py-2.5 bg-[#ce1126] text-white rounded-full font-black text-sm hover:bg-red-700 transition-colors active:scale-95">
                Got It
            </button>
        </div>
    </div>
</div>

<script src="../../assets/js/import_ledger.js?v=<?php echo time(); ?>"></script>