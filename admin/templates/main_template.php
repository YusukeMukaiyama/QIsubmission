<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <!-- <link rel="stylesheet" href="../public/admin.css" media="all"-->
    <link rel="stylesheet" href="admin.css" media="all"> 
    <title>管理者メニュー</title>
    <style>
        .center { 
            text-align: center; 
            margin: 0 auto; /* 親要素に対して中央に配置 */
            width: 100%; /* 要素の内容に合わせた幅にする */
        }
        table { 
            background-color: #bbb; 
            border-spacing: 1px; 
            margin: 0 auto; /* テーブル自体を中央に配置 */
        }
        th, td { 
            background-color: #fff; 
            padding: 5px; 
            text-align: left; 
        }
        th { 
            background-color: #ddd; 
        }
    </style>
</head>
<body>

<div class="center">

    <h1>QIシステム</h1>

    <table>
        <tr><th>メニュー</th></tr>
        <tr><td><a href='item1.php'>質問・回答編集</a></td></tr>
        <tr><td><a href='outcome_qa_order.php'>アウトカム質問順序設定</a></td></tr>
        <tr><td><a href='enquete.php'>質問前後のアンケート設定</a></td></tr>
        <tr><td><a href='usr.php'>ユーザ一覧</a></td></tr>
        <tr><td><a href='usr_reg.php'>ユーザ登録</a></td></tr>
        <tr><td><a href='download.php'>PDF・CSVダウンロード</a></td></tr>
        <tr><td><a href='outcome_csv_import.php'>アウトカムCSVデータインポート</a></td></tr>
        <tr><td><a href='avg_csv_import.php'>平均点CSVデータインポート</a></td></tr>
        <tr><td><a href='public.php'>公開設定</a></td></tr>
        <tr><td><a href='year.php'>年度変更</a></td></tr>
        <!-- <tr><td><a href='nextyear.php'>年次更新処理</a></td></tr> --><!-- 次年度更新処理へのリンクは必要に応じて復活 -->
        <tr><th>ステータス：<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></th></tr>
    </table>

</div>

</body>
</html>
