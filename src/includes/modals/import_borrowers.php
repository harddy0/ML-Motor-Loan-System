<div id="importBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Borrower / <span class="text-[#ff3b30]">Batch Import</span>
            </h2>
            <button onclick="closeModal('importBorrowerModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="importBorrowerForm" class="p-8">
            <div class="space-y-6">
                
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-bold text-slate-900 uppercase">Upload Data File</h3>
                    <p class="mt-1 text-xs text-slate-500">Supported formats: .CSV, .XLSX</p>
                </div>

                <div class="flex justify-center rounded-md border-2 border-dashed border-slate-300 px-6 pt-5 pb-6 hover:bg-slate-50 transition-colors cursor-pointer relative">
                    <div class="space-y-1 text-center">
                        <div class="flex text-sm text-slate-600 justify-center">
                            <label for="file-upload" class="relative cursor-pointer rounded-md font-bold text-[#ff3b30] focus-within:outline-none focus-within:ring-2 focus-within:ring-[#ff3b30] focus-within:ring-offset-2 hover:text-red-700">
                                <span>Upload a file</span>
                                <input id="file-upload" name="file-upload" type="file" class="sr-only" accept=".csv, .xlsx, .xls" onchange="updateFileName(this)">
                            </label>
                            <p class="pl-1 font-bold">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500 font-bold" id="file-name-display">No file chosen</p>
                    </div>
                </div>

                <div class="text-center border-t border-slate-100 pt-4">
                    <a href="#" class="text-[10px] font-black text-slate-400 uppercase hover:text-[#ff3b30] transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Template CSV
                    </a>
                </div>

            </div>

            <div class="bg-slate-100 px-8 py-4 flex justify-end gap-3 border-t-2 border-slate-200 -mx-8 -mb-8 mt-8">
                <button type="button" onclick="closeModal('importBorrowerModal')" class="bg-white border-2 border-slate-300 hover:border-slate-800 px-8 py-2 text-[10px] font-black uppercase transition-all">
                    Cancel
                </button>
                <button type="submit" class="bg-[#ff3b30] hover:bg-red-700 text-white px-8 py-2 text-[10px] font-black uppercase transition-all shadow-md">
                    Import Records
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateFileName(input) {
        const display = document.getElementById('file-name-display');
        if (input.files && input.files.length > 0) {
            display.textContent = input.files[0].name;
            display.classList.add('text-[#ff3b30]');
        } else {
            display.textContent = 'No file chosen';
            display.classList.remove('text-[#ff3b30]');
        }
    }
</script>