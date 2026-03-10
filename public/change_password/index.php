<?php
$noLayout = true;
$pageTitle = "Security Update | ML Motorcycle Loan";
require_once __DIR__ . '/../../src/includes/init.php';

// If they don't need to change password, send them to dashboard
if (empty($_SESSION['must_change_password'])) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

// Show the acknowledgement modal only ONCE per login session.
// Once the user clicks Acknowledge, we set this flag so refreshes
// and failed validation attempts never re-show it.
$showModal = empty($_SESSION['change_pw_modal_acknowledged']);
if ($showModal) {
    $_SESSION['change_pw_modal_acknowledged'] = true;
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
    <style>
        body { font-family: 'League Spartan', sans-serif; }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.92) translateY(12px); }
            to   { opacity: 1; transform: scale(1)    translateY(0); }
        }
        .modal-enter {
            animation: fadeInScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes slideOut {
            from { opacity: 1; transform: scale(1)    translateY(0); }
            to   { opacity: 0; transform: scale(0.95) translateY(-8px); }
        }
        .modal-exit {
            animation: slideOut 0.25s ease-in forwards;
        }
    </style>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">

    <?php if ($showModal): ?>
    <!-- Acknowledgement modal — shown ONCE per login session only -->
    <div id="mandatoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
        <div id="modalCard" class="modal-enter relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">

            <!-- Red accent bar at top -->
            <div class="h-1.5 w-full bg-[#dc2626]"></div>

            <div class="p-8 text-center">

                <!-- Icon -->
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-50 border-2 border-red-100 mb-5">
                    <svg class="h-7 w-7 text-[#dc2626]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-slate-800 font-black uppercase tracking-[0.15em] text-base mb-2">
                    Default password detected
                </h3>

                <!-- Divider -->
                <div class="w-8 h-0.5 bg-[#dc2626] mx-auto mb-4 rounded-full"></div>

                <!-- Body -->
                <p class="text-sm text-slate-400 leading-relaxed mb-8">
                    You must set a new password before you can continue.
                </p>

                <!-- Button -->
                <button id="acknowledgeBtn"
                    class="w-full bg-[#dc2626] hover:bg-[#b91c1c] active:bg-[#991b1b] text-white py-3.5 rounded-lg text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                    I Understand — Set New Password
                </button>

                <!-- Cancel / logout -->
                <a href="<?= BASE_URL ?>/public/actions/logout.php"
                   class="block mt-4 text-[10px] font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">
                    Cancel
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Password change form -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-8 md:p-10 relative overflow-hidden">

        <!-- Red accent bar -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-[#dc2626]"></div>

        <div class="text-center mb-8 mt-2">
            <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="M Lhuillier" class="h-12 w-auto mx-auto mb-4">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Mandatory Update</h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Set your new password to continue</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-[#dc2626] p-4 flex items-start gap-3 rounded-r-lg">
                <svg class="w-5 h-5 text-[#dc2626] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs font-bold text-red-700"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-5">

            <div class="space-y-2">
                <label for="new_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">New Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#dc2626] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-lg text-sm font-bold text-slate-800 outline-none focus:border-[#dc2626] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
            </div>

            <div class="space-y-2">
                <label for="confirm_password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirm New Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-300 group-focus-within:text-[#dc2626] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="••••••••"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-800 outline-none focus:border-[#dc2626] focus:bg-white transition-all placeholder:text-slate-300">
                </div>
                <!-- Live match indicator -->
                <p id="matchHint" class="text-[10px] font-bold ml-1 hidden"></p>
            </div>

            <div class="pt-2">
                <button type="submit" id="submitBtn"
                    class="w-full bg-[#dc2626] hover:bg-[#b91c1c] text-white py-4 rounded-lg text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                    Update Password
                </button>
            </div>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-5">
            <a href="<?= BASE_URL ?>/public/actions/logout.php"
               class="text-[10px] font-black text-slate-400 hover:text-[#dc2626] uppercase tracking-widest transition-colors inline-flex items-center gap-2 group">
                <svg class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Cancel 
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ── MODAL (only present in DOM if $showModal is true) ──────────────
        const modal        = document.getElementById('mandatoryModal');
        const modalCard    = document.getElementById('modalCard');
        const acknowledgeBtn = document.getElementById('acknowledgeBtn');

        if (modal && acknowledgeBtn) {
            acknowledgeBtn.addEventListener('click', () => {
                modalCard.classList.remove('modal-enter');
                modalCard.classList.add('modal-exit');
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.getElementById('new_password').focus();
                }, 250);
            });
        }

        // ── LIVE PASSWORD MATCH HINT ────────────────────────────────────────
        const newPw     = document.getElementById('new_password');
        const confirmPw = document.getElementById('confirm_password');
        const matchHint = document.getElementById('matchHint');
        const submitBtn = document.getElementById('submitBtn');

        function checkMatch() {
            if (!confirmPw.value) {
                matchHint.classList.add('hidden');
                return;
            }
            matchHint.classList.remove('hidden');
            if (newPw.value === confirmPw.value) {
                matchHint.textContent  = '✓ Passwords match';
                matchHint.className    = 'text-[10px] font-bold ml-1 text-green-600';
            } else {
                matchHint.textContent  = '✗ Passwords do not match';
                matchHint.className    = 'text-[10px] font-bold ml-1 text-red-500';
            }
        }

        newPw.addEventListener('input', checkMatch);
        confirmPw.addEventListener('input', checkMatch);
    });
    </script>

</body>
</html>