<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_staff();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();
$stmt = $pdo->prepare('SELECT original_name, stored_name, mime_type FROM request_files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$path = __DIR__ . '/../uploads/' . $file['stored_name'];
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: inline; filename="' . rawurlencode($file['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
