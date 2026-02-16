<?php
$pageTitle = "DASHBOARD";
$currentPage = "dashboard";
require_once __DIR__ . '/../src/includes/init.php';
?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-xl font-bold text-[#b04b4b] tracking-tight uppercase">DASHBOARD</h1>
    
    <div class="flex gap-2">
        <div class="flex items-center gap-2 bg-gray-100 rounded-full px-4 py-1 border border-gray-200">
            <span class="text-[10px] font-bold text-gray-400 uppercase">FROM</span>
            <select class="bg-transparent text-xs font-bold outline-none"><option>--</option></select>
        </div>
        <div class="flex items-center gap-2 bg-gray-100 rounded-full px-4 py-1 border border-gray-200">
            <span class="text-[10px] font-bold text-gray-400 uppercase">TO</span>
            <select class="bg-transparent text-xs font-bold outline-none"><option>--</option></select>
        </div>
    </div>
</div>