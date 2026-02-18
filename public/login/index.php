<?php
$noLayout = true;
$pageTitle = "Login | ML Motorcycle Loan";
require_once __DIR__ . '/../../src/includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

// Retrieve messages from Session (set by Actions)
$error = $_SESSION['error'] ?? $_SESSION['flash_error'] ?? null;
$success = $_SESSION['flash_success'] ?? null;

// Clear messages after displaying
unset($_SESSION['error'], $_SESSION['flash_error'], $_SESSION['flash_success']);
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
        
        <a href="/ML-MOTOR-LOAN-SYSTEM/" class="absolute top-6 left-6 text-slate-400 hover:text-[#ff3b30] transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>

        <div class="text-center mb-8">
            <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="M Lhuillier" class="h-12 w-auto mx-auto mb-4">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">System Login</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Enter your credentials</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                <p class="text-xs font-bold text-green-700"><?= $success ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-[#ff3b30] p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-[#ff3b30] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-xs font-bold text-red-700 mt-0.5"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/login.php" class="space-y-6">
            <div class="space-y-2">
                <label for="username" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Username / ID</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input type="text" name="username" id="username" required placeholder="admin"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-lg text-sm font-bold text-slate-800 outline-none focus:border-[#ff3b30] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                    <label for="password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Password</label>
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#ff3b30] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-lg text-sm font-bold text-slate-800 outline-none focus:border-[#ff3b30] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-[#ff3b30] hover:bg-[#d63229] text-white py-4 rounded-lg text-xs font-black uppercase tracking-[0.2em] shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    Sign In
                </button>
            </div>
        </form>

        <div class="mt-8 text-center border-t border-slate-100 pt-6">
            <p class="text-[10px] font-bold text-slate-400">
                Authorized Personnel Only
            </p>
        </div>
    </div>

</body>
</html>