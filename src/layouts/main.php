<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ML Motorcycle Loan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Global Font Application */
       * {
        font-family: 'Roboto', sans-serif;
        font-size: 16px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
        }
        ::-webkit-scrollbar { width: 10px;}
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #e11d48; border-radius: 10px; }
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

        #modal-ledger-rows td, 
        #modal-ledger-rows td span,
        #deductionTableBody td,
        #deductionTableBody td span {
            font-size: 14px !important;
        }

       /* 1. Hide the default native icon but keep the trigger area */
        .custom-date-input::-webkit-calendar-picker-indicator {
            position: absolute;
            top: -10px; /* Adjust to cover the height of the container */
            left: -40px; /* Adjust to cover the width of the container */
            width: 150%;
            height: 150%;
            cursor: pointer;
            background: transparent;
            color: transparent;
            z-index: 20;
        }

        /* 2. Ensure the parent label handles the relative positioning */
        label.relative {
            position: relative;
            overflow: hidden;
        }

        /* 3. Ensure the text stays below the invisible click-layer */
        .z-10 {
            z-index: 10;
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