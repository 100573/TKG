<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>かんばん・タグ生成</title>
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

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }

        input[type="date"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }

        .checkbox-item input {
            margin-right: 0.5rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        button {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: white;
        }

        .results-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .results-container.show {
            display: block;
        }

        .file-list {
            list-style: none;
        }

        .file-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .path-selector {
            display: flex;
            gap: 0.5rem;
        }

        .path-selector input {
            flex: 1;
        }

        .path-selector button {
            flex: 0 0 auto;
            padding: 0.75rem 1.5rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📊 かんばん・タグ生成</h1>
        <div class="breadcrumb">
            <a href="Home.php">ホーム</a> / かんばん・タグ生成
        </div>
    </div>

    <div class="form-container">
        <form id="generateForm">
            <div class="form-group">
                <label for="model">モデル選択</label>
                <select id="model" name="model" required>
                    <option value="">-- 選択してください --</option>
                    <option value="sword">Sword</option>
                    <option value="spark">Spark</option>
                    <option value="terra">Terra</option>
                </select>
            </div>

            <div class="form-group">
                <label for="targetDate">生産日</label>
                <input type="date" id="targetDate" name="targetDate" required>
            </div>

            <div class="form-group">
                <label>ライン選択</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="line_vk11" name="lines[]" value="VK11">
                        <label for="line_vk11">VK11</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="line_vk2" name="lines[]" value="VK2">
                        <label for="line_vk2">VK2</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="line_va11" name="lines[]" value="VA11">
                        <label for="line_va11">VA11</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="line_vka1" name="lines[]" value="VKA1">
                        <label for="line_vka1">VKA1</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="downloadPath">ダウンロード先パス</label>
                <div class="path-selector">
                    <input type="text" id="downloadPath" name="downloadPath" placeholder="C:\Downloads\" value="">
                    <button type="button" onclick="selectPath()">参照</button>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary">🔄 集計実行</button>
            </div>
        </form>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>処理中...</p>
    </div>

    <div class="results-container" id="results">
        <h2>生成されたファイル</h2>
        <ul class="file-list" id="fileList"></ul>

        <div class="button-group">
            <button type="button" class="btn-success" onclick="printKanban()">🖨️ かんばん印刷</button>
            <button type="button" class="btn-info" onclick="printTags()">🏷️ タグ印刷</button>
        </div>
    </div>

    <script>
        // デフォルトで明日の日付を設定
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dateStr = tomorrow.toISOString().split('T')[0];
            document.getElementById('targetDate').value = dateStr;

            // デフォルトパス設定
            document.getElementById('downloadPath').value = 'C:\\Users\\100573\\Downloads\\';
        });

        // フォーム送信
        document.getElementById('generateForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const lines = [];
            document.querySelectorAll('input[name="lines[]"]:checked').forEach(cb => {
                lines.push(cb.value);
            });

            if (lines.length === 0) {
                alert('ラインを1つ以上選択してください');
                return;
            }

            document.getElementById('loading').classList.add('show');
            document.getElementById('results').classList.remove('show');

            try {
                const response = await fetch('../api/aggregate.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    displayResults(result.files);
                } else {
                    alert('エラー: ' + result.message);
                }
            } catch (error) {
                alert('通信エラー: ' + error.message);
            } finally {
                document.getElementById('loading').classList.remove('show');
            }
        });

        function displayResults(files) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';

            files.forEach(file => {
                const li = document.createElement('li');
                li.className = 'file-item';
                // 表示はサーバパスではなくダウンロードURLを使う
                li.innerHTML = `
                    <span>📄 ${file.name}</span>
                    <a href="${file.download_url}" target="_blank">ダウンロード</a>
                `;
                fileList.appendChild(li);
            });

            // 自動ダウンロード: iframeを順に作ってダウンロードを開始する
            files.forEach((file, idx) => {
                setTimeout(() => {
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = file.download_url;
                    document.body.appendChild(iframe);
                    // 1分後にiframeを削除してクリーンアップ
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 60000);
                }, idx * 500);
            });

            document.getElementById('results').classList.add('show');
        }

        function printKanban() {
            window.open('../api/kanban.html', '_blank');
        }

        function printTags() {
            window.open('../api/tag.html', '_blank');
        }

        function selectPath() {
            // ファイルパス選択ダイアログ（ブラウザ制限により実装困難）
            alert('パスを直接入力してください');
        }
    </script>
</body>

</html>