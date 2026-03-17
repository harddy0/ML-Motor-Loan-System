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
        :root {
            --paper: #f7f5f2;
            --red-1: rgba(206, 17, 38, 0.22);
            --red-2: rgba(185, 28, 28, 0.18);
            --red-3: rgba(220, 38, 38, 0.14);
        }
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
        .live-bg-wrap {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 18% 24%, rgba(206, 17, 38, 0.07), transparent 36%),
                radial-gradient(circle at 82% 78%, rgba(220, 38, 38, 0.06), transparent 30%),
                var(--paper);
            z-index: 0;
            overflow: hidden;
        }
        #liveBg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .page-content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-[#f7f5f2]">

<div class="fixed top-0 left-0 w-full h-[0.5in] bg-white border-b border-slate-300 z-20 flex items-center px-4">
    <img src="<?= ASSET_URL ?>img/ml%20(2).png" alt="ML Logo" class="h-8 w-auto">
</div>

<div class="live-bg-wrap" aria-hidden="true">
    <canvas id="liveBg"></canvas>
</div>

<div class="page-content flex h-full items-center justify-center">

    <div name="main-page" class="anim-right relative flex flex-col justify-center w-full max-w-[900px] px-5">
        <div class="w-full max-w-[820px] bg-white min-h-[340px] mx-auto m-5 p-16 border border-slate-300 rounded-lg shadow-[0_18px_24px_-14px_rgba(15,23,42,0.65)] text-center flex flex-col items-center justify-center">
            <h1 class="anim-2 serif text-slate-800 leading-[1.08] tracking-wide mb-5 uppercase" style="font-size:clamp(1.8rem,2.4vw,2.4rem)">
                ML Motorcycle Loan System
            </h1>
            <a href="<?= BASE_URL ?>/public/login/"
            class="anim-4 inline-flex items-center justify-center gap-3 bg-slate-900 hover:bg-[#ce1126] text-white text-[11px] tracking-[0.2em] uppercase px-6 py-3.5 rounded-sm transition-all duration-200 group">
                Login
                <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>
    
</div>

<script>
    (function () {
        const canvas = document.getElementById('liveBg');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const palette = [
            'rgba(182, 33, 50, 0.22)',
            'rgba(173, 30, 30, 0.18)',
            'rgba(180, 29, 29, 0.14)'
        ];

        const blobs = [];
        let width = 0;
        let height = 0;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            blobs.length = 0;
            const count = width < 768 ? 7 : 11;
            for (let i = 0; i < count; i += 1) {
                const radius = width < 768 ? 80 + Math.random() * 70 : 120 + Math.random() * 140;
                blobs.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    radius,
                    driftX: (Math.random() - 0.5) * 0.2,
                    driftY: (Math.random() - 0.5) * 0.16,
                    waveAmp: 14 + Math.random() * 24,
                    waveFreq: 0.0007 + Math.random() * 0.001,
                    phase: Math.random() * Math.PI * 2,
                    color: palette[i % palette.length]
                });
            }
        }

        function drawBlob(blob, time) {
            const px = blob.x + Math.sin(time * blob.waveFreq + blob.phase) * blob.waveAmp;
            const py = blob.y + Math.cos(time * blob.waveFreq * 0.92 + blob.phase) * blob.waveAmp;

            const gradient = ctx.createRadialGradient(px, py, blob.radius * 0.2, px, py, blob.radius);
            gradient.addColorStop(0, blob.color);
            gradient.addColorStop(1, 'rgba(187, 32, 32, 0)');

            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(px, py, blob.radius, 0, Math.PI * 2);
            ctx.fill();

            blob.x += blob.driftX;
            blob.y += blob.driftY;

            if (blob.x < -blob.radius) blob.x = width + blob.radius;
            if (blob.x > width + blob.radius) blob.x = -blob.radius;
            if (blob.y < -blob.radius) blob.y = height + blob.radius;
            if (blob.y > height + blob.radius) blob.y = -blob.radius;
        }

        function animate(time) {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < blobs.length; i += 1) {
                drawBlob(blobs[i], time);
            }
            requestAnimationFrame(animate);
        }

        resize();
        requestAnimationFrame(animate);
        window.addEventListener('resize', resize, { passive: true });
    })();
</script>

</body>
</html>