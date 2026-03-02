<?php
// Let the global layout wrapper handle the HTML structure
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

<form id="uploadLedgerForm" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center transition-all hover:border-slate-500 hover:bg-slate-50/50 mx-10 mb-10 shadow-sm flex-1 min-h-[430px] overflow-hidden no-scrollbar relative">
    
    <input type="file" id="ledgerFile" name="file" accept=".xlsx, .xls, .csv" class="hidden" required>

    <div onclick="document.getElementById('ledgerFile').click()" class="relative mb-6 cursor-pointer group mt-10">
        <div class="w-20 h-24 bg-slate-50 rounded-xl relative border-2 border-slate-200 overflow-hidden group-hover:border-[#dc2626] group-hover:bg-white transition-all duration-300">
            <div class="absolute top-0 right-0 w-6 h-6 bg-slate-200 rounded-bl-lg group-hover:bg-[#dc2626] transition-colors"></div>
            <div class="absolute bottom-0 left-0 right-0 h-8 bg-slate-100 flex items-center justify-center group-hover:bg-[#dc2626] transition-colors">
                <span class="font-black text-slate-400 group-hover:text-white">XLSX</span>
            </div>
            <div class="mt-8 px-4 space-y-2">
                <div class="h-1 bg-slate-200 rounded w-full"></div>
                <div class="h-1 bg-slate-200 rounded w-3/4"></div>
                <div class="h-1 bg-slate-100 rounded w-full"></div>
            </div>
        </div>
        <div class="absolute inset-0 bg-[#dc2626]/5 rounded-xl scale-110 opacity-0 group-hover:opacity-100 transition-all duration-500 -z-10"></div>
    </div>

    <div class="mb-2 shrink-0">
        <label for="ledgerFile" class="cursor-pointer">
            <h2 class="text-black text-center">
                Drag & Drop file here or <span class="text-[#dc2626] hover:underline">Choose File</span>
            </h2>
        </label>
        <p class="text-slate-400 text-center mt-1">
            Supported formats: .XLSX, .XLS, .CSV
        </p>
    </div>

    <div class="mb-8 text-center shrink-0">
        <div class="inline-flex items-center bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
            <span class="text-slate-400 mr-2">File:</span>
            <span id="displayFileName" class="text-[#ce1126]">No file selected</span>
        </div>
    </div>

    <div id="buttonContainer" class="hidden flex items-center gap-4 shrink-0 mb-10">
        
        <button type="button" onclick="window.location.reload()" class="px-6 py-2 bg-white text-slate-400 border border-slate-200 rounded-full font-black hover:bg-slate-50 hover:text-slate-600 hover:shadow-sm transition-all duration-200 active:scale-95">
            Cancel
        </button>
        <button type="submit" id="btnUploadLedger" class="px-6 py-2 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-lg hover:bg-red-700 transition-all duration-200 active:scale-95">
            Process File
        </button>
    </div>
</form>

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
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">PN Number</p>
                            <p class="font-black text-sm text-slate-800" id="previewPn">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Date Released</p>
                            <p class="font-black text-sm text-slate-800" id="previewGranted">-</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wide">Terms / Maturity</p>
                            <p class="font-black text-sm text-slate-800"><span id="previewTerms">-</span> <span class="text-slate-400 mx-1">|</span> <span id="previewMaturity">-</span></p>
                        </div>
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
                        <tbody id="previewLedgerTableBody" class="text-slate-800">
                            </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="p-4 flex justify-end gap-4 shrink-0 border-t border-slate-200 bg-white z-10">
            <button type="button" onclick="document.getElementById('importLedgerPreviewModal').classList.add('hidden'); document.getElementById('importLedgerPreviewModal').classList.remove('flex');" class="px-8 py-2.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-full font-black hover:bg-slate-200 hover:text-slate-800 transition-all duration-200 active:scale-95">
                Cancel
            </button>    
            <button type="button" id="btnConfirmLedgerSave" class="px-8 py-2.5 bg-[#ce1126] text-white rounded-full font-black shadow-sm hover:shadow-md hover:bg-red-700 transition-all duration-200 ease-in-out active:scale-95">
                Confirm Save
            </button>
            
        </div>
    </div>
</div>

<script src="../../assets/js/import_ledger.js?v=<?php echo time(); ?>"></script>