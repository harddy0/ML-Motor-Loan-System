<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ML Motorcycle Loan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Global Font Application */
        *{font-family: 'League Spartan', sans-serif;}
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #e11d48; border-radius: 5px; }
        /* Smooth fade-in for content */
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        #import-list li {
        position: relative;
        transition: all 0.2s ease-in-out;
        }

        /* 2. Hover state: Border, Shadow, and Lift */
        #import-list li:hover {
            border-color: #e11d48 !important; /* Slate-800 */
            /* Visible shadow (Tailwind shadow-md equivalent) */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            z-index: 10; /* Ensures shadow appears OVER the item below it */
            transform: translateY(-1px); /* Slight lift to make shadow more visible */
        }

        /* 3. Number circle hover state */
        #import-list li:hover .bg-slate-200,
        #import-list li:hover .group-hover\:bg-\[\#ff3b30\] {
            background-color: #e11d48 !important;
            color: #ffffff !important;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-gray-100 font-sans overflow-hidden flex flex-col">

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>

    <div class="flex flex-1 h-0 overflow-hidden">
        
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto p-8 bg-[#fdfdfd]">
            <div class="animate-fadeIn max-w-7xl mx-auto">
                <?php echo $content; ?>
            </div>
        </main>

    </div>
</body>
</html>