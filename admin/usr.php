<?php
require_once 'setup.php';

// データベースに接続
$db = Connection::connect();

$searchResults = [];
$enumtype = 1; // デフォルト値
$UserID = '';

// 年度、都道府県、病院No、病棟Noの選択肢を取得
function getOptions($db, $query, $param = null) {
    $stmt = $db->prepare($query);
    if ($param) {
        $stmt->bind_param("s", $param);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$years = getOptions($db, "SELECT DISTINCT LEFT(uid, 2) AS itemdata FROM usr ORDER BY itemdata DESC");
$prefs = [];
$hospitals = [];
$wards = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $db->begin_transaction();
        try {
            for ($i = 0; isset($_POST['uid'.$i]); $i++) {
                $uid = $_POST['uid'.$i];
                $stmt = $db->prepare("UPDATE usr SET del = ?, comp = ? WHERE uid = ?");
                $stmt->bind_param("sss", $_POST['enable'.$i], $_POST['comp'.$i], $uid);
                $stmt->execute();

                if (isset($_POST['ureg'.$i])) {
                    $stmt = $db->prepare("DELETE FROM usr WHERE uid = ?");
                    $stmt->bind_param("s", $uid);
                    $stmt->execute();
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = "更新エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    if (isset($_POST['search']) || isset($_POST['update'])) {
        $enumtype = isset($_POST['enumtype']) ? $_POST['enumtype'] : 1;
        $UserID = isset($_POST['UserID']) ? $_POST['UserID'] : '';

        if ($enumtype == 1) {
            $sql = "";
            if (isset($_POST['year'])) $sql = $_POST['year']."-";
            if (isset($_POST['pref'])) $sql .= $_POST['pref']."-";
            if (isset($_POST['hosp'])) $sql .= $_POST['hosp']."-";
            if (isset($_POST['ward'])) $sql .= $_POST['ward']."-";
        } else {
            $sql = $UserID;
        }

        if ((!isset($_POST['update'])) && (!$sql)) {
            $errorMessage = "検索条件が指定されていません";
        } else {
            $stmt = $db->prepare("SELECT uid, pass, comp, del, lastupdate FROM usr WHERE uid LIKE ? ORDER BY uid");
            $searchTerm = $sql . '%';
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            $searchResults = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    if (!empty($_POST['year'])) {
        $yearTerm = $_POST['year'] . '-%';
        $prefs = getOptions($db, "SELECT DISTINCT SUBSTRING(uid, 4, 2) AS itemdata FROM usr WHERE uid LIKE ? ORDER BY itemdata ASC", $yearTerm);
    }

    if (!empty($_POST['year']) && !empty($_POST['pref'])) {
        $prefTerm = $_POST['year'] . '-' . $_POST['pref'] . '-%';
        $hospitals = getOptions($db, "SELECT DISTINCT SUBSTRING(uid, 7, 4) AS itemdata FROM usr WHERE uid LIKE ? ORDER BY itemdata ASC", $prefTerm);
    }

    if (!empty($_POST['year']) && !empty($_POST['pref']) && !empty($_POST['hosp'])) {
        $wardTerm = $_POST['year'] . '-' . $_POST['pref'] . '-' . $_POST['hosp'] . '-%';
        $wards = getOptions($db, "SELECT DISTINCT SUBSTRING(uid, 12, 2) AS itemdata FROM usr WHERE uid LIKE ? ORDER BY itemdata ASC", $wardTerm);
    }
}

Connection::disconnect($db);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="./admin.css" media="all">
    <title>ユーザ一覧</title>
</head>
<body>

<div align='center'>
    <h1>QIシステム</h1>

    <?php if (isset($errorMessage)): ?>
        <p style="color: red;"><?= $errorMessage ?></p>
    <?php endif; ?>

    <form method='POST' action='<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>'>
        <table cellspacing='1' cellpadding='5'>
            <tr><th><a href='index.php'>メニュー</a> ≫ ユーザ一覧</th></tr>
            <tr><td>
                <p>検索条件を指定してください。</p>
                <table cellspacing='1' cellpadding='5'>
                <tr><th rowspan='5'><input type='radio' name='enumtype' value='1'<?= $enumtype != 2 ? ' checked' : '' ?>>項目を選択して検索</th></tr>
                    <tr><th>年度</th><td>
                        <select name='year' onChange='this.form.submit();'>
                            <option value='0'<?= empty($_POST['year']) ? " selected" : "" ?>>選択して下さい</option>
                            <?php foreach ($years as $year): ?>
                                <option value='<?= $year['itemdata'] ?>'<?= $year['itemdata'] == $_POST['year'] ? ' selected' : '' ?>><?= $year['itemdata'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th>都道府県</th><td>
                        <select name='pref' onChange='this.form.submit();'>
                            <option value='0'<?= empty($_POST['pref']) ? " selected" : "" ?>>選択して下さい</option>
                            <?php foreach ($prefs as $pref): ?>
                                <option value='<?= $pref['itemdata'] ?>'<?= $pref['itemdata'] == $_POST['pref'] ? ' selected' : '' ?>><?= Config::$prefName[(int)$pref['itemdata']] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th>病院No.</th><td>
                        <select name='hosp' onChange='this.form.submit();'>
                            <option value='0'<?= empty($_POST['hosp']) ? " selected" : "" ?>>選択して下さい</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value='<?= $hospital['itemdata'] ?>'<?= $hospital['itemdata'] == $_POST['hosp'] ? ' selected' : '' ?>><?= $hospital['itemdata'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th>病棟No.</th><td>
                        <select name='ward' onChange='this.form.submit();'>
                            <option value='0'<?= empty($_POST['ward']) ? " selected" : "" ?>>選択して下さい</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value='<?= $ward['itemdata'] ?>'<?= $ward['itemdata'] == $_POST['ward'] ? ' selected' : '' ?>><?= $ward['itemdata'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td></tr>
                    <tr><th><input type='radio' name='enumtype' value='2'<?= $enumtype == 2 ? ' checked' : '' ?>>IDを指定して検索</th><th>ID</th>
                    <td><input size='25' type='text' maxlength='17' name='UserID' value='<?= htmlspecialchars($UserID, ENT_QUOTES, 'UTF-8') ?>' onKeyDown='if (event.keyCode == 13) { this.form.submit(); return false; }'></td></tr>
                    <tr><td colspan='3'><div align='right' style='margin:5px;'><input type='submit' name='search' value='検索'></div></td></tr>
                </table>

                <?php if (!empty($searchResults)): ?>
                    <p>検索結果 <input type='submit' name='back' value='≪戻る'></p>
                    <table cellspacing='1' cellpadding='5'>
                        <tr><th>ユーザID</th><th>パスワード</th><th>回答状況</th><th>ステータス</th><th>削除</th></tr>
                        <?php foreach ($searchResults as $i => $user): ?>
                            <tr>
                                <td><a href='./list.php?uid=<?= $user['uid'] ?>' target='_blank'><?= $user['uid'] ?></a><input type='hidden' name='uid<?= $i ?>' value='<?= $user['uid'] ?>'></td>
                                <td><?= $user['pass'] ?></td>
                                <td>
                                    <?php if ($user['lastupdate'] || $user['comp'] == Config::COMPLETE): ?>
                                        <input type='radio' name='comp<?= $i ?>' value='<?= Config::COMPLETE ?>'<?= $user['comp'] == Config::COMPLETE ? ' checked' : '' ?>> 完了 
                                        <input type='radio' name='comp<?= $i ?>' value='<?= Config::UNCOMPLETE ?>'<?= $user['comp'] == Config::UNCOMPLETE ? ' checked' : '' ?>> 未完了
                                    <?php else: ?>
                                        未完了
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type='radio' name='enable<?= $i ?>' value='<?= Config::ENABLE ?>'<?= $user['del'] != Config::DISABLE ? ' checked' : '' ?>> 有効 
                                    <input type='radio' name='enable<?= $i ?>' value='<?= Config::DISABLE ?>'<?= $user['del'] == Config::DISABLE ? ' checked' : '' ?>> 無効
                                </td>
                                <td><input type='checkbox' name='ureg<?= $i ?>'>削除</td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <div align='right' style='margin:5px;'><input type='reset' name='reset' value='リセット'> <input type='submit' name='update' value='更新'></div>
                <?php endif; ?>
            </td></tr>
        </table>

        <?php if (isset($_POST['search'])): ?>
            <input type='hidden' name='year' value='<?= htmlspecialchars($_POST['year'], ENT_QUOTES, 'UTF-8') ?>'>
            <input type='hidden' name='pref' value='<?= htmlspecialchars($_POST['pref'], ENT_QUOTES, 'UTF-8') ?>'>
            <input type='hidden' name='hosp' value='<?= htmlspecialchars($_POST['hosp'], ENT_QUOTES, 'UTF-8') ?>'>
            <input type='hidden' name='ward' value='<?= htmlspecialchars($_POST['ward'], ENT_QUOTES, 'UTF-8') ?>'>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
