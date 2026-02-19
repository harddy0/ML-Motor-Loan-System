<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../../src/includes/init.php'; 

// Mock Data
$totalLoanAmount = 850000;
$paymentAmount = 56950; 
$outstandingAmount = 793050; 
$incomeAmount = 6950; 

// Percentages for the Bar
$paymentWidth = ($paymentAmount / $totalLoanAmount) * 100;
$outstandingWidth = ($outstandingAmount / $totalLoanAmount) * 100;
?> 

   <div class="flex flex-col xl:flex-row justify-between items-end mb-8 gap-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">
            System <span class="text-[#e11d48]">Dashboard</span>
        </h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">
                AS OF <?= date('F d, Y') ?>
            </p>
        </div>
    </div>
    
    <button onclick="location.reload()" class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-600 uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
        Refresh Data
    </button>
</div>

<div class="w-full flex-1 min-h-0 flex flex-col gap-8">
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
        
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all border-t-4 border-t-amber-500">
            <div class="flex justify-between items-start ">
                <h2 class="text-slate-400 font-black text-[10px] tracking-widest uppercase">Payroll Deduction</h2>
                <span class="px-2 py-1 rounded-md text-[9px] font-black bg-green-50 text-green-600">↑ +12.5%</span>
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-black text-slate-800 tracking-tighter">12,450</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Units Processed</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all border-t-4 border-t-green-500">
            <div class="flex justify-between items-start ">
                <h2 class="text-slate-400 font-black text-[10px] tracking-widest uppercase">Ledgers</h2>
                <span class="px-2 py-1 rounded-md text-[9px] font-black bg-green-100 text-green-600">Total Cumulative</span>
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-black text-slate-800 tracking-tighter">8,932</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Active Records Found</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all border-t-4 border-t-[#e11d48]">
            <div class="flex justify-between items-start ">
                <h2 class="text-slate-400 font-black text-[10px] tracking-widest uppercase">Active Borrowers</h2>
                <span class="px-2 py-1 rounded-md text-[9px] font-black bg-orange-50 text-orange-600 uppercase">Live</span>
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-black text-slate-800 tracking-tighter">1,048</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Verified Accounts</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all border-t-4 border-t-blue-500">
            <div class="flex justify-between items-start">
                <h2 class="text-slate-400 font-black text-[10px] tracking-widest uppercase">Fully Paid</h2>
                <span class="px-2 py-1 rounded-md text-[9px] font-black bg-blue-50 text-blue-600 uppercase">Finalized</span>
            </div>
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-black text-slate-800 tracking-tighter">735</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Closed Portfolios</p>
        </div>
    </div> 

    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all border-t-4 border-t-[#e11d48]">
        <div class="flex justify-between items-start mb-5">
        <div>
            <h2 class="text-slate-800 font-black text-sm tracking-tight uppercase">
                Running Accounts <span class="text-[#e11d48]">Receivable</span>
            </h2>
            <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest mt-1">Live Cumulative Portfolio Status</p>
        </div>
        <div class="text-right">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Loan Amount</p>
            <span class="text-xl font-black text-slate-800">₱850,000</span>
        </div>
        </div>

    <div class="space-y-4">
        <div class="flex justify-between items-end">
            <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Collection Progress</span>
            <span class="text-[11px] font-black text-[#e11d48]">6.7% Collected</span>
        </div>
        
        <div class="relative w-full h-10 bg-slate-100 flex overflow-hidden border border-slate-200">
            <div class="h-full bg-[#e11d48] flex items-center justify-center" style="width: 6.7%">
                <span class="text-[9px] text-white font-black tracking-widest uppercase px-4">Paid</span>
            </div>
            <div class="h-full bg-slate-200 flex items-center px-4" style="width: 93.3%">
                <span class="text-[9px] text-slate-500 font-black tracking-widest uppercase">Outstanding: ₱793,050</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6 mt-5 pt-4 border-t border-slate-50">
        <div class="text-center sm:text-left">
            <span class="text-[9px] font-black text-slate-400 uppercase block tracking-widest mb-1">Total Payments</span>
            <span class="text-lg font-black text-slate-800">₱56,950</span>
        </div>
        <div class="text-center sm:text-left border-l border-slate-100 pl-6">
            <span class="text-[9px] font-black text-green-600 uppercase block tracking-widest mb-1">Interest Income</span>
            <span class="text-lg font-black text-green-600">₱6,950</span>
        </div>
        <div class="text-center sm:text-left border-l border-slate-100 pl-6">
            <span class="text-[9px] font-black text-[#e11d48] uppercase block tracking-widest mb-1">Net Outstanding</span>
            <span class="text-lg font-black text-slate-800">₱793,050</span>
        </div>
    </div>
</div>
</div>