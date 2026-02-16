<?php 
// 1 level up to reach src
require_once __DIR__ . '/../src/includes/init.php'; 

// 1 level up to reach src
include __DIR__ . '/../src/includes/header.php'; 
?>

<div class="container">
    <h1>Welcome to the App</h1>
    <p>Simulated Motor Loan System Environment</p>
    
    <a href="<?= BASE_URL ?>/login" class="btn">Login</a>
</div>

<?php 
// 1 level up to reach src
include __DIR__ . '/../src/includes/footer.php'; 
?>