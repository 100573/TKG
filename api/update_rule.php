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

    // 更新フィールド構築
    $fields = [];
    $params = ['id' => $id];

    foreach ($input as $key => $value) {
        if ($key !== 'table' && $key !== 'id') {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
    }

    if (empty($fields)) {
        throw new Exception('更新するフィールドがありません');
    }

    $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $mysql->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => '更新しました'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
