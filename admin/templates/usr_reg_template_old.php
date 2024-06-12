<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="admin.css"> 
    <title>ユーザ登録</title>
</head>
<body>
    <div align='center'>
        <h1>QIシステム</h1>
        <form method='POST' action='usr_reg.php'>
            <table cellspacing='1' cellpadding='5'>
                <tr><th><a href='index.php'>メニュー</a> ≫ ユーザ登録</th></tr>
                <tr>
                    <td>
                        <?php if ($errorMsg != "") echo $errorMsg; ?>
                        <p>
                            ユーザIDを入力し登録ボタンをクリックして下さい。<br>
                            ※ユーザIDは一度に複数登録できます。<br>
                            ※<?= $year ?>年度以外の(<?= $year ?>で始まらない)ユーザIDは登録できません。<br>
                            　登録する場合は先に年度変更を行ってください。<br>
                        </p>
                        <div><input type='submit' name='regist' value='　登録　'></div>
                        <textarea name='uid' rows='20' cols='30'><?= isset($_POST['uid']) ? $_POST['uid'] : '' ?></textarea>
                    </td>
                </tr>
            </table>
            <?php if ($cnt > 0): ?>
                <p>下記のユーザを登録しました。</p>
                <table cellspacing='1' cellpadding='5'>
                    <tr><th>ID</th><th>パスワード</th></tr>
                    <?php foreach ($registedList as $value): ?>
                        <tr><td><?= $value[0] ?></td><td><?= $value[1] ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
