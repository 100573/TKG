<?php
// Secure file download from output directory
$base = realpath(__DIR__ . '/../output');
if ($base === false) {
    http_response_code(500);
    echo 'Server misconfiguration: output dir not found';
    exit;
}

$file = $_GET['file'] ?? '';
if ($file === '') {
    http_response_code(400);
    echo 'file parameter required';
    exit;
}

// Normalize and prevent traversal
$requested = realpath($base . DIRECTORY_SEPARATOR . str_replace(['..', '\\', '/'], ['', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $file));
if ($requested === false || strpos($requested, $base) !== 0) {
    http_response_code(400);
    echo 'Invalid file path';
    exit;
}

if (!is_file($requested) || !is_readable($requested)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$filename = basename($requested);
$mime = mime_content_type($requested) ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($requested));
readfile($requested);
exit;
