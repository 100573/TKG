<?php
require_once __DIR__ . '/../api/function.php';
session_start();
$tagData = $_SESSION['tag_data'] ?? [];
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>タグプレビュー</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 16px
        }

        .tag {
            display: inline-block;
            border: 1px solid #333;
            padding: 8px;
            margin: 8px;
            width: 420px;
            vertical-align: top
        }

        .tag-title {
            font-weight: bold;
            margin-bottom: 6px
        }

        .tag-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0
        }

        .tag-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px
        }

        .tag-table th,
        .tag-table td {
            border: 1px solid #999;
            padding: 4px;
            font-size: 12px
        }

        .tag-break {
            height: 1px
        }

        .header {
            margin-bottom: 12px
        }
    </style>
</head>

<body>
    <div class="header">
        <a href="generate.php">生成画面に戻る</a>
    </div>
    <h2>タグプレビュー</h2>
    <?php
    if (empty($tagData)) {
        echo '<p>タグデータがありません。まずは生成を実行してください。</p>';
    } else {
        foreach ($tagData as $entry) {
            $parts = $entry['parts'] ?? [];
            $line = htmlspecialchars($entry['line'] ?? '');
            $type = $entry['type'] ?? '';
            $date = $entry['date'] ?? '';

            foreach ($parts as $part) {
                // generateTagHtml は HTML を返す
                echo generateTagHtml($part, $type, $line, $date);
            }
        }
    }
    ?>
</body>

</html>