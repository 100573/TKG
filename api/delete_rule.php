<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $table = $input['table'] ?? '';
    $id = $input['id'] ?? 0;

    if (!$table || !$id) {
        throw new Exception('パラメータが不足しています');
    }

    // テーブル名バリデーション
    $allowedTables = ['parts_rule', 'parts_matome_rule', 'model'];
    if (!in_array($table, $allowedTables)) {
        throw new Exception('不正なテーブル名です');
    }

    $db = new Database();
    $mysql = $db->getMySQLConnection();

    $stmt = $mysql->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        'success' => true,
        'message' => '削除しました'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
