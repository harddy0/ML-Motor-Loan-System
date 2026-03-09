<?php
require_once __DIR__ . '/../../../src/includes/init.php';
?>

<style>
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .no-scrollbar::-webkit-scrollbar { display: none; }

    /* ── Card: CSS grid so both halves are always equal height ─── */
    #uploadCard {
        display: grid;
        grid-template-columns: 1fr 1px 1fr;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        overflow: hidden;
        height: 450px; /* Exact height */
    }

    .card-divider {
        background: linear-gradient(to bottom, transparent, #e2e8f0 15%, #e2e8f0 85%, transparent);
    }

    /* ── Drop zone ─────────────────────────────────────────────── */
    #ledgerDropZone {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 40px;
        cursor: pointer;
        border-right: none;
        background: #fafafa;
        transition: border-color .2s, background .2s;
        user-select: none;
    }
    #ledgerDropZone:hover         { border-color: #cbd5e1; background: #f8fafc; }
    #ledgerDropZone.drag-over     { border-color: #ce1126 !important; background: #fff5f5 !important; }
    #ledgerDropZone.file-selected { border-color: #ce1126; }

    .drop-icon-box { transition: border-color .3s, background .3s; }
    #ledgerDropZone.drag-over  .drop-icon-box,
    #ledgerDropZone.file-selected .drop-icon-box { border-color: #ce1126; background: #fff0f0; }
    #ledgerDropZone.drag-over  #fileIconCorner,
    #ledgerDropZone.file-selected #fileIconCorner { background: #ce1126; }
    #ledgerDropZone.drag-over  #fileIconBar,
    #ledgerDropZone.file-selected #fileIconBar    { background: #ce1126; }
    #ledgerDropZone.drag-over  #fileIconExt,
    #ledgerDropZone.file-selected #fileIconExt    { color: #fff; }

    /* ── KPTN panel ────────────────────────────────────────────── */
    #kptnPanel {
        display: flex;
        flex-direction: column;
        padding-top: 36px;
        padding-bottom: 15px;
        padding-left: 32px;
        padding-right: 32px;
        transition: background .3s;
        overflow: hidden;
        min-width: 0;
    }
    #kptnPanel.locked {
        opacity: .42;
        pointer-events: none;
        user-select: none;
    }
    #kptnPanel.file-ready {
        background: linear-gradient(160deg, #fff 50%, #fff6f7 100%);
    }
    @keyframes kptnPulse {
        0%   { box-shadow: inset 3px 0 0 rgba(206,17,38,0); }
        40%  { box-shadow: inset 3px 0 0 rgba(206,17,38,.65); }
        100% { box-shadow: inset 3px 0 0 rgba(206,17,38,0); }
    }
    #kptnPanel.pulse { animation: kptnPulse .65s ease-out 2; }


   

    /* ── Toggle pill ───────────────────────────────────────────── */
    .kptn-toggle-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 999px;
        padding: 6px 16px 6px 8px;
        cursor: pointer;
        align-self: flex-start;
        transition: border-color .2s, background .2s;
    }
    .kptn-toggle-pill:hover  { border-color: #cbd5e1; background: #f1f5f9; }
    .kptn-toggle-pill.active { border-color: #ce1126; background: #fff5f5; }

    /* ── KPTN fields animated reveal ───────────────────────────── */
    #ledgerKptnFieldsContainer {
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height .38s cubic-bezier(.4,0,.2,1),
                    opacity .25s ease,
                    margin-top .25s ease;
        margin-top: 0;
    }
    #ledgerKptnFieldsContainer.open {
        max-height: 340px;
        opacity: 1;
        margin-top: 16px;
    }

    /* ── Field label ───────────────────────────────────────────── */
    .f-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        margin-bottom: 6px;
        white-space: nowrap;
    }
    .f-label .req { color: #ce1126; }

    /* ── Deposit input ─────────────────────────────────────────── */
    .deposit-wrap {
        display: flex;
        align-items: stretch;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
        height: 46px;
    }
    .deposit-wrap:focus-within {
        border-color: #334155;
        box-shadow: 0 0 0 3px rgba(51,65,85,.09);
    }
    .deposit-wrap .peso {
        padding: 0 12px;
        background: #f8fafc;
        border-right: 1.5px solid #e2e8f0;
        color: #94a3b8;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        user-select: none;
    }
    .deposit-wrap input {
        flex: 1; border: none; outline: none;
        background: transparent;
        text-align: right;
        padding: 0 10px;
        font-size: 14px; font-weight: 600;
        color: #1e293b; min-width: 0; width: 0;
    }
    .deposit-wrap input::placeholder { color: #cbd5e1; font-weight: 400; }

    /* ── KPTN number input ─────────────────────────────────────── */
    .kptn-num-input {
        width: 100%;
        height: 46px;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 14px;
        font-size: 14px;
        text-transform: uppercase;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        color: #1e293b;
    }
    .kptn-num-input::placeholder { text-transform: none; color: #cbd5e1; font-size: 13px; }
    .kptn-num-input:focus {
        border-color: #334155;
        box-shadow: 0 0 0 3px rgba(51,65,85,.09);
    }

    /* ── Receipt picker ─────────────────────────────────────────── */
    #ledgerKptnDropArea {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        height: 46px;
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 14px;
        cursor: pointer;
        transition: border-color .2s, background .2s, box-shadow .2s;
        overflow: hidden;
    }
    #ledgerKptnDropArea:hover {
        border-color: #535353;
        box-shadow: 0 0 0 3px rgba(85, 84, 84, 0.07);
    }
    #ledgerKptnDropArea.has-file {
        border-color: #7d7e7e;
        box-shadow: 0 0 0 3px rgba(22,163,74,.07);
    }
    #ledgerKptnDropArea.has-file .receipt-icon { color: #636363; }

    /* ── Toggle pill pulse — fires when ledger file is selected ─── */
    @keyframes pillPulse {
        0%   { box-shadow: 0 0 0 0px  rgba(206,17,38,0); }
        30%  { box-shadow: 0 0 0 6px  rgba(206,17,38,.25); }
        60%  { box-shadow: 0 0 0 10px rgba(206,17,38,.10); }
        100% { box-shadow: 0 0 0 14px rgba(206,17,38,0); }
    }
    .kptn-toggle-pill.glow {
        animation: pillPulse 1s ease-out 3;
    }
</style>

<!-- Page Header -->
<div class="flex justify-between items-end mb-5 pb-2 shrink-0 -mt-4">
    <div>
        <h1 class="text-2xl text-slate-800">Upload Ledger</h1>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MAIN CARD — CSS grid 50/50
═══════════════════════════════════════════════════════ -->
<form id="uploadLedgerForm" class="no-scrollbar">
    <input type="file" id="ledgerFile" name="file" accept=".xlsx,.xls,.csv" class="hidden">

    <div id="uploadCard">

        <!-- ─── LEFT: Drop Zone ──────────────────────────────────── -->
        <div id="ledgerDropZone">

            <div class="relative mb-5 pointer-events-none">
                <div class="drop-icon-box w-[68px] h-[82px] bg-white rounded-xl border-2 border-slate-200 relative overflow-hidden shadow-sm">
                    <div id="fileIconCorner" class="absolute top-0 right-0 w-5 h-5 bg-slate-200 rounded-bl-lg transition-colors"></div>
                    <div id="fileIconBar" class="absolute bottom-0 left-0 right-0 h-7 bg-slate-100 flex items-center justify-center transition-colors">
                        <span id="fileIconExt" class="font-black text-slate-400 text-[10px] transition-colors">XLSX</span>
                    </div>
                    <div class="mt-5 px-3 space-y-[5px]">
                        <div class="h-[3px] bg-slate-200 rounded w-full"></div>
                        <div class="h-[3px] bg-slate-200 rounded w-3/4"></div>
                        <div class="h-[3px] bg-slate-100 rounded w-full"></div>
                    </div>
                </div>
            </div>

            <p class="text-slate-700 font-semibold text-center text-[15px]">Drag &amp; Drop file here</p>
            <p class="text-slate-400 text-center text-sm mt-1">
                or <span class="text-[#ce1126] font-medium hover:underline">Choose File</span>
            </p>
            <p class="text-slate-500 text-[13px] mt-1 tracking-widest font-mono">.xlsx/.xls/.csv</p>

            <!-- File chip -->
            <div id="fileChip" class="hidden mt-5 flex items-center gap-2 bg-white border border-slate-200 rounded-full px-3 py-1.5 shadow-sm max-w-[220px]">
                <svg class="w-3.5 h-3.5 text-[#ce1126] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
                <span id="displayFileName" class="text-[#ce1126] text-xs font-semibold truncate"></span>
                <button type="button" id="btnClearFile" class="ml-auto text-slate-400 hover:text-slate-500 transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>

        <!-- ─── Divider ──────────────────────────────────────────── -->
        <div class="card-divider"></div>

        <!-- ─── RIGHT: KPTN Panel ────────────────────────────────── -->
        <div id="kptnPanel" class="locked">

            <div class="mb-2">
                <p class="text-[11px] text-slate-400 font-mono mb-0.5">Turn on toggle if borrower has Security Deposit.</p>
            </div>

            <!-- Toggle -->
            <label class="kptn-toggle-pill" id="kptnTogglePill">
                <span class="relative flex-shrink-0">
                    <input type="checkbox" id="ledgerRequiresKptnToggle" class="sr-only peer">
                    <span class="block w-10 h-[22px] bg-slate-300 rounded-full peer-checked:bg-[#ce1126] transition-colors"></span>
                    <span class="absolute top-[3px] left-[3px] w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-[18px] block"></span>
                </span>
                <span id="ledgerToggleLabelText" class="text-[13px] font-bold text-slate-500 select-none">No Security Deposit</span>
            </label>


            <!-- ── Three inputs stacked vertically ───────────────── -->
            <div id="ledgerKptnFieldsContainer">
                <div class="flex flex-col gap-3">

                    <!-- Deposit Amount -->
                    <div>
                        <p class="f-label">Security Deposit Amount <span class="req">*</span></p>
                        <div class="deposit-wrap">
                            <span class="peso">₱</span>
                            <input type="text" id="ledgerDepositAmount" inputmode="numeric" placeholder="0.00" autocomplete="off">
                        </div>
                    </div>

                    <!-- KPTN Receipt No. -->
                    <div>
                        <p class="f-label">KPTN <span class="req">*</span></p>
                        <input type="text" id="ledgerKptnNumber" class="kptn-num-input" placeholder="Enter KPTN">
                    </div>

                    <!-- Receipt File -->
                    <div>
                        <p class="f-label">KPTN Form<span class="req">*</span></p>
                        <div id="ledgerKptnDropArea">
                            <input type="file" id="ledgerKptnReceipt" accept="image/jpeg,image/png,application/pdf"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <svg class="receipt-icon w-4 h-4 text-slate-400 pointer-events-none shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0l-3 3m3-3l3 3"/>
                            </svg>
                            <span id="ledgerKptnFileLabel" class="text-[13px] hover:underline text-slate-500 cursor-pointer truncate">
                                Choose file...
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex-1 min-h-[12px]"></div>

            <!-- Action buttons -->
            <div class="flex flex-row gap-1 pt-3 justify-between items-center border-t border-slate-100">
               
                <button type="button" onclick="window.location.reload()"
                        class="px-6 py-1 bg-slate-100 text-slate-500 rounded-full font-black text-sm hover:bg-slate-200 hover:text-slate-700 transition-all duration-200 active:scale-95">
                    Cancel
                </button>

                 <button type="submit" id="btnUploadLedger"
                        class="px-6 py-1 bg-[#ce1126] text-white rounded-full font-black text-sm shadow-sm hover:bg-red-700 hover:shadow-md transition-all duration-200 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                        disabled>
                    Upload
                </button>
            </div>

        </div><!-- end kptnPanel -->

    </div><!-- end uploadCard -->
</form>


<!-- ═══════════════════════════════════════════════════════
     PREVIEW MODAL — full screen, styled like ledger_detail.php
═══════════════════════════════════════════════════════ -->
<div id="importLedgerPreviewModal" class="fixed inset-0 z-[60] hidden flex-col bg-white overflow-hidden text-[14px]">

    <!-- Floating close button — top-right, same as ledger_detail -->
    <a href="javascript:void(0);" id="btnCancelLedgerPreview"
       class="fixed top-2 right-4 group bg-red-500 text-white hover:bg-red-600 p-1 rounded-full transition-all shadow-md z-[70] flex items-center justify-center">
        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </a>

    <!-- Scrollable body -->
    <div class="flex-1 overflow-y-auto relative w-full scroll-smooth pb-16">

        <!-- Header info block -->
        <div class="flex flex-col w-full p-5 pb-3">
                <div class="flex items-start justify-between gap-10 w-full">

                    <!-- LEFT: Borrower info -->
                    <div class="flex flex-col gap-0.5 min-w-[340px] border-r border-slate-100 pr-8">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Borrower's Name:</span>
                            <h2 class="text-[13px] text-slate-800 font-bold uppercase" id="previewName">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Employee ID:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewId">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Reference Number:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewRef">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Region:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewRegion">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Branch:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewBranch">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Contact Number:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewContact">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">System Loan Number:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewPn">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Date Released:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewGranted">-</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] text-slate-400 uppercase w-44">Maturity Date:</span>
                            <h2 class="text-[13px] text-slate-800 uppercase" id="previewMaturity">-</h2>
                        </div>
                    </div>

                    <!-- CENTER: Loan details + payment summary -->
                    <div class="flex-grow px-2 -mt-2">
                        <div class="border-b border-slate-100 mb-1">
                            <div class="flex items-center gap-x-3 mb-2">
                                <h2 class="text-[14px] text-slate-800 uppercase font-bold tracking-widest">
                                    MOTORCYCLE LOAN REPORT
                                </h2>
                                <div id="previewKptnBadge"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-12 gap-y-1">
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[12px] text-slate-400 uppercase w-40">Loan Amount:</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewAmount">-</h2>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[12px] text-slate-400 uppercase w-40">Term(s):</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewTerms">-</h2>
                                </div>
                                <!-- Security deposit row — shown/hidden by JS -->
                                <div class="flex justify-between" id="preview-security-deposit-wrapper">
                                    <span class="text-[12px] text-slate-400 uppercase w-40">Security Deposit:</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewDepositAmount">₱ 0.00</h2>
                                </div>
                            </div>
                            <div class="space-y-1">
                                 <div class="flex justify-between">
                                    <span class="text-[12px] text-slate-400 uppercase w-40">Add-on Rate:</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewRate">-</h2>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[12px] text-slate-400 uppercase w-45">Semi-Monthly Amortization:</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewDeduction">-</h2>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[12px] text-slate-400 uppercase w-40">Monthly Amortization:</span>
                                    <h2 class="text-[13px] text-slate-800 font-semibold uppercase" id="previewMonthlyAmort">-</h2>
                                </div>
                            </div>
                        </div>

                        <div class="mt-1">
                            <span class="text-[12px] text-slate-900 font-bold uppercase">Payment Summary</span>
                            <div class="grid grid-cols-2 gap-x-12 gap-y-1">
                                <div class="flex justify-between border-b border-slate-50 pb-1">
                                    <span class="text-[12px] text-slate-500 uppercase">Principal Paid:</span>
                                    <span class="text-[12px] text-slate-800 font-medium" id="preview-principal-paid">₱ 0.00</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-1">
                                    <span class="text-[12px] text-slate-500 uppercase">Principal Balance:</span>
                                    <span class="text-[12px] text-slate-800 font-medium" id="preview-principal-balance">₱ 0.00</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-1">
                                    <span class="text-[12px] text-slate-500 uppercase">Interest Paid:</span>
                                    <span class="text-[12px] text-slate-800 font-medium" id="preview-interest-paid">₱ 0.00</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-1">
                                    <span class="text-[12px] text-slate-500 uppercase">Interest Balance:</span>
                                    <span class="text-[12px] text-slate-800 font-medium" id="preview-interest-balance">₱ 0.00</span>
                                </div>
                                <div class="flex justify-between pt-1">
                                    <span class="text-[12px] text-slate-900 font-bold uppercase">Total Collected:</span>
                                    <span class="text-[12px] text-slate-900 font-bold" id="preview-total-collected">₱ 0.00</span>
                                </div>
                                <div class="flex justify-between pt-1">
                                    <span class="text-[12px] text-rose-600 font-bold uppercase">Total Outstanding:</span>
                                    <span class="text-[12px] text-rose-600 font-bold" id="preview-total-outstanding">₱ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Amortization table — full-width, red header, same as ledger_detail -->
            <div class="w-full border-t border-slate-200">
                <table class="w-full text-left border-collapse table-fixed">
                    <thead class="sticky top-0">
                        <tr class="bg-[#ce1126] border-b border-slate-300">
                            <th class="py-1 w-[5%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">No.</th>
                            <th class="py-1 w-[16%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Due Date</th>
                            <th class="py-1 w-[15%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right pr-4">Principal</th>
                            <th class="py-1 w-[15%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right pr-4">Interest</th>
                            <th class="py-1 w-[15%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right pr-4">Total Amount</th>
                            <th class="py-1 w-[15%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-right pr-4">Balance</th>
                            <th class="py-1 w-[10%] text-[14px] font-black text-white uppercase tracking-widest border-r border-slate-100 text-center">Status</th>
                            <th class="py-1 px-4 text-[14px] font-black text-white uppercase tracking-widest text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="previewLedgerTableBody" class="divide-y divide-slate-50 text-slate-600 text-[13px]"></tbody>
                </table>
            </div>

    </div><!-- end scrollable body -->

    <!-- Bottom action row: full-width white strip with right-aligned buttons -->
    <div class="fixed bottom-0 left-0 right-0 z-[70] bg-white border-t border-slate-200 py-2 px-6 flex justify-end items-center gap-3">
        <button type="button" id="btnCancelLedgerPreview2"
                class="px-5 py-1 bg-white/90 backdrop-blur-sm text-slate-600 rounded-full font-black shadow-lg hover:bg-slate-100 transition-all duration-200 active:scale-95 border border-slate-200">
            Cancel
        </button>
        <button type="button" id="btnConfirmLedgerSave"
                class="px-5 py-1 bg-[#ce1126] text-white rounded-full font-black shadow-lg hover:bg-red-700 transition-all duration-200 active:scale-95">
            Save
        </button>
    </div>

</div>

<!-- SUCCESS MODAL -->
<div id="ledgerSuccessModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
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

<!-- WARNING MODAL -->
<div id="ledgerKptnWarningModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-amber-50 px-6 pt-8 pb-6 flex flex-col items-center text-center border-b border-amber-100">
            <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wide">Attention</h3>
            <p id="ledgerKptnWarningMsg" class="text-[13px] text-slate-500 mt-1">Please complete the required fields.</p>
        </div>
        <div class="px-6 py-5">
            <button id="btnCloseKptnWarning"
                    class="w-full py-2.5 bg-[#ce1126] text-white rounded-full font-black text-sm hover:bg-red-700 transition-colors active:scale-95">
                OK
            </button>
        </div>
    </div>
</div>

<script src="../../assets/js/import_ledger.js?v=<?php echo time(); ?>"></script>