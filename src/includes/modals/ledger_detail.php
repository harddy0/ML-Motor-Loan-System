<div id="ledgerDetailModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[60] hidden items-center justify-center p-2 md:p-4">
    <div class="bg-white w-full max-w-7xl h-[95vh] rounded-lg shadow-2xl border border-slate-200 overflow-hidden flex flex-col font-sans">
        
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 shrink-0 shadow-sm z-10">
            
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight" id="modal-ledger-name">--</h2>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 bg-slate-200 text-slate-600 text-[10px] font-black uppercase rounded" id="modal-ledger-id">--</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:inline-block">Motorcycle Loan Account</span>
                    </div>
                </div>
                <button onclick="closeLedgerModal()" class="group bg-white border border-slate-200 text-slate-400 hover:text-[#ff3b30] hover:border-[#ff3b30] p-2 rounded-full transition-all">
                    <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-x-8 gap-y-4 border-t border-slate-200 pt-4">
                
                <div class="space-y-1 border-r border-slate-100 pr-4">
                    <h4 class="text-[10px] font-black text-[#ff3b30] uppercase tracking-widest mb-2">Reference</h4>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">PN Number</span>
                        <span class="text-sm font-black text-slate-800" id="modal-ledger-pn">--</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">Granted</span>
                        <span class="text-sm font-bold text-slate-700" id="modal-ledger-pndate">--</span>
                    </div>
                     <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">Maturity</span>
                        <span class="text-sm font-bold text-slate-700" id="modal-ledger-maturity">--</span>
                    </div>
                </div>

                <div class="space-y-1 border-r border-slate-100 pr-4">
                    <h4 class="text-[10px] font-black text-[#ff3b30] uppercase tracking-widest mb-2">Terms</h4>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">Duration</span>
                        <span class="text-sm font-black text-slate-800" id="modal-ledger-terms">-- Months</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">Add-on Rate</span>
                        <span class="text-sm font-bold text-slate-700" id="modal-ledger-rate">--</span>
                    </div>
                </div>

                <div class="space-y-1 border-r border-slate-100 pr-4">
                    <h4 class="text-[10px] font-black text-[#ff3b30] uppercase tracking-widest mb-2">Financials</h4>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-bold text-slate-500 uppercase">Principal</span>
                        <span class="text-sm font-black text-slate-800" id="modal-ledger-principal">--</span>
                    </div>
                     <div class="flex justify-between items-center mt-2 bg-yellow-50 px-2 py-1 rounded border border-yellow-100">
                        <span class="text-[10px] font-bold text-yellow-700 uppercase">Amortization</span>
                        <span class="text-sm font-black text-slate-900" id="modal-ledger-amort">--</span>
                    </div>
                </div>

                <div class="hidden lg:flex flex-col justify-start items-start border-r border-slate-100 pr-4">
                    <span class="text-[10px] font-black text-[#ff3b30] uppercase tracking-widest mb-2">Account Status</span>
                    <span class="inline-block px-4 py-1.5 bg-green-100 text-green-700 text-xs font-black uppercase rounded-full" id="modal-ledger-status">Active</span>
                </div>

                <div class="flex flex-col justify-center items-end text-right pl-2">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Principal Balance</span>
                    <span class="block text-4xl font-black text-[#ff3b30] tracking-tighter leading-none mt-1" id="modal-ledger-balance">--</span>
                </div>

            </div>
        </div>

        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden bg-white h-0">
            
            <div class="flex-1 flex flex-col h-full overflow-hidden border-r border-slate-200 relative">
                
                <div class="bg-slate-900 text-white flex text-xs font-black uppercase tracking-wider sticky top-0 z-20 shadow-md">
                    <div class="w-32 p-4 text-center border-r border-white/10">Due Date</div>
                    <div class="w-32 p-4 text-center border-r border-white/10 bg-slate-800 text-slate-300">Date Paid</div>
                    <div class="flex-1 p-4 text-right border-r border-white/10">Principal</div>
                    <div class="flex-1 p-4 text-right border-r border-white/10">Interest</div>
                    <div class="flex-1 p-4 text-right border-r border-white/10 text-yellow-400">Total Due</div>
                    <div class="w-40 p-4 text-right border-r border-white/10 bg-[#ff3b30]">Balance</div>
                    <div class="w-24 p-4 text-center">Status</div>
                </div>

                <div class="overflow-y-auto custom-scrollbar flex-1 bg-white relative">
                    <div id="ledger-loading" class="absolute inset-0 bg-white/90 z-30 flex items-center justify-center hidden backdrop-blur-sm">
                        <div class="flex flex-col items-center">
                            <svg class="animate-spin h-10 w-10 text-[#ff3b30] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Calculatng Add-on Interest...</span>
                        </div>
                    </div>
                    
                    <table class="w-full text-left border-collapse">
                        <tbody id="modal-ledger-rows" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <div class="w-full lg:w-72 bg-slate-50 flex flex-col shrink-0 border-t lg:border-t-0 z-10 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.05)]">
                <div class="p-6 flex flex-col h-full">
                    
                    <h3 class="text-[#ff3b30] font-black text-xs uppercase tracking-widest border-b border-slate-200 pb-3 mb-4">Payment Summary</h3>

                    <div class="space-y-4 flex-1">
                         <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-200 border-dashed">
                            <span class="font-bold text-slate-500 uppercase">Principal Paid</span>
                            <span class="font-black text-slate-800" id="sum-principal">0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-200 border-dashed">
                            <span class="font-bold text-slate-500 uppercase">Interest Paid</span>
                            <span class="font-black text-slate-800" id="sum-interest">0.00</span>
                        </div>
                         <div class="bg-white p-4 rounded border border-slate-200 shadow-sm mt-2">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Total Collected</span>
                            <span class="block text-2xl font-black text-green-600 leading-none" id="sum-paid">0.00</span>
                        </div>
                    </div>

                    <div class="pt-6 mt-auto space-y-3">
                        <button class="w-full py-3 bg-slate-800 hover:bg-black text-white text-xs font-black uppercase rounded shadow-lg transition-all flex justify-center items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Ledger
                        </button>
                        <button class="w-full py-3 bg-white border border-slate-300 hover:border-[#ff3b30] hover:text-[#ff3b30] text-slate-500 text-xs font-black uppercase rounded transition-all flex justify-center items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0l-4-4m4 4V4"></path></svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // OPEN MODAL FUNCTION
    window.openLedgerModal = function(borrowerData) {
        const modal = document.getElementById('ledgerDetailModal');
        const loader = document.getElementById('ledger-loading');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loader.classList.remove('hidden'); 
        
        // Clear Table
        document.getElementById('modal-ledger-rows').innerHTML = '';

        // --- 1. POPULATE HEADER ---
        document.getElementById('modal-ledger-name').innerText = borrowerData.name;
        document.getElementById('modal-ledger-id').innerText = borrowerData.employe_id;
        document.getElementById('modal-ledger-pn').innerText = borrowerData.pn_number;
        document.getElementById('modal-ledger-pndate').innerText = borrowerData.pn_date;
        document.getElementById('modal-ledger-maturity').innerText = borrowerData.maturity_date;
        document.getElementById('modal-ledger-terms').innerText = borrowerData.term_months + ' Months';
        document.getElementById('modal-ledger-status').innerText = borrowerData.current_status;
        
        // --- ADD-ON RATE CALCULATION ---
        const principal = parseFloat(borrowerData.loan_amount);
        const semiAmort = parseFloat(borrowerData.semi_monthly_amt);
        const totalPaymentsCount = borrowerData.term_months * 2;
        
        // Total Obligation = Amort * Count
        const totalObligation = semiAmort * totalPaymentsCount;
        const totalInterest = totalObligation - principal;
        
        // Add-on Rate Per Month Formula: ((Total Interest / Principal) / Months) * 100
        const rateDecimal = (totalInterest / principal) / borrowerData.term_months;
        const ratePercent = (rateDecimal * 100).toFixed(2);
        
        document.getElementById('modal-ledger-rate').innerText = ratePercent + '% / Mo.';
        document.getElementById('modal-ledger-principal').innerText = '₱ ' + principal.toLocaleString(undefined, {minimumFractionDigits:2});
        document.getElementById('modal-ledger-amort').innerText = '₱ ' + semiAmort.toLocaleString(undefined, {minimumFractionDigits:2});

        // Status Badge
        const statusBadge = document.getElementById('modal-ledger-status');
        if(borrowerData.current_status === 'FULLY PAID') {
            statusBadge.className = "inline-block px-4 py-1.5 bg-slate-200 text-slate-600 text-xs font-black uppercase rounded-full";
        } else {
            statusBadge.className = "inline-block px-4 py-1.5 bg-green-100 text-green-700 text-xs font-black uppercase rounded-full";
        }

        // --- 2. FETCH & RENDER LEDGER ---
        fetchLedgerData(borrowerData)
            .then(transactions => {
                renderLedgerTable(transactions, principal);
                loader.classList.add('hidden');
            });
    }

    window.closeLedgerModal = function() {
        document.getElementById('ledgerDetailModal').classList.remove('flex');
        document.getElementById('ledgerDetailModal').classList.add('hidden');
    }

    // MOCK ADD-ON LOGIC
    function fetchLedgerData(data) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const transactions = [];
                const principal = parseFloat(data.loan_amount);
                const semiAmort = parseFloat(data.semi_monthly_amt);
                const totalPaymentsCount = data.term_months * 2;
                
                // Add-on Logic: Straight Line Split
                // Principal Part is constant. Interest Part is constant.
                const principalPart = principal / totalPaymentsCount;
                const interestPart = semiAmort - principalPart;
                
                let currentBal = principal;

                // Loop
                for (let i = 1; i <= totalPaymentsCount; i++) { 
                    currentBal = currentBal - principalPart;
                    if(currentBal < 0.1) currentBal = 0; // Floating point fix

                    // Mock: Mark first 6 as paid
                    const isPaid = (data.current_status === 'FULLY PAID') ? true : (i <= 6); 
                    
                    transactions.push({
                        scheduled_date: '2025-' + (i < 10 ? '0'+i : i) + '-15', // Mock dates
                        date_paid: isPaid ? '2025-' + (i < 10 ? '0'+i : i) + '-15' : null,
                        principal: principalPart,
                        interest: interestPart,
                        total: semiAmort,
                        balance: currentBal,
                        status: isPaid ? 'PAID' : 'PENDING'
                    });
                    
                    // Limit demo rows to 50
                    if (i >= 50) break; 
                }
                resolve(transactions);
            }, 600);
        });
    }

    function renderLedgerTable(transactions, initialPrincipal) {
        const tbody = document.getElementById('modal-ledger-rows');
        tbody.innerHTML = '';
        
        let totalPrincipalPaid = 0;
        let totalInterestPaid = 0;
        let totalPaid = 0;
        let finalBalance = initialPrincipal; 

        transactions.forEach(txn => {
            if(txn.status === 'PAID') {
                totalPrincipalPaid += txn.principal;
                totalInterestPaid += txn.interest;
                totalPaid += txn.total;
                finalBalance = txn.balance; 
            }

            const rowClass = txn.status === 'PAID' ? 'bg-white' : 'bg-yellow-50/30';
            const statusClass = txn.status === 'PAID' 
                ? 'bg-green-100 text-green-700' 
                : 'bg-yellow-100 text-yellow-700';
            const datePaidText = txn.date_paid ? `<span class="font-bold text-slate-700">${txn.date_paid}</span>` : '<span class="text-slate-300 italic">--</span>';

            const tr = document.createElement('tr');
            tr.className = `hover:bg-slate-50 transition-colors border-b border-slate-100 ${rowClass}`;
            tr.innerHTML = `
                <td class="px-3 py-3 text-xs font-bold text-slate-600 border-r border-slate-100 text-center">${txn.scheduled_date}</td>
                <td class="px-3 py-3 text-xs text-center border-r border-slate-100">${datePaidText}</td>
                <td class="px-3 py-3 text-xs font-bold text-slate-600 text-right border-r border-slate-100">
                    ${txn.principal.toLocaleString(undefined, {minimumFractionDigits:2})}
                </td>
                <td class="px-3 py-3 text-xs font-bold text-slate-600 text-right border-r border-slate-100">
                     ${txn.interest.toLocaleString(undefined, {minimumFractionDigits:2})}
                </td>
                <td class="px-3 py-3 text-xs font-black text-slate-800 text-right border-r border-slate-100 bg-yellow-50/50">
                     ${txn.total.toLocaleString(undefined, {minimumFractionDigits:2})}
                </td>
                <td class="px-3 py-3 text-sm font-black text-[#ff3b30] text-right border-r border-slate-100">
                    ${txn.balance.toLocaleString(undefined, {minimumFractionDigits:2})}
                </td>
                <td class="px-3 py-3 text-center">
                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-black uppercase ${statusClass}">${txn.status}</span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('modal-ledger-balance').innerText = '₱ ' + finalBalance.toLocaleString(undefined, {minimumFractionDigits:2});
        document.getElementById('sum-principal').innerText = '₱ ' + totalPrincipalPaid.toLocaleString(undefined, {minimumFractionDigits:2});
        document.getElementById('sum-interest').innerText = '₱ ' + totalInterestPaid.toLocaleString(undefined, {minimumFractionDigits:2});
        document.getElementById('sum-paid').innerText = '₱ ' + totalPaid.toLocaleString(undefined, {minimumFractionDigits:2});
    }
</script>