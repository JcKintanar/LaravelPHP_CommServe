<?php
session_start();
require_once __DIR__ . '/userAccounts/config.php';

// Admin/Official guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'official'], true)) {
  http_response_code(403);
  die('Access denied');
}

$file = $_GET['file'] ?? '';
$file_path = __DIR__ . '/' . $file;

// Security: Ensure file is within uploads directory
$real_path = realpath($file_path);
$uploads_dir = realpath(__DIR__ . '/uploads/');

if (!$real_path || strpos($real_path, $uploads_dir) !== 0) {
  http_response_code(404);
  die('File not found');
}

if (!file_exists($real_path)) {
  http_response_code(404);
  die('File not found');
}

// Determine content type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $real_path);
finfo_close($finfo);

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($real_path));
header('Content-Disposition: inline; filename="' . basename($real_path) . '"');

readfile($real_path);
exit;
?>
