<?php
$noLayout = true;
$pageTitle = "Security Update | ML Motorcycle Loan";
require_once __DIR__ . '/../../src/includes/init.php';

// Redundancy check: If they don't need to change password, send them to dashboard
if (empty($_SESSION['must_change_password'])) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

// Retrieve error messages
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'League Spartan', sans-serif; }</style>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">

    <div id="mandatoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/90 backdrop-blur-md transition-opacity duration-300">
        <div class="bg-[#dc2626] w-full max-w-sm rounded-3xl shadow-[0_0_50px_rgba(220,38,38,0.4)] border border-red-500 overflow-hidden transform transition-all scale-100 p-8 text-center relative">
            
            <div class="absolute inset-0 bg-gradient-to-b from-white/10 to-transparent pointer-events-none"></div>

            <div class="relative z-10">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white mb-5 shadow-lg shadow-red-900/50 transform animate-bounce-slow">
                    <svg class="h-8 w-8 text-[#dc2626]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                
                <h3 class="text-white font-black uppercase tracking-[0.2em] text-lg mb-2 drop-shadow-md">ACTION REQUIRED</h3>
                
                <p class="text-sm font-medium text-red-100 leading-relaxed mb-8 drop-shadow-sm">
                    DEFAULT PASSWORD DETECTED.<br>CHANGE REQUIRED TO PROCEED.
                </p>
                
                <button id="acknowledgeBtn" class="w-full bg-white hover:bg-slate-100 text-[#dc2626] py-4 rounded-xl text-xs font-black uppercase tracking-[0.2em] shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    ACKNOWLEDGE
                </button>
            </div>
        </div>
    </div>

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 p-8 md:p-10 relative overflow-hidden">

        <div class="text-center mb-8">
            <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="M Lhuillier" class="h-14 w-auto mx-auto mb-5">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">MANDATORY UPDATE</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">UPDATE PASSWORD TO PROCEED</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-[#dc2626] p-4 flex items-start gap-3 rounded-r-lg">
                <svg class="w-5 h-5 text-[#dc2626] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-xs font-bold text-red-700 mt-0.5"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-6">
            
            <div class="space-y-2">
                <label for="new_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">NEW PASSWORD</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#dc2626] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-800 outline-none focus:border-[#dc2626] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="space-y-2">
                <label for="confirm_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">CONFIRM NEW PASSWORD</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#dc2626] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-800 outline-none focus:border-[#dc2626] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-[#dc2626] hover:bg-red-700 text-white py-4 rounded-xl text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    UPDATE
                </button>
            </div>
        </form>

        <div class="mt-8 text-center border-t border-slate-100 pt-6">
            <a href="<?= BASE_URL ?>/public/actions/logout.php" class="text-[10px] font-black text-slate-400 hover:text-[#dc2626] uppercase tracking-widest transition-colors flex justify-center items-center gap-2 group">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                CANCEL
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('mandatoryModal');
            const acknowledgeBtn = document.getElementById('acknowledgeBtn');
            const newPasswordField = document.getElementById('new_password');

            acknowledgeBtn.addEventListener('click', () => {
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
                
                setTimeout(() => {
                    modal.style.display = 'none';
                    newPasswordField.focus();
                }, 300);
            });
        });
    </script>
</body>
</html>