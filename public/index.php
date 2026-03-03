<?php
$noLayout = true;
$pageTitle = "Welcome | ML Motorcycle Loan";
require_once __DIR__ . '/../src/includes/init.php';

if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Mono', monospace; }
        .serif { font-family: 'DM Serif Display', serif; }
        .bg-letter {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(16rem, 26vw, 34rem);
            line-height: 1;
            color: rgba(255,255,255,0.055);
            letter-spacing: -0.06em;
            pointer-events: none;
            user-select: none;
        }
        @keyframes slideLeft  { from { opacity:0; transform:translateX(-20px); } to { opacity:1; transform:none; } }
        @keyframes slideRight { from { opacity:0; transform:translateX( 20px); } to { opacity:1; transform:none; } }
        @keyframes fadeUp     { from { opacity:0; transform:translateY(10px);  } to { opacity:1; transform:none; } }
        .anim-left  { animation: slideLeft  0.7s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-right { animation: slideRight 0.7s cubic-bezier(0.22,1,0.36,1) both 0.1s; }
        .anim-1 { animation: fadeUp 0.5s ease both 0.25s; }
        .anim-2 { animation: fadeUp 0.5s ease both 0.35s; }
        .anim-3 { animation: fadeUp 0.5s ease both 0.43s; }
        .anim-4 { animation: fadeUp 0.5s ease both 0.52s; }
    </style>
</head>
<body class="h-screen overflow-hidden bg-[#f7f5f2]">

<div class="flex h-full">

    <!-- ── Left: Brand panel ── -->
    <div class="anim-left relative flex flex-col justify-between w-1/2 bg-[#ce1126] p-12 overflow-hidden">

        <!-- Ghost letter watermark -->
        <span class="bg-letter absolute bottom-0 left-0 -translate-x-1 translate-y-2 select-none">M</span>

        <!-- Right-edge divider -->
        <div class="absolute right-0 top-[10%] bottom-[10%] w-px bg-white/10"></div>

        <!-- Logo -->
        <div class="relative z-10 flex items-center gap-3">
            <div class="w-9 h-9 bg-white rounded-md flex items-center justify-center shrink-0">
                <img src="<?= ASSET_URL ?>img/ml-logo-1.png" alt="ML" class="w-6 h-auto">
            </div>
            <div class="leading-tight">
                <p class="text-white text-[11px] tracking-[0.18em] uppercase font-medium">M Lhuillier</p>
                <p class="text-white/50 text-[10px] tracking-[0.2em] uppercase">Financial Services</p>
            </div>
        </div>

        <!-- Headline -->
        <div class="relative z-10">
            <h1 class="serif text-white leading-[1.05] tracking-tight" style="font-size:clamp(2.6rem,4.2vw,4.8rem)">
                Motor<br>Loan<br><em class="text-white/50">System</em>
            </h1>
            <div class="w-10 h-px bg-white/20 my-5"></div>
            <p class="text-white/45 text-[11px] tracking-[0.15em] uppercase leading-relaxed">
                Loans · Amortization<br>Ledger · Payroll · AR
            </p>
        </div>

        <!-- Bottom stats -->
        <div class="relative z-10 flex items-center gap-6">
            <div>
                <p class="serif text-white text-2xl leading-none">24×</p>
                <p class="text-white/35 text-[9px] tracking-[0.2em] uppercase mt-1">Semi-monthly</p>
            </div>
            <div class="w-px h-9 bg-white/15"></div>
            <div>
                <p class="serif text-white text-2xl leading-none">PN</p>
                <p class="text-white/35 text-[9px] tracking-[0.2em] uppercase mt-1">Auto-generated</p>
            </div>
            <div class="w-px h-9 bg-white/15"></div>
            <div>
                <p class="serif text-white text-2xl leading-none">AR</p>
                <p class="text-white/35 text-[9px] tracking-[0.2em] uppercase mt-1">Running reports</p>
            </div>
        </div>

    </div>

    <!-- ── Right: Entry panel ── -->
    <div class="anim-right relative flex flex-col justify-center w-1/2 px-14 bg-[#f7f5f2]">

        <div class="max-w-[320px]">

            <div class="anim-1 flex items-center gap-3 mb-10">
                <div class="w-6 h-px bg-[#ce1126]"></div>
                <p class="text-[10px] text-slate-400 tracking-[0.3em] uppercase">Authorized Access</p>
            </div>

            <h2 class="anim-2 serif text-slate-800 leading-[1.08] tracking-tight mb-3" style="font-size:clamp(1.8rem,2.4vw,2.4rem)">
                Sign in to<br>your <em class="text-[#ce1126]">portal.</em>
            </h2>

            <p class="anim-3 text-[11px] text-slate-400 tracking-[0.06em] leading-relaxed mb-10">
                Loans · Amortization · Ledger<br>Payroll · Receivables
            </p>

            <a href="<?= BASE_URL ?>/public/login/"
               class="anim-4 inline-flex items-center gap-3 bg-slate-900 hover:bg-[#ce1126] text-white text-[11px] tracking-[0.2em] uppercase px-6 py-3.5 rounded-sm transition-all duration-200 group">
                Login
                <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>

        </div>

        <p class="absolute bottom-8 right-10 text-[9px] text-slate-300 tracking-[0.2em] uppercase [writing-mode:vertical-rl]">
            &copy; <?= date('Y') ?> MLFS
        </p>

    </div>

</div>

</body>
</html>