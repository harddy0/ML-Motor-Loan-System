<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ML Motorcycle Loan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ff3b30; border-radius: 10px; }
    </style>
</head>
<body class="h-full bg-gray-100 font-sans overflow-hidden flex flex-col">

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
        
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

        <main class="flex-1 overflow-y-auto p-8 bg-[#fdfdfd]">
            <div class="animate-fadeIn">
                <?php echo $content; ?>
            </div>
        </main>

    </div>
</body>
</html>