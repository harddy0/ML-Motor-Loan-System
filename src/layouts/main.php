<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ML Motorcycle Loan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>img/ml-diamond.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Global Font Application Red Color #ce1126*/
       * {
        font-family: 'Roboto', sans-serif;
        font-size: 16px;
        
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
        }
        ::-webkit-scrollbar { width: 10px;}
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cac1c1; ; border-radius: 10px; }

        /* Thinner scrollbar specifically for elements using the `custom-scrollbar` class */
        .custom-scrollbar {
            scrollbar-width: auto; /* Firefox */
            scrollbar-color: #cac1c1 #f1f1f1; /* thumb track */
        }
        .custom-scrollbar::-webkit-scrollbar { height: 10px; width: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cac1c1; border-radius: 8px; }
        /* Smooth fade-in for content */
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }

        div[name="new-card"] .overflow-y-auto {
             scrollbar-width: auto !important; /* Firefox */
            scrollbar-color: #cac1c1 #f1f1f1 !important;
        }
        #import-list li {
        position: relative;
        transition: all 0.2s ease-in-out;
        }

        #import-list li p.text-slate-400 {
            font-size: 12px !important; /* Adjust this value to your preferred size */
            color: #94a3b8 !important;   /* Keeps the slate-400 color */
            line-height: 1.2 !important;
            text-transform: none !important; /* This removes the uppercase */
        }

        /* 2. Hover state: Border, Shadow, and Lift */
        #import-list li:hover {
            border-color: #ce1126 !important; /* Slate-800 */
            /* Visible shadow (Tailwind shadow-md equivalent) */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            z-index: 10; /* Ensures shadow appears OVER the item below it */
            transform: translateY(-1px); /* Slight lift to make shadow more visible */
        }

        /* 3. Number circle hover state */
        #import-list li:hover .bg-slate-200,
        #import-list li:hover .group-hover\:bg-\[\#ff3b30\] {
            background-color: #ce1126 !important;
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

       /* 1. Force the entire table body to stop auto-uppercasing */
        #deductionTableBody,
        #deductionTableBody td,
        #deductionTableBody td span {
            text-transform: none !important;
        }

        /* 2. Target the Region and Date columns specifically to be sure */
        #deductionTableBody td:nth-child(5), /* Region column */
        #deductionTableBody td:nth-child(6)  /* Date/Status column */ {
            text-transform: lowercase !important; /* Forces lowercase first... */
            text-transform: capitalize !important; /* ...then capitalizes first letters */
        }

        /* 3. Ensure the match status (Match/Voided) stays readable */
        #deductionTableBody td span[class*="text-"] {
            text-transform: capitalize !important;
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

<script>
// Global date formatting helpers available across the frontend
window.formatFullDate = function(input) {
    if (!input && input !== 0) return '';
    try {
        const d = (input instanceof Date) ? input : new Date(String(input));
        if (!isNaN(d)) {
            return new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).format(d);
        }
        // fallback: try replacing dashes with slashes for YYYY-MM-DD
        const d2 = new Date(String(input).replace(/-/g, '/'));
        if (!isNaN(d2)) {
            return new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).format(d2);
        }
    } catch (e) {
        // ignore
    }
    return String(input);
};

window.setFullDate = function(elementId, dateInput) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.textContent = window.formatFullDate(dateInput);
};
</script>