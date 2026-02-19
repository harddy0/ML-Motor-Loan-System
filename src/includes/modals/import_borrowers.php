<div id="importBorrowerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-5 flex justify-between items-center">
            <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">
                Borrower Management / <span class="text-[#e11d48]">Batch Import</span>
            </h2>
            <button onclick="closeModal('importBorrowerModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="importBorrowerForm" class="p-8">
            <div class="space-y-8">
                
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-slate-50 rounded-full mb-3">
                        <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-tight">Data File Source</h3>
                    <p class="mt-1 text-[9px] font-bold text-slate-400 uppercase tracking-tighter">CSV / XLSX / XLS Format Only</p>
                </div>

                <div id="drop-zone" 
                    class="flex justify-center rounded-3xl border-2 border-dashed border-slate-100 px-6 py-10 hover:border-[#e11d48]/40 hover:bg-red-50/20 transition-all cursor-pointer relative group">
                    <div class="space-y-2 text-center">
                        <div class="flex text-[11px] text-slate-600 justify-center">
                            <label for="file-upload" class="relative cursor-pointer font-black text-[#e11d48] hover:text-[#be123c] transition-colors">
                                <span>SELECT FILE</span>
                                <input id="file-upload" name="file-upload" type="file" class="sr-only" accept=".csv, .xlsx, .xls">
                            </label>
                            <p class="pl-1 font-bold text-slate-400 uppercase">or drag and drop</p>
                        </div>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest transition-colors" id="file-name-display">
                            No file chosen
                        </p>
                    </div>
                </div>

                <div class="text-center">
                    <a href="#" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-slate-50 text-[9px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-100 hover:text-slate-600 transition-all">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Template CSV
                    </a>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-10">
                <button type="button" onclick="closeModal('importBorrowerModal')" 
                class="h-11 px-6 bg-slate-100 text-slate-800 rounded-full text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-slate-300 transition-all active:scale-95">
                    Cancel
                </button>
                <button type="submit" class="h-11 px-10 bg-[#e11d48] hover:bg-[#be123c] text-white text-[10px] font-black uppercase tracking-widest rounded-full transition-colors shadow-lg shadow-red-900/10">
                    Import Records
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-upload');
const fileNameDisplay = document.getElementById('file-name-display');

// 1. Function to update UI label
function updateUI(files) {
    if (files && files.length > 0) {
        fileNameDisplay.textContent = `File Name: ${files[0].name}`;
        fileNameDisplay.classList.remove('text-slate-400');
        fileNameDisplay.classList.add('text-[#e11d48]'); // Apply ML Red to the name
    } else {
        fileNameDisplay.textContent = 'No file chosen';
        fileNameDisplay.classList.remove('text-[#e11d48]');
        fileNameDisplay.classList.add('text-slate-400');
    }
}

// 2. Handle Manual Selection
fileInput.addEventListener('change', (e) => updateUI(e.target.files));

// 3. Handle Drag & Drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

// Visual feedback when dragging over
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.add('border-[#e11d48]', 'bg-red-50/30');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.remove('border-[#e11d48]', 'bg-red-50/30');
    }, false);
});

// Handle dropped files
dropZone.addEventListener('drop', (e) => {
    const droppedFiles = e.dataTransfer.files;
    fileInput.files = droppedFiles; // Sync dropped file to the hidden input
    updateUI(droppedFiles);
});
</script>