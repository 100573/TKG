<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>部品ピッキングシステム - ホーム</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 90%;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 2rem;
        }
        .menu {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .menu-item {
            display: block;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .menu-item.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .footer {
            margin-top: 2rem;
            text-align: center;
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏭 部品ピッキングシステム</h1>
        <p class="subtitle">かんばん・タグ生成システム</p>
        
        <div class="menu">
            <a href="generate.php" class="menu-item">
                📊 かんばん・タグ生成
            </a>
            <a href="Rule.php" class="menu-item secondary">
                ⚙️ ルール設定
            </a>
        </div>
        
        <div class="footer">
            <p>© 2026 VAIO Corporation</p>
            <p>Version 1.0.0</p>
        </div>
    </div>
</body>
</html>