<div id="importDetailModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-6xl max-h-[90vh] flex flex-col rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden text-sm sm:text-base">
        
        <div class="relative shrink-0">
            <div class="px-6 py-2 flex justify-between items-center bg-slate-50 border-b border-slate-100 mb-2">
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight leading-none">Preview</h1>
                </div>
                <button onclick="closeModal('importDetailModal')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-8 overflow-y-auto space-y-8 bg-white">
            
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1.5 h-4 bg-[#ce1126] rounded-full inline-block"></span>
                    <h3 class="font-bold text-slate-600 uppercase tracking-widest text-xs">Borrower's Profile</h3>
                </div>
                <div class="rounded-xl border border-slate-100 overflow-x-auto">
                    <table class="w-full table-fixed border-collapse">
                        <tr class="bg-[#ce2216]">
                            <th class="text-center text-[14px] text-white font-bold tracking-widest px-6 py-1">Borrower's Name</th>
                            <th class="text-center text-[14px] text-white font-bold tracking-widest px-6 py-1">Employee ID</th>
                            <th class="text-center text-[14px] text-white font-bold tracking-widest px-6 py-1">Contact Number</th>
                            <th class="text-center text-[14px] text-white font-bold tracking-widest px-6 py-1">Region</th>
                        </tr>
                        <tr>
                            <td id="imp-name" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                            <td id="imp-id" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                            <td id="imp-contact" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                            <td id="imp-region" class="text-center text-[14px] text-slate-800 font-mono lowercase first-letter:uppercase px-1 py-1">--</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1.5 h-4 bg-[#ce1126] rounded-full inline-block"></span>
                    <h3 class="font-bold text-slate-800 uppercase tracking-widest text-xs">Financial Details</h3>
                </div>
                <div class="space-y-3">
                    <div class="rounded-xl border border-slate-100 overflow-x-auto">
                        <table class="w-full table-fixed border-collapse">
                            <colgroup>
                                <col class="w-[20%]">
                                <col class="w-[24%]">
                                <col class="w-[14%]">
                                <col class="w-[12%]">
                                <col class="w-[15%]">
                                <col class="w-[15%]">
                            </colgroup>
                            <tr class="bg-[#ce2216]">
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">System Loan Number</th>
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">Reference Number</th>
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">Term(s)</th>
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">Interest</th>
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">Date Released</th>
                                <th class="text-center text-[14px] text-white font-bold tracking-widest px-3 py-1">Maturity Date</th>
                            </tr>
                            <tr>
                                <td id="imp-pn" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                                <td id="imp-ref" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                                <td id="imp-terms" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                                <td id="imp-rate" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                                <td id="imp-granted" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                                <td id="imp-maturity" class="text-center text-[14px] text-slate-800 font-mono px-1 py-1">--</td>
                            </tr>
                        </table>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="overflow-x-auto border border-slate-200 rounded-xl p-2 pr-4 pl-4">
                            <table class="w-full text-left text-slate-700">
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Loan Amount</th>
                                        <td id="imp-amount" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Semi-Monthly Amortization</th>
                                        <td id="imp-deduct" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Monthly Amortization</th>
                                        <td id="imp-monthly-amort" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="overflow-x-auto border border-slate-200 rounded-xl p-2 pr-4 pl-4">
                            <table class="w-full text-left text-slate-700">
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Gross Principal</th>
                                        <td id="imp-gross-principal" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Gross Interest</th>
                                        <td id="imp-gross-interest" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="w-1/2 px-6 py-1 text-xs text-slate-900 font-bold tracking-widest border-r border-slate-200">Total Gross</th>
                                        <td id="imp-gross-total" class="w-1/2 px-6 py-1 text-xs text-slate-900">--</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="imp-kptn-warning" class="hidden bg-red-50 border border-red-200 px-4 py-2 rounded-xl">
                <div class="flex items-start gap-3">
                    <div class=" flex items-center justify-center text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-red-800 tracking-wide uppercase">Warning: No KPTN Form Attached</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>