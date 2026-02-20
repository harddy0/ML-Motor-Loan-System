<div id="importPreviewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[85vh]">
        
        <div class="bg-slate-50 border-b border-slate-100 px-8 py-5 flex justify-between items-center shrink-0">
            <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">
                Import / <span class="text-[#e11d48]">Review Records</span>
            </h2>
            <button onclick="closeModal('importPreviewModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="bg-white px-8 py-3 border-b border-slate-50 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">
                Found <span id="import-count" class="text-slate-900">0</span> Records Available
            </span>
            <div class="flex items-center gap-3 bg-slate-50 px-4 py-1.5 rounded-full border border-slate-100">
                <label for="select-all" class="text-[9px] font-black text-slate-600 uppercase cursor-pointer select-none">Select All</label>
                <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" 
                    class="import-checkbox w-5 h-5 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer">
            </div>
        </div>

        <div class="overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50 p-6 hover: border-slate-700">
            <ul id="import-list" class="space-y-3">
            </ul>
        </div>

        <div class="bg-slate-50 px-8 py-5 flex justify-end gap-3 border-t border-slate-100 shrink-0">
            <button onclick="closeModal('importPreviewModal')" 
            class="h-11 px-6 bg-slate-100 text-slate-800 rounded-full text-[10px] 
                font-black uppercase tracking-widest shadow-md hover:bg-slate-300 transition-all active:scale-95">
                Cancel
            </button>
            <button onclick="finalizeImport()" 
            class="h-11 px-6 bg-[#e11d48] text-white rounded-full text-[10px] 
                font-black uppercase tracking-widest shadow-md hover:bg-[#be123c] transition-all active:scale-95">
                Import Selected
            </button>
        </div>
    </div>
</div>