<?php
session_start();
header('Content-Type: application/json');

try {
    $files = $_SESSION['kanban_files'] ?? [];

    if (empty($files)) {
        throw new Exception('印刷するかんばんがありません');
    }

    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
