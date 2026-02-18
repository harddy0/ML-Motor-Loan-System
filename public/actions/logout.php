<?php
require_once __DIR__ . '/../../src/includes/init.php';

$auth->logout();
header('Location: ' . BASE_URL . '/public/login/');
exit;