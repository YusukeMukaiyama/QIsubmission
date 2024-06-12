<?php
/*******************************************************************
usr_reg.php
  ユーザ登録メンテナンス
*******************************************************************/

// 必要なPHPファイルをインクルード
require_once 'setup.php';

// データベースに接続
$db = Connection::connect();

// ランダムな6桁の数字のパスワードを生成
function simplepasswd(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ランダムな1文字を生成
function random_char(): string {
    $p = random_int(0, 99);
    if ($p < 20) {
        return chr(random_int(48, 57)); // 数値 0-9
    } elseif ($p < 60) {
        return chr(random_int(65, 90)); // 大文字 A-Z
    } else {
        return chr(random_int(97, 122)); // 小文字 a-z
    }
}

// ランダムなパスワード文字列を生成
function random_str(int $minSize, int $maxSize, int $no): string {
    mt_srand((float) microtime() * 1000000 / $no);
    $result = '';
    $length = random_int($minSize, $maxSize);
    for ($i = 0; $i < $length; $i++) {
        $char = random_char();
        if (in_array($char, ['9', 'q', '0', 'O', 'I', 'l', '1'])) {
            $i--;
            continue;
        }
        $result .= $char;
    }
    return $result;
}

// ユーザIDの形式チェック
function check_uid(mysqli $db, string $uid, string $year): string {
    if (!preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
        return "「ユーザID:{$uid}」の登録形式に誤りがあります。<br>\n";
    }
    if (!preg_match("/^{$year}-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
        return "「ユーザID:{$uid}」の登録年度に誤りがあります。<br>\n";
    }

    $tmp = explode("-", $uid);
    $pref = (int)$tmp[1];
    if ($pref < 1 || $pref > 47) {
        return "「ユーザID:{$uid}」の登録形式に誤りがあります。<br>\n";
    }

    $stmt = $db->prepare("SELECT uid FROM usr WHERE uid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 0) {
        return "「ユーザID:{$uid}」は既に登録されています。<br>\n";
    }

    $stmt->close();

    return "";
}

// 年度取得
function getYear(mysqli $db): string {
    $sql = "SELECT year FROM year";
    $res = $db->query($sql);
    $fld = $res->fetch_object();
    return $fld->year;
}

$errorMsg = '';
$cnt = 0;
$registedList = [];
$uids = explode("\n", $_POST['uid'] ?? '');
$year = getYear($db);

foreach ($uids as $uid) {
    $uid = trim($uid);

    if ($uid === "") continue;

    $errorMsg = check_uid($db, $uid, $year);
    if ($errorMsg !== "") break;

    $cnt++;
    $no = substr($uid, 14, 3);

    if ($no === "000") {
        $category_id = 1;
    } elseif ($no >= "001" && $no <= "050") {
        $category_id = 2;
    } else {
        $category_id = 3;
    }

    $pass = simplepasswd();

    $stmt = $db->prepare("INSERT INTO usr (id, uid, pass, comp, del) VALUES (?, ?, ?, 'UNCOMPLETE', 'ENABLE')");
    $stmt->bind_param("iss", $category_id, $uid, $pass);
    $stmt->execute();
    $stmt->close();

    $registedList[] = [$uid, $pass];
}
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="admin.css"> 
    <title>ユーザ登録</title>
</head>
<body>
    <div align="center">
        <h1>QIシステム</h1>
        <form method="POST" action="usr_reg.php">
            <table cellspacing="1" cellpadding="5">
                <tr><th><a href="index.php">メニュー</a> ≫ ユーザ登録</th></tr>
                <tr>
                    <td>
                        <?php if (!empty($errorMsg)): ?>
                            <div class="error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <p>
                            ユーザIDを入力し登録ボタンをクリックして下さい。<br>
                            ※ユーザIDは一度に複数登録できます。<br>
                            ※<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>年度以外の(<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>で始まらない)ユーザIDは登録できません。<br>
                            　登録する場合は先に年度変更を行ってください。<br>
                        </p>
                        <div><input type="submit" name="regist" value="登録"></div>
                        <textarea name="uid" rows="20" cols="30"><?= htmlspecialchars($_POST['uid'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </td>
                </tr>
            </table>
            <?php if ($cnt > 0): ?>
                <p>下記のユーザを登録しました。</p>
                <table cellspacing="1" cellpadding="5">
                    <tr><th>ID</th><th>パスワード</th></tr>
                    <?php foreach ($registedList as $value): ?>
                        <tr><td><?= htmlspecialchars($value[0], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($value[1], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
