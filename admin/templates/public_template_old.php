<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <title>公開設定</title>
</head>
<body>

<div class="center-align">

    <h1>QIシステム</h1>

    <form method='POST' action='public.php'>
        <table cellspacing='1' cellpadding='5'>
            <tr>
                <th><a href='./index.php'>メニュー</a> ≫ 公開設定</th>
            </tr>
            <tr>
                <td>
                    <!-- 公開/非公開の選択ラジオボタン -->
                    <input type='radio' name='pub' value='1' <?= ($publicStatus ==  1) ? "checked" : "" ?>> 公開する<br>
                    <input type='radio' name='pub' value='2' <?= ($publicStatus == 2) ? "checked" : "" ?>> 非公開にする(メンテナンス)<br>
                    <p>配点の変更、年次更新処理は非公開時のみ可能です。</p>

                    <!-- エラーメッセージが存在する場合の表示 -->
                    <?php if ($errorMessage) : ?>
                        <div class="error-message">配点チェックに不整合があるため公開できません。<br><?= nl2br($errorMessage) ?></div>
                    <?php else : ?>
                        <!-- 設定ボタン -->
                        <input type='submit' name='set' value='   設定   '>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </form>

</div>

</body>
</html>
