<?php
/**
 * Secure Image Proxy
 * Serves images from data/upload while preventing direct access and metadata leakage.
 */

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('DS', DIRECTORY_SEPARATOR);

// 1. Basic Validation
$poiId = (int)($_GET['poi'] ?? 0);
$filename = basename($_GET['file'] ?? '');

if ($poiId === 0 || empty($filename)) {
    header("HTTP/1.0 400 Bad Request");
    exit("Invalid request parameters.");
}

// 2. Path Resolution
$uploadPath = ROOT . 'data' . DS . 'upload' . DS . 'poi' . DS . $poiId . DS . $filename;

// 3. Security Check: Ensure file exists and is within the expected directory
if (!file_exists($uploadPath) || !is_file($uploadPath)) {
    header("HTTP/1.0 404 Not Found");
    exit("Image not found.");
}

// 4. MIME Type Verification (Binary Check)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($uploadPath);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mimeType, $allowedMimes)) {
    header("HTTP/1.0 403 Forbidden");
    exit("File type not allowed.");
}

// 5. Serving the file with security/cache headers
header('Content-Type: ' . $mimeType);
header('Cache-Control: public, max-age=86400'); // 24h cache
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($uploadPath));

// 6. Output file content
readfile($uploadPath);
exit;
