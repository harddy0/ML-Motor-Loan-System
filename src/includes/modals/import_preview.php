<div id="importPreviewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded shadow-2xl border-2 border-slate-200 overflow-hidden transform transition-all flex flex-col max-h-[80vh]">
        
        <div class="bg-slate-100 border-b-2 border-slate-200 px-8 py-4 flex justify-between items-center shrink-0">
            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">
                Import / <span class="text-[#ff3b30]">Review Records</span>
            </h2>
            <button onclick="closeModal('importPreviewModal')" class="text-slate-400 hover:text-[#ff3b30] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="bg-slate-50 px-8 py-3 border-b border-slate-200 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-bold text-slate-500 uppercase">Found <span id="import-count" class="text-black">0</span> Records</span>
            <div class="flex items-center gap-2">
                <label for="select-all" class="text-[10px] font-black text-slate-700 uppercase cursor-pointer select-none">Select All</label>
                <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" class="w-4 h-4 text-[#ff3b30] rounded border-slate-300 focus:ring-[#ff3b30] cursor-pointer">
            </div>
        </div>

        <div class="overflow-y-auto custom-scrollbar flex-1 bg-white p-4">
            <ul id="import-list" class="space-y-2">
                </ul>
        </div>

        <div class="bg-slate-100 px-8 py-4 flex justify-end gap-3 border-t-2 border-slate-200 shrink-0">
            <button onclick="closeModal('importPreviewModal')" class="bg-white border-2 border-slate-300 hover:border-slate-800 px-8 py-2 text-[10px] font-black uppercase transition-all">
                Cancel
            </button>
            <button onclick="finalizeImport()" class="bg-[#ff3b30] hover:bg-red-700 text-white px-8 py-2 text-[10px] font-black uppercase transition-all shadow-md">
                Import Selected
            </button>
        </div>
    </div>
</div>