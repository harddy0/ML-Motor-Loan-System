<?php
// Define the base path to handle the subfolder structure
$baseUrl = '/ML-MOTOR-LOAN-SYSTEM/public';
$userName = $_SESSION['full_name'] ?? 'Admin User'; // Get dynamic name
?>

<aside id="sidebar" class="w-52 bg-[#e11d48] text-white flex flex-col transition-all duration-300 ease-in-out z-10 h-full sticky top-0 overflow-x-hidden">
    
    <div class="p-6 flex gap-20 items-center border-b border-white/20 min-w-[208px] shrink-0">
        <span class="sidebar-text font-bold tracking-[0.2em] text-sm">MENU</span>
        <button onclick="toggleSidebar()" class="p-1 hover:bg-white/10 rounded transition-colors focus:outline-none" title="Toggle Sidebar Lock">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <ul class="space-y-1 py-4">
            
            <li class="<?= ($currentPage ?? '') === 'dashboard' ? 'bg-black/20 border-l-4 border-white' : '' ?>">
                <a href="<?= $baseUrl ?>/dashboard/" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">Dashboard</span>
                </a>
            </li>

            <li class="<?= ($currentPage ?? '') === 'upload' ? 'bg-black/20 border-l-4 border-white' : '' ?>">
                <a href="<?= $baseUrl ?>/upload/" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>    
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">Upload</span>   
                </a>
            </li>

            <li class="<?= ($currentPage ?? '') === 'borrowers' ? 'bg-black/20 border-l-4 border-white' : '' ?>">
                <a href="<?= $baseUrl ?>/borrowers/" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>    
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">Borrowers</span>                
                </a>
            </li>

            <li class="relative">
                <button type="button" onclick="handleReportsClick(event)" class="w-full flex items-center gap-4 px-6 py-4 hover:bg-black/10 transition-all focus:outline-none cursor-pointer group">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg id="reports-arrow" class="w-3 h-3 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">Reports</span>
                </button>
                <ul id="reports-menu" class="max-h-0 overflow-hidden bg-black/10 transition-all duration-300 ease-in-out">
                    <li><a href="<?= $baseUrl ?>/reports/deduction/" class="block pl-10 pr-6 py-3 text-[10px] font-bold tracking-widest hover:bg-white/10 border-b border-white/5 uppercase">Deduction</a></li>
                    <li><a href="<?= $baseUrl ?>/reports/ledger/" class="block pl-10 pr-6 py-3 text-[10px] font-bold tracking-widest hover:bg-white/10 border-b border-white/5 uppercase">Ledger</a></li>
                    <li><a href="<?= $baseUrl ?>/reports/running_receivables/" class="block pl-10 pr-6 py-3 text-[10px] font-bold tracking-widest hover:bg-white/10 uppercase">Running Receivables</a></li>
                </ul>
            </li>

            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN'): ?>
            
            <li class="<?= ($currentPage ?? '') === 'borrower-mgt' ? 'bg-black/20 border-l-4 border-red-500' : '' ?>">
                <a href="<?= $baseUrl ?>/borrower-mgt/" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>    
                    <span class="sidebar-text text-xs font-bold tracking-widest text-red-400 uppercase whitespace-nowrap">Wipe Data</span>                
                </a>
            </li>

            <li class="<?= ($currentPage ?? '') === 'user-mgt' ? 'bg-black/20 border-l-4 border-white' : '' ?>">
                <a href="<?= $baseUrl ?>/user-mgt/" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>    
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">User Mgt</span>                
                </a>
            </li>

            <?php endif; ?>
            <li class="mt-4 border-t border-white/10">
                <a href="<?= $baseUrl ?>/actions/logout.php" class="flex items-center gap-9 px-6 py-4 hover:bg-black/10 transition-all group">
                    <svg class="w-6 h-6 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>    
                    <span class="sidebar-text text-xs font-bold tracking-widest uppercase whitespace-nowrap">Logout</span>                
                </a>
            </li>
        </ul>
    </nav>

    <div class="p-6 bg-black/10 flex gap-14 items-center whitespace-nowrap overflow-hidden min-w-[256px] shrink-0 border-t border-white/10">
        <span class="sidebar-text text-[10px] font-bold tracking-widest uppercase truncate max-w-[150px]" title="<?= htmlspecialchars($userName) ?>">
            <?= htmlspecialchars($userName) ?>
        </span>
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        </div>
    </div>
</aside>

<script>
// State to track if sidebar is explicitly pinned by the user
let isSidebarPinned = true;

// Logic to handle Reports click and Sidebar expansion
function handleReportsClick(event) {
    event.preventDefault();
    const sidebar = document.getElementById('sidebar');
    const menu = document.getElementById('reports-menu');
    const arrow = document.getElementById('reports-arrow');

    // Ensure it's expanded if user tries to interact with submenus
    if (sidebar.classList.contains('w-20')) {
        expandSidebar();
        // If it was collapsed and user clicked menu, assume they want to interact
        setTimeout(() => {
            menu.style.maxHeight = menu.scrollHeight + "px";
            arrow.classList.add('rotate-180');
        }, 100);
    } else {
        if (menu.style.maxHeight === '0px' || menu.style.maxHeight === '') {
            menu.style.maxHeight = menu.scrollHeight + "px";
            arrow.classList.add('rotate-180');
        } else {
            menu.style.maxHeight = '0px';
            arrow.classList.remove('rotate-180');
        }
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    
    // Toggle the pinned state
    if (isSidebarPinned) {
        // Unpin: collapse it
        isSidebarPinned = false;
        collapseSidebar();
    } else {
        // Pin: expand it
        isSidebarPinned = true;
        expandSidebar();
    }
}

function expandSidebar() {
    const sidebar = document.getElementById('sidebar');
    const texts = document.querySelectorAll('.sidebar-text');
    sidebar.classList.replace('w-20', 'w-52');
    setTimeout(() => { texts.forEach(el => el.classList.remove('hidden')); }, 150);
}

function collapseSidebar() {
    const sidebar = document.getElementById('sidebar');
    const texts = document.querySelectorAll('.sidebar-text');
    const menu = document.getElementById('reports-menu');
    const arrow = document.getElementById('reports-arrow');
    sidebar.classList.replace('w-52', 'w-20');
    texts.forEach(el => el.classList.add('hidden'));
    
    // Auto-close submenu when collapsing
    menu.style.maxHeight = '0px';
    arrow.classList.remove('rotate-180');
}
</script>