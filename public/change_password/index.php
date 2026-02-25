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

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-8 md:p-10 relative overflow-hidden">

        <div class="text-center mb-8">
            <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="M Lhuillier" class="h-12 w-auto mx-auto mb-4">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Security Update</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Change Default Password</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-[#ff3b30] p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-[#ff3b30] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-xs font-bold text-red-700 mt-0.5"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-6">
            
            <div class="space-y-2">
                <label for="new_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">New Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-lg text-sm font-bold text-slate-800 outline-none focus:border-[#ff3b30] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="space-y-2">
                <label for="confirm_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirm New Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-lg text-sm font-bold text-slate-800 outline-none focus:border-[#ff3b30] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-[#ff3b30] hover:bg-[#d63229] text-white py-4 rounded-lg text-xs font-black uppercase tracking-[0.2em] shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    Update Password
                </button>
            </div>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-6">
            <a href="<?= BASE_URL ?>/public/actions/logout.php" class="text-[10px] font-black text-slate-400 hover:text-[#ff3b30] uppercase tracking-widest transition-colors flex justify-center items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Cancel & Logout
            </a>
        </div>
    </div>

</body>
</html>