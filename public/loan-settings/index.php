<?php
$currentPage = 'loan-settings';
require_once __DIR__ . '/../../src/includes/init.php';

// KICK OUT UNAUTHORIZED USERS
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'ADMIN') {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>UNAUTHORIZED ACCESS</h1>");
}
?>

<div class="-mt-4 h-full overflow-y-auto custom-scrollbar pr-2">
    
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl text-slate-800 tracking-tight">System Settings</h1>
            <p class="text-slate-500 font-mono text-sm mt-1">Configure global parameters and system defaults.</p>
        </div>
    </div>

    <div id="flash-message-container">
        <?php if(isset($_GET['success'])): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 rounded-lg flex items-center shadow-sm max-w-4xl flash-msg">
                <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="font-mono text-[13px]"><?= htmlspecialchars($_GET['success']); ?></span>
                <button type="button" class="ml-auto text-sm font-semibold px-3 py-1 bg-white border rounded-md text-emerald-600 hover:bg-emerald-100 flash-ok-btn">OK</button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg flex items-center shadow-sm max-w-4xl flash-msg">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span class="font-mono text-[13px]"><?= htmlspecialchars($_GET['error']); ?></span>
                <button type="button" class="ml-auto text-sm font-semibold px-3 py-1 bg-white border rounded-md text-red-600 hover:bg-red-100 flash-ok-btn">OK</button>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
        
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-3.5 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <h2 class="text-[13px] font-bold text-slate-700 uppercase tracking-widest">Financial Parameters</h2>
            </div>
            
            <div class="p-8 flex-1 relative">
                <div id="form-loader" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-[#ce1126]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>

                <form action="<?= BASE_URL ?>/public/actions/update_settings.php" method="POST" class="h-full flex flex-col">
                    <div class="mb-8 flex-1">
                        <label for="add_on_rate" class="block text-[11px] font-bold text-slate-400 tracking-wider uppercase mb-2">Monthly Add-On Rate (%)</label>
                        <div class="relative flex items-center max-w-[200px]">
                            <input type="number" step="0.001" min="0" name="add_on_rate" id="add_on_rate" required placeholder="0.000"
                                   class="w-full font-mono px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#ce1126] focus:border-transparent transition-all text-slate-800 text-xl font-medium">
                            <div class="absolute right-0 inset-y-0 flex items-center pr-5 pointer-events-none">
                                <span class="text-slate-400 font-bold text-xl">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-start pt-5 border-t border-slate-100">
                        <button type="submit" class="bg-[#ce1126] px-8 py-2.5 text-white text-[13px] font-bold tracking-wider uppercase rounded-md hover:bg-red-800 transition-colors shadow-md shadow-red-900/20 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1 flex flex-col gap-6">
            
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden relative">
                 <div id="audit-loader" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>

                <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Configuration Log</h3>
                </div>
                
                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-0.5">Last Updated On</p>
                            <p id="ui-updated-at" class="text-[13px] font-medium text-slate-700">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50/50 rounded-xl border border-red-100 p-5 shadow-sm relative overflow-hidden">
                <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-red-100/50" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2 text-[#ce1126]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h3 class="text-[11px] font-bold uppercase tracking-widest">Important Notice</h3>
                    </div>
                    <p class="text-[12px] text-slate-600 leading-relaxed font-medium">
                        Changes to this rate immediately apply to all <strong class="text-[#ce1126]">newly created</strong> manual loans and future batch imports. <br><br>Existing active/ongoing loans remain safely locked to their originally contracted rates.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Fetch system settings via API on page load
    fetch('<?= BASE_URL ?>/public/api/get_system_settings.php')
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                // Populate the UI input with the fetched current value
                document.getElementById('add_on_rate').value = res.data.rate_percent;
                document.getElementById('ui-updated-at').textContent = res.data.updated_at;
            } else {
                console.error("Failed to load settings:", res.error);
                document.getElementById('ui-updated-at').textContent = 'Error loading data';
            }
        })
        .catch(err => {
            console.error("Network error loading settings:", err);
            document.getElementById('ui-updated-at').textContent = 'Network Error';
        })
        .finally(() => {
            // Remove the loading spinners regardless of success/fail
            const formLoader = document.getElementById('form-loader');
            const auditLoader = document.getElementById('audit-loader');
            if(formLoader) formLoader.remove();
            if(auditLoader) auditLoader.remove();
        });

    // 2. Dismiss flash messages and clean URL
    document.querySelectorAll('.flash-ok-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrap = btn.closest('.flash-msg');
            if (wrap) wrap.remove();
            
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({path: url.href}, '', url.href);
            }
        });
    });
});
</script>