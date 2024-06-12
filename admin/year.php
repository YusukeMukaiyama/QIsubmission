<?php
/*******************************************************************
year.php
*******************************************************************/
require_once("setup.php");

$db = Connection::connect(); // データベース接続

// 年度更新関数
function updateYear($db, $year)
{
    // 正規表現で年度形式をチェック
    if (!preg_match("/^([0-9]{2})$/", $year)) {
        return "「年度:".$year."」の登録形式に誤りがあります。<br>\n";
    }

    // 年度更新（プリペアドステートメントを使用）
    $stmt = $db->prepare("DELETE FROM year");
    $stmt->execute();
    
    $stmt = $db->prepare("INSERT INTO year(year) VALUES(?)");
    $stmt->bind_param("s", $year);
    $stmt->execute();

    return "変更登録が完了しました";
}

// 年度取得関数
function getYear($db)
{
    $stmt = $db->prepare("SELECT year FROM year");
    $stmt->execute();
    $result = $stmt->get_result();
    $fld = $result->fetch_object();
    return $fld->year;
}

// 公開 / 非公開取得
$stmt = $db->prepare("SELECT pub FROM public");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("データの取得に失敗しました");
}
$fld = $result->fetch_object();
$public = $fld->pub;

$errorMsg = "";
if ($public != Config::OPEN && isset($_POST['regist']) && !empty($_POST['year'])) {
    $errorMsg = updateYear($db, $_POST['year']);
}
$year = getYear($db);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./admin.css" media="all">
    <title>年度変更</title>
</head>
<body>
<div align='center'>
    <h1>QIシステム</h1>
    <form method='POST' action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
        <table cellspacing="1" cellpadding="5">
            <tr><th><a href="index.php">メニュー</a> ≫ 年度変更</th></tr>
            <tr><td>
                <?php if (!empty($errorMsg)) echo ErrorHandling::DispErrMsg($errorMsg); ?>
                <p>
                    新しい年度を入力し登録ボタンをクリックして下さい。<br>
                    ※年度を変更した場合、新しい年度以外のユーザを登録することはできません<br>
                    ※この操作によって古いデータが削除されることはありません<br>
                </p>
                <?php if ($public == Config::OPEN) { ?>
                    <div>
                        <input type="text" name="year" size="6" value="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>">年度<br>
                        公開中のため設定変更できません。
                    </div>
                <?php } else { ?>
                    <div>
                        <input type="text" name="year" size="6" value="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>">年度　
                        <input type="submit" name="regist" value="   登録   ">
                    </div>
                <?php } ?>
            </td></tr>
        </table>
    </form>
</div>
</body>
</html>
