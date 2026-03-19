<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'function.php';

try {
    // パラメータ取得
    $model = $_POST['model'] ?? '';
    $targetDate = $_POST['targetDate'] ?? '';
    $lines = $_POST['lines'] ?? [];
    $downloadPath = $_POST['downloadPath'] ?? '';

    if (empty($model) || empty($targetDate) || empty($lines)) {
        throw new Exception('必須パラメータが不足しています');
    }

    // DB接続
    $db = new Database();
    $oracle = $db->getOracleConnection();
    $mysql = $db->getMySQLConnection();

    // 生成ファイル管理テーブルを作成（なければ）
    $mysql->exec("CREATE TABLE IF NOT EXISTS generated_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        path VARCHAR(1024),
        download_url VARCHAR(1024),
        block VARCHAR(100),
        is_matome TINYINT(1),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 日付フォルダ作成
    $dateFolder = str_replace('-', '', $targetDate);

    // デフォルト出力先（サーバ上の相対パス）
    if (trim($downloadPath) === '') {
        $baseOutput = __DIR__ . '/../output';
    } else {
        $baseOutput = $downloadPath;
    }

    // 正しいディレクトリ区切りを使う
    $baseOutput = rtrim($baseOutput, "\\/") . DIRECTORY_SEPARATOR;
    $outputPath = $baseOutput . $dateFolder . DIRECTORY_SEPARATOR;
    if (!file_exists($outputPath)) {
        if (!@mkdir($outputPath, 0777, true)) {
            throw new Exception('出力フォルダを作成できません: ' . $outputPath);
        }
    }

    $generatedFiles = [];

    // ライン毎に処理
    foreach ($lines as $line) {
        // オーダー情報取得
        $orders = getOrders($oracle, $targetDate, $line);

        if (empty($orders)) {
            continue;
        }

        // 工程ブロック取得
        $blocks = getBlocks($mysql, $model);

        foreach ($blocks as $block) {
            $blockName = $block['block'];
            $isMatome = $block['matome'];

            // ルール取得（ブロックごと）
            $rules = getRules($mysql, $blockName, $isMatome);

            // 部品情報集計（ルールに従ってフィルタ／まとめ）
            $parts = aggregateParts($oracle, $mysql, $orders, $blockName, $isMatome, $rules);

            if (empty($parts)) {
                continue;
            }

            // 台車・箱No採番
            $partsWithNumbers = assignDaishaAndBoxNo($parts, $rules, $isMatome);

            // かんばん生成
            $type = $isMatome ? 'まとめ部品' : '投入順部品';
            $fileName = "{$dateFolder}_{$type}かんばん_{$blockName}.xlsx";
            $filePath = $outputPath . $fileName;

            generateKanban($partsWithNumbers, $filePath, $line, $blockName, $type, $targetDate);

            $downloadUrl = './api/download.php?file=' . urlencode($dateFolder . '/' . $fileName);

            // DBに登録
            try {
                $stmtIns = $mysql->prepare('INSERT INTO generated_files (name, path, download_url, block, is_matome) VALUES (:name, :path, :download_url, :block, :is_matome)');
                $stmtIns->execute([
                    ':name' => $fileName,
                    ':path' => $filePath,
                    ':download_url' => $downloadUrl,
                    ':block' => $blockName,
                    ':is_matome' => $isMatome ? 1 : 0
                ]);
                $fileId = $mysql->lastInsertId();
            } catch (Exception $e) {
                // 登録失敗しても処理は継続
                $fileId = null;
            }

            $generatedFiles[] = [
                'id' => $fileId,
                'name' => $fileName,
                'path' => $filePath,
                'download_url' => $downloadUrl,
                'type' => 'kanban',
                'block' => $blockName,
                'isMatome' => $isMatome
            ];

            // タグデータ保存（セッション）
            saveTagData($partsWithNumbers, $line, $blockName, $type, $targetDate);
        }
    }

    oci_close($oracle);

    echo json_encode([
        'success' => true,
        'files' => $generatedFiles,
        'message' => count($generatedFiles) . '件のファイルを生成しました'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * オーダー情報取得
 */
function getOrders($oracle, $targetDate, $line)
{
    $sql = "
        SELECT 
            F_NO,
            PROD_SEQ_NO,
            LINE_NM,
            LINE_IN_PLAN_DT,
            SUBSTR(F_NO, 1, 4) as MCODE
        FROM T_F_ORDER
        WHERE LINE_IN_PLAN_DT = TO_DATE(:target_date, 'YYYY-MM-DD')
        AND LINE_NM = :line
        ORDER BY PROD_SEQ_NO
    ";

    $db = new Database();
    return $db->oracleQuery($oracle, $sql, [
        ':target_date' => $targetDate,
        ':line' => $line
    ]);
}

/**
 * 工程ブロック取得
 */
function getBlocks($mysql, $model)
{
    $stmt = $mysql->prepare("
        SELECT block, matome 
        FROM model 
        WHERE model = :model
        ORDER BY id
    ");
    $stmt->execute(['model' => $model]);
    return $stmt->fetchAll();
}

/**
 * 部品情報集計
 * - $rules に従って該当部品のみ抽出
 * - まとめ部品の場合は同一部品を合算して返す
 */
function aggregateParts($oracle, $mysql, $orders, $blockName, $isMatome, $rules)
{
    $parts = [];
    $db = new Database();
    $orderIndex = 0;

    if ($isMatome) {
        // まとめ部品は同一部品ごとに数量を合算する
        $map = [];

        foreach ($orders as $order) {
            $orderIndex++;
            $mcode = $order['MCODE'];
            $fno = $order['F_NO'];

            $sql = "
                SELECT 
                    FNO,
                    SERIES,
                    CTG,
                    PN,
                    PN_ORG,
                    PN_NAME,
                    PN_QTY
                FROM AMD_PARTS_V
                WHERE FNO = :mcode
            ";

            $partList = $db->oracleQuery($oracle, $sql, [':mcode' => $mcode]);
            if (empty($partList)) {
                $partList = getPartsFromAIMonD($mcode);
            }

            foreach ($partList as $part) {
                // ルールに合致しない部品はスキップ
                if (findRule($rules, $part['PN']) === null) {
                    continue;
                }

                $pn = $part['PN'];
                $qty = isset($part['PN_QTY']) ? intval($part['PN_QTY']) : 0;
                $location = getLocation($mysql, $pn);

                if (!isset($map[$pn])) {
                    $map[$pn] = [
                        'f_no' => $fno,
                        'mcode' => $mcode,
                        'series' => $part['SERIES'],
                        'seq' => 0,
                        'order_index' => 0,
                        'part_number' => $pn,
                        'part_name' => $part['PN_NAME'],
                        'location' => $location,
                        'qty' => $qty,
                        'ctg' => $part['CTG']
                    ];
                } else {
                    $map[$pn]['qty'] += $qty;
                }
            }
        }

        $parts = array_values($map);
    } else {
        // 投入順部品はオーダー順に展開（ルールに合致するもののみ）
        foreach ($orders as $order) {
            $orderIndex++;
            $mcode = $order['MCODE'];
            $fno = $order['F_NO'];
            $seq = isset($order['PROD_SEQ_NO']) ? intval($order['PROD_SEQ_NO']) : 0;

            $sql = "
                SELECT 
                    FNO,
                    SERIES,
                    CTG,
                    PN,
                    PN_ORG,
                    PN_NAME,
                    PN_QTY
                FROM AMD_PARTS_V
                WHERE FNO = :mcode
            ";

            $partList = $db->oracleQuery($oracle, $sql, [':mcode' => $mcode]);
            if (empty($partList)) {
                $partList = getPartsFromAIMonD($mcode);
            }

            foreach ($partList as $part) {
                // ルールに合致しない部品はスキップ
                if (findRule($rules, $part['PN']) === null) {
                    continue;
                }

                $location = getLocation($mysql, $part['PN']);

                $parts[] = [
                    'f_no' => $fno,
                    'mcode' => $mcode,
                    'series' => $part['SERIES'],
                    'seq' => $seq,
                    'order_index' => $orderIndex,
                    'part_number' => $part['PN'],
                    'part_name' => $part['PN_NAME'],
                    'location' => $location,
                    'qty' => $part['PN_QTY'],
                    'ctg' => $part['CTG']
                ];
            }
        }
    }

    return $parts;
}

/**
 * AIMonDから部品情報取得
 */
function getPartsFromAIMonD($mcode)
{
    $db = new Database();
    $aimond = $db->getAImondConnection();

    // TODO: AIMONDのテーブル構造に応じて実装
    // 仕様が後で提供されるとのことなので暫定実装
    return [];
}

/**
 * ロケーション取得
 */
function getLocation($mysql, $partNumber)
{
    $stmt = $mysql->prepare("
        SELECT location 
        FROM location 
        WHERE part_number = :pn
        LIMIT 1
    ");
    $stmt->execute(['pn' => $partNumber]);
    $result = $stmt->fetch();
    return $result ? $result['location'] : '';
}

/**
 * ルール取得
 */
function getRules($mysql, $blockName, $isMatome)
{
    $table = $isMatome ? 'parts_matome_rule' : 'parts_rule';
    $stmt = $mysql->prepare("
        SELECT * FROM {$table}
        WHERE koutei = :block
    ");
    $stmt->execute(['block' => $blockName]);
    return $stmt->fetchAll();
}

/**
 * タグデータ保存
 */
function saveTagData($parts, $line, $block, $type, $date)
{
    session_start();
    if (!isset($_SESSION['tag_data'])) {
        $_SESSION['tag_data'] = [];
    }

    $_SESSION['tag_data'][] = [
        'parts' => $parts,
        'line' => $line,
        'block' => $block,
        'type' => $type,
        'date' => $date
    ];
}
