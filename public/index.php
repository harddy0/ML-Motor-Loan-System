<?php
// Disable the default dashboard layout for this page
$noLayout = true;
$pageTitle = "Welcome | ML Motorcycle Loan";
require_once __DIR__ . '/../src/includes/init.php';

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}
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
        .bg-diamond-pattern {
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 0);
            background-size: 24px 24px;
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-[#ff3b30] overflow-hidden relative">

    <div class="absolute inset-0 bg-diamond-pattern opacity-30"></div>
    <div class="absolute -right-64 -bottom-64 w-[800px] h-[800px] opacity-10 transform rotate-45 border-[40px] border-white"></div>
    <div class="absolute -left-32 -top-32 w-96 h-96 opacity-10 transform rotate-12 bg-white rounded-full blur-3xl"></div>

    <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4 animate-fade-in">
        
        <div class="mb-12">
             <div class="inline-flex items-center justify-center p-4 bg-white rounded-2xl shadow-2xl mb-6 transform hover:scale-105 transition-transform duration-300">
                <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="M Lhuillier" class="h-16 w-auto">
             </div>
             <div class="text-white/80 font-bold tracking-[0.3em] text-sm uppercase">Financial Services</div>
        </div>

        <h1 class="text-6xl md:text-8xl font-black text-white leading-tight tracking-tight mb-8 drop-shadow-lg">
            DRIVE YOUR <br>
            <span class="text-white/90">DREAMS</span>
        </h1>

        <p class="text-white/90 text-xl md:text-2xl font-medium max-w-2xl mb-12 leading-relaxed">
            Welcome to the official <strong>Motor Loan Management System</strong>. 
            Secure, fast, and reliable processing for all your financial needs.
        </p>

        <a href="<?= BASE_URL ?>/public/login/" 
           class="group relative inline-flex items-center justify-center px-12 py-5 text-lg font-black text-[#ff3b30] bg-white rounded-full overflow-hidden transition-all duration-300 hover:bg-slate-100 hover:shadow-[0_0_40px_rgba(255,255,255,0.5)] hover:-translate-y-1">
            <span class="absolute w-0 h-0 transition-all duration-500 ease-out bg-slate-100 rounded-full group-hover:w-80 group-hover:h-80 opacity-10"></span>
            <span class="relative flex items-center gap-3 uppercase tracking-widest">
                Login to Portal
                <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </span>
        </a>

        <div class="absolute bottom-8 text-white/50 text-xs font-bold uppercase tracking-widest">
            &copy; <?= date('Y') ?> M Lhuillier Financial Services, Inc.
        </div>

    </div>

</body>
</html>