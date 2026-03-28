<?php
// public/api/serve_document.php
require_once __DIR__ . '/../../src/includes/init.php';

// Security: Make sure the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized access.');
}

if (!isset($_GET['loan_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit('Missing loan identifier.');
}

$loanId = intval($_GET['loan_id']);

// Fetch the document path and type from the database
$stmt = $pdo->prepare("SELECT file_path, mime_type FROM Loan_Documents WHERE loan_id = ? ORDER BY document_id DESC LIMIT 1");
$stmt->execute([$loanId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc || empty($doc['file_path'])) {
    header("HTTP/1.1 404 Not Found");
    exit('Document not found. It may have been removed or is no longer available.');
}

// Construct the absolute path
$baseStoragePath = __DIR__ . '/../../storage/kptn_receipts/';
$targetPath = $baseStoragePath . ltrim($doc['file_path'], '/'); 
$filePath = realpath($targetPath);

if (!$filePath || !file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit('Physical file not found on the server at path: ' . htmlspecialchars($doc['file_path']));
}

// ==========================================
// THE FIX: PREVENT IMAGE CORRUPTION
// ==========================================
// Turn off PHP error reporting so warnings don't get printed into the image
error_reporting(0); 

// If there is any accidental whitespace or HTML already output, delete it!
if (ob_get_level()) {
    ob_end_clean();
}

// Feed the file content to the browser with the correct headers
header('Content-Type: ' . $doc['mime_type']);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Cache-Control: private, max-age=86400'); // Cache locally for 1 day

// Output the file stream
readfile($filePath);
exit;