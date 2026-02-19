<?php
// Moves up two levels: out of 'upload', out of 'public', and into 'src'
require_once __DIR__ . '/../../src/includes/init.php'; 
$current_page = basename($_SERVER['PHP_SELF']);
  
  // Define the "Active" and "Inactive" styles as variables for cleaner code
  $active_style = "bg-[#e11d48] text-white border-transparent shadow-sm"; 
  $inactive_style = "bg-white text-slate-500 border-slate-200 hover:bg-slate-50 hover:border-slate-300";

?>

<div class="flex flex-col lg:flex-row justify-between items-end mb-3 pb-2 shrink-0">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">
                PAYROLL DEDUCTION<span class="text-[#ff3b30]"> Imported logs</span>
            </h1>
        </div>
        <div class="flex items-center bg-white border border-slate-300 rounded-full shadow-sm overflow-hidden">
            <div class="px-4 py-2 border-r border-slate-100 flex items-center gap-3">
                <span class="text-[10px] font-black text-slate-400 uppercase">From</span>
                <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
            </div>
            <div class="px-4 py-2 flex items-center gap-3 border-r border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase">To</span>
                <input type="date" class="text-xs font-bold text-slate-700 outline-none bg-transparent">
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4 shrink-0">
        <div class="relative w-full md:w-1/2">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" placeholder="SEARCH NAME OR ID..." class="w-full pl-12 pr-4 py-3 bg-white border-2 border-slate-200 rounded-full text-xs font-bold outline-none uppercase placeholder:text-slate-300 transition-colors focus:border-[#ff3b30]">
        </div>
        <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-full border border-slate-100">
            <a href="index.php" 
            class="px-6 py-2 rounded-full text-[10px] font-bold uppercase transition-all duration-200 
            <?php echo ($current_page == 'index.php') ? $active_style : $inactive_style; ?>">
                Import
            </a>

            <a href="history.php" 
            class="px-6 py-2 rounded-full text-[10px] font-bold uppercase transition-all duration-200 
            <?php echo ($current_page == 'history.php') ? $active_style : $inactive_style; ?>">
                History
            </a>
        </div>
    </div>

<div class="space-y-6">
    
    <div x-data="{ open: true }" class="bg-white rounded border-2 border-slate-200 shadow-sm overflow-hidden">
        <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-3 bg-slate-100 border-b-2 border-slate-200">
            <span class="text-[11px] font-black text-slate-600 uppercase tracking-widest">Recent <span class="ml-2">▲</span></span>
        </button>

        <div x-show="open" class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[#8a3333] font-black text-[10px] uppercase border-b border-slate-100">
                        <th class="px-6 py-4 text-center">IDNO</th>
                        <th class="px-6 py-4 text-center">PAYROLL DATE</th>
                        <th class="px-6 py-4">FIRST NAME</th>
                        <th class="px-6 py-4">LAST NAME</th>
                        <th class="px-6 py-4 text-center">AMOUNT PAID</th>
                        <th class="px-6 py-4 text-center">REGION</th>
                        <th class="px-6 py-4 text-center">Date Imported</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px] font-bold text-slate-700">
                    <?php 
                    $mock_history = [
                        ['id' => '20150428', 'date' => '01/30/2026', 'fname' => 'REMARIM', 'lname' => 'CLARISA', 'amount' => '3,825.00', 'region' => 'HEAD OFFICE', 'd_imported'=> '01/30/2026'],
                        ['id' => '20190617', 'date' => '01/30/2026', 'fname' => 'GOZON JR', 'lname' => 'FRANCIS', 'amount' => '1,585.00', 'region' => 'HEAD OFFICE','d_imported'=> '01/30/2026'],
                        ['id' => '20230445', 'date' => '01/30/2026', 'fname' => 'DE GUZMAN', 'lname' => '', 'amount' => '3,570.00', 'region' => 'HEAD OFFICE', 'd_imported'=> '01/30/2026']
                    ];
                    foreach($mock_history as $row): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 text-center bg-yellow-100/30 border-r border-slate-100"><?= $row['id'] ?></td>
                        <td class="px-6 py-3 text-center border-r border-slate-100"><?= $row['date'] ?></td>
                        <td class="px-6 py-3 bg-yellow-100/30 border-r border-slate-100 uppercase"><?= $row['fname'] ?></td>
                        <td class="px-6 py-3 border-r border-slate-100 uppercase"><?= $row['lname'] ?></td>
                        <td class="px-6 py-3 text-center bg-yellow-100/30 font-black italic border-r border-slate-100"><?= $row['amount'] ?></td>
                        <td class="px-6 py-3 text-center uppercase"><?= $row['region'] ?></td>
                        <td class="px-6 py-3 text-center uppercase"><?= $row['d_imported'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-slate-100 rounded border-2 border-slate-200 px-6 py-3 flex justify-between items-center cursor-pointer hover:bg-slate-200 transition-all">
        <span class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Last Week <span class="ml-2">▼</span></span>
    </div>

    <div class="bg-slate-100 rounded border-2 border-slate-200 px-6 py-3 flex justify-between items-center cursor-pointer hover:bg-slate-200 transition-all">
        <span class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Last Month <span class="ml-2">▼</span></span>
    </div>
</div>