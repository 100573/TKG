<?php
session_start();
header('Content-Type: application/json');
require_once 'function.php';

try {
    $tagData = $_SESSION['tag_data'] ?? [];

    if (empty($tagData)) {
        throw new Exception('印刷するタグがありません');
    }

    $htmlContent = '';

    foreach ($tagData as $data) {
        $parts = $data['parts'];
        $line = $data['line'];
        $date = $data['date'];
        $type = $data['type'];

        foreach ($parts as $part) {
            $htmlContent .= generateTagHtml($part, $type, $line, $date);
        }
    }

    // HTMLファイルに書き込み
    file_put_contents('tag_temp.html', $htmlContent);

    echo json_encode([
        'success' => true,
        'html' => $htmlContent,
        'count' => count($tagData)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
