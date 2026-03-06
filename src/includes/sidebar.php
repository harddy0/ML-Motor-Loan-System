<?php
// Define the base path to handle the subfolder structure
$baseUrl = '/ML-MOTOR-LOAN-SYSTEM/public';
$userName = $_SESSION['full_name'] ?? 'Admin User'; 
?>

<aside id="sidebar" class="w-64 bg-[#ce1126] text-white flex flex-col z-10 h-full sticky top-0 overflow-x-hidden shadow-xl shadow-red-900/20" style="transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1);" onmouseenter="expandSidebarOnHover()" onmouseleave="collapseSidebarOnLeave()">
    
    <div class="px-6 py-5 flex justify-between items-center border-b border-white/10 shrink-0 bg-[#ce1126] w-full">
        <span class="sidebar-text font-bold tracking-widest text-xs text-white/90">MAIN MENU</span>
        <button onclick="toggleSidebarPin()" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors focus:outline-none" title="Pin Sidebar" id="pin-button">
            <svg id="pin-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <ul class="space-y-1 py-1">
            
            <li class="<?= ($currentPage ?? '') === 'dashboard' ? 'bg-black/25 border-l-4 border-white' : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/dashboard/" class="flex items-center gap-4 px-5 py-1 hover:bg-black/10 transition-all group">
                    <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">Dashboard</span>
                </a>
            </li>

            <li class="relative border-l-4 border-transparent">
                <button type="button" onclick="handleUploadsClick(event)" class="w-full flex items-center justify-between px-5 py-1 hover:bg-black/10 transition-all focus:outline-none cursor-pointer group">
                    <div class="flex items-center gap-4">
                        <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">Upload</span>
                    </div>
                    <svg id="uploads-arrow" class="w-4 h-4 sidebar-text opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <ul id="uploads-menu" class="max-h-0 overflow-hidden bg-black/20 shadow-inner" style="transition: max-height 300ms cubic-bezier(0.4, 0, 0.2, 1);">
                    <li><a href="<?= $baseUrl ?>/upload/payroll/" class="block pl-[3.25rem] pr-6 py-1 text-xs font-bold tracking-wider hover:bg-white/10 border-b border-white/5 uppercase text-white/90 hover:text-white transition-colors">Payroll Deduction</a></li>
                    <li><a href="<?= $baseUrl ?>/upload/ledger/" class="block pl-[3.25rem] pr-6 py-1 text-xs font-bold tracking-wider hover:bg-white/10 uppercase text-white/90 hover:text-white transition-colors">Ledger</a></li>
                </ul>
            </li>

            <li class="<?= ($currentPage ?? '') === 'borrowers' ? 'bg-black/25 border-l-4 border-white' : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/borrowers/" class="flex items-center gap-4 px-5 py-1 hover:bg-black/10 transition-all group">
                    <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>    
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">Borrowers</span>                
                </a>
            </li>

            <li class="relative border-l-4 border-transparent">
                <button type="button" onclick="handleReportsClick(event)" class="w-full flex items-center justify-between px-5 py-1 hover:bg-black/10 transition-all focus:outline-none cursor-pointer group">
                    <div class="flex items-center gap-4">
                        <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">Reports</span>
                    </div>
                    <svg id="reports-arrow" class="w-4 h-4 sidebar-text opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <ul id="reports-menu" class="max-h-0 overflow-hidden bg-black/20 shadow-inner" style="transition: max-height 300ms cubic-bezier(0.4, 0, 0.2, 1);">
                    <li><a href="<?= $baseUrl ?>/reports/deduction/" class="block pl-[3.25rem] pr-6 py-1 text-xs font-bold tracking-wider hover:bg-white/10 border-b border-white/5 uppercase text-white/90 hover:text-white transition-colors">Deductions</a></li>
                    <li><a href="<?= $baseUrl ?>/reports/ledger/" class="block pl-[3.25rem] pr-6 py-1 text-xs font-bold tracking-wider hover:bg-white/10 border-b border-white/5 uppercase text-white/90 hover:text-white transition-colors">Ledgers</a></li>
                    <li><a href="<?= $baseUrl ?>/reports/running_receivables/" class="block pl-[3.25rem] pr-6 py-1 text-xs font-bold tracking-wider hover:bg-white/10 uppercase text-white/90 hover:text-white transition-colors">Receivables</a></li>
                </ul>
            </li>

            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'ADMIN'): ?>
            <li class="<?= ($currentPage ?? '') === 'user-mgt' ? 'bg-black/25 border-l-4 border-white' : 'border-l-4 border-transparent hover:border-white/30' ?> mt-4 pt-2 border-t border-white/10 transition-colors">
                <a href="<?= $baseUrl ?>/user-mgt/" class="flex items-center gap-4 px-5 py-1 hover:bg-black/10 transition-all group">
                    <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>    
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">User Management</span>                
                </a>
            </li>
            <?php endif; ?>

            <li class="mt-4 border-t border-white/10 border-l-4 border-transparent hover:border-white/30 transition-colors">
                <a href="<?= $baseUrl ?>/actions/logout.php" class="flex items-center gap-4 px-5 py-1 hover:bg-black/10 transition-all group">
                    <svg class="w-[22px] h-[22px] opacity-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>    
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap drop-shadow-sm">Logout</span>                
                </a>
            </li>
        </ul>
    </nav>

    <div class="px-5 py-4 bg-[#ce1126] flex justify-between items-center whitespace-nowrap overflow-hidden w-full shrink-0 border-t border-white/10">
        <div class="flex flex-col justify-center sidebar-text transition-all duration-300">
            <span class="text-[12px] font-bold tracking-wider uppercase truncate max-w-[150px] text-white" title="<?= htmlspecialchars($userName) ?>">
                <?= htmlspecialchars($userName) ?>
            </span>
            <span class="text-[10px] text-white/60 tracking-widest uppercase">
                <?= isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'USER' ?>
            </span>
        </div>
        <div class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center shrink-0 shadow-inner">
            <svg class="w-5 h-5 text-white/80" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
        </div>
    </div>
</aside>

<script>
let isSidebarPinned = true;
let isHoveringOver = false;

// Close menus when sidebar is marked as not pinned (for auto-collapse behavior)
const sidebar = document.getElementById('sidebar');
if (sidebar) {
    sidebar.addEventListener('mouseenter', () => {
        isHoveringOver = true;
    });
    sidebar.addEventListener('mouseleave', () => {
        isHoveringOver = false;
    });
}

function expandSidebarOnHover() {
    expandSidebar();
}

function collapseSidebarOnLeave() {
    if (!isSidebarPinned) {
        setTimeout(() => {
            if (!isHoveringOver) {
                collapseSidebar();
            }
        }, 100);
    }
}

function handleUploadsClick(event) {
    event.preventDefault();
    const sidebar = document.getElementById('sidebar');
    const menu = document.getElementById('uploads-menu');
    const arrow = document.getElementById('uploads-arrow');

    if (sidebar.classList.contains('w-20')) {
        expandSidebar();
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

function handleReportsClick(event) {
    event.preventDefault();
    const sidebar = document.getElementById('sidebar');
    const menu = document.getElementById('reports-menu');
    const arrow = document.getElementById('reports-arrow');

    if (sidebar.classList.contains('w-20')) {
        expandSidebar();
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

function toggleSidebarPin() {
    isSidebarPinned = !isSidebarPinned;
    const pinButton = document.getElementById('pin-button');
    
    if (isSidebarPinned) {
        expandSidebar();
        pinButton.title = "Unpin Sidebar";
    } else {
        pinButton.title = "Pin Sidebar";
    }
}

function expandSidebar() {
    const sidebar = document.getElementById('sidebar');
    const texts = document.querySelectorAll('.sidebar-text');
    sidebar.classList.replace('w-20', 'w-64');
    setTimeout(() => { 
        texts.forEach(el => {
            el.classList.remove('hidden');
            el.style.opacity = '1';
            el.style.transition = 'opacity 300ms 100ms cubic-bezier(0.4, 0, 0.2, 1)';
        });
    }, 280);
}

function collapseSidebar() {
    const sidebar = document.getElementById('sidebar');
    const texts = document.querySelectorAll('.sidebar-text');
    const reportsMenu = document.getElementById('reports-menu');
    const reportsArrow = document.getElementById('reports-arrow');
    const uploadsMenu = document.getElementById('uploads-menu');
    const uploadsArrow = document.getElementById('uploads-arrow');

    texts.forEach(el => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 200ms cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    setTimeout(() => {
        texts.forEach(el => el.classList.add('hidden'));
        sidebar.classList.replace('w-64', 'w-20');
    }, 200);
    
    reportsMenu.style.maxHeight = '0px';
    reportsArrow.classList.remove('rotate-180');
    uploadsMenu.style.maxHeight = '0px';
    uploadsArrow.classList.remove('rotate-180');
}
</script>