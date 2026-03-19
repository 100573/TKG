<?php
require_once '../api/db.php';

// データ取得
$db = new Database();
$mysql = $db->getMySQLConnection();

// 投入順部品ルール取得
$stmt = $mysql->query("SELECT * FROM parts_rule ORDER BY id");
$partsRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// まとめ部品ルール取得
$stmt = $mysql->query("SELECT * FROM parts_matome_rule ORDER BY id");
$matomeRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 工程ブロック取得
$stmt = $mysql->query("SELECT * FROM model ORDER BY id");
$models = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ルール設定</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 1rem 2rem;
            background: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f5f7fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-delete {
            background: #f5576c;
            color: white;
        }

        .btn-add {
            background: #56ab2f;
            color: white;
            margin-bottom: 1rem;
        }

        .editable {
            cursor: text;
        }

        .editable:focus {
            outline: 2px solid #667eea;
            background: #fff9e6;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>⚙️ ルール設定</h1>
        <div class="breadcrumb">
            <a href="Home.php">ホーム</a> / ルール設定
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="showTab('parts')">投入順部品ルール</button>
        <button class="tab" onclick="showTab('matome')">まとめ部品ルール</button>
        <button class="tab" onclick="showTab('model')">工程ブロック</button>
    </div>

    <!-- 投入順部品ルール -->
    <div id="parts" class="tab-content active">
        <div class="table-container">
            <button class="btn btn-add" onclick="addRow('parts')">+ 新規追加</button>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>工程ブロック</th>
                        <th>種別</th>
                        <th>部品番号</th>
                        <th>シリーズ名</th>
                        <th>部品名称</th>
                        <th>1箱入数</th>
                        <th>箱内仕切数</th>
                        <th>台車積載箱数</th>
                        <th>所要数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="partsTableBody">
                    <?php foreach ($partsRules as $rule): ?>
                        <tr data-id="<?= $rule['id'] ?>">
                            <td><?= htmlspecialchars($rule['id']) ?></td>
                            <td contenteditable="true" class="editable" data-field="koutei"><?= htmlspecialchars($rule['koutei'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="type"><?= htmlspecialchars($rule['type'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="part_number"><?= htmlspecialchars($rule['part_number'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="series"><?= htmlspecialchars($rule['series'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="part_name"><?= htmlspecialchars($rule['part_name'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="box_qty"><?= htmlspecialchars($rule['box_qty'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="partition_qty"><?= htmlspecialchars($rule['partition_qty'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="daisha_qty"><?= htmlspecialchars($rule['daisha_qty'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="required_qty"><?= htmlspecialchars($rule['required_qty'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="saveRow('parts', <?= $rule['id'] ?>)">保存</button>
                                <button class="btn btn-delete" onclick="deleteRow('parts', <?= $rule['id'] ?>)">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- まとめ部品ルール -->
    <div id="matome" class="tab-content">
        <div class="table-container">
            <button class="btn btn-add" onclick="addRow('matome')">+ 新規追加</button>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>工程ブロック</th>
                        <th>部品番号</th>
                        <th>部品名称</th>
                        <th>最低在庫数</th>
                        <th>1箱入数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="matomeTableBody">
                    <?php foreach ($matomeRules as $rule): ?>
                        <tr data-id="<?= $rule['id'] ?>">
                            <td><?= htmlspecialchars($rule['id']) ?></td>
                            <td contenteditable="true" class="editable" data-field="koutei"><?= htmlspecialchars($rule['koutei'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="part_number"><?= htmlspecialchars($rule['part_number'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="part_name"><?= htmlspecialchars($rule['part_name'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="min_stock"><?= htmlspecialchars($rule['min_stock'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="box_qty"><?= htmlspecialchars($rule['box_qty'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="saveRow('matome', <?= $rule['id'] ?>)">保存</button>
                                <button class="btn btn-delete" onclick="deleteRow('matome', <?= $rule['id'] ?>)">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 工程ブロック -->
    <div id="model" class="tab-content">
        <div class="table-container">
            <button class="btn btn-add" onclick="addRow('model')">+ 新規追加</button>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>モデル</th>
                        <th>ブロック名</th>
                        <th>まとめフラグ</th>
                        <th>登録日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="modelTableBody">
                    <?php foreach ($models as $model): ?>
                        <tr data-id="<?= $model['id'] ?>">
                            <td><?= htmlspecialchars($model['id']) ?></td>
                            <td contenteditable="true" class="editable" data-field="model"><?= htmlspecialchars($model['model'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="block"><?= htmlspecialchars($model['block'] ?? '') ?></td>
                            <td contenteditable="true" class="editable" data-field="matome"><?= htmlspecialchars($model['matome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($model['reg_time'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-edit" onclick="saveRow('model', <?= $model['id'] ?>)">保存</button>
                                <button class="btn btn-delete" onclick="deleteRow('model', <?= $model['id'] ?>)">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // すべてのタブとコンテンツを非アクティブに
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // 選択されたタブをアクティブに
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }

        async function saveRow(table, id) {
            const row = document.querySelector(`#${table}TableBody tr[data-id="${id}"]`);
            const cells = row.querySelectorAll('.editable');
            const data = {
                id,
                table
            };

            cells.forEach(cell => {
                const field = cell.getAttribute('data-field');
                data[field] = cell.textContent.trim();
            });

            try {
                const response = await fetch('../api/update_rule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    alert('保存しました');
                } else {
                    alert('エラー: ' + result.message);
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            }
        }

        async function deleteRow(table, id) {
            if (!confirm('本当に削除しますか？')) return;

            try {
                const response = await fetch('../api/delete_rule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        table,
                        id
                    })
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('エラー: ' + result.message);
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            }
        }

        function addRow(table) {
            alert('新規追加機能は未実装です');
        }
    </script>
</body>

</html>