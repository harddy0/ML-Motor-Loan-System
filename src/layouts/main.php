<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageTitle ?? 'Motor Loan System'; ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <?php echo $content; ?>
    </main>
 
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="/assets/main.js"></script>
</body>
</html>